<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreProgramRequest;
use App\Http\Requests\Admin\UpdateProgramRequest;
use App\Models\Category;
use App\Models\Program;
use App\Models\ProgramType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProgramController extends Controller
{
    public function index(Request $request): View|string
    {
        $programs = Program::query()
            ->with(['category', 'programType'])
            ->orderBy('id', 'desc')
            ->get();

        if ($request->header('HX-Request')) {
            return view('admin.programs._table', compact('programs'))->render();
        }

        return view('admin.programs.index', compact('programs'));
    }

    public function create(): View
    {
        $categories = Category::query()->orderBy('sort_order')->get();
        $programTypes = ProgramType::query()->orderBy('sort_order')->get();

        return view('admin.programs.create', compact('categories', 'programTypes'));
    }

    public function store(StoreProgramRequest $request): RedirectResponse
    {
        Program::create($request->validated());

        return redirect()->route('admin.programs.index')
            ->with('success', 'プログラムを作成しました。');
    }

    public function edit(Program $program): View
    {
        $categories = Category::query()->orderBy('sort_order')->get();
        $programTypes = ProgramType::query()->orderBy('sort_order')->get();

        return view('admin.programs.edit', compact('program', 'categories', 'programTypes'));
    }

    public function update(UpdateProgramRequest $request, Program $program): RedirectResponse
    {
        $program->update($request->validated());

        return redirect()->route('admin.programs.index')
            ->with('success', 'プログラムを更新しました。');
    }

    public function destroy(Request $request, Program $program): RedirectResponse|string
    {
        $program->delete();

        if ($request->header('HX-Request')) {
            return '';
        }

        return redirect()->route('admin.programs.index')
            ->with('success', 'プログラムを削除しました。');
    }
}
