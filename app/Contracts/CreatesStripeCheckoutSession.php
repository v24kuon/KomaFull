<?php

namespace App\Contracts;

use Stripe\Checkout\Session;

/**
 * アプリケーション内の Stripe Checkout Session 作成境界（Stripe API `checkout.sessions.create` に相当するペイロードを渡す）。
 *
 * 実装は Cashier 経由で `StripeClient::checkout->sessions->create` / `retrieve` を呼び出す。必須キーと入れ子の形は Stripe 公式の Create a Checkout Session に従う（`mode`=`subscription` 等、呼び出しごとに異なる）。
 */
interface CreatesStripeCheckoutSession
{
    /**
     * Checkout Session を作成し、結果の `Session` を返す。
     *
     * @param  array{
     *     mode: string,
     *     line_items: list<array<string, mixed>>,
     *     success_url: string,
     *     cancel_url: string,
     *     customer?: string,
     *     client_reference_id?: string,
     *     metadata?: array<string, string>,
     * }|array<string, mixed>  $params  Stripe `checkout.sessions.create` のリクエストボディ。左の形状は本アプリの体験カード（`mode`=`payment`）で `TrialCheckoutSessionService` が渡す最低限のキー例。それ以外のキーや別 `mode` 用のペイロードは `array<string, mixed>` 側として許容する。
     *
     * @throws \Stripe\Exception\ApiErrorException 実装が Stripe API を呼び出した結果エラーとなった場合（実装依存）
     */
    public function create(array $params): Session;

    /**
     * 既存の Checkout Session を id で取得する（Stripe `checkout.sessions.retrieve`）。
     *
     * @param  non-empty-string  $sessionId
     *
     * @throws \Stripe\Exception\ApiErrorException 実装が Stripe API を呼び出した結果エラーとなった場合（実装依存）
     */
    public function retrieve(string $sessionId): Session;
}
