@extends('layouts.admin')

@section('title', 'Chi tiết Học viên')
@section('page-title', 'Chi tiết Học viên')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Học viên', 'url' => route('students.index')],
    ['title' => 'Chi tiết']
]" />

{{-- Page Header --}}
<x-page-header
    title="CHI TIẾT HỌC VIÊN"
    :actions="[
        [
            'url' => route('students.edit', $student),
            'label' => 'Chỉnh sửa',
            'icon' => 'pencil',
            'color' => 'blue'
        ],
        [
            'url' => route('students.index'),
            'label' => 'Quay lại danh sách',
            'icon' => 'arrow-left',
            'color' => 'gray'
        ]
    ]" />

<div class="grid gap-6 mb-6">
    {{-- Thông tin cơ bản --}}
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">Thông tin cơ bản</h3>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-600">Họ và tên</label>
                    <div class="mt-1 text-gray-900">{{ $student->name }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Mã học viên</label>
                    <div class="mt-1 text-gray-900">{{ $student->code ?? '-' }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Email</label>
                    <div class="mt-1 text-gray-900">{{ $student->email }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Lớp học</label>
                    <div class="mt-1 text-gray-900">{{ $student->class->name ?? 'N/A' }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Trạng thái</label>
                    <div class="mt-1">
                        <x-status-badge :is-active="$student->status == 1" />
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Ngày tạo</label>
                    <div class="mt-1 text-gray-900">{{ $student->created_at->format('d/m/Y H:i') }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Cập nhật lần cuối</label>
                    <div class="mt-1 text-gray-900">{{ $student->updated_at->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Xóa học viên --}}
    @canPermission('students.delete')
    <div class="bg-white rounded-lg shadow-sm p-4">
        <h3 class="font-semibold text-red-700 mb-4">Xóa Học viên</h3>
        <form action="{{ route('students.destroy', $student) }}" method="POST" data-confirm='Bạn có chắc chắn muốn xóa học viên này?'>
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Xóa</button>
        </form>
    </div>
    @endcanPermission
</div>
@endsection
