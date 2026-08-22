<?php

use Illuminate\Support\Facades\Route;
use Modules\Dashboard\Controllers\DashboardController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->middleware('permission:dashboards.index')
        ->name('dashboard');

    // AJAX routes cho thống kê
    Route::prefix('dashboard/ajax')
        ->name('dashboard.ajax.')
        ->middleware('permission:dashboards.index')
        ->group(function () {
            // Thống kê ngành đào tạo/lớp
            Route::get('class-statistics', [DashboardController::class, 'getClassStatistics'])
                ->name('class-statistics');
            Route::get('classes-by-specialization', [DashboardController::class, 'getClassesBySpecialization'])
                ->name('classes-by-specialization');

            // Thống kê khoa/giảng viên
            Route::get('instructor-statistics', [DashboardController::class, 'getInstructorStatistics'])
                ->name('instructor-statistics');
            Route::get('instructors-by-unit', [DashboardController::class, 'getInstructorsByUnit'])
                ->name('instructors-by-unit');
        });
});
