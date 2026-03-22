<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberAdditionalItemValue extends Model
{
    /** @use HasFactory<\Database\Factories\MemberAdditionalItemValueFactory> */
    use HasFactory;

    protected $table = 'member_additional_item_values';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'member_profile_id',
        'additional_item_id',
        'value',
    ];

    public function memberProfile(): BelongsTo
    {
        return $this->belongsTo(MemberProfile::class);
    }

    public function additionalItem(): BelongsTo
    {
        return $this->belongsTo(AdditionalItem::class);
    }
}
