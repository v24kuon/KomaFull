<?php

namespace App\Services\Member;

use App\Models\BalanceTransaction;
use App\Models\CourseEntitlement;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * 会員マイページ用の予約・残高サマリを組み立てる。
 *
 * 読み取り専用のクエリのみを実行し、永続化やキュー投入などの副作用を持たない。
 */
final class MemberDashboardSummary
{
    /**
     * 指定ユーザーのマイページ表示用サマリを集計する。
     *
     * 責務: `balance_transactions` の残高集計、現行周期の `course_entitlements`、今後開催分の確定 `reservations`
     * を Eloquent 経由で読み取り、ビュー向けの連想配列にまとめる。
     *
     * 副作用: なし（INSERT/UPDATE/DELETE は行わない）。
     *
     * トランザクション境界: 本メソッドは DB トランザクションを開始しない。複数 SELECT のあいだに他処理がコミットされうるため、
     * 厳密な単一スナップショットとしての整合性は保証しない（ダッシュボード表示用途で許容する）。
     *
     * 冪等性: 副作用がないため「再実行で状態が変わらない」という意味での冪等性は該当しない。
     * 同一 `User`・同一 DB 状態であれば結果は再現可能（参照の読み取り）。
     *
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
        $now = now();

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
            ->whereDate('period_start', '<=', $now->toDateString())
            ->whereDate('period_end', '>=', $now->toDateString())
            ->with('coursePlan')
            ->orderBy('period_end')
            ->get();

        $upcomingReservations = Reservation::query()
            ->where('reservations.user_id', $userId)
            ->where('reservations.status', Reservation::STATUS_CONFIRMED)
            ->join('lesson_sessions', 'reservations.lesson_session_id', '=', 'lesson_sessions.id')
            ->where('lesson_sessions.starts_at', '>=', $now)
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
