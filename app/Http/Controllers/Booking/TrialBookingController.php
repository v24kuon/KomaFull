<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTrialBookingRequest;
use App\Models\LessonSession;
use App\Models\MemberProfile;
use App\Models\Program;
use App\Models\TrialApplication;
use App\Services\Booking\TrialOnsiteBookingService;
use App\Services\Checkout\TrialCheckoutSessionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Js;
use Illuminate\View\View;

class TrialBookingController extends Controller
{
    /**
     * 体験予約: 開催枠・支払い方法の確認画面。
     */
    public function show(LessonSession $lessonSession): View|RedirectResponse
    {
        $user = request()->user();

        if ($user === null) {
            abort(403);
        }

        $lessonSession->load(['program', 'location', 'staff', 'reservationManagement']);

        $profile = MemberProfile::query()->where('user_id', $user->getKey())->first();

        if (! $profile instanceof MemberProfile || $profile->member_status !== MemberProfile::STATUS_PROVISIONAL) {
            return redirect()
                ->route('schedule.index', ['year' => now()->year, 'month' => now()->month])
                ->with('error', '体験予約は仮会員の方のみご利用いただけます。');
        }

        if ($lessonSession->status !== LessonSession::STATUS_ACTIVE) {
            abort(404);
        }

        if ($lessonSession->starts_at !== null && $lessonSession->starts_at->isPast()) {
            return redirect()
                ->route('schedule.index', ['year' => (int) $lessonSession->starts_at->year, 'month' => (int) $lessonSession->starts_at->month])
                ->with('error', 'この開催枠は既に開始済みのため予約できません。');
        }

        $program = $lessonSession->program;

        if (! $program instanceof Program || $program->status !== Program::STATUS_ACTIVE) {
            abort(404);
        }

        $trialRemaining = $this->trialRemaining($lessonSession);

        if ($trialRemaining < 1) {
            return redirect()
                ->route('schedule.index', ['year' => (int) $lessonSession->starts_at->year, 'month' => (int) $lessonSession->starts_at->month])
                ->with('error', '体験枠が満席のため予約できません。');
        }

        if ($this->hasBlockingTrialApplication((int) $user->getKey(), (int) $lessonSession->getKey())) {
            return redirect()
                ->route('schedule.index', ['year' => (int) $lessonSession->starts_at->year, 'month' => (int) $lessonSession->starts_at->month])
                ->with('error', 'この開催枠には既に体験のお申し込みがあります。');
        }

        return view('pages.booking.trial.show', [
            'lessonSession' => $lessonSession,
            'trialRemaining' => $trialRemaining,
        ]);
    }

    /**
     * 体験予約の送信（カード: Checkout へ / 現地: 即確定）。
     */
    public function store(
        StoreTrialBookingRequest $request,
        TrialOnsiteBookingService $onsiteBooking,
        TrialCheckoutSessionService $checkoutSessionService
    ): RedirectResponse {
        $user = $request->user();

        if ($user === null) {
            abort(403);
        }

        $validated = $request->validated();

        $lessonSession = LessonSession::query()
            ->with('program')
            ->findOrFail((int) $validated['lesson_session_id']);

        $profile = MemberProfile::query()->where('user_id', $user->getKey())->first();

        if (! $profile instanceof MemberProfile || $profile->member_status !== MemberProfile::STATUS_PROVISIONAL) {
            return redirect()
                ->route('schedule.index', ['year' => now()->year, 'month' => now()->month])
                ->with('error', '体験予約は仮会員の方のみご利用いただけます。');
        }

        if ($lessonSession->status !== LessonSession::STATUS_ACTIVE) {
            abort(404);
        }

        if ($lessonSession->starts_at !== null && $lessonSession->starts_at->isPast()) {
            return back()->withInput()->withErrors(['lesson_session_id' => 'この開催枠は予約できません。']);
        }

        $program = $lessonSession->program;

        if (! $program instanceof Program || $program->status !== Program::STATUS_ACTIVE) {
            abort(404);
        }

        if ($this->trialRemaining($lessonSession) < 1) {
            return back()->withInput()->withErrors(['lesson_session_id' => '体験枠が満席です。']);
        }

        if ($this->hasBlockingTrialApplication((int) $user->getKey(), (int) $lessonSession->getKey())) {
            return back()->withInput()->withErrors(['lesson_session_id' => 'この開催枠には既に体験のお申し込みがあります。']);
        }

        $paymentMethod = (string) $validated['payment_method'];

        if ($paymentMethod === TrialApplication::PAYMENT_METHOD_ONSITE) {
            $trial = $onsiteBooking->reserve($user, $lessonSession);

            return redirect()
                ->route('member.dashboard')
                ->with('success', sprintf('体験予約が確定しました（予約番号: %s）。', $trial->reservation?->code ?? '—'));
        }

        if ($program->price < 1) {
            return back()->withInput()->withErrors(['payment_method' => 'カード決済の金額が設定されていません。']);
        }

        $trial = TrialApplication::query()->create([
            'user_id' => $user->getKey(),
            'lesson_session_id' => $lessonSession->getKey(),
            'payment_method' => TrialApplication::PAYMENT_METHOD_CARD,
            'status' => TrialApplication::STATUS_PENDING_PAYMENT,
            'expires_at' => now()->addMinutes(30),
        ]);

        $successUrl = route('booking.trial.pending', [], true).'?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = route('booking.trial.show', $lessonSession, true);

        return $checkoutSessionService->redirectToCheckout($trial, $successUrl, $cancelUrl);
    }

    /**
     * Stripe 成功 URL 復帰後: Webhook 完了待ち画面。
     */
    public function pending(Request $request): View
    {
        $sessionId = $request->query('session_id');

        if (! is_string($sessionId) || trim($sessionId) === '') {
            return view('pages.booking.trial.pending', [
                'loadError' => '決済セッションが指定されていません。',
            ]);
        }

        $trial = TrialApplication::query()
            ->where('stripe_checkout_session_id', $sessionId)
            ->where('user_id', $request->user()?->getKey())
            ->with(['reservation', 'lessonSession.program'])
            ->first();

        if (! $trial instanceof TrialApplication) {
            return view('pages.booking.trial.pending', [
                'loadError' => 'お申し込みが見つかりません。マイページで状態をご確認ください。',
            ]);
        }

        $pollStatusUrl = route('booking.trial.payment.status', ['session_id' => $sessionId]);

        return view('pages.booking.trial.pending', [
            'trialApplication' => $trial,
            'bookingPendingXData' => 'bookingPaymentPending('.(string) Js::from($pollStatusUrl).')',
        ]);
    }

    /**
     * 体験カード決済後のステータスポーリング用 JSON。
     */
    public function paymentStatus(Request $request): JsonResponse
    {
        $sessionId = $request->query('session_id');

        if (! is_string($sessionId) || trim($sessionId) === '') {
            return response()->json(['error' => 'session_id が必要です。'], 422);
        }

        $trial = TrialApplication::query()
            ->where('stripe_checkout_session_id', $sessionId)
            ->where('user_id', $request->user()?->getKey())
            ->with('reservation')
            ->first();

        if (! $trial instanceof TrialApplication) {
            return response()->json(['error' => 'not_found'], 404);
        }

        return response()->json([
            'status' => $trial->status,
            'reservation_code' => $trial->reservation?->code,
            'redirect_url' => $trial->status === TrialApplication::STATUS_RESERVED
                ? route('member.dashboard')
                : null,
        ]);
    }

    private function trialRemaining(LessonSession $lessonSession): int
    {
        $rm = $lessonSession->reservationManagement;
        $reserved = $rm?->reserved_trial_count ?? 0;

        return max(0, $lessonSession->trial_capacity - $reserved);
    }

    /**
     * 同一開催枠に進行中の体験申込があるか。
     */
    private function hasBlockingTrialApplication(int $userId, int $lessonSessionId): bool
    {
        return TrialApplication::query()
            ->where('user_id', $userId)
            ->where('lesson_session_id', $lessonSessionId)
            ->whereIn('status', [
                TrialApplication::STATUS_PENDING_PAYMENT,
                TrialApplication::STATUS_PROCESSING,
                TrialApplication::STATUS_RESERVED,
                TrialApplication::STATUS_REFUND_PENDING,
            ])
            ->exists();
    }
}
