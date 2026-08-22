@extends('layouts.admin')

@section('title', 'Chi tiết HĐ chuyên môn')
@section('page-title', 'Chi tiết HĐ chuyên môn')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'HĐ chuyên môn', 'url' => route('standard-hours.conversion-categories.index')],
    ['title' => $conversionCategory->name]
]" />

<x-page-header title="CHI TIẾT DANH MỤC" :actions="[
    ['url' => route('standard-hours.conversion-categories.edit', $conversionCategory), 'label' => 'Chỉnh sửa', 'icon' => 'pencil', 'color' => 'blue'],
    ['url' => route('standard-hours.conversion-categories.index'), 'label' => 'Quay lại', 'icon' => 'arrow-left', 'color' => 'gray']
]" />

<div class="grid gap-6">
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><span class="text-sm text-gray-600">Mã</span><div class="font-mono font-medium">{{ $conversionCategory->code }}</div></div>
            <div><span class="text-sm text-gray-600">Tên</span><div class="font-medium">{{ $conversionCategory->name }}</div></div>
            <div><span class="text-sm text-gray-600">Đơn vị tính</span><div>{{ $conversionCategory->unit }}</div></div>
            <div><span class="text-sm text-gray-600">Phương thức</span><div>{{ $conversionCategory->conversion_method_text }}</div></div>
            <div>
                <span class="text-sm text-gray-600">Hệ số / Số giờ</span>
                <div class="text-blue-700 font-semibold">{{ $conversionCategory->conversion_value_text }}</div>
            </div>
            <div><span class="text-sm text-gray-600">Trạng thái</span><div>{{ $conversionCategory->status_text }}</div></div>
            @if($conversionCategory->description)
            <div class="md:col-span-2"><span class="text-sm text-gray-600">Mô tả</span><div>{{ $conversionCategory->description }}</div></div>
            @endif
            <div class="md:col-span-2 p-3 bg-gray-50 rounded text-sm text-gray-600">
                <strong>Ví dụ:</strong> 20 {{ $conversionCategory->unit }} ×
                {{ $conversionCategory->usesCoefficient() ? $conversionCategory->coefficient : $conversionCategory->fixed_hours }}
                = {{ number_format($conversionCategory->calculateHours(20), 0) }} giờ
            </div>
        </div>
    </div>

    @can('standard-hours.conversion-categories.manage')
    <div class="bg-white rounded-lg shadow-sm p-4">
        <form action="{{ route('standard-hours.conversion-categories.toggle-status', $conversionCategory) }}" method="POST"
              data-confirm="Thay đổi trạng thái danh mục?">
            @csrf @method('PATCH')
            <button type="submit" class="btn {{ $conversionCategory->is_active ? 'btn-warning' : 'btn-success' }}">
                {{ $conversionCategory->is_active ? 'Ngừng sử dụng' : 'Kích hoạt lại' }}
            </button>
        </form>
    </div>
    @endcan
</div>
@endsection
