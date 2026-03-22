<?php

namespace App\Http\Responses;

use App\Models\User;
use Illuminate\Http\Request;
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
