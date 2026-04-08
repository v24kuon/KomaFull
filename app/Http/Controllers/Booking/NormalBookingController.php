<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreNormalBookingRequest;
use App\Models\CoursePlan;
use App\Models\LessonSession;
use App\Models\MemberProfile;
use App\Models\Program;
use App\Models\Reservation;
use App\Services\Booking\NormalReservationBookingService;
use App\Services\Member\MemberDashboardSummary;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class NormalBookingController extends Controller
{
    /**
     * 通常予約: 消費元の選択と確認。
     */
    public function show(LessonSession $lessonSession, MemberDashboardSummary $summary): View|RedirectResponse
    {
        $user = request()->user();

        if ($user === null) {
            abort(403);
        }

        $lessonSession->load(['program.category', 'location', 'staff', 'reservationManagement']);

        $profile = MemberProfile::query()->where('user_id', $user->getKey())->first();

        if (! $profile instanceof MemberProfile || $profile->member_status !== MemberProfile::STATUS_ACTIVE) {
            return redirect()
                ->route('schedule.index', ['year' => now()->year, 'month' => now()->month])
                ->with('error', '通常予約は本会員の方のみご利用いただけます。');
        }

        if ($lessonSession->status !== LessonSession::STATUS_ACTIVE) {
            abort(404);
        }

        if ($lessonSession->starts_at !== null && $lessonSession->starts_at->isPast()) {
            return redirect()
                ->route('schedule.index', ['year' => (int) $lessonSession->starts_at->year, 'month' => (int) $lessonSession->starts_at->month])
                ->with('error', 'この開催枠は既に開始済みのため予約できません。');
        }

        $program = $lessonSession->program;

        if (! $program instanceof Program || $program->status !== Program::STATUS_ACTIVE) {
            abort(404);
        }

        if ($this->normalRemaining($lessonSession) < 1) {
            return redirect()
                ->route('schedule.index', ['year' => (int) $lessonSession->starts_at->year, 'month' => (int) $lessonSession->starts_at->month])
                ->with('error', '一般枠が満席のため予約できません。');
        }

        $dash = $summary->build($user);

        $dash['subscription_entitlements']->load(['coursePlan', 'items']);

        $subscriptionOptions = $this->subscriptionOptionsForProgram(
            $dash['subscription_entitlements'],
            $program
        );

        return view('pages.booking.normal.show', [
            'lessonSession' => $lessonSession,
            'normalRemaining' => $this->normalRemaining($lessonSession),
            'ticketBalance' => $dash['ticket_balance'],
            'pointBalance' => $dash['point_balance'],
            'subscriptionOptions' => $subscriptionOptions,
            'program' => $program,
        ]);
    }

    /**
     * @param  \Illuminate\Support\Collection<int, \App\Models\CourseEntitlement>  $entitlements
     * @return list<array{course_entitlement_id: int, label: string}>
     */
    private function subscriptionOptionsForProgram($entitlements, Program $program): array
    {
        $options = [];

        foreach ($entitlements as $entitlement) {
            $plan = $entitlement->coursePlan;

            if (! $plan instanceof CoursePlan) {
                continue;
            }

            if ($plan->allocation_type === CoursePlan::ALLOCATION_TYPE_TOTAL) {
                $remaining = $entitlement->granted_uses - $entitlement->used_uses;

                if ($remaining > 0) {
                    $options[] = [
                        'course_entitlement_id' => (int) $entitlement->getKey(),
                        'label' => sprintf('%s（残り %d 回）', $plan->name, $remaining),
                    ];
                }

                continue;
            }

            if ($plan->allocation_type === CoursePlan::ALLOCATION_TYPE_PER_CATEGORY) {
                foreach ($entitlement->items as $item) {
                    if ((int) $item->category_id !== (int) $program->category_id) {
                        continue;
                    }

                    $remaining = $item->granted_uses - $item->used_uses;

                    if ($remaining > 0) {
                        $options[] = [
                            'course_entitlement_id' => (int) $entitlement->getKey(),
                            'label' => sprintf('%s（残り %d 回・このカテゴリ）', $plan->name, $remaining),
                        ];
                    }

                    break;
                }
            }
        }

        return $options;
    }

    /**
     * 通常予約の確定。
     */
    public function store(StoreNormalBookingRequest $request, NormalReservationBookingService $bookingService): RedirectResponse
    {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $validated = $request->validated();

        $lessonSession = LessonSession::query()
            ->with('program')
            ->findOrFail((int) $validated['lesson_session_id']);

        $profile = MemberProfile::query()->where('user_id', $user->getKey())->first();

        if (! $profile instanceof MemberProfile || $profile->member_status !== MemberProfile::STATUS_ACTIVE) {
            return redirect()
                ->route('schedule.index', ['year' => now()->year, 'month' => now()->month])
                ->with('error', '通常予約は本会員の方のみご利用いただけます。');
        }

        $paymentMethod = (string) $validated['payment_method'];

        $map = [
            'subscription' => Reservation::PAYMENT_METHOD_SUBSCRIPTION,
            'tickets' => Reservation::PAYMENT_METHOD_TICKETS,
            'points' => Reservation::PAYMENT_METHOD_POINTS,
        ];

        $reservationPayment = $map[$paymentMethod] ?? null;

        if ($reservationPayment === null) {
            return back()->withInput()->withErrors(['payment_method' => '支払い方法が不正です。']);
        }

        $entitlementId = isset($validated['course_entitlement_id'])
            ? (int) $validated['course_entitlement_id']
            : null;

        try {
            $reservation = $bookingService->book(
                $user,
                $lessonSession,
                $reservationPayment,
                $entitlementId
            );
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->withErrors(['payment_method' => $e->getMessage()]);
        } catch (\RuntimeException $e) {
            return back()->withInput()->withErrors(['lesson_session_id' => $e->getMessage()]);
        }

        return redirect()
            ->route('member.dashboard')
            ->with('success', sprintf('予約が確定しました（予約番号: %s）。', $reservation->code));
    }

    private function normalRemaining(LessonSession $lessonSession): int
    {
        $rm = $lessonSession->reservationManagement;
        $reserved = $rm?->reserved_count ?? 0;

        return max(0, $lessonSession->capacity - $reserved);
    }
}
