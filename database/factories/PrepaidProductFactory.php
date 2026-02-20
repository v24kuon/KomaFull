<?php

namespace Database\Factories;

use App\Models\PrepaidProduct;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PrepaidProduct>
 */
class PrepaidProductFactory extends Factory
{
    protected $model = PrepaidProduct::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'PP'.$this->faker->unique()->numerify('######'),
            'prepaid_type' => PrepaidProduct::PREPAID_TYPE_TICKETS,
            'sales_name' => $this->faker->words(2, true).' Pass',
            'usage_count' => $this->faker->numberBetween(1, 20),
            'expires_in_days' => $this->faker->numberBetween(30, 365),
            'price' => $this->faker->numberBetween(1000, 50000),
            'status' => PrepaidProduct::STATUS_ACTIVE,
        ];
    }

    /**
     * Set the prepaid type to points.
     */
    public function points(): static
    {
        return $this->state(fn (): array => [
            'prepaid_type' => PrepaidProduct::PREPAID_TYPE_POINTS,
        ]);
    }

    /**
     * Mark the prepaid product as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'status' => PrepaidProduct::STATUS_INACTIVE,
        ]);
    }
}
