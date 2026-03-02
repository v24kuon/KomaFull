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
    public function index(Request $request): View|string
    {
        $additionalItems = AdditionalItem::query()->orderBy('id', 'desc')->get();

        if ($request->header('HX-Request')) {
            return view('admin.additional-items._table', compact('additionalItems'))->render();
        }

        return view('admin.additional-items.index', compact('additionalItems'));
    }

    public function create(): View
    {
        return view('admin.additional-items.create');
    }

    public function store(StoreAdditionalItemRequest $request): RedirectResponse
    {
        AdditionalItem::create($request->validated());

        return redirect()->route('admin.additional-items.index')
            ->with('success', '追加項目を作成しました。');
    }

    public function edit(AdditionalItem $additionalItem): View
    {
        return view('admin.additional-items.edit', compact('additionalItem'));
    }

    public function update(UpdateAdditionalItemRequest $request, AdditionalItem $additionalItem): RedirectResponse
    {
        $additionalItem->update($request->validated());

        return redirect()->route('admin.additional-items.index')
            ->with('success', '追加項目を更新しました。');
    }

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
