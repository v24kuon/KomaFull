<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReservationManagement extends Model
{
    /** @use HasFactory<\Database\Factories\ReservationManagementFactory> */
    use HasFactory;

    protected $table = 'reservation_management';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'lesson_session_id',
        'reserved_count',
        'reserved_trial_count',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reserved_count' => 'integer',
            'reserved_trial_count' => 'integer',
        ];
    }

    public function lessonSession(): BelongsTo
    {
        return $this->belongsTo(LessonSession::class);
    }
}
