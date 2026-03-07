<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Http\Requests\Admin\UpdateStaffRequest;
use App\Models\Staff;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffController extends Controller
{
    private const DELETE_CONSTRAINT_MESSAGE = '関連データが存在するためスタッフを削除できません。先に関連するレッスン枠・繰り返しルール等を削除してください。';

    /**
     * スタッフ一覧を表示し、HTMX要求時は一覧テーブルのみ返す。
     *
     * 前提: 管理者認可済みのルートから呼び出されること。
     * 更新方針: 読み取り専用で、DB更新は行わない。
     */
    public function index(Request $request): View|string
    {
        $staffs = Staff::query()->orderBy('id', 'desc')->get();

        if ($request->header('HX-Request')) {
            return view('admin.staffs._table', compact('staffs'))->render();
        }

        return view('admin.staffs.index', compact('staffs'));
    }

    /**
     * スタッフの新規作成フォームを表示する。
     *
     * 前提: 管理者認可済みのルートから呼び出されること。
     * 更新方針: 画面表示のみを行い、DB更新は行わない。
     */
    public function create(): View
    {
        return view('admin.staffs.create');
    }

    /**
     * バリデーション済み入力を用いてスタッフを新規作成する。
     *
     * 前提: StoreStaffRequest で入力検証済みであること。
     * 更新方針: validated() のみを create に渡して保存する。
     */
    public function store(StoreStaffRequest $request): RedirectResponse
    {
        Staff::create($request->validated());

        return redirect()->route('admin.staffs.index')
            ->with('success', 'スタッフを作成しました。');
    }

    /**
     * スタッフの編集フォームを表示する。
     *
     * 前提: ルートモデルバインディングで対象レコードが解決されること。
     * 更新方針: 画面表示のみを行い、DB更新は行わない。
     */
    public function edit(Staff $staff): View
    {
        return view('admin.staffs.edit', compact('staff'));
    }

    /**
     * バリデーション済み入力で対象スタッフを更新する。
     *
     * 前提: UpdateStaffRequest で入力検証済みであること。
     * 更新方針: validated() のみを update に渡して保存する。
     */
    public function update(UpdateStaffRequest $request, Staff $staff): RedirectResponse
    {
        $staff->update($request->validated());

        return redirect()->route('admin.staffs.index')
            ->with('success', 'スタッフを更新しました。');
    }

    /**
     * 対象スタッフを削除し、HTMX要求時は空レスポンスを返す。
     *
     * 前提: ルートモデルバインディングで対象レコードが解決されること。
     * 更新方針: 対象レコードを delete したうえで応答形式を分岐する。FK制約違反時は利用者向けエラーを返す。
     */
    public function destroy(Request $request, Staff $staff): RedirectResponse|string
    {
        try {
            $staff->delete();
        } catch (QueryException $exception) {
            if ($this->isForeignKeyConstraintViolation($exception)) {
                return $this->respondDeleteConstraintViolation($request, $staff);
            }

            throw $exception;
        }

        if ($request->header('HX-Request')) {
            return '';
        }

        return redirect()->route('admin.staffs.index')
            ->with('success', 'スタッフを削除しました。');
    }

    private function respondDeleteConstraintViolation(Request $request, Staff $staff): RedirectResponse|string
    {
        if ($request->header('HX-Request')) {
            return view('admin.staffs._delete_error_row', [
                'staff' => $staff,
                'message' => self::DELETE_CONSTRAINT_MESSAGE,
            ])->render();
        }

        return redirect()->route('admin.staffs.index')
            ->with('error', self::DELETE_CONSTRAINT_MESSAGE);
    }
}
