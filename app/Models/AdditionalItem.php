<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdditionalItem extends Model
{
    /** @use HasFactory<\Database\Factories\AdditionalItemFactory> */
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'additional_item_type',
        'label_name',
        'input_type',
        'digits',
        'select_options',
        'status',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'digits' => 'integer',
            'select_options' => 'array',
        ];
    }

    /**
     * @return HasMany<MemberAdditionalItemValue, self>
     */
    public function memberAdditionalItemValues(): HasMany
    {
        return $this->hasMany(MemberAdditionalItemValue::class);
    }
}
