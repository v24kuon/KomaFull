<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTrialBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'lesson_session_id' => ['required', 'integer', 'exists:lesson_sessions,id'],
            'payment_method' => ['required', 'string', Rule::in(['card', 'onsite'])],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lesson_session_id.required' => '開催枠を指定してください。',
            'payment_method.in' => '支払い方法が不正です。',
        ];
    }
}
