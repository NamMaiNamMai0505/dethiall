<?php

use Illuminate\Support\Facades\Route;
use Modules\ScheduleDetail\Controllers\ScheduleDetailController;

Route::middleware(['web','auth'])->group(function () {
    Route::resource('scheduledetails', ScheduleDetailController::class);
});
