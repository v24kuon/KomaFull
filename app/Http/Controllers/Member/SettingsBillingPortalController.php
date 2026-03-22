<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SettingsBillingPortalController extends Controller
{
    /**
     * Stripe Customer Portal へリダイレクトし、カード情報の更新・削除を行う。
     *
     * 前提: Cashier の Stripe Customer が存在すること（未作成時は作成する）。
     * 副作用: Stripe API を呼び出しセッション URL へリダイレクトする。
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        if ($user->memberProfile === null) {
            return redirect()
                ->route('member.dashboard')
                ->with('error', '会員プロフィールがまだありません。メール認証完了後に自動で作成されます。');
        }

        $user->createOrGetStripeCustomer();

        return $user->redirectToBillingPortal(route('member.settings.index'));
    }
}
