<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * 会員設定のハブ画面を表示する。
     *
     * 前提: 認証済みかつ `member_profiles` が存在すること（プロフィール画面と同様）。
     */
    public function __invoke(): View
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        $memberProfile = $user->memberProfile;

        return view('pages.member.settings.index', [
            'user' => $user,
            'memberProfile' => $memberProfile,
        ]);
    }
}
