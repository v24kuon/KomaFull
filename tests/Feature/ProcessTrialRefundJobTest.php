<?php

namespace Tests\Feature;

use App\Jobs\ProcessTrialRefundJob;
use App\Models\TrialApplication;
use App\Services\StripeRefundService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ProcessTrialRefundJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_marks_trial_application_as_refunded_when_refund_succeeds(): void
    {
        $trialApplication = TrialApplication::factory()->refundPending()->create();

        $refundService = $this->mock(StripeRefundService::class);
        $refundService
            ->shouldReceive('refundPaymentIntent')
            ->once()
            ->with(
                'pi_refund_success_001',
                sprintf('refund:trial_application:%d', $trialApplication->id)
            );

        ProcessTrialRefundJob::dispatchSync(
            trialApplicationId: $trialApplication->id,
            paymentIntentId: 'pi_refund_success_001'
        );

        $trialApplication->refresh();
        $this->assertSame(TrialApplication::STATUS_REFUNDED, $trialApplication->status);
        $this->assertNotNull($trialApplication->refunded_at);
        $this->assertNull($trialApplication->refund_reason);
    }

    public function test_it_marks_trial_application_as_refund_failed_when_stripe_refund_throws_exception(): void
    {
        $trialApplication = TrialApplication::factory()->refundPending()->create();

        $refundService = $this->mock(StripeRefundService::class);
        $refundService
            ->shouldReceive('refundPaymentIntent')
            ->once()
            ->with(
                'pi_refund_failure_001',
                sprintf('refund:trial_application:%d', $trialApplication->id)
            )
            ->andThrow(new RuntimeException('Stripe refund API error'));

        try {
            ProcessTrialRefundJob::dispatchSync(
                trialApplicationId: $trialApplication->id,
                paymentIntentId: 'pi_refund_failure_001'
            );

            $this->fail('RuntimeException was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Stripe refund API error', $exception->getMessage());
        }

        $trialApplication->refresh();
        $this->assertSame(TrialApplication::STATUS_REFUND_FAILED, $trialApplication->status);
        $this->assertStringContainsString('Stripe refund API error', (string) $trialApplication->refund_reason);
    }

    public function test_it_marks_trial_application_as_refund_failed_when_payment_intent_is_missing(): void
    {
        $trialApplication = TrialApplication::factory()->refundPending()->create();

        $refundService = $this->mock(StripeRefundService::class);
        $refundService->shouldReceive('refundPaymentIntent')->never();

        ProcessTrialRefundJob::dispatchSync(
            trialApplicationId: $trialApplication->id,
            paymentIntentId: null
        );

        $trialApplication->refresh();
        $this->assertSame(TrialApplication::STATUS_REFUND_FAILED, $trialApplication->status);
        $this->assertStringContainsString('Missing payment_intent', (string) $trialApplication->refund_reason);
    }
}
