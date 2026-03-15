<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProgramRepetitionRule;
use App\Services\ProgramRepetitionRuleSessionGenerationService;
use Illuminate\Http\RedirectResponse;

class ProgramRepetitionRuleGenerationController extends Controller
{
    public function __construct(
        private ProgramRepetitionRuleSessionGenerationService $generationService
    ) {}

    /**
     * 管理画面から 1 件の繰り返しルールに対して手動でセッション生成を実行する。
     *
     * 前提: 管理者認可済みルートから呼び出され、対象 `ProgramRepetitionRule` は route-model binding で
     * 解決済みであること。更新方針: 生成ロジックの真実は service 層に委譲し、この controller は
     * 「手動実行のみ」という運用境界と結果メッセージの整形だけを担う。追加の生成期間入力は受け取らない。
     */
    public function __invoke(ProgramRepetitionRule $programRepetitionRule): RedirectResponse
    {
        $result = $this->generationService->generate($programRepetitionRule);

        return redirect()->route('admin.dashboard')
            ->with('success', $this->formatSuccessMessage($result));
    }

    /**
     * 生成結果の件数を管理画面用のフラッシュメッセージへ整形する。
     *
     * @param  array{created_count: int, skipped_count: int}  $result
     */
    private function formatSuccessMessage(array $result): string
    {
        return sprintf(
            'セッション生成を実行しました。（作成: %d件 / スキップ: %d件）',
            $result['created_count'],
            $result['skipped_count']
        );
    }
}
