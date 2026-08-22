@extends('layouts.admin')

@section('title', 'Chi tiết đối tượng')
@section('page-title', 'Chi tiết đối tượng')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Đối tượng', 'url' => route('standard-hours.object-types.index')],
    ['title' => $objectType->name]
]" />

<x-page-header
    title="CHI TIẾT ĐỐI TƯỢNG"
    :actions="[
        [
            'url' => route('standard-hours.object-types.edit', $objectType),
            'label' => 'Chỉnh sửa',
            'icon' => 'pencil',
            'color' => 'blue'
        ],
        [
            'url' => route('standard-hours.object-types.index'),
            'label' => 'Quay lại danh sách',
            'icon' => 'arrow-left',
            'color' => 'gray'
        ]
    ]" />

<div class="grid gap-6 mb-6">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">Thông tin đối tượng</h3>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-600">Mã</label>
                    <div class="mt-1 text-gray-900 font-mono font-bold">{{ $objectType->code ?: '—' }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Tên đối tượng</label>
                    <div class="mt-1 text-gray-900 font-medium">{{ $objectType->name }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Định mức giờ chuẩn (GC)</label>
                    <div class="mt-1 text-gray-900 font-semibold tabular-nums">{{ number_format((float)$objectType->standard_hours, 0) }}</div>
                    <p class="text-xs text-slate-500">× tỉ lệ chức danh = giờ phải đạt</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Định mức NCKH (giờ HC)</label>
                    <div class="mt-1 text-gray-900 font-semibold tabular-nums">{{ number_format((float)$objectType->research_hours, 0) }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Giờ hành chính</label>
                    <div class="mt-1 text-indigo-700 font-semibold tabular-nums">{{ number_format((float)$objectType->administrative_hours, 0) }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Trạng thái</label>
                    <div class="mt-1">
                        @if($objectType->is_active)
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

                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-600">Mô tả</label>
                    <div class="mt-1 text-gray-900">{{ $objectType->description ?: '—' }}</div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Người tạo</label>
                    <div class="mt-1 text-gray-900">{{ $objectType->creator->name ?? 'N/A' }}</div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Ngày tạo</label>
                    <div class="mt-1 text-gray-900">{{ $objectType->created_at->format('d/m/Y H:i') }}</div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Cập nhật bởi</label>
                    <div class="mt-1 text-gray-900">{{ $objectType->updater->name ?? 'N/A' }}</div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Cập nhật lần cuối</label>
                    <div class="mt-1 text-gray-900">{{ $objectType->updated_at->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </div>
    </div>

    @can('standard-hours.object-types.manage')
    <div class="bg-white rounded-lg shadow-sm p-4">
        <form action="{{ route('standard-hours.object-types.toggle-status', $objectType) }}" method="POST"
              data-confirm="Bạn có chắc muốn thay đổi trạng thái đối tượng này?">
            @csrf
            @method('PATCH')
            <button type="submit" class="btn {{ $objectType->is_active ? 'btn-warning' : 'btn-success' }}">
                {{ $objectType->is_active ? 'Ngừng sử dụng' : 'Kích hoạt lại' }}
            </button>
        </form>
    </div>
    @endcan
</div>
@endsection
