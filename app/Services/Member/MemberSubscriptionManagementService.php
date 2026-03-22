<?php

namespace App\Services\Member;

use App\Models\CoursePlan;
use App\Models\User;
use Illuminate\Support\Collection;
use Laravel\Cashier\Subscription;

/**
 * 会員向けサブスクリプション（プラン変更・請求期間末解約・解約取り消し）を扱う。
 *
 * 副作用: Stripe API を呼び出す（Cashier の `swap` / `cancel` / `resume`）。失敗時は例外を投げる。
 *
 * 前提: Cashier の `type=default` サブスクを利用すること（本アプリのWebhook・テストと整合）。
 */
class MemberSubscriptionManagementService
{
    /**
     * `course_plans.stripe_price_id` から表示用プランを解決する（単一価格サブスク想定）。
     *
     * まず `status=active` の行を返し、無い場合は同一 `stripe_price_id` の非アクティブ行も返す。
     * 管理者がプランを無効化したあとも、契約中ユーザーにはマスタ上のプラン名を示すため。
     * 切替候補は {@see swapCandidates} が active のみを対象とする。
     */
    public function resolveCoursePlan(?Subscription $subscription): ?CoursePlan
    {
        if ($subscription === null) {
            return null;
        }

        $priceId = $subscription->stripe_price;
        if ($priceId === null && $subscription->relationLoaded('items') === false) {
            $subscription->load('items');
        }

        if ($priceId === null && $subscription->items->isNotEmpty()) {
            $priceId = $subscription->items->first()->stripe_price;
        }

        if ($priceId === null || $priceId === '') {
            return null;
        }

        $active = CoursePlan::query()
            ->where('stripe_price_id', $priceId)
            ->where('status', CoursePlan::STATUS_ACTIVE)
            ->first();

        if ($active !== null) {
            return $active;
        }

        return CoursePlan::query()
            ->where('stripe_price_id', $priceId)
            ->first();
    }

    /**
     * 切替プラン候補（有効かつ Stripe Price が付いているマスタ）。
     *
     * @return Collection<int, CoursePlan>
     */
    public function swapCandidates(?string $currentStripePriceId): Collection
    {
        $query = CoursePlan::query()
            ->where('status', CoursePlan::STATUS_ACTIVE)
            ->whereNotNull('stripe_price_id')
            ->orderBy('name');

        if ($currentStripePriceId !== null && $currentStripePriceId !== '') {
            $query->where('stripe_price_id', '!=', $currentStripePriceId);
        }

        return $query->get();
    }

    /**
     * プラン変更（Stripe の price へ swap）が可能か。
     */
    public function canSwap(Subscription $subscription): bool
    {
        return $this->subscriptionEligibleForSwapOrCancelAtPeriodEnd($subscription);
    }

    /**
     * 請求期間末の解約予約が可能か（未解約のうち）。
     */
    public function canCancelAtPeriodEnd(Subscription $subscription): bool
    {
        return $this->subscriptionEligibleForSwapOrCancelAtPeriodEnd($subscription);
    }

    /**
     * プラン変更と請求期間末解約の共通前提。現時点では同一条件。
     *
     * ビジネス上は別概念のため公開メソッドは分離したままとし、条件が分岐したら本メソッドを廃止して各 {@see canSwap} / {@see canCancelAtPeriodEnd} に実装を分ける。
     */
    private function subscriptionEligibleForSwapOrCancelAtPeriodEnd(Subscription $subscription): bool
    {
        if ($subscription->hasIncompletePayment()) {
            return false;
        }

        return $subscription->active() && ! $subscription->canceled();
    }

    /**
     * 解約取り消し（猶予期間内）が可能か。
     */
    public function canResume(Subscription $subscription): bool
    {
        return $subscription->onGracePeriod();
    }

    /**
     * 現在の Stripe Price ID（単一価格想定）。
     */
    public function currentStripePriceId(Subscription $subscription): ?string
    {
        if ($subscription->stripe_price !== null) {
            return $subscription->stripe_price;
        }

        if ($subscription->relationLoaded('items') === false) {
            $subscription->load('items');
        }

        if ($subscription->items->isEmpty()) {
            return null;
        }

        return $subscription->items->first()->stripe_price;
    }

    /**
     * プランを変更する。
     *
     * @throws \InvalidArgumentException 対象プランが無効な場合
     * @throws \Laravel\Cashier\Exceptions\IncompletePayment
     * @throws \Laravel\Cashier\Exceptions\SubscriptionUpdateFailure
     */
    public function swapToPrice(User $user, string $stripePriceId): void
    {
        $subscription = $user->subscription('default');
        if ($subscription === null) {
            throw new \InvalidArgumentException('Subscription not found.');
        }

        if (! $this->canSwap($subscription)) {
            throw new \InvalidArgumentException('Subscription cannot be swapped.');
        }

        if ($subscription->hasPrice($stripePriceId)) {
            throw new \InvalidArgumentException('Already on this price.');
        }

        $plan = CoursePlan::query()
            ->where('stripe_price_id', $stripePriceId)
            ->where('status', CoursePlan::STATUS_ACTIVE)
            ->first();

        if ($plan === null) {
            throw new \InvalidArgumentException('Invalid course plan.');
        }

        $subscription->swap($stripePriceId);
    }

    /**
     * 請求期間末に解約するよう予約する。
     *
     * @throws \InvalidArgumentException 解約できない状態の場合
     */
    public function cancelAtPeriodEnd(User $user): void
    {
        $subscription = $user->subscription('default');
        if ($subscription === null) {
            throw new \InvalidArgumentException('Subscription not found.');
        }

        if (! $this->canCancelAtPeriodEnd($subscription)) {
            throw new \InvalidArgumentException('Subscription cannot be canceled.');
        }

        $subscription->cancel();
    }

    /**
     * 解約予約を取り消す（猶予期間内）。
     *
     * @throws \InvalidArgumentException 再開できない状態の場合
     * @throws \LogicException Cashier が再開を拒否した場合
     */
    public function resume(User $user): void
    {
        $subscription = $user->subscription('default');
        if ($subscription === null) {
            throw new \InvalidArgumentException('Subscription not found.');
        }

        if (! $this->canResume($subscription)) {
            throw new \InvalidArgumentException('Subscription cannot be resumed.');
        }

        $subscription->resume();
    }
}
