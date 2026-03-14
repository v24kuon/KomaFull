<?php

namespace Database\Factories;

use App\Models\AdditionalItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AdditionalItem>
 */
class AdditionalItemFactory extends Factory
{
    protected $model = AdditionalItem::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'AI'.fake()->unique()->numerify('###'),
            'additional_item_type' => 'member_profile',
            'label_name' => fake()->word(),
            'input_type' => fake()->randomElement(['text', 'number', 'select', 'checkbox']),
            'digits' => fake()->optional()->numberBetween(1, 10),
            'status' => AdditionalItem::STATUS_ACTIVE,
        ];
    }
}
