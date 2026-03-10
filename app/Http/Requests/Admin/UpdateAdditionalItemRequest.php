<?php

namespace App\Http\Requests\Admin;

use App\Models\AdditionalItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAdditionalItemRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:255', Rule::unique('additional_items', 'code')->ignore($this->route('additional_item'))],
            'additional_item_type' => ['required', 'string', 'in:member_profile'],
            'label_name' => ['required', 'string', 'max:255'],
            'input_type' => ['required', 'string', 'in:text,number,select,checkbox'],
            'digits' => ['nullable', 'integer', 'min:1'],
            'status' => ['required', 'string', 'in:'.AdditionalItem::STATUS_ACTIVE.','.AdditionalItem::STATUS_INACTIVE],
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
            'additional_item_type.required' => '項目種別は必須です。',
            'label_name.required' => 'ラベル名は必須です。',
            'input_type.required' => '入力形式は必須です。',
            'status.required' => 'ステータスは必須です。',
        ];
    }
}
