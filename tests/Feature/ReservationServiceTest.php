<?php

namespace Tests\Feature;

use App\Models\LessonSession;
use App\Models\Reservation;
use App\Models\ReservationManagement;
use App\Models\User;
use App\Services\ReservationService;
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

        try {
            $this->service->book(
                userId: $user->id,
                lessonSessionId: $lessonSession->id,
                seatBucket: Reservation::SEAT_BUCKET_NORMAL,
                paymentMethod: Reservation::PAYMENT_METHOD_TICKETS
            );
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('normal capacity is full', $exception->getMessage());
        }

        $this->assertDatabaseMissing('reservations', [
            'user_id' => $user->id,
            'lesson_session_id' => $lessonSession->id,
        ]);

        $reservationManagement = ReservationManagement::query()
            ->where('lesson_session_id', $lessonSession->id)
            ->firstOrFail();
        $this->assertSame(1, $reservationManagement->reserved_count);
        $this->assertSame(0, $reservationManagement->reserved_trial_count);
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

        try {
            $this->service->book(
                userId: $user->id,
                lessonSessionId: $lessonSession->id,
                seatBucket: Reservation::SEAT_BUCKET_TRIAL,
                paymentMethod: Reservation::PAYMENT_METHOD_TRIAL_CARD
            );
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('trial capacity is full', $exception->getMessage());
        }

        $this->assertDatabaseMissing('reservations', [
            'user_id' => $user->id,
            'lesson_session_id' => $lessonSession->id,
        ]);

        $reservationManagement = ReservationManagement::query()
            ->where('lesson_session_id', $lessonSession->id)
            ->firstOrFail();
        $this->assertSame(0, $reservationManagement->reserved_count);
        $this->assertSame(1, $reservationManagement->reserved_trial_count);
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

    public function test_cancel_decrements_reserved_count_and_marks_reservation_as_canceled(): void
    {
        $user = User::factory()->create();
        $lessonSession = LessonSession::factory()->create([
            'capacity' => 2,
            'trial_capacity' => 1,
        ]);

        ReservationManagement::factory()
            ->forLessonSessionId($lessonSession->id)
            ->create([
                'reserved_count' => 1,
                'reserved_trial_count' => 0,
            ]);

        $reservation = Reservation::factory()
            ->forRelationIds($lessonSession->id, $user->id)
            ->create([
                'seat_bucket' => Reservation::SEAT_BUCKET_NORMAL,
                'status' => Reservation::STATUS_CONFIRMED,
            ]);

        $canceledReservation = $this->service->cancel($reservation, 'user_request');

        $this->assertSame(Reservation::STATUS_CANCELED, $canceledReservation->status);
        $this->assertNotNull($canceledReservation->canceled_at);
        $this->assertSame('user_request', $canceledReservation->cancel_reason);

        $reservationManagement = ReservationManagement::query()
            ->where('lesson_session_id', $lessonSession->id)
            ->firstOrFail();
        $this->assertSame(0, $reservationManagement->reserved_count);
        $this->assertSame(0, $reservationManagement->reserved_trial_count);
    }

    public function test_cancel_decrements_reserved_trial_count_for_trial_reservation(): void
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

        $reservation = Reservation::factory()
            ->forRelationIds($lessonSession->id, $user->id)
            ->trial()
            ->create([
                'status' => Reservation::STATUS_CONFIRMED,
            ]);

        $canceledReservation = $this->service->cancel($reservation, 'trial_cancel');

        $this->assertSame(Reservation::STATUS_CANCELED, $canceledReservation->status);
        $this->assertNotNull($canceledReservation->canceled_at);
        $this->assertSame('trial_cancel', $canceledReservation->cancel_reason);

        $reservationManagement = ReservationManagement::query()
            ->where('lesson_session_id', $lessonSession->id)
            ->firstOrFail();
        $this->assertSame(0, $reservationManagement->reserved_count);
        $this->assertSame(0, $reservationManagement->reserved_trial_count);
    }

    public function test_cancel_is_idempotent_for_already_canceled_reservation(): void
    {
        $user = User::factory()->create();
        $lessonSession = LessonSession::factory()->create([
            'capacity' => 2,
            'trial_capacity' => 1,
        ]);

        ReservationManagement::factory()
            ->forLessonSessionId($lessonSession->id)
            ->create([
                'reserved_count' => 1,
                'reserved_trial_count' => 0,
            ]);

        $reservation = Reservation::factory()
            ->forRelationIds($lessonSession->id, $user->id)
            ->canceled()
            ->create([
                'seat_bucket' => Reservation::SEAT_BUCKET_NORMAL,
                'cancel_reason' => 'already_canceled',
            ]);

        $originalCanceledAt = $reservation->canceled_at;

        $canceledReservation = $this->service->cancel($reservation, 'duplicate_request');

        $this->assertSame(Reservation::STATUS_CANCELED, $canceledReservation->status);
        $this->assertEquals($originalCanceledAt, $canceledReservation->canceled_at);
        $this->assertSame('already_canceled', $canceledReservation->cancel_reason);

        $reservationManagement = ReservationManagement::query()
            ->where('lesson_session_id', $lessonSession->id)
            ->firstOrFail();
        $this->assertSame(1, $reservationManagement->reserved_count);
        $this->assertSame(0, $reservationManagement->reserved_trial_count);
    }

    public function test_cancel_throws_when_reason_is_empty(): void
    {
        $reservation = Reservation::factory()->create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Cancel reason cannot be empty.');

        $this->service->cancel($reservation, '   ');
    }

    public function test_cancel_throws_when_reservation_management_is_missing(): void
    {
        $user = User::factory()->create();
        $lessonSession = LessonSession::factory()->create([
            'capacity' => 2,
            'trial_capacity' => 1,
        ]);

        $reservation = Reservation::factory()
            ->forRelationIds($lessonSession->id, $user->id)
            ->create([
                'seat_bucket' => Reservation::SEAT_BUCKET_NORMAL,
                'status' => Reservation::STATUS_CONFIRMED,
            ]);

        $this->assertDatabaseMissing('reservation_management', [
            'lesson_session_id' => $lessonSession->id,
        ]);

        try {
            $this->service->cancel($reservation, 'user_request');
            $this->fail('Expected ModelNotFoundException was not thrown.');
        } catch (ModelNotFoundException $exception) {
            $this->assertStringContainsString('ReservationManagement', $exception->getMessage());
        }

        $stillConfirmedReservation = Reservation::query()->findOrFail($reservation->id);
        $this->assertSame(Reservation::STATUS_CONFIRMED, $stillConfirmedReservation->status);
        $this->assertNull($stillConfirmedReservation->canceled_at);
        $this->assertNull($stillConfirmedReservation->cancel_reason);
    }

    public function test_cancel_throws_when_management_counter_is_inconsistent(): void
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

        $reservation = Reservation::factory()
            ->forRelationIds($lessonSession->id, $user->id)
            ->create([
                'seat_bucket' => Reservation::SEAT_BUCKET_NORMAL,
                'status' => Reservation::STATUS_CONFIRMED,
            ]);

        try {
            $this->service->cancel($reservation, 'user_request');
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('counter is inconsistent', $exception->getMessage());
        }

        $reservationManagement = ReservationManagement::query()
            ->where('lesson_session_id', $lessonSession->id)
            ->firstOrFail();
        $this->assertSame(0, $reservationManagement->reserved_count);
        $this->assertSame(0, $reservationManagement->reserved_trial_count);

        $stillConfirmedReservation = Reservation::query()->findOrFail($reservation->id);
        $this->assertSame(Reservation::STATUS_CONFIRMED, $stillConfirmedReservation->status);
        $this->assertNull($stillConfirmedReservation->canceled_at);
        $this->assertNull($stillConfirmedReservation->cancel_reason);
    }
}
