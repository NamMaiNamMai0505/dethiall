<?php

use App\Http\Controllers\PermissionController;
use App\Http\Controllers\SystemNotificationController;
use Illuminate\Support\Facades\Route;

// Test route để tránh redirect loop
Route::get('/test', function () {
    return 'Server is working!';
});

// In-app notifications
Route::middleware(['auth'])->prefix('notifications')->name('notifications.')->group(function () {
    Route::get('/', [SystemNotificationController::class, 'index'])->name('index');
    Route::get('/unread-count', [SystemNotificationController::class, 'unreadCount'])->name('unread-count');
    Route::post('/read-all', [SystemNotificationController::class, 'markAllRead'])->name('read-all');
    Route::post('/{notification}/read', [SystemNotificationController::class, 'markRead'])->name('read');
});

// Permission management routes
Route::middleware(['auth'])->prefix('permissions')->name('permissions.')->group(function () {
    Route::get('/', [PermissionController::class, 'index'])->name('index');
    Route::get('/roles', [PermissionController::class, 'roles'])->name('roles');
    Route::post('/roles', [PermissionController::class, 'storeRole'])->name('roles.store');
    Route::put('/roles/{role}', [PermissionController::class, 'updateRole'])->name('roles.update');
    Route::delete('/roles/{role}', [PermissionController::class, 'destroyRole'])->name('roles.destroy');

    Route::get('/users', [PermissionController::class, 'users'])->name('users');
    Route::put('/users/{user}', [PermissionController::class, 'updateUser'])->name('users.update');

    Route::get('/modules', [PermissionController::class, 'modules'])->name('modules');
    Route::post('/initialize', [PermissionController::class, 'initializePermissions'])->name('initialize');

    Route::get('/user/{user}/permissions', [PermissionController::class, 'getUserPermissions'])->name('user.permissions');
    Route::post('/check-access', [PermissionController::class, 'checkAccess'])->name('check.access');
});

// Routes được quản lý bởi các modules
// Authentication Module: /login, /register, /logout
// Home Module: /
// Dashboard Module: /dashboard