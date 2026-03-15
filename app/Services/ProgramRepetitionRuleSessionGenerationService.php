<?php

namespace App\Services;

use App\Models\LessonSession;
use App\Models\Location;
use App\Models\Program;
use App\Models\ProgramRepetitionRule;
use App\Models\ReservationManagement;
use App\Models\Staff;
use Carbon\CarbonImmutable;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProgramRepetitionRuleSessionGenerationService
{
    /**
     * @param  ProgramRepetitionRuleSessionCandidateService  $candidateService  Candidate enumeration for supported PH6-2 schedules
     * @param  ConnectionInterface  $connection  Transaction boundary for session and counter creation
     */
    public function __construct(
        private ProgramRepetitionRuleSessionCandidateService $candidateService,
        private ConnectionInterface $connection
    ) {}

    /**
     * Generate missing lesson sessions and reservation counters for a repetition rule.
     *
     * Preconditions: `$rule` must already satisfy PH6-2-1 foundation constraints. Candidate enumeration is delegated
     * to `ProgramRepetitionRuleSessionCandidateService`, which may throw for invalid schedule data.
     * Update policy: Existing `lesson_sessions` are never updated or deleted. Duplicate candidates are skipped based on
     * the concrete session identity (`program_id`, `location_id`, `staff_id`, `starts_at`), and each newly created
     * session receives a `reservation_management` row initialized to zero counters in the same transaction.
     * Transaction boundary: One DB transaction wraps the full generation run so session rows and reservation counters
     * stay in sync even if an exception occurs mid-run.
     * Idempotency: Re-running with unchanged inputs only creates previously missing candidates and returns skipped counts
     * for already existing session slots.
     *
     * @return array{created_count: int, skipped_count: int}
     */
    public function generate(ProgramRepetitionRule $rule): array
    {
        return $this->connection->transaction(function () use ($rule): array {
            $lockedRule = $this->lockGenerationScope($rule);
            /** @var Collection<int, CarbonImmutable> $candidates */
            $candidates = $this->candidateService->enumerate($lockedRule);

            if ($candidates->isEmpty()) {
                return [
                    'created_count' => 0,
                    'skipped_count' => 0,
                ];
            }

            $existingCandidateKeys = $this->existingCandidateKeys($lockedRule, $candidates);
            $createdCount = 0;
            $skippedCount = 0;

            foreach ($candidates as $candidate) {
                $candidateKey = $this->buildCandidateKey($candidate);

                if ($existingCandidateKeys->has($candidateKey)) {
                    $skippedCount++;

                    continue;
                }

                $lessonSession = $this->createLessonSession($lockedRule, $candidate);

                ReservationManagement::query()->create([
                    'lesson_session_id' => $lessonSession->id,
                    'reserved_count' => 0,
                    'reserved_trial_count' => 0,
                ]);

                $existingCandidateKeys->put($candidateKey, true);
                $createdCount++;
            }

            return [
                'created_count' => $createdCount,
                'skipped_count' => $skippedCount,
            ];
        });
    }

    /**
     * Lock the rule and its generation scope so concurrent runs serialize on the same slot family.
     *
     * Preconditions: Caller is already inside the generation transaction.
     * Update policy: Read-only row locks; no persisted values are changed here.
     * Lock strategy: Acquires locks in a fixed order (`program_repetition_rules` -> `programs` -> `locations` -> `staffs`)
     * before reading existing sessions, reducing the chance that concurrent generation for the same program/location/staff
     * combination can create duplicate `lesson_sessions`.
     */
    private function lockGenerationScope(ProgramRepetitionRule $rule): ProgramRepetitionRule
    {
        $lockedRule = ProgramRepetitionRule::query()
            ->whereKey($rule->id)
            ->lockForUpdate()
            ->firstOrFail();

        Program::query()
            ->whereKey($lockedRule->program_id)
            ->lockForUpdate()
            ->firstOrFail();

        Location::query()
            ->whereKey($lockedRule->location_id)
            ->lockForUpdate()
            ->firstOrFail();

        Staff::query()
            ->whereKey($lockedRule->staff_id)
            ->lockForUpdate()
            ->firstOrFail();

        return $lockedRule;
    }

    /**
     * Resolve the already persisted candidate keys that must be skipped for this rule execution.
     *
     * Preconditions: `$candidates` contains the concrete datetimes returned by the PH6-2-2 enumeration service.
     * Update policy: Read-only query; this method does not mutate sessions or counters.
     *
     * @param  Collection<int, CarbonImmutable>  $candidates
     * @return Collection<string, true>
     */
    private function existingCandidateKeys(
        ProgramRepetitionRule $rule,
        Collection $candidates
    ): Collection {
        $candidateDateTimes = $candidates
            ->map(
                static fn (CarbonImmutable $candidate): string => $candidate->format('Y-m-d H:i:s')
            )
            ->all();

        return LessonSession::query()
            ->where('program_id', $rule->program_id)
            ->where('location_id', $rule->location_id)
            ->where('staff_id', $rule->staff_id)
            ->whereIn('starts_at', $candidateDateTimes)
            ->pluck('starts_at')
            ->mapWithKeys(
                static fn ($startsAt): array => [
                    CarbonImmutable::parse((string) $startsAt)->format('Y-m-d H:i:s') => true,
                ]
            );
    }

    /**
     * Persist one concrete lesson session row derived from a repetition-rule candidate.
     *
     * Preconditions: Caller already confirmed that no existing session occupies the same concrete slot.
     * Update policy: Creates a brand-new `lesson_sessions` row only; related `reservation_management` is created by
     * the caller to keep both writes inside the same transaction.
     */
    private function createLessonSession(
        ProgramRepetitionRule $rule,
        CarbonImmutable $candidate
    ): LessonSession {
        return LessonSession::query()->create([
            'code' => $this->generateLessonSessionCode(),
            'program_id' => $rule->program_id,
            'location_id' => $rule->location_id,
            'staff_id' => $rule->staff_id,
            'starts_at' => $candidate,
            'capacity' => $rule->capacity,
            'trial_capacity' => $rule->trial_capacity,
            'status' => $rule->status === ProgramRepetitionRule::STATUS_INACTIVE
                ? LessonSession::STATUS_INACTIVE
                : LessonSession::STATUS_ACTIVE,
        ]);
    }

    /**
     * Build the duplicate-detection key for one concrete candidate slot.
     *
     * Preconditions: `$candidate` is the final concrete session start datetime.
     * Update policy: Pure helper with no side effects.
     */
    private function buildCandidateKey(CarbonImmutable $candidate): string
    {
        return $candidate->format('Y-m-d H:i:s');
    }

    /**
     * Generate a public lesson-session code.
     *
     * Preconditions: None.
     * Update policy: Produces a new code string without querying or mutating persisted state. The DB unique constraint
     * remains the source of truth for collision safety.
     */
    private function generateLessonSessionCode(): string
    {
        return 'SS'.strtoupper((string) Str::ulid());
    }
}
