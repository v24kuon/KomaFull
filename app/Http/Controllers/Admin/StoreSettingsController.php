<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStoreSettingsRequest;
use App\Models\StoreSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StoreSettingsController extends Controller
{
    public function edit(): View
    {
        $settings = StoreSettings::query()->firstOrCreate();

        return view('admin.store-settings.edit', compact('settings'));
    }

    public function update(UpdateStoreSettingsRequest $request): RedirectResponse
    {
        $settings = StoreSettings::query()->firstOrCreate();
        $settings->update($request->validated());

        return redirect()->route('admin.store-settings.edit')
            ->with('success', '店舗設定を更新しました。');
    }
}
