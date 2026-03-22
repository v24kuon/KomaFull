<?php

namespace App\Http\Requests\Member;

use App\Models\AdditionalItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateMemberProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }

    /**
     * @return array<string, array<int, \Illuminate\Contracts\Validation\ValidationRule|string>>
     */
    public function rules(): array
    {
        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'tel' => ['nullable', 'string', 'max:32'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
        ];

        foreach ($this->activeAdditionalItems() as $item) {
            $key = 'additional_items.'.$item->id;
            $rules[$key] = $this->rulesForAdditionalItem($item);
        }

        return $rules;
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $attributes = [];

        foreach ($this->activeAdditionalItems() as $item) {
            $attributes['additional_items.'.$item->id] = $item->label_name;
        }

        return $attributes;
    }

    protected function prepareForValidation(): void
    {
        $additional = $this->input('additional_items', []);
        if (! is_array($additional)) {
            $additional = [];
        }

        foreach ($this->activeAdditionalItems() as $item) {
            if ($item->input_type !== 'checkbox') {
                continue;
            }

            $idKey = (string) $item->id;
            if (! array_key_exists($item->id, $additional) && ! array_key_exists($idKey, $additional)) {
                $additional[$item->id] = false;
            }
        }

        $this->merge([
            'additional_items' => $additional,
        ]);
    }

    /**
     * @return array<int, \Illuminate\Contracts\Validation\ValidationRule|string>
     */
    private function rulesForAdditionalItem(AdditionalItem $item): array
    {
        return match ($item->input_type) {
            'text' => [
                'nullable',
                'string',
                'max:'.($item->digits !== null ? max(1, min(65535, $item->digits)) : 255),
            ],
            'number' => $this->numberRules($item),
            'select' => $this->selectRules($item),
            'checkbox' => ['boolean'],
            default => ['nullable', 'string', 'max:255'],
        };
    }

    /**
     * @return array<int, \Illuminate\Contracts\Validation\ValidationRule|string>
     */
    private function numberRules(AdditionalItem $item): array
    {
        $digits = $item->digits;
        if ($digits === null || $digits < 1) {
            return ['nullable', 'integer', 'min:0'];
        }

        $max = min(PHP_INT_MAX, (10 ** $digits) - 1);

        return ['nullable', 'integer', 'min:0', 'max:'.$max];
    }

    /**
     * @return array<int, \Illuminate\Contracts\Validation\ValidationRule|string>
     */
    private function selectRules(AdditionalItem $item): array
    {
        $opts = $item->select_options;
        if (is_array($opts) && $opts !== []) {
            $flat = array_map(static fn (mixed $v): string => is_string($v) ? $v : (string) $v, $opts);

            return ['nullable', 'string', Rule::in($flat)];
        }

        return ['nullable', 'string', 'max:255'];
    }

    /**
     * @return \Illuminate\Support\Collection<int, AdditionalItem>
     */
    private function activeAdditionalItems()
    {
        return AdditionalItem::query()
            ->where('additional_item_type', 'member_profile')
            ->where('status', AdditionalItem::STATUS_ACTIVE)
            ->orderBy('id')
            ->get();
    }
}
