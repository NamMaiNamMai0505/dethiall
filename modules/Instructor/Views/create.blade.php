@extends('layouts.admin')

@section('title', 'Tạo mới giảng viên')
@section('page-title', 'Tạo mới giảng viên')

@section('content')
{{-- Flash Messages --}}
@if(session('success'))
    <div class="mb-4 px-4 py-2 bg-green-100 border border-green-200 text-green-700 rounded">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="mb-4 px-4 py-2 bg-red-100 border border-red-200 text-red-700 rounded">
        {{ session('error') }}
    </div>
@endif

{{-- Header với nút actions --}}
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">TẠO MỚI GIẢNG VIÊN</h1>
    <div class="flex gap-3">
        <button type="button"
                onclick="document.getElementById('instructor-form').submit()"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
            Tạo mới
        </button>
        <a href="{{ route('instructors.index') }}"
           class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm font-medium">
            Quay lại danh sách
        </a>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm p-6">
    <form id="instructor-form" action="{{ route('instructors.store') }}" method="POST">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Cột trái --}}
            <div class="space-y-6">
                {{-- Họ và tên --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="name">
                        Họ và tên <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name') }}"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @else border-gray-300 @enderror"
                           placeholder="Nhập họ và tên"
                           required
                    >
                    @error('name')
                        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Số điện thoại --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="phone">
                        Số điện thoại <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="phone"
                           name="phone"
                           value="{{ old('phone') }}"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('phone') border-red-500 @else border-gray-300 @enderror"
                           placeholder="Nhập số điện thoại"
                           required
                    >
                    @error('phone')
                        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Trạng thái --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Trạng thái</label>
                    <div class="flex gap-4">
                        <label class="inline-flex items-center">
                            <input type="radio"
                                   name="status"
                                   value="active"
                                   class="text-blue-600 form-radio"
                                   {{ old('status', 'active') == 'active' ? 'checked' : '' }}
                            >
                            <span class="ml-2 text-sm text-gray-700">Hoạt động</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio"
                                   name="status"
                                   value="inactive"
                                   class="text-blue-600 form-radio"
                                   {{ old('status') == 'inactive' ? 'checked' : '' }}
                            >
                            <span class="ml-2 text-sm text-gray-700">Tạm ngừng</span>
                        </label>
                    </div>
                    @error('status')
                        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            {{-- Cột phải --}}
            <div class="space-y-6">
                {{-- Khoa/Chuyên môn --}}
                {{-- <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="specialization_id">
                        Chuyên môn <span class="text-red-500">*</span>
                    </label>
                    <select name="specialization_id"
                            id="specialization_id"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('specialization_id') border-red-500 @else border-gray-300 @enderror"
                            required
                    >
                        <option value="">Chọn chuyên môn</option>
                        @foreach($specializations as $specialization)
                            <option value="{{ $specialization->id }}" {{ old('specialization_id') == $specialization->id ? 'selected' : '' }}>
                                {{ $specialization->selection_label }}
                            </option>
                        @endforeach
                    </select>
                    @error('specialization_id')
                        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                    @enderror
                </div> --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="unit_id">
                        Đơn vị <span class="text-red-500">*</span>
                    </label>
                    <select name="unit_id"
                            id="unit_id"
                            class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('unit_id') border-red-500 @else border-gray-300 @enderror"
                            required
                    >
                        <option value="">Chọn Đơn vị</option>
                        @foreach($units as $unit)
                            <option value="{{ $unit->id }}" {{ old('unit_id') == $unit->id ? 'selected' : '' }}>
                                {{ $unit->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('specialization_id')
                        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="email">
                        Email
                    </label>
                    <input type="email"
                           id="email"
                           name="email"
                           value="{{ old('email') }}"
                           class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-500 @else border-gray-300 @enderror"
                           placeholder="Nhập email"
                           required
                    >
                    @error('email')
                        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                    @enderror
                </div>

                {{-- Mã giảng viên --}}
                <div class="hidden">
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="code">
                        Mã giảng viên 
                    </label>
                    <input type="text"
                           id="code"
                           name="code"
                           value="{{ old('code') }}"
                           class="w-full px-3 py-2 border rounded-lg pointer-events-none"
                           placeholder="Nhập mã giảng viên"
                           readonly
                    >
                    @error('code')
                        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        {{-- Mô tả (full width) --}}
        <div class="mt-6">
            <label class="block text-sm font-medium text-gray-700 mb-2" for="description">
                Mô tả
            </label>
            <textarea id="description"
                      name="description"
                      rows="4"
                      class="w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('description') border-red-500 @else border-gray-300 @enderror"
                      placeholder="Nhập mô tả về giảng viên"
            >{{ old('description') }}</textarea>
            @error('description')
                <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
            @enderror
        </div>
    </form>
</div>
@endsection
