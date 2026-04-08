<?php

namespace Tests\Feature\Booking;

use App\Models\BalanceTransaction;
use App\Models\LessonSession;
use App\Models\MemberProfile;
use App\Models\Program;
use App\Models\Reservation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NormalReservationBookingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-N-01: 本会員が回数券残高十分なとき、通常予約が確定する。
     */
    public function test_active_member_can_book_with_tickets(): void
    {
        $user = User::factory()->create();
        MemberProfile::factory()->active()->create(['user_id' => $user->getKey()]);
        BalanceTransaction::factory()->create([
            'user_id' => $user->getKey(),
            'unit' => BalanceTransaction::UNIT_TICKETS,
            'amount' => 10,
            'transaction_type' => BalanceTransaction::TYPE_GRANT,
            'idempotency_key' => 'grant-test-tickets-'.$user->getKey(),
            'occurred_at' => now(),
        ]);
        $program = Program::factory()->create([
            'ticket_cost' => 2,
            'point_cost' => 0,
            'status' => Program::STATUS_ACTIVE,
        ]);
        $lessonSession = LessonSession::factory()->create([
            'program_id' => $program->getKey(),
            'capacity' => 5,
            'starts_at' => now()->addDay(),
            'status' => LessonSession::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($user)->post(route('booking.normal.store'), [
            'lesson_session_id' => $lessonSession->getKey(),
            'payment_method' => 'tickets',
        ]);

        $response->assertRedirect(route('member.dashboard'));
        $this->assertDatabaseHas('reservations', [
            'user_id' => $user->getKey(),
            'lesson_session_id' => $lessonSession->getKey(),
            'payment_method' => Reservation::PAYMENT_METHOD_TICKETS,
            'ticket_cost' => 2,
        ]);
        $this->assertDatabaseHas('balance_transactions', [
            'user_id' => $user->getKey(),
            'unit' => BalanceTransaction::UNIT_TICKETS,
            'amount' => -2,
            'transaction_type' => BalanceTransaction::TYPE_CONSUME,
        ]);
    }

    /**
     * TC-A-01: 仮会員は通常予約へ入れない。
     */
    public function test_provisional_member_cannot_access_normal_booking(): void
    {
        $user = User::factory()->create();
        MemberProfile::factory()->create([
            'user_id' => $user->getKey(),
            'member_status' => MemberProfile::STATUS_PROVISIONAL,
        ]);
        $program = Program::factory()->create(['status' => Program::STATUS_ACTIVE]);
        $lessonSession = LessonSession::factory()->create([
            'program_id' => $program->getKey(),
            'capacity' => 5,
            'starts_at' => now()->addDay(),
        ]);

        $response = $this->actingAs($user)->get(route('booking.normal.show', $lessonSession));

        $response->assertRedirect(route('schedule.index', [
            'year' => now()->year,
            'month' => now()->month,
        ]));
    }
}
