<?php

namespace App\Http\Controllers;

use App\Models\Location;
use Illuminate\View\View;

class LegalController extends Controller
{
    /**
     * 特定商取引法に基づく表記ページを表示する。
     *
     * 前提: 表示用の連絡先には、最初の active 店舗（id 昇順）を用いる。店舗未登録時はプレースホルダ文言のみとする。
     * 更新方針: 法務文言の正本は運用で差し替え。将来は `store_settings` 等への移行を検討。
     */
    public function tokushoho(): View
    {
        $primaryLocation = Location::query()
            ->where('status', Location::STATUS_ACTIVE)
            ->orderBy('id')
            ->first();

        return view('pages.legal.tokushoho', compact('primaryLocation'));
    }
}
