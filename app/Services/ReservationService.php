<?php

namespace App\Services;

use App\Models\LessonSession;
use App\Models\Reservation;
use App\Models\ReservationManagement;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

class ReservationService
{
    public function __construct(private ConnectionInterface $connection) {}

    /**
     * Book a reservation for a lesson session.
     *
     * @param  array{ticket_cost?: int, point_cost?: int, course_entitlement_id?: int}  $options
     */
    public function book(
        int $userId,
        int $lessonSessionId,
        string $seatBucket,
        string $paymentMethod,
        array $options = []
    ): Reservation {
        return $this->connection->transaction(function () use (
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

            ReservationManagement::query()->createOrFirst(
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

            return $reservation;
        });
    }

    /**
     * Cancel an existing reservation.
     */
    public function cancel(Reservation $reservation, string $reason): Reservation
    {
        $normalizedReason = trim($reason);

        if ($normalizedReason === '') {
            throw new InvalidArgumentException('Cancel reason cannot be empty.');
        }

        if (! $reservation->exists) {
            throw new InvalidArgumentException('Reservation must exist before cancellation.');
        }

        return $this->connection->transaction(function () use ($reservation, $normalizedReason): Reservation {
            $lockedReservation = Reservation::query()
                ->whereKey($reservation->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedReservation->status === Reservation::STATUS_CANCELED) {
                return $lockedReservation;
            }

            $reservedCountColumn = $this->resolveReservedCountColumn($lockedReservation->seat_bucket);

            $reservationManagement = ReservationManagement::query()
                ->where('lesson_session_id', $lockedReservation->lesson_session_id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($reservationManagement->{$reservedCountColumn} < 1) {
                throw new RuntimeException('Reservation management counter is inconsistent.');
            }

            $reservationManagement->decrement($reservedCountColumn);

            $lockedReservation->update([
                'status' => Reservation::STATUS_CANCELED,
                'canceled_at' => now(),
                'cancel_reason' => $normalizedReason,
            ]);

            return $lockedReservation;
        });
    }

    /**
     * @return array{0: 'reserved_count'|'reserved_trial_count', 1: int}
     */
    private function resolveCounterColumnAndCapacity(int $lessonSessionId, string $seatBucket): array
    {
        $lessonSession = LessonSession::query()->findOrFail($lessonSessionId);
        $reservedCountColumn = $this->resolveReservedCountColumn($seatBucket);

        return [
            $reservedCountColumn,
            $reservedCountColumn === 'reserved_count'
                ? $lessonSession->capacity
                : $lessonSession->trial_capacity,
        ];
    }

    /**
     * @return 'reserved_count'|'reserved_trial_count'
     */
    private function resolveReservedCountColumn(string $seatBucket): string
    {
        return match ($seatBucket) {
            Reservation::SEAT_BUCKET_NORMAL => 'reserved_count',
            Reservation::SEAT_BUCKET_TRIAL => 'reserved_trial_count',
            default => throw new InvalidArgumentException(sprintf('Unsupported seat bucket: %s', $seatBucket)),
        };
    }

    private function generateUniqueReservationCode(): string
    {
        return 'R'.strtoupper((string) Str::ulid());
    }
}
