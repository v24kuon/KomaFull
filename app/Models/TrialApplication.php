<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrialApplication extends Model
{
    /** @use HasFactory<\Database\Factories\TrialApplicationFactory> */
    use HasFactory;

    public const PAYMENT_METHOD_CARD = 'card';

    public const PAYMENT_METHOD_ONSITE = 'onsite';

    public const STATUS_PENDING_PAYMENT = 'pending_payment';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_RESERVED = 'reserved';

    public const STATUS_REFUND_PENDING = 'refund_pending';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_REFUND_FAILED = 'refund_failed';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_CANCELED = 'canceled';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'lesson_session_id',
        'payment_method',
        'status',
        'stripe_checkout_session_id',
        'expires_at',
        'reservation_id',
        'refunded_at',
        'refund_reason',
        'canceled_at',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'refunded_at' => 'datetime',
            'canceled_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, self>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<LessonSession, self>
     */
    public function lessonSession(): BelongsTo
    {
        return $this->belongsTo(LessonSession::class);
    }

    /**
     * @return BelongsTo<Reservation, self>
     */
    public function reservation(): BelongsTo
    {
        return $this->belongsTo(Reservation::class);
    }
}
