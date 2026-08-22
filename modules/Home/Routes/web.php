<?php

use Illuminate\Support\Facades\Route;
use Modules\Home\Controllers\HomeController;

// Sử dụng web middleware group để có session - permissions handled by ModuleBaseController
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
});
