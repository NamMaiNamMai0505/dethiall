<?php

use Illuminate\Support\Facades\Route;
use Modules\Instructor\Controllers\InstructorController;

Route::middleware(['web', 'auth'])->group(function () {
    Route::resource('instructors', InstructorController::class);

    // User account creation for instructors
    Route::get('instructors/{instructor}/create-user-account', [InstructorController::class, 'createUserAccount'])
        ->middleware('permission:users.create')
        ->name('instructors.create-user-account');
    Route::post('instructors/{instructor}/create-user-account', [InstructorController::class, 'storeUserAccount'])
        ->middleware('permission:users.create')
        ->name('instructors.store-user-account');
});
