<?php

namespace Modules\Authentication\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use Modules\Authentication\Requests\LoginRequest;
use Modules\Authentication\Requests\RegisterRequest;
use Modules\Authentication\Requests\ForgotPasswordRequest;
use Modules\Authentication\Requests\ResetPasswordRequest;
use Modules\Authentication\Services\AuthenticationService;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class AuthenticationController extends AuthenticationBaseController
{
    protected AuthenticationService $authService;

    public function __construct(AuthenticationService $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Hiển thị form đăng nhập
     */
    public function showLogin()
    {
        // Nếu đã đăng nhập, redirect dựa trên role
        if (Auth::check()) {
            return redirect($this->authService->getRedirectUrl());
        }

        return $this->view('authentication::login');
    }

    /**
     * Hiển thị form đăng ký
     */
    public function showRegister()
    {
        if (Auth::check()) {
            return redirect($this->authService->getRedirectUrl());
        }

        return $this->view('authentication::register');
    }

    /**
     * Xử lý đăng nhập
     */
    public function login(LoginRequest $request)
    {

        $email = $request->input('email');
        $ip = $request->ip();

        // === LAYER 1: Chặn spam request theo IP (bất kể đúng/sai) ===
        $spamKey = 'login.spam.' . $ip;

        if (RateLimiter::tooManyAttempts($spamKey, 5)) { // 10 requests/phút
            $seconds = RateLimiter::availableIn($spamKey);

            throw ValidationException::withMessages([
                'email' => "⏰ Bạn đang thao tác quá nhanh! Vui lòng đợi {$seconds} giây."
            ]);
        }

        // Hit spam counter ngay khi có request
        RateLimiter::hit($spamKey, 60);


        // === LAYER 2: Chặn login fail theo Email ===
        $failKey = 'login.fail.' . $email;

        if (RateLimiter::tooManyAttempts($failKey, 3)) { // 3 lần sai/5 phút
            $seconds = RateLimiter::availableIn($failKey);

            throw ValidationException::withMessages([
                'email' => "🔒 Tài khoản tạm khóa do nhập sai mật khẩu quá 3 lần. Vui lòng thử lại sau {$seconds} giây."
            ]);
        }


        // === Thực hiện login ===
        $credentials = $request->validated();
        $remember = $request->boolean('remember');

        if ($this->authService->attemptLogin($credentials, $remember)) {
            $request->session()->regenerate();

            // Clear tất cả rate limit khi login thành công
            RateLimiter::clear($failKey);
            RateLimiter::clear($spamKey);

            // Lấy URL redirect dựa trên role của user
            $redirectUrl = $this->authService->getRedirectUrl();

            // Kiểm tra nếu có intended URL và nó không phải là trang login
            $intendedUrl = $request->session()->pull('url.intended');
            $blockedDashboardIntent = $intendedUrl && str_contains($intendedUrl, '/dashboard') && ! $request->user()->can('dashboards.index');
            if ($intendedUrl && !str_contains($intendedUrl, '/login') && ! $blockedDashboardIntent) {
                return redirect()
                    ->to($intendedUrl)
                    ->with('success', '✅ Đăng nhập thành công! Chào mừng bạn quay trở lại.');
            }

            return redirect()
                ->to($redirectUrl)
                ->with('success', '✅ Đăng nhập thành công! Chào mừng bạn quay trở lại.');
        }


        // === Login thất bại ===
        // Tăng fail counter, lock 5 phút (300s) sau 3 lần sai
        RateLimiter::hit($failKey, 300);

        // Đếm số lần sai còn lại
        $attemptsLeft = 3 - RateLimiter::attempts($failKey);

        return back()
            ->withErrors([
                'email' => $attemptsLeft > 0
                    ? "❌ Thông tin đăng nhập không chính xác. Còn {$attemptsLeft} lần thử."
                    : "🔒 Tài khoản đã bị khóa do nhập sai quá 3 lần."
            ])
            ->withInput($request->only('email'));

    }

    /**
     * Xử lý đăng ký
     */
    public function register(RegisterRequest $request)
    {
        $userData = $request->validated();

        $user = $this->authService->createUser($userData);

        Auth::login($user);

        return redirect()
            ->to($this->authService->getRedirectUrl())
            ->with('success', 'Đăng ký thành công! Chào mừng bạn đến với hệ thống.');
    }

    /**
     * Hiển thị form quên mật khẩu
     */
    public function showForgotPassword()
    {
        return $this->view('authentication::forgot-password');
    }

    /**
     * Xử lý gửi email reset mật khẩu
     */
    public function forgotPassword(ForgotPasswordRequest $request)
    {
        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return back()
                ->with('status', 'Chúng tôi đã gửi link đặt lại mật khẩu đến email của bạn!');
        }

        return back()
            ->withErrors(['email' => 'Có lỗi xảy ra khi gửi email. Vui lòng thử lại.']);
    }

    /**
     * Hiển thị form reset mật khẩu
     */
    public function showResetPassword(Request $request)
    {
        return $this->view('authentication::reset-password', [
            'token' => $request->route('token'),
            'email' => $request->email,
        ]);
    }

    /**
     * Xử lý reset mật khẩu
     */
    public function resetPassword(ResetPasswordRequest $request)
    {
        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => Hash::make($password)
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('login')
                ->with('status', 'Mật khẩu đã được đặt lại thành công! Bạn có thể đăng nhập với mật khẩu mới.');
        }

        return back()
            ->withErrors(['email' => 'Token đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.']);
    }

    /**
     * Xử lý đăng xuất
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('home')
            ->with('success', 'Đăng xuất thành công! Hẹn gặp lại bạn.');
    }
}
