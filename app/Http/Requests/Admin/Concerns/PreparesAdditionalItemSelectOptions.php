<?php

namespace App\Http\Requests\Admin\Concerns;

/**
 * 追加項目マスタの select_options_lines（改行区切り）を select_options 配列へ正規化する。
 *
 * input_type が select 以外のときは select_options を null にし、非表示フィールド由来の送信を無視する。
 */
trait PreparesAdditionalItemSelectOptions
{
    protected function prepareForValidation(): void
    {
        if ($this->input('input_type') !== 'select') {
            $this->merge(['select_options' => null]);

            return;
        }

        $lines = $this->input('select_options_lines');
        if (! is_string($lines)) {
            $this->merge(['select_options' => null]);

            return;
        }

        $trimmed = trim($lines);
        if ($trimmed === '') {
            $this->merge(['select_options' => null]);

            return;
        }

        $trimmedLines = array_map('trim', preg_split('/\r\n|\r|\n/', $lines));
        $arr = array_values(array_filter($trimmedLines, static fn (string $value): bool => $value !== ''));
        $this->merge(['select_options' => $arr]);
    }
}
