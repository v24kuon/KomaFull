<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\View\View;

class PublicStoreController extends Controller
{
    /**
     * 公開の店舗一覧を表示する。
     *
     * 前提: `status=active` の Location のみを名称昇順で列挙する。
     * 更新方針: 読み取り専用。掲載条件を変える場合は管理画面のステータス運用と合わせる。
     */
    public function index(): View
    {
        $locations = Location::query()
            ->where('status', Location::STATUS_ACTIVE)
            ->orderBy('name')
            ->get();

        return view('pages.stores.index', compact('locations'));
    }

    /**
     * 公開の店舗詳細を表示する。
     *
     * 前提: ルートモデルバインディングで `code` により解決されること。
     * 更新方針: inactive は 404 とする（一覧に出さない URL からの直叩き対策）。
     */
    public function show(Location $location): View
    {
        if ($location->status !== Location::STATUS_ACTIVE) {
            abort(404);
        }

        return view('pages.stores.show', compact('location'));
    }
}
