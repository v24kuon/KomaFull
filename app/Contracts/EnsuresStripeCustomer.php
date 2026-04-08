<?php

namespace App\Contracts;

use App\Models\User;

interface EnsuresStripeCustomer
{
    /**
     * Billable なユーザーに対応する Stripe Customer を確保し、その Stripe Customer ID（`cus_...`）を返す。
     *
     * 責務: Cashier `Billable` 経由で、未作成なら Stripe 上に Customer を作成し、作成済みなら既存 Customer を解決する。
     *
     * 副作用:
     * - Stripe API 呼び出し（Customer の create または retrieve）。
     * - 成功時に `users.stripe_id`（および Cashier が同期する関連カラム）が永続化され得る。読み取り専用ではない。
     *
     * 冪等性・再実行: `stripe_id` が既に設定されていれば、同一ユーザーを再度呼んでも新規 Customer を二重作成しない（`createOrGetStripeCustomer` の契約に従う）。
     *
     * トランザクション境界: 本メソッドは自ら DB トランザクションを張らない。呼び出し側が `ConnectionInterface::transaction()` 内で利用する場合、Cashier による `users` 更新が当該トランザクションに参加するかは Cashier／接続の挙動に依存するため、同一トランザクション内で他行を `lockForUpdate` する順序と整合させること。
     *
     * @return non-empty-string Stripe Customer ID（`cus_...`）
     *
     * @throws \RuntimeException Stripe 処理後も `stripe_id` が解決できない場合（実装依存）
     */
    public function ensureStripeCustomerId(User $user): string;
}
