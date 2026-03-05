<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStoreSettingsRequest;
use App\Models\StoreSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StoreSettingsController extends Controller
{
    /**
     * 店舗設定の編集フォームを表示する。
     *
     * 前提: 管理者認可済みのルートから呼び出されること。
     * 更新方針: シングルトン設定を取得して表示するのみで、このメソッドではDB更新は行わない。
     */
    public function edit(): View
    {
        $settings = $this->resolveSettings();

        return view('admin.store-settings.edit', compact('settings'));
    }

    /**
     * バリデーション済み入力で店舗設定を更新する。
     *
     * 前提: UpdateStoreSettingsRequest で入力検証済みであること。
     * 更新方針: validated() のみを update に渡して保存する。
     */
    public function update(UpdateStoreSettingsRequest $request): RedirectResponse
    {
        $settings = $this->resolveSettings();
        $settings->update($request->validated());

        return redirect()->route('admin.store-settings.edit')
            ->with('success', '店舗設定を更新しました。');
    }

    private function resolveSettings(): StoreSettings
    {
        return StoreSettings::query()->createOrFirst([
            'singleton_key' => 'singleton',
        ]);
    }
}
