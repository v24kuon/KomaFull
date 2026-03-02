<?php

namespace App\Jobs;

use App\Models\CourseEntitlement;
use App\Models\CourseEntitlementItem;
use App\Models\CoursePlan;
use App\Models\CoursePlanCategory;
use App\Models\WebhookLog;
use App\Services\WebhookEventIdGuard;
use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Subscription;
use Throwable;

class ProcessSubscriptionPaymentWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct(public readonly int $webhookLogId) {}

    /**
     * Process subscription-related webhooks and persist grant processing results.
     *
     * Preconditions:
     * - The webhook log must be atomically claimed by WebhookEventIdGuard.
     * - The target webhook log row must exist.
     *
     * Supported events:
     * - checkout.session.completed
     * - checkout.session.async_payment_succeeded
     * - invoice.payment_succeeded
     *
     * State transition policy:
     * - This method runs after claimForProcessing() and finalizes the row as
     *   processed or failed based on validation/business outcomes.
     * - Unsupported non-target events are treated as terminal processed.
     *
     * Idempotency and concurrency:
     * - claimForProcessing() is the first gate; if claim fails, this execution
     *   becomes a no-op and exits safely.
     *
     * Transaction boundary:
     * - This method does not open an outer transaction.
     * - invoice.payment_succeeded data mutations are delegated to
     *   processInvoicePaymentSucceeded(), which uses one DB transaction.
     * - Webhook status updates (markProcessed/markFailed) are executed outside
     *   that helper transaction.
     */
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

            if ($eventType === 'invoice.payment_succeeded') {
                $this->processInvoicePaymentSucceeded($connection, $payload);

                return;
            }

            if (! in_array($eventType, ['checkout.session.completed', 'checkout.session.async_payment_succeeded'], true)) {
                $this->markProcessed();

                return;
            }

            $checkoutSession = data_get($payload, 'data.object');

            if (! is_array($checkoutSession)) {
                $this->markFailed('checkout.session payload is missing.');

                return;
            }

            $checkoutSessionId = trim((string) ($checkoutSession['id'] ?? ''));

            if ($checkoutSessionId === '') {
                $this->markFailed('checkout.session.id is missing.');

                return;
            }

            $mode = trim((string) ($checkoutSession['mode'] ?? ''));

            if ($mode !== 'subscription') {
                $this->markFailed(sprintf(
                    'checkout.session.mode is unsupported for subscription processing: %s',
                    $mode
                ));

                return;
            }

            $paymentStatus = trim((string) ($checkoutSession['payment_status'] ?? ''));

            if ($paymentStatus === '') {
                $this->markFailed('checkout.session.payment_status is missing.');

                return;
            }

            if (! in_array($paymentStatus, ['paid', 'unpaid', 'no_payment_required'], true)) {
                $this->markFailed(sprintf('checkout.session.payment_status is unsupported: %s', $paymentStatus));

                return;
            }

            $this->markProcessed();
        } catch (Throwable $exception) {
            Log::error('Subscription webhook processing raised an exception.', [
                'webhook_log_id' => $this->webhookLogId,
                'exception_class' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            try {
                $this->markFailed('subscription webhook processing failed.');
            } catch (Throwable) {
                throw $exception;
            }
        }
    }

    /**
     * Process invoice.payment_succeeded and grant subscription entitlement.
     *
     * Side effects:
     * - Creates or reuses course_entitlements for the billed period.
     * - Creates or reuses course_entitlement_items for per-category plans.
     * - Updates webhook_logs to processed / failed.
     *
     * Preconditions:
     * - Payload must contain invoice object, subscription id, matching
     *   subscription line, line price.id, and a valid period range.
     *
     * State transition policy:
     * - This method performs business validation and data mutations.
     * - It returns a terminal result to the caller, and the caller applies the
     *   final webhook status update (processed/failed).
     *
     * Idempotency and concurrency:
     * - Subscription row is selected with lockForUpdate() to serialize
     *   competing updates for the same subscription.
     * - Entitlement/item creation uses createOrFirst() with DB unique
     *   constraints to avoid duplicate grants during retries/replays.
     * - Entitlement row is reloaded with lockForUpdate() before child item
     *   writes to keep dependent writes in the same lock scope.
     *
     * Transaction boundary:
     * - Subscription lookup, plan/category checks, and entitlement/item writes
     *   are executed in one DB transaction.
     * - webhook_logs status updates are intentionally outside this transaction.
     *
     * @param  array{
     *     data?: array{
     *         object?: array{
     *             subscription?: string,
     *             lines?: array{
     *                 data?: array<int, array{
     *                     type?: string,
     *                     subscription?: string,
     *                     price?: array{id?: string},
     *                     period?: array{start?: int, end?: int}
     *                 }>
     *             }
     *         }
     *     }
     * }  $payload
     */
    private function processInvoicePaymentSucceeded(ConnectionInterface $connection, array $payload): void
    {
        $invoice = data_get($payload, 'data.object');

        if (! is_array($invoice)) {
            $this->markFailed('invoice payload is missing.');

            return;
        }

        $subscriptionId = trim((string) ($invoice['subscription'] ?? ''));

        if ($subscriptionId === '') {
            $this->markFailed('invoice.subscription is missing.');

            return;
        }

        $subscriptionLine = $this->resolveSubscriptionLine($invoice, $subscriptionId);

        if ($subscriptionLine === null) {
            $this->markFailed('invoice subscription line is missing.');

            return;
        }

        $lineSubscriptionId = trim((string) data_get($subscriptionLine, 'subscription', ''));

        if ($lineSubscriptionId !== '' && $lineSubscriptionId !== $subscriptionId) {
            $this->markFailed(sprintf(
                'invoice line subscription mismatch: invoice=%s line=%s',
                $subscriptionId,
                $lineSubscriptionId
            ));

            return;
        }

        $priceId = trim((string) data_get($subscriptionLine, 'price.id', ''));
        $periodStart = (int) data_get($subscriptionLine, 'period.start', 0);
        $periodEnd = (int) data_get($subscriptionLine, 'period.end', 0);

        if ($priceId === '') {
            $this->markFailed('invoice line price.id is missing.');

            return;
        }

        if ($periodStart <= 0 || $periodEnd <= $periodStart) {
            $this->markFailed('invoice line period is invalid.');

            return;
        }

        $result = $connection->transaction(function () use (
            $subscriptionId,
            $priceId,
            $periodStart,
            $periodEnd
        ): array {
            $subscription = Subscription::query()
                ->where('stripe_id', $subscriptionId)
                ->lockForUpdate()
                ->first();

            if (! $subscription instanceof Subscription) {
                return [
                    'status' => 'failed',
                    'message' => sprintf('subscriptions not found for stripe_id: %s', $subscriptionId),
                ];
            }

            $coursePlan = CoursePlan::query()
                ->where('stripe_price_id', $priceId)
                ->first();

            if (! $coursePlan instanceof CoursePlan) {
                return [
                    'status' => 'failed',
                    'message' => sprintf('course_plans not found for stripe_price_id: %s', $priceId),
                ];
            }

            $planCategories = null;

            if ($coursePlan->allocation_type === CoursePlan::ALLOCATION_TYPE_PER_CATEGORY) {
                $planCategories = CoursePlanCategory::query()
                    ->where('course_plan_id', $coursePlan->id)
                    ->get();

                if ($planCategories->isEmpty()) {
                    return [
                        'status' => 'failed',
                        'message' => sprintf(
                            'course_plan_categories not found for per_category plan: %d',
                            $coursePlan->id
                        ),
                    ];
                }
            }

            $periodStartDate = CarbonImmutable::createFromTimestampUTC($periodStart)->toDateString();
            $periodEndDate = CarbonImmutable::createFromTimestampUTC($periodEnd)->subSecond()->toDateString();

            $entitlement = CourseEntitlement::query()->createOrFirst(
                [
                    'user_id' => (int) $subscription->user_id,
                    'course_plan_id' => (int) $coursePlan->id,
                    'period_start' => $periodStartDate,
                    'period_end' => $periodEndDate,
                ],
                [
                    'granted_uses' => (int) $coursePlan->usage_count,
                    'used_uses' => 0,
                ]
            );

            $lockedEntitlement = CourseEntitlement::query()
                ->whereKey($entitlement->id)
                ->lockForUpdate()
                ->first();

            if (! $lockedEntitlement instanceof CourseEntitlement) {
                return [
                    'status' => 'failed',
                    'message' => sprintf('course_entitlements not found for id: %d', (int) $entitlement->id),
                ];
            }

            if ($planCategories !== null) {
                foreach ($planCategories as $planCategory) {
                    CourseEntitlementItem::query()->createOrFirst(
                        [
                            'course_entitlement_id' => $lockedEntitlement->id,
                            'category_id' => $planCategory->category_id,
                        ],
                        [
                            'granted_uses' => (int) $coursePlan->usage_count,
                            'used_uses' => 0,
                        ]
                    );
                }
            }

            return ['status' => 'processed'];
        });

        if ($result['status'] === 'failed') {
            $this->markFailed((string) $result['message']);

            return;
        }

        $this->markProcessed();
    }

    /**
     * Handle terminal queue failure after retries are exhausted.
     *
     * When this callback runs, the webhook log may still be in received /
     * processing state. This method force-transitions it to failed so the
     * recovery dashboard can detect and replay safely.
     */
    public function failed(Throwable $exception): void
    {
        WebhookLog::query()
            ->whereKey($this->webhookLogId)
            ->whereIn('status', [WebhookLog::STATUS_RECEIVED, WebhookLog::STATUS_PROCESSING])
            ->update([
                'status' => WebhookLog::STATUS_FAILED,
                'error_message' => 'subscription webhook job failed before completion.',
            ]);

        Log::warning('Subscription webhook job failed and entered terminal state.', [
            'webhook_log_id' => $this->webhookLogId,
            'exception_class' => $exception::class,
            'message' => $exception->getMessage(),
        ]);
    }

    /**
     * Resolve a candidate subscription line object from an invoice payload.
     *
     * Preference order:
     * - A line whose `subscription` equals the target subscription ID.
     * - A line whose `subscription` is empty.
     * - The first subscription-type line.
     *
     * @param  array<string, mixed>  $invoice
     * @return array<string, mixed>|null
     */
    private function resolveSubscriptionLine(array $invoice, string $subscriptionId): ?array
    {
        $lines = data_get($invoice, 'lines.data');

        if (! is_array($lines)) {
            return null;
        }

        $firstSubscriptionLine = null;
        $subscriptionlessLine = null;

        foreach ($lines as $line) {
            if (! is_array($line)) {
                continue;
            }

            if ((string) ($line['type'] ?? '') !== 'subscription') {
                continue;
            }

            $firstSubscriptionLine ??= $line;

            $lineSubscriptionId = trim((string) ($line['subscription'] ?? ''));

            if ($lineSubscriptionId === $subscriptionId) {
                return $line;
            }

            if ($lineSubscriptionId === '' && $subscriptionlessLine === null) {
                $subscriptionlessLine = $line;
            }
        }

        return $subscriptionlessLine ?? $firstSubscriptionLine;
    }

    /**
     * Mark webhook log as processed.
     *
     * State transition policy:
     * - Sets terminal status to processed.
     * - Stores processed_at timestamp and clears error_message.
     *
     * Idempotency:
     * - Repeated calls are logically safe for status, but processed_at is
     *   overwritten with the latest timestamp.
     *
     * Transaction boundary:
     * - Single-row update without an explicit transaction.
     */
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

    /**
     * Mark webhook log as failed with an error message.
     *
     * State transition policy:
     * - Sets terminal status to failed and records the latest failure reason.
     * - Leaves processed_at unchanged.
     *
     * Idempotency:
     * - Repeated calls overwrite error_message with the newest message.
     *
     * Transaction boundary:
     * - Single-row update without an explicit transaction.
     */
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
