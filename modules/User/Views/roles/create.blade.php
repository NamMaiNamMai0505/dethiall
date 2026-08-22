@extends('layouts.admin')

@section('title', 'Tạo vai trò')
@section('page-title', 'Tạo vai trò')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Quản lý tài khoản', 'url' => route('accounts.hub')],
    ['title' => 'Vai trò', 'url' => route('roles.index')],
    ['title' => 'Tạo mới']
]" />

<x-page-header
    title="TẠO VAI TRÒ MỚI"
    subtitle="Đặt tên và chọn quyền xem / sử dụng tính năng"
    :actions="[
        [
            'url' => route('roles.index'),
            'label' => 'Quay lại',
            'icon' => 'arrow-left',
            'color' => 'gray'
        ]
    ]" />

<form action="{{ route('roles.store') }}" method="POST" class="space-y-6">
    @csrf

    <div class="bg-white rounded-xl shadow-sm border p-5">
        <label for="name" class="block text-sm font-medium text-gray-700 mb-1.5">
            Tên vai trò <span class="text-red-500">*</span>
        </label>
        <input type="text" name="name" id="name" value="{{ old('name') }}" required
               list="role-catalog-names"
               class="w-full max-w-md px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
               placeholder="vd: faculty-manager, exam-manager">
        <datalist id="role-catalog-names">
            @foreach($roleGroups ?? [] as $group)
                <option value="{{ $group['name'] }}">{{ $group['label'] }}</option>
            @endforeach
        </datalist>
        @error('name')
            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
        @enderror

        @if(!empty($roleGroups))
            <div class="mt-3 rounded-lg border border-slate-200 bg-slate-50 p-3">
                <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Nhóm vai trò chuẩn của nhà trường</p>
                <ul class="mt-2 space-y-1 text-xs text-slate-600">
                    @foreach($roleGroups as $group)
                        <li>
                            <code class="rounded bg-white px-1 py-0.5 font-mono text-slate-700">{{ $group['name'] }}</code>
                            — <strong>{{ $group['label'] }}</strong>: {{ $group['description'] }}
                        </li>
                    @endforeach
                </ul>
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
            'readonly' => false,
        ])

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
            Tạo vai trò
        </button>
    </div>
</form>
@endsection
