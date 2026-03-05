<?php

namespace Tests\Feature\Admin;

use App\Models\ProgramType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProgramTypeCrudTest extends TestCase
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

    public function test_index_displays_program_types(): void
    {
        $programType = ProgramType::factory()->createOne();

        $response = $this->actingAs($this->admin)->get(route('admin.program-types.index'));

        $response->assertOk();
        $response->assertSeeText($programType->name);
        $response->assertSeeText('プログラム種別管理');
    }

    public function test_index_returns_partial_for_htmx(): void
    {
        ProgramType::factory()->createOne();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.program-types.index'), ['HX-Request' => 'true']);

        $response->assertOk();
        $response->assertDontSee('<!DOCTYPE html>');
    }

    public function test_create_form_is_displayed(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.program-types.create'));

        $response->assertOk();
        $response->assertSeeText('プログラム種別作成');
    }

    public function test_store_creates_program_type(): void
    {
        $data = [
            'code' => 'PT-NEW',
            'name' => 'テスト種別',
            'sort_order' => 1,
            'status' => 'active',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.program-types.store'), $data);

        $response->assertRedirect(route('admin.program-types.index'));
        $this->assertDatabaseHas('program_types', ['code' => 'PT-NEW']);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.program-types.store'), []);

        $response->assertSessionHasErrors(['code', 'name', 'sort_order', 'status']);
    }

    public function test_store_validates_unique_code(): void
    {
        ProgramType::factory()->createOne(['code' => 'EXISTING']);

        $response = $this->actingAs($this->admin)->post(route('admin.program-types.store'), [
            'code' => 'EXISTING',
            'name' => '重複テスト',
            'sort_order' => 0,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['code']);
    }

    public function test_store_validates_invalid_status(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.program-types.store'), [
            'code' => 'NEW',
            'name' => 'テスト',
            'sort_order' => 0,
            'status' => 'invalid',
        ]);

        $response->assertSessionHasErrors(['status']);
    }

    public function test_edit_form_is_displayed(): void
    {
        $programType = ProgramType::factory()->createOne();

        $response = $this->actingAs($this->admin)->get(route('admin.program-types.edit', $programType));

        $response->assertOk();
        $response->assertSeeText('プログラム種別編集');
        $response->assertSee($programType->name);
    }

    public function test_update_modifies_program_type(): void
    {
        $programType = ProgramType::factory()->createOne();

        $response = $this->actingAs($this->admin)->put(route('admin.program-types.update', $programType), [
            'code' => $programType->code,
            'name' => '更新後種別',
            'sort_order' => 99,
            'status' => 'inactive',
        ]);

        $response->assertRedirect(route('admin.program-types.index'));
        $this->assertDatabaseHas('program_types', ['id' => $programType->id, 'name' => '更新後種別']);
    }

    public function test_destroy_deletes_program_type(): void
    {
        $programType = ProgramType::factory()->createOne();

        $response = $this->actingAs($this->admin)->delete(route('admin.program-types.destroy', $programType));

        $response->assertRedirect(route('admin.program-types.index'));
        $this->assertDatabaseMissing('program_types', ['id' => $programType->id]);
    }

    public function test_destroy_with_htmx_returns_empty(): void
    {
        $programType = ProgramType::factory()->createOne();

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.program-types.destroy', $programType), [], ['HX-Request' => 'true']);

        $response->assertOk();
        $this->assertEquals('', $response->getContent());
        $this->assertDatabaseMissing('program_types', ['id' => $programType->id]);
    }

    public function test_guest_cannot_access_program_types(): void
    {
        $response = $this->get(route('admin.program-types.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_program_types(): void
    {
        /** @var User $member */
        $member = User::factory()->createOne(['role' => User::ROLE_MEMBER]);

        $response = $this->actingAs($member)->get(route('admin.program-types.index'));

        $response->assertForbidden();
    }
}
