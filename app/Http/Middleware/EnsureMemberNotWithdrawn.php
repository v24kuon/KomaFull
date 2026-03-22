<?php

namespace App\Http\Middleware;

use App\Models\MemberProfile;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureMemberNotWithdrawn
{
    /**
     * 退会済み会員のマイページ利用を拒否する。
     *
     * 前提: `auth` 済みであること。管理者は対象外。
     * 更新方針: `member_profiles.member_status=withdrawn` のときセッションを破棄しホームへ誘導する。
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user instanceof User) {
            return $next($request);
        }

        if ($user->isAdministrator()) {
            return $next($request);
        }

        $profile = $user->memberProfile;

        if ($profile !== null && $profile->member_status === MemberProfile::STATUS_WITHDRAWN) {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => '退会済みのため、マイページを利用できません。',
                ]);
        }

        return $next($request);
    }
}
