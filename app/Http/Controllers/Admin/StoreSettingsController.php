<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateStoreSettingsRequest;
use App\Models\StoreSettings;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StoreSettingsController extends Controller
{
    public function __construct(private ConnectionInterface $connection) {}

    /**
     * 店舗設定の編集フォームを表示する。
     *
     * 前提: 管理者認可済みのルートから呼び出されること。
     * 更新方針: シングルトン設定を取得して表示するのみで、このメソッドではDB更新は行わない。
     */
    public function edit(): View
    {
        $settings = $this->resolveSettings();

        return view('pages.admin.store-settings.edit', compact('settings'));
    }

    /**
     * バリデーション済み入力で店舗設定を更新する。
     *
     * 前提: UpdateStoreSettingsRequest で入力検証済みであること。
     * 更新方針: createOrFirst() 後に lockForUpdate() で対象行を再取得し、同一トランザクション内で更新する。
     */
    public function update(UpdateStoreSettingsRequest $request): RedirectResponse
    {
        $this->connection->transaction(function () use ($request): void {
            $settings = $this->resolveSettingsForUpdate();
            $settings->update($request->validated());
        });

        return redirect()->route('admin.store-settings.edit')
            ->with('success', '店舗設定を更新しました。');
    }

    /**
     * 単一の店舗設定を取得または作成する。
     *
     * 前提: singleton_key='singleton' の単一行を扱うこと。
     * 更新方針: createOrFirst() で設定行を確保し、未存在時のみ作成する。
     */
    private function resolveSettings(): StoreSettings
    {
        return StoreSettings::query()->createOrFirst([
            'singleton_key' => 'singleton',
        ]);
    }

    /**
     * 更新対象の店舗設定を行ロック付きで取得する。
     *
     * 前提: 呼び出し元がトランザクション内で実行していること。
     * 更新方針: createOrFirst() で単一行を確保した後、同一行を lockForUpdate() で再取得して更新競合を直列化する。
     */
    private function resolveSettingsForUpdate(): StoreSettings
    {
        $settings = $this->resolveSettings();

        return StoreSettings::query()
            ->whereKey($settings->id)
            ->lockForUpdate()
            ->firstOrFail();
    }
}
