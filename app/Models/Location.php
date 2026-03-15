<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    /** @use HasFactory<\Database\Factories\LocationFactory> */
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
        'name',
        'address',
        'tel',
        'email',
        'description',
        'status',
    ];

    public function lessonSessions(): HasMany
    {
        return $this->hasMany(LessonSession::class);
    }

    public function programRepetitionRules(): HasMany
    {
        return $this->hasMany(ProgramRepetitionRule::class);
    }
}
