<?php

namespace Tests\Feature;

use App\Models\LessonSession;
use App\Models\Reservation;
use App\Models\ReservationManagement;
use App\Models\User;
use App\Services\ReservationService;
use BadMethodCallException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class ReservationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ReservationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ReservationService::class);
    }

    public function test_service_can_be_resolved_from_container(): void
    {
        $this->assertInstanceOf(ReservationService::class, $this->service);
    }

    public function test_book_creates_normal_reservation_and_increments_reserved_count(): void
    {
        $user = User::factory()->create();
        $lessonSession = LessonSession::factory()->create([
            'capacity' => 1,
            'trial_capacity' => 1,
        ]);

        ReservationManagement::factory()
            ->forLessonSessionId($lessonSession->id)
            ->create([
                'reserved_count' => 0,
                'reserved_trial_count' => 0,
            ]);

        $reservation = $this->service->book(
            userId: $user->id,
            lessonSessionId: $lessonSession->id,
            seatBucket: Reservation::SEAT_BUCKET_NORMAL,
            paymentMethod: Reservation::PAYMENT_METHOD_TICKETS,
            options: [
                'ticket_cost' => 2,
                'point_cost' => 0,
            ]
        );

        $this->assertSame(Reservation::SEAT_BUCKET_NORMAL, $reservation->seat_bucket);
        $this->assertSame(Reservation::PAYMENT_METHOD_TICKETS, $reservation->payment_method);
        $this->assertSame(Reservation::STATUS_CONFIRMED, $reservation->status);
        $this->assertSame(2, $reservation->ticket_cost);
        $this->assertMatchesRegularExpression('/^R[0-9A-HJKMNP-TV-Z]{26}$/', $reservation->code);

        $reservationManagement = ReservationManagement::query()
            ->where('lesson_session_id', $lessonSession->id)
            ->firstOrFail();
        $this->assertSame(1, $reservationManagement->reserved_count);
        $this->assertSame(0, $reservationManagement->reserved_trial_count);
    }

    public function test_book_creates_trial_reservation_and_increments_reserved_trial_count(): void
    {
        $user = User::factory()->create();
        $lessonSession = LessonSession::factory()->create([
            'capacity' => 2,
            'trial_capacity' => 1,
        ]);

        ReservationManagement::factory()
            ->forLessonSessionId($lessonSession->id)
            ->create([
                'reserved_count' => 0,
                'reserved_trial_count' => 0,
            ]);

        $reservation = $this->service->book(
            userId: $user->id,
            lessonSessionId: $lessonSession->id,
            seatBucket: Reservation::SEAT_BUCKET_TRIAL,
            paymentMethod: Reservation::PAYMENT_METHOD_TRIAL_ONSITE
        );

        $this->assertSame(Reservation::SEAT_BUCKET_TRIAL, $reservation->seat_bucket);
        $this->assertSame(Reservation::PAYMENT_METHOD_TRIAL_ONSITE, $reservation->payment_method);
        $this->assertSame(Reservation::STATUS_CONFIRMED, $reservation->status);

        $reservationManagement = ReservationManagement::query()
            ->where('lesson_session_id', $lessonSession->id)
            ->firstOrFail();
        $this->assertSame(0, $reservationManagement->reserved_count);
        $this->assertSame(1, $reservationManagement->reserved_trial_count);
    }

    public function test_book_creates_reservation_management_row_when_missing(): void
    {
        $user = User::factory()->create();
        $lessonSession = LessonSession::factory()->create([
            'capacity' => 2,
            'trial_capacity' => 1,
        ]);

        $this->assertDatabaseMissing('reservation_management', [
            'lesson_session_id' => $lessonSession->id,
        ]);

        $this->service->book(
            userId: $user->id,
            lessonSessionId: $lessonSession->id,
            seatBucket: Reservation::SEAT_BUCKET_NORMAL,
            paymentMethod: Reservation::PAYMENT_METHOD_TICKETS
        );

        $this->assertDatabaseHas('reservation_management', [
            'lesson_session_id' => $lessonSession->id,
            'reserved_count' => 1,
            'reserved_trial_count' => 0,
        ]);
    }

    public function test_book_throws_when_normal_capacity_is_full(): void
    {
        $user = User::factory()->create();
        $lessonSession = LessonSession::factory()->create([
            'capacity' => 1,
            'trial_capacity' => 1,
        ]);

        ReservationManagement::factory()
            ->forLessonSessionId($lessonSession->id)
            ->create([
                'reserved_count' => 1,
                'reserved_trial_count' => 0,
            ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('normal capacity is full');

        $this->service->book(
            userId: $user->id,
            lessonSessionId: $lessonSession->id,
            seatBucket: Reservation::SEAT_BUCKET_NORMAL,
            paymentMethod: Reservation::PAYMENT_METHOD_TICKETS
        );
    }

    public function test_book_throws_when_trial_capacity_is_full(): void
    {
        $user = User::factory()->create();
        $lessonSession = LessonSession::factory()->create([
            'capacity' => 2,
            'trial_capacity' => 1,
        ]);

        ReservationManagement::factory()
            ->forLessonSessionId($lessonSession->id)
            ->create([
                'reserved_count' => 0,
                'reserved_trial_count' => 1,
            ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('trial capacity is full');

        $this->service->book(
            userId: $user->id,
            lessonSessionId: $lessonSession->id,
            seatBucket: Reservation::SEAT_BUCKET_TRIAL,
            paymentMethod: Reservation::PAYMENT_METHOD_TRIAL_CARD
        );
    }

    public function test_book_throws_for_unsupported_seat_bucket(): void
    {
        $user = User::factory()->create();
        $lessonSession = LessonSession::factory()->create([
            'capacity' => 2,
            'trial_capacity' => 1,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported seat bucket');

        $this->service->book(
            userId: $user->id,
            lessonSessionId: $lessonSession->id,
            seatBucket: 'vip',
            paymentMethod: Reservation::PAYMENT_METHOD_TICKETS
        );
    }

    public function test_book_throws_when_lesson_session_does_not_exist(): void
    {
        $user = User::factory()->create();
        $nonExistentLessonSessionId = ((int) LessonSession::query()->max('id')) + 1;

        $this->expectException(ModelNotFoundException::class);
        $this->expectExceptionMessage('No query results for model [App\\Models\\LessonSession]');

        $this->service->book(
            userId: $user->id,
            lessonSessionId: $nonExistentLessonSessionId,
            seatBucket: Reservation::SEAT_BUCKET_NORMAL,
            paymentMethod: Reservation::PAYMENT_METHOD_TICKETS
        );
    }

    public function test_cancel_throws_until_ph4_3_is_implemented(): void
    {
        $reservation = Reservation::factory()->make();

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('PH4-3');

        $this->service->cancel($reservation, 'user_request');
    }
}
