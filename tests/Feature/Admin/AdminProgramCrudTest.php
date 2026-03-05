<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Program;
use App\Models\ProgramType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminProgramCrudTest extends TestCase
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

    public function test_index_displays_programs_with_relations(): void
    {
        $program = Program::factory()->createOne();

        $response = $this->actingAs($this->admin)->get(route('admin.programs.index'));

        $response->assertOk();
        $response->assertSeeText($program->name);
        $response->assertSeeText($program->category->name);
    }

    public function test_create_form_shows_category_and_type_selects(): void
    {
        $category = Category::factory()->createOne();
        $programType = ProgramType::factory()->createOne();

        $response = $this->actingAs($this->admin)->get(route('admin.programs.create'));

        $response->assertOk();
        $response->assertSee($category->name);
        $response->assertSee($programType->name);
    }

    public function test_store_creates_program(): void
    {
        $category = Category::factory()->createOne();
        $programType = ProgramType::factory()->createOne();

        $data = [
            'code' => 'PRG-NEW',
            'category_id' => $category->id,
            'program_type_id' => $programType->id,
            'name' => 'テストプログラム',
            'duration_minutes' => 60,
            'price' => 3000,
            'point_cost' => 1,
            'ticket_cost' => 1,
            'status' => 'active',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.programs.store'), $data);

        $response->assertRedirect(route('admin.programs.index'));
        $this->assertDatabaseHas('programs', ['code' => 'PRG-NEW', 'name' => 'テストプログラム']);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.programs.store'), []);

        $response->assertSessionHasErrors(['code', 'category_id', 'program_type_id', 'name', 'duration_minutes', 'price', 'point_cost', 'ticket_cost', 'status']);
    }

    public function test_store_validates_foreign_keys(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.programs.store'), [
            'code' => 'TEST',
            'category_id' => 99999,
            'program_type_id' => 99999,
            'name' => 'テスト',
            'duration_minutes' => 60,
            'price' => 0,
            'point_cost' => 0,
            'ticket_cost' => 0,
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['category_id', 'program_type_id']);
    }

    public function test_update_modifies_program(): void
    {
        $program = Program::factory()->createOne();

        $response = $this->actingAs($this->admin)->put(route('admin.programs.update', $program), [
            'code' => $program->code,
            'category_id' => $program->category_id,
            'program_type_id' => $program->program_type_id,
            'name' => '更新プログラム',
            'duration_minutes' => 90,
            'price' => 5000,
            'point_cost' => 2,
            'ticket_cost' => 2,
            'status' => 'inactive',
        ]);

        $response->assertRedirect(route('admin.programs.index'));
        $this->assertDatabaseHas('programs', ['id' => $program->id, 'name' => '更新プログラム']);
    }

    public function test_destroy_deletes_program(): void
    {
        $program = Program::factory()->createOne();

        $response = $this->actingAs($this->admin)->delete(route('admin.programs.destroy', $program));

        $response->assertRedirect(route('admin.programs.index'));
        $this->assertDatabaseMissing('programs', ['id' => $program->id]);
    }
}
