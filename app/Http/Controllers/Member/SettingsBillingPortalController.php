<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class SettingsBillingPortalController extends Controller
{
    /**
     * Stripe Customer Portal へリダイレクトし、カード情報の更新・削除を行う。
     *
     * 前提: Cashier の Stripe Customer が存在すること（未作成時は作成する）。
     * 副作用: Stripe API を呼び出しセッション URL へリダイレクトする。
     *
     * 外部 API 失敗時はログに記録し、会員設定へフラッシュエラーで戻す（未処理例外による 500 を避ける）。
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

        try {
            $user->createOrGetStripeCustomer();

            return $user->redirectToBillingPortal(route('member.settings.index'));
        } catch (Throwable $e) {
            Log::error('Stripe billing portal session failed', [
                'user_id' => $user->getKey(),
                'exception' => $e,
            ]);

            return redirect()
                ->route('member.settings.index')
                ->with('error', 'お支払い情報の画面を開けませんでした。時間をおいて再度お試しください。');
        }
    }
}
