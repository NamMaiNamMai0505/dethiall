<?php

use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\Route;
use Modules\TemplateManagement\Controllers\TemplateManagementController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::prefix('template-management')->name('template-management.')->group(function () {
        Route::get('/', [TemplateManagementController::class, 'index'])->name('index');
        Route::get('/create', [TemplateManagementController::class, 'create'])->name('create');
        Route::post('/', [TemplateManagementController::class, 'store'])->name('store');
        Route::get('/{template}', [TemplateManagementController::class, 'show'])->name('show');
        Route::delete('/{template}', [TemplateManagementController::class, 'destroy'])->name('destroy');
    });
});
