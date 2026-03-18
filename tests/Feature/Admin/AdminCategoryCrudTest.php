<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Program;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCategoryCrudTest extends TestCase
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

    public function test_index_displays_categories(): void
    {
        $category = Category::factory()->createOne();

        $response = $this->actingAs($this->admin)->get(route('admin.categories.index'));

        $response->assertOk();
        $response->assertSeeText($category->name);
        $response->assertSeeText('カテゴリ管理');
    }

    public function test_index_returns_partial_for_htmx(): void
    {
        Category::factory()->createOne();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.categories.index'), ['HX-Request' => 'true']);

        $response->assertOk();
        $response->assertDontSee('<!DOCTYPE html>', false);
    }

    public function test_create_form_is_displayed(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.categories.create'));

        $response->assertOk();
        $response->assertSeeText('カテゴリ作成');
    }

    public function test_store_creates_category(): void
    {
        $data = [
            'code' => 'CAT-NEW',
            'name' => 'テストカテゴリ',
            'sort_order' => 5,
            'status' => 'active',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.categories.store'), $data);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', ['code' => 'CAT-NEW', 'name' => 'テストカテゴリ']);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.categories.store'), []);

        $response->assertSessionHasErrors(['code', 'name', 'sort_order', 'status']);
    }

    public function test_validation_errors_are_rendered_with_alert_role(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('admin.categories.create'))
            ->followingRedirects()
            ->post(route('admin.categories.store'), []);

        $response->assertOk();
        $response->assertSee('class="invalid-feedback" role="alert"', false);
    }

    public function test_store_validates_unique_code(): void
    {
        Category::factory()->createOne(['code' => 'EXISTING']);

        $response = $this->actingAs($this->admin)->post(route('admin.categories.store'), [
            'code' => 'EXISTING',
            'name' => '重複テスト',
            'sort_order' => 0,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    public function test_store_validates_invalid_status(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.categories.store'), [
            'code' => 'NEW',
            'name' => 'テスト',
            'sort_order' => 0,
            'status' => 'invalid',
        ]);

        $response->assertSessionHasErrors(['status']);
    }

    public function test_edit_form_is_displayed(): void
    {
        $category = Category::factory()->createOne();

        $response = $this->actingAs($this->admin)->get(route('admin.categories.edit', $category));

        $response->assertOk();
        $response->assertSeeText('カテゴリ編集');
        $response->assertSee($category->name);
    }

    public function test_update_modifies_category(): void
    {
        $category = Category::factory()->createOne();

        $response = $this->actingAs($this->admin)->put(route('admin.categories.update', $category), [
            'code' => $category->code,
            'name' => '更新後カテゴリ',
            'sort_order' => 10,
            'status' => 'inactive',
        ]);

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => '更新後カテゴリ', 'status' => 'inactive']);
    }

    public function test_update_allows_same_code(): void
    {
        $category = Category::factory()->createOne(['code' => 'SAME']);

        $response = $this->actingAs($this->admin)->put(route('admin.categories.update', $category), [
            'code' => 'SAME',
            'name' => '名前変更',
            'sort_order' => 0,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('admin.categories.index'));
    }

    public function test_update_rejects_duplicate_code(): void
    {
        $category = Category::factory()->createOne([
            'code' => 'CAT-001',
            'name' => '更新前カテゴリ',
            'sort_order' => 1,
            'status' => 'active',
        ]);
        Category::factory()->createOne(['code' => 'CAT-002']);

        $response = $this->actingAs($this->admin)->put(route('admin.categories.update', $category), [
            'code' => 'CAT-002',
            'name' => '更新後カテゴリ',
            'sort_order' => 10,
            'status' => 'inactive',
        ]);

        $response->assertSessionHasErrors(['code']);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'code' => 'CAT-001',
            'name' => '更新前カテゴリ',
            'sort_order' => 1,
            'status' => 'active',
        ]);
    }

    public function test_destroy_deletes_category(): void
    {
        $category = Category::factory()->createOne();

        $response = $this->actingAs($this->admin)->delete(route('admin.categories.destroy', $category));

        $response->assertRedirect(route('admin.categories.index'));
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_destroy_fails_with_error_message_when_category_has_related_programs(): void
    {
        $category = Category::factory()->createOne();
        Program::factory()->createOne(['category_id' => $category->id]);

        $response = $this->actingAs($this->admin)->delete(route('admin.categories.destroy', $category));

        $response->assertRedirect(route('admin.categories.index'));
        $response->assertSessionHas('error', fn (string $message): bool => str_contains($message, '削除できません'));
        $response->assertSessionHas('error', fn (string $message): bool => str_contains($message, '関連プログラム'));
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_destroy_with_htmx_returns_error_row_when_category_has_related_programs(): void
    {
        $category = Category::factory()->createOne();
        Program::factory()->createOne(['category_id' => $category->id]);

        $response = $this->actingAs($this->admin)
            ->withHeader('HX-Request', 'true')
            ->delete(route('admin.categories.destroy', $category));

        $response->assertOk();
        $response->assertSeeText('削除できません');
        $response->assertSeeText('関連プログラム');
        $response->assertSee('role="alert"', false);
        $response->assertSee('id="category-row-'.$category->id.'"', false);
        $this->assertDatabaseHas('categories', ['id' => $category->id]);
    }

    public function test_destroy_with_htmx_returns_empty(): void
    {
        $category = Category::factory()->createOne();

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.categories.destroy', $category), [], ['HX-Request' => 'true']);

        $response->assertOk();
        $this->assertEquals('', $response->getContent());
        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }

    public function test_guest_cannot_access_categories(): void
    {
        $response = $this->get(route('admin.categories.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_categories(): void
    {
        /** @var User $member */
        $member = User::factory()->createOne(['role' => User::ROLE_MEMBER]);

        $response = $this->actingAs($member)->get(route('admin.categories.index'));

        $response->assertForbidden();
    }
}
