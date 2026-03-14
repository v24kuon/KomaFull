<?php

namespace Tests\Feature;

use App\Models\Location;
use App\Models\Program;
use App\Models\Staff;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class ProgramRepetitionRuleFoundationTest extends TestCase
{
    use RefreshDatabase;

    private const MODEL_CLASS = \App\Models\ProgramRepetitionRule::class;

    private Program $program;

    private Location $location;

    private Staff $staff;

    protected function setUp(): void
    {
        parent::setUp();

        $this->program = Program::factory()->createOne();
        $this->location = Location::factory()->createOne();
        $this->staff = Staff::factory()->createOne();
    }

    public function test_daily_rule_can_be_created(): void
    {
        $rule = $this->createRule();

        $this->assertDatabaseHas('program_repetition_rules', [
            'id' => $rule->id,
            'cycle_type' => 'daily',
            'day_of_week' => null,
        ]);
        $this->assertSame('2026-03-31', $rule->fresh()->end_date->format('Y-m-d'));
    }

    public function test_weekly_rule_can_be_created_with_valid_day_of_week(): void
    {
        $rule = $this->createRule([
            'cycle_type' => 'weekly',
            'day_of_week' => 1,
        ]);

        $this->assertDatabaseHas('program_repetition_rules', [
            'id' => $rule->id,
            'cycle_type' => 'weekly',
            'day_of_week' => 1,
        ]);
    }

    public function test_weekly_rule_accepts_day_of_week_zero(): void
    {
        $rule = $this->createRule([
            'cycle_type' => 'weekly',
            'day_of_week' => 0,
        ]);

        $this->assertDatabaseHas('program_repetition_rules', [
            'id' => $rule->id,
            'day_of_week' => 0,
        ]);
    }

    public function test_weekly_rule_accepts_day_of_week_six(): void
    {
        $rule = $this->createRule([
            'cycle_type' => 'weekly',
            'day_of_week' => 6,
        ]);

        $this->assertDatabaseHas('program_repetition_rules', [
            'id' => $rule->id,
            'day_of_week' => 6,
        ]);
    }

    public function test_invalid_cycle_type_is_rejected(): void
    {
        $this->expectException(QueryException::class);

        $this->createRule(['cycle_type' => 'monthly']);
    }

    public function test_empty_cycle_type_is_rejected(): void
    {
        $this->expectException(QueryException::class);

        $this->createRule(['cycle_type' => '']);
    }

    public function test_null_cycle_type_is_rejected(): void
    {
        $this->expectException(QueryException::class);

        $this->createRule(['cycle_type' => null]);
    }

    public function test_weekly_rule_requires_day_of_week(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createRule([
            'cycle_type' => 'weekly',
            'day_of_week' => null,
        ]);
    }

    public function test_daily_rule_prohibits_day_of_week(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createRule([
            'cycle_type' => 'daily',
            'day_of_week' => 1,
        ]);
    }

    public function test_weekly_rule_rejects_day_of_week_below_zero(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createRule([
            'cycle_type' => 'weekly',
            'day_of_week' => -1,
        ]);
    }

    public function test_weekly_rule_rejects_day_of_week_above_six(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createRule([
            'cycle_type' => 'weekly',
            'day_of_week' => 7,
        ]);
    }

    public function test_supported_rules_reject_week_of_month(): void
    {
        $this->expectException(InvalidArgumentException::class);

        $this->createRule([
            'cycle_type' => 'weekly',
            'day_of_week' => 1,
            'week_of_month' => 1,
        ]);
    }

    public function test_end_date_is_required(): void
    {
        $this->expectException(QueryException::class);

        $this->createRule(['end_date' => null]);
    }

    public function test_weekday_invariant_is_enforced_even_when_model_events_are_disabled(): void
    {
        $this->expectException(QueryException::class);

        $this->createRuleWithoutEvents([
            'cycle_type' => 'daily',
            'day_of_week' => 1,
        ]);
    }

    public function test_weekly_requires_day_of_week_even_when_model_events_are_disabled(): void
    {
        $this->expectException(QueryException::class);

        $this->createRuleWithoutEvents([
            'cycle_type' => 'weekly',
            'day_of_week' => null,
        ]);
    }

    public function test_week_of_month_is_enforced_even_when_model_events_are_disabled(): void
    {
        $this->expectException(QueryException::class);

        $this->createRuleWithoutEvents([
            'cycle_type' => 'weekly',
            'day_of_week' => 1,
            'week_of_month' => 1,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createRule(array $overrides = []): object
    {
        $modelClass = $this->programRepetitionRuleModelClass();

        return $modelClass::query()->create(array_merge($this->validAttributes(), $overrides));
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function createRuleWithoutEvents(array $overrides = []): object
    {
        $modelClass = $this->programRepetitionRuleModelClass();

        return $modelClass::withoutEvents(
            fn (): object => $modelClass::query()->create(array_merge($this->validAttributes(), $overrides))
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function validAttributes(): array
    {
        return [
            'program_id' => $this->program->id,
            'location_id' => $this->location->id,
            'staff_id' => $this->staff->id,
            'cycle_type' => 'daily',
            'day_of_week' => null,
            'week_of_month' => null,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'start_time' => '10:00:00',
            'capacity' => 12,
            'trial_capacity' => 2,
            'status' => 'active',
        ];
    }

    private function programRepetitionRuleModelClass(): string
    {
        $this->assertTrue(class_exists(self::MODEL_CLASS), 'ProgramRepetitionRule model must exist before foundation behavior can be verified.');

        return self::MODEL_CLASS;
    }
}
