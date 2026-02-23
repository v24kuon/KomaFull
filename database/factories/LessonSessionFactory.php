<?php

namespace Database\Factories;

use App\Models\LessonSession;
use App\Models\Location;
use App\Models\Program;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\LessonSession>
 */
class LessonSessionFactory extends Factory
{
    protected $model = LessonSession::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'LS'.$this->faker->unique()->numerify('######'),
            'program_id' => Program::factory(),
            'location_id' => Location::factory(),
            'staff_id' => Staff::factory(),
            'starts_at' => $this->faker->dateTimeBetween('+1 day', '+3 months'),
            'capacity' => $this->faker->numberBetween(1, 30),
            'trial_capacity' => $this->faker->numberBetween(0, 10),
            'status' => LessonSession::STATUS_ACTIVE,
        ];
    }

    /**
     * Set specific relation IDs for testing.
     *
     * @param  int  $programId  The program ID to assign
     * @param  int  $locationId  The location ID to assign
     * @param  int  $staffId  The staff ID to assign
     */
    public function forRelationIds(int $programId, int $locationId, int $staffId): static
    {
        return $this->state(fn (): array => [
            'program_id' => $programId,
            'location_id' => $locationId,
            'staff_id' => $staffId,
        ]);
    }
}
