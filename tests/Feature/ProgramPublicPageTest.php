<?php

namespace Tests\Feature;

use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * 公開プログラム一覧・詳細（HTMX 部分テンプレート含む）
 *
 * テスト観点表:
 *
 * | Case ID   | Input / Precondition        | Perspective              | Expected Result                          |
 * |-----------|-----------------------------|--------------------------|------------------------------------------|
 * | TC-N-01   | ゲスト、active プログラム1件 | Equivalence – normal     | 一覧 200、pages.programs.index           |
 * | TC-N-02   | HX-Request で一覧           | Equivalence – HTMX       | partials.programs.list、DOCTYPE なし       |
 * | TC-N-03   | active と inactive が混在   | Equivalence – filter     | 一覧に active のみ                       |
 * | TC-N-04   | active プログラムの show    | Equivalence – normal     | 200、pages.programs.show                 |
 * | TC-N-05   | HX-Request で show        | Equivalence – HTMX       | partials.programs.detail、DOCTYPE なし |
 * | TC-A-01   | inactive の show URL        | Boundary – inactive      | 404                                      |
 * | TC-A-02   | 存在しない code             | Boundary – missing       | 404                                      |
 */
class ProgramPublicPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_displays_active_programs_for_guest(): void
    {
        $active = Program::factory()->createOne([
            'status' => Program::STATUS_ACTIVE,
            'name' => '公開ヨガ初級',
        ]);

        $response = $this->get(route('programs.index'));

        $response->assertOk();
        $response->assertViewIs('pages.programs.index');
        $response->assertSee('公開ヨガ初級', false);
        $response->assertViewHas('programs', fn ($programs) => $programs->contains('id', $active->id));
    }

    public function test_index_with_htmx_returns_list_partial_without_full_layout(): void
    {
        Program::factory()->createOne(['status' => Program::STATUS_ACTIVE]);

        $response = $this->withHeader('HX-Request', 'true')
            ->get(route('programs.index'));

        $response->assertOk();
        $response->assertViewIs('partials.programs.list');
        $response->assertDontSee('<!DOCTYPE html>', false);
    }

    public function test_index_lists_only_active_programs(): void
    {
        $active = Program::factory()->createOne([
            'status' => Program::STATUS_ACTIVE,
            'name' => '表示されるプログラム',
        ]);
        Program::factory()->createOne([
            'status' => Program::STATUS_INACTIVE,
            'name' => '非表示プログラム',
        ]);

        $response = $this->get(route('programs.index'));

        $response->assertOk();
        $response->assertSee('表示されるプログラム', false);
        $response->assertDontSee('非表示プログラム', false);
        $response->assertViewHas('programs', fn ($programs) => $programs->count() === 1
            && $programs->first()->is($active));
    }

    public function test_show_displays_program_detail_for_active_program(): void
    {
        $program = Program::factory()->createOne([
            'status' => Program::STATUS_ACTIVE,
            'name' => '詳細ページテスト',
        ]);

        $response = $this->get(route('programs.show', $program));

        $response->assertOk();
        $response->assertViewIs('pages.programs.show');
        $response->assertViewHas('program', fn ($p) => $p->is($program));
        $response->assertSee('詳細ページテスト', false);
    }

    public function test_show_with_htmx_returns_detail_partial_without_full_layout(): void
    {
        $program = Program::factory()->createOne([
            'status' => Program::STATUS_ACTIVE,
            'name' => 'HTMX詳細',
        ]);

        $response = $this->withHeader('HX-Request', 'true')
            ->get(route('programs.show', $program));

        $response->assertOk();
        $response->assertViewIs('partials.programs.detail');
        $response->assertDontSee('<!DOCTYPE html>', false);
        $response->assertSee('HTMX詳細', false);
    }

    public function test_show_returns_not_found_for_inactive_program(): void
    {
        $program = Program::factory()->createOne([
            'status' => Program::STATUS_INACTIVE,
        ]);

        $response = $this->get(route('programs.show', $program));

        $response->assertNotFound();
    }

    public function test_show_returns_not_found_for_unknown_code(): void
    {
        $response = $this->get('/programs/PRG000000');

        $response->assertNotFound();
    }
}
