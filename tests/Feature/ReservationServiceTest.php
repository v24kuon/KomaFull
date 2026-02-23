<?php

namespace Tests\Feature;

use App\Models\LessonSession;
use App\Models\Reservation;
use App\Models\ReservationManagement;
use App\Models\User;
use App\Services\ReservationService;
use BadMethodCallException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class ReservationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_can_be_resolved_from_container(): void
    {
        $service = app(ReservationService::class);

        $this->assertInstanceOf(ReservationService::class, $service);
    }

    public function test_book_creates_normal_reservation_and_increments_reserved_count(): void
    {
        $service = app(ReservationService::class);
        $user = User::factory()->create();
        $lessonSession = $this->createLessonSession(capacity: 1, trialCapacity: 1);

        ReservationManagement::factory()
            ->forLessonSessionId($lessonSession->id)
            ->create([
                'reserved_count' => 0,
                'reserved_trial_count' => 0,
            ]);

        $reservation = $service->book(
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
        $this->assertMatchesRegularExpression('/^R\d{6}$/', $reservation->code);

        $reservationManagement = ReservationManagement::query()
            ->where('lesson_session_id', $lessonSession->id)
            ->firstOrFail();
        $this->assertSame(1, $reservationManagement->reserved_count);
        $this->assertSame(0, $reservationManagement->reserved_trial_count);
    }

    public function test_book_creates_trial_reservation_and_increments_reserved_trial_count(): void
    {
        $service = app(ReservationService::class);
        $user = User::factory()->create();
        $lessonSession = $this->createLessonSession(capacity: 2, trialCapacity: 1);

        ReservationManagement::factory()
            ->forLessonSessionId($lessonSession->id)
            ->create([
                'reserved_count' => 0,
                'reserved_trial_count' => 0,
            ]);

        $reservation = $service->book(
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
        $service = app(ReservationService::class);
        $user = User::factory()->create();
        $lessonSession = $this->createLessonSession(capacity: 2, trialCapacity: 1);

        $this->assertDatabaseMissing('reservation_management', [
            'lesson_session_id' => $lessonSession->id,
        ]);

        $service->book(
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
        $service = app(ReservationService::class);
        $user = User::factory()->create();
        $lessonSession = $this->createLessonSession(capacity: 1, trialCapacity: 1);

        ReservationManagement::factory()
            ->forLessonSessionId($lessonSession->id)
            ->create([
                'reserved_count' => 1,
                'reserved_trial_count' => 0,
            ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('normal capacity is full');

        $service->book(
            userId: $user->id,
            lessonSessionId: $lessonSession->id,
            seatBucket: Reservation::SEAT_BUCKET_NORMAL,
            paymentMethod: Reservation::PAYMENT_METHOD_TICKETS
        );
    }

    public function test_book_throws_when_trial_capacity_is_full(): void
    {
        $service = app(ReservationService::class);
        $user = User::factory()->create();
        $lessonSession = $this->createLessonSession(capacity: 2, trialCapacity: 1);

        ReservationManagement::factory()
            ->forLessonSessionId($lessonSession->id)
            ->create([
                'reserved_count' => 0,
                'reserved_trial_count' => 1,
            ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('trial capacity is full');

        $service->book(
            userId: $user->id,
            lessonSessionId: $lessonSession->id,
            seatBucket: Reservation::SEAT_BUCKET_TRIAL,
            paymentMethod: Reservation::PAYMENT_METHOD_TRIAL_CARD
        );
    }

    public function test_book_throws_for_unsupported_seat_bucket(): void
    {
        $service = app(ReservationService::class);
        $user = User::factory()->create();
        $lessonSession = $this->createLessonSession(capacity: 2, trialCapacity: 1);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported seat bucket');

        $service->book(
            userId: $user->id,
            lessonSessionId: $lessonSession->id,
            seatBucket: 'vip',
            paymentMethod: Reservation::PAYMENT_METHOD_TICKETS
        );
    }

    public function test_cancel_throws_until_ph4_3_is_implemented(): void
    {
        $service = app(ReservationService::class);
        $reservation = Reservation::factory()->make([
            'user_id' => 1,
            'lesson_session_id' => 1,
        ]);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('PH4-3');

        $service->cancel($reservation, 'user_request');
    }

    private function createLessonSession(int $capacity, int $trialCapacity): LessonSession
    {
        $suffix = uniqid();
        $now = now();

        $categoryId = DB::table('categories')->insertGetId([
            'code' => 'CAT-'.$suffix,
            'name' => 'Category '.$suffix,
            'sort_order' => 1,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $programTypeId = DB::table('program_types')->insertGetId([
            'code' => 'PT-'.$suffix,
            'name' => 'ProgramType '.$suffix,
            'sort_order' => 1,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $programId = DB::table('programs')->insertGetId([
            'code' => 'PRG-'.$suffix,
            'category_id' => $categoryId,
            'program_type_id' => $programTypeId,
            'name' => 'Program '.$suffix,
            'duration_minutes' => 60,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $locationId = DB::table('locations')->insertGetId([
            'code' => 'LOC-'.$suffix,
            'name' => 'Location '.$suffix,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $staffId = DB::table('staffs')->insertGetId([
            'code' => 'STF-'.$suffix,
            'name' => 'Staff '.$suffix,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $lessonSessionId = DB::table('lesson_sessions')->insertGetId([
            'code' => 'LS-'.$suffix,
            'program_id' => $programId,
            'location_id' => $locationId,
            'staff_id' => $staffId,
            'starts_at' => now()->addDay(),
            'capacity' => $capacity,
            'trial_capacity' => $trialCapacity,
            'status' => 'active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return LessonSession::query()->findOrFail($lessonSessionId);
    }
}
