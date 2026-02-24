<?php

namespace App\Services;

use Laravel\Cashier\Cashier;

class StripeRefundService
{
    /**
     * Create a Stripe refund for a payment intent.
     *
     * @param  non-empty-string  $paymentIntentId
     * @param  non-empty-string  $idempotencyKey
     *
     * @throws \Stripe\Exception\ApiErrorException
     */
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
