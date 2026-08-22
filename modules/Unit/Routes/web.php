<?php

use Illuminate\Support\Facades\Route;
use Modules\Unit\Controllers\UnitController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::resource('units', UnitController::class);
    Route::post('units/{unit}/toggle-status', [UnitController::class, 'toggleStatus'])->name('units.toggle-status');
});
