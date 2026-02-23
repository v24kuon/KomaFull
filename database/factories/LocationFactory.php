<?php

namespace Database\Factories;

use App\Models\Location;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Location>
 */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'LOC'.$this->faker->unique()->numerify('######'),
            'name' => $this->faker->company(),
            'address' => $this->faker->address(),
            'tel' => $this->faker->phoneNumber(),
            'email' => $this->faker->safeEmail(),
            'description' => $this->faker->sentence(),
            'status' => Location::STATUS_ACTIVE,
        ];
    }
}
