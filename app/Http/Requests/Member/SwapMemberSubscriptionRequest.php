<?php

namespace App\Http\Requests\Member;

use App\Models\CoursePlan;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SwapMemberSubscriptionRequest extends FormRequest
{
    /**
     * 会員向けプラン変更のみ許可する。
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
            'stripe_price_id' => [
                'required',
                'string',
                Rule::exists('course_plans', 'stripe_price_id')->where(
                    fn ($query) => $query->where('status', CoursePlan::STATUS_ACTIVE)->whereNotNull('stripe_price_id')
                ),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'stripe_price_id.required' => '変更先のプランを選択してください。',
            'stripe_price_id.exists' => '選択したプランは現在ご利用できません。',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $user = $this->user();
            if (! $user instanceof User) {
                return;
            }

            $subscription = $user->subscription('default');
            if ($subscription === null) {
                $validator->errors()->add('stripe_price_id', '有効なサブスクリプションが見つかりません。');

                return;
            }

            $priceId = $this->input('stripe_price_id');
            if (! is_string($priceId) || $priceId === '') {
                return;
            }

            if ($subscription->hasPrice($priceId)) {
                $validator->errors()->add('stripe_price_id', 'すでに同じプランです。');
            }
        });
    }
}
