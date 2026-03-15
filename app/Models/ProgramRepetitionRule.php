<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use InvalidArgumentException;

class ProgramRepetitionRule extends Model
{
    /** @use HasFactory<\Database\Factories\ProgramRepetitionRuleFactory> */
    use HasFactory;

    public const CYCLE_TYPE_DAILY = 'daily';

    public const CYCLE_TYPE_WEEKLY = 'weekly';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'program_id',
        'location_id',
        'staff_id',
        'cycle_type',
        'day_of_week',
        'week_of_month',
        'start_date',
        'end_date',
        'start_time',
        'capacity',
        'trial_capacity',
        'status',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'day_of_week' => 'integer',
            'week_of_month' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'capacity' => 'integer',
            'trial_capacity' => 'integer',
        ];
    }

    /**
     * Register the lifecycle hook that validates supported schedule constraints
     * before the rule is persisted.
     */
    protected static function booted(): void
    {
        static::saving(function (self $rule): void {
            $rule->ensureSupportedScheduleConfiguration();
        });
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Validate that the repetition rule matches the PH6-2-1 supported schedule constraints.
     *
     * `end_date` is required, `week_of_month` is not supported, `daily` rules
     * must not have `day_of_week`, and `weekly` rules must provide `day_of_week`
     * within the 0-6 range.
     *
     * @throws InvalidArgumentException
     */
    private function ensureSupportedScheduleConfiguration(): void
    {
        if ($this->end_date === null) {
            throw new InvalidArgumentException('end_date is required.');
        }

        if ($this->week_of_month !== null) {
            throw new InvalidArgumentException('week_of_month is not supported in PH6-2-1.');
        }

        if ($this->cycle_type === self::CYCLE_TYPE_DAILY && $this->day_of_week !== null) {
            throw new InvalidArgumentException('day_of_week must be null when cycle_type is daily.');
        }

        if ($this->cycle_type !== self::CYCLE_TYPE_WEEKLY) {
            return;
        }

        if ($this->day_of_week === null) {
            throw new InvalidArgumentException('day_of_week is required when cycle_type is weekly.');
        }

        if ($this->day_of_week < 0 || $this->day_of_week > 6) {
            throw new InvalidArgumentException('day_of_week must be between 0 and 6 when cycle_type is weekly.');
        }
    }
}
