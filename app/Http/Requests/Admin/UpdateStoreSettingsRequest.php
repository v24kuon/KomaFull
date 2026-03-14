<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStoreSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'program_label' => ['required', 'string', 'max:255'],
            'session_label' => ['required', 'string', 'max:255'],
            'staff_label' => ['required', 'string', 'max:255'],
            'location_label' => ['required', 'string', 'max:255'],
            'reserve_deadline_minutes' => ['required', 'integer', 'min:0'],
            'cancel_deadline_minutes' => ['required', 'integer', 'min:0'],
            'withdrawal_deadline_days' => ['required', 'integer', 'min:0'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'program_label.required' => 'プログラム表記は必須です。',
            'session_label.required' => 'セッション表記は必須です。',
            'staff_label.required' => 'スタッフ表記は必須です。',
            'location_label.required' => '店舗表記は必須です。',
            'reserve_deadline_minutes.required' => '予約締切は必須です。',
            'cancel_deadline_minutes.required' => 'キャンセル締切は必須です。',
            'withdrawal_deadline_days.required' => '退会猶予日数は必須です。',
        ];
    }
}
