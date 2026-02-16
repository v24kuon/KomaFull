<?php

namespace Database\Factories;

use App\Models\CourseEntitlement;
use App\Models\CourseEntitlementItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CourseEntitlementItem>
 */
class CourseEntitlementItemFactory extends Factory
{
    protected $model = CourseEntitlementItem::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'course_entitlement_id' => CourseEntitlement::factory(),
            'category_id' => $this->faker->numberBetween(1, 1000),
            'granted_uses' => $this->faker->numberBetween(1, 12),
            'used_uses' => 0,
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
