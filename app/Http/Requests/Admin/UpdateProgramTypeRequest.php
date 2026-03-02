<?php

namespace App\Http\Requests\Admin;

use App\Models\ProgramType;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProgramTypeRequest extends FormRequest
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
        $id = $this->route('program_type')?->id;

        return [
            'code' => ['required', 'string', 'max:255', 'unique:program_types,code,'.$id],
            'name' => ['required', 'string', 'max:255'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'status' => ['required', 'string', 'in:'.ProgramType::STATUS_ACTIVE.','.ProgramType::STATUS_INACTIVE],
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
            'status.required' => 'ステータスは必須です。',
        ];
    }
}
