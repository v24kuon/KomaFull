<?php

namespace Database\Factories;

use App\Models\LessonSession;
use App\Models\TrialApplication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TrialApplication>
 */
class TrialApplicationFactory extends Factory
{
    protected $model = TrialApplication::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'lesson_session_id' => LessonSession::factory(),
            'payment_method' => TrialApplication::PAYMENT_METHOD_CARD,
            'status' => TrialApplication::STATUS_PENDING_PAYMENT,
            'stripe_checkout_session_id' => 'cs_test_'.$this->faker->unique()->bothify('????????????????????'),
            'expires_at' => now()->addMinutes(30),
            'reservation_id' => null,
            'refunded_at' => null,
            'refund_reason' => null,
            'canceled_at' => null,
        ];
    }

    public function refundPending(): static
    {
        return $this->state(fn (): array => [
            'status' => TrialApplication::STATUS_REFUND_PENDING,
        ]);
    }
}
