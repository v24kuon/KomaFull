<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CoursePlan extends Model
{
    /** @use HasFactory<\Database\Factories\CoursePlanFactory> */
    use HasFactory;

    public const ALLOCATION_TYPE_TOTAL = 'total';

    public const ALLOCATION_TYPE_PER_CATEGORY = 'per_category';

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
        'stripe_price_id',
        'usage_count',
        'allocation_type',
        'level',
        'description',
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
            'usage_count' => 'integer',
        ];
    }

    public function categories(): HasMany
    {
        return $this->hasMany(CoursePlanCategory::class);
    }

    public function entitlements(): HasMany
    {
        return $this->hasMany(CourseEntitlement::class);
    }
}
