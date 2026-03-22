<?php

namespace App\Http\Requests\Member;

use App\Models\User;
use App\Services\Member\MemberSubscriptionManagementService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ResumeMemberSubscriptionRequest extends FormRequest
{
    /**
     * サブスク管理画面は複数フォームを同時表示するため、default バッグと重複表示しない。
     */
    protected $errorBag = 'resume';

    /**
     * 会員向け解約取り消しのみ許可する。
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
            'resume_confirmed' => ['required', 'accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'resume_confirmed.required' => '内容を確認し、チェックを入れてください。',
            'resume_confirmed.accepted' => '内容を確認し、チェックを入れてください。',
        ];
    }

    /**
     * 解約取り消し可能（猶予期間内）かを検証する（{@see SwapMemberSubscriptionRequest} と同様に after で状態を検証）。
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

            if ($subscription === null || ! $management->canResume($subscription)) {
                $validator->errors()->add(
                    'resume_confirmed',
                    '現在、解約の取り消しができる状態ではありません。'
                );
            }
        });
    }
}
