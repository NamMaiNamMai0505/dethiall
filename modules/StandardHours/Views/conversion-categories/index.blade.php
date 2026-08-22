@extends('layouts.admin')

@section('title', 'Danh mục HĐ chuyên môn')
@section('page-title', 'Danh mục HĐ chuyên môn')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Giờ chuẩn GV', 'url' => route('standard-hours.hub')],
    ['title' => 'HĐ chuyên môn']
]" />

<x-page-header
    title="DANH MỤC HOẠT ĐỘNG CHUYÊN MÔN"
    :actions="[
        \Modules\StandardHours\Support\HubNavigation::backAction(),
        [
            'url' => route('standard-hours.conversion-categories.create'),
            'label' => 'Thêm mới',
            'icon' => 'plus',
            'color' => 'blue'
        ]
    ]" />

<x-filter-form
    :action="route('standard-hours.conversion-categories.index')"
    :clear-url="route('standard-hours.conversion-categories.index')"
    :filters="[
        ['type' => 'search', 'name' => 'search', 'placeholder' => 'Tìm theo mã, tên...'],
        ['type' => 'select', 'name' => 'conversion_method', 'placeholder' => 'Tất cả phương thức', 'options' => $conversionMethods],
        ['type' => 'select', 'name' => 'status', 'placeholder' => 'Tất cả trạng thái', 'options' => ['active' => 'Đang sử dụng', 'inactive' => 'Ngừng sử dụng']]
    ]">

@if($conversionCategories->total() > 0)
    <div class="mb-4 flex justify-between items-center text-sm text-gray-600">
        <span>Hiển thị {{ $conversionCategories->firstItem() }} - {{ $conversionCategories->lastItem() }} / {{ $conversionCategories->total() }}</span>
        <select name="per_page" class="text-sm border rounded px-2 py-1" onchange="this.form.submit()">
            @foreach([5,10,15,25,50] as $n)
                <option value="{{ $n }}" {{ request('per_page',10)==$n?'selected':'' }}>{{ $n }}</option>
            @endforeach
        </select>
    </div>
@endif
</x-filter-form>

<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    @if($conversionCategories->count() > 0)
        <table class="w-full">
            <thead class="bg-slate-100 text-slate-800 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-left w-12">STT</th>
                    <x-table.sort-header column="code" label="Mã" />
                    <th class="px-4 py-3 text-left">Tên</th>
                    <th class="px-4 py-3 text-left">Đơn vị</th>
                    <th class="px-4 py-3 text-left">Hệ số</th>
                    <th class="px-4 py-3 text-left">Số giờ</th>
                    <th class="px-4 py-3 text-left">Trạng thái</th>
                    <th class="px-4 py-3 text-left">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($conversionCategories as $i => $category)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">{{ $conversionCategories->firstItem() + $i }}</td>
                    <td class="px-4 py-3"><code class="text-sm bg-gray-100 px-2 py-1 rounded">{{ $category->code }}</code></td>
                    <td class="px-4 py-3 font-medium">{{ $category->name }}</td>
                    <td class="px-4 py-3">{{ $category->unit }}</td>
                    <td class="px-4 py-3">{{ $category->usesCoefficient() ? number_format($category->coefficient, 2) : '—' }}</td>
                    <td class="px-4 py-3">{{ $category->usesCoefficient() ? '—' : number_format($category->fixed_hours, 2) }}</td>
                    <td class="px-4 py-3">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs {{ $category->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $category->status_text }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <x-table.action-buttons :item="$category" :routes="[
                            'show' => 'standard-hours.conversion-categories.show',
                            'edit' => 'standard-hours.conversion-categories.edit',
                            'destroy' => 'standard-hours.conversion-categories.destroy'
                        ]" />
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if($conversionCategories->hasPages())
        <div class="px-4 py-3 border-t flex justify-center">{{ $conversionCategories->appends(request()->query())->links() }}</div>
        @endif
    @else
        <div class="text-center py-12">
            <i class="bi bi-list-check text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 mb-4">Chưa có danh mục hoạt động chuyên môn.</p>
            <a href="{{ route('standard-hours.conversion-categories.create') }}" class="btn btn-primary">Thêm danh mục</a>
        </div>
    @endif
</div>
@endsection
