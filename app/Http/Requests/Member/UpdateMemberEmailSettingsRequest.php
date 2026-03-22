<?php

namespace App\Http\Requests\Member;

use App\Models\User;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMemberEmailSettingsRequest extends FormRequest
{
    /**
     * 会員向けメール変更のみ許可する（管理者セッションやプロフィール未作成ユーザーを拒否）。
     *
     * {@see EnsureMemberNotWithdrawn} は管理者を対象外とするため、ルート単体では会員専用を保証できない。
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
        /** @var User $user */
        $user = $this->user();

        return [
            'current_password' => ['required', 'string', 'current_password:web'],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
                function (string $attribute, mixed $value, Closure $fail) use ($user): void {
                    if (is_string($value) && $value === $user->email) {
                        $fail('メールアドレスが変更されていません。');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'current_password' => '現在のパスワード',
            'email' => 'メールアドレス',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'current_password.required' => '現在のパスワードを入力してください。',
            'current_password.current_password' => '現在のパスワードが正しくありません。',
            'email.required' => 'メールアドレスを入力してください。',
            'email.email' => 'メールアドレスの形式が正しくありません。',
            'email.max' => 'メールアドレスは:max文字以内で入力してください。',
            'email.unique' => 'このメールアドレスは既に使用されています。',
        ];
    }
}
