<?php

namespace App\Providers;

use App\Actions\Fortify\CreateProvisionalMemberProfile;
use App\Jobs\ProcessSubscriptionPaymentWebhookJob;
use App\Jobs\RouteCheckoutSessionWebhookJob;
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

            $eventType = (string) ($payload['type'] ?? '');

            if (! in_array($eventType, ['checkout.session.completed', 'checkout.session.async_payment_succeeded', 'invoice.payment_succeeded'], true)) {
                return;
            }

            $eventId = trim((string) ($payload['id'] ?? ''));

            if ($eventId === '') {
                $checkoutSessionId = trim((string) data_get($payload, 'data.object.id', ''));

                Log::warning('Stripe webhook payload is missing event id.', [
                    'event_type' => $payload['type'] ?? null,
                    'checkout_session_id' => $checkoutSessionId !== '' ? $checkoutSessionId : null,
                ]);

                return;
            }

            try {
                $webhookLog = app(WebhookEventIdGuard::class)->recordReceived(
                    eventId: $eventId,
                    provider: 'stripe',
                    payload: $payload
                );

                if ($eventType === 'invoice.payment_succeeded') {
                    ProcessSubscriptionPaymentWebhookJob::dispatch($webhookLog->id);

                    return;
                }

                RouteCheckoutSessionWebhookJob::dispatch($webhookLog->id);
            } catch (\Throwable $exception) {
                Log::error('Failed to queue checkout webhook processing.', [
                    'event_id' => $eventId,
                    'exception_class' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);

                throw $exception;
            }
        });
    }
}
