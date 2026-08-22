@extends('layouts.admin')

@section('title', 'Quản lý Phòng học')
@section('page-title', 'Quản lý Phòng học')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Phòng học']
]" />

{{-- Page Header --}}
<x-page-header
    title="DANH SÁCH PHÒNG HỌC"
    :actions="array_filter([
        auth()->user()->can('classrooms.create') ? [
            'url' => route('classrooms.create'),
            'label' => 'Tạo mới',
            'icon' => 'plus',
            'color' => 'blue'
        ] : null,
    ])" />

{{-- Filters --}}
<div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
    <form method="GET" action="{{ route('classrooms.index') }}" id="filterForm">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            {{-- Search Filter --}}
            <div>
                <div class="relative">
                    <input type="text"
                           name="search"
                           data-live-search="1"
                           value="{{ request('search') }}"
                           placeholder="Tìm kiếm theo tên Phòng học..."
                           class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <i class="bi bi-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                </div>
            </div>

            {{-- Status Filter --}}
            <div>
                <select name="status"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Tất cả trạng thái</option>
                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Hoạt động</option>
                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Ngừng hoạt động</option>
                </select>
            </div>

            {{-- Building Filter with onchange --}}
            <div>
                <select name="building_id"
                        onchange="document.getElementById('filterForm').submit()"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Tất cả giảng đường</option>
                    @foreach($buildings as $building)
                        <option value="{{ $building->id }}" {{ (string)request('building_id') === (string)$building->id ? 'selected' : '' }}>
                            {{ $building->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>

        {{-- Filter Buttons --}}
        <div class="flex justify-end mt-4 gap-2">
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                <i class="bi bi-funnel mr-2"></i>Lọc
            </button>
            <a href="{{ route('classrooms.index') }}"
               class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium">
                <i class="bi bi-x-circle"></i>
            </a>
        </div>

        {{-- Results Summary and Per Page Selector --}}
        @if($classrooms->total() > 0)
            <div class="mt-4 flex justify-between items-center">
                <div class="text-sm text-gray-600">
                    Hiển thị {{ $classrooms->firstItem() }} - {{ $classrooms->lastItem() }}
                    trong tổng số {{ $classrooms->total() }} kết quả
                </div>

                <div class="flex items-center gap-2">
                    <label for="per_page" class="text-sm text-gray-600">Hiển thị:</label>
                    <select name="per_page" id="per_page"
                        class="text-sm border border-gray-300 rounded-md px-2 py-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        onchange="this.form.submit()">
                        @foreach([5, 10, 15, 25, 50] as $option)
                            <option value="{{ $option }}" {{ request('per_page', 10) == $option ? 'selected' : '' }}>
                                {{ $option }}
                            </option>
                        @endforeach
                    </select>
                    <span class="text-sm text-gray-600">/ trang</span>
                </div>
            </div>
        @endif
    </form>
</div>

{{-- Data Table --}}
<div class="bg-white rounded-lg shadow">
    <div class="p-6">
        @if($classrooms->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-slate-100 text-slate-800 border-b border-slate-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'id', 'sort_order' => request('sort_order') === 'asc' ? 'desc' : 'asc']) }}" 
                                   class="flex items-center space-x-1 hover:text-gray-700">
                                    <span>ID</span>
                                    @if(request('sort_by') === 'id')
                                        <i class="bi bi-arrow-{{ request('sort_order') === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_order' => request('sort_order') === 'asc' ? 'desc' : 'asc']) }}" 
                                   class="flex items-center space-x-1 hover:text-gray-700">
                                    <span>Giảng đường</span>
                                    @if(request('sort_by') === 'name')
                                        <i class="bi bi-arrow-{{ request('sort_order') === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_order' => request('sort_order') === 'asc' ? 'desc' : 'asc']) }}" 
                                   class="flex items-center space-x-1 hover:text-gray-700">
                                    <span>Tên Phòng học</span>
                                    @if(request('sort_by') === 'name')
                                        <i class="bi bi-arrow-{{ request('sort_order') === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                Trạng thái
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                Người tạo
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'created_at', 'sort_order' => request('sort_order') === 'asc' ? 'desc' : 'asc']) }}" 
                                   class="flex items-center space-x-1 hover:text-gray-700">
                                    <span>Ngày tạo</span>
                                    @if(request('sort_by') === 'created_at')
                                        <i class="bi bi-arrow-{{ request('sort_order') === 'asc' ? 'up' : 'down' }}"></i>
                                    @endif
                                </a>
                            </th>
                            <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">
                                Thao tác
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($classrooms as $classroom)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $classroom->id }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    {{ $classroom->building->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $classroom->name }}
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($classroom->status)
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
                                    {{ $classroom->creator->name ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    {{ $classroom->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-2">
                                    @if(auth()->check() && auth()->user()->can('classrooms.show'))
                                        <a href="{{ route('classrooms.show', $classroom) }}" 
                                        class="text-blue-600 hover:text-blue-900" title="Xem chi tiết">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    @endif
                                    @if(auth()->check() && auth()->user()->can('classrooms.edit'))
                                        <a href="{{ route('classrooms.edit', $classroom) }}" 
                                        class="text-indigo-600 hover:text-indigo-900" title="Chỉnh sửa">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endif
                                    @if(auth()->check() && auth()->user()->can('classrooms.delete'))
                                        <form action="{{ route('classrooms.destroy', $classroom) }}" 
                                            method="POST" 
                                            class="inline-block"
                                            data-confirm='Bạn có chắc chắn muốn xóa Phòng học này?'>
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
                {{ $classrooms->links() }}
            </div>
        @else
            <x-empty-state
                icon="bi-building"
                title="Chưa có Phòng học nào"
                description="Hãy tạo Phòng học đầu tiên để bắt đầu quản lý."
                :action="[
                    'url' => route('classrooms.create'),
                    'label' => 'Tạo Phòng học mới'
                ]" />
        @endif
    </div>
</div>
@include('partials.live-search')
@endsection
