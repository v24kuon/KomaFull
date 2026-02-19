<?php

namespace Database\Factories;

use App\Models\CourseEntitlement;
use App\Models\CourseEntitlementItem;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            'category_id' => $this->resolveCategoryId(),
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

    private function resolveCategoryId(): int
    {
        $existingCategoryId = DB::table('categories')->inRandomOrder()->value('id');

        if ($existingCategoryId !== null) {
            return (int) $existingCategoryId;
        }

        return (int) DB::table('categories')->insertGetId([
            'code' => 'CAT_'.Str::uuid()->toString(),
            'name' => 'Factory Category',
            'sort_order' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
