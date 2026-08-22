@extends('layouts.admin')

@section('title', 'Chỉnh sửa Học viên')
@section('page-title', 'Chỉnh sửa Học viên')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Học viên', 'url' => route('students.index')],
    ['title' => 'Chỉnh sửa']
]" />

{{-- Page Header --}}
<x-page-header
    title="CHỈNH SỬA HỌC VIÊN"
    :actions="[
        [
            'url' => route('students.index'),
            'label' => 'Quay lại',
            'icon' => 'arrow-left',
            'color' => 'gray'
        ]
    ]" />

<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    <form action="{{ route('students.update', $student) }}" method="POST">
        @csrf
        @method('PUT')

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
                            :value="old('name', $student->name)"
                        />
                    </div>

                    <!-- Mã học viên -->
                    <div>
                        <x-form.input
                            label="Mã học viên"
                            name="code"
                            placeholder="Nhập mã học viên (tùy chọn)"
                            :value="old('code', $student->code)"
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
                            :value="old('email', $student->email)"
                        />
                    </div>
                </div>
            </div>

            <!-- Mật khẩu -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Mật khẩu</h3>
                <p class="text-sm text-gray-500 mb-4">Để trống nếu không muốn thay đổi mật khẩu</p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <x-form.input
                            type="password"
                            label="Mật khẩu mới"
                            name="password"
                            placeholder="Nhập mật khẩu mới"
                        />
                    </div>
                    <div>
                        <x-form.input
                            type="password"
                            label="Xác nhận mật khẩu"
                            name="password_confirmation"
                            placeholder="Xác nhận mật khẩu"
                        />
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
                            :value="old('class_id', $student->class_id)"
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
                        :checked="old('status', $student->status == 1)"
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
                <i class="bi bi-save mr-2"></i>Cập nhật học viên
            </button>
        </div>
    </form>
</div>
@endsection
