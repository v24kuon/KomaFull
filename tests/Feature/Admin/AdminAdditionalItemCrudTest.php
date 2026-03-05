<?php

namespace Tests\Feature\Admin;

use App\Models\AdditionalItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAdditionalItemCrudTest extends TestCase
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

    public function test_index_displays_additional_items(): void
    {
        $item = AdditionalItem::factory()->createOne();

        $response = $this->actingAs($this->admin)->get(route('admin.additional-items.index'));

        $response->assertOk();
        $response->assertSeeText($item->label_name);
    }

    public function test_store_creates_additional_item(): void
    {
        $data = [
            'code' => 'AI-NEW',
            'additional_item_type' => 'member_profile',
            'label_name' => 'テスト項目',
            'input_type' => 'text',
            'digits' => 5,
            'status' => 'active',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.additional-items.store'), $data);

        $response->assertRedirect(route('admin.additional-items.index'));
        $this->assertDatabaseHas('additional_items', ['code' => 'AI-NEW', 'label_name' => 'テスト項目']);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.additional-items.store'), []);

        $response->assertSessionHasErrors(['code', 'additional_item_type', 'label_name', 'input_type', 'status']);
    }

    public function test_store_validates_input_type(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.additional-items.store'), [
            'code' => 'NEW',
            'additional_item_type' => 'member_profile',
            'label_name' => 'テスト',
            'input_type' => 'invalid',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['input_type']);
    }

    public function test_update_modifies_additional_item(): void
    {
        $item = AdditionalItem::factory()->createOne();

        $response = $this->actingAs($this->admin)->put(route('admin.additional-items.update', $item), [
            'code' => $item->code,
            'additional_item_type' => 'member_profile',
            'label_name' => '更新項目',
            'input_type' => 'number',
            'status' => 'inactive',
        ]);

        $response->assertRedirect(route('admin.additional-items.index'));
        $this->assertDatabaseHas('additional_items', ['id' => $item->id, 'label_name' => '更新項目']);
    }

    public function test_destroy_deletes_additional_item(): void
    {
        $item = AdditionalItem::factory()->createOne();

        $response = $this->actingAs($this->admin)->delete(route('admin.additional-items.destroy', $item));

        $response->assertRedirect(route('admin.additional-items.index'));
        $this->assertDatabaseMissing('additional_items', ['id' => $item->id]);
    }
}
