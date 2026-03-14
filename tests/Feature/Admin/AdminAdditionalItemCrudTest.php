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
            'status' => AdditionalItem::STATUS_ACTIVE,
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

    public function test_additional_item_type_validation_error_is_rendered_with_alert_role(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('admin.additional-items.create'))
            ->followingRedirects()
            ->post(route('admin.additional-items.store'), [
                'code' => 'AI-NEW',
                'label_name' => 'テスト項目',
                'input_type' => 'text',
                'status' => AdditionalItem::STATUS_ACTIVE,
            ]);

        $response->assertOk();
        $response->assertSeeText('項目種別は必須です。');
        $response->assertSee('role="alert"', false);
    }

    public function test_store_validates_input_type(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.additional-items.store'), [
            'code' => 'NEW',
            'additional_item_type' => 'member_profile',
            'label_name' => 'テスト',
            'input_type' => 'invalid',
            'status' => AdditionalItem::STATUS_ACTIVE,
        ]);

        $response->assertSessionHasErrors(['input_type']);
    }

    public function test_store_validates_unique_code(): void
    {
        AdditionalItem::factory()->createOne(['code' => 'EXISTING']);

        $response = $this->actingAs($this->admin)->post(route('admin.additional-items.store'), [
            'code' => 'EXISTING',
            'additional_item_type' => 'member_profile',
            'label_name' => '重複テスト',
            'input_type' => 'text',
            'status' => AdditionalItem::STATUS_ACTIVE,
        ]);

        $response->assertSessionHasErrors(['code']);
        $this->assertDatabaseCount('additional_items', 1);
    }

    public function test_update_modifies_additional_item(): void
    {
        $item = AdditionalItem::factory()->createOne();

        $response = $this->actingAs($this->admin)->put(route('admin.additional-items.update', $item), [
            'code' => $item->code,
            'additional_item_type' => 'member_profile',
            'label_name' => '更新項目',
            'input_type' => 'number',
            'status' => AdditionalItem::STATUS_INACTIVE,
        ]);

        $response->assertRedirect(route('admin.additional-items.index'));
        $this->assertDatabaseHas('additional_items', ['id' => $item->id, 'label_name' => '更新項目']);
    }

    public function test_update_validates_input_type(): void
    {
        $item = AdditionalItem::factory()->createOne([
            'code' => 'AI-INPUT-01',
            'label_name' => '更新前項目',
            'input_type' => 'text',
            'status' => AdditionalItem::STATUS_ACTIVE,
        ]);

        $response = $this->actingAs($this->admin)->put(route('admin.additional-items.update', $item), [
            'code' => $item->code,
            'additional_item_type' => 'member_profile',
            'label_name' => '更新後項目',
            'input_type' => 'invalid',
            'status' => AdditionalItem::STATUS_INACTIVE,
        ]);

        $response->assertSessionHasErrors(['input_type']);
        $this->assertDatabaseHas('additional_items', [
            'id' => $item->id,
            'code' => 'AI-INPUT-01',
            'label_name' => '更新前項目',
            'input_type' => 'text',
            'status' => AdditionalItem::STATUS_ACTIVE,
        ]);
    }

    public function test_update_rejects_duplicate_code(): void
    {
        $item = AdditionalItem::factory()->createOne([
            'code' => 'AI-001',
            'label_name' => '更新前項目',
            'status' => AdditionalItem::STATUS_ACTIVE,
        ]);
        AdditionalItem::factory()->createOne(['code' => 'AI-002']);

        $response = $this->actingAs($this->admin)->put(route('admin.additional-items.update', $item), [
            'code' => 'AI-002',
            'additional_item_type' => 'member_profile',
            'label_name' => '更新後項目',
            'input_type' => 'number',
            'status' => AdditionalItem::STATUS_INACTIVE,
        ]);

        $response->assertSessionHasErrors(['code']);
        $this->assertDatabaseHas('additional_items', [
            'id' => $item->id,
            'code' => 'AI-001',
            'label_name' => '更新前項目',
            'status' => AdditionalItem::STATUS_ACTIVE,
        ]);
    }

    public function test_destroy_deletes_additional_item(): void
    {
        $item = AdditionalItem::factory()->createOne();

        $response = $this->actingAs($this->admin)->delete(route('admin.additional-items.destroy', $item));

        $response->assertRedirect(route('admin.additional-items.index'));
        $this->assertDatabaseMissing('additional_items', ['id' => $item->id]);
    }
}
