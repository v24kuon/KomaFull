<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware(['auth', 'can:access-admin'])->group(function (): void {
    Route::view('/admin', 'admin.dashboard')->name('admin.dashboard');
});
