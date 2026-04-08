<?php

namespace Tests\Feature;

use App\Contracts\CreatesStripeCheckoutSession;
use App\Contracts\EnsuresStripeCustomer;
use App\Models\LessonSession;
use App\Models\Program;
use App\Models\TrialApplication;
use App\Models\User;
use App\Services\Checkout\TrialCheckoutSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;
use Mockery;
use Stripe\Checkout\Session;
use Stripe\Exception\InvalidRequestException;
use Tests\TestCase;

class TrialCheckoutSessionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['cashier.currency' => 'jpy']);
    }

    public function test_redirect_to_checkout_updates_trial_and_redirects_to_stripe(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['price' => 1500]);
        $lessonSession = LessonSession::factory()->create(['program_id' => $program->id]);

        $trial = TrialApplication::factory()->create([
            'user_id' => $user->id,
            'lesson_session_id' => $lessonSession->id,
            'payment_method' => TrialApplication::PAYMENT_METHOD_CARD,
            'status' => TrialApplication::STATUS_PENDING_PAYMENT,
            'stripe_checkout_session_id' => null,
        ]);

        $session = Session::constructFrom([
            'id' => 'cs_test_abc123',
            'object' => 'checkout.session',
            'url' => 'https://checkout.stripe.com/c/pay/cs_test_abc123',
        ]);

        $checkoutMock = Mockery::mock(CreatesStripeCheckoutSession::class);
        $checkoutMock->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (array $params): bool {
                return $params['mode'] === 'payment'
                    && $params['customer'] === 'cus_test_1'
                    && $params['line_items'][0]['price_data']['unit_amount'] === 1500
                    && $params['line_items'][0]['price_data']['currency'] === 'jpy';
            }))
            ->andReturn($session);

        $customerMock = Mockery::mock(EnsuresStripeCustomer::class);
        $customerMock->shouldReceive('ensureStripeCustomerId')->once()->with(Mockery::type(User::class))->andReturn('cus_test_1');

        $this->app->instance(CreatesStripeCheckoutSession::class, $checkoutMock);
        $this->app->instance(EnsuresStripeCustomer::class, $customerMock);

        $service = app(TrialCheckoutSessionService::class);

        $response = $service->redirectToCheckout(
            $trial,
            'https://example.test/success?session_id={CHECKOUT_SESSION_ID}',
            'https://example.test/cancel'
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('https://checkout.stripe.com/c/pay/cs_test_abc123', $response->getTargetUrl());

        $trial->refresh();
        $this->assertSame('cs_test_abc123', $trial->stripe_checkout_session_id);
    }

    public function test_second_redirect_reuses_open_checkout_session_via_retrieve_without_second_create(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['price' => 1500]);
        $lessonSession = LessonSession::factory()->create(['program_id' => $program->id]);

        $trial = TrialApplication::factory()->create([
            'user_id' => $user->id,
            'lesson_session_id' => $lessonSession->id,
            'payment_method' => TrialApplication::PAYMENT_METHOD_CARD,
            'status' => TrialApplication::STATUS_PENDING_PAYMENT,
            'stripe_checkout_session_id' => null,
        ]);

        $session = Session::constructFrom([
            'id' => 'cs_test_first',
            'object' => 'checkout.session',
            'status' => 'open',
            'url' => 'https://checkout.stripe.com/c/pay/cs_test_first',
        ]);

        $retrieved = Session::constructFrom([
            'id' => 'cs_test_first',
            'object' => 'checkout.session',
            'status' => 'open',
            'url' => 'https://checkout.stripe.com/c/pay/cs_test_first',
        ]);

        $checkoutMock = Mockery::mock(CreatesStripeCheckoutSession::class);
        $checkoutMock->shouldReceive('create')->once()->andReturn($session);
        $checkoutMock->shouldReceive('retrieve')
            ->once()
            ->with('cs_test_first')
            ->andReturn($retrieved);

        $customerMock = Mockery::mock(EnsuresStripeCustomer::class);
        $customerMock->shouldReceive('ensureStripeCustomerId')->once()->with(Mockery::type(User::class))->andReturn('cus_test_1');

        $this->app->instance(CreatesStripeCheckoutSession::class, $checkoutMock);
        $this->app->instance(EnsuresStripeCustomer::class, $customerMock);

        $service = app(TrialCheckoutSessionService::class);

        $first = $service->redirectToCheckout(
            $trial,
            'https://example.test/success?session_id={CHECKOUT_SESSION_ID}',
            'https://example.test/cancel'
        );

        $this->assertSame('https://checkout.stripe.com/c/pay/cs_test_first', $first->getTargetUrl());

        $second = $service->redirectToCheckout(
            $trial->fresh(),
            'https://example.test/success?session_id={CHECKOUT_SESSION_ID}',
            'https://example.test/cancel'
        );

        $this->assertSame('https://checkout.stripe.com/c/pay/cs_test_first', $second->getTargetUrl());
    }

    public function test_it_rejects_non_card_payment_method(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['price' => 1500]);
        $lessonSession = LessonSession::factory()->create(['program_id' => $program->id]);

        $trial = TrialApplication::factory()->create([
            'user_id' => $user->id,
            'lesson_session_id' => $lessonSession->id,
            'payment_method' => TrialApplication::PAYMENT_METHOD_ONSITE,
            'status' => TrialApplication::STATUS_PENDING_PAYMENT,
            'stripe_checkout_session_id' => null,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('card');

        app(TrialCheckoutSessionService::class)->redirectToCheckout(
            $trial,
            'https://example.test/success?session_id={CHECKOUT_SESSION_ID}',
            'https://example.test/cancel'
        );
    }

    public function test_it_rejects_non_pending_payment_status(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['price' => 1500]);
        $lessonSession = LessonSession::factory()->create(['program_id' => $program->id]);

        $trial = TrialApplication::factory()->create([
            'user_id' => $user->id,
            'lesson_session_id' => $lessonSession->id,
            'payment_method' => TrialApplication::PAYMENT_METHOD_CARD,
            'status' => TrialApplication::STATUS_RESERVED,
            'stripe_checkout_session_id' => null,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('pending_payment');

        app(TrialCheckoutSessionService::class)->redirectToCheckout(
            $trial,
            'https://example.test/success?session_id={CHECKOUT_SESSION_ID}',
            'https://example.test/cancel'
        );
    }

    public function test_it_rejects_when_program_price_is_zero(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['price' => 0]);
        $lessonSession = LessonSession::factory()->create(['program_id' => $program->id]);

        $trial = TrialApplication::factory()->create([
            'user_id' => $user->id,
            'lesson_session_id' => $lessonSession->id,
            'payment_method' => TrialApplication::PAYMENT_METHOD_CARD,
            'status' => TrialApplication::STATUS_PENDING_PAYMENT,
            'stripe_checkout_session_id' => null,
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('positive');

        app(TrialCheckoutSessionService::class)->redirectToCheckout(
            $trial,
            'https://example.test/success?session_id={CHECKOUT_SESSION_ID}',
            'https://example.test/cancel'
        );
    }

    public function test_expired_checkout_session_id_is_cleared_and_new_session_is_created(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['price' => 1500]);
        $lessonSession = LessonSession::factory()->create(['program_id' => $program->id]);

        $trial = TrialApplication::factory()->create([
            'user_id' => $user->id,
            'lesson_session_id' => $lessonSession->id,
            'payment_method' => TrialApplication::PAYMENT_METHOD_CARD,
            'status' => TrialApplication::STATUS_PENDING_PAYMENT,
            'stripe_checkout_session_id' => 'cs_test_expired',
        ]);

        $expiredSession = Session::constructFrom([
            'id' => 'cs_test_expired',
            'object' => 'checkout.session',
            'status' => 'expired',
        ]);

        $newSession = Session::constructFrom([
            'id' => 'cs_test_new_after_expired',
            'object' => 'checkout.session',
            'status' => 'open',
            'url' => 'https://checkout.stripe.com/c/pay/cs_test_new_after_expired',
        ]);

        $checkoutMock = Mockery::mock(CreatesStripeCheckoutSession::class);
        $checkoutMock->shouldReceive('retrieve')
            ->once()
            ->with('cs_test_expired')
            ->andReturn($expiredSession);
        $checkoutMock->shouldReceive('create')
            ->once()
            ->andReturn($newSession);

        $customerMock = Mockery::mock(EnsuresStripeCustomer::class);
        $customerMock->shouldReceive('ensureStripeCustomerId')->once()->with(Mockery::type(User::class))->andReturn('cus_test_1');

        $this->app->instance(CreatesStripeCheckoutSession::class, $checkoutMock);
        $this->app->instance(EnsuresStripeCustomer::class, $customerMock);

        $response = app(TrialCheckoutSessionService::class)->redirectToCheckout(
            $trial,
            'https://example.test/success?session_id={CHECKOUT_SESSION_ID}',
            'https://example.test/cancel'
        );

        $this->assertSame('https://checkout.stripe.com/c/pay/cs_test_new_after_expired', $response->getTargetUrl());

        $trial->refresh();
        $this->assertSame('cs_test_new_after_expired', $trial->stripe_checkout_session_id);
    }

    public function test_missing_checkout_session_on_stripe_clears_id_and_creates_new(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['price' => 1500]);
        $lessonSession = LessonSession::factory()->create(['program_id' => $program->id]);

        $trial = TrialApplication::factory()->create([
            'user_id' => $user->id,
            'lesson_session_id' => $lessonSession->id,
            'payment_method' => TrialApplication::PAYMENT_METHOD_CARD,
            'status' => TrialApplication::STATUS_PENDING_PAYMENT,
            'stripe_checkout_session_id' => 'cs_test_missing',
        ]);

        $missing = InvalidRequestException::factory(
            'No such checkout.session: cs_test_missing',
            404,
            null,
            null,
            null,
            'resource_missing'
        );

        $newSession = Session::constructFrom([
            'id' => 'cs_test_after_missing',
            'object' => 'checkout.session',
            'status' => 'open',
            'url' => 'https://checkout.stripe.com/c/pay/cs_test_after_missing',
        ]);

        $checkoutMock = Mockery::mock(CreatesStripeCheckoutSession::class);
        $checkoutMock->shouldReceive('retrieve')
            ->once()
            ->with('cs_test_missing')
            ->andThrow($missing);
        $checkoutMock->shouldReceive('create')
            ->once()
            ->andReturn($newSession);

        $customerMock = Mockery::mock(EnsuresStripeCustomer::class);
        $customerMock->shouldReceive('ensureStripeCustomerId')->once()->with(Mockery::type(User::class))->andReturn('cus_test_1');

        $this->app->instance(CreatesStripeCheckoutSession::class, $checkoutMock);
        $this->app->instance(EnsuresStripeCustomer::class, $customerMock);

        $response = app(TrialCheckoutSessionService::class)->redirectToCheckout(
            $trial,
            'https://example.test/success?session_id={CHECKOUT_SESSION_ID}',
            'https://example.test/cancel'
        );

        $this->assertSame('https://checkout.stripe.com/c/pay/cs_test_after_missing', $response->getTargetUrl());

        $trial->refresh();
        $this->assertSame('cs_test_after_missing', $trial->stripe_checkout_session_id);
    }

    public function test_complete_paid_session_redirects_to_success_url_without_second_create(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['price' => 1500]);
        $lessonSession = LessonSession::factory()->create(['program_id' => $program->id]);

        $trial = TrialApplication::factory()->create([
            'user_id' => $user->id,
            'lesson_session_id' => $lessonSession->id,
            'payment_method' => TrialApplication::PAYMENT_METHOD_CARD,
            'status' => TrialApplication::STATUS_PENDING_PAYMENT,
            'stripe_checkout_session_id' => 'cs_test_complete_paid',
        ]);

        $completeSession = Session::constructFrom([
            'id' => 'cs_test_complete_paid',
            'object' => 'checkout.session',
            'status' => 'complete',
            'payment_status' => 'paid',
        ]);

        $checkoutMock = Mockery::mock(CreatesStripeCheckoutSession::class);
        $checkoutMock->shouldReceive('retrieve')
            ->once()
            ->with('cs_test_complete_paid')
            ->andReturn($completeSession);
        $checkoutMock->shouldReceive('create')->never();

        $customerMock = Mockery::mock(EnsuresStripeCustomer::class);
        $customerMock->shouldReceive('ensureStripeCustomerId')->never();

        $this->app->instance(CreatesStripeCheckoutSession::class, $checkoutMock);
        $this->app->instance(EnsuresStripeCustomer::class, $customerMock);

        $response = app(TrialCheckoutSessionService::class)->redirectToCheckout(
            $trial,
            'https://example.test/success?sid={CHECKOUT_SESSION_ID}',
            'https://example.test/cancel'
        );

        $this->assertSame('https://example.test/success?sid=cs_test_complete_paid', $response->getTargetUrl());
    }

    public function test_it_rejects_unexpected_checkout_session_state(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['price' => 1500]);
        $lessonSession = LessonSession::factory()->create(['program_id' => $program->id]);

        $trial = TrialApplication::factory()->create([
            'user_id' => $user->id,
            'lesson_session_id' => $lessonSession->id,
            'payment_method' => TrialApplication::PAYMENT_METHOD_CARD,
            'status' => TrialApplication::STATUS_PENDING_PAYMENT,
            'stripe_checkout_session_id' => 'cs_test_weird',
        ]);

        $weirdSession = Session::constructFrom([
            'id' => 'cs_test_weird',
            'object' => 'checkout.session',
            'status' => 'complete',
            'payment_status' => 'unknown_future_status',
        ]);

        $checkoutMock = Mockery::mock(CreatesStripeCheckoutSession::class);
        $checkoutMock->shouldReceive('retrieve')
            ->once()
            ->with('cs_test_weird')
            ->andReturn($weirdSession);
        $checkoutMock->shouldReceive('create')->never();

        $customerMock = Mockery::mock(EnsuresStripeCustomer::class);
        $customerMock->shouldReceive('ensureStripeCustomerId')->never();

        $this->app->instance(CreatesStripeCheckoutSession::class, $checkoutMock);
        $this->app->instance(EnsuresStripeCustomer::class, $customerMock);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('unexpected state');

        app(TrialCheckoutSessionService::class)->redirectToCheckout(
            $trial,
            'https://example.test/success?session_id={CHECKOUT_SESSION_ID}',
            'https://example.test/cancel'
        );
    }

    public function test_usd_currency_uses_cents_for_unit_amount(): void
    {
        config(['cashier.currency' => 'usd']);

        $user = User::factory()->create();
        $program = Program::factory()->create(['price' => 12]);
        $lessonSession = LessonSession::factory()->create(['program_id' => $program->id]);

        $trial = TrialApplication::factory()->create([
            'user_id' => $user->id,
            'lesson_session_id' => $lessonSession->id,
            'payment_method' => TrialApplication::PAYMENT_METHOD_CARD,
            'status' => TrialApplication::STATUS_PENDING_PAYMENT,
            'stripe_checkout_session_id' => null,
        ]);

        $session = Session::constructFrom([
            'id' => 'cs_test_usd',
            'object' => 'checkout.session',
            'url' => 'https://checkout.stripe.com/c/pay/cs_test_usd',
        ]);

        $checkoutMock = Mockery::mock(CreatesStripeCheckoutSession::class);
        $checkoutMock->shouldReceive('create')
            ->once()
            ->with(Mockery::on(function (array $params): bool {
                return $params['line_items'][0]['price_data']['unit_amount'] === 1200
                    && $params['line_items'][0]['price_data']['currency'] === 'usd';
            }))
            ->andReturn($session);

        $customerMock = Mockery::mock(EnsuresStripeCustomer::class);
        $customerMock->shouldReceive('ensureStripeCustomerId')->once()->andReturn('cus_test_usd');

        $this->app->instance(CreatesStripeCheckoutSession::class, $checkoutMock);
        $this->app->instance(EnsuresStripeCustomer::class, $customerMock);

        $response = app(TrialCheckoutSessionService::class)->redirectToCheckout(
            $trial,
            'https://example.test/success?session_id={CHECKOUT_SESSION_ID}',
            'https://example.test/cancel'
        );

        $this->assertInstanceOf(RedirectResponse::class, $response);
    }

    public function test_it_rejects_unknown_iso_currency_before_calling_stripe(): void
    {
        config(['cashier.currency' => 'zzz']);

        $user = User::factory()->create();
        $program = Program::factory()->create(['price' => 1000]);
        $lessonSession = LessonSession::factory()->create(['program_id' => $program->id]);

        $trial = TrialApplication::factory()->create([
            'user_id' => $user->id,
            'lesson_session_id' => $lessonSession->id,
            'payment_method' => TrialApplication::PAYMENT_METHOD_CARD,
            'status' => TrialApplication::STATUS_PENDING_PAYMENT,
            'stripe_checkout_session_id' => null,
        ]);

        $checkoutMock = Mockery::mock(CreatesStripeCheckoutSession::class);
        $checkoutMock->shouldReceive('create')->never();

        $customerMock = Mockery::mock(EnsuresStripeCustomer::class);
        $customerMock->shouldReceive('ensureStripeCustomerId')->never();

        $this->app->instance(CreatesStripeCheckoutSession::class, $checkoutMock);
        $this->app->instance(EnsuresStripeCustomer::class, $customerMock);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported currency');

        app(TrialCheckoutSessionService::class)->redirectToCheckout(
            $trial,
            'https://example.test/success?session_id={CHECKOUT_SESSION_ID}',
            'https://example.test/cancel'
        );
    }
}
