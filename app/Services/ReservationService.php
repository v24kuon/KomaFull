<?php

namespace App\Services;

use App\Models\LessonSession;
use App\Models\Reservation;
use App\Models\ReservationManagement;
use BadMethodCallException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

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
        return DB::transaction(function () use (
            $userId,
            $lessonSessionId,
            $seatBucket,
            $paymentMethod,
            $options
        ): Reservation {
            [$reservedCountColumn, $capacity] = $this->resolveCounterColumnAndCapacity(
                lessonSessionId: $lessonSessionId,
                seatBucket: $seatBucket
            );

            ReservationManagement::query()->firstOrCreate(
                ['lesson_session_id' => $lessonSessionId],
                [
                    'reserved_count' => 0,
                    'reserved_trial_count' => 0,
                ]
            );

            $reservationManagement = ReservationManagement::query()
                ->where('lesson_session_id', $lessonSessionId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($reservationManagement->{$reservedCountColumn} >= $capacity) {
                throw new RuntimeException(sprintf('%s capacity is full.', $seatBucket));
            }

            $reservation = Reservation::query()->create([
                'code' => $this->generateUniqueReservationCode(),
                'user_id' => $userId,
                'lesson_session_id' => $lessonSessionId,
                'seat_bucket' => $seatBucket,
                'payment_method' => $paymentMethod,
                'status' => Reservation::STATUS_CONFIRMED,
                'ticket_cost' => max(0, (int) ($options['ticket_cost'] ?? 0)),
                'point_cost' => max(0, (int) ($options['point_cost'] ?? 0)),
                'course_entitlement_id' => isset($options['course_entitlement_id'])
                    ? (int) $options['course_entitlement_id']
                    : null,
            ]);

            $reservationManagement->increment($reservedCountColumn);

            return $reservation->fresh();
        });
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

    /**
     * @return array{0: 'reserved_count'|'reserved_trial_count', 1: int}
     */
    private function resolveCounterColumnAndCapacity(int $lessonSessionId, string $seatBucket): array
    {
        $lessonSession = LessonSession::query()->findOrFail($lessonSessionId);

        return match ($seatBucket) {
            Reservation::SEAT_BUCKET_NORMAL => ['reserved_count', $lessonSession->capacity],
            Reservation::SEAT_BUCKET_TRIAL => ['reserved_trial_count', $lessonSession->trial_capacity],
            default => throw new InvalidArgumentException(sprintf('Unsupported seat bucket: %s', $seatBucket)),
        };
    }

    private function generateUniqueReservationCode(): string
    {
        for ($attempt = 0; $attempt < 100; $attempt++) {
            $code = sprintf('R%06d', random_int(0, 999999));

            if (! Reservation::query()->where('code', $code)->exists()) {
                return $code;
            }
        }

        throw new RuntimeException('Failed to generate unique reservation code.');
    }
}
