<?php

namespace App\Http\Controllers\Member;

use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Http\Controllers\Controller;
use App\Http\Requests\Member\UpdateMemberEmailSettingsRequest;
use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * メールアドレス変更。ルートは `verified` 外（変更直後は未認証のため）— 定義は routes/web.php を参照。
 */
class SettingsEmailController extends Controller
{
    public function __construct(
        private readonly UpdateUserProfileInformation $updateUserProfileInformation,
    ) {}

    /**
     * メールアドレス変更フォームを表示する。
     *
     * プロフィール未作成時: メール未認証ユーザーは `member.dashboard` へ送らない（`verified` により verification.notice へ連鎖し
     * フラッシュが消える）。未認証なら `verification.notice` へ、認証済みなら `member.dashboard` へ誘導する。
     */
    public function edit(): View|RedirectResponse
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        if ($user->memberProfile === null) {
            if ($user->hasVerifiedEmail()) {
                return redirect()
                    ->route('member.dashboard')
                    ->with('error', MemberProfile::FLASH_ERROR_MISSING_PROFILE_VERIFIED);
            }

            return redirect()
                ->route('verification.notice')
                ->with('error', MemberProfile::FLASH_ERROR_MISSING_PROFILE_UNVERIFIED);
        }

        return view('pages.member.settings.email', [
            'user' => $user,
        ]);
    }

    /**
     * メールアドレスを更新する（変更時は再認証メールを送る）。
     *
     * 前提: Fortify の `UpdateUserProfileInformation` と整合するため `name` は現状値を渡す。
     */
    public function update(UpdateMemberEmailSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $this->updateUserProfileInformation->update($user, [
            'name' => $user->name,
            'email' => $request->validated('email'),
        ]);

        return redirect()
            ->route('member.settings.email.edit')
            ->with('success', 'メールアドレスを更新しました。変更後のアドレス宛に確認メールを送信しましたので、メール内のリンクから認証を完了してください。');
    }
}
