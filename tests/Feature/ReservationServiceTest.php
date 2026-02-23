<?php

namespace Tests\Feature;

use App\Models\Reservation;
use App\Services\ReservationService;
use BadMethodCallException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReservationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_service_can_be_resolved_from_container(): void
    {
        $service = app(ReservationService::class);

        $this->assertInstanceOf(ReservationService::class, $service);
    }

    public function test_book_throws_until_ph4_2_is_implemented(): void
    {
        $service = app(ReservationService::class);

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('PH4-2');

        $service->book(1, 1, Reservation::SEAT_BUCKET_NORMAL, Reservation::PAYMENT_METHOD_TICKETS);
    }

    public function test_cancel_throws_until_ph4_3_is_implemented(): void
    {
        $service = app(ReservationService::class);
        $reservation = new Reservation;

        $this->expectException(BadMethodCallException::class);
        $this->expectExceptionMessage('PH4-3');

        $service->cancel($reservation, 'user_request');
    }
}
