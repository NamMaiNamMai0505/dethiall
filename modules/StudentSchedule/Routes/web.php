<?php

use Illuminate\Support\Facades\Route;
use Modules\StudentSchedule\Controllers\StudentScheduleController;

/*
|--------------------------------------------------------------------------
| Student Schedule Module Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['web', 'auth'])
    ->prefix('student-schedule')
    ->name('student-schedule.')
    ->group(function () {
        Route::get('/', [StudentScheduleController::class, 'index'])
            ->name('index')
            ->middleware('permission:student-schedule.index');
        Route::post('export', [StudentScheduleController::class, 'export'])
            ->name('export')
            ->middleware('permission:student-schedule.index');
    });
