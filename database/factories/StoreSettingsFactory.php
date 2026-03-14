<?php

namespace Database\Factories;

use App\Models\StoreSettings;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StoreSettings>
 */
class StoreSettingsFactory extends Factory
{
    protected $model = StoreSettings::class;

    /**
     * @return array{
     *     singleton_key: 'singleton',
     *     program_label: string,
     *     session_label: string,
     *     staff_label: string,
     *     location_label: string,
     *     reserve_deadline_minutes: int,
     *     cancel_deadline_minutes: int,
     *     withdrawal_deadline_days: int
     * }
     */
    public function definition(): array
    {
        return [
            'singleton_key' => 'singleton',
            'program_label' => 'プログラム',
            'session_label' => 'セッション',
            'staff_label' => 'スタッフ',
            'location_label' => '店舗',
            'reserve_deadline_minutes' => 60,
            'cancel_deadline_minutes' => 1440,
            'withdrawal_deadline_days' => 30,
        ];
    }
}
