@extends('layouts.admin')

@section('title', 'Quản lý Học viên')
@section('page-title', 'Quản lý Học viên')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Học viên', 'url' => route('students.index')],
    ['title' => 'Tạo mới']
]" />

{{-- Page Header --}}
<x-page-header
    title="TẠO MỚI HỌC VIÊN"
    :actions="[
        [
            'url' => route('students.index'),
            'label' => 'Quay lại',
            'icon' => 'arrow-left',
            'color' => 'gray'
        ]
    ]" />

<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    <form action="{{ route('students.store') }}" method="POST">
        @csrf

        <div class="p-6 space-y-6">
            <!-- Thông tin cơ bản -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Thông tin cơ bản</h3>
                <div class="grid grid-cols-2 gap-4">
                    <!-- Họ và tên -->
                    <div>
                        <x-form.input
                            label="Họ và tên"
                            name="name"
                            required
                            placeholder="Nhập họ và tên"
                            :value="old('name')"
                        />
                    </div>

                    <!-- Mã học viên -->
                    <div>
                        <x-form.input
                            label="Mã học viên"
                            name="code"
                            placeholder="Nhập mã học viên (tùy chọn)"
                            :value="old('code')"
                        />
                    </div>

                    <!-- Email -->
                    <div>
                        <x-form.input
                            type="email"
                            label="Email"
                            name="email"
                            required
                            placeholder="Nhập email"
                            :value="old('email')"
                        />
                    </div>
                </div>
            </div>

            <!-- Mật khẩu -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Mật khẩu</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="relative">
                        <x-form.input
                            type="password"
                            label="Mật khẩu"
                            name="password"
                            required
                            placeholder="Nhập mật khẩu"
                            id="password"
                        />
                        <button type="button" onclick="togglePassword('password', this)" class="absolute right-3 top-9 text-gray-500 focus:outline-none">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="relative">
                        <x-form.input
                            type="password"
                            label="Xác nhận mật khẩu"
                            name="password_confirmation"
                            required
                            placeholder="Xác nhận mật khẩu"
                            id="password_confirmation"
                        />
                        <button type="button" onclick="togglePassword('password_confirmation', this)" class="absolute right-3 top-9 text-gray-500 focus:outline-none">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Phân quyền -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Phân quyền</h3>
                <div class="grid grid-cols-1 gap-4">
                    <!-- Lớp học -->
                    <div>
                        <x-form.select
                            label="Lớp học"
                            name="class_id"
                            required
                            :options="$classes"
                            placeholder="Chọn lớp học"
                            :value="old('class_id')"
                        />
                    </div>
                </div>
            </div>

            <!-- Trạng thái -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Trạng thái</h3>
                <div>
                    <x-form.checkbox
                        label="Hoạt động"
                        name="status"
                        :checked="old('status', true)"
                    />
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="bg-gray-50 px-6 py-4 border-t flex justify-end space-x-2">
            <a href="{{ route('students.index') }}" class="btn btn-secondary">
                <i class="bi bi-x-lg mr-2"></i>Hủy
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="bi bi-save mr-2"></i>Lưu học viên
            </button>
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
