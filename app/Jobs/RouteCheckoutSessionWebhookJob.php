<?php

namespace App\Jobs;

use App\Models\PrepaidPurchase;
use App\Models\TrialApplication;
use App\Models\WebhookLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class RouteCheckoutSessionWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 8;

    public function __construct(public readonly int $webhookLogId) {}

    /**
     * @return list<int>
     */
    public function backoff(): array
    {
        return [10, 30, 60, 120, 300, 600, 900];
    }

    /**
     * Route a checkout-session webhook to the domain-specific processing job.
     *
     * This method resolves a target by checkout_session_id and dispatches either
     * trial or prepaid processing. If no target exists yet, it reschedules itself
     * with backoff so record creation races can settle.
     *
     * Side effects:
     * - May dispatch ProcessTrialPaymentWebhookJob or ProcessPrepaidPaymentWebhookJob.
     * - May dispatch ProcessSubscriptionPaymentWebhookJob for subscription mode.
     * - May update webhook_logs to failed via markFailed() for terminal states.
     * - May release this job back to the queue for delayed retry.
     * - Rethrows when rescheduling fails so queue retry/failed() can take over.
     *
     * Consistency guarantees:
     * - Routing is attempted only while webhook_logs.status is received.
     * - Repeated execution is safe because status checks and conditional updates
     *   prevent overwriting processing/proceeding terminal states.
     * - Retry boundary is controlled by $tries and backoff(); once attempts reach
     *   the boundary this method marks the webhook as failed.
     */
    public function handle(): void
    {
        $webhookLog = WebhookLog::query()->find($this->webhookLogId);

        if (! $webhookLog instanceof WebhookLog) {
            return;
        }

        if ($webhookLog->status !== WebhookLog::STATUS_RECEIVED) {
            return;
        }

        $payload = $webhookLog->payload;
        $eventType = (string) ($payload['type'] ?? '');

        if (! in_array($eventType, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
            return;
        }

        $checkoutSessionId = trim((string) data_get($payload, 'data.object.id', ''));
        $checkoutMode = trim((string) data_get($payload, 'data.object.mode', ''));

        if ($checkoutSessionId === '') {
            $this->markFailed('checkout.session.id is missing.');

            return;
        }

        if ($checkoutMode === 'subscription') {
            ProcessSubscriptionPaymentWebhookJob::dispatch($webhookLog->id);

            return;
        }

        $trialExists = TrialApplication::query()
            ->where('stripe_checkout_session_id', $checkoutSessionId)
            ->exists();

        $prepaidExists = PrepaidPurchase::query()
            ->where('stripe_checkout_session_id', $checkoutSessionId)
            ->exists();

        if ($trialExists && $prepaidExists) {
            $this->markFailed(sprintf(
                'Multiple checkout targets found for checkout_session_id: %s',
                $checkoutSessionId
            ));

            Log::error('Stripe checkout webhook has multiple checkout targets.', [
                'event_id' => $webhookLog->event_id,
                'checkout_session_id' => $checkoutSessionId,
                'webhook_log_id' => $webhookLog->id,
            ]);

            return;
        }

        if ($trialExists) {
            if ($eventType !== 'checkout.session.completed') {
                $this->markFailed(sprintf(
                    'Unsupported checkout.session event type for trial: %s',
                    $eventType
                ));

                return;
            }

            ProcessTrialPaymentWebhookJob::dispatch($webhookLog->id);

            return;
        }

        if ($prepaidExists) {
            ProcessPrepaidPaymentWebhookJob::dispatch($webhookLog->id);

            return;
        }

        Log::info('Stripe checkout webhook target not found yet; will retry routing.', [
            'event_id' => $webhookLog->event_id,
            'event_type' => $eventType,
            'checkout_session_id' => $checkoutSessionId,
            'webhook_log_id' => $webhookLog->id,
            'attempt' => $this->attempts(),
        ]);

        if ($this->attempts() >= $this->tries) {
            $this->markFailed(sprintf(
                'No checkout target found for checkout_session_id after retries: %s',
                $checkoutSessionId
            ));

            return;
        }

        try {
            $this->release($this->retryDelaySeconds());
        } catch (Throwable $exception) {
            Log::warning('Stripe checkout webhook routing could not be rescheduled.', [
                'event_id' => $webhookLog->event_id,
                'event_type' => $eventType,
                'checkout_session_id' => $checkoutSessionId,
                'webhook_log_id' => $webhookLog->id,
                'attempt' => $this->attempts(),
                'queue_connection' => config('queue.default'),
                'exception_class' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * @return int seconds
     */
    private function retryDelaySeconds(): int
    {
        $backoffs = $this->backoff();
        $index = max(0, $this->attempts() - 1);

        return $backoffs[min($index, count($backoffs) - 1)];
    }

    /**
     * Mark the webhook log as failed if it is still pending routing.
     *
     * Side effects:
     * - Updates webhook_logs.status to failed and stores error_message.
     *
     * Consistency guarantees:
     * - The update is conditional on status=received so duplicate workers do not
     *   overwrite rows already claimed/processed by downstream jobs.
     */
    private function markFailed(string $message): void
    {
        WebhookLog::query()
            ->whereKey($this->webhookLogId)
            ->where('status', WebhookLog::STATUS_RECEIVED)
            ->update([
                'status' => WebhookLog::STATUS_FAILED,
                'error_message' => $message,
            ]);
    }

    /**
     * Handle terminal queue failure after retries are exhausted.
     *
     * This callback runs when the queue worker marks the job as failed due to an
     * unhandled exception or retry exhaustion. It records a terminal failure on
     * webhook_logs only when the row is still in received state.
     *
     * Side effects:
     * - May update webhook_logs to failed via markFailed().
     * - Emits a warning log with failure context.
     *
     * Consistency guarantees:
     * - No-op when webhook_logs has already transitioned from received, avoiding
     *   accidental overwrite of concurrent processor outcomes.
     */
    public function failed(Throwable $exception): void
    {
        $webhookLog = WebhookLog::query()->find($this->webhookLogId);

        if (! $webhookLog instanceof WebhookLog) {
            return;
        }

        if ($webhookLog->status !== WebhookLog::STATUS_RECEIVED) {
            return;
        }

        $checkoutSessionId = trim((string) data_get($webhookLog->payload, 'data.object.id', ''));

        $this->markFailed($checkoutSessionId !== ''
            ? sprintf('No checkout target found for checkout_session_id after retries: %s', $checkoutSessionId)
            : 'No checkout target found after retries.');

        Log::warning('Stripe checkout webhook routing exceeded retries.', [
            'event_id' => $webhookLog->event_id,
            'checkout_session_id' => $checkoutSessionId !== '' ? $checkoutSessionId : null,
            'webhook_log_id' => $webhookLog->id,
            'exception_class' => $exception::class,
            'message' => $exception->getMessage(),
        ]);
    }
}
