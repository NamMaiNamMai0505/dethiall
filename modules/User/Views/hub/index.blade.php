@extends('layouts.admin')

@section('title', 'Quản lý tài khoản')
@section('page-title', 'Quản lý tài khoản')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Quản lý tài khoản']
]" />

<x-page-header
    title="QUẢN LÝ TÀI KHOẢN"
    subtitle="Người dùng, học viên, vai trò và phân quyền truy cập tính năng"
    :actions="[[
        'url' => url()->previous() !== url()->current() ? url()->previous() : route('dashboard'),
        'label' => 'Quay lại',
        'icon' => 'arrow-left',
        'color' => 'gray'
    ]]"
/>

{{-- Stats --}}
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-8">
    <div class="bg-white rounded-xl shadow-sm border p-5 border-l-4 border-blue-500">
        <p class="text-sm text-gray-600">Người dùng nội bộ</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['users'] }}</p>
        <p class="text-xs text-gray-400 mt-1">Không tính học viên</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border p-5 border-l-4 border-green-500">
        <p class="text-sm text-gray-600">Học viên</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['students'] }}</p>
        <p class="text-xs text-gray-400 mt-1">user_type = student</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border p-5 border-l-4 border-purple-500">
        <p class="text-sm text-gray-600">Giảng viên (TK)</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['instructors'] }}</p>
        <p class="text-xs text-gray-400 mt-1">user_type = instructor</p>
    </div>
    <div class="bg-white rounded-xl shadow-sm border p-5 border-l-4 border-amber-500">
        <p class="text-sm text-gray-600">Vai trò / Quyền</p>
        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['roles'] }} <span class="text-lg text-gray-400">/</span> {{ $stats['permissions'] }}</p>
        <p class="text-xs text-gray-400 mt-1">{{ $stats['active_users'] }} tài khoản đang hoạt động</p>
    </div>
</div>

{{-- Menu chức năng --}}
<div class="mb-4">
    <h2 class="text-lg font-semibold text-gray-900">Chức năng</h2>
    <p class="text-sm text-gray-500">Chọn tính năng cần thao tác</p>
</div>

@php
    $menuItems = [
        [
            'route' => 'users.index',
            'icon' => 'bi-person-badge',
            'label' => 'Người dùng nội bộ',
            'desc' => 'Tài khoản quản trị, cán bộ',
            'perm' => 'users.index',
            'iconBg' => 'bg-blue-100 text-blue-700',
        ],
        [
            'route' => 'students.index',
            'icon' => 'bi-mortarboard',
            'label' => 'Học viên',
            'desc' => 'Tài khoản học viên theo lớp',
            'perm' => 'users.index',
            'iconBg' => 'bg-green-100 text-green-700',
        ],
        [
            'route' => 'roles.index',
            'icon' => 'bi-shield-lock',
            'label' => 'Vai trò & phân quyền',
            'desc' => 'Gán quyền xem / dùng tính năng',
            'perm' => 'roles.index',
            'iconBg' => 'bg-amber-100 text-amber-800',
            'role' => 'super-admin',
        ],
        [
            'route' => 'roles.integrity',
            'icon' => 'bi-shield-check',
            'label' => 'Sức khỏe phân quyền',
            'desc' => 'Phát hiện role và phạm vi bị lệch',
            'perm' => 'roles.index',
            'iconBg' => 'bg-emerald-100 text-emerald-800',
            'role' => 'super-admin',
        ],
    ];
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-10">
    @foreach($menuItems as $item)
        @php
            $canAccess = auth()->user()->can($item['perm']);
            if (!empty($item['role'])) {
                $canAccess = $canAccess && auth()->user()->hasRole($item['role']);
            }
        @endphp
        @if($canAccess)
            <a href="{{ route($item['route']) }}"
               class="group bg-white rounded-xl shadow-sm border hover:shadow-md hover:border-blue-200 transition p-5 flex flex-col">
                <div class="w-11 h-11 rounded-lg {{ $item['iconBg'] }} flex items-center justify-center mb-3">
                    <i class="{{ $item['icon'] }} text-xl"></i>
                </div>
                <span class="font-semibold text-gray-900 group-hover:text-blue-700">{{ $item['label'] }}</span>
                <span class="text-sm text-gray-500 mt-1">{{ $item['desc'] }}</span>
                <span class="text-xs text-gray-400 mt-3">Nhấn để mở →</span>
            </a>
        @endif
    @endforeach
</div>

{{-- Tóm tắt vai trò --}}
<div class="mb-4 flex items-center justify-between gap-3">
    <div>
        <h2 class="text-lg font-semibold text-gray-900">Vai trò hệ thống</h2>
        <p class="text-sm text-gray-500">Xem nhanh số quyền / số người; bấm để chỉnh quyền chi tiết</p>
    </div>
    @role('super-admin')
        @can('roles.create')
            <a href="{{ route('roles.create') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white shadow-sm">
                <i class="bi bi-plus-lg"></i> Tạo vai trò
            </a>
        @endcan
    @endrole
</div>

<div class="bg-white rounded-xl shadow-sm border overflow-hidden">
    @if($roles->isEmpty())
        <div class="p-8 text-center text-gray-500">Chưa có vai trò nào.</div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase">Vai trò</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Số quyền</th>
                        <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase">Số người</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($roles as $role)
                        @php $isSuperAdmin = $role->name === 'super-admin'; @endphp
                        <tr class="hover:bg-blue-50/40">
                            <td class="px-5 py-3">
                                <div class="font-medium text-gray-900">{{ $role->name }}</div>
                                @if($isSuperAdmin)
                                    <div class="text-xs text-amber-600 mt-0.5">Toàn quyền · không xóa được</div>
                                @endif
                            </td>
                            <td class="px-5 py-3 text-center">
                                <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700">
                                    {{ $role->permissions_count }}
                                </span>
                            </td>
                            <td class="px-5 py-3 text-center text-sm text-gray-700">
                                {{ $role->users_count }}
                            </td>
                            <td class="px-5 py-3 text-right">
                                <div class="flex space-x-2 items-center justify-end action-icons">
                                    @role('super-admin')
                                        <a href="{{ route('roles.show', $role) }}"
                                           class="action-icon text-blue-600"
                                           title="Xem phân quyền">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                        <a href="{{ route('roles.edit', $role) }}"
                                           class="action-icon text-green-600"
                                           title="Cập nhật phân quyền">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        @unless($isSuperAdmin)
                                            <form action="{{ route('roles.destroy', $role) }}" method="POST" class="inline"
                                                  data-confirm="Xóa vai trò «{{ $role->name }}»? Người dùng gán role này sẽ mất role đó."
                                                  data-confirm-danger="1"
                                                  data-confirm-ok="Xóa">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="action-icon text-red-600"
                                                        title="Xóa vai trò">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        @endunless
                                    @else
                                        <span class="text-xs text-gray-400">Chỉ super-admin chỉnh</span>
                                    @endrole
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
