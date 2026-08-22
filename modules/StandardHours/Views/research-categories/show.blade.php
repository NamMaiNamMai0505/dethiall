@extends('layouts.admin')

@section('title', 'Chi tiết danh mục NCKH')
@section('page-title', 'Chi tiết danh mục NCKH')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Danh mục NCKH', 'url' => route('standard-hours.research-categories.index')],
    ['title' => $researchCategory->name]
]" />

<x-page-header title="CHI TIẾT DANH MỤC NCKH" :actions="[
    ['url' => route('standard-hours.research-categories.edit', $researchCategory), 'label' => 'Chỉnh sửa', 'icon' => 'pencil', 'color' => 'blue'],
    ['url' => route('standard-hours.research-categories.index'), 'label' => 'Quay lại', 'icon' => 'arrow-left', 'color' => 'gray']
]" />

<div class="grid gap-6">
    <div class="bg-white rounded-lg shadow-sm p-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div><span class="text-sm text-gray-600">Mã</span><div class="font-mono font-medium">{{ $researchCategory->code }}</div></div>
            <div><span class="text-sm text-gray-600">Tên</span><div class="font-medium">{{ $researchCategory->name }}</div></div>
            <div><span class="text-sm text-gray-600">Đơn vị tính</span><div class="font-medium">{{ $researchCategory->unit ?: '—' }}</div></div>
            <div><span class="text-sm text-gray-600">Giờ quy đổi</span><div class="text-lg font-semibold text-blue-700">{{ $researchCategory->formatted_research_hours }}</div></div>
            <div><span class="text-sm text-gray-600">Trạng thái</span><div>{{ $researchCategory->status_text }}</div></div>
            @if($researchCategory->description)
            <div class="md:col-span-2"><span class="text-sm text-gray-600">Mô tả</span><div>{{ $researchCategory->description }}</div></div>
            @endif
            <div class="md:col-span-2 p-3 bg-gray-50 rounded text-sm text-gray-600">
                Giờ quy đổi lấy từ danh mục. Khi có nhiều thành viên, hệ thống sẽ chia theo quy tắc trong <em>BUSINESS_RULES.md</em> khi tính toán.
            </div>
        </div>
    </div>

    @can('standard-hours.research-categories.manage')
    <div class="bg-white rounded-lg shadow-sm p-4">
        <form action="{{ route('standard-hours.research-categories.toggle-status', $researchCategory) }}" method="POST"
              data-confirm="Thay đổi trạng thái danh mục?">
            @csrf @method('PATCH')
            <button type="submit" class="btn {{ $researchCategory->is_active ? 'btn-warning' : 'btn-success' }}">
                {{ $researchCategory->is_active ? 'Ngừng sử dụng' : 'Kích hoạt lại' }}
            </button>
        </form>
    </div>
    @endcan
</div>
@endsection
