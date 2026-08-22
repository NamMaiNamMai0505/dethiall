<?php

use Illuminate\Support\Facades\Route;
use Modules\Authentication\Controllers\AuthenticationController;

/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
| Routes cho xử lý authentication: đăng nhập, đăng ký, quên mật khẩu
*/

// Sử dụng web middleware group để có session
Route::middleware(['web'])->group(function () {
    // Routes cho guest (chưa đăng nhập)
    Route::middleware('guest')->group(function () {
        // Đăng nhập
        Route::get('/login', [AuthenticationController::class, 'showLogin'])
            ->name('login');
        Route::post('/login', [AuthenticationController::class, 'login']);
        
        // Đăng ký
        /* Route::get('/register', [AuthenticationController::class, 'showRegister'])
            ->name('register');
        Route::post('/register', [AuthenticationController::class, 'register']);
        
        // Quên mật khẩu
        Route::get('/forgot-password', [AuthenticationController::class, 'showForgotPassword'])
            ->name('password.request');
        Route::post('/forgot-password', [AuthenticationController::class, 'forgotPassword'])
            ->name('password.email');
        
        // Reset mật khẩu
        Route::get('/reset-password/{token}', [AuthenticationController::class, 'showResetPassword'])
            ->name('password.reset');
        Route::post('/reset-password', [AuthenticationController::class, 'resetPassword'])
            ->name('password.update'); */
    });

    // Routes cho authenticated user (đã đăng nhập)
    Route::middleware('auth')->group(function () {
        // Đăng xuất
        Route::post('/logout', [AuthenticationController::class, 'logout'])
            ->name('logout');
    });
});