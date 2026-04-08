<?php

namespace App\Services\Booking;

use App\Models\LessonSession;
use App\Models\Reservation;
use App\Models\ReservationManagement;
use App\Models\TrialApplication;
use App\Models\User;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * 体験レッスンの現地払い申込を、定員カウンタと同一トランザクションで確定する。
 *
 * ロック順序: `trial_applications` → `reservation_management`（プランの固定順序に準拠）。
 */
final class TrialOnsiteBookingService
{
    public function __construct(private readonly ConnectionInterface $connection) {}

    /**
     * 体験枠が空いていれば `trial_applications` と `reservations` を確定し、カウンタを更新する。
     *
     * @throws RuntimeException 満席・不正なセッション状態
     */
    public function reserve(User $user, LessonSession $lessonSession): TrialApplication
    {
        return $this->connection->transaction(function () use ($user, $lessonSession): TrialApplication {
            $lessonSession->load('program');

            if ($lessonSession->status !== LessonSession::STATUS_ACTIVE) {
                throw new RuntimeException('この開催枠は予約を受け付けていません。');
            }

            if ($lessonSession->starts_at !== null && $lessonSession->starts_at->isPast()) {
                throw new RuntimeException('過去の開催枠は予約できません。');
            }

            $trialApplication = TrialApplication::query()->create([
                'user_id' => $user->getKey(),
                'lesson_session_id' => $lessonSession->getKey(),
                'payment_method' => TrialApplication::PAYMENT_METHOD_ONSITE,
                'status' => TrialApplication::STATUS_PROCESSING,
                'expires_at' => null,
            ]);

            $lockedTrial = TrialApplication::query()
                ->whereKey($trialApplication->getKey())
                ->lockForUpdate()
                ->firstOrFail();

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

            if ($management->reserved_trial_count >= $lessonSession->trial_capacity) {
                throw new RuntimeException('体験枠が満席のため予約できません。');
            }

            $reservation = Reservation::query()->create([
                'code' => 'R'.strtoupper((string) Str::ulid()),
                'user_id' => $user->getKey(),
                'lesson_session_id' => $lessonSession->getKey(),
                'seat_bucket' => Reservation::SEAT_BUCKET_TRIAL,
                'payment_method' => Reservation::PAYMENT_METHOD_TRIAL_ONSITE,
                'status' => Reservation::STATUS_CONFIRMED,
                'ticket_cost' => 0,
                'point_cost' => 0,
                'course_entitlement_id' => null,
            ]);

            $management->increment('reserved_trial_count');

            $lockedTrial->update([
                'status' => TrialApplication::STATUS_RESERVED,
                'reservation_id' => $reservation->getKey(),
            ]);

            return $lockedTrial->fresh(['reservation', 'lessonSession.program']);
        });
    }
}
