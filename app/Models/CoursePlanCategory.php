<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CoursePlanCategory extends Model
{
    /** @use HasFactory<\Database\Factories\CoursePlanCategoryFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'course_plan_id',
        'category_id',
    ];

    public function coursePlan(): BelongsTo
    {
        return $this->belongsTo(CoursePlan::class);
    }

    /**
     * TODO(PH9-1-3): Category モデル実装後に belongsTo リレーションを追加
     * - category(): BelongsTo<Category>
     */
}
