<?php

namespace Database\Factories;

use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Staff>
 */
class StaffFactory extends Factory
{
    protected $model = Staff::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'STF'.$this->faker->unique()->numerify('######'),
            'name' => $this->faker->name(),
            'gender' => $this->faker->randomElement(['male', 'female', null]),
            'birth_date' => $this->faker->optional()->date(),
            'licence_skill' => $this->faker->optional()->sentence(),
            'main_expertise' => $this->faker->optional()->word(),
            'role' => $this->faker->optional()->word(),
            'description' => $this->faker->optional()->sentence(),
            'status' => Staff::STATUS_ACTIVE,
        ];
    }
}
