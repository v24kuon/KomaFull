<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BalanceTransaction extends Model
{
    /** @use HasFactory<\Database\Factories\BalanceTransactionFactory> */
    use HasFactory;

    public const UNIT_TICKETS = 'tickets';

    public const UNIT_POINTS = 'points';

    public const TYPE_GRANT = 'grant';

    public const TYPE_CONSUME = 'consume';

    public const TYPE_REFUND = 'refund';

    public const TYPE_EXPIRE = 'expire';

    public const TYPE_ADJUST = 'adjust';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'unit',
        'amount',
        'transaction_type',
        'idempotency_key',
        'prepaid_purchase_id',
        'reservation_id',
        'stripe_reference_id',
        'occurred_at',
        'expires_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'occurred_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function prepaidPurchase(): BelongsTo
    {
        return $this->belongsTo(PrepaidPurchase::class);
    }

    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
