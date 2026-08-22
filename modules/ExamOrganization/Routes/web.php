<?php
use Illuminate\Support\Facades\Route;
use Modules\ExamOrganization\Controllers\ExamOrganizationController;

Route::middleware(['web','auth'])->prefix('exam-organization')->name('exam-organization.')->group(function () {
    Route::get('/', [ExamOrganizationController::class, 'index'])->middleware('permission:exam-organization.index')->name('index');
    Route::post('/plans', [ExamOrganizationController::class, 'store'])->middleware('permission:exam-organization.plan')->name('plans.store');
    Route::post('/actions', [ExamOrganizationController::class, 'action'])->name('actions.store');
    Route::post('/process', [ExamOrganizationController::class, 'process'])->name('process');
});
