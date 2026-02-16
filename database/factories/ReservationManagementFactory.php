<?php

namespace Database\Factories;

use App\Models\ReservationManagement;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ReservationManagement>
 */
class ReservationManagementFactory extends Factory
{
    protected $model = ReservationManagement::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lesson_session_id' => 1,
            'reserved_count' => 0,
            'reserved_trial_count' => 0,
        ];
    }

    /**
     * Set explicit lesson session ID for testing.
     *
     * @param  int  $lessonSessionId  The lesson session ID to assign
     */
    public function forLessonSessionId(int $lessonSessionId): static
    {
        return $this->state(fn (): array => [
            'lesson_session_id' => $lessonSessionId,
        ]);
    }
}
