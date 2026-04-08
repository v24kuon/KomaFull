<?php

namespace App\Services\Booking;

use App\Models\BalanceTransaction;
use App\Models\CourseEntitlement;
use App\Models\CourseEntitlementItem;
use App\Models\CoursePlan;
use App\Models\CoursePlanCategory;
use App\Models\LessonSession;
use App\Models\MemberProfile;
use App\Models\Program;
use App\Models\Reservation;
use App\Models\ReservationManagement;
use App\Models\User;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

/**
 * 本会員の通常予約を、カウンタと消費元（サブスク枠 / 回数券 / ポイント）を同一トランザクションで確定する。
 *
 * ロック順序: `reservation_management` →（サブスク）`course_entitlements` /（回数券・ポイント）`member_profiles`
 */
final class NormalReservationBookingService
{
    public function __construct(private readonly ConnectionInterface $connection) {}

    /**
     * @param  'subscription'|'tickets'|'points'  $paymentMethod
     */
    public function book(
        User $user,
        LessonSession $lessonSession,
        string $paymentMethod,
        ?int $courseEntitlementId = null
    ): Reservation {
        return $this->connection->transaction(function () use (
            $user,
            $lessonSession,
            $paymentMethod,
            $courseEntitlementId
        ): Reservation {
            $lessonSession->load('program');

            if ($lessonSession->status !== LessonSession::STATUS_ACTIVE) {
                throw new RuntimeException('この開催枠は予約を受け付けていません。');
            }

            if ($lessonSession->starts_at !== null && $lessonSession->starts_at->isPast()) {
                throw new RuntimeException('過去の開催枠は予約できません。');
            }

            $program = $lessonSession->program;

            if (! $program instanceof Program) {
                throw new RuntimeException('プログラム情報を取得できません。');
            }

            ReservationManagement::query()->createOrFirst(
                ['lesson_session_id' => $lessonSession->getKey()],
                [
                    'reserved_count' => 0,
                    'reserved_trial_count' => 0,
                ]
            );

            $management = ReservationManagement::query()
                ->where('lesson_session_id', $lessonSession->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if ($management->reserved_count >= $lessonSession->capacity) {
                throw new RuntimeException('一般枠が満席のため予約できません。');
            }

            $memberProfileRow = MemberProfile::query()
                ->where('user_id', $user->getKey())
                ->first();

            if (! $memberProfileRow instanceof MemberProfile || $memberProfileRow->member_status !== MemberProfile::STATUS_ACTIVE) {
                throw new RuntimeException('本会員のみ通常予約できます。');
            }

            $ticketCost = max(0, (int) $program->ticket_cost);
            $pointCost = max(0, (int) $program->point_cost);

            $courseEntitlementIdToSave = null;

            if ($paymentMethod === Reservation::PAYMENT_METHOD_SUBSCRIPTION) {
                if ($courseEntitlementId === null) {
                    throw new InvalidArgumentException('サブスク枠を選択してください。');
                }

                $this->consumeSubscriptionSlot(
                    userId: (int) $user->getKey(),
                    lessonSession: $lessonSession,
                    program: $program,
                    courseEntitlementId: $courseEntitlementId
                );

                $courseEntitlementIdToSave = $courseEntitlementId;
            } elseif ($paymentMethod === Reservation::PAYMENT_METHOD_TICKETS) {
                if ($ticketCost < 1) {
                    throw new RuntimeException('このプログラムは回数券での予約に対応していません。');
                }

                MemberProfile::query()
                    ->where('user_id', $user->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertBalanceCovers((int) $user->getKey(), BalanceTransaction::UNIT_TICKETS, $ticketCost);
            } elseif ($paymentMethod === Reservation::PAYMENT_METHOD_POINTS) {
                if ($pointCost < 1) {
                    throw new RuntimeException('このプログラムはポイントでの予約に対応していません。');
                }

                MemberProfile::query()
                    ->where('user_id', $user->getKey())
                    ->lockForUpdate()
                    ->firstOrFail();

                $this->assertBalanceCovers((int) $user->getKey(), BalanceTransaction::UNIT_POINTS, $pointCost);
            } else {
                throw new InvalidArgumentException('未対応の支払い方法です。');
            }

            $reservation = Reservation::query()->create([
                'code' => 'R'.strtoupper((string) Str::ulid()),
                'user_id' => $user->getKey(),
                'lesson_session_id' => $lessonSession->getKey(),
                'seat_bucket' => Reservation::SEAT_BUCKET_NORMAL,
                'payment_method' => $paymentMethod,
                'status' => Reservation::STATUS_CONFIRMED,
                'ticket_cost' => $paymentMethod === Reservation::PAYMENT_METHOD_TICKETS ? $ticketCost : 0,
                'point_cost' => $paymentMethod === Reservation::PAYMENT_METHOD_POINTS ? $pointCost : 0,
                'course_entitlement_id' => $courseEntitlementIdToSave,
            ]);

            if ($paymentMethod === Reservation::PAYMENT_METHOD_TICKETS) {
                $this->recordConsumeTransaction(
                    user: $user,
                    unit: BalanceTransaction::UNIT_TICKETS,
                    amount: -$ticketCost,
                    reservation: $reservation
                );
            }

            if ($paymentMethod === Reservation::PAYMENT_METHOD_POINTS) {
                $this->recordConsumeTransaction(
                    user: $user,
                    unit: BalanceTransaction::UNIT_POINTS,
                    amount: -$pointCost,
                    reservation: $reservation
                );
            }

            $management->increment('reserved_count');

            return $reservation->fresh(['lessonSession.program']);
        });
    }

    private function consumeSubscriptionSlot(
        int $userId,
        LessonSession $lessonSession,
        Program $program,
        int $courseEntitlementId
    ): void {
        $now = now();

        $entitlement = CourseEntitlement::query()
            ->whereKey($courseEntitlementId)
            ->where('user_id', $userId)
            ->whereDate('period_start', '<=', $now->toDateString())
            ->whereDate('period_end', '>=', $now->toDateString())
            ->with('coursePlan')
            ->lockForUpdate()
            ->first();

        if (! $entitlement instanceof CourseEntitlement) {
            throw new RuntimeException('選択したサブスク枠が無効です。');
        }

        $plan = $entitlement->coursePlan;

        if (! $plan instanceof CoursePlan || $plan->status !== CoursePlan::STATUS_ACTIVE) {
            throw new RuntimeException('プランが有効ではありません。');
        }

        if ($plan->allocation_type === CoursePlan::ALLOCATION_TYPE_TOTAL) {
            if ($entitlement->used_uses >= $entitlement->granted_uses) {
                throw new RuntimeException('サブスク枠の残回数がありません。');
            }

            $entitlement->increment('used_uses');

            return;
        }

        if ($plan->allocation_type === CoursePlan::ALLOCATION_TYPE_PER_CATEGORY) {
            $allowed = CoursePlanCategory::query()
                ->where('course_plan_id', $plan->getKey())
                ->where('category_id', $program->category_id)
                ->exists();

            if (! $allowed) {
                throw new RuntimeException('このプログラムのカテゴリはプラン対象外です。');
            }

            $item = CourseEntitlementItem::query()
                ->where('course_entitlement_id', $entitlement->getKey())
                ->where('category_id', $program->category_id)
                ->lockForUpdate()
                ->first();

            if (! $item instanceof CourseEntitlementItem) {
                throw new RuntimeException('カテゴリ別枠が見つかりません。');
            }

            if ($item->used_uses >= $item->granted_uses) {
                throw new RuntimeException('このカテゴリのサブスク枠の残回数がありません。');
            }

            $item->increment('used_uses');

            return;
        }

        throw new RuntimeException('未対応の割当方式です。');
    }

    private function assertBalanceCovers(int $userId, string $unit, int $cost): void
    {
        $balance = $this->sumBalance($userId, $unit);

        if ($balance < $cost) {
            throw new RuntimeException('残高が不足しています。');
        }
    }

    private function recordConsumeTransaction(
        User $user,
        string $unit,
        int $amount,
        Reservation $reservation
    ): void {
        BalanceTransaction::query()->create([
            'user_id' => $user->getKey(),
            'unit' => $unit,
            'amount' => $amount,
            'transaction_type' => BalanceTransaction::TYPE_CONSUME,
            'idempotency_key' => sprintf(
                'booking-%s-%d-%d',
                $unit,
                $reservation->getKey(),
                $user->getKey()
            ),
            'prepaid_purchase_id' => null,
            'reservation_id' => $reservation->getKey(),
            'stripe_reference_id' => null,
            'occurred_at' => now(),
            'expires_at' => null,
        ]);
    }

    private function sumBalance(int $userId, string $unit): int
    {
        $total = BalanceTransaction::query()
            ->where('user_id', $userId)
            ->where('unit', $unit)
            ->sum('amount');

        return (int) $total;
    }
}
