<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - {{ config('app.name') }}</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="{{ asset('build/vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    @stack('styles')
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <!-- Background Pattern -->
    <div class="absolute inset-0 bg-white bg-opacity-75"></div>
    <div class="absolute inset-0" style="background-image: url('data:image/svg+xml,%3Csvg width="40" height="40" viewBox="0 0 40 40" xmlns="https://www.w3.org/2000/svg"%3E%3Cg fill="%239C92AC" fill-opacity="0.05"%3E%3Cpath d="m0 40h40v-40h-40z"/%3E%3C/g%3E%3C/svg%3E');"></div>
    
    <div class="relative z-10 min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Logo/Header -->
            <div class="text-center">
                <div class="mx-auto h-12 w-12 bg-indigo-600 rounded-full flex items-center justify-center mb-4">
                    <i class="fas fa-dumbbell text-white text-xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-900">{{ config('app.name') }}</h1>
                <p class="text-sm text-gray-600">Hệ thống quản lý đào tạo</p>
            </div>

            <!-- Main Content -->
            <div class="bg-white shadow-xl rounded-lg px-8 py-6 space-y-6">
                @yield('content')
            </div>

            <!-- Footer Links -->
            <div class="text-center space-y-2">
                <div class="flex justify-center space-x-4 text-sm">
                    <a href="{{ route('home') }}" class="text-indigo-600 hover:text-indigo-500">
                        <i class="fas fa-home mr-1"></i>Trang chủ
                    </a>
                    <span class="text-gray-300">|</span>
                    <a href="#" class="text-gray-500 hover:text-gray-700">Hỗ trợ</a>
                    <span class="text-gray-300">|</span>
                    <a href="#" class="text-gray-500 hover:text-gray-700">Điều khoản</a>
                </div>
                <p class="text-xs text-gray-500">
                    © {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
                </p>
            </div>
        </div>
    </div>

    @stack('scripts')
</body>
</html>
