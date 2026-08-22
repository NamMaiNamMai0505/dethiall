@extends('layouts.admin')

@section('title', 'Chỉnh sửa đơn vị')
@section('page-title', 'Chỉnh sửa đơn vị')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Đơn vị', 'url' => route('units.index')],
    ['title' => 'Chỉnh sửa']
]" />

{{-- Page Header --}}
<x-page-header
    title="CHỈNH SỬA ĐƠN VỊ"
    :actions="[
        [
            'url' => route('units.index'),
            'label' => 'Quay lại danh sách',
            'icon' => 'arrow-left',
            'color' => 'gray'
        ]
    ]" />

<div class="bg-white rounded-lg shadow-sm p-6">
    <form action="{{ route('units.update', $unit) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="max-w-3xl">
            {{-- Đơn vị cấp trên --}}
            <div class="mb-6">
                <label class="block font-medium mb-2" for="parent_id">
                    Đơn vị cấp trên
                </label>
                <select name="parent_id"
                        id="parent_id"
                        class="form-select w-full @error('parent_id') border-red-500 @enderror"
                >
                    <option value="">Chọn đơn vị cấp trên</option>
                    @foreach($parentUnits as $parentUnit)
                        <option value="{{ $parentUnit->id }}" {{ old('parent_id', $unit->parent_id) == $parentUnit->id ? 'selected' : '' }}>
                            {{ $parentUnit->formatted_name }}
                        </option>
                    @endforeach
                </select>
                @error('parent_id')
                    <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                @enderror
            </div>

            {{-- Mã đơn vị --}}
            <div class="mb-6">
                <label class="block font-medium mb-2" for="code">
                    Mã đơn vị <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       id="code"
                       name="code"
                       value="{{ old('code', $unit->code) }}"
                       class="form-input w-full @error('code') border-red-500 @enderror"
                       placeholder="Nhập mã đơn vị"
                       required
                >
                @error('code')
                    <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                @enderror
            </div>

            {{-- Tên đơn vị --}}
            <div class="mb-6">
                <label class="block font-medium mb-2" for="name">
                    Tên đơn vị <span class="text-red-500">*</span>
                </label>
                <input type="text"
                       id="name"
                       name="name"
                       value="{{ old('name', $unit->name) }}"
                       class="form-input w-full @error('name') border-red-500 @enderror"
                       placeholder="Nhập tên đơn vị"
                       required
                >
                @error('name')
                    <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                @enderror
            </div>

            {{-- Tên viết tắt --}}
            <div class="mb-6">
                <label class="block font-medium mb-2" for="abbreviation">
                    Tên viết tắt
                </label>
                <input type="text"
                       id="abbreviation"
                       name="abbreviation"
                       value="{{ old('abbreviation', $unit->abbreviation) }}"
                       class="form-input w-full @error('abbreviation') border-red-500 @enderror"
                       placeholder="VD: CNTT"
                       maxlength="50"
                >
                <p class="mt-1 text-xs text-slate-500">Dùng thay tên đầy đủ trong báo cáo/thống kê cho gọn.</p>
                @error('abbreviation')
                    <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-6">
                <label class="block font-medium mb-2" for="functional_type">
                    Chức năng đơn vị <span class="text-red-500">*</span>
                </label>
                <select name="functional_type" id="functional_type"
                        class="form-select w-full @error('functional_type') border-red-500 @enderror" required>
                    @foreach(\Modules\Unit\Models\Unit::getFunctionalTypeOptions() as $value => $label)
                        <option value="{{ $value }}" @selected(old('functional_type', $unit->functional_type ?? 'other') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="mt-1 text-xs text-slate-500">Nếu chọn "Khoa chuyên môn", chính Mã đơn vị ở trên sẽ là mã khoa dùng để giới hạn dữ liệu môn, bài học và giảng viên — không cần khai riêng.</p>
                @error('functional_type')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
            </div>

            {{-- Trạng thái --}}
            <div class="mb-6">
                <label class="block font-medium mb-2">Trạng thái</label>
                <div class="flex gap-4">
                    <label class="inline-flex items-center">
                        <input type="radio"
                               name="status"
                               value="active"
                               class="form-radio"
                               {{ old('status', $unit->status) == 'active' ? 'checked' : '' }}
                        >
                        <span class="ml-2">Hoạt động</span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio"
                               name="status"
                               value="inactive"
                               class="form-radio"
                               {{ old('status', $unit->status) == 'inactive' ? 'checked' : '' }}
                        >
                        <span class="ml-2">Tạm ngừng</span>
                    </label>
                </div>
                @error('status')
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
