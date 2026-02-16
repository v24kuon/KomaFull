<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
            'starts_at' => 'datetime',
        ];
    }

    /**
     * TODO(PH2-2): Program/Location/Staff モデル実装後に belongsTo リレーションを追加
     * - program(): BelongsTo<Program>
     * - location(): BelongsTo<Location>
     * - staff(): BelongsTo<Staff>
     */
}
