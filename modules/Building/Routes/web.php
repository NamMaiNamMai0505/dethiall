<?php

use Illuminate\Support\Facades\Route;
use Modules\Building\Controllers\BuildingController;

Route::middleware(['web', 'auth'])->group(function () {
    // Building resource routes - permissions handled by ModuleBaseController
    Route::get('buildings', [BuildingController::class, 'index'])->name('buildings.index');
    Route::get('buildings/create', [BuildingController::class, 'create'])->name('buildings.create');
    Route::post('buildings', [BuildingController::class, 'store'])->name('buildings.store');
    Route::get('buildings/{building}', [BuildingController::class, 'show'])->name('buildings.show');
    Route::get('buildings/{building}/edit', [BuildingController::class, 'edit'])->name('buildings.edit');
    Route::put('buildings/{building}', [BuildingController::class, 'update'])->name('buildings.update');
    Route::delete('buildings/{building}', [BuildingController::class, 'destroy'])->name('buildings.destroy');
});
