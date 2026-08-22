<?php

use Illuminate\Support\Facades\Route;
use Modules\Classroom\Controllers\ClassroomController;

Route::middleware(['web', 'auth'])->group(function () {
    // Classroom resource routes - permissions handled by ModuleBaseController
    Route::get('classrooms', [ClassroomController::class, 'index'])->name('classrooms.index');
    Route::get('classrooms/create', [ClassroomController::class, 'create'])->name('classrooms.create');
    Route::post('classrooms', [ClassroomController::class, 'store'])->name('classrooms.store');
    Route::get('classrooms/{classroom}', [ClassroomController::class, 'show'])->name('classrooms.show');
    Route::get('classrooms/{classroom}/edit', [ClassroomController::class, 'edit'])->name('classrooms.edit');
    Route::put('classrooms/{classroom}', [ClassroomController::class, 'update'])->name('classrooms.update');
    Route::delete('classrooms/{classroom}', [ClassroomController::class, 'destroy'])->name('classrooms.destroy');
});
