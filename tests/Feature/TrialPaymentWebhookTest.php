<?php

namespace Tests\Feature;

use App\Jobs\ProcessTrialRefundJob;
use App\Models\Reservation;
use App\Models\ReservationManagement;
use App\Models\TrialApplication;
use App\Models\WebhookLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class TrialPaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookSecret;

    protected function setUp(): void
    {
        parent::setUp();

        $this->webhookSecret = 'whsec_trial_webhook_test_secret';
        config()->set('cashier.webhook.secret', $this->webhookSecret);
    }

    public function test_checkout_session_completed_confirms_trial_reservation_when_capacity_is_available(): void
    {
        $trialApplication = TrialApplication::factory()->create([
            'status' => TrialApplication::STATUS_PENDING_PAYMENT,
            'stripe_checkout_session_id' => 'cs_trial_success_001',
        ]);

        ReservationManagement::factory()
            ->forLessonSessionId($trialApplication->lesson_session_id)
            ->create([
                'reserved_count' => 0,
                'reserved_trial_count' => 0,
            ]);

        $payload = $this->makeCheckoutCompletedPayload(
            eventId: 'evt_trial_success_001',
            checkoutSessionId: 'cs_trial_success_001',
            paymentIntentId: 'pi_trial_success_001'
        );

        $response = $this->postWebhook($payload);

        $response->assertOk();

        $trialApplication->refresh();
        $this->assertSame(TrialApplication::STATUS_RESERVED, $trialApplication->status);
        $this->assertNotNull($trialApplication->reservation_id);

        $reservation = Reservation::query()->findOrFail($trialApplication->reservation_id);
        $this->assertSame(Reservation::SEAT_BUCKET_TRIAL, $reservation->seat_bucket);
        $this->assertSame(Reservation::PAYMENT_METHOD_TRIAL_CARD, $reservation->payment_method);
        $this->assertSame(Reservation::STATUS_CONFIRMED, $reservation->status);

        $reservationManagement = ReservationManagement::query()
            ->where('lesson_session_id', $trialApplication->lesson_session_id)
            ->firstOrFail();
        $this->assertSame(1, $reservationManagement->reserved_trial_count);

        $this->assertDatabaseHas('webhook_logs', [
            'event_id' => 'evt_trial_success_001',
            'provider' => 'stripe',
            'status' => WebhookLog::STATUS_PROCESSED,
        ]);
    }

    public function test_checkout_session_completed_sets_refund_pending_and_dispatches_refund_job_when_trial_capacity_is_full(): void
    {
        Bus::fake([ProcessTrialRefundJob::class]);

        $trialApplication = TrialApplication::factory()->create([
            'status' => TrialApplication::STATUS_PENDING_PAYMENT,
            'stripe_checkout_session_id' => 'cs_trial_full_001',
        ]);

        ReservationManagement::factory()
            ->forLessonSessionId($trialApplication->lesson_session_id)
            ->create([
                'reserved_count' => 0,
                'reserved_trial_count' => $trialApplication->lessonSession->trial_capacity,
            ]);

        $payload = $this->makeCheckoutCompletedPayload(
            eventId: 'evt_trial_full_001',
            checkoutSessionId: 'cs_trial_full_001',
            paymentIntentId: 'pi_trial_full_001'
        );

        $response = $this->postWebhook($payload);

        $response->assertOk();

        $trialApplication->refresh();
        $this->assertSame(TrialApplication::STATUS_REFUND_PENDING, $trialApplication->status);
        $this->assertNull($trialApplication->reservation_id);

        $this->assertDatabaseMissing('reservations', [
            'user_id' => $trialApplication->user_id,
            'lesson_session_id' => $trialApplication->lesson_session_id,
            'payment_method' => Reservation::PAYMENT_METHOD_TRIAL_CARD,
            'status' => Reservation::STATUS_CONFIRMED,
        ]);

        Bus::assertDispatched(ProcessTrialRefundJob::class, function (ProcessTrialRefundJob $job) use ($trialApplication): bool {
            return $job->trialApplicationId === $trialApplication->id
                && $job->paymentIntentId === 'pi_trial_full_001';
        });

        $this->assertDatabaseHas('webhook_logs', [
            'event_id' => 'evt_trial_full_001',
            'provider' => 'stripe',
            'status' => WebhookLog::STATUS_PROCESSED,
        ]);
    }

    public function test_duplicate_event_id_is_ignored_and_does_not_create_duplicate_trial_reservation(): void
    {
        $trialApplication = TrialApplication::factory()->create([
            'status' => TrialApplication::STATUS_PENDING_PAYMENT,
            'stripe_checkout_session_id' => 'cs_trial_duplicate_001',
        ]);

        ReservationManagement::factory()
            ->forLessonSessionId($trialApplication->lesson_session_id)
            ->create([
                'reserved_count' => 0,
                'reserved_trial_count' => 0,
            ]);

        $payload = $this->makeCheckoutCompletedPayload(
            eventId: 'evt_trial_duplicate_001',
            checkoutSessionId: 'cs_trial_duplicate_001',
            paymentIntentId: 'pi_trial_duplicate_001'
        );

        $firstResponse = $this->postWebhook($payload);
        $secondResponse = $this->postWebhook($payload);

        $firstResponse->assertOk();
        $secondResponse->assertOk();

        $this->assertSame(1, Reservation::query()
            ->where('user_id', $trialApplication->user_id)
            ->where('lesson_session_id', $trialApplication->lesson_session_id)
            ->count());

        $this->assertSame(1, WebhookLog::query()
            ->where('event_id', 'evt_trial_duplicate_001')
            ->count());
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postWebhook(array $payload)
    {
        $encodedPayload = json_encode($payload, JSON_THROW_ON_ERROR);

        return $this->call(
            'POST',
            route('cashier.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $this->makeStripeSignatureHeader($encodedPayload),
            ],
            $encodedPayload
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function makeCheckoutCompletedPayload(string $eventId, string $checkoutSessionId, string $paymentIntentId): array
    {
        return [
            'id' => $eventId,
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => $checkoutSessionId,
                    'payment_intent' => $paymentIntentId,
                    'payment_status' => 'paid',
                ],
            ],
        ];
    }

    private function makeStripeSignatureHeader(string $payload): string
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $this->webhookSecret);

        return sprintf('t=%d,v1=%s', $timestamp, $signature);
    }
}
