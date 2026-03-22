<?php

namespace App\Actions\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * ログアウト後にセッションを無効化し CSRF トークンを再生成する。
 *
 * 退会済み会員の拒否処理・退会手続き成功後のログアウトなど、認証状態を完全に破棄する際に
 * {@see \App\Http\Middleware\EnsureMemberNotWithdrawn}・{@see \App\Http\Responses\LoginResponse}・
 * {@see \App\Http\Controllers\Member\SettingsWithdrawalController::destroy} で共通利用する。
 */
class LogoutAndInvalidateSession
{
    /**
     * 認証状態を破棄し、セッションと CSRF トークンを無効化する。
     *
     * 責務: `Auth::logout()` の後にセッションを無効化し、トークンを再生成する手順を一箇所に固定する。
     * リダイレクト先・ユーザー向けメッセージは担わない（呼び出し側の責務）。
     *
     * 前提: `$request` にセッションが紐づいていること（通常は `auth` 済みの Web リクエスト）。同一リクエスト内で
     * このメソッドの後にセッションへ書き込む必要がある場合は呼び出し側で順序を検討すること。
     *
     * 副作用: セッションストアを無効化するため、以降のリクエストでは新しいセッションになる。
     *
     * 更新方針: 上記クラスと同じ手順が必要な場合のみ本アクションを経由する。
     * 手順の追加・変更は各呼び出し元の挙動とテストを同時に確認すること。
     *
     * @param  Request  $request  セッションを保持する HTTP リクエスト
     */
    public function __invoke(Request $request): void
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }
}
