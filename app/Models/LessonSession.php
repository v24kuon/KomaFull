<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class LessonSession extends Model
{
    /** @use HasFactory<\Database\Factories\LessonSessionFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'program_id',
        'location_id',
        'staff_id',
        'starts_at',
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
            'program_id' => 'integer',
            'location_id' => 'integer',
            'staff_id' => 'integer',
            'starts_at' => 'datetime',
            'capacity' => 'integer',
            'trial_capacity' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Program, self>
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * @return BelongsTo<Location, self>
     */
    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    /**
     * @return BelongsTo<Staff, self>
     */
    public function staff(): BelongsTo
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * @return HasOne<ReservationManagement, self>
     */
    public function reservationManagement(): HasOne
    {
        return $this->hasOne(ReservationManagement::class);
    }

    /**
     * 公開ルートのルートキーは `lesson_sessions.code`（一意）を用いる。
     */
    public function getRouteKeyName(): string
    {
        return 'code';
    }
}
