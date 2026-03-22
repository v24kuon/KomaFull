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
