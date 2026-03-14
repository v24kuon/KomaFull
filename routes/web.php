<?php

use App\Http\Controllers\Admin\AdditionalItemController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\ProgramTypeController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StoreSettingsController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'can:access-admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::view('/', 'admin.dashboard')->name('dashboard');

    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('program-types', ProgramTypeController::class)->except(['show']);
    Route::resource('programs', ProgramController::class)->except(['show']);
    Route::resource('locations', LocationController::class)->except(['show']);
    Route::resource('staffs', StaffController::class)->except(['show']);
    Route::resource('additional-items', AdditionalItemController::class)->except(['show']);

    Route::get('store-settings', [StoreSettingsController::class, 'edit'])->name('store-settings.edit');
    Route::put('store-settings', [StoreSettingsController::class, 'update'])->name('store-settings.update');
});
