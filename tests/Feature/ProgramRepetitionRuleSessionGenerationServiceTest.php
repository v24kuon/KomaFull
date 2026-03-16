<?php

namespace Tests\Feature;

use App\Models\LessonSession;
use App\Models\ProgramRepetitionRule;
use App\Models\ReservationManagement;
use App\Services\ProgramRepetitionRuleSessionGenerationService;
use Carbon\CarbonImmutable;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class ProgramRepetitionRuleSessionGenerationServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProgramRepetitionRuleSessionGenerationService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ProgramRepetitionRuleSessionGenerationService::class);
    }

    public function test_generate_creates_missing_lesson_sessions_and_reservation_management_rows(): void
    {
        $rule = ProgramRepetitionRule::factory()->createOne([
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_DAILY,
            'day_of_week' => null,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-03',
            'start_time' => '10:15:30',
            'capacity' => 12,
            'trial_capacity' => 2,
            'status' => ProgramRepetitionRule::STATUS_ACTIVE,
        ]);

        $result = $this->service->generate($rule);

        $this->assertSame(3, $result['created_count']);
        $this->assertSame(0, $result['skipped_count']);

        $sessions = $rule->program->lessonSessions()
            ->with('reservationManagement')
            ->where('location_id', $rule->location_id)
            ->where('staff_id', $rule->staff_id)
            ->orderBy('starts_at')
            ->get();

        $this->assertCount(3, $sessions);
        $this->assertSame([
            '2026-03-01 10:15:30',
            '2026-03-02 10:15:30',
            '2026-03-03 10:15:30',
        ], $sessions->map(
            static fn ($session): string => CarbonImmutable::instance($session->starts_at)->format('Y-m-d H:i:s')
        )->all());
        $this->assertSame([12, 12, 12], $sessions->pluck('capacity')->all());
        $this->assertSame([2, 2, 2], $sessions->pluck('trial_capacity')->all());
        $this->assertContainsOnlyInstancesOf(
            ReservationManagement::class,
            $sessions->pluck('reservationManagement')->all()
        );
        $this->assertSame(
            [0, 0, 0],
            $sessions->map(
                static fn ($session): int => $session->reservationManagement->reserved_count
            )->all()
        );
        $this->assertSame(
            [0, 0, 0],
            $sessions->map(
                static fn ($session): int => $session->reservationManagement->reserved_trial_count
            )->all()
        );
    }

    public function test_generate_skips_existing_session_slots_without_modifying_existing_rows(): void
    {
        $rule = ProgramRepetitionRule::factory()->createOne([
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_DAILY,
            'day_of_week' => null,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-03',
            'start_time' => '10:15:30',
            'capacity' => 12,
            'trial_capacity' => 2,
            'status' => ProgramRepetitionRule::STATUS_ACTIVE,
        ]);

        $existingSession = LessonSession::factory()
            ->forRelationIds($rule->program_id, $rule->location_id, $rule->staff_id)
            ->create([
                'starts_at' => '2026-03-02 10:15:30',
                'capacity' => 99,
                'trial_capacity' => 7,
                'status' => LessonSession::STATUS_INACTIVE,
            ]);

        $existingReservationManagement = ReservationManagement::factory()
            ->forLessonSessionId($existingSession->id)
            ->create([
                'reserved_count' => 3,
                'reserved_trial_count' => 1,
            ]);

        $result = $this->service->generate($rule);

        $this->assertSame(2, $result['created_count']);
        $this->assertSame(1, $result['skipped_count']);
        $this->assertSame(3, LessonSession::query()->count());
        $this->assertSame(3, ReservationManagement::query()->count());
        $this->assertSame(
            1,
            LessonSession::query()
                ->where('program_id', $rule->program_id)
                ->where('location_id', $rule->location_id)
                ->where('staff_id', $rule->staff_id)
                ->where('starts_at', '2026-03-02 10:15:30')
                ->count()
        );

        $this->assertSame(99, $existingSession->fresh()->capacity);
        $this->assertSame(7, $existingSession->fresh()->trial_capacity);
        $this->assertSame(LessonSession::STATUS_INACTIVE, $existingSession->fresh()->status);
        $this->assertSame(3, $existingReservationManagement->fresh()->reserved_count);
        $this->assertSame(1, $existingReservationManagement->fresh()->reserved_trial_count);
    }

    public function test_generate_creates_weekly_lesson_sessions_for_matching_dates(): void
    {
        $rule = ProgramRepetitionRule::factory()->createOne([
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_WEEKLY,
            'day_of_week' => 1,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-20',
            'start_time' => '18:45:00',
            'capacity' => 8,
            'trial_capacity' => 1,
            'status' => ProgramRepetitionRule::STATUS_ACTIVE,
        ]);

        $result = $this->service->generate($rule);

        $this->assertSame(3, $result['created_count']);
        $this->assertSame(0, $result['skipped_count']);

        $sessions = LessonSession::query()
            ->with('reservationManagement')
            ->where('program_id', $rule->program_id)
            ->where('location_id', $rule->location_id)
            ->where('staff_id', $rule->staff_id)
            ->orderBy('starts_at')
            ->get();

        $this->assertSame([
            '2026-03-02 18:45:00',
            '2026-03-09 18:45:00',
            '2026-03-16 18:45:00',
        ], $sessions->map(
            static fn ($session): string => CarbonImmutable::instance($session->starts_at)->format('Y-m-d H:i:s')
        )->all());
        $this->assertContainsOnlyInstancesOf(
            ReservationManagement::class,
            $sessions->pluck('reservationManagement')->all()
        );
        $this->assertSame([0, 0, 0], $sessions->map(
            static fn ($session): int => $session->reservationManagement->reserved_count
        )->all());
        $this->assertSame([0, 0, 0], $sessions->map(
            static fn ($session): int => $session->reservationManagement->reserved_trial_count
        )->all());
    }

    public function test_generate_is_idempotent_when_run_twice_for_the_same_rule(): void
    {
        $rule = ProgramRepetitionRule::factory()->createOne([
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_DAILY,
            'day_of_week' => null,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-03',
            'start_time' => '10:15:30',
            'capacity' => 12,
            'trial_capacity' => 2,
            'status' => ProgramRepetitionRule::STATUS_ACTIVE,
        ]);

        $firstResult = $this->service->generate($rule);
        $secondResult = $this->service->generate($rule);

        $this->assertSame(3, $firstResult['created_count']);
        $this->assertSame(0, $firstResult['skipped_count']);
        $this->assertSame(0, $secondResult['created_count']);
        $this->assertSame(3, $secondResult['skipped_count']);
        $this->assertSame(3, LessonSession::query()->count());
        $this->assertSame(3, ReservationManagement::query()->count());
    }

    public function test_generate_rolls_back_created_sessions_when_reservation_management_creation_fails(): void
    {
        $rule = ProgramRepetitionRule::factory()->createOne([
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_DAILY,
            'day_of_week' => null,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-03',
            'start_time' => '10:15:30',
            'capacity' => 12,
            'trial_capacity' => 2,
            'status' => ProgramRepetitionRule::STATUS_ACTIVE,
        ]);

        ReservationManagement::creating(static function (): void {
            throw new RuntimeException('Simulated reservation management creation failure.');
        });

        try {
            $this->service->generate($rule);
            $this->fail('Expected RuntimeException was not thrown.');
        } catch (RuntimeException $exception) {
            $this->assertSame('Simulated reservation management creation failure.', $exception->getMessage());
        } finally {
            ReservationManagement::flushEventListeners();
        }

        $this->assertDatabaseCount('lesson_sessions', 0);
        $this->assertDatabaseCount('reservation_management', 0);
    }

    public function test_generate_returns_zero_counts_when_candidate_enumeration_is_empty(): void
    {
        $rule = ProgramRepetitionRule::factory()->createOne([
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_WEEKLY,
            'day_of_week' => 1,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-01',
            'start_time' => '10:15:30',
            'capacity' => 12,
            'trial_capacity' => 2,
            'status' => ProgramRepetitionRule::STATUS_ACTIVE,
        ]);

        $result = $this->service->generate($rule);

        $this->assertSame(0, $result['created_count']);
        $this->assertSame(0, $result['skipped_count']);
        $this->assertSame(0, LessonSession::query()->count());
        $this->assertSame(0, ReservationManagement::query()->count());
    }

    public function test_generate_throws_when_candidate_count_exceeds_limit(): void
    {
        $rule = ProgramRepetitionRule::factory()->createOne([
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_DAILY,
            'day_of_week' => null,
            'start_date' => '2026-01-01',
            'end_date' => '2027-12-31',
            'start_time' => '10:15:30',
            'capacity' => 12,
            'trial_capacity' => 2,
            'status' => ProgramRepetitionRule::STATUS_ACTIVE,
        ]);

        try {
            $this->service->generate($rule);
            $this->fail('Expected InvalidArgumentException was not thrown.');
        } catch (InvalidArgumentException $exception) {
            $this->assertSame('candidate count must not exceed 366.', $exception->getMessage());
        }

        $this->assertDatabaseCount('lesson_sessions', 0);
        $this->assertDatabaseCount('reservation_management', 0);
    }

    public function test_generate_skips_slot_when_concurrent_insert_wins_the_unique_constraint_race(): void
    {
        $rule = ProgramRepetitionRule::factory()->createOne([
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_DAILY,
            'day_of_week' => null,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-01',
            'start_time' => '10:15:30',
            'capacity' => 12,
            'trial_capacity' => 2,
            'status' => ProgramRepetitionRule::STATUS_ACTIVE,
        ]);

        $competingInsertPerformed = false;

        LessonSession::creating(function () use ($rule, &$competingInsertPerformed): void {
            if ($competingInsertPerformed) {
                return;
            }

            $competingInsertPerformed = true;

            $competingSession = LessonSession::withoutEvents(
                fn (): LessonSession => LessonSession::factory()
                    ->forRelationIds($rule->program_id, $rule->location_id, $rule->staff_id)
                    ->create([
                        'starts_at' => '2026-03-01 10:15:30',
                        'capacity' => 99,
                        'trial_capacity' => 7,
                        'status' => LessonSession::STATUS_INACTIVE,
                    ])
            );

            ReservationManagement::factory()
                ->forLessonSessionId($competingSession->id)
                ->create([
                    'reserved_count' => 3,
                    'reserved_trial_count' => 1,
                ]);
        });

        try {
            $result = $this->service->generate($rule);
        } finally {
            LessonSession::flushEventListeners();
        }

        $this->assertSame(0, $result['created_count']);
        $this->assertSame(1, $result['skipped_count']);
        $this->assertDatabaseCount('lesson_sessions', 1);
        $this->assertDatabaseCount('reservation_management', 1);

        $existingSession = LessonSession::query()
            ->where('program_id', $rule->program_id)
            ->where('location_id', $rule->location_id)
            ->where('staff_id', $rule->staff_id)
            ->where('starts_at', '2026-03-01 10:15:30')
            ->with('reservationManagement')
            ->firstOrFail();

        $this->assertSame(99, $existingSession->capacity);
        $this->assertSame(7, $existingSession->trial_capacity);
        $this->assertSame(LessonSession::STATUS_INACTIVE, $existingSession->status);
        $this->assertSame(3, $existingSession->reservationManagement->reserved_count);
        $this->assertSame(1, $existingSession->reservationManagement->reserved_trial_count);
    }

    public function test_lesson_sessions_table_rejects_duplicate_concrete_slot_identity(): void
    {
        $rule = ProgramRepetitionRule::factory()->createOne([
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_DAILY,
            'day_of_week' => null,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-03',
            'start_time' => '10:15:30',
            'capacity' => 12,
            'trial_capacity' => 2,
            'status' => ProgramRepetitionRule::STATUS_ACTIVE,
        ]);

        LessonSession::factory()
            ->forRelationIds($rule->program_id, $rule->location_id, $rule->staff_id)
            ->create([
                'starts_at' => '2026-03-01 10:15:30',
            ]);

        try {
            LessonSession::factory()
                ->forRelationIds($rule->program_id, $rule->location_id, $rule->staff_id)
                ->create([
                    'starts_at' => '2026-03-01 10:15:30',
                ]);

            $this->fail('Expected UniqueConstraintViolationException was not thrown.');
        } catch (UniqueConstraintViolationException) {
            $this->assertDatabaseCount('lesson_sessions', 1);
        }
    }
}
