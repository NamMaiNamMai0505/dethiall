@extends('layouts.admin')

@section('title', 'Danh mục NCKH')
@section('page-title', 'Danh mục NCKH')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Giờ chuẩn GV', 'url' => route('standard-hours.hub')],
    ['title' => 'Danh mục NCKH']
]" />

<x-page-header title="DANH MỤC NGHIÊN CỨU KHOA HỌC" :actions="[
    \Modules\StandardHours\Support\HubNavigation::backAction(),
    ['url' => route('standard-hours.research-categories.create'), 'label' => 'Thêm mới', 'icon' => 'plus', 'color' => 'blue'],
]" />

<x-filter-form
    :action="route('standard-hours.research-categories.index')"
    :clear-url="route('standard-hours.research-categories.index')"
    :filters="[
        ['type' => 'search', 'name' => 'search', 'placeholder' => 'Tìm theo mã, tên...'],
        ['type' => 'select', 'name' => 'status', 'placeholder' => 'Tất cả trạng thái', 'options' => ['active' => 'Đang sử dụng', 'inactive' => 'Ngừng sử dụng']]
    ]">

@if($researchCategories->total() > 0)
    <div class="mb-4 flex justify-between text-sm text-gray-600">
        <span>{{ $researchCategories->firstItem() }}-{{ $researchCategories->lastItem() }} / {{ $researchCategories->total() }}</span>
        <select name="per_page" class="border rounded px-2 py-1 text-sm" onchange="this.form.submit()">
            @foreach([5,10,15,25,50] as $n)<option value="{{ $n }}" {{ request('per_page',10)==$n?'selected':'' }}>{{ $n }}</option>@endforeach
        </select>
    </div>
@endif
</x-filter-form>

<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    @if($researchCategories->count() > 0)
        <table class="w-full">
            <thead class="bg-slate-100 text-slate-800 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-left w-12">STT</th>
                    <x-table.sort-header column="code" label="Mã" />
                    <th class="px-4 py-3 text-left">Tên danh mục</th>
                    <th class="px-4 py-3 text-left">Đơn vị</th>
                    <th class="px-4 py-3 text-left">Giờ quy đổi</th>
                    <th class="px-4 py-3 text-left">Trạng thái</th>
                    <th class="px-4 py-3 text-left">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($researchCategories as $i => $category)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">{{ $researchCategories->firstItem() + $i }}</td>
                    <td class="px-4 py-3"><code class="text-sm bg-gray-100 px-2 py-1 rounded">{{ $category->code }}</code></td>
                    <td class="px-4 py-3 font-medium">{{ $category->name }}</td>
                    <td class="px-4 py-3 text-sm text-gray-600">{{ $category->unit ?: '—' }}</td>
                    <td class="px-4 py-3 text-blue-700 font-medium">{{ number_format($category->research_hours, 0) }} giờ</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs {{ $category->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $category->status_text }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <x-table.action-buttons :item="$category" :routes="[
                            'show' => 'standard-hours.research-categories.show',
                            'edit' => 'standard-hours.research-categories.edit',
                            'destroy' => 'standard-hours.research-categories.destroy'
                        ]" />
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($researchCategories->hasPages())
        <div class="px-4 py-3 border-t flex justify-center">{{ $researchCategories->appends(request()->query())->links() }}</div>
        @endif
    @else
        <div class="text-center py-12">
            <i class="bi bi-journal-richtext text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 mb-4">Chưa có danh mục NCKH.</p>
            <a href="{{ route('standard-hours.research-categories.create') }}" class="btn btn-primary">Thêm danh mục</a>
        </div>
    @endif
</div>
@endsection
