@extends('layouts.admin')

@section('title', 'Thông tin tài khoản')
@section('page-title', 'Thông tin tài khoản')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Thông tin tài khoản']
]" />

<x-page-header
    title="THÔNG TIN TÀI KHOẢN"
    subtitle="Cập nhật họ tên và mật khẩu đăng nhập"
    :actions="[
        ['label' => 'Chữ ký số', 'url' => route('signatures.index'), 'icon' => 'pen', 'color' => 'secondary'],
    ]" />

@if(session('success'))
    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
        {{ session('success') }}
    </div>
@endif

<div class="grid gap-6 mb-6 lg:grid-cols-3">
    {{-- Tóm tắt (chỉ xem) --}}
    <div class="bg-white rounded-xl shadow-sm border p-5 lg:col-span-1 space-y-4">
        <div>
            <p class="text-sm text-gray-500">Mã người dùng</p>
            <p class="mt-0.5 font-semibold text-gray-900">{{ $user->code ?? '—' }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500">Email</p>
            <p class="mt-0.5 font-semibold text-gray-900 break-all">{{ $user->email }}</p>
        </div>
        <div>
            <p class="text-sm text-gray-500">Loại tài khoản</p>
            <div class="mt-1">
                @if($user->user_type === 'instructor')
                    <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs font-medium">Giảng viên</span>
                @elseif($user->user_type === 'internal_user')
                    <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-medium">Nội bộ</span>
                @elseif($user->user_type === 'student')
                    <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs font-medium">Học viên</span>
                @else
                    <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs font-medium">{{ $user->user_type ?: '—' }}</span>
                @endif
            </div>
        </div>
        <div>
            <p class="text-sm text-gray-500">Vai trò</p>
            <p class="mt-0.5 font-semibold text-gray-900">
                {{ $user->getRoleNames()->implode(', ') ?: ($user->roleRelation->name ?? '—') }}
            </p>
        </div>
        @if($user->militaryRank)
            <div>
                <p class="text-sm text-gray-500">Cấp bậc</p>
                <div class="mt-1"><x-military-rank-badge :rank="$user->militaryRank" /></div>
            </div>
        @endif
        @if($user->unit)
            <div>
                <p class="text-sm text-gray-500">Đơn vị</p>
                <p class="mt-0.5 font-semibold text-gray-900">{{ $user->unit->name }}</p>
            </div>
        @endif
        @if($user->user_type === 'student' && $user->class)
            <div>
                <p class="text-sm text-gray-500">Lớp học</p>
                <p class="mt-0.5 font-semibold text-gray-900">{{ $user->class->name }}</p>
            </div>
        @endif
        @if($user->user_type === 'instructor' && $user->instructor)
            <div>
                <p class="text-sm text-gray-500">Mã giảng viên</p>
                <p class="mt-0.5 font-semibold text-gray-900">{{ $user->instructor->code ?? '—' }}</p>
            </div>
        @endif
        <div>
            <p class="text-sm text-gray-500">Trạng thái</p>
            <div class="mt-1">
                <x-status-badge :is-active="$user->status == 1" />
            </div>
        </div>
    </div>

    {{-- Form chỉnh sửa: họ tên + mật khẩu --}}
    <div class="bg-white rounded-xl shadow-sm border p-5 lg:col-span-2">
        <h3 class="text-base font-semibold text-gray-900 mb-1">Cập nhật thông tin</h3>
        <p class="text-sm text-gray-500 mb-5">
            Giảng viên và học viên có thể tự đổi <strong>họ và tên</strong> và <strong>mật khẩu</strong>.
            Email / mã tài khoản do quản trị viên quản lý.
        </p>

        <form action="{{ route('profile.update') }}" method="POST" class="space-y-5" autocomplete="off">
            @csrf
            @method('PUT')

            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">
                    Họ và tên <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name" required
                       value="{{ old('name', $user->name) }}"
                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
                       placeholder="Nhập họ và tên">
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div class="border-t border-gray-100 pt-5">
                <h4 class="text-sm font-semibold text-gray-900 mb-1">Đổi mật khẩu</h4>
                <p class="text-xs text-gray-500 mb-4">Để trống nếu không muốn đổi mật khẩu.</p>

                <div class="grid grid-cols-1 md:grid-cols-1 gap-4">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 mb-1.5">
                            Mật khẩu hiện tại
                        </label>
                        <div class="relative">
                            <input type="password" name="current_password" id="current_password"
                                   class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-10 @error('current_password') border-red-500 @enderror"
                                   placeholder="Nhập mật khẩu hiện tại"
                                   autocomplete="current-password">
                            <button type="button" onclick="toggleProfilePassword('current_password', this)"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        @error('current_password')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-1.5">
                                Mật khẩu mới
                            </label>
                            <div class="relative">
                                <input type="password" name="password" id="password"
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-10 @error('password') border-red-500 @enderror"
                                       placeholder="Tối thiểu 8 ký tự"
                                       autocomplete="new-password">
                                <button type="button" onclick="toggleProfilePassword('password', this)"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-1.5">
                                Xác nhận mật khẩu mới
                            </label>
                            <div class="relative">
                                <input type="password" name="password_confirmation" id="password_confirmation"
                                       class="w-full px-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-10"
                                       placeholder="Nhập lại mật khẩu mới"
                                       autocomplete="new-password">
                                <button type="button" onclick="toggleProfilePassword('password_confirmation', this)"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap justify-end gap-2 pt-2 border-t border-gray-100">
                <button type="submit"
                        class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white shadow-sm">
                    <i class="bi bi-check-lg"></i>
                    Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleProfilePassword(id, btn) {
    const input = document.getElementById(id);
    if (!input) return;
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        if (icon) icon.className = 'bi bi-eye-slash';
    } else {
        input.type = 'password';
        if (icon) icon.className = 'bi bi-eye';
    }
}
</script>
@endpush
