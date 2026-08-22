@extends('authentication::layout')

@section('title', 'Quên mật khẩu')

@section('content')
<div>
    <h2 class="text-center text-2xl font-bold text-gray-900 mb-2">
        Quên mật khẩu?
    </h2>
    <p class="text-center text-sm text-gray-600 mb-6">
        Nhập email của bạn và chúng tôi sẽ gửi link đặt lại mật khẩu
    </p>
</div>

<!-- Hiển thị thông báo thành công -->
@if (session('status'))
    <div class="bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded-md mb-4">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            {{ session('status') }}
        </div>
    </div>
@endif

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

<form action="{{ route('password.email') }}" method="POST" class="space-y-4">
    @csrf
    
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
            placeholder="Nhập địa chỉ email đã đăng ký"
        >
        @error('email')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror
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
            <i class="fas fa-paper-plane mr-2"></i>
            Gửi link đặt lại mật khẩu
        </button>
    </div>

    <!-- Back to Login -->
    <div class="text-center">
        <a href="{{ route('login') }}" class="text-sm text-indigo-600 hover:text-indigo-500">
            <i class="fas fa-arrow-left mr-1"></i>Quay lại đăng nhập
        </a>
    </div>
</form>

<!-- Info Box -->
<div class="bg-blue-50 p-4 rounded-md">
    <div class="flex items-start">
        <i class="fas fa-info-circle text-blue-400 mt-0.5 mr-3"></i>
        <div class="text-sm text-blue-700">
            <p class="font-medium mb-1">Lưu ý:</p>
            <ul class="space-y-1 text-xs">
                <li>• Link đặt lại mật khẩu sẽ có hiệu lực trong 60 phút</li>
                <li>• Kiểm tra cả hộp thư spam nếu không thấy email</li>
                <li>• Nếu vẫn không nhận được, hãy liên hệ hỗ trợ</li>
            </ul>
        </div>
    </div>
</div>
@endsection
