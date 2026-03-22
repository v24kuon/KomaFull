<?php

namespace App\Services\Member;

use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Database\ConnectionInterface;
use Laravel\Cashier\Subscription;

/**
 * 会員退会（プロフィール更新と Stripe 即時解約）を扱う。
 *
 * Lock strategy:
 * - 退会確定の更新は `member_profiles` 行に対して `lockForUpdate()` で悲観ロックし、同一会員の並行リクエストが最終行更新を直列化する。
 * - Stripe API はネットワーク I/O のためロック保持中には呼ばない（下記トランザクション境界）。
 *
 * Transaction boundaries:
 * - `cancelNow()` は Stripe 側の不可逆副作用のため `ConnectionInterface::transaction()` の外で実行する。
 * - DB 更新は `member_profiles` のみを `connection->transaction()` 内で行う。
 *
 * Idempotency handling:
 * - 先頭の `member_status === withdrawn` で早期 return。
 * - トランザクション内で再取得した行が既に withdrawn なら更新しない（再試行・並行完了後の二重更新を避ける）。
 * - サブスクは `active()` が false なら `cancelNow()` を呼ばない（Stripe 側が既に解約済みの再試行）。
 *
 * Residual risk / operations:
 * - `cancelNow()` 成功後に DB トランザクションが失敗すると、一時的に Stripe 側は解約済みだが `member_profiles` が未退会のまま残る。
 *   再試行では `active()` が false のため二重解約は起きず、DB が利用可能になればトランザクションで withdrawn へ収束する。
 *   長期に不整合が残るケースの検知はアプリ外の運用監視（例: サブスク状態と DB の突合）で検討する余地がある。
 */
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
     * 副作用: Stripe API 呼び出し（各 `cancelNow()`）。`cancelNow()` は Stripe API の後に Cashier が subscriptions 行を更新する。
     * ロールバック不能な Stripe 副作用のため、これらは DB トランザクション内で実行しない（RFP-009）。
     * `member_profiles` の更新のみトランザクションで行い、その中で `lockForUpdate()` により退会行の更新を直列化する。
     */
    public function withdraw(User $user): void
    {
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

        $this->connection->transaction(function () use ($profile): void {
            $locked = MemberProfile::query()
                ->whereKey($profile->getKey())
                ->lockForUpdate()
                ->first();

            if ($locked === null || $locked->member_status === MemberProfile::STATUS_WITHDRAWN) {
                return;
            }

            $locked->update([
                'member_status' => MemberProfile::STATUS_WITHDRAWN,
                'withdrawn_at' => now(),
            ]);
        });
    }
}
