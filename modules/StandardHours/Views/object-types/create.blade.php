@extends('layouts.admin')

@section('title', 'Thêm đối tượng')
@section('page-title', 'Thêm đối tượng')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Đối tượng', 'url' => route('standard-hours.object-types.index')],
    ['title' => 'Thêm mới']
]" />

<x-page-header
    title="THÊM ĐỐI TƯỢNG"
    :actions="[
        [
            'url' => route('standard-hours.object-types.index'),
            'label' => 'Quay lại danh sách',
            'icon' => 'arrow-left',
            'color' => 'gray'
        ]
    ]" />

<div class="bg-white rounded-lg shadow-sm p-6">
    <form action="{{ route('standard-hours.object-types.store') }}" method="POST">
        @csrf

        <div class="max-w-5xl">
            <p class="mb-4 text-sm text-slate-600 bg-slate-50 border border-slate-100 rounded-lg px-3 py-2">
                Định mức giờ chuẩn + NCKH gắn <strong>đối tượng</strong> (không gắn chức danh).
                Khi kê khai: <code class="bg-white px-1 rounded">giờ phải đạt = định mức GC × tỉ lệ chức danh %</code>
                (VD: 380 × 10% = 38 giờ).
            </p>
            <div class="mb-6 grid sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div>
                    <label class="block font-medium mb-2" for="code">
                        Mã <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="code" name="code" value="{{ old('code') }}"
                           class="form-input w-full font-mono @error('code') border-red-500 @enderror"
                           placeholder="01" maxlength="20" required>
                    @error('code')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label class="block font-medium mb-2" for="name">
                        Tên đối tượng <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}"
                           class="form-input w-full @error('name') border-red-500 @enderror"
                           placeholder="Đối tượng 01" required>
                    @error('name')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="mb-6 grid sm:grid-cols-2 gap-4">
                <div>
                    <label class="block font-medium mb-2" for="standard_hours">
                        Định mức giờ chuẩn (GC) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.01" min="0" id="standard_hours" name="standard_hours"
                           value="{{ old('standard_hours', 380) }}"
                           class="form-input w-full @error('standard_hours') border-red-500 @enderror" required>
                    <p class="text-xs text-slate-500 mt-1">Cơ sở × % chức danh (VD CDHC2: 380)</p>
                    @error('standard_hours')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label class="block font-medium mb-2" for="research_hours">
                        Định mức NCKH (giờ HC) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.01" min="0" id="research_hours" name="research_hours"
                           value="{{ old('research_hours', 300) }}"
                           class="form-input w-full @error('research_hours') border-red-500 @enderror" required>
                    <p class="text-xs text-slate-500 mt-1">Giờ hành chính NCKH phải đạt</p>
                    @error('research_hours')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                </div>
                <div>
                    <label class="block font-medium mb-2" for="administrative_hours">
                        Giờ hành chính <span class="text-red-500">*</span>
                    </label>
                    <input type="number" step="0.01" min="0" id="administrative_hours" name="administrative_hours"
                           value="{{ old('administrative_hours', 1140) }}"
                           class="form-input w-full @error('administrative_hours') border-red-500 @enderror" required>
                    <p class="text-xs text-slate-500 mt-1">Định mức giờ hành chính của đối tượng</p>
                    @error('administrative_hours')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="mb-6">
                <label class="block font-medium mb-2" for="description">Mô tả</label>
                <textarea id="description" name="description" rows="3"
                          class="form-textarea w-full @error('description') border-red-500 @enderror"
                          placeholder="VD: Cao đẳng — TT 06/2026 Đ.11">{{ old('description') }}</textarea>
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
                               {{ old('is_active', '1') == '1' ? 'checked' : '' }}>
                        <span class="ml-2">Đang sử dụng</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio"
                               name="is_active"
                               value="0"
                               class="form-radio"
                               {{ old('is_active') === '0' ? 'checked' : '' }}>
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
                Lưu
            </button>
        </div>
    </form>
</div>
@endsection
