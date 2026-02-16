<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Reservation extends Model
{
    /** @use HasFactory<\Database\Factories\ReservationFactory> */
    use HasFactory;

    public const STATUS_CONFIRMED = 'confirmed';

    public const STATUS_CANCELED = 'canceled';

    public const SEAT_BUCKET_TRIAL = 'trial';

    public const SEAT_BUCKET_NORMAL = 'normal';

    public const PAYMENT_METHOD_SUBSCRIPTION = 'subscription';

    public const PAYMENT_METHOD_TICKETS = 'tickets';

    public const PAYMENT_METHOD_POINTS = 'points';

    public const PAYMENT_METHOD_TRIAL_CARD = 'trial_card';

    public const PAYMENT_METHOD_TRIAL_ONSITE = 'trial_onsite';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'code',
        'user_id',
        'lesson_session_id',
        'seat_bucket',
        'payment_method',
        'status',
        'ticket_cost',
        'point_cost',
        'course_entitlement_id',
        'canceled_at',
        'cancel_reason',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'canceled_at' => 'datetime',
            'ticket_cost' => 'integer',
            'point_cost' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lessonSession(): BelongsTo
    {
        return $this->belongsTo(LessonSession::class);
    }

    public function courseEntitlement(): BelongsTo
    {
        return $this->belongsTo(CourseEntitlement::class);
    }
}
