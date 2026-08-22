@extends('layouts.admin')

@section('title', 'Chi tiết giảng viên')
@section('page-title', 'Chi tiết giảng viên')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Giảng viên', 'url' => route('instructors.index')],
    ['title' => 'Chi tiết']
]" />

{{-- Page Header --}}
<x-page-header
    title="CHI TIẾT GIẢNG VIÊN"
    :actions="[
        [
            'url' => route('instructors.edit', $instructor),
            'label' => 'Chỉnh sửa',
            'icon' => 'pencil',
            'color' => 'blue'
        ],
        [
            'url' => route('instructors.index'),
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
                    <div class="mt-1 text-gray-900">{{ $instructor->name }}</div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Mã giảng viên</label>
                    <div class="mt-1 text-gray-900">{{ $instructor->code }}</div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Email</label>
                    <div class="mt-1 text-gray-900">{{ $instructor->email }}</div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Số điện thoại</label>
                    <div class="mt-1 text-gray-900">{{ $instructor->phone }}</div>
                </div>

                {{-- <div>
                    <label class="text-sm font-medium text-gray-600">Chuyên môn</label>
                    <div class="mt-1 text-gray-900">{{ $instructor->specialization->name ?? 'Chưa cập nhật' }}</div>
                </div> --}}
                
                <div>
                    <label class="text-sm font-medium text-gray-600">Đơn vị</label>
                    <div class="mt-1 text-gray-900">{{ $instructor->unit->name ?? 'Chưa cập nhật' }}</div>
                </div>

                @if($instructor->description)
                <div>
                    <label class="text-sm font-medium text-gray-600">Mô tả</label>
                    <div class="mt-1 text-gray-900">{{ $instructor->description }}</div>
                </div>
                @endif

                <div>
                    <label class="text-sm font-medium text-gray-600">Trạng thái</label>
                    <div class="mt-1">
                        @if($instructor->status === 'active')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Hoạt động
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Tạm ngừng
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Thông tin tài khoản đăng nhập --}}
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">Tài khoản đăng nhập</h3>
            @if(!$instructor->hasUserAccount())
                <a href="{{ route('instructors.create-user-account', $instructor) }}"
                   class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Tạo tài khoản
                </a>
            @endif
        </div>
        <div class="p-4">
            @if($instructor->hasUserAccount())
                @php
                    $user = $instructor->user;
                @endphp
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Email đăng nhập</label>
                        <div class="mt-1 text-gray-900">{{ $user->email }}</div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-600">Vai trò</label>
                        <div class="mt-1 text-gray-900">{{ $user->roleRelation->name ?? 'N/A' }}</div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-600">Trạng thái tài khoản</label>
                        <div class="mt-1">
                            @if($user->status == 1)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Hoạt động
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    Bị khóa
                                </span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <label class="text-sm font-medium text-gray-600">Ngày tạo tài khoản</label>
                        <div class="mt-1 text-gray-900">{{ $user->created_at->format('d/m/Y H:i') }}</div>
                    </div>
                </div>
                <div class="mt-4 pt-4 border-t border-gray-200">
                    <a href="{{ route('users.edit', $user) }}"
                       class="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-700 bg-blue-50 hover:bg-blue-100 rounded-lg transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Chỉnh sửa tài khoản
                    </a>
                </div>
            @else
                <div class="text-center py-8">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">Chưa có tài khoản đăng nhập</h3>
                    <p class="mt-1 text-sm text-gray-500">Giảng viên này chưa có tài khoản để đăng nhập vào hệ thống.</p>
                    <div class="mt-6">
                        <a href="{{ route('instructors.create-user-account', $instructor) }}"
                           class="inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Tạo tài khoản đăng nhập
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- Thông tin cập nhật --}}
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">Thông tin hệ thống</h3>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-600">Người tạo</label>
                    <div class="mt-1 text-gray-900">{{ $instructor->creator->name ?? 'N/A' }}</div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Người cập nhật</label>
                    <div class="mt-1 text-gray-900">{{ $instructor->updater->name ?? 'N/A' }}</div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Ngày tạo</label>
                    <div class="mt-1 text-gray-900">{{ $instructor->created_at->format('d/m/Y H:i') }}</div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Cập nhật lần cuối</label>
                    <div class="mt-1 text-gray-900">{{ $instructor->updated_at->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($instructor->trashed())
    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="bi bi-exclamation-triangle text-red-400"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">
                    Giảng viên đã bị xóa
                </h3>
                <div class="mt-2">
                    <form action="{{ route('instructors.restore', $instructor->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="btn btn-danger">
                            Khôi phục
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif

@endsection
