<?php

namespace App\Http\Requests\Member;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class WithdrawMemberAccountRequest extends FormRequest
{
    /**
     * 会員向け退会のみ許可する（管理者セッションやプロフィール未作成ユーザーを拒否）。
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
            'withdrawal_confirmed' => ['required', 'accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'current_password' => '現在のパスワード',
            'withdrawal_confirmed' => '退会の確認',
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
            'current_password.string' => '現在のパスワードの形式が正しくありません。',
            'withdrawal_confirmed.required' => '退会するには確認にチェックを入れてください。',
            'withdrawal_confirmed.accepted' => '退会するには確認にチェックを入れてください。',
        ];
    }
}
