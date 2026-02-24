<?php

namespace Tests\Feature;

use Tests\TestCase;

class StripeWebhookSignatureVerificationTest extends TestCase
{
    private string $webhookSecret;

    protected function setUp(): void
    {
        parent::setUp();

        $this->webhookSecret = 'whsec_test_secret';
        config()->set('cashier.webhook.secret', $this->webhookSecret);
    }

    public function test_it_rejects_webhook_with_invalid_signature(): void
    {
        $payload = json_encode([
            'id' => 'evt_signature_invalid_001',
            'type' => 'test.event',
            'data' => [
                'object' => [
                    'id' => 'obj_invalid_signature',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->call(
            'POST',
            route('cashier.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => sprintf('t=%d,v1=invalid', time()),
            ],
            $payload
        );

        $response->assertStatus(403);
    }

    public function test_it_rejects_webhook_without_signature_header(): void
    {
        $payload = json_encode([
            'id' => 'evt_signature_missing_header_001',
            'type' => 'test.event',
            'data' => [
                'object' => [
                    'id' => 'obj_missing_signature_header',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->call(
            'POST',
            route('cashier.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
            ],
            $payload
        );

        $response->assertStatus(403);
    }

    public function test_it_accepts_webhook_with_valid_signature(): void
    {
        $payload = json_encode([
            'id' => 'evt_signature_valid_001',
            'type' => 'test.event',
            'data' => [
                'object' => [
                    'id' => 'obj_valid_signature',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->call(
            'POST',
            route('cashier.webhook'),
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_STRIPE_SIGNATURE' => $this->makeStripeSignatureHeader($payload, $this->webhookSecret),
            ],
            $payload
        );

        $response->assertOk();
    }

    /**
     * Create a Stripe-Signature header value for test payloads.
     */
    private function makeStripeSignatureHeader(string $payload, string $secret): string
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return sprintf('t=%d,v1=%s', $timestamp, $signature);
    }
}
