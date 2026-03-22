<?php

namespace Database\Factories;

use App\Models\AdditionalItem;
use App\Models\MemberAdditionalItemValue;
use App\Models\MemberProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MemberAdditionalItemValue>
 */
class MemberAdditionalItemValueFactory extends Factory
{
    protected $model = MemberAdditionalItemValue::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'member_profile_id' => MemberProfile::factory(),
            'additional_item_id' => AdditionalItem::factory(),
            'value' => fake()->word(),
        ];
    }
}
