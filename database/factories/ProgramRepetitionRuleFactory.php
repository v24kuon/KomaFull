<?php

namespace Database\Factories;

use App\Models\Location;
use App\Models\Program;
use App\Models\ProgramRepetitionRule;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ProgramRepetitionRule>
 */
class ProgramRepetitionRuleFactory extends Factory
{
    protected $model = ProgramRepetitionRule::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('+0 days', '+30 days');
        $endDate = (clone $startDate)->modify('+14 days');

        return [
            'program_id' => Program::factory(),
            'location_id' => Location::factory(),
            'staff_id' => Staff::factory(),
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_DAILY,
            'day_of_week' => null,
            'week_of_month' => null,
            'start_date' => $startDate->format('Y-m-d'),
            'end_date' => $endDate->format('Y-m-d'),
            'start_time' => $this->faker->time('H:i:s'),
            'capacity' => $this->faker->numberBetween(1, 20),
            'trial_capacity' => $this->faker->numberBetween(0, 5),
            'status' => ProgramRepetitionRule::STATUS_ACTIVE,
        ];
    }

    public function weekly(int $dayOfWeek = 1): static
    {
        return $this->state(fn (): array => [
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_WEEKLY,
            'day_of_week' => $dayOfWeek,
        ]);
    }
}
