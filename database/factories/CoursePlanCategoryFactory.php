<?php

namespace Database\Factories;

use App\Models\CoursePlan;
use App\Models\CoursePlanCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

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
            'category_id' => fn (): int => $this->resolveCategoryId(),
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
