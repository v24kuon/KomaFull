<?php

namespace App\Http\Requests\Member;

use App\Actions\Fortify\PasswordValidationRules;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMemberPasswordSettingsRequest extends FormRequest
{
    use PasswordValidationRules;

    /**
     * 会員向けパスワード変更のみ許可する（管理者セッションやプロフィール未作成ユーザーを拒否）。
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
        return [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => $this->passwordRules(),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'current_password' => '現在のパスワード',
            'password' => '新しいパスワード',
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
            'password.required' => '新しいパスワードを入力してください。',
            'password.string' => '新しいパスワードの形式が正しくありません。',
            'password.min' => '新しいパスワードは:min文字以上で入力してください。',
            'password.confirmed' => '新しいパスワード（確認）が一致しません。',
        ];
    }
}
