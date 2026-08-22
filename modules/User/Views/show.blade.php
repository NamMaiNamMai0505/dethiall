@extends('layouts.admin')

@section('title', 'Chi tiết Người dùng')
@section('page-title', 'Chi tiết Người dùng')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Người dùng', 'url' => route('users.index')],
    ['title' => 'Chi tiết']
]" />

{{-- Page Header --}}
<x-page-header
    title="CHI TIẾT NGƯỜI DÙNG"
    :actions="[
        [
            'url' => route('users.edit', $user),
            'label' => 'Chỉnh sửa',
            'icon' => 'pencil',
            'color' => 'blue'
        ],
        [
            'url' => route('users.index'),
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
                    <div class="mt-1 text-gray-900">{{ $user->name }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Mã người dùng</label>
                    <div class="mt-1 text-gray-900">{{ $user->code ?? '-' }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Email</label>
                    <div class="mt-1 text-gray-900">{{ $user->email }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Số điện thoại</label>
                    <div class="mt-1 text-gray-900">{{ $user->phone ?? '-' }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Loại người dùng</label>
                    <div class="mt-1">
                        @if($user->user_type === 'instructor')
                            <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs">Giảng viên</span>
                        @elseif($user->user_type === 'internal_user')
                            <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">Nội bộ</span>
                        @elseif($user->user_type === 'student')
                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded text-xs">Học viên</span>
                        @else
                            <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs">-</span>
                        @endif
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Đơn vị</label>
                    <div class="mt-1 text-gray-900">{{ $user->unit->name ?? 'N/A' }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Vai trò</label>
                    <div class="mt-1 text-gray-900">{{ $user->roleRelation->name ?? 'N/A' }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Cấp bậc</label>
                    <div class="mt-1">
                        @if($user->militaryRank)
                            <x-military-rank-badge :rank="$user->militaryRank" />
                        @else
                            <span class="text-gray-500">Chưa gán</span>
                        @endif
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Trạng thái</label>
                    <div class="mt-1">
                        <x-status-badge :is-active="$user->status == 1" />
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Ngày tạo</label>
                    <div class="mt-1 text-gray-900">{{ $user->created_at->format('d/m/Y H:i') }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Cập nhật lần cuối</label>
                    <div class="mt-1 text-gray-900">{{ $user->updated_at->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Xóa người dùng --}}
    <div class="bg-white rounded-lg shadow-sm p-4">
        <h3 class="font-semibold text-red-700 mb-4">Xóa Người dùng</h3>
        <form action="{{ route('users.destroy', $user) }}" method="POST" data-confirm='Bạn có chắc chắn muốn xóa người dùng này?'>
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-danger">Xóa</button>
        </form>
    </div>
</div>
@endsection
