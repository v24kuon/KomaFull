<?php

namespace App\Services;

use App\Models\WebhookLog;
use InvalidArgumentException;

class WebhookEventIdGuard
{
    /**
     * Record a webhook event only once by event_id.
     *
     * @param string $eventId Webhook イベントの一意識別子
     * @param string $provider Webhook プロバイダー名
     * @param array<string, mixed> $payload Webhook ペイロード
     * @return WebhookLog
     * @throws InvalidArgumentException event_id/provider が空、payload がJSON変換不可、またはprovider不一致の場合
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

        if (json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) === false) {
            throw new InvalidArgumentException('payload must be JSON serializable.');
        }

        $webhookLog = WebhookLog::query()->createOrFirst(
            ['event_id' => $normalizedEventId],
            [
                'provider' => $normalizedProvider,
                'payload' => $payload,
                'status' => WebhookLog::STATUS_RECEIVED,
            ]
        );

        if (! $webhookLog->wasRecentlyCreated && $webhookLog->provider !== $normalizedProvider) {
            throw new InvalidArgumentException(sprintf(
                "event_id '%s' is already registered with provider '%s'.",
                $normalizedEventId,
                $webhookLog->provider
            ));
        }

        return $webhookLog;
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
