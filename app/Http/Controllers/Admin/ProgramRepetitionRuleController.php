<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProgramRepetitionRuleRequest;
use App\Http\Requests\Admin\UpdateProgramRepetitionRuleRequest;
use App\Models\Location;
use App\Models\Program;
use App\Models\ProgramRepetitionRule;
use App\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramRepetitionRuleController extends Controller
{
    /**
     * 繰り返し設定一覧を表示し、HTMX 要求時は一覧テーブルのみ返す。
     */
    public function index(Request $request): View
    {
        $programRepetitionRules = ProgramRepetitionRule::query()
            ->with(['program', 'location', 'staff'])
            ->orderByDesc('id')
            ->get();

        if ($request->header('HX-Request')) {
            return view('partials.admin.program-repetition-rules.table', compact('programRepetitionRules'));
        }

        return view('pages.admin.program-repetition-rules.index', compact('programRepetitionRules'));
    }

    /**
     * 繰り返し設定の新規作成フォームを表示する。
     */
    public function create(): View
    {
        return view('pages.admin.program-repetition-rules.create', $this->resolveFormMasterData());
    }

    /**
     * バリデーション済み入力を用いて繰り返し設定を新規作成する。
     */
    public function store(StoreProgramRepetitionRuleRequest $request): RedirectResponse
    {
        ProgramRepetitionRule::create(
            $this->normalizeValidatedAttributes($request->validated(), $request->input('cycle_type'))
        );

        return redirect()->route('admin.program-repetition-rules.index')
            ->with('success', '繰り返し設定を作成しました。');
    }

    /**
     * 繰り返し設定の編集フォームを表示する。
     */
    public function edit(ProgramRepetitionRule $programRepetitionRule): View
    {
        $formMasterData = $this->resolveFormMasterData();

        return view('pages.admin.program-repetition-rules.edit', [
            'programRepetitionRule' => $programRepetitionRule,
            'programs' => $formMasterData['programs'],
            'locations' => $formMasterData['locations'],
            'staffs' => $formMasterData['staffs'],
        ]);
    }

    /**
     * バリデーション済み入力で対象の繰り返し設定を更新する。
     */
    public function update(
        UpdateProgramRepetitionRuleRequest $request,
        ProgramRepetitionRule $programRepetitionRule
    ): RedirectResponse {
        $programRepetitionRule->update(
            $this->normalizeValidatedAttributes($request->validated(), $request->input('cycle_type'))
        );

        return redirect()->route('admin.program-repetition-rules.index')
            ->with('success', '繰り返し設定を更新しました。');
    }

    /**
     * 対象の繰り返し設定を削除し、HTMX 要求時は空レスポンスを返す。
     */
    public function destroy(
        Request $request,
        ProgramRepetitionRule $programRepetitionRule
    ): RedirectResponse|string {
        $programRepetitionRule->delete();

        if ($request->header('HX-Request')) {
            return '';
        }

        return redirect()->route('admin.program-repetition-rules.index')
            ->with('success', '繰り返し設定を削除しました。');
    }

    /**
     * 繰り返し設定フォームで利用するマスタデータを取得する。
     *
     * @return array{
     *     programs: \Illuminate\Database\Eloquent\Collection<int, Program>,
     *     locations: \Illuminate\Database\Eloquent\Collection<int, Location>,
     *     staffs: \Illuminate\Database\Eloquent\Collection<int, Staff>
     * }
     */
    private function resolveFormMasterData(): array
    {
        return [
            'programs' => Program::query()->with(['category', 'programType'])->orderBy('name')->get(),
            'locations' => Location::query()->orderBy('name')->get(),
            'staffs' => Staff::query()->orderBy('name')->get(),
        ];
    }

    /**
     * Normalize validated payload so daily rules always clear the stored weekday.
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function normalizeValidatedAttributes(array $validated, ?string $cycleType): array
    {
        if (
            $cycleType === ProgramRepetitionRule::CYCLE_TYPE_DAILY
            && ! array_key_exists('day_of_week', $validated)
        ) {
            $validated['day_of_week'] = null;
        }

        return $validated;
    }
}
