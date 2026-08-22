@extends('layouts.admin')

@section('title', 'Chỉnh sửa đối tượng')
@section('page-title', 'Chỉnh sửa đối tượng')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Đối tượng', 'url' => route('standard-hours.object-types.index')],
    ['title' => 'Chỉnh sửa']
]" />

<x-page-header
    title="CHỈNH SỬA ĐỐI TƯỢNG"
    :actions="[
        [
            'url' => route('standard-hours.object-types.show', $objectType),
            'label' => 'Xem chi tiết',
            'icon' => 'eye',
            'color' => 'gray'
        ],
        [
            'url' => route('standard-hours.object-types.index'),
            'label' => 'Quay lại danh sách',
            'icon' => 'arrow-left',
            'color' => 'gray'
        ]
    ]" />

<div class="bg-white rounded-lg shadow-sm p-6">
    <form action="{{ route('standard-hours.object-types.update', $objectType) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="max-w-5xl">
            <p class="mb-4 text-sm text-slate-600 bg-slate-50 border border-slate-100 rounded-lg px-3 py-2">
                Giờ phải đạt = <strong>định mức GC × tỉ lệ chức danh</strong>
                ({{ number_format((float)$objectType->standard_hours, 0) }} GC × % chức danh).
            </p>
            <div class="mb-6 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block font-medium mb-2" for="code">Mã <span class="text-red-500">*</span></label>
                    <input type="text" id="code" name="code"
                           value="{{ old('code', $objectType->code) }}"
                           class="form-input w-full font-mono @error('code') border-red-500 @enderror"
                           maxlength="20" required>
                    @error('code')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label class="block font-medium mb-2" for="name">Tên đối tượng <span class="text-red-500">*</span></label>
                    <input type="text" id="name" name="name"
                           value="{{ old('name', $objectType->name) }}"
                           class="form-input w-full @error('name') border-red-500 @enderror" required>
                    @error('name')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="mb-6 grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-medium mb-2" for="standard_hours">Định mức giờ chuẩn (GC) <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" min="0" id="standard_hours" name="standard_hours"
                           value="{{ old('standard_hours', $objectType->standard_hours) }}"
                           class="form-input w-full @error('standard_hours') border-red-500 @enderror" required>
                    @error('standard_hours')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label class="block font-medium mb-2" for="research_hours">Định mức NCKH (giờ HC) <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" min="0" id="research_hours" name="research_hours"
                           value="{{ old('research_hours', $objectType->research_hours) }}"
                           class="form-input w-full @error('research_hours') border-red-500 @enderror" required>
                    @error('research_hours')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label class="block font-medium mb-2" for="administrative_hours">Giờ hành chính <span class="text-red-500">*</span></label>
                    <input type="number" step="0.01" min="0" id="administrative_hours" name="administrative_hours"
                           value="{{ old('administrative_hours', $objectType->administrative_hours) }}"
                           class="form-input w-full @error('administrative_hours') border-red-500 @enderror" required>
                    @error('administrative_hours')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                </div>
            </div>
            <div class="mb-6">
                <label class="block font-medium mb-2" for="description">Mô tả</label>
                <textarea id="description" name="description" rows="3"
                          class="form-textarea w-full @error('description') border-red-500 @enderror">{{ old('description', $objectType->description) }}</textarea>
                @error('description')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
            </div>

            <div class="mb-6">
                <label class="block font-medium mb-2">Trạng thái</label>
                <div class="flex gap-4">
                    <label class="inline-flex items-center">
                        <input type="radio"
                               name="is_active"
                               value="1"
                               class="form-radio"
                               {{ old('is_active', $objectType->is_active ? '1' : '0') == '1' ? 'checked' : '' }}>
                        <span class="ml-2">Đang sử dụng</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio"
                               name="is_active"
                               value="0"
                               class="form-radio"
                               {{ old('is_active', $objectType->is_active ? '1' : '0') == '0' ? 'checked' : '' }}>
                        <span class="ml-2">Ngừng sử dụng</span>
                    </label>
                </div>
                @error('is_active')
                    <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                @enderror
            </div>
        </div>

        <div class="border-t border-gray-200 mt-6 pt-6 text-right">
            <button type="submit" class="btn btn-primary min-w-[100px]">
                Cập nhật
            </button>
        </div>
    </form>
</div>
@endsection
