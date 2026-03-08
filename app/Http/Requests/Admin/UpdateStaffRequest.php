<?php

namespace App\Http\Requests\Admin;

use App\Models\Staff;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStaffRequest extends FormRequest
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
        $id = $this->route('staff')?->id;

        return [
            'code' => ['required', 'string', 'max:255', 'unique:staffs,code,'.$id],
            'name' => ['required', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'in:male,female,other'],
            'birth_date' => ['nullable', 'date'],
            'licence_skill' => ['nullable', 'string', 'max:255'],
            'main_expertise' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'status' => ['required', 'string', 'in:'.Staff::STATUS_ACTIVE.','.Staff::STATUS_INACTIVE],
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
            'gender.in' => '性別の値が不正です。',
            'status.required' => 'ステータスは必須です。',
        ];
    }
}
