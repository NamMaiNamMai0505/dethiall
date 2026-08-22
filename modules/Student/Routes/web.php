<?php

use Illuminate\Support\Facades\Route;
use Modules\Student\Controllers\StudentController;

Route::middleware(['web', 'auth'])->group(function () {
    // Student management routes - permissions handled by ModuleBaseController
    Route::get('students', [StudentController::class, 'index'])->name('students.index');
    Route::get('students/create', [StudentController::class, 'create'])->name('students.create');
    Route::post('students', [StudentController::class, 'store'])->name('students.store');
    Route::get('students/{student}', [StudentController::class, 'show'])->name('students.show');
    Route::get('students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
    Route::put('students/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::delete('students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
    Route::delete('students-bulk-delete', [StudentController::class, 'bulkDestroy'])->name('students.bulk-delete');
});
