<?php

namespace App\Jobs;

use App\Models\LessonSession;
use App\Models\Reservation;
use App\Models\ReservationManagement;
use App\Models\TrialApplication;
use App\Models\WebhookLog;
use App\Services\WebhookEventIdGuard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

class ProcessTrialPaymentWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly int $webhookLogId) {}

    public function handle(ConnectionInterface $connection, WebhookEventIdGuard $guard): void
    {
        if (! $guard->claimForProcessing($this->webhookLogId)) {
            return;
        }

        $webhookLog = WebhookLog::query()->find($this->webhookLogId);

        if (! $webhookLog instanceof WebhookLog) {
            return;
        }

        try {
            $payload = $webhookLog->payload;
            $eventType = (string) ($payload['type'] ?? '');

            if ($eventType !== 'checkout.session.completed') {
                $this->markProcessed();

                return;
            }

            $checkoutSession = $payload['data']['object'] ?? null;

            if (! is_array($checkoutSession)) {
                $this->markFailed('checkout.session payload is missing.');

                return;
            }

            $checkoutSessionId = trim((string) ($checkoutSession['id'] ?? ''));

            if ($checkoutSessionId === '') {
                $this->markFailed('checkout.session.id is missing.');

                return;
            }

            $paymentIntentId = trim((string) ($checkoutSession['payment_intent'] ?? ''));

            $result = $connection->transaction(function () use ($checkoutSessionId): array {
                $trialApplication = TrialApplication::query()
                    ->where('stripe_checkout_session_id', $checkoutSessionId)
                    ->lockForUpdate()
                    ->first();

                if (! $trialApplication instanceof TrialApplication) {
                    return [
                        'status' => 'failed',
                        'message' => sprintf(
                            'trial_applications not found for checkout_session_id: %s',
                            $checkoutSessionId
                        ),
                    ];
                }

                if (in_array(
                    $trialApplication->status,
                    [
                        TrialApplication::STATUS_RESERVED,
                        TrialApplication::STATUS_REFUND_PENDING,
                        TrialApplication::STATUS_REFUNDED,
                    ],
                    true
                )) {
                    return ['status' => 'processed'];
                }

                ReservationManagement::query()->createOrFirst(
                    ['lesson_session_id' => $trialApplication->lesson_session_id],
                    [
                        'reserved_count' => 0,
                        'reserved_trial_count' => 0,
                    ]
                );

                $reservationManagement = ReservationManagement::query()
                    ->where('lesson_session_id', $trialApplication->lesson_session_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                $lessonSession = LessonSession::query()->findOrFail($trialApplication->lesson_session_id);

                if ($reservationManagement->reserved_trial_count >= $lessonSession->trial_capacity) {
                    $trialApplication->update([
                        'status' => TrialApplication::STATUS_REFUND_PENDING,
                    ]);

                    return [
                        'status' => 'refund_pending',
                        'trial_application_id' => $trialApplication->id,
                    ];
                }

                $reservation = Reservation::query()->create([
                    'code' => 'R'.strtoupper((string) Str::ulid()),
                    'user_id' => $trialApplication->user_id,
                    'lesson_session_id' => $trialApplication->lesson_session_id,
                    'seat_bucket' => Reservation::SEAT_BUCKET_TRIAL,
                    'payment_method' => Reservation::PAYMENT_METHOD_TRIAL_CARD,
                    'status' => Reservation::STATUS_CONFIRMED,
                    'ticket_cost' => 0,
                    'point_cost' => 0,
                    'course_entitlement_id' => null,
                ]);

                $reservationManagement->increment('reserved_trial_count');

                $trialApplication->update([
                    'status' => TrialApplication::STATUS_RESERVED,
                    'reservation_id' => $reservation->id,
                ]);

                return ['status' => 'processed'];
            });

            if ($result['status'] === 'failed') {
                $this->markFailed((string) $result['message']);

                return;
            }

            $this->markProcessed();

            if ($result['status'] === 'refund_pending') {
                ProcessTrialRefundJob::dispatch(
                    trialApplicationId: (int) $result['trial_application_id'],
                    paymentIntentId: $paymentIntentId !== '' ? $paymentIntentId : null
                );
            }
        } catch (Throwable $exception) {
            $this->markFailed(sprintf('trial checkout processing failed: %s', $exception->getMessage()));
        }
    }

    private function markProcessed(): void
    {
        WebhookLog::query()
            ->whereKey($this->webhookLogId)
            ->update([
                'status' => WebhookLog::STATUS_PROCESSED,
                'processed_at' => now(),
                'error_message' => null,
            ]);
    }

    private function markFailed(string $message): void
    {
        WebhookLog::query()
            ->whereKey($this->webhookLogId)
            ->update([
                'status' => WebhookLog::STATUS_FAILED,
                'error_message' => $message,
            ]);
    }
}
