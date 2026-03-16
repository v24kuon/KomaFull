<?php

namespace App\Http\Requests\Admin;

use App\Models\ProgramRepetitionRule;

class UpdateProgramRepetitionRuleRequest extends StoreProgramRepetitionRuleRequest
{
    /**
     * Normalize update-only daily payloads before the shared repetition-rule validator runs.
     *
     * Preconditions: edit submissions reuse the store request validation, and switching an existing weekly rule to daily
     * can leave a stale `day_of_week` in the request payload.
     * Update policy: only the update path nulls `day_of_week` for daily payloads so weekly→daily edits succeed without
     * broadening the store path, which should continue rejecting tampered daily weekday input.
     */
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
