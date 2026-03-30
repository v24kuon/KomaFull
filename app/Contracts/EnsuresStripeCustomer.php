<?php

namespace App\Contracts;

use App\Models\User;

interface EnsuresStripeCustomer
{
    /**
     * Ensure the billable user has a Stripe Customer and return its id.
     *
     * @return non-empty-string
     */
    public function ensureStripeCustomerId(User $user): string;
}
