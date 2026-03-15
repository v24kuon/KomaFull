<?php

namespace App\Services;

use App\Models\ProgramRepetitionRule;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class ProgramRepetitionRuleSessionCandidateService
{
    /**
     * Enumerate PH6-2-2 candidate session start datetimes from a repetition rule.
     *
     * @return Collection<int, CarbonImmutable>
     */
    public function enumerate(ProgramRepetitionRule $rule): Collection
    {
        $startDate = $this->resolveBoundaryDate($rule->start_date, 'start_date');
        $endDate = $this->resolveBoundaryDate($rule->end_date, 'end_date');

        if ($startDate->gt($endDate)) {
            throw new InvalidArgumentException('start_date must be on or before end_date.');
        }

        if ($rule->week_of_month !== null) {
            throw new InvalidArgumentException('week_of_month is not supported for PH6-2-2.');
        }

        [$hours, $minutes, $seconds] = $this->parseStartTime($rule->start_time);

        return match ($rule->cycle_type) {
            ProgramRepetitionRule::CYCLE_TYPE_DAILY => $this->enumerateDaily(
                $startDate,
                $endDate,
                $hours,
                $minutes,
                $seconds,
                $rule
            ),
            ProgramRepetitionRule::CYCLE_TYPE_WEEKLY => $this->enumerateWeekly(
                $startDate,
                $endDate,
                $hours,
                $minutes,
                $seconds,
                $this->resolveWeeklyDayOfWeek($rule)
            ),
            default => throw new InvalidArgumentException(sprintf(
                'Unsupported cycle_type: %s',
                is_scalar($rule->cycle_type) ? (string) $rule->cycle_type : gettype($rule->cycle_type)
            )),
        };
    }

    /**
     * @return Collection<int, CarbonImmutable>
     */
    private function enumerateDaily(
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
        int $hours,
        int $minutes,
        int $seconds,
        ProgramRepetitionRule $rule
    ): Collection {
        if ($rule->day_of_week !== null) {
            throw new InvalidArgumentException('day_of_week must be null for daily rules.');
        }

        $candidates = [];

        for ($cursor = $startDate; $cursor->lte($endDate); $cursor = $cursor->addDay()) {
            $candidates[] = $cursor->setTime($hours, $minutes, $seconds);
        }

        return collect($candidates);
    }

    /**
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

    private function resolveBoundaryDate(mixed $value, string $field): CarbonImmutable
    {
        if (! $value instanceof CarbonInterface) {
            throw new InvalidArgumentException(sprintf('%s is required.', $field));
        }

        return $value->toImmutable()->startOfDay();
    }

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
