<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCategoryRequest;
use App\Http\Requests\Admin\UpdateCategoryRequest;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CategoryController extends Controller
{
    /**
     * カテゴリ一覧を表示し、HTMX要求時は一覧テーブルのみ返す。
     *
     * 前提: 管理者認可済みのルートから呼び出されること。
     * 更新方針: 読み取り専用で、DB更新は行わない。
     */
    public function index(Request $request): View
    {
        $categories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($request->header('HX-Request')) {
            return view('partials.admin.categories.table', compact('categories'));
        }

        return view('pages.admin.categories.index', compact('categories'));
    }

    /**
     * カテゴリ新規作成フォームを表示する。
     *
     * 前提: 管理者認可済みのルートから呼び出されること。
     * 更新方針: フォーム表示のみで、DB更新は行わない。
     */
    public function create(): View
    {
        return view('pages.admin.categories.create');
    }

    /**
     * バリデーション済み入力を用いてカテゴリを新規作成する。
     *
     * 前提: StoreCategoryRequest で入力検証済みであること。
     * 更新方針: validated() のみを create に渡して保存する。
     */
    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return redirect()->route('admin.categories.index')
            ->with('success', 'カテゴリを作成しました。');
    }

    /**
     * カテゴリ編集フォームを表示する。
     *
     * 前提: ルートモデルバインディングで対象レコードが解決されること。
     * 更新方針: フォーム表示のみで、DB更新は行わない。
     */
    public function edit(Category $category): View
    {
        return view('pages.admin.categories.edit', compact('category'));
    }

    /**
     * バリデーション済み入力で対象カテゴリを更新する。
     *
     * 前提: UpdateCategoryRequest で入力検証済みであること。
     * 更新方針: validated() のみを update に渡して保存する。
     */
    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('admin.categories.index')
            ->with('success', 'カテゴリを更新しました。');
    }

    /**
     * 対象カテゴリを削除し、HTMX要求時は空レスポンスを返す。
     *
     * 前提: ルートモデルバインディングで対象レコードが解決されること。
     * 更新方針: 対象レコードを delete したうえで応答形式を分岐する。
     */
    public function destroy(Request $request, Category $category): RedirectResponse|string
    {
        $category->delete();

        if ($request->header('HX-Request')) {
            return '';
        }

        return redirect()->route('admin.categories.index')
            ->with('success', 'カテゴリを削除しました。');
    }
}
