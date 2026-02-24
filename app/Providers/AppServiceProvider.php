<?php

namespace App\Providers;

use App\Actions\Fortify\CreateProvisionalMemberProfile;
use App\Jobs\ProcessTrialPaymentWebhookJob;
use App\Models\User;
use App\Services\WebhookEventIdGuard;
use Illuminate\Auth\Events\Verified;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Laravel\Cashier\Events\WebhookReceived;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(function (Verified $event): void {
            if (! $event->user instanceof User) {
                return;
            }

            try {
                app(CreateProvisionalMemberProfile::class)->createFor($event->user);
            } catch (\Throwable $exception) {
                Log::error('Failed to create provisional member profile during email verification.', [
                    'user_id' => $event->user->id,
                    'exception' => $exception,
                ]);
            }
        });

        Event::listen(function (WebhookReceived $event): void {
            $payload = $event->payload;

            if (($payload['type'] ?? null) !== 'checkout.session.completed') {
                return;
            }

            $eventId = trim((string) ($payload['id'] ?? ''));

            if ($eventId === '') {
                Log::warning('Stripe webhook payload is missing event id.', [
                    'payload' => $payload,
                ]);

                return;
            }

            try {
                $webhookLog = app(WebhookEventIdGuard::class)->recordReceived(
                    eventId: $eventId,
                    provider: 'stripe',
                    payload: $payload
                );

                ProcessTrialPaymentWebhookJob::dispatch($webhookLog->id);
            } catch (\Throwable $exception) {
                Log::error('Failed to queue trial checkout webhook processing.', [
                    'event_id' => $eventId,
                    'exception' => $exception,
                ]);
            }
        });
    }
}
