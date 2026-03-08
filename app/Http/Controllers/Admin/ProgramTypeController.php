<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProgramTypeRequest;
use App\Http\Requests\Admin\UpdateProgramTypeRequest;
use App\Models\ProgramType;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramTypeController extends Controller
{
    private const DELETE_CONSTRAINT_MESSAGE = '関連データが存在するためプログラム種別を削除できません。先に関連プログラムを削除してください。';

    /**
     * プログラム種別一覧を表示し、HTMX要求時は一覧テーブルのみ返す。
     *
     * 前提: 管理者認可済みのルートから呼び出されること。
     * 更新方針: 読み取り専用で、DB更新は行わない。
     */
    public function index(Request $request): View|string
    {
        $programTypes = ProgramType::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($request->header('HX-Request')) {
            return view('admin.program-types._table', compact('programTypes'))->render();
        }

        return view('admin.program-types.index', compact('programTypes'));
    }

    /**
     * プログラム種別の新規作成フォームを表示する。
     *
     * 前提: 管理者認可済みのルートから呼び出されること。
     * 更新方針: 画面表示のみを行い、DB更新は行わない。
     */
    public function create(): View
    {
        return view('admin.program-types.create');
    }

    /**
     * バリデーション済み入力を用いてプログラム種別を新規作成する。
     *
     * 前提: StoreProgramTypeRequest で入力検証済みであること。
     * 更新方針: validated() のみを create に渡して保存する。
     */
    public function store(StoreProgramTypeRequest $request): RedirectResponse
    {
        ProgramType::create($request->validated());

        return redirect()->route('admin.program-types.index')
            ->with('success', 'プログラム種別を作成しました。');
    }

    /**
     * プログラム種別の編集フォームを表示する。
     *
     * 前提: ルートモデルバインディングで対象レコードが解決されること。
     * 更新方針: 画面表示のみを行い、DB更新は行わない。
     */
    public function edit(ProgramType $programType): View
    {
        return view('admin.program-types.edit', compact('programType'));
    }

    /**
     * バリデーション済み入力で対象プログラム種別を更新する。
     *
     * 前提: UpdateProgramTypeRequest で入力検証済みであること。
     * 更新方針: validated() のみを update に渡して保存する。
     */
    public function update(UpdateProgramTypeRequest $request, ProgramType $programType): RedirectResponse
    {
        $programType->update($request->validated());

        return redirect()->route('admin.program-types.index')
            ->with('success', 'プログラム種別を更新しました。');
    }

    /**
     * 対象プログラム種別を削除し、HTMX要求時は空レスポンスを返す。
     *
     * 前提: ルートモデルバインディングで対象レコードが解決されること。
     * 更新方針: 対象レコードを delete したうえで応答形式を分岐する。FK制約違反時は利用者向けエラーを返す。
     */
    public function destroy(Request $request, ProgramType $programType): RedirectResponse|string
    {
        try {
            $programType->delete();
        } catch (QueryException $exception) {
            if ($this->isForeignKeyConstraintViolation($exception)) {
                return $this->respondDeleteConstraintViolation($request, $programType);
            }

            throw $exception;
        }

        if ($request->header('HX-Request')) {
            return '';
        }

        return redirect()->route('admin.program-types.index')
            ->with('success', 'プログラム種別を削除しました。');
    }

    /**
     * FK制約違反時の削除失敗レスポンスを要求種別に応じて返す。
     *
     * 前提: destroy() から外部キー制約違反として判定された場合のみ呼び出されること。
     * 更新方針: HTMX要求では対象行のエラー表示HTMLを返し、通常要求では一覧画面へリダイレクトしてエラーフラッシュを設定する。
     */
    private function respondDeleteConstraintViolation(Request $request, ProgramType $programType): RedirectResponse|string
    {
        if ($request->header('HX-Request')) {
            return view('admin.program-types._delete_error_row', [
                'programType' => $programType,
                'message' => self::DELETE_CONSTRAINT_MESSAGE,
            ])->render();
        }

        return redirect()->route('admin.program-types.index')
            ->with('error', self::DELETE_CONSTRAINT_MESSAGE);
    }
}
