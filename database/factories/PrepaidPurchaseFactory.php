<?php

namespace Database\Factories;

use App\Models\PrepaidProduct;
use App\Models\PrepaidPurchase;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PrepaidPurchase>
 */
class PrepaidPurchaseFactory extends Factory
{
    protected $model = PrepaidPurchase::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'PU'.$this->faker->unique()->numerify('######'),
            'user_id' => User::factory(),
            'prepaid_product_id' => PrepaidProduct::factory(),
            'purchased_at' => null,
            'expires_at' => null,
            'status' => PrepaidPurchase::STATUS_PENDING_PAYMENT,
            'stripe_checkout_session_id' => null,
        ];
    }

    /**
     * Mark the purchase as completed.
     */
    public function completed(): static
    {
        return $this->state(fn (): array => [
            'purchased_at' => now(),
            'expires_at' => now()->addDays(30),
            'status' => PrepaidPurchase::STATUS_COMPLETED,
            'stripe_checkout_session_id' => 'cs_test_'.$this->faker->unique()->bothify('????????????????????'),
        ]);
    }
}
