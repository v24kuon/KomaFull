<?php

namespace App\Http\Middleware;

use App\Actions\Auth\LogoutAndInvalidateSession;
use App\Models\MemberProfile;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMemberNotWithdrawn
{
    public function __construct(
        private readonly LogoutAndInvalidateSession $logoutAndInvalidateSession,
    ) {}

    /**
     * 退会済み会員のマイページ利用を拒否する。
     *
     * 前提: `auth` 済みであること。管理者は退会チェック対象外（会員ロールの境界は {@see EnsureMemberRole}）。
     * 更新方針: `member_profiles.member_status=withdrawn` のとき {@see LogoutAndInvalidateSession} でセッションを破棄し、
     * `login` 名前付きルートへリダイレクトする（`home` やトップへの誘導ではない）。
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
            ($this->logoutAndInvalidateSession)($request);

            return redirect()
                ->route('login')
                ->withErrors([
                    'email' => '退会済みのため、マイページを利用できません。',
                ]);
        }

        return $next($request);
    }
}
