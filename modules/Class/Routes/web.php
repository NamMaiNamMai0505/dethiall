<?php

use Illuminate\Support\Facades\Route;
use Modules\Class\Controllers\ClassController;

/*
|--------------------------------------------------------------------------
| Class Module Routes
|--------------------------------------------------------------------------
*/

// Class resource routes - permissions handled by ModuleBaseController
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/', [ClassController::class, 'index'])->name('classes.index');
    Route::get('/create', [ClassController::class, 'create'])->name('classes.create');
    Route::post('/', [ClassController::class, 'store'])->name('classes.store');
    Route::get('/{class}', [ClassController::class, 'show'])->name('classes.show');
    Route::get('/{class}/edit', [ClassController::class, 'edit'])->name('classes.edit');
    Route::put('/{class}', [ClassController::class, 'update'])->name('classes.update');
    Route::delete('/{class}', [ClassController::class, 'destroy'])->name('classes.destroy');
});
