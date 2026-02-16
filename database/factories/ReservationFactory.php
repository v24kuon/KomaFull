<?php

namespace Database\Factories;

use App\Models\LessonSession;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reservation>
 */
class ReservationFactory extends Factory
{
    protected $model = Reservation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'code' => 'R'.$this->faker->unique()->numerify('######'),
            'user_id' => User::factory(),
            'lesson_session_id' => LessonSession::factory(),
            'seat_bucket' => Reservation::SEAT_BUCKET_NORMAL,
            'payment_method' => Reservation::PAYMENT_METHOD_TICKETS,
            'status' => Reservation::STATUS_CONFIRMED,
            'ticket_cost' => 0,
            'point_cost' => 0,
            'course_entitlement_id' => null,
            'canceled_at' => null,
            'cancel_reason' => null,
        ];
    }

    /**
     * Set explicit relation IDs for testing.
     *
     * @param  int  $lessonSessionId  The lesson session ID to assign
     * @param  int|null  $userId  Optional existing user ID to assign
     */
    public function forRelationIds(int $lessonSessionId, ?int $userId = null): static
    {
        return $this->state(fn (): array => [
            'lesson_session_id' => $lessonSessionId,
            'user_id' => $userId ?? User::factory(),
        ]);
    }

    /**
     * Create a canceled reservation.
     */
    public function canceled(): static
    {
        return $this->state(fn (): array => [
            'status' => Reservation::STATUS_CANCELED,
            'canceled_at' => now(),
            'cancel_reason' => $this->faker->sentence(),
        ]);
    }

    /**
     * Create a trial reservation.
     */
    public function trial(): static
    {
        return $this->state(fn (): array => [
            'seat_bucket' => Reservation::SEAT_BUCKET_TRIAL,
            'payment_method' => Reservation::PAYMENT_METHOD_TRIAL_CARD,
        ]);
    }
}
