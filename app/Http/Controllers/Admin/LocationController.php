<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLocationRequest;
use App\Http\Requests\Admin\UpdateLocationRequest;
use App\Models\Location;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LocationController extends Controller
{
    public function index(Request $request): View|string
    {
        $locations = Location::query()->orderBy('id', 'desc')->get();

        if ($request->header('HX-Request')) {
            return view('admin.locations._table', compact('locations'))->render();
        }

        return view('admin.locations.index', compact('locations'));
    }

    public function create(): View
    {
        return view('admin.locations.create');
    }

    public function store(StoreLocationRequest $request): RedirectResponse
    {
        Location::create($request->validated());

        return redirect()->route('admin.locations.index')
            ->with('success', '店舗を作成しました。');
    }

    public function edit(Location $location): View
    {
        return view('admin.locations.edit', compact('location'));
    }

    public function update(UpdateLocationRequest $request, Location $location): RedirectResponse
    {
        $location->update($request->validated());

        return redirect()->route('admin.locations.index')
            ->with('success', '店舗を更新しました。');
    }

    public function destroy(Request $request, Location $location): RedirectResponse|string
    {
        $location->delete();

        if ($request->header('HX-Request')) {
            return '';
        }

        return redirect()->route('admin.locations.index')
            ->with('success', '店舗を削除しました。');
    }
}
