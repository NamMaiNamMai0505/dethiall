@extends('layouts.admin')

@section('title', 'Chi tiết chức danh')
@section('page-title', 'Chi tiết chức danh')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Chức danh', 'url' => route('standard-hours.positions.index')],
    ['title' => $position->name]
]" />

<x-page-header
    title="CHI TIẾT CHỨC DANH"
    :actions="[
        [
            'url' => route('standard-hours.positions.edit', $position),
            'label' => 'Chỉnh sửa',
            'icon' => 'pencil',
            'color' => 'blue'
        ],
        [
            'url' => route('standard-hours.positions.index'),
            'label' => 'Quay lại danh sách',
            'icon' => 'arrow-left',
            'color' => 'gray'
        ]
    ]" />

<div class="grid gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">Thông tin chức danh</h3>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-600">Tên chức danh</label>
                    <div class="mt-1 text-gray-900 font-medium">{{ $position->name }}</div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Trạng thái</label>
                    <div class="mt-1">
                        @if($position->is_active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Đang sử dụng
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Ngừng sử dụng
                            </span>
                        @endif
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Tỷ lệ chức danh</label>
                    <div class="mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                            {{ $position->formatted_ratio_percent }}
                        </span>
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Tỷ lệ tối thiểu đứng lớp</label>
                    <div class="mt-1 text-gray-900">{{ $position->formatted_min_classroom_percent }}</div>
                </div>

                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-600">Mô tả</label>
                    <div class="mt-1 text-gray-900">{{ $position->description ?: '—' }}</div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Người tạo</label>
                    <div class="mt-1 text-gray-900">{{ $position->creator->name ?? 'N/A' }}</div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Ngày tạo</label>
                    <div class="mt-1 text-gray-900">{{ $position->created_at->format('d/m/Y H:i') }}</div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Cập nhật bởi</label>
                    <div class="mt-1 text-gray-900">{{ $position->updater->name ?? 'N/A' }}</div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Cập nhật lần cuối</label>
                    <div class="mt-1 text-gray-900">{{ $position->updated_at->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </div>
    </div>

    @can('standard-hours.positions.manage')
    <div class="bg-white rounded-lg shadow-sm p-4">
        <form action="{{ route('standard-hours.positions.toggle-status', $position) }}" method="POST"
              data-confirm="Bạn có chắc muốn thay đổi trạng thái chức danh này?">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn {{ $position->is_active ? 'btn-warning' : 'btn-success' }}">
                {{ $position->is_active ? 'Ngừng sử dụng' : 'Kích hoạt lại' }}
            </button>
        </form>
    </div>
    @endcan
</div>
@endsection
