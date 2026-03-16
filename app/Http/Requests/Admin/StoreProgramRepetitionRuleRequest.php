<?php

namespace App\Http\Requests\Admin;

use App\Models\ProgramRepetitionRule;
use App\Services\ProgramRepetitionRuleSessionCandidateService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreProgramRepetitionRuleRequest extends FormRequest
{
    private const UNSIGNED_INT_MAX = 4294967295;

    protected function prepareForValidation(): void
    {
        $dayOfWeek = $this->input('day_of_week');

        if (! is_string($dayOfWeek)) {
            return;
        }

        $this->merge([
            'day_of_week' => trim($dayOfWeek),
        ]);
    }

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
            'program_id' => ['required', 'exists:programs,id'],
            'location_id' => ['required', 'exists:locations,id'],
            'staff_id' => ['required', 'exists:staffs,id'],
            'cycle_type' => [
                'required',
                'string',
                'in:'.ProgramRepetitionRule::CYCLE_TYPE_DAILY.','.ProgramRepetitionRule::CYCLE_TYPE_WEEKLY,
            ],
            'day_of_week' => ['nullable', 'regex:/^-?\d+$/', 'integer', 'between:0,6'],
            'week_of_month' => ['prohibited'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'start_time' => ['required', 'date_format:H:i:s'],
            'capacity' => ['required', 'integer', 'min:0', 'max:'.self::UNSIGNED_INT_MAX],
            'trial_capacity' => ['required', 'integer', 'min:0', 'max:'.self::UNSIGNED_INT_MAX],
            'status' => [
                'required',
                'string',
                'in:'.ProgramRepetitionRule::STATUS_ACTIVE.','.ProgramRepetitionRule::STATUS_INACTIVE,
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'program_id.required' => 'プログラムは必須です。',
            'program_id.exists' => '選択されたプログラムが見つかりません。',
            'location_id.required' => '店舗は必須です。',
            'location_id.exists' => '選択された店舗が見つかりません。',
            'staff_id.required' => '担当スタッフは必須です。',
            'staff_id.exists' => '選択された担当スタッフが見つかりません。',
            'cycle_type.required' => '繰り返し種別は必須です。',
            'cycle_type.in' => '繰り返し種別の値が不正です。',
            'day_of_week.integer' => '曜日は整数で指定してください。',
            'day_of_week.regex' => '曜日は整数で指定してください。',
            'day_of_week.between' => '曜日は 0 から 6 の範囲で指定してください。',
            'week_of_month.prohibited' => '週番号の指定は PH6-2 では利用できません。',
            'start_date.required' => '開始日は必須です。',
            'start_date.date' => '開始日は日付形式で入力してください。',
            'end_date.required' => '終了日は必須です。',
            'end_date.date' => '終了日は日付形式で入力してください。',
            'end_date.after_or_equal' => '終了日は開始日以降で入力してください。',
            'start_time.required' => '開始時刻は必須です。',
            'start_time.date_format' => '開始時刻は HH:MM:SS 形式で入力してください。',
            'capacity.required' => '通常定員は必須です。',
            'capacity.integer' => '通常定員は整数で入力してください。',
            'capacity.min' => '通常定員は0以上で入力してください。',
            'capacity.max' => '通常定員は4294967295以下で入力してください。',
            'trial_capacity.required' => '体験定員は必須です。',
            'trial_capacity.integer' => '体験定員は整数で入力してください。',
            'trial_capacity.min' => '体験定員は0以上で入力してください。',
            'trial_capacity.max' => '体験定員は4294967295以下で入力してください。',
            'status.required' => 'ステータスは必須です。',
            'status.in' => 'ステータスの値が不正です。',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $cycleType = $this->input('cycle_type');

            if (! in_array($cycleType, [
                ProgramRepetitionRule::CYCLE_TYPE_DAILY,
                ProgramRepetitionRule::CYCLE_TYPE_WEEKLY,
            ], true)) {
                return;
            }

            if (
                $cycleType === ProgramRepetitionRule::CYCLE_TYPE_DAILY
                && $this->filled('day_of_week')
            ) {
                $validator->errors()->add('day_of_week', '繰り返し種別が毎日の場合は曜日を指定できません。');
            }

            if (
                $cycleType === ProgramRepetitionRule::CYCLE_TYPE_WEEKLY
                && ! $this->filled('day_of_week')
            ) {
                $validator->errors()->add('day_of_week', '週次ルールでは曜日が必須です。');
            }

            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $candidateService = app(ProgramRepetitionRuleSessionCandidateService::class);
            $candidateCount = $candidateService->candidateCount(
                new ProgramRepetitionRule([
                    'cycle_type' => $cycleType,
                    'day_of_week' => $this->input('day_of_week'),
                    'week_of_month' => $this->input('week_of_month'),
                    'start_date' => $this->input('start_date'),
                    'end_date' => $this->input('end_date'),
                    'start_time' => $this->input('start_time'),
                ])
            );

            if ($candidateCount > ProgramRepetitionRuleSessionCandidateService::MAX_GENERATION_CANDIDATES) {
                $validator->errors()->add(
                    'end_date',
                    sprintf(
                        '生成対象は %d 件以内にしてください。',
                        ProgramRepetitionRuleSessionCandidateService::MAX_GENERATION_CANDIDATES
                    )
                );
            }
        });
    }
}
