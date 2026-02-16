<?php

namespace Database\Factories;

use App\Models\CoursePlan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CoursePlan>
 */
class CoursePlanFactory extends Factory
{
    protected $model = CoursePlan::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'CP'.$this->faker->unique()->numerify('######'),
            'name' => $this->faker->words(2, true).' Course',
            'stripe_price_id' => null,
            'usage_count' => $this->faker->numberBetween(1, 12),
            'allocation_type' => CoursePlan::ALLOCATION_TYPE_TOTAL,
            'level' => $this->faker->randomElement([
                CoursePlan::LEVEL_BEGINNER,
                CoursePlan::LEVEL_STANDARD,
                CoursePlan::LEVEL_ADVANCED,
            ]),
            'description' => $this->faker->sentence(),
            'status' => CoursePlan::STATUS_ACTIVE,
        ];
    }

    /**
     * Set allocation type to per-category.
     */
    public function perCategory(): static
    {
        return $this->state(fn (): array => [
            'allocation_type' => CoursePlan::ALLOCATION_TYPE_PER_CATEGORY,
        ]);
    }

    /**
     * Mark the course plan as inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (): array => [
            'status' => CoursePlan::STATUS_INACTIVE,
        ]);
    }
}
