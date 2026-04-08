<?php

namespace Tests\Feature\Booking;

use App\Models\LessonSession;
use App\Models\MemberProfile;
use App\Models\Program;
use App\Models\Reservation;
use App\Models\TrialApplication;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrialOnsiteBookingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-N-01: 仮会員が現地払いを選ぶと体験予約が確定し、予約行が作成される。
     */
    public function test_provisional_member_can_complete_trial_onsite_booking(): void
    {
        $user = User::factory()->create();
        MemberProfile::factory()->create([
            'user_id' => $user->getKey(),
            'member_status' => MemberProfile::STATUS_PROVISIONAL,
        ]);
        $program = Program::factory()->create([
            'price' => 1000,
            'status' => Program::STATUS_ACTIVE,
        ]);
        $lessonSession = LessonSession::factory()->create([
            'program_id' => $program->getKey(),
            'trial_capacity' => 2,
            'starts_at' => now()->addDay(),
            'status' => LessonSession::STATUS_ACTIVE,
        ]);

        $this->actingAs($user);
        $this->assertAuthenticatedAs($user);

        $response = $this->post(route('booking.trial.store'), [
            'lesson_session_id' => $lessonSession->getKey(),
            'payment_method' => 'onsite',
        ]);

        $response->assertRedirect(route('member.dashboard'));
        $this->assertDatabaseHas('trial_applications', [
            'user_id' => $user->getKey(),
            'lesson_session_id' => $lessonSession->getKey(),
            'payment_method' => TrialApplication::PAYMENT_METHOD_ONSITE,
            'status' => TrialApplication::STATUS_RESERVED,
        ]);
        $this->assertDatabaseHas('reservations', [
            'user_id' => $user->getKey(),
            'lesson_session_id' => $lessonSession->getKey(),
            'seat_bucket' => Reservation::SEAT_BUCKET_TRIAL,
            'payment_method' => Reservation::PAYMENT_METHOD_TRIAL_ONSITE,
        ]);
    }

    /**
     * TC-A-01: 本会員は体験予約画面へ入れず開催枠へ戻る。
     */
    public function test_active_member_cannot_use_trial_booking_flow(): void
    {
        $user = User::factory()->create();
        MemberProfile::factory()->active()->create(['user_id' => $user->getKey()]);
        $program = Program::factory()->create(['status' => Program::STATUS_ACTIVE]);
        $lessonSession = LessonSession::factory()->create([
            'program_id' => $program->getKey(),
            'trial_capacity' => 2,
            'starts_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($user)->get(route('booking.trial.show', $lessonSession));

        $response->assertRedirect(route('schedule.index', [
            'year' => now()->year,
            'month' => now()->month,
        ]));
    }

    /**
     * TC-A-02: 未ログインは体験予約 URL へアクセスできない。
     */
    public function test_guest_is_redirected_from_trial_booking(): void
    {
        $program = Program::factory()->create(['status' => Program::STATUS_ACTIVE]);
        $lessonSession = LessonSession::factory()->create([
            'program_id' => $program->getKey(),
            'trial_capacity' => 2,
            'starts_at' => now()->addDay(),
        ]);

        $response = $this->get(route('booking.trial.show', $lessonSession));

        $response->assertRedirect();
    }
}
