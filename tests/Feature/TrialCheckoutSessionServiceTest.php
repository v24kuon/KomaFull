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

    public function test_it_rejects_when_checkout_session_id_already_exists(): void
    {
        $user = User::factory()->create();
        $program = Program::factory()->create(['price' => 1500]);
        $lessonSession = LessonSession::factory()->create(['program_id' => $program->id]);

        $trial = TrialApplication::factory()->create([
            'user_id' => $user->id,
            'lesson_session_id' => $lessonSession->id,
            'payment_method' => TrialApplication::PAYMENT_METHOD_CARD,
            'status' => TrialApplication::STATUS_PENDING_PAYMENT,
            'stripe_checkout_session_id' => 'cs_test_existing',
        ]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('already has');

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
}
