<?php

namespace App\Services\Member;

use App\Models\User;
use Illuminate\Http\RedirectResponse;

/**
 * Stripe Customer Portal（カード情報管理）へのリダイレクトを行う。
 *
 * 前提: Cashier の Stripe Customer が存在すること（未作成時は作成する）。
 * 副作用: Stripe API を呼び出しセッション URL へリダイレクトする。
 */
class MemberStripeBillingPortalService
{
    /**
     * Cashier 経由で Customer を確保し、Billing Portal セッションへリダイレクトする。
     *
     * @throws \Throwable Stripe API または Cashier が失敗した場合
     */
    public function redirectToBillingPortal(User $user, string $returnUrl): RedirectResponse
    {
        $user->createOrGetStripeCustomer();

        return $user->redirectToBillingPortal($returnUrl);
    }
}
