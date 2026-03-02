<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreSettings extends Model
{
    /** @use HasFactory<\Database\Factories\StoreSettingsFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'program_label',
        'session_label',
        'staff_label',
        'location_label',
        'reserve_deadline_minutes',
        'cancel_deadline_minutes',
        'withdrawal_deadline_days',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'reserve_deadline_minutes' => 'integer',
            'cancel_deadline_minutes' => 'integer',
            'withdrawal_deadline_days' => 'integer',
        ];
    }
}
