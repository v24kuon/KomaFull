<?php

namespace App\Services\Checkout;

use App\Contracts\EnsuresStripeCustomer;
use App\Models\User;

class CashierStripeCustomerEnsurer implements EnsuresStripeCustomer
{
    public function ensureStripeCustomerId(User $user): string
    {
        $user->createOrGetStripeCustomer();

        $id = $user->stripe_id;

        if ($id === null || $id === '') {
            throw new \RuntimeException('Stripe customer id was not set after createOrGetStripeCustomer.');
        }

        return $id;
    }
}
