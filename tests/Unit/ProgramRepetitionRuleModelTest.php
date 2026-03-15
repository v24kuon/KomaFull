<?php

namespace Tests\Unit;

use App\Models\Location;
use App\Models\Program;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class ProgramRepetitionRuleModelTest extends TestCase
{
    private const MODEL_CLASS = \App\Models\ProgramRepetitionRule::class;

    public function test_program_repetition_rule_model_exists(): void
    {
        $this->assertTrue(class_exists(self::MODEL_CLASS));
    }

    public function test_program_repetition_rule_has_expected_constants_casts_and_relations(): void
    {
        $modelClass = $this->programRepetitionRuleModelClass();
        $rule = new $modelClass;
        $casts = $rule->getCasts();

        $this->assertSame('daily', $modelClass::CYCLE_TYPE_DAILY);
        $this->assertSame('weekly', $modelClass::CYCLE_TYPE_WEEKLY);
        $this->assertSame('active', $modelClass::STATUS_ACTIVE);
        $this->assertSame('inactive', $modelClass::STATUS_INACTIVE);
        $this->assertArrayHasKey('day_of_week', $casts);
        $this->assertSame('integer', $casts['day_of_week']);
        $this->assertArrayHasKey('week_of_month', $casts);
        $this->assertSame('integer', $casts['week_of_month']);
        $this->assertArrayHasKey('start_date', $casts);
        $this->assertSame('date', $casts['start_date']);
        $this->assertArrayHasKey('end_date', $casts);
        $this->assertSame('date', $casts['end_date']);
        $this->assertArrayHasKey('capacity', $casts);
        $this->assertSame('integer', $casts['capacity']);
        $this->assertArrayHasKey('trial_capacity', $casts);
        $this->assertSame('integer', $casts['trial_capacity']);
        $this->assertInstanceOf(BelongsTo::class, $rule->program());
        $this->assertSame('program_id', $rule->program()->getForeignKeyName());
        $this->assertInstanceOf(BelongsTo::class, $rule->location());
        $this->assertSame('location_id', $rule->location()->getForeignKeyName());
        $this->assertInstanceOf(BelongsTo::class, $rule->staff());
        $this->assertSame('staff_id', $rule->staff()->getForeignKeyName());
    }

    public function test_program_has_program_repetition_rules_relation(): void
    {
        $this->assertTrue(method_exists(Program::class, 'programRepetitionRules'));

        $program = new Program;
        $relation = $program->programRepetitionRules();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertSame('program_id', $relation->getForeignKeyName());
    }

    public function test_location_has_program_repetition_rules_relation(): void
    {
        $this->assertTrue(method_exists(Location::class, 'programRepetitionRules'));

        $location = new Location;
        $relation = $location->programRepetitionRules();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertSame('location_id', $relation->getForeignKeyName());
    }

    public function test_staff_has_program_repetition_rules_relation(): void
    {
        $this->assertTrue(method_exists(Staff::class, 'programRepetitionRules'));

        $staff = new Staff;
        $relation = $staff->programRepetitionRules();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertSame('staff_id', $relation->getForeignKeyName());
    }

    private function programRepetitionRuleModelClass(): string
    {
        $this->assertTrue(class_exists(self::MODEL_CLASS), 'ProgramRepetitionRule model must exist before model structure can be verified.');

        return self::MODEL_CLASS;
    }
}
