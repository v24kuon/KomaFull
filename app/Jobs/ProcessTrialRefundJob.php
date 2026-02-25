<?php

namespace App\Jobs;

use App\Models\TrialApplication;
use App\Services\StripeRefundService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class ProcessTrialRefundJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 4;

    public function __construct(
        public readonly int $trialApplicationId,
        public readonly ?string $paymentIntentId
    ) {}

    /**
     * Calculate the number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(ConnectionInterface $connection, StripeRefundService $stripeRefundService): void
    {
        $shouldProcess = $connection->transaction(function (): bool {
            $trialApplication = TrialApplication::query()
                ->whereKey($this->trialApplicationId)
                ->lockForUpdate()
                ->first();

            if (! $trialApplication instanceof TrialApplication) {
                return false;
            }

            if ($trialApplication->status === TrialApplication::STATUS_REFUNDED) {
                return false;
            }

            return in_array(
                $trialApplication->status,
                [
                    TrialApplication::STATUS_REFUND_PENDING,
                    TrialApplication::STATUS_REFUND_FAILED,
                ],
                true
            );
        });

        if (! $shouldProcess) {
            return;
        }

        $paymentIntentId = trim((string) $this->paymentIntentId);

        if ($paymentIntentId === '') {
            $this->markRefundFailed(
                $connection,
                'Missing payment_intent for trial refund processing.'
            );

            return;
        }

        try {
            $stripeRefundService->refundPaymentIntent(
                paymentIntentId: $paymentIntentId,
                idempotencyKey: $this->buildRefundIdempotencyKey()
            );

            $this->markRefunded($connection);
        } catch (Throwable $exception) {
            $this->markRefundFailed(
                $connection,
                sprintf('Stripe refund failed: %s', $exception->getMessage())
            );

            throw $exception;
        }
    }

    private function buildRefundIdempotencyKey(): string
    {
        return sprintf('refund:trial_application:%d', $this->trialApplicationId);
    }

    private function markRefunded(ConnectionInterface $connection): void
    {
        $connection->transaction(function (): void {
            $trialApplication = TrialApplication::query()
                ->whereKey($this->trialApplicationId)
                ->lockForUpdate()
                ->first();

            if (! $trialApplication instanceof TrialApplication) {
                return;
            }

            $trialApplication->update([
                'status' => TrialApplication::STATUS_REFUNDED,
                'refunded_at' => now(),
                'refund_reason' => null,
            ]);
        });
    }

    private function markRefundFailed(ConnectionInterface $connection, string $reason): void
    {
        $connection->transaction(function () use ($reason): void {
            $trialApplication = TrialApplication::query()
                ->whereKey($this->trialApplicationId)
                ->lockForUpdate()
                ->first();

            if (! $trialApplication instanceof TrialApplication) {
                return;
            }

            if ($trialApplication->status === TrialApplication::STATUS_REFUNDED) {
                return;
            }

            $trialApplication->update([
                'status' => TrialApplication::STATUS_REFUND_FAILED,
                'refund_reason' => $reason,
            ]);
        });
    }
}
