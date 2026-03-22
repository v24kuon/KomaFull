<?php

use App\Http\Controllers\Admin\AdditionalItemController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\LocationController;
use App\Http\Controllers\Admin\ProgramController as AdminProgramController;
use App\Http\Controllers\Admin\ProgramRepetitionRuleController;
use App\Http\Controllers\Admin\ProgramRepetitionRuleGenerationController;
use App\Http\Controllers\Admin\ProgramTypeController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StoreSettingsController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\ProgramController as PublicProgramController;
use App\Http\Controllers\PublicStoreController;
use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('pages.welcome');
})->name('home');

Route::get('/programs', [PublicProgramController::class, 'index'])->name('programs.index');
Route::get('/programs/{program:code}', [PublicProgramController::class, 'show'])->name('programs.show');

Route::get('/schedule', [ScheduleController::class, 'index'])->name('schedule.index');

Route::get('/stores', [PublicStoreController::class, 'index'])->name('stores.index');
Route::get('/stores/{location:code}', [PublicStoreController::class, 'show'])->name('stores.show');

Route::get('/contact', [ContactController::class, 'create'])->name('contact.create');
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

Route::get('/legal/tokushoho', [LegalController::class, 'tokushoho'])->name('legal.tokushoho');

Route::middleware(['auth', 'can:access-admin'])->prefix('admin')->name('admin.')->group(function (): void {
    Route::view('/', 'pages.admin.dashboard')->name('dashboard');

    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::resource('program-types', ProgramTypeController::class)->except(['show']);
    Route::resource('programs', AdminProgramController::class)->except(['show']);
    Route::resource('program-repetition-rules', ProgramRepetitionRuleController::class)->except(['show']);
    Route::resource('locations', LocationController::class)->except(['show']);
    Route::resource('staffs', StaffController::class)->except(['show']);
    Route::resource('additional-items', AdditionalItemController::class)->except(['show']);
    Route::post(
        'program-repetition-rules/{program_repetition_rule}/generate',
        ProgramRepetitionRuleGenerationController::class
    )->name('program-repetition-rules.generate');

    Route::get('store-settings', [StoreSettingsController::class, 'edit'])->name('store-settings.edit');
    Route::put('store-settings', [StoreSettingsController::class, 'update'])->name('store-settings.update');
});
