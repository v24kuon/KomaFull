<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use App\Http\Requests\Member\CancelMemberSubscriptionRequest;
use App\Http\Requests\Member\ResumeMemberSubscriptionRequest;
use App\Http\Requests\Member\SwapMemberSubscriptionRequest;
use App\Models\MemberProfile;
use App\Models\User;
use App\Services\Member\MemberSubscriptionManagementService;
use Carbon\CarbonInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use InvalidArgumentException;
use Laravel\Cashier\Exceptions\IncompletePayment;
use Laravel\Cashier\Exceptions\SubscriptionUpdateFailure;
use Laravel\Cashier\Subscription;
use LogicException;
use Throwable;

class SettingsSubscriptionController extends Controller
{
    public function __construct(
        private readonly MemberSubscriptionManagementService $subscriptionManagement,
    ) {}

    /**
     * サブスクリプション（プラン変更・解約）画面を表示する。
     */
    public function edit(): View|RedirectResponse
    {
        $user = Auth::user();
        if (! $user instanceof User) {
            abort(403);
        }

        if ($user->memberProfile === null) {
            return redirect()
                ->route('member.dashboard')
                ->with('error', MemberProfile::FLASH_ERROR_MISSING_PROFILE_VERIFIED);
        }

        /** @var Subscription|null $subscription */
        $subscription = $user->subscription('default');
        $currentPlan = $this->subscriptionManagement->resolveCoursePlan($subscription);
        $currentPriceId = $subscription !== null
            ? $this->subscriptionManagement->currentStripePriceId($subscription)
            : null;

        $swapCandidates = collect();
        $canSwap = false;
        $canCancel = false;
        $canResume = false;

        if ($subscription !== null) {
            $canSwap = $this->subscriptionManagement->canSwap($subscription);
            $canCancel = $this->subscriptionManagement->canCancelAtPeriodEnd($subscription);
            $canResume = $this->subscriptionManagement->canResume($subscription);

            if ($canSwap) {
                $swapCandidates = $this->subscriptionManagement->swapCandidates($currentPriceId);
            }
        }

        $subscriptionCurrentPeriodEnd = $this->resolveSubscriptionCurrentPeriodEndForDisplay($subscription);

        return view('pages.member.settings.subscription', [
            'user' => $user,
            'subscription' => $subscription,
            'currentPlan' => $currentPlan,
            'currentPriceId' => $currentPriceId,
            'swapCandidates' => $swapCandidates,
            'canSwap' => $canSwap,
            'canCancel' => $canCancel,
            'canResume' => $canResume,
            'hasActiveLikeSubscription' => $subscription !== null && $subscription->valid(),
            'subscriptionCurrentPeriodEnd' => $subscriptionCurrentPeriodEnd,
        ]);
    }

    /**
     * プランを変更する（Stripe の price へ swap）。
     */
    public function swap(SwapMemberSubscriptionRequest $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        $stripePriceId = $request->validated('stripe_price_id');

        try {
            $this->subscriptionManagement->swapToPrice($user, $stripePriceId);
        } catch (InvalidArgumentException $e) {
            Log::warning('Member subscription swap rejected (business rule)', [
                'user_id' => $user->getKey(),
                'stripe_price_id' => $stripePriceId,
                'exception' => $e,
            ]);

            return redirect()
                ->route('member.settings.subscription.edit')
                ->with('error', $this->memberSubscriptionInvalidArgumentUserMessage($e));
        } catch (IncompletePayment $e) {
            Log::warning('Member subscription swap incomplete payment', [
                'user_id' => $user->getKey(),
                'stripe_price_id' => $stripePriceId,
                'exception' => $e,
            ]);

            return redirect()
                ->route('member.settings.subscription.edit')
                ->with('error', 'お支払いの確認が必要です。カード情報を更新してから再度お試しください。');
        } catch (SubscriptionUpdateFailure $e) {
            Log::error('Member subscription swap failed (Stripe update)', [
                'user_id' => $user->getKey(),
                'stripe_price_id' => $stripePriceId,
                'exception' => $e,
            ]);

            return redirect()
                ->route('member.settings.subscription.edit')
                ->with('error', 'プラン変更を完了できませんでした。時間をおいて再度お試しください。');
        } catch (Throwable $e) {
            Log::error('Member subscription swap failed (unexpected)', [
                'user_id' => $user->getKey(),
                'stripe_price_id' => $stripePriceId,
                'exception' => $e,
            ]);

            return redirect()
                ->route('member.settings.subscription.edit')
                ->with('error', 'プラン変更を完了できませんでした。時間をおいて再度お試しください。');
        }

        return redirect()
            ->route('member.settings.subscription.edit')
            ->with('success', 'プランを変更しました。次回請求から新しい内容が適用されます。');
    }

    /**
     * 請求期間末での解約を予約する。
     */
    public function cancel(CancelMemberSubscriptionRequest $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        try {
            $this->subscriptionManagement->cancelAtPeriodEnd($user);
        } catch (InvalidArgumentException $e) {
            Log::warning('Member subscription cancel-at-period-end rejected (business rule)', [
                'user_id' => $user->getKey(),
                'exception' => $e,
            ]);

            return redirect()
                ->route('member.settings.subscription.edit')
                ->with('error', $this->memberSubscriptionInvalidArgumentUserMessage($e));
        } catch (Throwable $e) {
            Log::error('Member subscription cancel-at-period-end failed (unexpected)', [
                'user_id' => $user->getKey(),
                'exception' => $e,
            ]);

            return redirect()
                ->route('member.settings.subscription.edit')
                ->with('error', '解約手続きを完了できませんでした。時間をおいて再度お試しください。');
        }

        return redirect()
            ->route('member.settings.subscription.edit')
            ->with('success', '請求期間の終了日に解約するよう予約しました。');
    }

    /**
     * 解約予約を取り消す。
     */
    public function resume(ResumeMemberSubscriptionRequest $request): RedirectResponse
    {
        $user = $request->user();
        if (! $user instanceof User) {
            abort(403);
        }

        try {
            $this->subscriptionManagement->resume($user);
        } catch (InvalidArgumentException $e) {
            Log::warning('Member subscription resume rejected (business rule)', [
                'user_id' => $user->getKey(),
                'exception' => $e,
            ]);

            return redirect()
                ->route('member.settings.subscription.edit')
                ->with('error', $this->memberSubscriptionInvalidArgumentUserMessage($e));
        } catch (LogicException $e) {
            Log::warning('Member subscription resume rejected (Cashier)', [
                'user_id' => $user->getKey(),
                'exception' => $e,
            ]);

            return redirect()
                ->route('member.settings.subscription.edit')
                ->with('error', '解約予約を取り消せませんでした。状態をご確認のうえ、再度お試しください。');
        } catch (Throwable $e) {
            Log::error('Member subscription resume failed (unexpected)', [
                'user_id' => $user->getKey(),
                'exception' => $e,
            ]);

            return redirect()
                ->route('member.settings.subscription.edit')
                ->with('error', '解約の取り消しを完了できませんでした。時間をおいて再度お試しください。');
        }

        return redirect()
            ->route('member.settings.subscription.edit')
            ->with('success', '解約予約を取り消しました。');
    }

    /**
     * {@see MemberSubscriptionManagementService} が投げる {@see InvalidArgumentException} のメッセージを会員向け文言へマップする。
     *
     * 前提: サービス層の例外メッセージ文字列と本メソッド内の match キーが整合していること。
     */
    private function memberSubscriptionInvalidArgumentUserMessage(InvalidArgumentException $e): string
    {
        return match ($e->getMessage()) {
            'Subscription not found.' => '有効なサブスクリプションが見つかりません。',
            'Subscription cannot be swapped.' => '現在、プランを変更できる状態ではありません。',
            'Already on this price.' => 'すでに同じプランです。',
            'Invalid course plan.' => '選択したプランは現在ご利用できません。',
            'Subscription cannot be canceled.' => '現在、請求期間末での解約を予約できる状態ではありません。',
            'Subscription cannot be resumed.' => '現在、解約の取り消しができる状態ではありません。',
            default => '操作を完了できませんでした。内容をご確認のうえ、再度お試しください。',
        };
    }

    /**
     * 表示用の請求期間終了日時を1回だけ解決する。
     *
     * `Subscription::currentPeriodEnd()` は内部で Stripe API を参照し得るため、ビューで複数回呼ばない。
     * 取得失敗時は画面全体を落とさず、目安行を出さない。
     *
     * `null` を返す条件: `$subscription` が null または非 valid、トライアル中、猶予期間中、`currentPeriodEnd()` が例外または取得不能。
     */
    private function resolveSubscriptionCurrentPeriodEndForDisplay(?Subscription $subscription): ?CarbonInterface
    {
        if ($subscription === null || ! $subscription->valid()) {
            return null;
        }

        if ($subscription->onTrial() || $subscription->onGracePeriod()) {
            return null;
        }

        try {
            return $subscription->currentPeriodEnd();
        } catch (Throwable $e) {
            Log::warning('Subscription currentPeriodEnd failed while rendering subscription settings', [
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->getKey(),
                'exception' => $e,
            ]);

            return null;
        }
    }
}
