<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLocationRequest;
use App\Http\Requests\Admin\UpdateLocationRequest;
use App\Models\Location;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    private const DELETE_CONSTRAINT_MESSAGE = '関連データが存在するため店舗を削除できません。先にレッスン枠・繰り返しルールを削除してください。';

    /**
     * 店舗一覧を表示し、HTMX要求時は一覧テーブルのみ返す。
     *
     * 前提: 管理者認可済みのルートから呼び出されること。
     * 更新方針: 読み取り専用で、DB更新は行わない。
     */
    public function index(Request $request): View|string
    {
        $locations = Location::query()->orderBy('id', 'desc')->get();

        if ($request->header('HX-Request')) {
            return view('admin.locations._table', compact('locations'))->render();
        }

        return view('admin.locations.index', compact('locations'));
    }

    /**
     * 店舗の新規作成フォームを表示する。
     *
     * 前提: 管理者認可済みのルートから呼び出されること。
     * 更新方針: 画面表示のみを行い、DB更新は行わない。
     */
    public function create(): View
    {
        return view('admin.locations.create');
    }

    /**
     * バリデーション済み入力を用いて店舗を新規作成する。
     *
     * 前提: StoreLocationRequest で入力検証済みであること。
     * 更新方針: validated() のみを create に渡して保存する。
     */
    public function store(StoreLocationRequest $request): RedirectResponse
    {
        Location::create($request->validated());

        return redirect()->route('admin.locations.index')
            ->with('success', '店舗を作成しました。');
    }

    /**
     * 店舗の編集フォームを表示する。
     *
     * 前提: ルートモデルバインディングで対象レコードが解決されること。
     * 更新方針: 画面表示のみを行い、DB更新は行わない。
     */
    public function edit(Location $location): View
    {
        return view('admin.locations.edit', compact('location'));
    }

    /**
     * バリデーション済み入力で対象店舗を更新する。
     *
     * 前提: UpdateLocationRequest で入力検証済みであること。
     * 更新方針: validated() のみを update に渡して保存する。
     */
    public function update(UpdateLocationRequest $request, Location $location): RedirectResponse
    {
        $location->update($request->validated());

        return redirect()->route('admin.locations.index')
            ->with('success', '店舗を更新しました。');
    }

    /**
     * 対象店舗を削除し、HTMX要求時は空レスポンスを返す。
     *
     * 前提: ルートモデルバインディングで対象レコードが解決されること。
     * 更新方針: 対象レコードを delete したうえで応答形式を分岐する。FK制約違反時は利用者向けエラーを返す。
     */
    public function destroy(Request $request, Location $location): RedirectResponse|string
    {
        try {
            $location->delete();
        } catch (QueryException $exception) {
            if ($this->isForeignKeyConstraintViolation($exception)) {
                return $this->respondDeleteConstraintViolation($request, $location);
            }

            throw $exception;
        }

        if ($request->header('HX-Request')) {
            return '';
        }

        return redirect()->route('admin.locations.index')
            ->with('success', '店舗を削除しました。');
    }

    private function respondDeleteConstraintViolation(Request $request, Location $location): RedirectResponse|string
    {
        if ($request->header('HX-Request')) {
            return view('admin.locations._delete_error_row', [
                'location' => $location,
                'message' => self::DELETE_CONSTRAINT_MESSAGE,
            ])->render();
        }

        return redirect()->route('admin.locations.index')
            ->with('error', self::DELETE_CONSTRAINT_MESSAGE);
    }
}
