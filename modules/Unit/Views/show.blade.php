@extends('layouts.admin')

@section('title', 'Chi tiết đơn vị')
@section('page-title', 'Chi tiết đơn vị')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Đơn vị', 'url' => route('units.index')],
    ['title' => 'Chi tiết']
]" />

{{-- Page Header --}}
<x-page-header
    title="CHI TIẾT ĐƠN VỊ"
    :actions="[
        [
            'url' => route('units.edit', $unit),
            'label' => 'Chỉnh sửa',
            'icon' => 'pencil',
            'color' => 'blue'
        ],
        [
            'url' => route('units.index'),
            'label' => 'Quay lại danh sách',
            'icon' => 'arrow-left',
            'color' => 'gray'
        ]
    ]" />

<div class="grid gap-6 mb-6">
    {{-- Thông tin cơ bản --}}
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">Thông tin cơ bản</h3>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-600">Mã đơn vị</label>
                    <div class="mt-1">
                        <code class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm font-mono">{{ $unit->code }}</code>
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Tên đơn vị</label>
                    <div class="mt-1 text-gray-900">{{ $unit->name }}</div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Đơn vị cấp trên</label>
                    <div class="mt-1 text-gray-900">{{ $unit->parent->name ?? 'N/A' }}</div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Chức năng đơn vị</label>
                    <div class="mt-1 text-gray-900">
                        {{ $unit->functional_type_label }}@if($unit->faculty_code) · Mã phạm vi {{ $unit->faculty_code }}@endif
                    </div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Trạng thái</label>
                    <div class="mt-1">
                        @if($unit->status === 'active')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                Hoạt động
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                Tạm ngừng
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Đơn vị con --}}
    @if($unit->children->count() > 0)
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">Đơn vị trực thuộc</h3>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($unit->children as $child)
                    <div class="flex items-center justify-between p-3 border rounded-lg">
                        <div>
                            <div class="font-medium">{{ $child->name }}</div>
                            <div class="text-sm text-gray-600">{{ $child->code }}</div>
                        </div>
                        <a href="{{ route('units.show', $child) }}" class="text-blue-600 hover:text-blue-800">
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

    {{-- Thông tin cập nhật --}}
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">Thông tin cập nhật</h3>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-600">Người tạo</label>
                    <div class="mt-1 text-gray-900">{{ $unit->creator->name ?? 'N/A' }}</div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Người cập nhật</label>
                    <div class="mt-1 text-gray-900">{{ $unit->updater->name ?? 'N/A' }}</div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Ngày tạo</label>
                    <div class="mt-1 text-gray-900">{{ $unit->created_at->format('d/m/Y H:i') }}</div>
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-600">Cập nhật lần cuối</label>
                    <div class="mt-1 text-gray-900">{{ $unit->updated_at->format('d/m/Y H:i') }}</div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($unit->trashed())
    <div class="bg-red-50 border border-red-200 rounded-lg p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i class="bi bi-exclamation-triangle text-red-400"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800">
                    Đơn vị đã bị xóa
                </h3>
                <div class="mt-2">
                    <form action="{{ route('units.restore', $unit->id) }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="btn btn-danger">
                            Khôi phục
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endif

@endsection
