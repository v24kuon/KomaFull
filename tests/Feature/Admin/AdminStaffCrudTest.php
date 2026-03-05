<?php

namespace Tests\Feature\Admin;

use App\Models\LessonSession;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminStaffCrudTest extends TestCase
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

    public function test_index_displays_staffs(): void
    {
        $staff = Staff::factory()->createOne();

        $response = $this->actingAs($this->admin)->get(route('admin.staffs.index'));

        $response->assertOk();
        $response->assertSeeText($staff->name);
    }

    public function test_store_creates_staff(): void
    {
        $data = [
            'code' => 'STF-NEW',
            'name' => 'テストスタッフ',
            'gender' => 'female',
            'role' => 'インストラクター',
            'status' => 'active',
        ];

        $response = $this->actingAs($this->admin)->post(route('admin.staffs.store'), $data);

        $response->assertRedirect(route('admin.staffs.index'));
        $this->assertDatabaseHas('staffs', ['code' => 'STF-NEW']);
    }

    public function test_store_validates_required_fields(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.staffs.store'), []);

        $response->assertSessionHasErrors(['code', 'name', 'status']);
    }

    public function test_store_validates_birth_date_format(): void
    {
        $response = $this->actingAs($this->admin)->post(route('admin.staffs.store'), [
            'code' => 'NEW',
            'name' => 'テスト',
            'birth_date' => 'invalid-date',
            'status' => 'active',
        ]);

        $response->assertSessionHasErrors(['birth_date']);
    }

    public function test_update_modifies_staff(): void
    {
        $staff = Staff::factory()->createOne();

        $response = $this->actingAs($this->admin)->put(route('admin.staffs.update', $staff), [
            'code' => $staff->code,
            'name' => '更新スタッフ',
            'status' => 'inactive',
        ]);

        $response->assertRedirect(route('admin.staffs.index'));
        $this->assertDatabaseHas('staffs', ['id' => $staff->id, 'name' => '更新スタッフ']);
    }

    public function test_destroy_deletes_staff(): void
    {
        $staff = Staff::factory()->createOne();

        $response = $this->actingAs($this->admin)->delete(route('admin.staffs.destroy', $staff));

        $response->assertRedirect(route('admin.staffs.index'));
        $this->assertDatabaseMissing('staffs', ['id' => $staff->id]);
    }

    public function test_destroy_fails_with_error_message_when_staff_has_related_lesson_session(): void
    {
        $staff = Staff::factory()->createOne();
        LessonSession::factory()->createOne(['staff_id' => $staff->id]);

        $response = $this->actingAs($this->admin)->delete(route('admin.staffs.destroy', $staff));

        $response->assertRedirect(route('admin.staffs.index'));
        $response->assertSessionHas('error', fn (string $message): bool => str_contains($message, '削除できません'));
        $this->assertDatabaseHas('staffs', ['id' => $staff->id]);
    }

    public function test_destroy_with_htmx_returns_error_row_when_staff_has_related_lesson_session(): void
    {
        $staff = Staff::factory()->createOne();
        LessonSession::factory()->createOne(['staff_id' => $staff->id]);

        $response = $this->actingAs($this->admin)
            ->withHeader('HX-Request', 'true')
            ->delete(route('admin.staffs.destroy', $staff));

        $response->assertOk();
        $response->assertSeeText('削除できません');
        $response->assertSeeText('レッスン枠・繰り返しルール');
        $response->assertSee('id="staff-row-'.$staff->id.'"', false);
        $this->assertDatabaseHas('staffs', ['id' => $staff->id]);
    }
}
