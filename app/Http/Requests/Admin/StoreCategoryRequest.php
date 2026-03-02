<?php

namespace App\Http\Requests\Admin;

use App\Models\Category;
use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:255', 'unique:categories,code'],
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'string', 'in:'.Category::STATUS_ACTIVE.','.Category::STATUS_INACTIVE],
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
            'sort_order.required' => '表示順は必須です。',
            'sort_order.integer' => '表示順は整数で入力してください。',
            'sort_order.min' => '表示順は0以上で入力してください。',
            'status.required' => 'ステータスは必須です。',
            'status.in' => 'ステータスの値が不正です。',
        ];
    }
}
