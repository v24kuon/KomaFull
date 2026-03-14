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
    public function index(Request $request): View|string
    {
        $categories = Category::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($request->header('HX-Request')) {
            return view('admin.categories._table', compact('categories'))->render();
        }

        return view('admin.categories.index', compact('categories'));
    }

    public function create(): View
    {
        return view('admin.categories.create');
    }

    public function store(StoreCategoryRequest $request): RedirectResponse
    {
        Category::create($request->validated());

        return redirect()->route('admin.categories.index')
            ->with('success', 'カテゴリを作成しました。');
    }

    public function edit(Category $category): View
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(UpdateCategoryRequest $request, Category $category): RedirectResponse
    {
        $category->update($request->validated());

        return redirect()->route('admin.categories.index')
            ->with('success', 'カテゴリを更新しました。');
    }

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
