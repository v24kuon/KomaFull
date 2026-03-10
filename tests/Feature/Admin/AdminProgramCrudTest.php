<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\LessonSession;
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

    public function test_validation_errors_are_rendered_with_alert_role(): void
    {
        $response = $this->actingAs($this->admin)
            ->from(route('admin.programs.create'))
            ->followingRedirects()
            ->post(route('admin.programs.store'), []);

        $response->assertOk();
        $response->assertSee('class="invalid-feedback" role="alert"', false);
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

    public function test_update_rejects_duplicate_code(): void
    {
        $program = Program::factory()->createOne([
            'code' => 'PRG-001',
            'name' => '更新前プログラム',
        ]);
        Program::factory()->createOne(['code' => 'PRG-002']);

        $response = $this->actingAs($this->admin)->put(route('admin.programs.update', $program), [
            'code' => 'PRG-002',
            'category_id' => $program->category_id,
            'program_type_id' => $program->program_type_id,
            'name' => '更新後プログラム',
            'duration_minutes' => 90,
            'price' => 5000,
            'point_cost' => 2,
            'ticket_cost' => 2,
            'status' => 'inactive',
        ]);

        $response->assertSessionHasErrors(['code']);
        $this->assertDatabaseHas('programs', [
            'id' => $program->id,
            'code' => 'PRG-001',
            'name' => '更新前プログラム',
        ]);
    }

    public function test_destroy_deletes_program(): void
    {
        $program = Program::factory()->createOne();

        $response = $this->actingAs($this->admin)->delete(route('admin.programs.destroy', $program));

        $response->assertRedirect(route('admin.programs.index'));
        $this->assertDatabaseMissing('programs', ['id' => $program->id]);
    }

    public function test_destroy_fails_with_error_message_when_program_has_related_lesson_session(): void
    {
        $program = Program::factory()->createOne();
        LessonSession::factory()->createOne(['program_id' => $program->id]);

        $response = $this->actingAs($this->admin)->delete(route('admin.programs.destroy', $program));

        $response->assertRedirect(route('admin.programs.index'));
        $response->assertSessionHas('error', fn (string $message): bool => str_contains($message, '削除できません'));
        $this->assertDatabaseHas('programs', ['id' => $program->id]);
    }

    public function test_destroy_with_htmx_returns_error_row_when_program_has_related_lesson_session(): void
    {
        $program = Program::factory()->createOne();
        LessonSession::factory()->createOne(['program_id' => $program->id]);

        $response = $this->actingAs($this->admin)
            ->withHeader('HX-Request', 'true')
            ->delete(route('admin.programs.destroy', $program));

        $response->assertOk();
        $response->assertSeeText('削除できません');
        $response->assertSeeText('レッスン枠・繰り返しルール');
        $response->assertSee('role="alert"', false);
        $response->assertSee('id="program-row-'.$program->id.'"', false);
        $this->assertDatabaseHas('programs', ['id' => $program->id]);
    }

    public function test_guest_cannot_access_programs(): void
    {
        $response = $this->get(route('admin.programs.index'));

        $response->assertRedirect(route('login'));
    }

    public function test_non_admin_cannot_access_programs(): void
    {
        /** @var User $member */
        $member = User::factory()->createOne(['role' => User::ROLE_MEMBER]);

        $response = $this->actingAs($member)->get(route('admin.programs.index'));

        $response->assertForbidden();
    }
}
