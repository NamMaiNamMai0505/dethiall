@extends('layouts.admin')

@section('title', 'Tạo tài khoản đăng nhập cho giảng viên')
@section('page-title', 'Tạo tài khoản đăng nhập')

@section('content')
{{-- Flash Messages --}}
@if(session('success'))
    <div class="mb-4 px-4 py-2 bg-green-100 border border-green-200 text-green-700 rounded">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="mb-4 px-4 py-2 bg-red-100 border border-red-200 text-red-700 rounded">
        {{ session('error') }}
    </div>
@endif

{{-- Header với nút actions --}}
<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-900">TẠO TÀI KHOẢN ĐĂNG NHẬP</h1>
        <p class="text-sm text-gray-600 mt-1">Cho giảng viên: <span class="font-medium">{{ $instructor->name }} ({{ $instructor->code }})</span></p>
    </div>
    <div class="flex gap-3">
        <button type="button"
                onclick="document.getElementById('user-account-form').submit()"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
            Tạo tài khoản
        </button>
        <a href="{{ route('instructors.show', $instructor) }}"
           class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">
            Hủy bỏ
        </a>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm p-6">
    <form id="user-account-form" action="{{ route('instructors.store-user-account', $instructor) }}" method="POST">
        @csrf

        {{-- Thông tin giảng viên (read-only) --}}
        <div class="mb-6 p-4 bg-gray-50 rounded-lg border border-gray-200">
            <h3 class="text-sm font-semibold text-gray-700 mb-3">Thông tin giảng viên</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-600">Họ tên:</span>
                    <span class="ml-2 font-medium text-gray-900">{{ $instructor->name }}</span>
                </div>
                <div>
                    <span class="text-gray-600">Mã GV:</span>
                    <span class="ml-2 font-medium text-gray-900">{{ $instructor->code }}</span>
                </div>
                <div>
                    <span class="text-gray-600">Email GV:</span>
                    <span class="ml-2 font-medium text-gray-900">{{ $instructor->email }}</span>
                </div>
                <div>
                    <span class="text-gray-600">Đơn vị:</span>
                    <span class="ml-2 font-medium text-gray-900">{{ $instructor->unit->name ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Email đăng nhập --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2" for="email">
                    Email đăng nhập <span class="text-red-500">*</span>
                </label>
                <input type="email"
                       id="email"
                       name="email"
                       value="{{ old('email', $instructor->email) }}"
                       required
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-500 @enderror">
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Email này sẽ được dùng để đăng nhập vào hệ thống</p>
            </div>

            {{-- Vai trò --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2" for="role_id">
                    Vai trò <span class="text-red-500">*</span>
                </label>
                <select id="role_id"
                        name="role_id"
                        required
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('role_id') border-red-500 @enderror">
                    <option value="">-- Chọn vai trò --</option>
                    @foreach(\Spatie\Permission\Models\Role::all() as $role)
                        <option value="{{ $role->id }}"
                                {{ old('role_id', $defaultRole->id ?? '') == $role->id ? 'selected' : '' }}>
                            {{ $role->name }}
                        </option>
                    @endforeach
                </select>
                @error('role_id')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Mật khẩu --}}
            <div class="relative">
                <label class="block text-sm font-medium text-gray-700 mb-2" for="password">
                    Mật khẩu <span class="text-red-500">*</span>
                </label>
                <input type="password"
                       id="password"
                       name="password"
                       required
                       minlength="6"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('password') border-red-500 @enderror">
                <button type="button" onclick="togglePassword('password', this)" class="absolute right-3 top-9 text-gray-500 focus:outline-none">
                    <i class="bi bi-eye"></i>
                </button>
                @error('password')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                <p class="mt-1 text-xs text-gray-500">Tối thiểu 6 ký tự</p>
            </div>

            {{-- Xác nhận mật khẩu --}}
            <div class="relative">
                <label class="block text-sm font-medium text-gray-700 mb-2" for="password_confirmation">
                    Xác nhận mật khẩu <span class="text-red-500">*</span>
                </label>
                <input type="password"
                       id="password_confirmation"
                       name="password_confirmation"
                       required
                       minlength="6"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <button type="button" onclick="togglePassword('password_confirmation', this)" class="absolute right-3 top-9 text-gray-500 focus:outline-none">
                    <i class="bi bi-eye"></i>
                </button>
                <p class="mt-1 text-xs text-gray-500">Nhập lại mật khẩu để xác nhận</p>
            </div>
        </div>

        {{-- Thông tin tự động --}}
        <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                </svg>
                <div class="text-sm text-blue-800">
                    <p class="font-medium">Thông tin tự động:</p>
                    <ul class="mt-2 space-y-1 list-disc list-inside">
                        <li>Tên người dùng: <span class="font-medium">{{ $instructor->name }}</span></li>
                        <li>Mã người dùng: <span class="font-medium">{{ $instructor->code }}</span></li>
                        <li>Loại tài khoản: <span class="font-medium">Giảng viên</span></li>
                        <li>Đơn vị: <span class="font-medium">{{ $instructor->unit->name ?? 'N/A' }}</span></li>
                    </ul>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function togglePassword(fieldId, btn) {
    const input = document.getElementById(fieldId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = '<i class="bi bi-eye-slash"></i>';
    } else {
        input.type = 'password';
        btn.innerHTML = '<i class="bi bi-eye"></i>';
    }
}
</script>
@endpush
