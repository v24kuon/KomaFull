<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdditionalItemRequest;
use App\Http\Requests\Admin\UpdateAdditionalItemRequest;
use App\Models\AdditionalItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdditionalItemController extends Controller
{
    /**
     * 追加項目一覧を表示し、HTMX要求時は一覧テーブルのみ返す。
     *
     * 前提: 管理者認可済みのルートから呼び出されること。
     * 更新方針: 読み取り専用で、DB更新は行わない。
     */
    public function index(Request $request): View
    {
        $additionalItems = AdditionalItem::query()->orderBy('id', 'desc')->get();

        if ($request->header('HX-Request')) {
            return view('partials.admin.additional-items.table', compact('additionalItems'));
        }

        return view('pages.admin.additional-items.index', compact('additionalItems'));
    }

    /**
     * 追加項目の新規作成フォームを表示する。
     *
     * 前提: 管理者認可済みのルートから呼び出されること。
     * 更新方針: 画面表示のみを行い、DB更新は行わない。
     */
    public function create(): View
    {
        return view('pages.admin.additional-items.create');
    }

    /**
     * バリデーション済み入力を用いて追加項目を新規作成する。
     *
     * 前提: StoreAdditionalItemRequest で入力検証済みであること。
     * 更新方針: validated() のみを create に渡して保存する。
     */
    public function store(StoreAdditionalItemRequest $request): RedirectResponse
    {
        AdditionalItem::create($request->validated());

        return redirect()->route('admin.additional-items.index')
            ->with('success', '追加項目を作成しました。');
    }

    /**
     * 追加項目の編集フォームを表示する。
     *
     * 前提: ルートモデルバインディングで対象レコードが解決されること。
     * 更新方針: 画面表示のみを行い、DB更新は行わない。
     */
    public function edit(AdditionalItem $additionalItem): View
    {
        return view('pages.admin.additional-items.edit', compact('additionalItem'));
    }

    /**
     * バリデーション済み入力で対象追加項目を更新する。
     *
     * 前提: UpdateAdditionalItemRequest で入力検証済みであること。
     * 更新方針: validated() のみを update に渡して保存する。
     */
    public function update(UpdateAdditionalItemRequest $request, AdditionalItem $additionalItem): RedirectResponse
    {
        $additionalItem->update($request->validated());

        return redirect()->route('admin.additional-items.index')
            ->with('success', '追加項目を更新しました。');
    }

    /**
     * 対象追加項目を削除し、HTMX要求時は空レスポンスを返す。
     *
     * 前提: ルートモデルバインディングで対象レコードが解決されること。
     * 更新方針: 対象レコードを delete したうえで応答形式を分岐する。
     */
    public function destroy(Request $request, AdditionalItem $additionalItem): RedirectResponse|string
    {
        $additionalItem->delete();

        if ($request->header('HX-Request')) {
            return '';
        }

        return redirect()->route('admin.additional-items.index')
            ->with('success', '追加項目を削除しました。');
    }
}
