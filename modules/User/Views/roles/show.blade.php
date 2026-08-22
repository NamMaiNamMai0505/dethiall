@extends('layouts.admin')

@section('title', 'Chi tiết vai trò')
@section('page-title', 'Chi tiết vai trò')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Quản lý tài khoản', 'url' => route('accounts.hub')],
    ['title' => 'Vai trò', 'url' => route('roles.index')],
    ['title' => \App\Support\RoleDisplay::label($role->name)]
]" />

<x-page-header
    title="VAI TRÒ: {{ mb_strtoupper(\App\Support\RoleDisplay::label($role->name)) }}"
    subtitle="Quyền xem / sử dụng tính năng hiện tại"
    :actions="[
        [
            'url' => route('roles.edit', $role),
            'label' => 'Chỉnh phân quyền',
            'icon' => 'sliders',
            'color' => 'blue'
        ],
        [
            'url' => route('roles.index'),
            'label' => 'Danh sách',
            'icon' => 'arrow-left',
            'color' => 'gray'
        ],
    ]" />

<div class="grid gap-6">
    <div class="bg-white rounded-xl shadow-sm border p-5">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <p class="text-sm text-gray-500">Tên vai trò</p>
                <p class="text-lg font-semibold text-gray-900 mt-0.5">{{ \App\Support\RoleDisplay::label($role->name) }}</p>
                <p class="text-xs text-gray-500">{{ $role->name }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Số quyền</p>
                <p class="text-lg font-semibold text-gray-900 mt-0.5">{{ $role->permissions->count() }}</p>
            </div>
            <div>
                <p class="text-sm text-gray-500">Guard</p>
                <p class="text-lg font-semibold text-gray-900 mt-0.5">{{ $role->guard_name }}</p>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border p-5">
        <h3 class="text-base font-semibold text-gray-900 mb-4 flex items-center gap-2">
            <i class="bi bi-pencil-square text-blue-600"></i>
            Ma trận quyền (chỉ xem)
        </h3>

        @include('user::roles._permission-matrix', [
            'subsystems' => $subsystems,
            'actions' => $actions,
            'actionLabels' => $actionLabels,
            'extraPermissions' => $extraPermissions,
            'readonly' => true,
        ])
    </div>
</div>
@endsection
