<?php

namespace App\Providers;

use App\Actions\Fortify\CreateProvisionalMemberProfile;
use App\Jobs\ProcessPrepaidPaymentWebhookJob;
use App\Jobs\ProcessTrialPaymentWebhookJob;
use App\Models\PrepaidPurchase;
use App\Models\TrialApplication;
use App\Models\User;
use App\Models\WebhookLog;
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

                $checkoutSessionId = trim((string) data_get($payload, 'data.object.id', ''));

                if ($checkoutSessionId !== '' && TrialApplication::query()
                    ->where('stripe_checkout_session_id', $checkoutSessionId)
                    ->exists()) {
                    ProcessTrialPaymentWebhookJob::dispatch($webhookLog->id);

                    return;
                }

                if ($checkoutSessionId !== '' && PrepaidPurchase::query()
                    ->where('stripe_checkout_session_id', $checkoutSessionId)
                    ->exists()) {
                    ProcessPrepaidPaymentWebhookJob::dispatch($webhookLog->id);

                    return;
                }

                WebhookLog::query()
                    ->whereKey($webhookLog->id)
                    ->update([
                        'status' => WebhookLog::STATUS_FAILED,
                        'error_message' => $checkoutSessionId === ''
                            ? 'checkout.session.id is missing.'
                            : sprintf(
                                'No checkout target found for checkout_session_id: %s',
                                $checkoutSessionId
                            ),
                    ]);

                Log::warning('Stripe checkout webhook target was not found.', [
                    'event_id' => $eventId,
                    'checkout_session_id' => $checkoutSessionId !== '' ? $checkoutSessionId : null,
                ]);
            } catch (\Throwable $exception) {
                Log::error('Failed to queue checkout webhook processing.', [
                    'event_id' => $eventId,
                    'exception_class' => $exception::class,
                    'message' => $exception->getMessage(),
                ]);
            }
        });
    }
}
