<?php

namespace App\Http\Requests\Member;

use App\Models\User;
use App\Services\Member\MemberSubscriptionManagementService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CancelMemberSubscriptionRequest extends FormRequest
{
    /**
     * サブスク管理画面は複数フォームを同時表示するため、default バッグと重複表示しない。
     */
    protected $errorBag = 'cancel';

    /**
     * 会員向け解約予約のみ許可する。
     */
    public function authorize(): bool
    {
        $user = $this->user();

        return $user instanceof User
            && $user->getAttribute('role') === User::ROLE_MEMBER
            && $user->memberProfile !== null;
    }

    /**
     * @return array<string, array<int, \Illuminate\Contracts\Validation\ValidationRule|string>>
     */
    public function rules(): array
    {
        return [
            'cancellation_confirmed' => ['required', 'accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'cancellation_confirmed.required' => '解約の内容を確認し、チェックを入れてください。',
            'cancellation_confirmed.accepted' => '解約の内容を確認し、チェックを入れてください。',
        ];
    }

    /**
     * 解約予約可能なサブスクかを検証する（{@see SwapMemberSubscriptionRequest} と同様に after で状態を検証）。
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();
            if (! $user instanceof User) {
                return;
            }

            $subscription = $user->subscription('default');
            $management = app(MemberSubscriptionManagementService::class);

            if ($subscription === null || ! $management->canCancelAtPeriodEnd($subscription)) {
                $validator->errors()->add(
                    'cancellation_confirmed',
                    '現在、請求期間末での解約を予約できるサブスクリプションがありません。'
                );
            }
        });
    }
}
