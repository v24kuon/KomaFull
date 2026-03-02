<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStaffRequest;
use App\Http\Requests\Admin\UpdateStaffRequest;
use App\Models\Staff;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffController extends Controller
{
    public function index(Request $request): View|string
    {
        $staffs = Staff::query()->orderBy('id', 'desc')->get();

        if ($request->header('HX-Request')) {
            return view('admin.staffs._table', compact('staffs'))->render();
        }

        return view('admin.staffs.index', compact('staffs'));
    }

    public function create(): View
    {
        return view('admin.staffs.create');
    }

    public function store(StoreStaffRequest $request): RedirectResponse
    {
        Staff::create($request->validated());

        return redirect()->route('admin.staffs.index')
            ->with('success', 'スタッフを作成しました。');
    }

    public function edit(Staff $staff): View
    {
        return view('admin.staffs.edit', compact('staff'));
    }

    public function update(UpdateStaffRequest $request, Staff $staff): RedirectResponse
    {
        $staff->update($request->validated());

        return redirect()->route('admin.staffs.index')
            ->with('success', 'スタッフを更新しました。');
    }

    public function destroy(Request $request, Staff $staff): RedirectResponse|string
    {
        $staff->delete();

        if ($request->header('HX-Request')) {
            return '';
        }

        return redirect()->route('admin.staffs.index')
            ->with('success', 'スタッフを削除しました。');
    }
}
