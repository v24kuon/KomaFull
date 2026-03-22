<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

class StoreContactRequest extends FormRequest
{
    /**
     * @var list<string>
     */
    public const INQUIRY_TYPES = ['reservation', 'billing', 'account', 'other'];

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string|Rule>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'email:filter', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'inquiry_type' => ['required', 'string', Rule::in(self::INQUIRY_TYPES)],
            'body' => ['required', 'string', 'max:5000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'お名前は必須です。',
            'email.required' => 'メールアドレスは必須です。',
            'email.email' => 'メールアドレスの形式が正しくありません。',
            'inquiry_type.required' => 'お問い合わせ種別を選択してください。',
            'inquiry_type.in' => 'お問い合わせ種別が不正です。',
            'body.required' => 'お問い合わせ内容は必須です。',
        ];
    }

    /**
     * @throws InvalidArgumentException `inquiry_type` が {@see self::INQUIRY_TYPES} に含まれないとき
     */
    public static function labelFor(string $type): string
    {
        return match ($type) {
            'reservation' => '予約・開催枠',
            'billing' => '決済・請求',
            'account' => '会員アカウント',
            'other' => 'その他',
            default => throw new InvalidArgumentException('Unsupported inquiry_type: '.$type),
        };
    }
}
