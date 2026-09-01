@extends('layouts.admin')

@section('title', 'Chỉnh sửa Lịch Đào Tạo')
@section('page-title', 'Chỉnh sửa Lịch Đào Tạo')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Lịch Đào Tạo', 'url' => route('training-schedules.index')],
    ['title' => 'Chỉnh sửa']
]" />

{{-- Flash: layouts.admin; chỉ hiện validation errors của form --}}
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
    <h1 class="text-2xl font-bold text-gray-900">CHỈNH SỬA LỊCH ĐÀO TẠO</h1>
    <div class="flex space-x-3">
        <button type="submit" form="training-schedule-form" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
            Cập nhật
        </button>
        <a href="{{ route('training-schedules.show', $trainingSchedule) }}" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium">
            Hủy
        </a>
    </div>
</div>

{{-- Form --}}
<form id="training-schedule-form" action="{{ route('training-schedules.update', $trainingSchedule) }}" method="POST">
    @csrf
    @method('PUT')
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main Form Fields --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Basic Info Card --}}
            <div class="bg-white rounded-lg shadow-sm border">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">THÔNG TIN CƠ BẢN</h3>
                </div>
                <div class="p-6 space-y-6">
                    {{-- Tên lịch đào tạo và viết tắt --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Tên lịch đào tạo <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" value="{{ old('name', $trainingSchedule->name) }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('name') border-red-500 @enderror"
                                   placeholder="Lịch đào tạo Y54">
                            @error('name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Tên viết tắt</label>
                            <input type="text" name="abbreviation" value="{{ old('abbreviation', $trainingSchedule->abbreviation) }}" maxlength="20"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('abbreviation') border-red-500 @enderror"
                                   placeholder="VD: LHL-Y54-2024-HK1">
                            @error('abbreviation')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">Tối đa 20 ký tự. Sử dụng cho hiển thị gọn trong lịch.</p>
                        </div>
                    </div>

                    {{-- Mã lịch --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mã lịch đào tạo</label>
                        <input type="text" name="code" value="{{ old('code', $trainingSchedule->code) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('code') border-red-500 @enderror"
                               placeholder="Mã sẽ được tự động tạo...">
                        @error('code')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Ngành đào tạo và lớp học --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Ngành đào tạo <span class="text-red-500">*</span>
                            </label>
                            <select name="specialization_id" id="specialization_id" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('specialization_id') border-red-500 @enderror">
                                <option value="">-- Chọn ngành đào tạo --</option>
                                @foreach($specializations as $specialization)
                                    <option value="{{ $specialization->id }}"
                                            {{ old('specialization_id', $trainingSchedule->specialization_id) == $specialization->id ? 'selected' : '' }}>
                                        {{ $specialization->selection_label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('specialization_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Lớp học</label>
                            <select name="class_code" id="class_code" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('class_code') border-red-500 @enderror">
                                <option value="">-- Chọn lớp học --</option>
                                @foreach($classes as $class)
                                    <option value="{{ $class->code }}"
                                            {{ old('class_code', $trainingSchedule->class_code) == $class->code ? 'selected' : '' }}>
                                        {{ $class->name }}
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
                            <x-academic-year-select 
                                :selected="old('academic_year', $trainingSchedule->academic_year)" 
                                required 
                                :disabled="$trainingSchedule->hasScheduleDetails()"
                                placeholder="Chọn năm học" />

                            {{-- Nếu đã có chi tiết thì thêm hidden input để giữ value --}}
                            @if($trainingSchedule->hasScheduleDetails())
                                <input type="hidden" name="academic_year" value="{{ $trainingSchedule->academic_year }}">
                                <p class="mt-1 text-xs text-gray-500 italic">Lịch đã có chi tiết, không thể thay đổi năm học.</p>
                            @endif
                            @error('academic_year')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Học kỳ <span class="text-red-500">*</span>
                            </label>

                            <select name="semester" required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('semester') border-red-500 @enderror disabled:opacity-50"
                                    {{ $trainingSchedule->hasScheduleDetails() ? 'disabled' : '' }}
                                    title="{{ $trainingSchedule->hasScheduleDetails() ? 'Không thể sửa vì đã có lịch chi tiết' : '' }}">
                                <option value="">-- Chọn học kỳ --</option>
                                <option value="semester_1" {{ old('semester', $trainingSchedule->semester) == 'semester_1' ? 'selected' : '' }}>Học kỳ 1</option>
                                <option value="semester_2" {{ old('semester', $trainingSchedule->semester) == 'semester_2' ? 'selected' : '' }}>Học kỳ 2</option>
                                <option value="summer" {{ old('semester', $trainingSchedule->semester) == 'summer' ? 'selected' : '' }}>Học kỳ hè</option>
                            </select>

                            {{-- Nếu đã có chi tiết thì thêm hidden input để giữ value --}}
                            @if($trainingSchedule->hasScheduleDetails())
                                <input type="hidden" name="semester" value="{{ $trainingSchedule->semester }}">
                                <p class="mt-1 text-xs text-gray-500 italic">Lịch đã có chi tiết, không thể thay đổi học kỳ.</p>
                            @endif

                            @error('semester')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Thời gian --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Ngày bắt đầu <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="start_date" value="{{ old('start_date', $trainingSchedule->start_date?->format('Y-m-d')) }}" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('start_date') border-red-500 @enderror disabled:opacity-50"
                                {{ $trainingSchedule->hasScheduleDetails() ? 'disabled' : '' }}  {{-- Chỉ disabled, không readonly --}}
                                title="{{ $trainingSchedule->hasScheduleDetails() ? 'Không thể sửa vì đã có lịch chi tiết' : '' }}">
                            @error('start_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            @if($trainingSchedule->hasScheduleDetails())
                                <p class="mt-1 text-xs text-gray-500 italic">Lịch đã có chi tiết, không thể thay đổi ngày bắt đầu.</p>
                            @endif

                            {{-- Sau input date (hoặc ở cuối form) --}}
                            @if($trainingSchedule->hasScheduleDetails())
                                {{-- Giá trị thực sẽ được gửi qua input hidden (đã có hoặc thêm nếu cần) --}}
                                <input type="hidden" name="start_date" value="{{ old('start_date', $trainingSchedule->start_date?->format('Y-m-d')) }}">
                                <input type="hidden" name="end_date"   value="{{ old('end_date', $trainingSchedule->end_date?->format('Y-m-d')) }}">

                                {{-- Hidden để validator same:... so sánh --}}
                                <input type="hidden" name="old_start_date" value="{{ $trainingSchedule->start_date?->format('Y-m-d') }}">
                                <input type="hidden" name="old_end_date"   value="{{ $trainingSchedule->end_date?->format('Y-m-d') }}">
                            @endif

                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Ngày kết thúc <span class="text-red-500">*</span>
                            </label>
                            <input type="date" name="end_date" value="{{ old('end_date', $trainingSchedule->end_date?->format('Y-m-d')) }}" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('end_date') border-red-500 @enderror disabled:opacity-50"
                                {{ $trainingSchedule->hasScheduleDetails() ? 'disabled' : '' }}  {{-- Chỉ disabled --}}
                                title="{{ $trainingSchedule->hasScheduleDetails() ? 'Không thể sửa vì đã có lịch chi tiết' : '' }}">
                            @error('end_date')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            @if($trainingSchedule->hasScheduleDetails())
                                <p class="mt-1 text-xs text-gray-500 italic">Lịch đã có chi tiết, không thể thay đổi ngày kết thúc.</p>
                            @endif
                        </div>
                    </div>

                    {{-- Giảng đường --}}
                    {{-- <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Giảng đường</label>
                        <select name="classroom_id" id="classroom_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('classroom_id') border-red-500 @enderror">
                            <option value="">-- Chọn giảng đường --</option>
                            @foreach($classrooms as $classroom)
                                <option value="{{ $classroom->id }}"
                                        {{ old('classroom_id', $trainingSchedule->classroom_id) == $classroom->id ? 'selected' : '' }}>
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
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mô tả</label>
                        <textarea name="description" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('description') border-red-500 @enderror"
                                  placeholder="Mô tả về lịch đào tạo...">{{ old('description', $trainingSchedule->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div> --}}
                </div>
            </div>


        </div> {{-- Kết thúc Main Form Fields (lg:col-span-2) --}}
                {{-- Sidebar --}}
        <div class="space-y-6">
            {{-- Actions Card --}}
            <div class="bg-white rounded-lg shadow-sm border">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">THAO TÁC</h3>
                </div>
                <div class="p-6 space-y-3">
                    {{-- Button Cập nhật (submit form) --}}
                    <button type="submit" form="training-schedule-form"
                            class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                        <i class="bi bi-check-circle mr-2"></i>Cập nhật lịch đào tạo
                    </button>
                    
                    <a href="{{ route('training-schedules.show', $trainingSchedule) }}"
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
                               {{ old('is_active', $trainingSchedule->is_active) ? 'checked' : '' }}>
                        <label for="is_active" class="ml-2 block text-sm text-gray-700">
                            Kích hoạt lịch đào tạo
                        </label>
                    </div>
                    @error('is_active')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Help Card --}}
            <div class="bg-blue-50 rounded-lg border border-blue-200">
                <div class="p-6">
                    <div class="flex items-start">
                        <i class="bi bi-info-circle text-blue-600 text-xl mr-3 mt-0.5"></i>
                        <div>
                            <h4 class="text-sm font-medium text-blue-800 mb-2">Hướng dẫn chỉnh sửa</h4>
                            <ul class="text-sm text-blue-700 space-y-1">
                                <li>• Các trường có dấu (*) là bắt buộc</li>
                                <li>• Mã lịch sẽ tự động tạo nếu để trống</li>
                                <li>• Thay đổi lịch đào tạo sẽ cập nhật toàn bộ</li>
                                <li>• Sử dụng + Thêm tiết để thêm tiết học cho ngày</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div> {{-- Kết thúc grid grid-cols-1 lg:grid-cols-3 --}}

</form>



    {{-- Hidden fields for compatibility --}}
    <input type="hidden" name="schedule_type" value="course">
    <input type="hidden" name="start_date" id="hidden-start-date" value="{{ old('start_date', $trainingSchedule->start_date?->format('Y-m-d')) }}">
    <input type="hidden" name="end_date" id="hidden-end-date" value="{{ old('end_date', $trainingSchedule->end_date?->format('Y-m-d')) }}">
</form>

@endsection
@push('scripts')
{{--
    Lọc liên động Ngành → Lớp — cùng cơ chế với create.blade.php (Sprint 44 /
    B2). Bản cũ ghi thẳng innerHTML của <select> nên không đồng bộ với
    TomSelect (select đã bị TomSelect bọc từ initTomSelects toàn cục) và
    không lọc lại khi vừa mở trang sửa (ngành đã chọn sẵn nhưng lớp vẫn hiện
    hết). Dùng window.setTomSelectOptions() để rebuild đúng cách, lọc local
    trước (allClasses đã có sẵn từ server), chỉ gọi API khi danh sách local
    rỗng.
--}}
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
    const initialClassCode = @json(old('class_code', $trainingSchedule->class_code));

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

    function filterClassesBySpecialization(keepInitial) {
        const specializationSelect = document.getElementById('specialization_id');
        const classSelect = document.getElementById('class_code');
        if (!specializationSelect || !classSelect) return;

        const specId = getVal(specializationSelect);
        const current = keepInitial ? initialClassCode : getVal(classSelect);

        if (!specId) {
            setClassOptions(allClasses, current);
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
                const keep = items.some(function (c) { return String(c.value) === String(current); }) ? current : '';
                setClassOptions(items, keep);
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

        bindChange(specializationSelect, function () {
            filterClassesBySpecialization(false);
        });
        bindChange(classSelect, syncSpecializationFromClass);

        // Ngành đã có sẵn (đang sửa lịch) → lọc lớp ngay, giữ lớp đang chọn.
        if (getVal(specializationSelect)) {
            filterClassesBySpecialization(true);
        }
    }

    document.addEventListener('DOMContentLoaded', boot);
    document.addEventListener('turbo:load', boot);
    if (document.readyState !== 'loading') boot();
})();
</script>
@endpush
