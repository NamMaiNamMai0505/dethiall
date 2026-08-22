<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="turbo-cache-control" content="no-cache">
    <meta name="turbo-visit-control" content="reload">
    <title>Đăng nhập - {{ config('app.name', 'Quản lý đào tạo') }}</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="{{ asset('build/vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/js/all.min.js" defer></script>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
        }
        /* Glassmorphism effect for the card */
        .glass-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        /* Animation for errors */
        .shake-animation {
            animation: shake 0.6s ease-in-out;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
            20%, 40%, 60%, 80% { transform: translateX(5px); }
        }
    </style>
</head>
<body class="h-screen w-full overflow-hidden flex items-center justify-center bg-gray-900">

    <!-- Background Image Layer -->
    <div class="absolute inset-0 z-0">
        <img src="https://cdhc2.edu.vn/wp-content/uploads/2025/07/IMGP2277ffff-scaled.jpg" 
             alt="Background" 
             class="w-full h-full object-cover object-center"
             onerror="this.src='https://placehold.co/1920x1080/1a1a1a/FFF?text=Background+Image+Error'"
        >
        <!-- Dark Overlay for readability -->
        <div class="absolute inset-0 bg-gradient-to-br from-blue-900/80 to-black/60"></div>
    </div>

    <!-- Main Container -->
    <div class="relative z-10 w-full max-w-lg px-6">
        
        <!-- Login Card -->
        <div class="glass-card rounded-2xl shadow-2xl p-8 transform transition-all duration-300">
            
            <!-- Logo Section -->
            <div class="flex justify-center mb-6">
                <img src="https://cdhc2.edu.vn/wp-content/uploads/2025/12/Logo-11-12-2025.png" 
                     alt="Logo Trường Cao Đẳng Hậu Cần 2" 
                     class="h-28 w-auto drop-shadow-md"
                >
            </div>

            <!-- Site Name / Header -->
            <div class="text-center mb-6">
                <h2 class="text-xl font-bold text-gray-800 uppercase tracking-wide">
                    Quản lý đào tạo
                </h2>
                <h3 class="text-md font-semibold text-blue-800 mt-1">
                    Trường Cao Đẳng Hậu Cần 2
                </h3>
            </div>

            <!-- Notifications (Session Status) -->
            @if (session('status'))
                <div class="notification mb-4 p-3 rounded-lg bg-green-100 border border-green-200 flex items-center">
                    <i class="fas fa-check-circle text-green-600 mr-2"></i>
                    <span class="text-sm text-green-700">{{ session('status') }}</span>
                </div>
            @endif

            <!-- Notifications (Validation Errors) -->
            @if ($errors->any())
                <div class="notification mb-4 p-3 rounded-lg bg-red-100 border border-red-200 shake-animation">
                    <div class="flex items-start">
                        <i class="fas fa-exclamation-triangle text-red-600 mt-0.5 mr-2"></i>
                        <div class="text-sm text-red-700">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Login Form -->
            <form action="{{ route('login') }}" method="POST" class="space-y-5" data-turbo="false">
                @csrf

                <!-- Email Input -->
                <div class="space-y-1">
                    <label for="email" class="text-sm font-medium text-gray-700 block ml-1">
                        Địa chỉ Email
                    </label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400 group-focus-within:text-blue-600 transition-colors"></i>
                        </div>
                        <input type="email" 
                               name="email" 
                               id="email" 
                               value="{{ old('email') }}"
                               class="block w-full pl-10 pr-3 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition duration-200 placeholder-gray-400 text-gray-900 bg-gray-50
                               @error('email') border-red-500 focus:ring-red-500 @enderror" 
                               placeholder="example@email.com" 
                               required
                               autocomplete="email"
                               autofocus>
                    </div>
                    @error('email')
                        <p class="text-red-500 text-xs mt-1 ml-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Password Input -->
                <div class="space-y-1">
                    <label for="password" class="text-sm font-medium text-gray-700 block ml-1">
                        Mật khẩu
                    </label>
                    <div class="relative group">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400 group-focus-within:text-blue-600 transition-colors"></i>
                        </div>
                        <input type="password" 
                               name="password" 
                               id="password" 
                               class="block w-full pl-10 pr-10 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-600 focus:border-transparent transition duration-200 placeholder-gray-400 text-gray-900 bg-gray-50
                               @error('password') border-red-500 focus:ring-red-500 @enderror" 
                               placeholder="••••••••" 
                               required
                               autocomplete="current-password">
                        
                        <!-- Toggle Password Button -->
                        <button type="button" onclick="togglePassword()" 
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600 focus:outline-none cursor-pointer">
                            <i id="password-icon" class="fas fa-eye"></i>
                        </button>
                    </div>
                    @error('password')
                        <p class="text-red-500 text-xs mt-1 ml-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between text-sm pt-1">
                    <div class="flex items-center">
                        <input id="remember_me" name="remember" type="checkbox" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded cursor-pointer">
                        <label for="remember_me" class="ml-2 block text-gray-700 cursor-pointer select-none">
                            Ghi nhớ đăng nhập
                        </label>
                    </div>

                    <div class="text-sm">
                        @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" class="font-medium text-blue-700 hover:text-blue-500 hover:underline transition">
                            Quên mật khẩu?
                        </a>
                        @endif
                    </div>
                </div>

                <!-- Login Button -->
                <button type="submit" id="submitBtn" class="w-full flex justify-center items-center py-3 px-4 border border-transparent rounded-lg shadow-md text-sm font-bold text-white bg-blue-700 hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-600 transition-all duration-200 transform hover:-translate-y-0.5">
                    <i class="fas fa-sign-in-alt mr-2"></i>
                    <span>ĐĂNG NHẬP</span>
                </button>

            </form>
        </div>

        <!-- Footer Text -->
        <p class="mt-6 text-center text-xs text-gray-200 opacity-80 shadow-black drop-shadow-md">
            &copy; {{ date('Y') }} Trường Cao Đẳng Hậu Cần 2 - Tổng cục Hậu Cần - Kỹ Thuật  
        </p>
    </div>

    <!-- JavaScript Logic -->
    <script>
        // Toggle Password Visibility
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const passwordIcon = document.getElementById('password-icon');

            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                passwordIcon.classList.remove('fa-eye');
                passwordIcon.classList.add('fa-eye-slash');
            } else {
                passwordField.type = 'password';
                passwordIcon.classList.remove('fa-eye-slash');
                passwordIcon.classList.add('fa-eye');
            }
        }

        // Prevent Double Submission & Show Loading State
        let isSubmitting = false;
        document.querySelector('form').addEventListener('submit', function (e) {
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }

            isSubmitting = true;
            const submitBtn = document.getElementById('submitBtn');
            const originalContent = submitBtn.innerHTML;

            submitBtn.innerHTML = `
                <i class="fas fa-spinner fa-spin mr-2"></i>
                <span>Đang đăng nhập...</span>
            `;
            submitBtn.classList.add('opacity-75', 'cursor-not-allowed');
            
            // Safety timeout to reset button if page doesn't reload (e.g. strict client validation failure)
            // In a real navigation, the page unloads before this fires.
            setTimeout(() => {
                isSubmitting = false;
                submitBtn.innerHTML = originalContent;
                submitBtn.classList.remove('opacity-75', 'cursor-not-allowed');
            }, 5000);
        });

        // Auto-hide notifications after 5 seconds
        document.addEventListener('DOMContentLoaded', function () {
            const notifications = document.querySelectorAll('.notification');
            notifications.forEach(notification => {
                setTimeout(() => {
                    notification.style.transition = 'opacity 0.5s ease-out, transform 0.5s ease-out';
                    notification.style.opacity = '0';
                    notification.style.transform = 'translateY(-10px)';
                    setTimeout(() => notification.remove(), 500);
                }, 5000);
            });

            // Countdown Timer for Lockout (if specific error message exists)
            const errorMsg = document.querySelector('.notification');
            if (errorMsg) {
                const text = errorMsg.textContent;
                // Look for pattern like "60 seconds" or "60 giây"
                const match = text.match(/(\d+)\s+(giây|seconds)/);

                if (match) {
                    let seconds = parseInt(match[1]);
                    const submitBtn = document.getElementById('submitBtn');
                    
                    // Disable button initially
                    submitBtn.disabled = true;
                    submitBtn.classList.add('bg-gray-500', 'hover:bg-gray-500', 'cursor-not-allowed');
                    submitBtn.classList.remove('bg-blue-700', 'hover:bg-blue-800');

                    const interval = setInterval(() => {
                        seconds--;
                        if (seconds > 0) {
                            submitBtn.innerHTML = `
                                <i class="fas fa-lock mr-2"></i>
                                <span>Vui lòng đợi ${seconds}s</span>
                            `;
                        } else {
                            clearInterval(interval);
                            // Re-enable button
                            submitBtn.disabled = false;
                            submitBtn.classList.remove('bg-gray-500', 'hover:bg-gray-500', 'cursor-not-allowed');
                            submitBtn.classList.add('bg-blue-700', 'hover:bg-blue-800');
                            submitBtn.innerHTML = `
                                <i class="fas fa-sign-in-alt mr-2"></i>
                                <span>ĐĂNG NHẬP</span>
                            `;
                        }
                    }, 1000);
                }
            }
        });
    </script>
</body>
</html>
