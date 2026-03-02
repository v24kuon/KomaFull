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
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
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
