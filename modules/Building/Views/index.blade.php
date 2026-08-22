@extends('layouts.admin')

@section('title', 'Quản lý Giảng đường')
@section('page-title', 'Quản lý Giảng đường')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Giảng đường']
]" />

{{-- Page Header --}}
<x-page-header
    title="DANH SÁCH GIẢNG ĐƯỜNG"
    :actions="array_filter([
        auth()->user()->can('buildings.create') ? [
            'url' => route('buildings.create'),
            'label' => 'Tạo mới',
            'icon' => 'plus',
            'color' => 'blue'
        ] : null,
    ])" />

{{-- Filters --}}
<x-filter-form
    :action="route('buildings.index')"
    :clear-url="route('buildings.index')"
    :filters="[
        [
            'type' => 'search',
            'name' => 'search',
            'placeholder' => 'Tìm kiếm theo tên hoặc mã Giảng đường...'
        ],
        [
            'type' => 'select',
            'name' => 'status',
            'placeholder' => 'Tất cả trạng thái',
            'options' => [
                '1' => 'Hoạt động',
                '0' => 'Ngừng hoạt động'
            ]
        ]
    ]" />

{{-- Data Table --}}
<div class="bg-white rounded-lg shadow">
    <div class="p-6">
        @if($buildings->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-slate-100 text-slate-800 border-b border-slate-200">
                        <tr class="">
                            {{-- ID --}}
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'id', 'sort_order' => request('sort_order') === 'asc' ? 'desc' : 'asc']) }}" 
                                   class="flex items-center space-x-1 hover:text-gray-700">
                                    <span>ID</span>
                                    @if(request('sort_by') === 'id')
                                        <i class="bi bi-arrow-{{ request('sort_order') === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            {{-- Code --}}
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                Mã
                            </th>
                            {{-- Name --}}
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_order' => request('sort_order') === 'asc' ? 'desc' : 'asc']) }}" 
                                   class="flex items-center space-x-1 hover:text-gray-700">
                                    <span>Tên Giảng đường</span>
                                    @if(request('sort_by') === 'name')
                                        <i class="bi bi-arrow-{{ request('sort_order') === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            {{-- Status --}}
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                Trạng thái
                            </th>
                            {{-- Creator --}}
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                Người tạo
                            </th>
                            {{-- Created_at --}}
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'created_at', 'sort_order' => request('sort_order') === 'asc' ? 'desc' : 'asc']) }}" 
                                   class="flex items-center space-x-1 hover:text-gray-700">
                                    <span>Ngày tạo</span>
                                    @if(request('sort_by') === 'created_at')
                                        <i class="bi bi-arrow-{{ request('sort_order') === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            {{-- Actions --}}
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">
                                Thao tác
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($buildings as $building)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $building->id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $building->code }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $building->name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($building->status)
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                            Hoạt động
                                        </span>
                                    @else
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                            Ngừng hoạt động
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $building->creator->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $building->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    @if(auth()->check() && auth()->user()->can('buildings.show'))
                                        <a href="{{ route('buildings.show', $building) }}" 
                                       class="text-blue-600 hover:text-blue-900" title="Xem chi tiết">
                                        <i class="bi bi-eye"></i>
                                        </a>
                                    @endif
                                    
                                    @if(auth()->check() && auth()->user()->can('buildings.edit'))
                                        <a href="{{ route('buildings.edit', $building) }}" 
                                        class="text-indigo-600 hover:text-indigo-900" title="Chỉnh sửa">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endif
                                    @if(auth()->check() && auth()->user()->can('buildings.delete'))
                                        <form action="{{ route('buildings.destroy', $building) }}" 
                                            method="POST" 
                                            class="inline-block"
                                            data-confirm='Bạn có chắc chắn muốn xóa Giảng đường này?'>
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" 
                                                    class="text-red-600 hover:text-red-900" 
                                                    title="Xóa">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            <div class="mt-6">
                {{ $buildings->links() }}
            </div>
        @else
            <x-empty-state
                icon="bi-building"
                title="Chưa có Giảng đường nào"
                description="Hãy tạo Giảng đường đầu tiên để bắt đầu quản lý."
                :action="[
                    'url' => route('buildings.create'),
                    'label' => 'Tạo Giảng đường mới'
                ]" />
        @endif
    </div>
</div>
@endsection
