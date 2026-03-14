<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    /** @use HasFactory<\Database\Factories\ProgramFactory> */
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
        'category_id',
        'program_type_id',
        'name',
        'level',
        'duration_minutes',
        'overview',
        'detail',
        'price',
        'point_cost',
        'ticket_cost',
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
            'duration_minutes' => 'integer',
            'price' => 'integer',
            'point_cost' => 'integer',
            'ticket_cost' => 'integer',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function programType(): BelongsTo
    {
        return $this->belongsTo(ProgramType::class);
    }

    public function lessonSessions(): HasMany
    {
        return $this->hasMany(LessonSession::class);
    }

    public function programRepetitionRules(): HasMany
    {
        return $this->hasMany(ProgramRepetitionRule::class);
    }
}
