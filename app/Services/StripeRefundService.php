<?php

namespace App\Services;

use Laravel\Cashier\Cashier;

class StripeRefundService
{
    public function refundPaymentIntent(string $paymentIntentId, string $idempotencyKey): void
    {
        Cashier::stripe()->refunds->create(
            [
                'payment_intent' => $paymentIntentId,
            ],
            [
                'idempotency_key' => $idempotencyKey,
            ]
        );
    }
}
