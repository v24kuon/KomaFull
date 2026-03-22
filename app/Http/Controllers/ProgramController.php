<?php

namespace App\Http\Controllers;

use App\Models\Program;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramController extends Controller
{
    /**
     * 公開プログラム一覧を表示する。HTMX 要求時は一覧部分のみ返す。
     *
     * 前提: ステータスが active のプログラムのみ対象とする。
     * 更新方針: 読み取り専用。カテゴリの sort_order で並べ、同一カテゴリ内は名称順。
     */
    public function index(Request $request): View
    {
        $programs = $this->publicProgramsQuery()->get();

        if ($request->header('HX-Request')) {
            return view('partials.programs.list', compact('programs'));
        }

        return view('pages.programs.index', compact('programs'));
    }

    /**
     * 公開プログラム詳細を表示する。HTMX 要求時は詳細部分のみ返す（モーダル埋め込み用）。
     *
     * 前提: ルートモデルバインディングで code により解決されること。
     * 更新方針: inactive は 404 とする。
     */
    public function show(Request $request, Program $program): View
    {
        if ($program->status !== Program::STATUS_ACTIVE) {
            abort(404);
        }

        $program->load(['category', 'programType']);

        if ($request->header('HX-Request')) {
            return view('partials.programs.detail', compact('program'));
        }

        return view('pages.programs.show', compact('program'));
    }

    /**
     * 公開一覧用の Program クエリビルダを返す（実行は呼び出し側）。
     *
     * 責務: `Program::STATUS_ACTIVE` の行のみを対象にし、カテゴリの表示順（`categories.sort_order`、同順位は `categories.id`）で並べ、同一カテゴリ内は `programs.name` 昇順とする。一覧表示に必要な `category` / `programType` を eager load する。
     * 前提: `categories` と内部結合できること（カテゴリ未設定のプログラムは存在しない想定。存在する場合は一覧から落ちるため、データ投入ルールまたはスキーマで担保する）。
     * 設計判断: `join` は関連テーブル列による `ORDER BY` のためのみ。結果は `select('programs.*')` で `Program` に限定し、`with(['category', 'programType'])` は Eloquent の eager load 用（リレーションの代替として join しているわけではない）。純粋なリレーション API だけでは同一の並びを簡潔に表現しにくい場合の、クエリビルダ上の合理的な選択。
     * 更新方針: 読み取り専用の SELECT 用。並び・絞り込み・結合を変える場合は公開仕様（何を「掲載」とみなすか）と HTMX 一覧の期待を同時に確認する。`select('programs.*')` は join 時の列曖昧さ解消用であり、追加カラムが必要なら `addSelect` で明示する。
     *
     * @return Builder<Program>
     */
    private function publicProgramsQuery(): Builder
    {
        return Program::query()
            ->with(['category', 'programType'])
            ->where('programs.status', Program::STATUS_ACTIVE)
            ->join('categories', 'programs.category_id', '=', 'categories.id')
            ->orderBy('categories.sort_order')
            ->orderBy('categories.id')
            ->orderBy('programs.name')
            ->select('programs.*');
    }
}
