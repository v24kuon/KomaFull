<?php

namespace App\Services\Member;

use App\Models\BalanceTransaction;
use App\Models\CourseEntitlement;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * 会員マイページ用の予約・残高サマリを組み立てる。
 */
final class MemberDashboardSummary
{
    /**
     * @return array{
     *     ticket_balance: int,
     *     point_balance: int,
     *     subscription_entitlements: Collection<int, CourseEntitlement>,
     *     upcoming_reservations: Collection<int, Reservation>,
     * }
     */
    public function build(User $user): array
    {
        $userId = $user->getKey();

        $balances = BalanceTransaction::query()
            ->where('user_id', $userId)
            ->selectRaw('unit, SUM(amount) as total')
            ->groupBy('unit')
            ->get()
            ->keyBy('unit');

        $ticketBalance = (int) ($balances->get(BalanceTransaction::UNIT_TICKETS)?->total ?? 0);
        $pointBalance = (int) ($balances->get(BalanceTransaction::UNIT_POINTS)?->total ?? 0);

        $subscriptionEntitlements = CourseEntitlement::query()
            ->where('user_id', $userId)
            ->whereDate('period_start', '<=', now()->toDateString())
            ->whereDate('period_end', '>=', now()->toDateString())
            ->with('coursePlan')
            ->orderBy('period_end')
            ->get();

        $upcomingReservations = Reservation::query()
            ->where('reservations.user_id', $userId)
            ->where('reservations.status', Reservation::STATUS_CONFIRMED)
            ->join('lesson_sessions', 'reservations.lesson_session_id', '=', 'lesson_sessions.id')
            ->where('lesson_sessions.starts_at', '>=', now())
            ->orderBy('lesson_sessions.starts_at')
            ->select('reservations.*')
            ->with(['lessonSession.program', 'lessonSession.location'])
            ->limit(20)
            ->get();

        return [
            'ticket_balance' => $ticketBalance,
            'point_balance' => $pointBalance,
            'subscription_entitlements' => $subscriptionEntitlements,
            'upcoming_reservations' => $upcomingReservations,
        ];
    }
}
