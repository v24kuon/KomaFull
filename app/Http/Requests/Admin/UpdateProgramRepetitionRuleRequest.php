<?php

namespace App\Http\Requests\Admin;

use App\Models\ProgramRepetitionRule;

class UpdateProgramRepetitionRuleRequest extends StoreProgramRepetitionRuleRequest
{
    protected function prepareForValidation(): void
    {
        parent::prepareForValidation();

        if ($this->input('cycle_type') !== ProgramRepetitionRule::CYCLE_TYPE_DAILY) {
            return;
        }

        $this->merge([
            'day_of_week' => null,
        ]);
    }
}
