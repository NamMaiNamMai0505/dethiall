<?php

namespace Modules\Authentication\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

class AuthenticationService
{
    /**
     * Thử đăng nhập với credentials
     */
    public function attemptLogin(array $credentials, bool $remember = false): bool
    {
        return Auth::attempt($credentials, $remember);
    }

    /**
     * Tạo user mới
     */
    public function createUser(array $userData): User
    {
        return User::create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => Hash::make($userData['password']),
            'role_id' => $userData['role_id'] ?? 4, // Default role 'user' if not provided
        ]);
    }

    /**
     * Gửi link reset password
     */
    public function sendResetLink(string $email): string
    {
        return Password::sendResetLink(['email' => $email]);
    }

    /**
     * Reset password
     */
    public function resetPassword(array $credentials, callable $callback): string
    {
        return Password::reset($credentials, $callback);
    }

    /**
     * Đăng xuất
     */
    public function logout(): void
    {
        Auth::logout();
    }

    /**
     * Lấy user hiện tại
     */
    public function user(): ?User
    {
        return Auth::user();
    }

    /**
     * Kiểm tra đã đăng nhập chưa
     */
    public function check(): bool
    {
        return Auth::check();
    }

    /**
     * Kiểm tra là guest
     */
    public function guest(): bool
    {
        return Auth::guest();
    }

    /**
     * Đăng nhập user
     */
    public function login(User $user, bool $remember = false): void
    {
        Auth::login($user, $remember);
    }

    /**
     * Đăng nhập user bằng ID
     */
    public function loginById(int $userId, bool $remember = false): bool
    {
        return Auth::loginUsingId($userId, $remember);
    }

    /**
     * Lấy URL redirect sau khi đăng nhập dựa trên role của user
     */
    public function getRedirectUrl(?User $user = null): string
    {
        $user ??= $this->user();

        if (! $user) {
            return route('home');
        }

        // Học viên: chỉ vào cổng LMS (lịch học/thi xem trong LMS)
        if ($user->user_type === 'student' || (method_exists($user, 'isStudent') && $user->isStudent())) {
            return route('lms.learn.home');
        }

        if ($user->user_type === 'instructor' || (method_exists($user, 'isInstructor') && $user->isInstructor())) {
            // GV → portal giảng dạy /lms/gv (lịch dạy nằm trong LMS; dashboard chỉ kê khai giờ chuẩn)
            if ($user->can('lms.index') || $user->can('lms.edit') || $user->can('lms.show')) {
                return route('lms.teach.home');
            }

            return route('lms.teach.schedule');
        }

        // Tài khoản quân nhân chỉ có quyền đề xuất phép không dùng dashboard tổng hợp.
        if ($user->can('leave-management.access.index') && ! $user->can('dashboards.index')) {
            return route('leave-management.index');
        }

        // Admin / manager / super-admin → dashboard
        return route('dashboard');
    }
}
