<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * 店舗・プログラム・開催枠などのデモデータは本番で `db:seed` を誤実行した際のリスクがあるため、
     * ここでは投入しない。必要なときのみ `php artisan db:seed --class=DemoStoreDataSeeder` を実行する。
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
