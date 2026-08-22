<?php

use Illuminate\Support\Facades\Route;
use Modules\SystemSettings\Controllers\SystemSettingsController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/settings', [SystemSettingsController::class, 'index'])
        ->defaults('portal', 'dashboard')
        ->name('settings.dashboard');
    Route::get('/lms/settings', [SystemSettingsController::class, 'index'])
        ->defaults('portal', 'lms')
        ->name('settings.lms');
    Route::get('/grades/settings', [SystemSettingsController::class, 'index'])
        ->defaults('portal', 'grades')
        ->name('settings.grades');

    Route::post('/settings/academic-years', [SystemSettingsController::class, 'storeAcademicYear'])
        ->name('settings.academic-years.store');
    Route::patch('/settings/academic-years/{academicYear}', [SystemSettingsController::class, 'updateAcademicYear'])
        ->name('settings.academic-years.update');
    Route::post('/settings/academic-years/{academicYear}/current', [SystemSettingsController::class, 'makeCurrent'])
        ->name('settings.academic-years.current');
    Route::put('/settings/{portal}/general', [SystemSettingsController::class, 'updateGeneral'])
        ->whereIn('portal', ['dashboard', 'lms', 'grades'])
        ->name('settings.general.update');
});
