<?php

namespace Database\Factories;

use App\Models\CoursePlan;
use App\Models\CoursePlanCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CoursePlanCategory>
 */
class CoursePlanCategoryFactory extends Factory
{
    protected $model = CoursePlanCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_plan_id' => CoursePlan::factory(),
            'category_id' => $this->faker->numberBetween(1, 1000),
        ];
    }

    /**
     * Set explicit category ID for testing.
     *
     * @param  int  $categoryId  The category ID to assign
     */
    public function forCategoryId(int $categoryId): static
    {
        return $this->state(fn (): array => [
            'category_id' => $categoryId,
        ]);
    }
}
