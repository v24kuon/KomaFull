<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\WithdrawMemberAccountRequest;
use App\Models\User;
use App\Services\Member\MemberWithdrawalService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SettingsWithdrawalController extends Controller
{
    public function __construct(
        private readonly MemberWithdrawalService $memberWithdrawalService,
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
                ->with('error', '会員プロフィールがまだありません。メール認証完了後に自動で作成されます。');
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
     */
    public function destroy(WithdrawMemberAccountRequest $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $this->memberWithdrawalService->withdraw($user);

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('home')
            ->with('success', '退会手続きが完了しました。ご利用ありがとうございました。');
    }
}
