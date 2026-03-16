<?php

namespace Tests\Feature\Admin;

use App\Models\Location;
use App\Models\Program;
use App\Models\ProgramRepetitionRule;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProgramRepetitionRuleCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        /** @var User $admin */
        $admin = User::factory()->createOne(['role' => User::ROLE_ADMIN]);
        $this->admin = $admin;
    }

    public function test_index_displays_rules_with_related_labels_and_generate_action(): void
    {
        $rule = ProgramRepetitionRule::factory()->weekly(2)->createOne([
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'start_time' => '10:15:00',
            'capacity' => 12,
            'trial_capacity' => 2,
            'status' => ProgramRepetitionRule::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.program-repetition-rules.index'));

        $response->assertOk();
        $response->assertSeeText('繰り返し設定管理');
        $response->assertSeeText($rule->program->name);
        $response->assertSeeText($rule->location->name);
        $response->assertSeeText($rule->staff->name);
        $response->assertSeeText('1件生成');
        $response->assertSee(route('admin.program-repetition-rules.generate', $rule), false);
    }

    public function test_index_returns_partial_for_htmx(): void
    {
        $rule = ProgramRepetitionRule::factory()->createOne();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.program-repetition-rules.index'), ['HX-Request' => 'true']);

        $response->assertOk();
        $response->assertDontSee('<!DOCTYPE html>', false);
        $response->assertSeeText($rule->program->name);
    }

    public function test_create_form_is_displayed_without_week_of_month_field(): void
    {
        $program = Program::factory()->createOne();
        $location = Location::factory()->createOne();
        $staff = Staff::factory()->createOne();

        $response = $this->actingAs($this->admin)->get(route('admin.program-repetition-rules.create'));

        $response->assertOk();
        $response->assertSeeText('繰り返し設定作成');
        $response->assertSeeText($program->name);
        $response->assertSeeText($location->name);
        $response->assertSeeText($staff->name);
        $response->assertDontSee('week_of_month', false);
    }

    public function test_store_creates_daily_rule(): void
    {
        $payload = $this->validDailyPayload();

        $response = $this->actingAs($this->admin)
            ->post(route('admin.program-repetition-rules.store'), $payload);

        $response->assertRedirect(route('admin.program-repetition-rules.index'));
        $this->assertDatabaseHas('program_repetition_rules', [
            'program_id' => $payload['program_id'],
            'location_id' => $payload['location_id'],
            'staff_id' => $payload['staff_id'],
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_DAILY,
            'day_of_week' => null,
            'week_of_month' => null,
            'start_time' => $payload['start_time'],
            'capacity' => $payload['capacity'],
            'trial_capacity' => $payload['trial_capacity'],
            'status' => $payload['status'],
        ]);

        $storedRule = ProgramRepetitionRule::query()->sole();
        $this->assertSame($payload['start_date'], $storedRule->start_date->format('Y-m-d'));
        $this->assertSame($payload['end_date'], $storedRule->end_date->format('Y-m-d'));
    }

    public function test_store_creates_weekly_rule_with_boundary_day_of_week_values(): void
    {
        foreach ([0, 6] as $dayOfWeek) {
            $payload = $this->validWeeklyPayload(['day_of_week' => $dayOfWeek]);

            $response = $this->actingAs($this->admin)
                ->post(route('admin.program-repetition-rules.store'), $payload);

            $response->assertRedirect(route('admin.program-repetition-rules.index'));
            $this->assertDatabaseHas('program_repetition_rules', [
                'program_id' => $payload['program_id'],
                'location_id' => $payload['location_id'],
                'staff_id' => $payload['staff_id'],
                'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_WEEKLY,
                'day_of_week' => $dayOfWeek,
            ]);
        }
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)
            ->post(route('admin.program-repetition-rules.store'), []);

        $response->assertSessionHasErrors([
            'program_id',
            'location_id',
            'staff_id',
            'cycle_type',
            'start_date',
            'end_date',
            'start_time',
            'capacity',
            'trial_capacity',
            'status',
        ]);
    }

    public function test_validation_errors_are_rendered_with_alert_role(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('admin.program-repetition-rules.create'))
            ->followingRedirects()
            ->post(route('admin.program-repetition-rules.store'), []);

        $response->assertOk();
        $response->assertSee('class="alert alert-danger" role="alert"', false);
        $response->assertSee('class="invalid-feedback" role="alert"', false);
    }

    public function test_store_rejects_invalid_foreign_keys_enums_and_tampered_week_of_month(): void
    {
        $payload = $this->validDailyPayload([
            'program_id' => 999999,
            'location_id' => 999999,
            'staff_id' => 999999,
            'cycle_type' => 'monthly',
            'status' => 'invalid',
            'week_of_month' => 2,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.program-repetition-rules.store'), $payload);

        $response->assertSessionHasErrors([
            'program_id',
            'location_id',
            'staff_id',
            'cycle_type',
            'status',
            'week_of_month',
        ]);
        $this->assertDatabaseCount('program_repetition_rules', 0);
    }

    public function test_store_rejects_weekly_rule_without_day_of_week(): void
    {
        $payload = $this->validWeeklyPayload(['day_of_week' => null]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.program-repetition-rules.store'), $payload);

        $response->assertSessionHasErrors(['day_of_week']);
        $this->assertDatabaseCount('program_repetition_rules', 0);
    }

    public function test_store_rejects_out_of_range_weekly_day_of_week(): void
    {
        foreach ([-1, 7] as $dayOfWeek) {
            $payload = $this->validWeeklyPayload(['day_of_week' => $dayOfWeek]);

            $response = $this->actingAs($this->admin)
                ->post(route('admin.program-repetition-rules.store'), $payload);

            $response->assertSessionHasErrors(['day_of_week']);
        }

        $this->assertDatabaseCount('program_repetition_rules', 0);
    }

    public function test_store_rejects_non_canonical_weekly_day_of_week_input(): void
    {
        $payload = $this->validWeeklyPayload(['day_of_week' => '+1']);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.program-repetition-rules.store'), $payload);

        $response->assertSessionHasErrors(['day_of_week']);
        $this->assertDatabaseCount('program_repetition_rules', 0);
    }

    public function test_store_rejects_day_of_week_for_daily_rules(): void
    {
        $payload = $this->validDailyPayload(['day_of_week' => 3]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.program-repetition-rules.store'), $payload);

        $response->assertSessionHasErrors(['day_of_week']);
        $this->assertDatabaseCount('program_repetition_rules', 0);
    }

    public function test_store_rejects_end_date_before_start_date(): void
    {
        $payload = $this->validDailyPayload([
            'start_date' => '2026-03-10',
            'end_date' => '2026-03-09',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.program-repetition-rules.store'), $payload);

        $response->assertSessionHasErrors(['end_date']);
        $this->assertDatabaseCount('program_repetition_rules', 0);
    }

    public function test_store_rejects_ranges_that_generate_too_many_candidates(): void
    {
        $payload = $this->validDailyPayload([
            'start_date' => '2026-01-01',
            'end_date' => '2027-12-31',
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.program-repetition-rules.store'), $payload);

        $response->assertSessionHasErrors(['end_date']);
        $this->assertDatabaseCount('program_repetition_rules', 0);
    }

    public function test_store_rejects_capacity_values_that_exceed_unsigned_integer_limit(): void
    {
        $payload = $this->validDailyPayload([
            'capacity' => 5000000000,
            'trial_capacity' => 5000000000,
        ]);

        $response = $this->actingAs($this->admin)
            ->post(route('admin.program-repetition-rules.store'), $payload);

        $response->assertSessionHasErrors(['capacity', 'trial_capacity']);
        $this->assertDatabaseCount('program_repetition_rules', 0);
    }

    public function test_edit_form_is_displayed_with_existing_values(): void
    {
        $rule = ProgramRepetitionRule::factory()->weekly(4)->createOne([
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'start_time' => '10:15:00',
            'capacity' => 12,
            'trial_capacity' => 2,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.program-repetition-rules.edit', $rule));

        $response->assertOk();
        $response->assertSeeText('繰り返し設定編集');
        $response->assertSeeText($rule->program->name);
        $response->assertSeeText($rule->location->name);
        $response->assertSeeText($rule->staff->name);
    }

    public function test_update_modifies_rule_and_clears_day_of_week_when_switching_to_daily(): void
    {
        $rule = ProgramRepetitionRule::factory()->weekly(4)->createOne([
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'start_time' => '10:15:00',
            'capacity' => 12,
            'trial_capacity' => 2,
            'status' => ProgramRepetitionRule::STATUS_ACTIVE,
        ]);

        $payload = [
            'program_id' => $rule->program_id,
            'location_id' => $rule->location_id,
            'staff_id' => $rule->staff_id,
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_DAILY,
            'day_of_week' => null,
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-10',
            'start_time' => '11:30:00',
            'capacity' => 18,
            'trial_capacity' => 4,
            'status' => ProgramRepetitionRule::STATUS_INACTIVE,
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.program-repetition-rules.update', $rule), $payload);

        $response->assertRedirect(route('admin.program-repetition-rules.index'));
        $this->assertDatabaseHas('program_repetition_rules', [
            'id' => $rule->id,
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_DAILY,
            'day_of_week' => null,
            'week_of_month' => null,
            'start_time' => '11:30:00',
            'capacity' => 18,
            'trial_capacity' => 4,
            'status' => ProgramRepetitionRule::STATUS_INACTIVE,
        ]);

        $rule->refresh();
        $this->assertSame('2026-04-01', $rule->start_date->format('Y-m-d'));
        $this->assertSame('2026-04-10', $rule->end_date->format('Y-m-d'));
    }

    public function test_update_clears_day_of_week_when_daily_request_omits_the_field(): void
    {
        $rule = ProgramRepetitionRule::factory()->weekly(5)->createOne([
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'start_time' => '10:15:00',
            'capacity' => 12,
            'trial_capacity' => 2,
            'status' => ProgramRepetitionRule::STATUS_ACTIVE,
        ]);

        $payload = [
            'program_id' => $rule->program_id,
            'location_id' => $rule->location_id,
            'staff_id' => $rule->staff_id,
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_DAILY,
            'start_date' => '2026-04-01',
            'end_date' => '2026-04-10',
            'start_time' => '11:30:00',
            'capacity' => 18,
            'trial_capacity' => 4,
            'status' => ProgramRepetitionRule::STATUS_INACTIVE,
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.program-repetition-rules.update', $rule), $payload);

        $response->assertRedirect(route('admin.program-repetition-rules.index'));
        $rule->refresh();
        $this->assertSame(ProgramRepetitionRule::CYCLE_TYPE_DAILY, $rule->cycle_type);
        $this->assertNull($rule->day_of_week);
    }

    public function test_update_rejects_invalid_payload_and_leaves_rule_unchanged(): void
    {
        $rule = ProgramRepetitionRule::factory()->weekly(4)->createOne([
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'start_time' => '10:15:00',
            'capacity' => 12,
            'trial_capacity' => 2,
            'status' => ProgramRepetitionRule::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)
            ->put(route('admin.program-repetition-rules.update', $rule), [
                'program_id' => $rule->program_id,
                'location_id' => $rule->location_id,
                'staff_id' => $rule->staff_id,
                'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_WEEKLY,
                'day_of_week' => 7,
                'start_date' => '2026-03-01',
                'end_date' => '2026-03-31',
                'start_time' => '10:15:00',
                'capacity' => 12,
                'trial_capacity' => 2,
                'status' => ProgramRepetitionRule::STATUS_ACTIVE,
            ]);

        $response->assertSessionHasErrors(['day_of_week']);
        $this->assertDatabaseHas('program_repetition_rules', [
            'id' => $rule->id,
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_WEEKLY,
            'day_of_week' => 4,
            'status' => ProgramRepetitionRule::STATUS_ACTIVE,
        ]);
    }

    public function test_destroy_deletes_rule(): void
    {
        $rule = ProgramRepetitionRule::factory()->createOne();

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.program-repetition-rules.destroy', $rule));

        $response->assertRedirect(route('admin.program-repetition-rules.index'));
        $this->assertDatabaseMissing('program_repetition_rules', ['id' => $rule->id]);
    }

    public function test_destroy_with_htmx_returns_empty(): void
    {
        $rule = ProgramRepetitionRule::factory()->createOne();

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.program-repetition-rules.destroy', $rule), [], ['HX-Request' => 'true']);

        $response->assertOk();
        $this->assertSame('', $response->getContent());
        $this->assertDatabaseMissing('program_repetition_rules', ['id' => $rule->id]);
    }

    public function test_guest_cannot_access_index(): void
    {
        $response = $this->get(route('admin.program-repetition-rules.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_index(): void
    {
        /** @var User $member */
        $member = User::factory()->createOne(['role' => User::ROLE_MEMBER]);

        $response = $this->actingAs($member)
            ->get(route('admin.program-repetition-rules.index'));

        $response->assertForbidden();
    }

    /**
     * @return array{
     *     program_id: int,
     *     location_id: int,
     *     staff_id: int,
     *     cycle_type: string,
     *     day_of_week: int|null,
     *     start_date: string,
     *     end_date: string,
     *     start_time: string,
     *     capacity: int,
     *     trial_capacity: int,
     *     status: string
     * }
     */
    private function validDailyPayload(array $overrides = []): array
    {
        $payload = [
            'program_id' => Program::factory()->createOne()->id,
            'location_id' => Location::factory()->createOne()->id,
            'staff_id' => Staff::factory()->createOne()->id,
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_DAILY,
            'day_of_week' => null,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
            'start_time' => '10:15:00',
            'capacity' => 12,
            'trial_capacity' => 2,
            'status' => ProgramRepetitionRule::STATUS_ACTIVE,
        ];

        return array_merge($payload, $overrides);
    }

    /**
     * @return array{
     *     program_id: int,
     *     location_id: int,
     *     staff_id: int,
     *     cycle_type: string,
     *     day_of_week: int|null,
     *     start_date: string,
     *     end_date: string,
     *     start_time: string,
     *     capacity: int,
     *     trial_capacity: int,
     *     status: string
     * }
     */
    private function validWeeklyPayload(array $overrides = []): array
    {
        return array_merge($this->validDailyPayload(), [
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_WEEKLY,
            'day_of_week' => 1,
        ], $overrides);
    }
}
