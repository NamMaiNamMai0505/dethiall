@extends('layouts.admin')

@section('title', 'Chi tiết Giảng đường')
@section('page-title', 'Chi tiết Giảng đường')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Giảng đường', 'url' => route('buildings.index')],
    ['title' => 'Chi tiết']
]" />

{{-- Page Header --}}
<x-page-header
    title="CHI TIẾT GIẢNG ĐƯỜNG"
    :actions="[
        [
            'url' => route('buildings.index'),
            'label' => 'Quay lại',
            'icon' => 'arrow-left',
            'color' => 'gray'
        ],
        [
            'url' => route('buildings.edit', $building),
            'label' => 'Chỉnh sửa',
            'icon' => 'pencil',
            'color' => 'blue'
        ]
    ]" />

{{-- Content --}}
<div class="bg-white rounded-lg shadow">
    <div class="p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Basic Information --}}
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Thông tin cơ bản</h3>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">ID</label>
                    <p class="text-sm text-gray-900">{{ $building->id }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Mã Giảng đường</label>
                    <p class="text-sm text-gray-900 font-semibold">{{ $building->code }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Tên Giảng đường</label>
                    <p class="text-sm text-gray-900 font-semibold">{{ $building->name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                    @if($building->status)
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                            Hoạt động
                        </span>
                    @else
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                            Ngừng hoạt động
                        </span>
                    @endif
                </div>
            </div>

            {{-- System Information --}}
            <div class="space-y-4">
                <h3 class="text-lg font-semibold text-gray-900 border-b pb-2">Thông tin hệ thống</h3>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Người tạo</label>
                    <p class="text-sm text-gray-900">{{ $building->creator->name ?? 'N/A' }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ngày tạo</label>
                    <p class="text-sm text-gray-900">{{ $building->created_at->format('d/m/Y H:i:s') }}</p>
                </div>

                @if($building->updated_by)
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Người cập nhật</label>
                    <p class="text-sm text-gray-900">{{ $building->updater->name ?? 'N/A' }}</p>
                </div>
                @endif

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Ngày cập nhật</label>
                    <p class="text-sm text-gray-900">{{ $building->updated_at->format('d/m/Y H:i:s') }}</p>
                </div>
            </div>
        </div>

        {{-- Actions --}}
        <div class="flex justify-end space-x-3 pt-6 border-t mt-6">
            <a href="{{ route('buildings.edit', $building) }}" 
               class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                <i class="bi bi-pencil mr-1"></i>
                Chỉnh sửa
            </a>
            <form action="{{ route('buildings.destroy', $building) }}" 
                  method="POST" 
                  class="inline-block"
                  data-confirm='Bạn có chắc chắn muốn xóa Giảng đường này?'>
                @csrf
                @method('DELETE')
                <button type="submit" 
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                    <i class="bi bi-trash mr-1"></i>
                    Xóa
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
