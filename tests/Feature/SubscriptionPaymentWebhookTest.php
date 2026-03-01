<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\CourseEntitlement;
use App\Models\CourseEntitlementItem;
use App\Models\CoursePlan;
use App\Models\CoursePlanCategory;
use App\Models\User;
use App\Models\WebhookLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Cashier\Subscription;
use Tests\TestCase;

class SubscriptionPaymentWebhookTest extends TestCase
{
    use RefreshDatabase;

    private string $webhookSecret;

    protected function setUp(): void
    {
        parent::setUp();

        $this->webhookSecret = 'whsec_subscription_webhook_test_secret';
        config()->set('cashier.webhook.secret', $this->webhookSecret);
    }

    public function test_invoice_payment_succeeded_grants_total_entitlement(): void
    {
        $user = User::factory()->create([
            'stripe_id' => 'cus_subscription_total_001',
        ]);

        $coursePlan = CoursePlan::factory()->create([
            'stripe_price_id' => 'price_subscription_total_001',
            'usage_count' => 8,
            'allocation_type' => CoursePlan::ALLOCATION_TYPE_TOTAL,
        ]);

        Subscription::query()->create([
            'user_id' => $user->id,
            'type' => 'default',
            'stripe_id' => 'sub_subscription_total_001',
            'stripe_status' => 'active',
            'stripe_price' => 'price_subscription_total_001',
            'quantity' => 1,
        ]);

        $response = $this->postWebhook($this->makeInvoicePaymentSucceededPayload(
            eventId: 'evt_subscription_total_001',
            invoiceId: 'in_subscription_total_001',
            subscriptionId: 'sub_subscription_total_001',
            priceId: 'price_subscription_total_001',
            periodStart: 1735689600,
            periodEnd: 1738368000
        ));

        $response->assertOk();

        $this->assertDatabaseHas('course_entitlements', [
            'user_id' => $user->id,
            'course_plan_id' => $coursePlan->id,
            'granted_uses' => 8,
            'used_uses' => 0,
        ]);

        $entitlement = CourseEntitlement::query()
            ->where('user_id', $user->id)
            ->where('course_plan_id', $coursePlan->id)
            ->firstOrFail();

        $this->assertSame('2025-01-01', $entitlement->period_start?->toDateString());
        $this->assertSame('2025-01-31', $entitlement->period_end?->toDateString());

        $this->assertDatabaseHas('webhook_logs', [
            'event_id' => 'evt_subscription_total_001',
            'provider' => 'stripe',
            'status' => WebhookLog::STATUS_PROCESSED,
        ]);
        $this->assertDatabaseCount('course_entitlement_items', 0);
    }

    public function test_invoice_payment_succeeded_grants_per_category_entitlement_items(): void
    {
        $user = User::factory()->create([
            'stripe_id' => 'cus_subscription_per_category_001',
        ]);

        $coursePlan = CoursePlan::factory()->perCategory()->create([
            'stripe_price_id' => 'price_subscription_per_category_001',
            'usage_count' => 6,
        ]);

        $categoryA = Category::factory()->create();
        $categoryB = Category::factory()->create();

        CoursePlanCategory::factory()->create([
            'course_plan_id' => $coursePlan->id,
            'category_id' => $categoryA->id,
        ]);
        CoursePlanCategory::factory()->create([
            'course_plan_id' => $coursePlan->id,
            'category_id' => $categoryB->id,
        ]);

        Subscription::query()->create([
            'user_id' => $user->id,
            'type' => 'default',
            'stripe_id' => 'sub_subscription_per_category_001',
            'stripe_status' => 'active',
            'stripe_price' => 'price_subscription_per_category_001',
            'quantity' => 1,
        ]);

        $response = $this->postWebhook($this->makeInvoicePaymentSucceededPayload(
            eventId: 'evt_subscription_per_category_001',
            invoiceId: 'in_subscription_per_category_001',
            subscriptionId: 'sub_subscription_per_category_001',
            priceId: 'price_subscription_per_category_001',
            periodStart: 1735689600,
            periodEnd: 1738368000
        ));

        $response->assertOk();

        $entitlement = CourseEntitlement::query()
            ->where('user_id', $user->id)
            ->where('course_plan_id', $coursePlan->id)
            ->firstOrFail();

        $this->assertSame(
            2,
            CourseEntitlementItem::query()
                ->where('course_entitlement_id', $entitlement->id)
                ->count()
        );
        $this->assertDatabaseHas('course_entitlement_items', [
            'course_entitlement_id' => $entitlement->id,
            'category_id' => $categoryA->id,
            'granted_uses' => 6,
            'used_uses' => 0,
        ]);
        $this->assertDatabaseHas('course_entitlement_items', [
            'course_entitlement_id' => $entitlement->id,
            'category_id' => $categoryB->id,
            'granted_uses' => 6,
            'used_uses' => 0,
        ]);
    }

    public function test_duplicate_event_id_is_ignored_for_subscription_invoice_webhook(): void
    {
        $user = User::factory()->create([
            'stripe_id' => 'cus_subscription_duplicate_001',
        ]);

        $coursePlan = CoursePlan::factory()->create([
            'stripe_price_id' => 'price_subscription_duplicate_001',
            'usage_count' => 5,
        ]);

        Subscription::query()->create([
            'user_id' => $user->id,
            'type' => 'default',
            'stripe_id' => 'sub_subscription_duplicate_001',
            'stripe_status' => 'active',
            'stripe_price' => 'price_subscription_duplicate_001',
            'quantity' => 1,
        ]);

        $payload = $this->makeInvoicePaymentSucceededPayload(
            eventId: 'evt_subscription_duplicate_001',
            invoiceId: 'in_subscription_duplicate_001',
            subscriptionId: 'sub_subscription_duplicate_001',
            priceId: 'price_subscription_duplicate_001',
            periodStart: 1735689600,
            periodEnd: 1738368000
        );

        $firstResponse = $this->postWebhook($payload);
        $secondResponse = $this->postWebhook($payload);

        $firstResponse->assertOk();
        $secondResponse->assertOk();

        $this->assertSame(
            1,
            CourseEntitlement::query()
                ->where('user_id', $user->id)
                ->where('course_plan_id', $coursePlan->id)
                ->count()
        );
        $this->assertSame(
            1,
            WebhookLog::query()
                ->where('event_id', 'evt_subscription_duplicate_001')
                ->count()
        );
    }

    public function test_subscription_checkout_session_completed_is_processed_without_failed_routing(): void
    {
        $response = $this->postWebhook($this->makeSubscriptionCheckoutPayload(
            eventId: 'evt_subscription_checkout_completed_001',
            checkoutSessionId: 'cs_subscription_checkout_completed_001',
            paymentStatus: 'paid'
        ));

        $response->assertOk();

        $this->assertDatabaseHas('webhook_logs', [
            'event_id' => 'evt_subscription_checkout_completed_001',
            'provider' => 'stripe',
            'status' => WebhookLog::STATUS_PROCESSED,
        ]);
        $this->assertDatabaseCount('course_entitlements', 0);
    }

    public function test_invoice_payment_succeeded_without_subscription_id_marks_webhook_failed(): void
    {
        $payload = $this->makeInvoicePaymentSucceededPayload(
            eventId: 'evt_subscription_missing_subscription_001',
            invoiceId: 'in_subscription_missing_subscription_001',
            subscriptionId: '',
            priceId: 'price_missing_subscription',
            periodStart: 1735689600,
            periodEnd: 1738368000
        );

        $response = $this->postWebhook($payload);

        $response->assertOk();

        $webhookLog = WebhookLog::query()
            ->where('event_id', 'evt_subscription_missing_subscription_001')
            ->firstOrFail();

        $this->assertSame(WebhookLog::STATUS_FAILED, $webhookLog->status);
        $this->assertStringContainsString(
            'invoice.subscription is missing.',
            (string) $webhookLog->error_message
        );
    }

    public function test_invoice_payment_succeeded_with_mismatched_line_subscription_marks_webhook_failed(): void
    {
        $user = User::factory()->create([
            'stripe_id' => 'cus_subscription_line_mismatch_001',
        ]);

        CoursePlan::factory()->create([
            'stripe_price_id' => 'price_subscription_line_mismatch_001',
            'usage_count' => 7,
        ]);

        Subscription::query()->create([
            'user_id' => $user->id,
            'type' => 'default',
            'stripe_id' => 'sub_subscription_line_mismatch_001',
            'stripe_status' => 'active',
            'stripe_price' => 'price_subscription_line_mismatch_001',
            'quantity' => 1,
        ]);

        $payload = $this->makeInvoicePaymentSucceededPayload(
            eventId: 'evt_subscription_line_mismatch_001',
            invoiceId: 'in_subscription_line_mismatch_001',
            subscriptionId: 'sub_subscription_line_mismatch_001',
            priceId: 'price_subscription_line_mismatch_001',
            periodStart: 1735689600,
            periodEnd: 1738368000,
            lineSubscriptionId: 'sub_other_subscription_001'
        );

        $response = $this->postWebhook($payload);

        $response->assertOk();

        $webhookLog = WebhookLog::query()
            ->where('event_id', 'evt_subscription_line_mismatch_001')
            ->firstOrFail();

        $this->assertSame(WebhookLog::STATUS_FAILED, $webhookLog->status);
        $this->assertStringContainsString(
            'invoice subscription line is missing.',
            (string) $webhookLog->error_message
        );
        $this->assertDatabaseCount('course_entitlements', 0);
    }

    /**
     * @param  array<string, mixed>  $payload
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
     * @return array{id: string, type: 'invoice.payment_succeeded', data: array{object: array{id: string, subscription: string, lines: array{data: array<int, array{id: string, type: string, subscription?: string, price: array{id: string}, period: array{start: int, end: int}}>}}}}
     */
    private function makeInvoicePaymentSucceededPayload(
        string $eventId,
        string $invoiceId,
        string $subscriptionId,
        string $priceId,
        int $periodStart,
        int $periodEnd,
        ?string $lineSubscriptionId = null
    ): array {
        $line = [
            'id' => 'il_'.$invoiceId,
            'type' => 'subscription',
            'price' => [
                'id' => $priceId,
            ],
            'period' => [
                'start' => $periodStart,
                'end' => $periodEnd,
            ],
        ];

        if ($lineSubscriptionId !== null) {
            $line['subscription'] = $lineSubscriptionId;
        }

        return [
            'id' => $eventId,
            'type' => 'invoice.payment_succeeded',
            'data' => [
                'object' => [
                    'id' => $invoiceId,
                    'subscription' => $subscriptionId,
                    'lines' => [
                        'data' => [
                            $line,
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array{id: string, type: 'checkout.session.completed', data: array{object: array{id: string, mode: 'subscription', payment_status: 'paid'|'unpaid'|'no_payment_required'}}}
     */
    private function makeSubscriptionCheckoutPayload(
        string $eventId,
        string $checkoutSessionId,
        string $paymentStatus
    ): array {
        return [
            'id' => $eventId,
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => $checkoutSessionId,
                    'mode' => 'subscription',
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
