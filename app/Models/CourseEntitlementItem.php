<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CourseEntitlementItem extends Model
{
    /** @use HasFactory<\Database\Factories\CourseEntitlementItemFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'course_entitlement_id',
        'category_id',
        'granted_uses',
        'used_uses',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'granted_uses' => 'integer',
            'used_uses' => 'integer',
        ];
    }

    public function courseEntitlement(): BelongsTo
    {
        return $this->belongsTo(CourseEntitlement::class);
    }

    /**
     * TODO(PH9-1-3): Category モデル実装後に belongsTo リレーションを追加
     * - category(): BelongsTo<Category>
     */
}
