<?php

use Illuminate\Support\Facades\Route;
use Modules\InstructorSchedule\Controllers\InstructorScheduleController;

/*
|--------------------------------------------------------------------------
| Instructor Schedule Module Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth'])
    ->prefix('instructor-schedule')
    ->name('instructor-schedule.')
    ->group(function () {
        Route::get('/', [InstructorScheduleController::class, 'index'])
            ->name('index')
            ->middleware('permission:instructor-schedule.index');
        Route::post('export', [InstructorScheduleController::class, 'export'])
            ->name('export')
            ->middleware('permission:instructor-schedule.index');
    });
