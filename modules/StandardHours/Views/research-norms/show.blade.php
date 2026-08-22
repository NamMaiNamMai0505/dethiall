@extends('layouts.admin')

@section('title', 'Chi tiết định mức NCKH')
@section('page-title', 'Chi tiết định mức NCKH')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Định mức NCKH', 'url' => route('standard-hours.research-norms.index')],
    ['title' => 'Chi tiết']
]" />

<x-page-header
    title="CHI TIẾT ĐỊNH MỨC NCKH"
    :actions="[
        ['url' => route('standard-hours.research-norms.edit', $researchNorm), 'label' => 'Chỉnh sửa', 'icon' => 'pencil', 'color' => 'blue'],
        ['url' => route('standard-hours.research-norms.index'), 'label' => 'Quay lại', 'icon' => 'arrow-left', 'color' => 'gray']
    ]" />

<div class="grid gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">Thông tin định mức NCKH</h3>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-600">Đối tượng</label>
                    <div class="mt-1 font-medium">{{ $researchNorm->objectType->name }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Năm</label>
                    <div class="mt-1">{{ $researchNorm->year }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Số giờ NCKH</label>
                    <div class="mt-1 text-lg font-semibold text-blue-700">{{ number_format($researchNorm->research_hours, 0) }} giờ</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Trạng thái</label>
                    <div class="mt-1">{{ $researchNorm->status_text }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Người tạo</label>
                    <div class="mt-1">{{ $researchNorm->creator->name ?? 'N/A' }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Ngày tạo</label>
                    <div class="mt-1">{{ $researchNorm->created_at->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </div>
    </div>

    @can('standard-hours.object-types.manage')
    <div class="bg-white rounded-lg shadow-sm p-4">
        <form action="{{ route('standard-hours.research-norms.toggle-status', $researchNorm) }}" method="POST"
              data-confirm="Bạn có chắc muốn thay đổi trạng thái?">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn {{ $researchNorm->is_active ? 'btn-warning' : 'btn-success' }}">
                {{ $researchNorm->is_active ? 'Ngừng sử dụng' : 'Kích hoạt lại' }}
            </button>
        </form>
    </div>
    @endcan
</div>
@endsection
