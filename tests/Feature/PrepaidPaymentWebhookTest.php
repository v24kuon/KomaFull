<?php

namespace Tests\Feature;

use App\Models\BalanceTransaction;
use App\Models\PrepaidProduct;
use App\Models\PrepaidPurchase;
use App\Models\WebhookLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class PrepaidPaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookSecret;

    protected function setUp(): void
    {
        parent::setUp();

        $this->webhookSecret = 'whsec_prepaid_webhook_test_secret';
        config()->set('cashier.webhook.secret', $this->webhookSecret);
    }

    public function test_checkout_session_completed_grants_tickets_and_marks_purchase_completed(): void
    {
        $prepaidProduct = PrepaidProduct::factory()->create([
            'prepaid_type' => PrepaidProduct::PREPAID_TYPE_TICKETS,
            'usage_count' => 5,
            'expires_in_days' => 45,
        ]);

        $prepaidPurchase = PrepaidPurchase::factory()->create([
            'prepaid_product_id' => $prepaidProduct->id,
            'status' => PrepaidPurchase::STATUS_PENDING_PAYMENT,
            'stripe_checkout_session_id' => 'cs_prepaid_tickets_success_001',
            'purchased_at' => null,
            'expires_at' => null,
        ]);

        $response = $this->postWebhook($this->makeCheckoutCompletedPayload(
            eventId: 'evt_prepaid_tickets_success_001',
            checkoutSessionId: 'cs_prepaid_tickets_success_001'
        ));

        $response->assertOk();

        $prepaidPurchase->refresh();
        $this->assertSame(PrepaidPurchase::STATUS_COMPLETED, $prepaidPurchase->status);
        $this->assertNotNull($prepaidPurchase->purchased_at);
        $this->assertNotNull($prepaidPurchase->expires_at);

        $this->assertDatabaseHas('balance_transactions', [
            'user_id' => $prepaidPurchase->user_id,
            'unit' => BalanceTransaction::UNIT_TICKETS,
            'amount' => 5,
            'transaction_type' => BalanceTransaction::TYPE_GRANT,
            'idempotency_key' => sprintf('balance:grant:prepaid_purchase:%d', $prepaidPurchase->id),
            'prepaid_purchase_id' => $prepaidPurchase->id,
            'stripe_reference_id' => 'cs_prepaid_tickets_success_001',
        ]);

        $this->assertDatabaseHas('webhook_logs', [
            'event_id' => 'evt_prepaid_tickets_success_001',
            'provider' => 'stripe',
            'status' => WebhookLog::STATUS_PROCESSED,
        ]);
    }

    public function test_checkout_session_completed_grants_points_and_marks_purchase_completed(): void
    {
        $prepaidProduct = PrepaidProduct::factory()->points()->create([
            'usage_count' => 120,
            'expires_in_days' => 30,
        ]);

        $prepaidPurchase = PrepaidPurchase::factory()->create([
            'prepaid_product_id' => $prepaidProduct->id,
            'status' => PrepaidPurchase::STATUS_PENDING_PAYMENT,
            'stripe_checkout_session_id' => 'cs_prepaid_points_success_001',
            'purchased_at' => null,
            'expires_at' => null,
        ]);

        $response = $this->postWebhook($this->makeCheckoutCompletedPayload(
            eventId: 'evt_prepaid_points_success_001',
            checkoutSessionId: 'cs_prepaid_points_success_001'
        ));

        $response->assertOk();

        $prepaidPurchase->refresh();
        $this->assertSame(PrepaidPurchase::STATUS_COMPLETED, $prepaidPurchase->status);

        $this->assertDatabaseHas('balance_transactions', [
            'user_id' => $prepaidPurchase->user_id,
            'unit' => BalanceTransaction::UNIT_POINTS,
            'amount' => 120,
            'transaction_type' => BalanceTransaction::TYPE_GRANT,
            'idempotency_key' => sprintf('balance:grant:prepaid_purchase:%d', $prepaidPurchase->id),
            'prepaid_purchase_id' => $prepaidPurchase->id,
            'stripe_reference_id' => 'cs_prepaid_points_success_001',
        ]);
    }

    public function test_checkout_session_completed_with_unpaid_status_does_not_grant_balance(): void
    {
        $prepaidPurchase = PrepaidPurchase::factory()->create([
            'status' => PrepaidPurchase::STATUS_PENDING_PAYMENT,
            'stripe_checkout_session_id' => 'cs_prepaid_unpaid_001',
            'purchased_at' => null,
            'expires_at' => null,
        ]);

        $response = $this->postWebhook($this->makeCheckoutCompletedPayload(
            eventId: 'evt_prepaid_unpaid_001',
            checkoutSessionId: 'cs_prepaid_unpaid_001',
            paymentStatus: 'unpaid'
        ));

        $response->assertOk();

        $prepaidPurchase->refresh();
        $this->assertSame(PrepaidPurchase::STATUS_PENDING_PAYMENT, $prepaidPurchase->status);
        $this->assertNull($prepaidPurchase->purchased_at);
        $this->assertNull($prepaidPurchase->expires_at);
        $this->assertDatabaseCount('balance_transactions', 0);
        $this->assertDatabaseHas('webhook_logs', [
            'event_id' => 'evt_prepaid_unpaid_001',
            'provider' => 'stripe',
            'status' => WebhookLog::STATUS_PROCESSED,
        ]);
    }

    public function test_async_payment_succeeded_grants_balance_after_unpaid_checkout_session_completed(): void
    {
        $prepaidProduct = PrepaidProduct::factory()->create([
            'prepaid_type' => PrepaidProduct::PREPAID_TYPE_TICKETS,
            'usage_count' => 3,
            'expires_in_days' => 14,
        ]);

        $prepaidPurchase = PrepaidPurchase::factory()->create([
            'prepaid_product_id' => $prepaidProduct->id,
            'status' => PrepaidPurchase::STATUS_PENDING_PAYMENT,
            'stripe_checkout_session_id' => 'cs_prepaid_async_success_001',
            'purchased_at' => null,
            'expires_at' => null,
        ]);

        $completedResponse = $this->postWebhook($this->makeCheckoutCompletedPayload(
            eventId: 'evt_prepaid_async_completed_001',
            checkoutSessionId: 'cs_prepaid_async_success_001',
            paymentStatus: 'unpaid'
        ));

        $asyncSuccessResponse = $this->postWebhook($this->makeCheckoutCompletedPayload(
            eventId: 'evt_prepaid_async_succeeded_001',
            checkoutSessionId: 'cs_prepaid_async_success_001',
            paymentStatus: 'paid',
            eventType: 'checkout.session.async_payment_succeeded'
        ));

        $completedResponse->assertOk();
        $asyncSuccessResponse->assertOk();

        $prepaidPurchase->refresh();
        $this->assertSame(PrepaidPurchase::STATUS_COMPLETED, $prepaidPurchase->status);
        $this->assertNotNull($prepaidPurchase->purchased_at);
        $this->assertNotNull($prepaidPurchase->expires_at);

        $this->assertSame(
            1,
            BalanceTransaction::query()
                ->where('prepaid_purchase_id', $prepaidPurchase->id)
                ->where('transaction_type', BalanceTransaction::TYPE_GRANT)
                ->count()
        );

        $this->assertDatabaseHas('webhook_logs', [
            'event_id' => 'evt_prepaid_async_completed_001',
            'provider' => 'stripe',
            'status' => WebhookLog::STATUS_PROCESSED,
        ]);
        $this->assertDatabaseHas('webhook_logs', [
            'event_id' => 'evt_prepaid_async_succeeded_001',
            'provider' => 'stripe',
            'status' => WebhookLog::STATUS_PROCESSED,
        ]);
    }

    public function test_duplicate_event_id_is_ignored_and_does_not_create_duplicate_balance_transaction(): void
    {
        $prepaidPurchase = PrepaidPurchase::factory()->create([
            'status' => PrepaidPurchase::STATUS_PENDING_PAYMENT,
            'stripe_checkout_session_id' => 'cs_prepaid_duplicate_event_001',
            'purchased_at' => null,
            'expires_at' => null,
        ]);

        $payload = $this->makeCheckoutCompletedPayload(
            eventId: 'evt_prepaid_duplicate_event_001',
            checkoutSessionId: 'cs_prepaid_duplicate_event_001'
        );

        $firstResponse = $this->postWebhook($payload);
        $secondResponse = $this->postWebhook($payload);

        $firstResponse->assertOk();
        $secondResponse->assertOk();

        $this->assertSame(
            1,
            BalanceTransaction::query()
                ->where('prepaid_purchase_id', $prepaidPurchase->id)
                ->where('transaction_type', BalanceTransaction::TYPE_GRANT)
                ->count()
        );

        $this->assertSame(1, WebhookLog::query()
            ->where('event_id', 'evt_prepaid_duplicate_event_001')
            ->count());
    }

    public function test_completed_purchase_is_not_reprocessed_into_duplicate_grant(): void
    {
        $prepaidPurchase = PrepaidPurchase::factory()->completed()->create([
            'status' => PrepaidPurchase::STATUS_COMPLETED,
            'stripe_checkout_session_id' => 'cs_prepaid_already_completed_001',
        ]);

        $originalPurchasedAt = $prepaidPurchase->purchased_at;
        $originalExpiresAt = $prepaidPurchase->expires_at;

        $response = $this->postWebhook($this->makeCheckoutCompletedPayload(
            eventId: 'evt_prepaid_already_completed_001',
            checkoutSessionId: 'cs_prepaid_already_completed_001'
        ));

        $response->assertOk();

        $prepaidPurchase->refresh();
        $this->assertSame(PrepaidPurchase::STATUS_COMPLETED, $prepaidPurchase->status);
        $this->assertTrue($prepaidPurchase->purchased_at?->equalTo($originalPurchasedAt));
        $this->assertTrue($prepaidPurchase->expires_at?->equalTo($originalExpiresAt));
        $this->assertDatabaseCount('balance_transactions', 0);
    }

    public function test_grant_failed_purchase_is_not_reprocessed_into_duplicate_grant(): void
    {
        $prepaidPurchase = PrepaidPurchase::factory()->create([
            'status' => PrepaidPurchase::STATUS_GRANT_FAILED,
            'stripe_checkout_session_id' => 'cs_prepaid_grant_failed_001',
            'purchased_at' => null,
            'expires_at' => null,
        ]);

        $response = $this->postWebhook($this->makeCheckoutCompletedPayload(
            eventId: 'evt_prepaid_grant_failed_001',
            checkoutSessionId: 'cs_prepaid_grant_failed_001'
        ));

        $response->assertOk();

        $prepaidPurchase->refresh();
        $this->assertSame(PrepaidPurchase::STATUS_GRANT_FAILED, $prepaidPurchase->status);
        $this->assertDatabaseCount('balance_transactions', 0);
    }

    public function test_invalid_prepaid_type_marks_purchase_as_grant_failed(): void
    {
        $prepaidProduct = PrepaidProduct::factory()->create([
            'prepaid_type' => 'unknown_type',
        ]);

        $prepaidPurchase = PrepaidPurchase::factory()->create([
            'prepaid_product_id' => $prepaidProduct->id,
            'status' => PrepaidPurchase::STATUS_PENDING_PAYMENT,
            'stripe_checkout_session_id' => 'cs_prepaid_invalid_type_001',
            'purchased_at' => null,
            'expires_at' => null,
        ]);

        $response = $this->postWebhook($this->makeCheckoutCompletedPayload(
            eventId: 'evt_prepaid_invalid_type_001',
            checkoutSessionId: 'cs_prepaid_invalid_type_001'
        ));

        $response->assertOk();

        $prepaidPurchase->refresh();
        $this->assertSame(PrepaidPurchase::STATUS_GRANT_FAILED, $prepaidPurchase->status);
        $this->assertDatabaseCount('balance_transactions', 0);

        $webhookLog = WebhookLog::query()
            ->where('event_id', 'evt_prepaid_invalid_type_001')
            ->firstOrFail();
        $this->assertSame(WebhookLog::STATUS_FAILED, $webhookLog->status);
        $this->assertStringContainsString(
            'Unsupported prepaid_type for balance grant',
            (string) $webhookLog->error_message
        );
    }

    /**
     * @param  array{id: string, type: 'checkout.session.completed'|'checkout.session.async_payment_succeeded', data: array{object: array{id: string, payment_status: 'paid'|'unpaid'|'no_payment_required'}}}  $payload
     */
    private function postWebhook(array $payload): TestResponse
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
     * @return array{id: string, type: 'checkout.session.completed'|'checkout.session.async_payment_succeeded', data: array{object: array{id: string, payment_status: 'paid'|'unpaid'|'no_payment_required'}}}
     */
    private function makeCheckoutCompletedPayload(
        string $eventId,
        string $checkoutSessionId,
        string $paymentStatus = 'paid',
        string $eventType = 'checkout.session.completed'
    ): array {
        return [
            'id' => $eventId,
            'type' => $eventType,
            'data' => [
                'object' => [
                    'id' => $checkoutSessionId,
                    'payment_status' => $paymentStatus,
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
