<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Program;
use App\Models\ProgramType;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Program>
 */
class ProgramFactory extends Factory
{
    protected $model = Program::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'PRG'.$this->faker->unique()->numerify('######'),
            'category_id' => Category::factory(),
            'program_type_id' => ProgramType::factory(),
            'name' => $this->faker->words(2, true),
            'level' => $this->faker->randomElement(['beginner', 'intermediate', 'advanced']),
            'duration_minutes' => $this->faker->numberBetween(30, 120),
            'overview' => $this->faker->sentence(),
            'detail' => $this->faker->paragraph(),
            'price' => $this->faker->numberBetween(1000, 10000),
            'point_cost' => $this->faker->numberBetween(0, 30),
            'ticket_cost' => $this->faker->numberBetween(0, 10),
            'status' => Program::STATUS_ACTIVE,
        ];
    }
}
