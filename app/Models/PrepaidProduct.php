<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PrepaidProduct extends Model
{
    /** @use HasFactory<\Database\Factories\PrepaidProductFactory> */
    use HasFactory;

    public const PREPAID_TYPE_TICKETS = 'tickets';

    public const PREPAID_TYPE_POINTS = 'points';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'prepaid_type',
        'sales_name',
        'usage_count',
        'expires_in_days',
        'price',
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
            'expires_in_days' => 'integer',
            'price' => 'integer',
        ];
    }

    public function purchases(): HasMany
    {
        return $this->hasMany(PrepaidPurchase::class);
    }
}
