<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProgramRequest;
use App\Http\Requests\Admin\UpdateProgramRequest;
use App\Models\Category;
use App\Models\Program;
use App\Models\ProgramType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramController extends Controller
{
    /**
     * プログラム一覧を表示し、HTMX要求時は一覧テーブルのみ返す。
     *
     * 前提: 管理者認可済みのルートから呼び出されること。
     * 更新方針: 読み取り専用で、DB更新は行わない。
     */
    public function index(Request $request): View|string
    {
        $programs = Program::query()
            ->with(['category', 'programType'])
            ->orderBy('id', 'desc')
            ->get();

        if ($request->header('HX-Request')) {
            return view('admin.programs._table', compact('programs'))->render();
        }

        return view('admin.programs.index', compact('programs'));
    }

    /**
     * プログラム新規作成フォームを表示する。
     *
     * 前提: 管理者認可済みのルートから呼び出されること。
     * 更新方針: フォーム表示に必要なマスタのみ取得し、DB更新は行わない。
     */
    public function create(): View
    {
        $formMasterData = $this->resolveFormMasterData();

        return view('admin.programs.create', $formMasterData);
    }

    /**
     * バリデーション済み入力を用いてプログラムを新規作成する。
     *
     * 前提: StoreProgramRequest で入力検証済みであること。
     * 更新方針: validated() のみを create に渡して保存する。
     */
    public function store(StoreProgramRequest $request): RedirectResponse
    {
        Program::create($request->validated());

        return redirect()->route('admin.programs.index')
            ->with('success', 'プログラムを作成しました。');
    }

    /**
     * プログラム編集フォームを表示する。
     *
     * 前提: ルートモデルバインディングで対象レコードが解決されること。
     * 更新方針: フォーム表示に必要なマスタのみ取得し、DB更新は行わない。
     */
    public function edit(Program $program): View
    {
        $formMasterData = $this->resolveFormMasterData();

        return view('admin.programs.edit', [
            'program' => $program,
            'categories' => $formMasterData['categories'],
            'programTypes' => $formMasterData['programTypes'],
        ]);
    }

    /**
     * バリデーション済み入力で対象プログラムを更新する。
     *
     * 前提: UpdateProgramRequest で入力検証済みであること。
     * 更新方針: validated() のみを update に渡して保存する。
     */
    public function update(UpdateProgramRequest $request, Program $program): RedirectResponse
    {
        $program->update($request->validated());

        return redirect()->route('admin.programs.index')
            ->with('success', 'プログラムを更新しました。');
    }

    /**
     * 対象プログラムを削除し、HTMX要求時は空レスポンスを返す。
     *
     * 前提: ルートモデルバインディングで対象レコードが解決されること。
     * 更新方針: 対象レコードを delete したうえで応答形式を分岐する。
     */
    public function destroy(Request $request, Program $program): RedirectResponse|string
    {
        $program->delete();

        if ($request->header('HX-Request')) {
            return '';
        }

        return redirect()->route('admin.programs.index')
            ->with('success', 'プログラムを削除しました。');
    }

    /**
     * プログラムフォームで利用するマスタデータを取得する。
     *
     * @return array{
     *     categories: \Illuminate\Database\Eloquent\Collection<int, Category>,
     *     programTypes: \Illuminate\Database\Eloquent\Collection<int, ProgramType>
     * }
     */
    private function resolveFormMasterData(): array
    {
        return [
            'categories' => Category::query()->orderBy('sort_order')->get(),
            'programTypes' => ProgramType::query()->orderBy('sort_order')->get(),
        ];
    }
}
