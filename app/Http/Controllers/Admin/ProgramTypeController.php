<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProgramTypeRequest;
use App\Http\Requests\Admin\UpdateProgramTypeRequest;
use App\Models\ProgramType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramTypeController extends Controller
{
    public function index(Request $request): View|string
    {
        $programTypes = ProgramType::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($request->header('HX-Request')) {
            return view('admin.program-types._table', compact('programTypes'))->render();
        }

        return view('admin.program-types.index', compact('programTypes'));
    }

    public function create(): View
    {
        return view('admin.program-types.create');
    }

    public function store(StoreProgramTypeRequest $request): RedirectResponse
    {
        ProgramType::create($request->validated());

        return redirect()->route('admin.program-types.index')
            ->with('success', 'プログラム種別を作成しました。');
    }

    public function edit(ProgramType $programType): View
    {
        return view('admin.program-types.edit', compact('programType'));
    }

    public function update(UpdateProgramTypeRequest $request, ProgramType $programType): RedirectResponse
    {
        $programType->update($request->validated());

        return redirect()->route('admin.program-types.index')
            ->with('success', 'プログラム種別を更新しました。');
    }

    public function destroy(Request $request, ProgramType $programType): RedirectResponse|string
    {
        $programType->delete();

        if ($request->header('HX-Request')) {
            return '';
        }

        return redirect()->route('admin.program-types.index')
            ->with('success', 'プログラム種別を削除しました。');
    }
}
