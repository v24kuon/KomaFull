<?php

namespace App\Services;

use App\Models\Reservation;
use BadMethodCallException;

class ReservationService
{
    /**
     * Book a reservation for a lesson session.
     *
     * TODO(PH4-2): Implement booking transaction and lock logic.
     *
     * @param  array<string, mixed>  $options
     */
    public function book(
        int $userId,
        int $lessonSessionId,
        string $seatBucket,
        string $paymentMethod,
        array $options = []
    ): Reservation {
        throw new BadMethodCallException('ReservationService::book() is not implemented yet. See PH4-2.');
    }

    /**
     * Cancel an existing reservation.
     *
     * TODO(PH4-3): Implement cancellation and rollback logic.
     */
    public function cancel(Reservation $reservation, string $reason): Reservation
    {
        throw new BadMethodCallException('ReservationService::cancel() is not implemented yet. See PH4-3.');
    }
}
