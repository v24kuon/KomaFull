<?php

namespace Database\Factories;

use App\Models\ProgramType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProgramType>
 */
class ProgramTypeFactory extends Factory
{
    protected $model = ProgramType::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'PT'.$this->faker->unique()->numerify('######'),
            'name' => $this->faker->words(2, true),
            'sort_order' => $this->faker->numberBetween(1, 99),
            'status' => ProgramType::STATUS_ACTIVE,
        ];
    }
}
