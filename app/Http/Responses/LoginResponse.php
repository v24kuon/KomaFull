<?php

namespace App\Http\Responses;

use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

/**
 * Fortify ログイン成功時のレスポンス。
 *
 * {@see \Illuminate\Routing\Redirector::intended()} により、セッションに `url.intended` がある場合は
 * `redirectPath()` が返すロール別デフォルトより優先される。
 *
 * 退会済み会員は `wantsJson()` より先に判定し、HTML はログインへエラー、JSON は 403。いずれも logout 後に
 * session invalidate / CSRF 再生成を行う。
 */
class LoginResponse implements LoginResponseContract
{
    /**
     * Fortify のログイン成功レスポンスを返す（契約上のエントリーポイント）。
     *
     * 退会済み会員: `Auth::logout()` の後にセッションを無効化し CSRF トークンを再生成し、JSON は 403、HTML は
     * ログイン画面へエラーを付与してリダイレクトする（{@see EnsureMemberNotWithdrawn} 等との整合）。
     * 通常の会員・管理者: JSON 要求なら `two_factor: false` のみ、それ以外は intended またはロール別ダッシュボードへ。
     *
     * @param  Request  $request  認証済みユーザー・セッションを保持するリクエスト
     * @return Response リダイレクト、403 JSON、またはログイン画面へのレスポンス
     */
    public function toResponse($request): Response
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->isAdministrator()) {
            $profile = $user->memberProfile;
            if ($profile !== null && $profile->member_status === MemberProfile::STATUS_WITHDRAWN) {
                Auth::logout();

                $request->session()->invalidate();
                $request->session()->regenerateToken();

                if ($request->wantsJson()) {
                    return response()->json([
                        'message' => 'このアカウントは退会済みです。',
                    ], 403);
                }

                return redirect()
                    ->route('login')
                    ->withErrors([
                        'email' => 'このアカウントは退会済みです。',
                    ]);
            }
        }

        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        return redirect()->intended($this->redirectPath($request));
    }

    /**
     * `intended` が無いときのフォールバック先パス（管理者は管理ダッシュボード、それ以外は会員ダッシュボード）。
     *
     * 副作用なし（読み取りのみ）。
     *
     * @param  Request  $request  現在のリクエスト（`user()` でロール判定）
     * @return string 相対 URL パス（`redirect()->intended()` のフォールバック引数）
     */
    private function redirectPath(Request $request): string
    {
        $user = $request->user();

        if ($user instanceof User && $user->isAdministrator()) {
            return route('admin.dashboard', absolute: false);
        }

        return route('member.dashboard', absolute: false);
    }
}
