<?php

namespace Tests\Feature\Admin;

use App\Models\Location;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminLocationCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        /** @var User $admin */
        $admin = User::factory()->createOne(['role' => 'admin']);
        $this->admin = $admin;
    }

    public function test_index_displays_locations(): void
    {
        $location = Location::factory()->createOne();

        $response = $this->actingAs($this->admin)->get(route('admin.locations.index'));

        $response->assertOk();
        $response->assertSeeText($location->name);
    }

    public function test_store_creates_location(): void
    {
        $data = [
            'code' => 'LOC-NEW',
            'name' => 'テスト店舗',
            'address' => '東京都渋谷区',
            'tel' => '03-1234-5678',
            'email' => 'test@example.com',
            'description' => '説明文',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.locations.store'), $data);

        $response->assertRedirect(route('admin.locations.index'));
        $this->assertDatabaseHas('locations', ['code' => 'LOC-NEW']);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.locations.store'), []);

        $response->assertSessionHasErrors(['code', 'name', 'status']);
    }

    public function test_store_validates_email_format(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.locations.store'), [
            'code' => 'NEW',
            'name' => 'テスト',
            'email' => 'not-an-email',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['email']);
    }

    public function test_update_modifies_location(): void
    {
        $location = Location::factory()->createOne();

        $response = $this->actingAs($this->admin)->put(route('admin.locations.update', $location), [
            'code' => $location->code,
            'name' => '更新店舗',
            'status' => 'inactive',
        ]);

        $response->assertRedirect(route('admin.locations.index'));
        $this->assertDatabaseHas('locations', ['id' => $location->id, 'name' => '更新店舗']);
    }

    public function test_destroy_deletes_location(): void
    {
        $location = Location::factory()->createOne();

        $response = $this->actingAs($this->admin)->delete(route('admin.locations.destroy', $location));

        $response->assertRedirect(route('admin.locations.index'));
        $this->assertDatabaseMissing('locations', ['id' => $location->id]);
    }
}
