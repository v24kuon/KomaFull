<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\UpdateMemberPasswordSettingsRequest;
use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SettingsPasswordController extends Controller
{
    /**
     * ログイン中パスワード変更フォームを表示する。
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

        return view('pages.member.settings.password', [
            'user' => $user,
        ]);
    }

    /**
     * ログイン中パスワードを更新する。
     *
     * 前提: `UpdateMemberPasswordSettingsRequest` で検証済みであること。
     * 副作用: `users.password` を更新する（モデル cast によりハッシュ化される）。
     */
    public function update(UpdateMemberPasswordSettingsRequest $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $user->forceFill([
            'password' => $request->validated('password'),
        ])->save();

        return redirect()
            ->route('member.settings.password.edit')
            ->with('success', 'パスワードを変更しました。');
    }
}
