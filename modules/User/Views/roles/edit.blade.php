@extends('layouts.admin')

@section('title', 'Phân quyền vai trò')
@section('page-title', 'Phân quyền vai trò')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Quản lý tài khoản', 'url' => route('accounts.hub')],
    ['title' => 'Vai trò', 'url' => route('roles.index')],
    ['title' => \App\Support\RoleDisplay::label($role->name)]
]" />

<x-page-header
    title="PHÂN QUYỀN: {{ mb_strtoupper(\App\Support\RoleDisplay::label($role->name)) }}"
    subtitle="Chọn module/tính năng mà vai trò này được xem và sử dụng"
    :actions="[
        [
            'url' => route('roles.show', $role),
            'label' => 'Xem chi tiết',
            'icon' => 'eye',
            'color' => 'gray'
        ],
        [
            'url' => route('roles.index'),
            'label' => 'Danh sách vai trò',
            'icon' => 'arrow-left',
            'color' => 'gray'
        ],
        [
            'url' => route('accounts.hub'),
            'label' => 'Hub tài khoản',
            'icon' => 'grid',
            'color' => 'gray'
        ],
    ]" />

@if(session('success'))
    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
        {{ session('success') }}
    </div>
@endif

<form action="{{ route('roles.update', $role) }}" method="POST" class="space-y-6">
    @csrf
    @method('PUT')

    <div class="bg-white rounded-xl shadow-sm border p-5">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">
                    Tên vai trò <span class="text-red-500">*</span>
                </label>
                <input type="text" name="name" id="name"
                       value="{{ old('name', $role->name) }}"
                       required
                       @if(!empty($isSuperAdmin)) readonly @endif
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @if(!empty($isSuperAdmin)) bg-gray-50 text-gray-600 @endif @error('name') border-red-500 @enderror"
                       placeholder="vd: manager">
                @if(!empty($isSuperAdmin))
                    <p class="mt-1 text-xs text-amber-600">super-admin — không đổi tên; luôn giữ toàn bộ quyền.</p>
                @endif
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex items-end">
                <div class="text-sm text-gray-600">
                    Đang gán: <strong class="text-gray-900">{{ $grantedCount }}</strong> quyền
                    · Guard: <code class="text-xs bg-gray-100 px-1 rounded">{{ $role->guard_name }}</code>
                </div>
            </div>
        </div>

        @php($catalog = \App\Support\RoleCatalog::find($role->name))
        @if($catalog)
            <div class="mt-4 rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-900">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="rounded-full bg-emerald-600 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide text-white">
                        Vai trò chuẩn
                    </span>
                    <strong>{{ $catalog['label'] }}</strong>
                    <span class="inline-flex items-center gap-1 rounded-full bg-white px-2 py-0.5 text-xs font-medium text-emerald-800 ring-1 ring-inset ring-emerald-200">
                        <i class="bi bi-bullseye text-[10px]"></i>{{ $catalog['scope'] }}
                    </span>
                </div>
                <p class="mt-1.5 leading-5">{{ $catalog['description'] }}</p>
            </div>
        @endif

        @if($role->name === \App\Support\ManagementRole::SUPER_ADMIN)
            <div class="mt-3 rounded-lg border border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                <i class="bi bi-shield-lock mr-1"></i>
                Vai trò <strong>Super Admin</strong> luôn được đồng bộ <strong>toàn bộ quyền</strong> khi lưu —
                ma trận bên dưới chỉ để xem.
            </div>
        @endif
    </div>

    <div class="bg-white rounded-xl shadow-sm border p-5">
        <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <i class="bi bi-pencil-square text-blue-600"></i>
            Ma trận phân quyền
        </h3>

        @include('user::roles._permission-matrix', [
            'subsystems' => $subsystems,
            'actions' => $actions,
            'actionLabels' => $actionLabels,
            'extraPermissions' => $extraPermissions,
            'readonly' => !empty($isSuperAdmin),
        ])

        {{-- super-admin: không gửi permissions[] (controller tự sync full) --}}

        @error('abilities')
            <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
        @enderror
    </div>

    <div class="flex flex-wrap justify-end gap-2">
        <a href="{{ route('roles.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-200">
            Hủy
        </a>
        <button type="submit"
                class="inline-flex items-center gap-2 px-5 py-2.5 rounded-lg text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white shadow-sm">
            <i class="bi bi-check-lg"></i>
            Lưu phân quyền
        </button>
    </div>
</form>
@endsection
