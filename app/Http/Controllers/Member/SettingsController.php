<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * 会員設定のハブ画面を表示する。
     *
     * 前提: 認証済みであること。`member_profiles` はメール認証時の作成が失敗し得るため、未作成時はダッシュボードへ誘導する。
     */
    public function __invoke(): View|RedirectResponse
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

        return view('pages.member.settings.index', [
            'user' => $user,
        ]);
    }
}
