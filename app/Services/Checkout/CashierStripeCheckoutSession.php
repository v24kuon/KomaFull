<?php

namespace App\Services\Checkout;

use App\Contracts\CreatesStripeCheckoutSession;
use Laravel\Cashier\Cashier;
use Stripe\Checkout\Session;

class CashierStripeCheckoutSession implements CreatesStripeCheckoutSession
{
    /**
     * @param  array<string, mixed>  $params
     */
    public function create(array $params): Session
    {
        return Cashier::stripe()->checkout->sessions->create($params);
    }
}
