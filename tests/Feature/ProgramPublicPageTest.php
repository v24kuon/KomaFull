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
 * | Case ID | Input / Precondition | Perspective (Equivalence / Boundary) | Expected Result | Notes |
 * |---------|----------------------|----------------------------------------|-----------------|-------|
 * | TC-N-01 | ゲスト、active プログラム1件 | Equivalence – normal | 一覧 200、pages.programs.index | - |
 * | TC-N-02 | HX-Request で一覧 | Equivalence – HTMX | partials.programs.list、DOCTYPE なし | - |
 * | TC-N-03 | active と inactive が混在 | Equivalence – filter | 一覧に active のみ | - |
 * | TC-N-06 | active 0 件（inactive のみ） | Boundary – empty list | 一覧 200、空状態メッセージ表示 | 空配列は 200（失敗系ではない） |
 * | TC-N-04 | active プログラムの show | Equivalence – normal | 200、pages.programs.show | - |
 * | TC-N-05 | HX-Request で show | Equivalence – HTMX | partials.programs.detail、DOCTYPE なし | - |
 * | TC-A-01 | inactive の show URL | Boundary – inactive | 404 | `show` は active のみ。HX 有無は 404 前に同一分岐 |
 * | TC-A-02 | 存在しない code | Boundary – missing | 404 | ルート解決失敗 |
 *
 * 失敗系件数について: `.cursor/rules/test-strategy.mdc` は「正常系と同数以上の失敗系」を原則とするが、本コントローラは読み取り専用の GET のみでバリデーション・ミューテーション・外部 API がなく、HTTP エラーとして意味のある失敗経路は TC-A-01・TC-A-02 の 404 のみである。分岐網羅は上記で満たし、同ルール「達成が合理的でない場合は主要なエラー経路を優先」「未カバーは Notes または PR 本文に明示」に従い、数合わせの冗長テストは追加しない。
 */
class ProgramPublicPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * TC-N-01: ゲストで active が1件のとき一覧が 200・pages.programs.index となる。
     */
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

    /**
     * TC-N-02: HX-Request で一覧を求めると partials.programs.list のみで DOCTYPE を含まない。
     */
    public function test_index_with_htmx_returns_list_partial_without_full_layout(): void
    {
        Program::factory()->createOne(['status' => Program::STATUS_ACTIVE]);

        $response = $this->withHeader('HX-Request', 'true')
            ->get(route('programs.index'));

        $response->assertOk();
        $response->assertViewIs('partials.programs.list');
        $response->assertDontSee('<!DOCTYPE html>', false);
    }

    /**
     * TC-N-03: active と inactive が混在するとき一覧には active のみ含まれる。
     */
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

    /**
     * TC-N-06: active が0件（inactive のみ）のとき一覧は 200・空状態メッセージとなる。
     */
    public function test_index_displays_empty_state_when_no_active_programs(): void
    {
        Program::factory()->createOne([
            'status' => Program::STATUS_INACTIVE,
            'name' => '一覧に出ないプログラム',
        ]);

        $response = $this->get(route('programs.index'));

        $response->assertOk();
        $response->assertViewIs('pages.programs.index');
        $response->assertSee('現在表示できるプログラムはありません。', false);
        $response->assertViewHas('programs', fn ($programs) => $programs->isEmpty());
        $response->assertDontSee('一覧に出ないプログラム', false);
    }

    /**
     * TC-N-04: active プログラムの show は 200・pages.programs.show となる。
     */
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

    /**
     * TC-N-05: HX-Request で show を求めると partials.programs.detail のみで DOCTYPE を含まない。
     */
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

    /**
     * TC-A-01: inactive のプログラムを show すると 404 となる。
     */
    public function test_show_returns_not_found_for_inactive_program(): void
    {
        $program = Program::factory()->createOne([
            'status' => Program::STATUS_INACTIVE,
        ]);

        $response = $this->get(route('programs.show', $program));

        $response->assertNotFound();
    }

    /**
     * TC-A-02: 存在しない code を show すると 404 となる。
     */
    public function test_show_returns_not_found_for_unknown_code(): void
    {
        $response = $this->get(route('programs.show', ['program' => 'PRG000000']));

        $response->assertNotFound();
    }
}
