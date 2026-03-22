<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ScheduleIndexRequest extends FormRequest
{
    public const MIN_YEAR = 2000;

    public const MAX_YEAR = 2100;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * クエリ未指定時は現在の年月を補い、ルールを単純化する。
     *
     * `Carbon::now()` は 1 回だけ呼び、`config('app.timezone')` は `ScheduleController` / `buildCalendarWeeks()` と同じくアプリのタイムゾーンで年月を決める（年またぎの境界で year と month が別時点になるのを避ける）。
     */
    protected function prepareForValidation(): void
    {
        $now = Carbon::now(config('app.timezone'));

        $this->merge([
            'year' => $this->query('year', $now->year),
            'month' => $this->query('month', $now->month),
        ]);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'year' => ['required', 'integer', 'min:'.self::MIN_YEAR, 'max:'.self::MAX_YEAR],
            'month' => ['required', 'integer', 'min:1', 'max:12'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'year.required' => '年を指定してください。',
            'year.integer' => '年は整数で指定してください。',
            'year.min' => '年は2000年から2100年の範囲で指定してください。',
            'year.max' => '年は2000年から2100年の範囲で指定してください。',
            'month.required' => '月を指定してください。',
            'month.integer' => '月は整数で指定してください。',
            'month.min' => '月は1から12の範囲で指定してください。',
            'month.max' => '月は1から12の範囲で指定してください。',
        ];
    }

    /**
     * API / XHR 等で JSON を期待するリクエストだけカスタム 422 JSON を返す。
     *
     * 公開 HTML（ブラウザの通常 GET）では `expectsJson()` が偽のため親の `ValidationException` に委譲し、リダイレクトとセッションエラーで扱う（生 JSON を表示しない）。
     */
    protected function failedValidation(Validator $validator): void
    {
        if ($this->expectsJson()) {
            throw new HttpResponseException(
                response()->json([
                    'message' => '開催枠カレンダーの表示条件が不正です。',
                    'errors' => $validator->errors(),
                ], 422)
            );
        }

        parent::failedValidation($validator);
    }
}
