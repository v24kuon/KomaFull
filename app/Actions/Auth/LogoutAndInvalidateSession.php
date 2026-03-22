<?php

namespace App\Actions\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ログアウト後にセッションを無効化し CSRF トークンを再生成する。
 *
 * 退会済み会員の拒否処理など、認証状態を完全に破棄する際に
 * {@see \App\Http\Middleware\EnsureMemberNotWithdrawn} と {@see \App\Http\Responses\LoginResponse} で共通利用する。
 */
class LogoutAndInvalidateSession
{
    public function __invoke(Request $request): void
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
