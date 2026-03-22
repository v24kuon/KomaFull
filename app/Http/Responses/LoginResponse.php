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
 */
class LoginResponse implements LoginResponseContract
{
    /**
     * Create an HTTP response that represents the object.
     */
    public function toResponse($request): Response
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $user = $request->user();

        if ($user instanceof User && ! $user->isAdministrator()) {
            $profile = $user->memberProfile;
            if ($profile !== null && $profile->member_status === MemberProfile::STATUS_WITHDRAWN) {
                Auth::logout();

                return redirect()
                    ->route('login')
                    ->withErrors([
                        'email' => 'このアカウントは退会済みです。',
                    ]);
            }
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
