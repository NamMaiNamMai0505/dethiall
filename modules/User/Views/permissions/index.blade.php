@extends('layouts.admin')

@section('title', 'Quản lý Quyền')
@section('page-title', 'Quản lý Quyền')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Quyền']
]" />

{{-- Page Header --}}
<x-page-header
    title="DANH SÁCH QUYỀN"
    :actions="[
        [
            'url' => route('permissions.create'),
            'label' => 'Tạo mới quyền',
            'icon' => 'plus',
            'color' => 'blue'
        ]
    ]" />

<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    @if($permissions->count())
    <table class="w-full">
        <thead class="bg-gray-100 text-gray-700">
            <tr>
                <th class="px-4 py-3 text-left font-medium">Tên quyền</th>
                <th class="px-4 py-3 text-left font-medium">Guard</th>
                <th class="px-4 py-3 text-left font-medium">Số vai trò</th>
                <th class="px-4 py-3 text-left font-medium">Thao tác</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            @foreach($permissions as $permission)
            <tr class="hover:bg-gray-50">
                <td class="px-4 py-3">{{ $permission->name }}</td>
                <td class="px-4 py-3">{{ $permission->guard_name }}</td>
                <td class="px-4 py-3">{{ $permission->roles_count }}</td>
                <td class="px-4 py-3">
                    <x-table.action-buttons
                        :item="$permission"
                        :routes="['edit' => 'permissions.edit','destroy' => 'permissions.destroy']" />
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <div class="px-4 py-3 border-t border-gray-200 bg-gray-50 flex justify-center">
        {{ $permissions->links() }}
    </div>
    @else
    <div class="p-6 text-center text-gray-600">
        Chưa có quyền nào.
    </div>
    @endif
</div>
@endsection

