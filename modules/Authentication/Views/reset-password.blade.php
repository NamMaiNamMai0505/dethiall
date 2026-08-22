@extends('authentication::layout')

@section('title', 'Đặt lại mật khẩu')

@section('content')
<div>
    <h2 class="text-center text-2xl font-bold text-gray-900 mb-2">
        Đặt lại mật khẩu
    </h2>
    <p class="text-center text-sm text-gray-600 mb-6">
        Nhập mật khẩu mới cho tài khoản của bạn
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

<form action="{{ route('password.update') }}" method="POST" class="space-y-4">
    @csrf
    
    <!-- Hidden fields -->
    <input type="hidden" name="token" value="{{ $token }}">
    <input type="hidden" name="email" value="{{ $email }}">
    
    <!-- Email Display -->
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1">
            <i class="fas fa-envelope mr-1"></i>Email
        </label>
        <div class="w-full px-3 py-2 bg-gray-50 border border-gray-300 rounded-md text-gray-600">
            {{ $email }}
        </div>
    </div>

    <!-- Password Field -->
    <div>
        <label for="password" class="block text-sm font-medium text-gray-700 mb-1">
            <i class="fas fa-lock mr-1"></i>Mật khẩu mới
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
                placeholder="Nhập mật khẩu mới (tối thiểu 8 ký tự)"
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
            <i class="fas fa-lock mr-1"></i>Xác nhận mật khẩu mới
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
                placeholder="Nhập lại mật khẩu mới"
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

    <!-- Submit Button -->
    <div>
        <button 
            type="submit" 
            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-md shadow-sm 
                   text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 
                   focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500
                   transition duration-150 ease-in-out"
        >
            <i class="fas fa-key mr-2"></i>
            Đặt lại mật khẩu
        </button>
    </div>

    <!-- Back to Login -->
    <div class="text-center">
        <a href="{{ route('login') }}" class="text-sm text-indigo-600 hover:text-indigo-500">
            <i class="fas fa-arrow-left mr-1"></i>Quay lại đăng nhập
        </a>
    </div>
</form>

<!-- Password Requirements -->
<div class="bg-gray-50 p-4 rounded-md">
    <p class="text-sm text-gray-700 mb-2">
        <i class="fas fa-info-circle mr-1"></i>Yêu cầu mật khẩu mới:
    </p>
    <ul class="text-sm text-gray-600 space-y-1">
        <li class="flex items-center">
            <i class="fas fa-check text-green-500 mr-2 text-xs"></i>
            Tối thiểu 8 ký tự
        </li>
        <li class="flex items-center">
            <i class="fas fa-check text-green-500 mr-2 text-xs"></i>
            Nên bao gồm chữ hoa, chữ thường và số
        </li>
        <li class="flex items-center">
            <i class="fas fa-check text-green-500 mr-2 text-xs"></i>
            Khác với mật khẩu cũ
        </li>
        <li class="flex items-center">
            <i class="fas fa-check text-green-500 mr-2 text-xs"></i>
            Tránh sử dụng thông tin cá nhân
        </li>
    </ul>
</div>

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

// Password match validation
document.getElementById('password_confirmation').addEventListener('input', function(e) {
    const password = document.getElementById('password').value;
    const confirmation = e.target.value;
    
    if (password && confirmation) {
        if (password === confirmation) {
            e.target.classList.remove('border-red-300');
            e.target.classList.add('border-green-300');
        } else {
            e.target.classList.remove('border-green-300');
            e.target.classList.add('border-red-300');
        }
    }
});
</script>
@endpush
@endsection
