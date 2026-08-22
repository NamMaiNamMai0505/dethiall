@extends('layouts.admin')

@section('title', 'Tạo Giảng đường mới')
@section('page-title', 'Tạo Giảng đường mới')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Giảng đường', 'url' => route('buildings.index')],
    ['title' => 'Tạo mới']
]" />

{{-- Page Header --}}
<x-page-header
    title="TẠO GIẢNG ĐƯỜNG MỚI"
    :actions="[
        [
            'url' => route('buildings.index'),
            'label' => 'Quay lại',
            'icon' => 'arrow-left',
            'color' => 'gray'
        ]
    ]" />

{{-- Form --}}
<div class="bg-white rounded-lg shadow">
    <div class="p-6">
        <form action="{{ route('buildings.store') }}" method="POST" class="space-y-6">
            @csrf

            {{-- Code Field --}}
            <div>
                <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                    Mã Giảng đường <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       id="code" 
                       name="code" 
                       value="{{ old('code') }}"
                       class="w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('code') border-red-500 @else border-gray-300 @enderror"
                       placeholder="Nhập mã Giảng đường"
                       required>
                @error('code')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Name Field --}}
            <div>
                <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                    Tên Giảng đường <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       id="name" 
                       name="name" 
                       value="{{ old('name') }}"
                       class="w-full px-3 py-2 border rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @else border-gray-300 @enderror"
                       placeholder="Nhập tên Giảng đường"
                       required>
                @error('name')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Status Field --}}
            <div>
                <label for="status" class="block text-sm font-medium text-gray-700 mb-2">
                    Trạng thái
                </label>
                <select id="status" 
                        name="status" 
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="0" {{ old('status') == 0 ? 'selected' : '' }}>Ngừng hoạt động</option>
                        <option value="1" {{ old('status', 1) == 1 ? 'selected' : '' }}>Hoạt động</option>
                </select>
                @error('status')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            {{-- Submit Buttons --}}
            <div class="flex justify-end space-x-3 pt-6 border-t">
                <a href="{{ route('buildings.index') }}" 
                   class="px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                    Hủy bỏ
                </a>
                <button type="submit" 
                        class="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700">
                    <i class="bi bi-save mr-1"></i>
                    Tạo Giảng đường
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
