<?php

namespace App\Services;

use App\Models\WebhookLog;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class WebhookEventIdGuard
{
    /**
     * Record a webhook event only once by event_id.
     */
    public function recordReceived(string $eventId, string $provider, array $payload): WebhookLog
    {
        $normalizedEventId = trim($eventId);
        $normalizedProvider = trim($provider);

        if ($normalizedEventId === '') {
            throw new InvalidArgumentException('event_id must not be empty.');
        }

        if ($normalizedProvider === '') {
            throw new InvalidArgumentException('provider must not be empty.');
        }

        $encodedPayload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($encodedPayload === false) {
            throw new InvalidArgumentException('payload must be JSON serializable.');
        }

        $timestamp = now();

        DB::table('webhook_logs')->insertOrIgnore([
            'event_id' => $normalizedEventId,
            'provider' => $normalizedProvider,
            'payload' => $encodedPayload,
            'status' => WebhookLog::STATUS_RECEIVED,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ]);

        return WebhookLog::query()
            ->where('event_id', $normalizedEventId)
            ->firstOrFail();
    }

    /**
     * Atomically claim a webhook event for processing.
     */
    public function claimForProcessing(int $webhookLogId): bool
    {
        $claimedCount = WebhookLog::query()
            ->whereKey($webhookLogId)
            ->where('status', WebhookLog::STATUS_RECEIVED)
            ->update([
                'status' => WebhookLog::STATUS_PROCESSING,
            ]);

        return $claimedCount === 1;
    }
}
