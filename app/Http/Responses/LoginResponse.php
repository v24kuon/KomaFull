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
     * Create an HTTP response that represents the object.
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

    private function redirectPath(Request $request): string
    {
        $user = $request->user();

        if ($user instanceof User && $user->isAdministrator()) {
            return route('admin.dashboard', absolute: false);
        }

        return route('member.dashboard', absolute: false);
    }
}
