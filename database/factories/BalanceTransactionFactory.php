<?php

namespace Database\Factories;

use App\Models\BalanceTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\BalanceTransaction>
 */
class BalanceTransactionFactory extends Factory
{
    protected $model = BalanceTransaction::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'unit' => BalanceTransaction::UNIT_TICKETS,
            'amount' => 1,
            'transaction_type' => BalanceTransaction::TYPE_GRANT,
            'idempotency_key' => 'bt_'.$this->faker->unique()->uuid(),
            'prepaid_purchase_id' => null,
            'reservation_id' => null,
            'stripe_reference_id' => null,
            'occurred_at' => now(),
            'expires_at' => null,
        ];
    }

    /**
     * Mark the transaction as consumption.
     */
    public function consume(): static
    {
        return $this->state(fn (): array => [
            'amount' => -1,
            'transaction_type' => BalanceTransaction::TYPE_CONSUME,
        ]);
    }

    /**
     * Set the transaction unit to points.
     */
    public function points(): static
    {
        return $this->state(fn (): array => [
            'unit' => BalanceTransaction::UNIT_POINTS,
        ]);
    }
}
