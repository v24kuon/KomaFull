<?php

namespace App\Http\Requests\Admin;

use App\Models\Location;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, \Illuminate\Validation\Rules\Unique|string>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:255', Rule::unique('locations', 'code')->ignore($this->route('location'))],
            'name' => ['required', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:255'],
            'tel' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:'.Location::STATUS_ACTIVE.','.Location::STATUS_INACTIVE],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'コードは必須です。',
            'code.unique' => 'このコードは既に使用されています。',
            'name.required' => '名前は必須です。',
            'status.required' => 'ステータスは必須です。',
        ];
    }
}
