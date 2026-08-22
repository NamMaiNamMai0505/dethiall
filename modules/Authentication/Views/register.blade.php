@extends('authentication::layout')

@section('title', 'Đăng ký')

@section('content')
<div>
    <h2 class="text-center text-2xl font-bold text-gray-900 mb-2">
        Tạo tài khoản mới
    </h2>
    <p class="text-center text-sm text-gray-600 mb-6">
        Hoặc
        <a href="{{ route('login') }}" class="font-medium text-indigo-600 hover:text-indigo-500">
            đăng nhập vào tài khoản có sẵn
        </a>
    </p>
</div>

<!-- Hiển thị lỗi -->
@if ($errors->any())
    <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded-md mb-4">
        <div class="flex items-center mb-2">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <span class="font-medium">Có lỗi xảy ra:</span>
        </div>
        <ul class="list-disc list-inside space-y-1 text-sm">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<form action="{{ route('register') }}" method="POST" class="space-y-4">
    @csrf
    
    <!-- Name Field -->
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1">
            <i class="fas fa-user mr-1"></i>Họ và tên
        </label>
        <input 
            id="name" 
            name="name" 
            type="text" 
            autocomplete="name" 
            required 
            value="{{ old('name') }}"
            class="w-full px-3 py-2 border rounded-md shadow-sm placeholder-gray-400 
                   focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                   {{ $errors->has('name') ? 'border-red-300' : 'border-gray-300' }}" 
            placeholder="Nhập họ và tên của bạn"
        >
        @error('name')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Email Field -->
    <div>
        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
            <i class="fas fa-envelope mr-1"></i>Email
        </label>
        <input 
            id="email" 
            name="email" 
            type="email" 
            autocomplete="email" 
            required 
            value="{{ old('email') }}"
            class="w-full px-3 py-2 border rounded-md shadow-sm placeholder-gray-400 
                   focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                   {{ $errors->has('email') ? 'border-red-300' : 'border-gray-300' }}" 
            placeholder="Nhập địa chỉ email của bạn"
        >
        @error('email')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Password Field -->
    <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
            <i class="fas fa-lock mr-1"></i>Mật khẩu
        </label>
        <div class="relative">
            <input 
                id="password" 
                name="password" 
                type="password" 
                autocomplete="new-password" 
                required 
                class="w-full px-3 py-2 border rounded-md shadow-sm placeholder-gray-400 
                       focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500
                       {{ $errors->has('password') ? 'border-red-300' : 'border-gray-300' }}" 
                placeholder="Nhập mật khẩu (tối thiểu 8 ký tự)"
            >
            <button 
                type="button" 
                onclick="togglePassword('password')"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
            >
                <i id="password-icon" class="fas fa-eye"></i>
            </button>
        </div>
        @error('password')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <!-- Password Confirmation Field -->
    <div>
        <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1">
            <i class="fas fa-lock mr-1"></i>Xác nhận mật khẩu
        </label>
        <div class="relative">
            <input 
                id="password_confirmation" 
                name="password_confirmation" 
                type="password" 
                autocomplete="new-password" 
                required 
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm placeholder-gray-400 
                       focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500" 
                placeholder="Nhập lại mật khẩu"
            >
            <button 
                type="button" 
                onclick="togglePassword('password_confirmation')"
                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600"
            >
                <i id="password-confirmation-icon" class="fas fa-eye"></i>
            </button>
        </div>
    </div>

    <!-- Terms Agreement -->
    <div class="flex items-start">
        <input 
            id="terms" 
            name="terms" 
            type="checkbox" 
            required
            class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded mt-1"
        >
        <label for="terms" class="ml-2 block text-sm text-gray-700">
            Tôi đồng ý với 
            <a href="#" class="text-indigo-600 hover:text-indigo-500">điều khoản sử dụng</a> 
            và 
            <a href="#" class="text-indigo-600 hover:text-indigo-500">chính sách bảo mật</a>
        </label>
    </div>

    <!-- Submit Button -->
    <div>
        <button 
            type="submit" 
            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm 
                   text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 
                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500
                   transition duration-150 ease-in-out"
        >
            <i class="fas fa-user-plus mr-2"></i>
            Tạo tài khoản
        </button>
    </div>

    <!-- Password Requirements -->
    <div class="bg-gray-50 p-3 rounded-md">
        <p class="text-xs text-gray-600 mb-1">
            <i class="fas fa-info-circle mr-1"></i>Yêu cầu mật khẩu:
        </p>
        <ul class="text-xs text-gray-500 space-y-1">
            <li>• Tối thiểu 8 ký tự</li>
            <li>• Nên bao gồm chữ hoa, chữ thường và số</li>
            <li>• Tránh sử dụng thông tin cá nhân</li>
        </ul>
    </div>
</form>

@push('scripts')
<script>
function togglePassword(fieldId) {
    const passwordField = document.getElementById(fieldId);
    const iconId = fieldId === 'password' ? 'password-icon' : 'password-confirmation-icon';
    const passwordIcon = document.getElementById(iconId);
    
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

// Password strength indicator
document.getElementById('password').addEventListener('input', function(e) {
    const password = e.target.value;
    // Add password strength logic here if needed
});
</script>
@endpush
@endsection
