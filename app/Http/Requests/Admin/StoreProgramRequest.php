<?php

namespace App\Http\Requests\Admin;

use App\Models\Program;
use Illuminate\Foundation\Http\FormRequest;

class StoreProgramRequest extends FormRequest
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
            'code' => ['required', 'string', 'max:255', 'unique:programs,code'],
            'category_id' => ['required', 'exists:categories,id'],
            'program_type_id' => ['required', 'exists:program_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'level' => ['nullable', 'string', 'max:255'],
            'duration_minutes' => ['required', 'integer', 'min:1'],
            'overview' => ['nullable', 'string'],
            'detail' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'point_cost' => ['required', 'integer', 'min:0'],
            'ticket_cost' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'string', 'in:'.Program::STATUS_ACTIVE.','.Program::STATUS_INACTIVE],
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
            'category_id.required' => 'カテゴリは必須です。',
            'category_id.exists' => '選択されたカテゴリが見つかりません。',
            'program_type_id.required' => 'プログラム種別は必須です。',
            'program_type_id.exists' => '選択されたプログラム種別が見つかりません。',
            'name.required' => '名前は必須です。',
            'duration_minutes.required' => '時間（分）は必須です。',
            'price.required' => '料金は必須です。',
            'point_cost.required' => 'ポイントコストは必須です。',
            'ticket_cost.required' => '回数券コストは必須です。',
            'status.required' => 'ステータスは必須です。',
        ];
    }
}
