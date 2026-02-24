<?php

namespace Tests\Feature;

use Tests\TestCase;

class StripeWebhookSignatureVerificationTest extends TestCase
{
    public function test_it_rejects_webhook_with_invalid_signature(): void
    {
        config()->set('cashier.webhook.secret', 'whsec_test_secret');

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
        config()->set('cashier.webhook.secret', 'whsec_test_secret');

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
        $secret = 'whsec_test_secret';
        config()->set('cashier.webhook.secret', $secret);

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
                'HTTP_STRIPE_SIGNATURE' => $this->makeStripeSignatureHeader($payload, $secret),
            ],
            $payload
        );

        $response->assertOk();
    }

    private function makeStripeSignatureHeader(string $payload, string $secret): string
    {
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp.'.'.$payload, $secret);

        return sprintf('t=%d,v1=%s', $timestamp, $signature);
    }
}
