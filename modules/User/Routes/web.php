<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Controllers\AccountHubController;
use Modules\User\Controllers\DigitalSignatureController;
use Modules\User\Controllers\PermissionController;
use Modules\User\Controllers\RoleController;
use Modules\User\Controllers\UserController;

Route::middleware(['web', 'auth'])->group(function () {
    // Profile route - accessible to all authenticated users (GV + học viên + nội bộ)
    Route::get('profile', [UserController::class, 'profile'])->name('profile');
    Route::put('profile', [UserController::class, 'updateProfile'])->name('profile.update');

    // Chữ ký số (ảnh) — mỗi user tự quản lý; mẫu LHL claim theo tên
    Route::get('signatures', [DigitalSignatureController::class, 'index'])->name('signatures.index');
    Route::post('signatures', [DigitalSignatureController::class, 'store'])->name('signatures.store');
    Route::put('signatures/{signature}', [DigitalSignatureController::class, 'update'])->name('signatures.update');
    Route::delete('signatures/{signature}', [DigitalSignatureController::class, 'destroy'])->name('signatures.destroy');
    Route::post('signatures/claim', [DigitalSignatureController::class, 'claim'])->name('signatures.claim');
    Route::post('signatures/admin-claim-all', [DigitalSignatureController::class, 'adminClaimAll'])->name('signatures.admin-claim-all');

    // Hub quản lý tài khoản
    Route::get('accounts', [AccountHubController::class, 'index'])->name('accounts.hub');

    // User management routes - permissions handled by ModuleBaseController
    Route::get('users', [UserController::class, 'index'])->name('users.index');
    Route::get('users/create', [UserController::class, 'create'])->name('users.create');
    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
    Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
    Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::delete('users-bulk-delete', [UserController::class, 'bulkDestroy'])->name('users.bulk-delete');
    Route::post('users-bulk-sync-instructor', [UserController::class, 'bulkSyncInstructor'])->name('users.bulk-sync-instructor');

    // Import routes
    Route::get('users-import-template', [UserController::class, 'downloadTemplate'])->name('users.import.template');
    Route::post('users-import', [UserController::class, 'import'])->name('users.import');

    // Role and Permission management (admin only)
    Route::middleware('role:super-admin')->group(function () {
        Route::get('roles/integrity', [RoleController::class, 'integrity'])->name('roles.integrity');
        Route::post('roles/integrity/repair-links', [RoleController::class, 'repairRoleLinks'])
            ->name('roles.integrity.repair-links');
        Route::resource('roles', RoleController::class);
        Route::get('permissions/matrix', [PermissionController::class, 'matrix'])->name('permissions.matrix');
        Route::resource('permissions', PermissionController::class);
    });
});
