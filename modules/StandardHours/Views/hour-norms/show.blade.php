@extends('layouts.admin')

@section('title', 'Chi tiết định mức giờ chuẩn')
@section('page-title', 'Chi tiết định mức giờ chuẩn')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Định mức giờ chuẩn', 'url' => route('standard-hours.hour-norms.index')],
    ['title' => 'Chi tiết']
]" />

<x-page-header
    title="CHI TIẾT ĐỊNH MỨC GIỜ CHUẨN"
    :actions="[
        ['url' => route('standard-hours.hour-norms.edit', $hourNorm), 'label' => 'Chỉnh sửa', 'icon' => 'pencil', 'color' => 'blue'],
        ['url' => route('standard-hours.hour-norms.index'), 'label' => 'Quay lại', 'icon' => 'arrow-left', 'color' => 'gray']
    ]" />

<div class="grid gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">Thông tin định mức</h3>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-600">Đối tượng</label>
                    <div class="mt-1 font-medium">{{ $hourNorm->objectType->name }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Chức danh</label>
                    <div class="mt-1">{{ $hourNorm->position->name }} ({{ $hourNorm->position->formatted_ratio_percent }})</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Năm</label>
                    <div class="mt-1">{{ $hourNorm->year }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Trạng thái</label>
                    <div class="mt-1">{{ $hourNorm->status_text }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Số giờ chuẩn (cơ sở)</label>
                    <div class="mt-1 text-lg font-semibold">{{ number_format($hourNorm->standard_hours, 0) }} giờ</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Giờ hiệu lực</label>
                    <div class="mt-1 text-lg font-semibold text-blue-700">{{ number_format($hourNorm->effective_hours, 0) }} giờ</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Giờ tối thiểu đứng lớp</label>
                    <div class="mt-1 text-lg font-semibold">{{ number_format($hourNorm->min_classroom_hours, 0) }} giờ</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Công thức</label>
                    <div class="mt-1 text-sm text-gray-600">
                        Giờ hiệu lực = {{ number_format($hourNorm->standard_hours, 0) }} × {{ number_format($hourNorm->position->ratio_percent, 0) }}%<br>
                        Tối thiểu đứng lớp = {{ number_format($hourNorm->effective_hours, 0) }} × {{ number_format($hourNorm->position->min_classroom_percent, 0) }}%
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('standard-hours.object-types.manage')
    <div class="bg-white rounded-lg shadow-sm p-4">
        <form action="{{ route('standard-hours.hour-norms.toggle-status', $hourNorm) }}" method="POST"
              data-confirm="Bạn có chắc muốn thay đổi trạng thái?">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn {{ $hourNorm->is_active ? 'btn-warning' : 'btn-success' }}">
                {{ $hourNorm->is_active ? 'Ngừng sử dụng' : 'Kích hoạt lại' }}
            </button>
        </form>
    </div>
    @endcan
</div>
@endsection
