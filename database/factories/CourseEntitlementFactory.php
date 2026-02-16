<?php

namespace Database\Factories;

use App\Models\CourseEntitlement;
use App\Models\CoursePlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CourseEntitlement>
 */
class CourseEntitlementFactory extends Factory
{
    protected $model = CourseEntitlement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_plan_id' => CoursePlan::factory(),
            'period_start' => now()->startOfMonth()->toDateString(),
            'period_end' => now()->endOfMonth()->toDateString(),
            'granted_uses' => $this->faker->numberBetween(1, 12),
            'used_uses' => 0,
        ];
    }

    /**
     * Mark the entitlement as partially consumed.
     */
    public function partiallyConsumed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'used_uses' => min(1, (int) ($attributes['granted_uses'] ?? 1)),
        ]);
    }
}
