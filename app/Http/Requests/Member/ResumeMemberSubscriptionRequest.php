<?php

namespace App\Http\Requests\Member;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class ResumeMemberSubscriptionRequest extends FormRequest
{
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
            'resume_confirmed.accepted' => '内容を確認し、チェックを入れてください。',
        ];
    }
}
