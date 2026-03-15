<?php

namespace Tests\Unit;

use App\Models\ProgramRepetitionRule;
use App\Services\ProgramRepetitionRuleSessionCandidateService;
use Carbon\CarbonImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ProgramRepetitionRuleSessionCandidateServiceTest extends TestCase
{
    private ProgramRepetitionRuleSessionCandidateService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ProgramRepetitionRuleSessionCandidateService::class);
    }

    public function test_service_can_be_resolved_from_container(): void
    {
        $this->assertTrue(class_exists(ProgramRepetitionRuleSessionCandidateService::class));
        $this->assertInstanceOf(
            ProgramRepetitionRuleSessionCandidateService::class,
            $this->service
        );
    }

    public function test_service_has_enumerate_method(): void
    {
        $this->assertTrue(method_exists(
            ProgramRepetitionRuleSessionCandidateService::class,
            'enumerate'
        ));
    }

    public function test_daily_rule_enumerates_every_date_in_range(): void
    {
        $candidates = $this->service->enumerate($this->makeRule([
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-03',
            'start_time' => '10:15:30',
        ]));

        $this->assertCount(3, $candidates);
        $this->assertContainsOnlyInstancesOf(CarbonImmutable::class, $candidates->all());
        $this->assertSame([
            '2026-03-01 10:15:30',
            '2026-03-02 10:15:30',
            '2026-03-03 10:15:30',
        ], $this->formatCandidates($candidates->all()));
    }

    public function test_weekly_rule_enumerates_only_matching_weekday_in_range(): void
    {
        $candidates = $this->service->enumerate($this->makeRule([
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_WEEKLY,
            'day_of_week' => 1,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-20',
            'start_time' => '18:45:00',
        ]));

        $this->assertSame([
            '2026-03-02 18:45:00',
            '2026-03-09 18:45:00',
            '2026-03-16 18:45:00',
        ], $this->formatCandidates($candidates->all()));
    }

    /**
     * @param  list<string>  $expectedCandidates
     */
    #[DataProvider('weeklyBoundaryDayOfWeekProvider')]
    public function test_weekly_rule_accepts_boundary_day_of_week_values(
        int $dayOfWeek,
        string $startDate,
        string $endDate,
        array $expectedCandidates
    ): void {
        $candidates = $this->service->enumerate($this->makeRule([
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_WEEKLY,
            'day_of_week' => $dayOfWeek,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'start_time' => '09:00:00',
        ]));

        $this->assertSame($expectedCandidates, $this->formatCandidates($candidates->all()));
    }

    public function test_daily_rule_returns_single_candidate_for_single_day_range(): void
    {
        $candidates = $this->service->enumerate($this->makeRule([
            'start_date' => '2026-03-05',
            'end_date' => '2026-03-05',
            'start_time' => '08:00:00',
        ]));

        $this->assertSame(['2026-03-05 08:00:00'], $this->formatCandidates($candidates->all()));
    }

    public function test_weekly_rule_returns_single_candidate_for_matching_single_day_range(): void
    {
        $candidates = $this->service->enumerate($this->makeRule([
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_WEEKLY,
            'day_of_week' => 0,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-01',
            'start_time' => '08:30:00',
        ]));

        $this->assertSame(['2026-03-01 08:30:00'], $this->formatCandidates($candidates->all()));
    }

    public function test_weekly_rule_returns_no_candidates_for_non_matching_single_day_range(): void
    {
        $candidates = $this->service->enumerate($this->makeRule([
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_WEEKLY,
            'day_of_week' => 1,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-01',
            'start_time' => '08:30:00',
        ]));

        $this->assertSame([], $this->formatCandidates($candidates->all()));
    }

    public function test_inverted_date_range_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('start_date must be on or before end_date.');

        $this->service->enumerate($this->makeRule([
            'start_date' => '2026-03-10',
            'end_date' => '2026-03-01',
        ]));
    }

    public function test_unsupported_cycle_type_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported cycle_type: monthly');

        $this->service->enumerate($this->makeRule([
            'cycle_type' => 'monthly',
        ]));
    }

    public function test_weekly_rule_without_day_of_week_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('day_of_week is required for weekly rules.');

        $this->service->enumerate($this->makeRule([
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_WEEKLY,
            'day_of_week' => null,
        ]));
    }

    public function test_daily_rule_with_day_of_week_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('day_of_week must be null for daily rules.');

        $this->service->enumerate($this->makeRule([
            'day_of_week' => 1,
        ]));
    }

    public function test_rule_with_week_of_month_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('week_of_month is not supported for PH6-2-2.');

        $this->service->enumerate($this->makeRule([
            'week_of_month' => 1,
        ]));
    }

    public function test_weekly_rule_with_out_of_range_day_of_week_throws_exception(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('day_of_week must be between 0 and 6 for weekly rules.');

        $this->service->enumerate($this->makeRule([
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_WEEKLY,
            'day_of_week' => 7,
        ]));
    }

    /**
     * @param  scalar|null  $dayOfWeek
     */
    #[DataProvider('invalidRawWeeklyDayOfWeekProvider')]
    public function test_weekly_rule_rejects_invalid_raw_day_of_week_values(
        mixed $dayOfWeek,
        string $expectedMessage
    ): void {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($expectedMessage);

        $this->service->enumerate($this->makeRule([
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_WEEKLY,
            'day_of_week' => $dayOfWeek,
        ]));
    }

    #[DataProvider('invalidStartTimeProvider')]
    public function test_invalid_start_time_throws_exception(string $startTime): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('start_time must be in H:i:s format.');

        $this->service->enumerate($this->makeRule([
            'start_time' => $startTime,
        ]));
    }

    /**
     * @return array<string, array{0: int, 1: string, 2: string, 3: list<string>}>
     */
    public static function weeklyBoundaryDayOfWeekProvider(): array
    {
        return [
            'sunday' => [
                0,
                '2026-03-01',
                '2026-03-15',
                [
                    '2026-03-01 09:00:00',
                    '2026-03-08 09:00:00',
                    '2026-03-15 09:00:00',
                ],
            ],
            'saturday' => [
                6,
                '2026-03-01',
                '2026-03-15',
                [
                    '2026-03-07 09:00:00',
                    '2026-03-14 09:00:00',
                ],
            ],
        ];
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function invalidStartTimeProvider(): array
    {
        return [
            'empty' => [''],
            'missing-seconds' => ['10:00'],
            'hour-overflow' => ['24:00:00'],
            'minute-overflow' => ['10:60:00'],
            'non-numeric' => ['ab:cd:ef'],
        ];
    }

    /**
     * @return array<string, array{0: scalar|null, 1: string}>
     */
    public static function invalidRawWeeklyDayOfWeekProvider(): array
    {
        return [
            'empty-string' => ['', 'day_of_week is required for weekly rules.'],
            'whitespace-only' => ['   ', 'day_of_week is required for weekly rules.'],
            'whitespace-padded-numeric' => [' 1 ', 'day_of_week must be an integer for weekly rules.'],
            'non-numeric-string' => ['abc', 'day_of_week must be an integer for weekly rules.'],
        ];
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeRule(array $overrides = []): ProgramRepetitionRule
    {
        return new ProgramRepetitionRule(array_merge([
            'cycle_type' => ProgramRepetitionRule::CYCLE_TYPE_DAILY,
            'day_of_week' => null,
            'week_of_month' => null,
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-03',
            'start_time' => '10:00:00',
            'capacity' => 12,
            'trial_capacity' => 2,
            'status' => ProgramRepetitionRule::STATUS_ACTIVE,
        ], $overrides));
    }

    /**
     * @param  list<CarbonImmutable>  $candidates
     * @return list<string>
     */
    private function formatCandidates(array $candidates): array
    {
        return array_map(
            static fn (CarbonImmutable $candidate): string => $candidate->format('Y-m-d H:i:s'),
            $candidates
        );
    }
}
