<?php

namespace App\Services\Checkout;

use App\Contracts\CreatesStripeCheckoutSession;
use App\Contracts\EnsuresStripeCustomer;
use App\Models\LessonSession;
use App\Models\Program;
use App\Models\TrialApplication;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;
use Stripe\Checkout\Session;

/**
 * 体験レッスン（カード）用の Stripe Checkout セッションを作成し、リダイレクト先 URL を返す。
 *
 * 前提: `trial_applications` は `pending_payment` かつ `payment_method=card`。金額は紐づく `programs.price`（主通貨の整数単位）を使用する。
 * 副作用: Stripe API を呼び出し、`trial_applications.stripe_checkout_session_id` を更新する。
 */
class TrialCheckoutSessionService
{
    public function __construct(
        private readonly CreatesStripeCheckoutSession $checkoutSessions,
        private readonly EnsuresStripeCustomer $stripeCustomer,
    ) {}

    /**
     * Checkout Session を作成し、試行申込に `stripe_checkout_session_id` を保存して Stripe へリダイレクトする。
     *
     * @param  non-empty-string  $successUrl  Stripe の `success_url`（例: `{CHECKOUT_SESSION_ID}` placeholder を含む）
     * @param  non-empty-string  $cancelUrl  Stripe の `cancel_url`
     *
     * @throws InvalidArgumentException 申込がカード決済の未決済でない、または金額が不正な場合
     */
    public function redirectToCheckout(TrialApplication $trialApplication, string $successUrl, string $cancelUrl): RedirectResponse
    {
        $this->assertEligible($trialApplication);

        $user = $trialApplication->user;

        if (! $user instanceof User) {
            throw new InvalidArgumentException('Trial application has no user.');
        }

        $lessonSession = $trialApplication->lessonSession()->with('program')->first();

        if (! $lessonSession instanceof LessonSession) {
            throw new InvalidArgumentException('Trial application has no lesson session.');
        }

        $program = $lessonSession->program;

        if (! $program instanceof Program) {
            throw new InvalidArgumentException('Lesson session has no program.');
        }

        if ($program->price < 1) {
            throw new InvalidArgumentException('Program price must be positive for card checkout.');
        }

        $unitAmount = $this->unitAmountForStripe($program->price);
        $currency = strtolower((string) config('cashier.currency'));

        $customerId = $this->stripeCustomer->ensureStripeCustomerId($user);

        $session = $this->checkoutSessions->create([
            'mode' => 'payment',
            'customer' => $customerId,
            'client_reference_id' => (string) $trialApplication->getKey(),
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => $currency,
                        'unit_amount' => $unitAmount,
                        'product_data' => [
                            'name' => sprintf('体験レッスン: %s', $program->name),
                            'metadata' => [
                                'trial_application_id' => (string) $trialApplication->getKey(),
                            ],
                        ],
                    ],
                    'quantity' => 1,
                ],
            ],
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'trial_application_id' => (string) $trialApplication->getKey(),
                'type' => 'trial',
            ],
        ]);

        $this->persistSessionId($trialApplication, $session);

        $url = $session->url;

        if ($url === null || $url === '') {
            throw new \RuntimeException('Stripe Checkout Session did not return a url.');
        }

        return redirect()->away($url);
    }

    private function assertEligible(TrialApplication $trialApplication): void
    {
        if ($trialApplication->payment_method !== TrialApplication::PAYMENT_METHOD_CARD) {
            throw new InvalidArgumentException('Trial application payment method must be card.');
        }

        if ($trialApplication->status !== TrialApplication::STATUS_PENDING_PAYMENT) {
            throw new InvalidArgumentException('Trial application status must be pending_payment.');
        }

        if ($trialApplication->stripe_checkout_session_id !== null && $trialApplication->stripe_checkout_session_id !== '') {
            throw new InvalidArgumentException('Trial application already has a Stripe Checkout session.');
        }
    }

    private function persistSessionId(TrialApplication $trialApplication, Session $session): void
    {
        $sessionId = $session->id;

        if ($sessionId === null || $sessionId === '') {
            throw new \RuntimeException('Stripe Checkout Session did not return an id.');
        }

        $trialApplication->update([
            'stripe_checkout_session_id' => $sessionId,
        ]);
    }

    /**
     * `programs.price` は主通貨の単位（例: JPY なら円、USD ならドル）を整数で保持する。
     */
    private function unitAmountForStripe(int $amountInMajorUnits): int
    {
        $currency = strtolower((string) config('cashier.currency'));

        if ($this->isZeroDecimalCurrency($currency)) {
            return $amountInMajorUnits;
        }

        return (int) round($amountInMajorUnits * 100);
    }

    private function isZeroDecimalCurrency(string $currencyLower): bool
    {
        return in_array($currencyLower, [
            'bif', 'clp', 'djf', 'gnf', 'jpy', 'kmf', 'krw', 'mga', 'pyg', 'rwf',
            'ugx', 'vnd', 'vuv', 'xaf', 'xof', 'xpf',
        ], true);
    }
}
