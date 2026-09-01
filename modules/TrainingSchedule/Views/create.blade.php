@extends('layouts.admin')

@section('title', 'Tạo Lịch đào tạo Mới')
@section('page-title', 'Tạo Lịch đào tạo Mới')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Lịch đào tạo', 'url' => route('training-schedules.index')],
    ['title' => 'Tạo mới']
]" />

{{-- Hiển thị lỗi nếu có --}}
@if(session('error'))
    <div class="mb-4 px-4 py-2 bg-red-100 border border-red-200 text-red-700 rounded">
        {!! session('error') !!}
    </div>
@endif
@if($errors->any())
    <div class="mb-4 px-4 py-2 bg-red-100 border border-red-200 text-red-700 rounded">
        <ul class="list-disc pl-5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- Page Header --}}
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-900">TẠO LỊCH ĐÀO TẠO MỚI</h1>
    <a href="{{ route('training-schedules.index') }}"
       class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium">
        <i class="bi bi-arrow-left mr-2"></i>Quay lại
    </a>
</div>

<form action="{{ route('training-schedules.store') }}" method="POST" id="training-schedule-form">
    @csrf
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Form Fields --}}
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-sm border">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">THÔNG TIN CƠ BẢN</h3>
                </div>
                <div class="p-6 space-y-6">
                    {{-- Tên lịch đào tạo - luôn hiển thị --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                                Tên lịch đào tạo <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="name" name="name"
                                   value="{{ old('name') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
                                   placeholder="Lịch đào tạo Y54"
                                   required>
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Tên viết tắt - luôn hiển thị --}}
                        <div>
                            <label for="abbreviation" class="block text-sm font-medium text-gray-700 mb-2">
                                Tên viết tắt
                            </label>
                            <input type="text" id="abbreviation" name="abbreviation"
                                   value="{{ old('abbreviation') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('abbreviation') border-red-500 @enderror"
                                   placeholder="VD: LHL-Y54-HK1">
                            @error('abbreviation')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Mã lịch --}}
                    <div>
                        <label for="code" class="block text-sm font-medium text-gray-700 mb-2">
                            Mã lịch <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="code" name="code"
                               value="{{ old('code') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('code') border-red-500 @enderror"
                               placeholder="VD: LHL-Y54-HK1">
                        @error('code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Ngành đào tạo và lớp học --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="specialization_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Ngành đào tạo <span class="text-red-500">*</span>
                            </label>
                            <select id="specialization_id" name="specialization_id"
                                    data-searchable="1"
                                    data-placeholder="Chọn ngành đào tạo"
                                    class="w-full @error('specialization_id') border-red-500 @enderror"
                                    required>
                                <option value="">-- Chọn ngành đào tạo --</option>
                                @foreach($specializations as $specialization)
                                    <option value="{{ $specialization->id }}"
                                            {{ old('specialization_id') == $specialization->id ? 'selected' : '' }}>
                                        {{ $specialization->selection_label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('specialization_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="class_code" class="block text-sm font-medium text-gray-700 mb-2">
                                Lớp học
                            </label>
                            <select id="class_code" name="class_code"
                                    data-searchable="1"
                                    data-placeholder="Chọn lớp học"
                                    class="w-full @error('class_code') border-red-500 @enderror">
                                <option value="">-- Chọn lớp học --</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->code }}"
                                            data-specialization-id="{{ $class->specialization_id }}"
                                            {{ old('class_code', request('class_code')) == $class->code ? 'selected' : '' }}>
                                        {{ $class->name }}@if($class->code) ({{ $class->code }})@endif
                                    </option>
                                @endforeach
                            </select>
                            @error('class_code')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Năm học và học kỳ --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Năm học <span class="text-red-500">*</span>
                    </label>
                    <x-academic-year-select :selected="request('academic_year')" required />
                </div>

                        <div>
                            <label for="semester" class="block text-sm font-medium text-gray-700 mb-2">
                                Học kỳ <span class="text-red-500">*</span>
                            </label>
                            <select id="semester" name="semester"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('semester') border-red-500 @enderror"
                                    required>
                                <option value="">-- Chọn học kỳ --</option>
                                <option value="semester_1" {{ old('semester') == '1' ? 'selected' : '' }}>Học kỳ 1</option>
                                <option value="semester_2" {{ old('semester') == 'semester_2' ? 'selected' : '' }}>Học kỳ 2</option>
                                <option value="summer" {{ old('semester') == 'summer' ? 'selected' : '' }}>Học kỳ hè</option>
                            </select>
                            @error('semester')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Thời gian --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Ngày bắt đầu <span class="text-red-500">*</span>
                            </label>
                            <input type="date" id="start_date" name="start_date"
                                   value="{{ old('start_date') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('start_date') border-red-500 @enderror"
                                   required>
                            @error('start_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-2">
                                Ngày kết thúc <span class="text-red-500">*</span>
                            </label>
                            <input type="date" id="end_date" name="end_date"
                                   value="{{ old('end_date') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('end_date') border-red-500 @enderror"
                                   required>
                            @error('end_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Giảng đường chính --}}
                    {{-- <div>
                        <label for="classroom_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Giảng đường chính
                        </label>
                        <select id="classroom_id" name="classroom_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('classroom_id') border-red-500 @enderror">
                            <option value="">-- Chọn giảng đường --</option>
                            @foreach($classrooms as $classroom)
                                <option value="{{ $classroom->id }}"
                                        {{ old('classroom_id') == $classroom->id ? 'selected' : '' }}>
                                    {{ $classroom->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('classroom_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div> --}}

                    {{-- Mô tả --}}
                    {{-- <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Mô tả
                        </label>
                        <textarea id="description" name="description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('description') border-red-500 @enderror"
                                  placeholder="Nhập mô tả chi tiết về lịch đào tạo">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div> --}}
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Actions Card --}}
            <div class="bg-white rounded-lg shadow-sm border">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">THAO TÁC</h3>
                </div>
                <div class="p-6 space-y-3">
                    {{-- Luôn hiển thị button Lưu lịch đào tạo --}}
                    <button type="submit"
                            class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium">
                        <i class="bi bi-check-circle mr-2"></i>Lưu lịch đào tạo
                    </button>
                    
                    <a href="{{ route('training-schedules.index') }}"
                       class="w-full bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium text-center block">
                        <i class="bi bi-x-circle mr-2"></i>Hủy bỏ
                    </a>
                </div>
            </div>

            {{-- Status Card --}}
            <div class="bg-white rounded-lg shadow-sm border">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">TRẠNG THÁI</h3>
                </div>
                <div class="p-6">
                    <div class="flex items-center">
                        <input type="checkbox" id="is_active" name="is_active" value="1"
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                               {{ old('is_active', true) ? 'checked' : '' }}>
                        <label for="is_active" class="ml-2 block text-sm text-gray-700">
                            Kích hoạt ngay sau khi tạo
                        </label>
                    </div>
                </div>
            </div>

            {{-- Help Card --}}
            <div class="bg-blue-50 rounded-lg border border-blue-200">
                <div class="p-6">
                    <div class="flex items-start">
                        <i class="bi bi-info-circle text-blue-600 text-xl mr-3 mt-0.5"></i>
                        <div>
                            <h4 class="text-sm font-medium text-blue-800 mb-2">Hướng dẫn</h4>
                            <ul class="text-sm text-blue-700 space-y-1">
                                <li>• Các trường có dấu (*) là bắt buộc</li>
                                <li>• Mã lịch sẽ tự động tạo nếu để trống</li>
                                <li>• Lịch chi tiết sẽ được quản lý riêng</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
@php
    $classesForJs = collect($classes ?? [])->map(function ($c) {
        return [
            'value' => $c->code,
            'text' => $c->name.($c->code ? ' ('.$c->code.')' : ''),
            'specialization_id' => (string) $c->specialization_id,
        ];
    })->values();
@endphp
<script>
(function () {
    const classesUrl = @json(route('training-schedules.api.classes'));
    const allClasses = @json($classesForJs);

    function getVal(el) {
        if (!el) return '';
        if (typeof window.getSelectValue === 'function') return String(window.getSelectValue(el) || '');
        if (el.tomselect) {
            const v = el.tomselect.getValue();
            return String(Array.isArray(v) ? (v[0] || '') : (v || ''));
        }
        return String(el.value || '');
    }

    function bindChange(el, handler) {
        if (!el) return;
        if (typeof window.onTomChange === 'function') {
            window.onTomChange(el, handler);
            return;
        }
        el.addEventListener('change', handler);
        if (el.tomselect) el.tomselect.on('change', handler);
    }

    function setClassOptions(items, selected) {
        const classSelect = document.getElementById('class_code');
        if (!classSelect) return;
        const list = [{ value: '', text: '-- Chọn lớp học --' }].concat(items || []);
        if (typeof window.setTomSelectOptions === 'function') {
            window.setTomSelectOptions(classSelect, list, { selected: selected || '', enabled: true });
            return;
        }
        classSelect.innerHTML = '';
        list.forEach(function (item) {
            const opt = document.createElement('option');
            opt.value = item.value;
            opt.textContent = item.text;
            if (selected && String(item.value) === String(selected)) opt.selected = true;
            classSelect.appendChild(opt);
        });
    }

    function filterClassesBySpecialization() {
        const specializationSelect = document.getElementById('specialization_id');
        const classSelect = document.getElementById('class_code');
        if (!specializationSelect || !classSelect) return;

        const specId = getVal(specializationSelect);
        const current = getVal(classSelect);

        if (!specId) {
            setClassOptions(allClasses, '');
            return;
        }

        // Ưu tiên lọc local (nhanh); fallback API nếu list rỗng
        let filtered = allClasses.filter(function (c) {
            return String(c.specialization_id) === String(specId);
        });

        if (filtered.length > 0) {
            const keep = filtered.some(function (c) { return String(c.value) === String(current); }) ? current : '';
            setClassOptions(filtered, keep);
            return;
        }

        setClassOptions([], '');
        fetch(classesUrl + '?specialization_id=' + encodeURIComponent(specId), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                const items = (data || []).map(function (c) {
                    return {
                        value: c.code,
                        text: c.name + (c.code ? ' (' + c.code + ')' : ''),
                        specialization_id: String(specId),
                    };
                });
                setClassOptions(items, '');
            })
            .catch(function (err) {
                console.error('Error fetching classes:', err);
            });
    }

    function syncSpecializationFromClass() {
        const specializationSelect = document.getElementById('specialization_id');
        const classSelect = document.getElementById('class_code');
        const selected = classSelect?.selectedOptions?.[0];
        const specializationId = selected?.dataset?.specializationId || '';
        if (!specializationSelect || !specializationId || getVal(specializationSelect) === specializationId) return;
        if (specializationSelect.tomselect) {
            specializationSelect.tomselect.setValue(specializationId, true);
        } else {
            specializationSelect.value = specializationId;
        }
        specializationSelect.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function boot() {
        const specializationSelect = document.getElementById('specialization_id');
        const classSelect = document.getElementById('class_code');
        if (!specializationSelect || !classSelect) return;
        if (specializationSelect.dataset.classFilterBound === '1') return;
        specializationSelect.dataset.classFilterBound = '1';

        if (typeof window.initTomSelects === 'function') {
            window.initTomSelects(document.getElementById('admin-content') || document);
        }

        bindChange(specializationSelect, filterClassesBySpecialization);
        bindChange(classSelect, syncSpecializationFromClass);

        // Nếu đã có ngành (old input) → lọc lớp ngay
        if (getVal(specializationSelect)) {
            filterClassesBySpecialization();
        }
    }

    document.addEventListener('DOMContentLoaded', boot);
    document.addEventListener('turbo:load', boot);
    if (document.readyState !== 'loading') boot();
})();
</script>
@endpush
