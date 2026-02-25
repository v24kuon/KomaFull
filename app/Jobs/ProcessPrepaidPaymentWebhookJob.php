<?php

namespace App\Jobs;

use App\Models\BalanceTransaction;
use App\Models\PrepaidProduct;
use App\Models\PrepaidPurchase;
use App\Models\WebhookLog;
use App\Services\WebhookEventIdGuard;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use InvalidArgumentException;
use Throwable;

class ProcessPrepaidPaymentWebhookJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public function __construct(public readonly int $webhookLogId) {}

    /**
     * Process prepaid checkout webhooks and persist grant processing results.
     *
     * The webhook row is atomically claimed before processing. Purchase state and
     * balance grant are updated in one transaction with row locking and a stable
     * idempotency key to prevent duplicate ledger grants.
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

        $checkoutSessionId = '';

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

            $result = $connection->transaction(function () use ($checkoutSessionId): array {
                $prepaidPurchase = PrepaidPurchase::query()
                    ->where('stripe_checkout_session_id', $checkoutSessionId)
                    ->lockForUpdate()
                    ->first();

                if (! $prepaidPurchase instanceof PrepaidPurchase) {
                    return [
                        'status' => 'failed',
                        'message' => sprintf(
                            'prepaid_purchases not found for checkout_session_id: %s',
                            $checkoutSessionId
                        ),
                        'mark_grant_failed' => false,
                    ];
                }

                if (in_array(
                    $prepaidPurchase->status,
                    [
                        PrepaidPurchase::STATUS_COMPLETED,
                        PrepaidPurchase::STATUS_GRANT_FAILED,
                    ],
                    true
                )) {
                    return ['status' => 'processed'];
                }

                if (! in_array(
                    $prepaidPurchase->status,
                    [
                        PrepaidPurchase::STATUS_PENDING_PAYMENT,
                        PrepaidPurchase::STATUS_PROCESSING,
                    ],
                    true
                )) {
                    return [
                        'status' => 'failed',
                        'message' => sprintf(
                            'prepaid_purchases has unsupported status for grant: %s',
                            $prepaidPurchase->status
                        ),
                        'mark_grant_failed' => true,
                    ];
                }

                $prepaidProduct = PrepaidProduct::query()->find($prepaidPurchase->prepaid_product_id);

                if (! $prepaidProduct instanceof PrepaidProduct) {
                    return [
                        'status' => 'failed',
                        'message' => sprintf(
                            'prepaid_products not found for prepaid_purchase_id: %d',
                            $prepaidPurchase->id
                        ),
                        'mark_grant_failed' => true,
                    ];
                }

                $balanceUnit = $this->resolveBalanceUnit($prepaidProduct->prepaid_type);
                $grantExpiresAt = $prepaidPurchase->expires_at ?? now()->addDays($prepaidProduct->expires_in_days);

                $prepaidPurchase->update([
                    'status' => PrepaidPurchase::STATUS_PROCESSING,
                ]);

                BalanceTransaction::query()->createOrFirst(
                    ['idempotency_key' => $this->buildGrantIdempotencyKey($prepaidPurchase->id)],
                    [
                        'user_id' => $prepaidPurchase->user_id,
                        'unit' => $balanceUnit,
                        'amount' => $prepaidProduct->usage_count,
                        'transaction_type' => BalanceTransaction::TYPE_GRANT,
                        'prepaid_purchase_id' => $prepaidPurchase->id,
                        'reservation_id' => null,
                        'stripe_reference_id' => $checkoutSessionId,
                        'occurred_at' => now(),
                        'expires_at' => $grantExpiresAt,
                    ]
                );

                $prepaidPurchase->update([
                    'status' => PrepaidPurchase::STATUS_COMPLETED,
                    'purchased_at' => $prepaidPurchase->purchased_at ?? now(),
                    'expires_at' => $grantExpiresAt,
                ]);

                return ['status' => 'processed'];
            });

            if ($result['status'] === 'failed') {
                if (($result['mark_grant_failed'] ?? false) === true) {
                    $this->markGrantFailed($connection, $checkoutSessionId);
                }

                $this->markFailed((string) $result['message']);

                return;
            }

            $this->markProcessed();
        } catch (Throwable $exception) {
            try {
                $this->markGrantFailed($connection, $checkoutSessionId);
            } catch (Throwable) {
            }

            $this->markFailed(sprintf('prepaid checkout processing failed: %s', $exception->getMessage()));
        }
    }

    /**
     * Build a stable idempotency key used for prepaid grant ledger creation.
     *
     * @return non-empty-string
     */
    private function buildGrantIdempotencyKey(int $prepaidPurchaseId): string
    {
        return sprintf('balance:grant:prepaid_purchase:%d', $prepaidPurchaseId);
    }

    /**
     * Resolve prepaid product type to balance transaction unit.
     *
     * @throws InvalidArgumentException
     */
    private function resolveBalanceUnit(string $prepaidType): string
    {
        return match ($prepaidType) {
            PrepaidProduct::PREPAID_TYPE_TICKETS => BalanceTransaction::UNIT_TICKETS,
            PrepaidProduct::PREPAID_TYPE_POINTS => BalanceTransaction::UNIT_POINTS,
            default => throw new InvalidArgumentException(sprintf(
                'Unsupported prepaid_type for balance grant: %s',
                $prepaidType
            )),
        };
    }

    /**
     * Mark prepaid purchase as grant_failed unless it is already completed.
     */
    private function markGrantFailed(ConnectionInterface $connection, string $checkoutSessionId): void
    {
        if ($checkoutSessionId === '') {
            return;
        }

        $connection->transaction(function () use ($checkoutSessionId): void {
            $prepaidPurchase = PrepaidPurchase::query()
                ->where('stripe_checkout_session_id', $checkoutSessionId)
                ->lockForUpdate()
                ->first();

            if (! $prepaidPurchase instanceof PrepaidPurchase) {
                return;
            }

            if ($prepaidPurchase->status === PrepaidPurchase::STATUS_COMPLETED) {
                return;
            }

            $prepaidPurchase->update([
                'status' => PrepaidPurchase::STATUS_GRANT_FAILED,
            ]);
        });
    }

    /**
     * Mark webhook log as processed.
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
