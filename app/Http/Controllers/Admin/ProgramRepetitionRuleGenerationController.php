<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProgramRepetitionRule;
use App\Services\ProgramRepetitionRuleSessionCandidateService;
use App\Services\ProgramRepetitionRuleSessionGenerationService;
use Illuminate\Http\RedirectResponse;
use InvalidArgumentException;

class ProgramRepetitionRuleGenerationController extends Controller
{
    public function __construct(
        private ProgramRepetitionRuleSessionCandidateService $candidateService,
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
        try {
            if (
                $this->candidateService->candidateCount($programRepetitionRule)
                > ProgramRepetitionRuleSessionCandidateService::MAX_GENERATION_CANDIDATES
            ) {
                return redirect()->route('admin.program-repetition-rules.index')
                    ->with('error', $this->formatTooManyCandidatesMessage());
            }

            $result = $this->generationService->generate($programRepetitionRule);
        } catch (InvalidArgumentException) {
            return redirect()->route('admin.program-repetition-rules.index')
                ->with('error', '繰り返し設定の内容が不正です。設定を見直してください。');
        }

        return redirect()->route('admin.program-repetition-rules.index')
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

    private function formatTooManyCandidatesMessage(): string
    {
        return sprintf(
            '生成対象が多すぎます。期間を短くして %d 件以内にしてください。',
            ProgramRepetitionRuleSessionCandidateService::MAX_GENERATION_CANDIDATES
        );
    }
}
