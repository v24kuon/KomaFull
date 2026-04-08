<?php

namespace App\Services\Checkout;

use App\Contracts\CreatesStripeCheckoutSession;
use Laravel\Cashier\Cashier;
use Stripe\Checkout\Session;

class CashierStripeCheckoutSession implements CreatesStripeCheckoutSession
{
    /**
     * {@inheritdoc}
     */
    public function create(array $params, array $requestOptions = []): Session
    {
        $opts = $requestOptions === [] ? null : $requestOptions;

        return Cashier::stripe()->checkout->sessions->create($params, $opts);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve(string $sessionId): Session
    {
        return Cashier::stripe()->checkout->sessions->retrieve($sessionId);
    }
}
