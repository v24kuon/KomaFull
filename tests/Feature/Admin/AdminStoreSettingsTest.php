<?php

namespace Tests\Feature\Admin;

use App\Models\StoreSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStoreSettingsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        /** @var User $admin */
        $admin = User::factory()->createOne(['role' => User::ROLE_ADMIN]);
        $this->admin = $admin;
    }

    public function test_edit_displays_settings_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.store-settings.edit'));

        $response->assertOk();
        $response->assertSeeText('店舗設定');
    }

    public function test_edit_creates_default_settings_if_missing(): void
    {
        $this->assertDatabaseCount('store_settings', 0);

        $this->actingAs($this->admin)->get(route('admin.store-settings.edit'));

        $this->assertDatabaseCount('store_settings', 1);
    }

    public function test_edit_keeps_singleton_settings_row(): void
    {
        $this->actingAs($this->admin)->get(route('admin.store-settings.edit'));
        $this->actingAs($this->admin)->get(route('admin.store-settings.edit'));

        $this->assertDatabaseCount('store_settings', 1);
    }

    public function test_update_creates_singleton_settings_if_missing(): void
    {
        $this->assertDatabaseCount('store_settings', 0);

        $data = [
            'program_label' => 'レッスン',
            'session_label' => 'クラス',
            'staff_label' => '講師',
            'location_label' => 'スタジオ',
            'reserve_deadline_minutes' => 30,
            'cancel_deadline_minutes' => 720,
            'withdrawal_deadline_days' => 14,
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.store-settings.update'), $data);

        $response->assertRedirect(route('admin.store-settings.edit'));
        $this->assertDatabaseCount('store_settings', 1);
        $this->assertDatabaseHas('store_settings', [
            'singleton_key' => 'singleton',
            'program_label' => 'レッスン',
            'staff_label' => '講師',
        ]);
    }

    public function test_update_modifies_settings(): void
    {
        StoreSettings::factory()->createOne();

        $data = [
            'program_label' => 'レッスン',
            'session_label' => 'クラス',
            'staff_label' => '講師',
            'location_label' => 'スタジオ',
            'reserve_deadline_minutes' => 30,
            'cancel_deadline_minutes' => 720,
            'withdrawal_deadline_days' => 14,
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.store-settings.update'), $data);

        $response->assertRedirect(route('admin.store-settings.edit'));
        $this->assertDatabaseHas('store_settings', ['program_label' => 'レッスン', 'staff_label' => '講師']);
    }

    public function test_update_reuses_existing_singleton_settings_row_when_unique_key_already_exists(): void
    {
        $existingSettings = StoreSettings::factory()->createOne();

        $data = [
            'program_label' => 'レッスン',
            'session_label' => 'クラス',
            'staff_label' => '講師',
            'location_label' => 'スタジオ',
            'reserve_deadline_minutes' => 30,
            'cancel_deadline_minutes' => 720,
            'withdrawal_deadline_days' => 14,
        ];

        $response = $this->actingAs($this->admin)->put(route('admin.store-settings.update'), $data);

        $updatedSettings = StoreSettings::query()->sole();

        $response->assertRedirect(route('admin.store-settings.edit'));
        $this->assertDatabaseCount('store_settings', 1);
        $this->assertSame($existingSettings->id, $updatedSettings->id);
        $this->assertSame('singleton', $updatedSettings->singleton_key);
        $this->assertSame('レッスン', $updatedSettings->program_label);
        $this->assertSame('講師', $updatedSettings->staff_label);
    }

    public function test_update_validates_required_fields(): void
    {
        StoreSettings::factory()->createOne();

        $response = $this->actingAs($this->admin)->put(route('admin.store-settings.update'), []);

        $response->assertSessionHasErrors([
            'program_label', 'session_label', 'staff_label', 'location_label',
            'reserve_deadline_minutes', 'cancel_deadline_minutes', 'withdrawal_deadline_days',
        ]);
    }

    public function test_validation_errors_are_rendered_with_alert_role(): void
    {
        StoreSettings::factory()->createOne();

        $response = $this->actingAs($this->admin)
            ->from(route('admin.store-settings.edit'))
            ->followingRedirects()
            ->put(route('admin.store-settings.update'), []);

        $response->assertOk();
        $response->assertSee('class="invalid-feedback" role="alert"', false);
    }

    public function test_update_validates_numeric_fields(): void
    {
        StoreSettings::factory()->createOne();

        $response = $this->actingAs($this->admin)->put(route('admin.store-settings.update'), [
            'program_label' => 'テスト',
            'session_label' => 'テスト',
            'staff_label' => 'テスト',
            'location_label' => 'テスト',
            'reserve_deadline_minutes' => -1,
            'cancel_deadline_minutes' => 'abc',
            'withdrawal_deadline_days' => -5,
        ]);

        $response->assertSessionHasErrors(['reserve_deadline_minutes', 'cancel_deadline_minutes', 'withdrawal_deadline_days']);
    }
}
