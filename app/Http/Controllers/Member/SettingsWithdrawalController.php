<?php

namespace App\Http\Controllers\Member;

use App\Actions\Auth\LogoutAndInvalidateSession;
use App\Http\Controllers\Controller;
use App\Http\Requests\Member\WithdrawMemberAccountRequest;
use App\Models\MemberProfile;
use App\Models\User;
use App\Services\Member\MemberWithdrawalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class SettingsWithdrawalController extends Controller
{
    public function __construct(
        private readonly MemberWithdrawalService $memberWithdrawalService,
        private readonly LogoutAndInvalidateSession $logoutAndInvalidateSession,
    ) {}

    /**
     * 退会確認画面を表示する。
     */
    public function edit(): View|RedirectResponse
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        if ($user->memberProfile === null) {
            return redirect()
                ->route('member.dashboard')
                ->with('error', MemberProfile::FLASH_ERROR_MISSING_PROFILE_VERIFIED);
        }

        return view('pages.member.settings.withdraw', [
            'user' => $user,
            'memberProfile' => $user->memberProfile,
        ]);
    }

    /**
     * 退会処理を実行し、ログアウトする。
     *
     * 前提: `WithdrawMemberAccountRequest` で現在パスワードと確認チェック済みであること。
     *
     * `withdraw()` 内（Stripe 等）で例外が出た場合はログに記録し、退会画面へエラーを付けて戻す（未ログアウト）。
     * 成功時のログアウト・セッション無効化は {@see LogoutAndInvalidateSession} に委ね、退会拒否系と手順を揃える。
     */
    public function destroy(WithdrawMemberAccountRequest $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        try {
            $this->memberWithdrawalService->withdraw($user);
        } catch (Throwable $e) {
            Log::error('Member withdrawal failed', [
                'user_id' => $user->getKey(),
                'exception' => $e,
            ]);

            return redirect()
                ->route('member.settings.withdraw.edit')
                ->with('error', '退会処理を完了できませんでした。時間をおいて再度お試しください。');
        }

        ($this->logoutAndInvalidateSession)($request);

        return redirect()
            ->route('home')
            ->with('success', '退会手続きが完了しました。ご利用ありがとうございました。');
    }
}
