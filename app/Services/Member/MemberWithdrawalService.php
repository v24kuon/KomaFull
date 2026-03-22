<?php

namespace App\Services\Member;

use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Database\ConnectionInterface;
use Laravel\Cashier\Subscription;

class MemberWithdrawalService
{
    public function __construct(
        private readonly ConnectionInterface $connection,
    ) {}

    /**
     * 会員を退会状態にし、有効なサブスクがあれば即時解約する。
     *
     * 前提: 呼び出し元で現在パスワード認証済みであること。
     * 更新方針: `member_profiles` のみ更新（`users` は削除しない）。サブスクは `Subscription::active()` 相当で即時解約する。
     *
     * 副作用: Stripe API 呼び出し（各 `cancelNow()`）。
     */
    public function withdraw(User $user): void
    {
        $this->connection->transaction(function () use ($user): void {
            $profile = $user->memberProfile;
            if (! $profile instanceof MemberProfile) {
                return;
            }

            if ($profile->member_status === MemberProfile::STATUS_WITHDRAWN) {
                return;
            }

            $user->subscriptions->each(function (Subscription $subscription): void {
                if ($subscription->active()) {
                    $subscription->cancelNow();
                }
            });

            $profile->update([
                'member_status' => MemberProfile::STATUS_WITHDRAWN,
                'withdrawn_at' => now(),
            ]);
        });
    }
}
