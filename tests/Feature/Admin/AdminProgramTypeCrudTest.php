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
        $admin = User::factory()->createOne(['role' => 'admin']);
        $this->admin = $admin;
    }

    public function test_index_displays_program_types(): void
    {
        $programType = ProgramType::factory()->createOne();

        $response = $this->actingAs($this->admin)->get(route('admin.program-types.index'));

        $response->assertOk();
        $response->assertSeeText($programType->name);
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
}
