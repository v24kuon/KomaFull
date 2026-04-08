<?php

namespace App\Services\Checkout;

use App\Contracts\CreatesStripeCheckoutSession;
use App\Contracts\EnsuresStripeCustomer;
use App\Models\LessonSession;
use App\Models\Program;
use App\Models\TrialApplication;
use App\Models\User;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;
use Money\Currencies\ISOCurrencies;
use Money\Currency as Iso4217Currency;
use Money\Exception\UnknownCurrencyException;
use Stripe\Checkout\Session;
use Stripe\Exception\InvalidRequestException;

/**
 * 体験レッスン（カード）用の Stripe Checkout セッションを作成し、リダイレクト先 URL を返す。
 *
 * 前提: `trial_applications` は `pending_payment` かつ `payment_method=card`。金額は紐づく `programs.price`（主通貨の整数単位）を使用する。
 * 副作用: Stripe Checkout Session 作成 API と `trial_applications.stripe_checkout_session_id` の更新。
 *
 * 並行制御（クラス全体）: 同一申込行に対する二重セッション作成を防ぐため、`redirectToCheckout` 内で `lockForUpdate()` により行ロックしたうえで資格再検証し、Stripe 呼び出しと DB 更新を同一 DB トランザクションに収める（外部 API はロック保持中に実行するトレードオフあり）。詳細は `redirectToCheckout` の PHPDoc を参照。
 */
class TrialCheckoutSessionService
{
    public function __construct(
        private readonly CreatesStripeCheckoutSession $checkoutSessions,
        private readonly EnsuresStripeCustomer $stripeCustomer,
        private readonly ConnectionInterface $connection,
    ) {}

    /**
     * Checkout Session を作成し、試行申込に `stripe_checkout_session_id` を保存して Stripe へリダイレクトする。
     *
     * トランザクション境界: `ConnectionInterface::transaction()` が本メソッドの呼び出し全体を囲む。コミットは Stripe Session 作成成功後、`stripe_checkout_session_id` 更新まで含めて一度だけ行われる。Stripe 作成前に例外が出た場合は Checkout Session は存在しない。作成成功後に DB 更新前に例外が出た場合は DB はロールバックされるが、Stripe 上に未紐付けの Checkout Session が残り得る（稀な障害時は運用で確認・失効させる）。
     *
     * ロック戦略: トランザクション内で対象 `trial_applications` 行を `lockForUpdate()` してから `assertCardPendingPayment` と既存 Session の retrieve を行う。同一行への並行リクエストは一方がロック解放まで待機し、先に完了した側が `stripe_checkout_session_id` を埋めたあと、後続は `open` な Session の再利用で二重の create を避ける。
     *
     * 冪等性・再実行: `stripe_checkout_session_id` が既にある場合は Stripe `checkout.sessions.retrieve` で状態を確認する。`open` なら同一 URL へ再誘導（新規 create はしない）。`expired`・Stripe 上に存在しない id（`resource_missing`）・`complete` かつ未払い（`unpaid`）では DB の id をクリアしてから新規 Session を作成する。`complete` かつ `paid` で申込が未更新の場合は Webhook 遅延を想定し `success_url` の `{CHECKOUT_SESSION_ID}` を埋めてリダイレクトする（二重課金を避ける）。Stripe Checkout Session 作成 API には本メソから `Idempotency-Key` を付与していない（必要なら呼び出し側またはゲートウェイ層で追加を検討する）。
     *
     * 処理順序（新規 Session 作成経路）: `unitAmountForStripe` による通貨・金額の検証を `ensureStripeCustomerId` より前に実行する。未対応通貨（`config('cashier.currency')` が ISO 4217 として解決できない等）では Stripe Customer 作成 API を呼ばずに失敗させる。この順序を変更すると不要な Stripe 呼び出しや `tests/Feature/TrialCheckoutSessionServiceTest::test_it_rejects_unknown_iso_currency_before_calling_stripe` の期待と不整合になり得る。
     *
     * Idempotency: 新規 `checkout.sessions.create` には `CreatesStripeCheckoutSession::create` の第2引数で `idempotency_key` を渡す。同一試行の再送で Stripe 上に重複 Session が作られにくくする（キーは申込主キー由来。ペイロードが変わる再試行は Stripe の idempotency 仕様どおり衝突し得る）。
     *
     * @param  non-empty-string  $successUrl  Stripe の `success_url`（例: `{CHECKOUT_SESSION_ID}` placeholder を含む）
     * @param  non-empty-string  $cancelUrl  Stripe の `cancel_url`
     *
     * @throws InvalidArgumentException 申込がカード決済の未決済でない、または金額が不正な場合
     * @throws \RuntimeException Stripe が session id または url を返さない場合
     */
    public function redirectToCheckout(TrialApplication $trialApplication, string $successUrl, string $cancelUrl): RedirectResponse
    {
        $trialId = $trialApplication->getKey();

        return $this->connection->transaction(function () use ($trialId, $successUrl, $cancelUrl): RedirectResponse {
            $trial = TrialApplication::query()
                ->whereKey($trialId)
                ->with(['user', 'lessonSession.program'])
                ->lockForUpdate()
                ->firstOrFail();

            $this->assertCardPendingPayment($trial);

            $redirectFromExisting = $this->maybeRedirectUsingExistingCheckoutSession($trial, $successUrl);

            if ($redirectFromExisting !== null) {
                return $redirectFromExisting;
            }

            $user = $trial->user;

            if (! $user instanceof User) {
                throw new InvalidArgumentException('Trial application has no user.');
            }

            $lessonSession = $trial->lessonSession;

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

            $currency = strtolower((string) config('cashier.currency'));
            $unitAmount = $this->unitAmountForStripe($program->price, $currency);

            $customerId = $this->stripeCustomer->ensureStripeCustomerId($user);

            $session = $this->checkoutSessions->create([
                'mode' => 'payment',
                'customer' => $customerId,
                'client_reference_id' => (string) $trial->getKey(),
                'line_items' => [
                    [
                        'price_data' => [
                            'currency' => $currency,
                            'unit_amount' => $unitAmount,
                            'product_data' => [
                                'name' => sprintf('体験レッスン: %s', $program->name),
                                'metadata' => [
                                    'trial_application_id' => (string) $trial->getKey(),
                                ],
                            ],
                        ],
                        'quantity' => 1,
                    ],
                ],
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata' => [
                    'trial_application_id' => (string) $trial->getKey(),
                    'type' => 'trial',
                ],
            ], [
                'idempotency_key' => $this->idempotencyKeyForTrialCheckoutSessionCreate($trial),
            ]);

            $this->persistSessionId($trial, $session);

            $url = $session->url;

            if ($url === null || $url === '') {
                throw new \RuntimeException('Stripe Checkout Session did not return a url.');
            }

            return redirect()->away($url);
        });
    }

    /**
     * カード決済・未払い（pending_payment）のみを許可する。Checkout Session id の有無は `maybeRedirectUsingExistingCheckoutSession` で扱う。
     */
    private function assertCardPendingPayment(TrialApplication $trialApplication): void
    {
        if ($trialApplication->payment_method !== TrialApplication::PAYMENT_METHOD_CARD) {
            throw new InvalidArgumentException('Trial application payment method must be card.');
        }

        if ($trialApplication->status !== TrialApplication::STATUS_PENDING_PAYMENT) {
            throw new InvalidArgumentException('Trial application status must be pending_payment.');
        }
    }

    /**
     * 既に保存された Checkout Session id がある場合、retrieve して再利用またはクリアする。
     *
     * @return RedirectResponse|null 再利用で即リダイレクトする場合はレスポンス。新規作成フローへ進む場合は null。
     */
    private function maybeRedirectUsingExistingCheckoutSession(TrialApplication $trial, string $successUrl): ?RedirectResponse
    {
        $sessionId = trim((string) $trial->stripe_checkout_session_id);

        if ($sessionId === '') {
            return null;
        }

        try {
            $existing = $this->checkoutSessions->retrieve($sessionId);
        } catch (InvalidRequestException $e) {
            if ($e->getStripeCode() === 'resource_missing') {
                $trial->update([
                    'stripe_checkout_session_id' => null,
                ]);

                return null;
            }

            throw $e;
        }

        $status = (string) ($existing->status ?? '');

        if ($status === 'open') {
            $url = $existing->url;

            if ($url !== null && $url !== '') {
                return redirect()->away($url);
            }

            $trial->update([
                'stripe_checkout_session_id' => null,
            ]);

            return null;
        }

        if ($status === 'expired') {
            $trial->update([
                'stripe_checkout_session_id' => null,
            ]);

            return null;
        }

        if ($status === 'complete') {
            $paymentStatus = (string) ($existing->payment_status ?? '');

            if ($paymentStatus === 'paid' || $paymentStatus === 'no_payment_required') {
                $checkoutSessionId = (string) ($existing->id ?? '');

                if ($checkoutSessionId === '') {
                    throw new \RuntimeException('Stripe Checkout Session did not return an id.');
                }

                $target = str_replace('{CHECKOUT_SESSION_ID}', $checkoutSessionId, $successUrl);

                return redirect()->away($target);
            }

            if ($paymentStatus === 'unpaid') {
                $trial->update([
                    'stripe_checkout_session_id' => null,
                ]);

                return null;
            }
        }

        throw new InvalidArgumentException('Trial application has a Stripe Checkout session in an unexpected state.');
    }

    /**
     * `checkout.sessions.create` に渡す Idempotency-Key。申込主キー単位で安定させ、同一再送で重複 Session 作成を抑止する。
     *
     * @return non-empty-string
     */
    private function idempotencyKeyForTrialCheckoutSessionCreate(TrialApplication $trial): string
    {
        return sprintf('trial-checkout-%s', $trial->getKey());
    }

    /**
     * ロック済み `trial` 行に Checkout Session id を保存する。呼び出しは `redirectToCheckout` のトランザクション内に限定する。
     */
    private function persistSessionId(TrialApplication $trial, Session $session): void
    {
        $sessionId = $session->id;

        if ($sessionId === null || $sessionId === '') {
            throw new \RuntimeException('Stripe Checkout Session did not return an id.');
        }

        $trial->update([
            'stripe_checkout_session_id' => $sessionId,
        ]);
    }

    /**
     * `programs.price` は主通貨の単位（例: JPY なら円、USD ならドル）を整数で保持する。
     *
     * Stripe の `unit_amount` は最小通貨単位。原則として ISO 4217 の `minorUnit`（`moneyphp/money` の `ISOCurrencies::subunitFor()`）で 10^minor へ換算する。
     *
     * 例外: Stripe は ISK / UGX を ISO の minor 0 ではなく、常に「2 桁相当」の最小単位（100 倍）で表す（例: 5 ISK → `unit_amount` 500）。`subunitFor` が 0 を返すまま送ると実額が 1/100 になる。
     *
     * @param  non-empty-string  $currencyLower  `config('cashier.currency')` を小文字化したもの（呼び出し側で1回だけ取得して渡す）
     */
    private function unitAmountForStripe(int $amountInMajorUnits, string $currencyLower): int
    {
        $normalized = strtolower($currencyLower);

        if (in_array($normalized, ['isk', 'ugx'], true)) {
            return $amountInMajorUnits * 100;
        }

        $currencies = new ISOCurrencies;

        try {
            $minorUnit = $currencies->subunitFor(
                new Iso4217Currency(strtoupper($currencyLower))
            );
        } catch (UnknownCurrencyException) {
            throw new InvalidArgumentException(
                sprintf('Unsupported currency for Stripe checkout: %s', $currencyLower)
            );
        }

        if ($minorUnit === 0) {
            return $amountInMajorUnits;
        }

        return (int) round($amountInMajorUnits * (10 ** $minorUnit));
    }
}
