<?php

namespace Tests\Feature;

use App\Models\WebhookLog;
use App\Services\WebhookEventIdGuard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class WebhookEventIdGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_records_new_event_as_received(): void
    {
        // Given: a guard service and a valid webhook payload
        $guard = app(WebhookEventIdGuard::class);
        $eventId = 'evt_new_001';
        $payload = ['id' => $eventId, 'type' => 'checkout.session.completed'];

        // When: a new event is recorded
        $webhookLog = $guard->recordReceived($eventId, 'stripe', $payload);

        // Then: a received webhook log is created
        $this->assertSame(WebhookLog::STATUS_RECEIVED, $webhookLog->status);
        $this->assertSame($payload, $webhookLog->payload);
        $this->assertDatabaseHas('webhook_logs', [
            'id' => $webhookLog->id,
            'event_id' => $eventId,
            'provider' => 'stripe',
            'status' => WebhookLog::STATUS_RECEIVED,
        ]);
    }

    public function test_it_returns_existing_log_for_duplicate_event_id(): void
    {
        // Given: an event already recorded once
        $guard = app(WebhookEventIdGuard::class);
        $eventId = 'evt_duplicate_001';
        $payload = ['id' => $eventId, 'type' => 'checkout.session.completed'];
        $first = $guard->recordReceived($eventId, 'stripe', $payload);

        // When: the same event_id is recorded again
        $second = $guard->recordReceived($eventId, 'stripe', $payload);

        // Then: only one record exists and the same record is returned
        $this->assertSame($first->id, $second->id);
        $this->assertDatabaseCount('webhook_logs', 1);
    }

    public function test_it_rejects_same_event_id_with_different_provider(): void
    {
        // Given: the event_id is already recorded with stripe provider
        $guard = app(WebhookEventIdGuard::class);
        $eventId = 'evt_provider_conflict_001';
        $payload = ['id' => $eventId, 'type' => 'checkout.session.completed'];
        $guard->recordReceived($eventId, 'stripe', $payload);

        // When: the same event_id is recorded with a different provider
        try {
            $guard->recordReceived($eventId, 'stripe_connect', $payload);
            $this->fail('Expected InvalidArgumentException was not thrown.');
        } catch (InvalidArgumentException $exception) {
            // Then: the provider mismatch is rejected and original record remains unchanged
            $this->assertSame(
                "event_id 'evt_provider_conflict_001' is already registered with provider 'stripe'.",
                $exception->getMessage()
            );
        }

        $this->assertDatabaseCount('webhook_logs', 1);
        $this->assertDatabaseHas('webhook_logs', [
            'event_id' => $eventId,
            'provider' => 'stripe',
            'status' => WebhookLog::STATUS_RECEIVED,
        ]);
    }

    public function test_it_claims_received_event_only_once(): void
    {
        // Given: a received webhook event
        $guard = app(WebhookEventIdGuard::class);
        $webhookLog = $guard->recordReceived(
            'evt_claim_001',
            'stripe',
            ['id' => 'evt_claim_001']
        );

        // When: processing claim is attempted twice
        $firstClaim = $guard->claimForProcessing($webhookLog->id);
        $secondClaim = $guard->claimForProcessing($webhookLog->id);

        // Then: first claim succeeds and second claim is rejected
        $this->assertTrue($firstClaim);
        $this->assertFalse($secondClaim);
        $this->assertSame(
            WebhookLog::STATUS_PROCESSING,
            $webhookLog->fresh()->status
        );
    }

    public function test_it_returns_false_when_claim_target_is_missing(): void
    {
        // Given: a guard service and non-existing webhook_log id
        $guard = app(WebhookEventIdGuard::class);

        // When: processing claim is attempted
        $claimed = $guard->claimForProcessing(999999);

        // Then: claim is not acquired
        $this->assertFalse($claimed);
    }

    public function test_it_rejects_empty_event_id(): void
    {
        // Given: a guard service and empty event_id input
        $guard = app(WebhookEventIdGuard::class);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('event_id must not be empty.');

        // When: recording is attempted with empty event_id
        $guard->recordReceived('', 'stripe', ['id' => 'evt_invalid_001']);

        // Then: an invalid argument exception is thrown
    }

    public function test_it_rejects_empty_provider(): void
    {
        // Given: a guard service and empty provider input
        $guard = app(WebhookEventIdGuard::class);
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('provider must not be empty.');

        // When: recording is attempted with empty provider
        $guard->recordReceived('evt_invalid_provider_001', '', ['id' => 'evt_invalid_provider_001']);

        // Then: an invalid argument exception is thrown
    }
}
