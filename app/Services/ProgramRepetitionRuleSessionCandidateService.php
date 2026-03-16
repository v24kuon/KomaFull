<?php

namespace App\Services;

use App\Models\ProgramRepetitionRule;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ProgramRepetitionRuleSessionCandidateService
{
    public const MAX_GENERATION_CANDIDATES = 366;

    /**
     * Enumerate PH6-2-2 candidate session start datetimes from a repetition rule.
     *
     * Preconditions: `$rule` must be a PH6-2-2 supported rule (cycle_type daily or weekly, end_date required,
     * week_of_month null, daily without day_of_week, weekly with valid day_of_week 0-6).
     * Update policy: Returns derived datetimes only and does not mutate the rule or any persisted state.
     * Lock: none. Transaction: none. Idempotent: yes (pure function; same input yields same output).
     *
     * @return Collection<int, CarbonImmutable>
     */
    public function enumerate(ProgramRepetitionRule $rule): Collection
    {
        $candidateRule = $this->resolveCandidateRule($rule);
        [$hours, $minutes, $seconds] = $candidateRule['startTime'];

        return match ($candidateRule['cycleType']) {
            ProgramRepetitionRule::CYCLE_TYPE_DAILY => $this->enumerateDaily(
                $candidateRule['startDate'],
                $candidateRule['endDate'],
                $hours,
                $minutes,
                $seconds
            ),
            ProgramRepetitionRule::CYCLE_TYPE_WEEKLY => $this->enumerateWeekly(
                $candidateRule['startDate'],
                $candidateRule['endDate'],
                $hours,
                $minutes,
                $seconds,
                $candidateRule['dayOfWeek']
            ),
        };
    }

    /**
     * Count candidate sessions for one repetition rule without materializing the full collection.
     *
     * Preconditions: `$rule` must satisfy the PH6-2 supported schedule constraints.
     */
    public function candidateCount(ProgramRepetitionRule $rule): int
    {
        $candidateRule = $this->resolveCandidateRule($rule);

        return match ($candidateRule['cycleType']) {
            ProgramRepetitionRule::CYCLE_TYPE_DAILY => $candidateRule['startDate']->diffInDays($candidateRule['endDate']) + 1,
            ProgramRepetitionRule::CYCLE_TYPE_WEEKLY => $this->countWeeklyCandidates(
                $candidateRule['startDate'],
                $candidateRule['endDate'],
                $candidateRule['dayOfWeek']
            ),
        };
    }

    /**
     * Validate a repetition rule once and return the normalized schedule inputs shared by counting and enumeration.
     *
     * Preconditions: `$rule` may contain persisted legacy values, but must still satisfy the PH6-2-2 schedule
     * constraints before candidate processing continues.
     * Update policy: Returns derived date/time scalars only and does not mutate the rule or any persisted state.
     *
     * @return array{
     *   startDate: CarbonImmutable,
     *   endDate: CarbonImmutable,
     *   cycleType: string,
     *   startTime: array{0: int, 1: int, 2: int},
     *   dayOfWeek?: int
     * }
     */
    private function resolveCandidateRule(ProgramRepetitionRule $rule): array
    {
        $startDate = $this->resolveBoundaryDate($rule->start_date, 'start_date');
        $endDate = $this->resolveBoundaryDate($rule->end_date, 'end_date');

        if ($startDate->gt($endDate)) {
            throw new InvalidArgumentException('start_date must be on or before end_date.');
        }

        if ($rule->week_of_month !== null) {
            throw new InvalidArgumentException('week_of_month is not supported for PH6-2-2.');
        }

        $startTime = $this->parseStartTime($rule->start_time);

        if ($rule->cycle_type === ProgramRepetitionRule::CYCLE_TYPE_DAILY && $rule->day_of_week !== null) {
            throw new InvalidArgumentException('day_of_week must be null for daily rules.');
        }

        return match ($rule->cycle_type) {
            ProgramRepetitionRule::CYCLE_TYPE_DAILY => [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'cycleType' => ProgramRepetitionRule::CYCLE_TYPE_DAILY,
                'startTime' => $startTime,
            ],
            ProgramRepetitionRule::CYCLE_TYPE_WEEKLY => [
                'startDate' => $startDate,
                'endDate' => $endDate,
                'cycleType' => ProgramRepetitionRule::CYCLE_TYPE_WEEKLY,
                'startTime' => $startTime,
                'dayOfWeek' => $this->resolveWeeklyDayOfWeek($rule),
            ],
            default => throw new InvalidArgumentException(sprintf(
                'Unsupported cycle_type: %s',
                is_scalar($rule->cycle_type) ? (string) $rule->cycle_type : gettype($rule->cycle_type)
            )),
        };
    }

    /**
     * Build daily candidate datetimes across the inclusive effective date range.
     *
     * Preconditions: `$startDate` and `$endDate` are normalized boundaries. Caller must ensure day_of_week is null for daily rules.
     * Update policy: Returns derived datetimes only and does not mutate application state.
     *
     * @return Collection<int, CarbonImmutable>
     */
    private function enumerateDaily(
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        int $hours,
        int $minutes,
        int $seconds
    ): Collection {
        $candidates = [];

        for ($cursor = $startDate; $cursor->lte($endDate); $cursor = $cursor->addDay()) {
            $candidates[] = $cursor->setTime($hours, $minutes, $seconds);
        }

        return collect($candidates);
    }

    /**
     * Build weekly candidate datetimes that match the validated weekday within the inclusive effective date range.
     *
     * Preconditions: `$startDate` and `$endDate` are normalized boundaries, and `$dayOfWeek` is already validated to 0-6.
     * Update policy: Returns derived datetimes only and does not mutate application state.
     *
     * @return Collection<int, CarbonImmutable>
     */
    private function enumerateWeekly(
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        int $hours,
        int $minutes,
        int $seconds,
        int $dayOfWeek
    ): Collection {
        $daysUntilFirstMatch = ($dayOfWeek - $startDate->dayOfWeek + 7) % 7;
        $firstCandidateDate = $startDate->addDays($daysUntilFirstMatch);
        $candidates = [];

        for ($cursor = $firstCandidateDate; $cursor->lte($endDate); $cursor = $cursor->addWeek()) {
            $candidates[] = $cursor->setTime($hours, $minutes, $seconds);
        }

        return collect($candidates);
    }

    /**
     * Count weekly candidates that match the configured weekday within the inclusive date range.
     */
    private function countWeeklyCandidates(
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        int $dayOfWeek
    ): int {
        $daysUntilFirstMatch = ($dayOfWeek - $startDate->dayOfWeek + 7) % 7;
        $firstCandidateDate = $startDate->addDays($daysUntilFirstMatch);

        if ($firstCandidateDate->gt($endDate)) {
            return 0;
        }

        return intdiv($firstCandidateDate->diffInDays($endDate), 7) + 1;
    }

    /**
     * Normalize a rule boundary date to an immutable start-of-day value.
     *
     * Preconditions: `$value` must already be available as a `CarbonInterface` from the rule attribute.
     * Update policy: Returns a normalized copy only and does not mutate the source value or rule state.
     */
    private function resolveBoundaryDate(mixed $value, string $field): CarbonImmutable
    {
        if (! $value instanceof CarbonInterface) {
            throw new InvalidArgumentException(sprintf('%s is required.', $field));
        }

        return $value->toImmutable()->startOfDay();
    }

    /**
     * Validate the raw weekly weekday input and convert it to the canonical integer value.
     *
     * Preconditions: The caller is handling a weekly rule, and raw attributes may still contain uncast input.
     * Update policy: Reads the rule attributes without mutating the rule or any persisted state.
     */
    private function resolveWeeklyDayOfWeek(ProgramRepetitionRule $rule): int
    {
        $rawValue = $rule->getAttributes()['day_of_week'] ?? $rule->day_of_week;

        if ($rawValue === null || (is_string($rawValue) && trim($rawValue) === '')) {
            throw new InvalidArgumentException('day_of_week is required for weekly rules.');
        }

        if (! is_int($rawValue) && ! (is_string($rawValue) && preg_match('/^-?\d+$/', $rawValue) === 1)) {
            throw new InvalidArgumentException('day_of_week must be an integer for weekly rules.');
        }

        $value = (int) $rawValue;

        if ($value < 0 || $value > 6) {
            throw new InvalidArgumentException('day_of_week must be between 0 and 6 for weekly rules.');
        }

        return $value;
    }

    /**
     * Validate the configured start time and split it into hour, minute, and second components.
     *
     * Preconditions: `$value` must be the raw `start_time` input for the rule.
     * Update policy: Returns parsed scalar components only and does not mutate application state.
     *
     * @return array{0: int, 1: int, 2: int}
     */
    private function parseStartTime(mixed $value): array
    {
        if (! is_string($value)) {
            throw new InvalidArgumentException('start_time must be in H:i:s format.');
        }

        $normalizedValue = trim($value);

        if (preg_match('/^(2[0-3]|[01]\d):([0-5]\d):([0-5]\d)$/', $normalizedValue, $matches) !== 1) {
            throw new InvalidArgumentException('start_time must be in H:i:s format.');
        }

        return [
            (int) $matches[1],
            (int) $matches[2],
            (int) $matches[3],
        ];
    }
}
