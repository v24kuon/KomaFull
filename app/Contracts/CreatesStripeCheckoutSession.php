<?php

namespace App\Contracts;

use Stripe\Checkout\Session;

interface CreatesStripeCheckoutSession
{
    /**
     * @param  array<string, mixed>  $params
     */
    public function create(array $params): Session;
}
