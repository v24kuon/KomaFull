<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 会員向けマイページ（/mypage）へのアクセスを会員ロールに限定する。
 *
 * {@see EnsureMemberNotWithdrawn} は管理者を対象外とするため、管理者が会員 UI に入り込む経路をここで塞ぐ。
 * 前提: `auth` 済みであること（未認証は `auth` ミドルウェアで扱う）。
 */
class EnsureMemberRole
{
    /**
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User || $user->getAttribute('role') !== User::ROLE_MEMBER) {
            abort(403);
        }

        return $next($request);
    }
}
