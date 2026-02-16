<?php

namespace Database\Factories;

use App\Models\MemberProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\MemberProfile>
 */
class MemberProfileFactory extends Factory
{
    protected $model = MemberProfile::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'code' => 'MB'.$this->faker->unique()->numerify('######'),
            'member_status' => MemberProfile::STATUS_PROVISIONAL,
            'tel' => $this->faker->phoneNumber(),
            'birth_date' => $this->faker->date(),
            'activated_at' => null,
            'withdrawn_at' => null,
        ];
    }
}
