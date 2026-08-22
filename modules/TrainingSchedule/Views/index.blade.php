@extends('layouts.admin')

@section('title', 'Lịch Đào Tạo')
@section('page-title', 'Lịch Đào Tạo')

@section('content')
    {{-- Breadcrumb --}}
    <x-breadcrumb :items="[
            ['title' => 'Trang chủ'],
            ['title' => 'Lịch đào tạo', 'url' => route('training-schedules.hub')],
            ['title' => 'Danh sách']
        ]" />

    {{-- Flash: chỉ render ở layouts.admin — không include trùng --}}

    {{-- Page Header --}}
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">DANH SÁCH LỊCH ĐÀO TẠO</h1>
        <div class="flex space-x-3">
            <a href="{{ route('training-schedules.hub') }}"
                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                <i class="bi bi-grid mr-2"></i>Hub
            </a>
            <a href="{{ route('training-schedules.calendar') }}"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium">
                <i class="bi bi-calendar3 mr-2"></i>Lịch tổng hợp
            </a>
            @if(auth()->user()?->can('training-schedules.create') && \App\Support\TrainingDept::canManageScheduleSkeleton())
            <a href="{{ route('training-schedules.create') }}"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                <i class="bi bi-plus-lg mr-2"></i>Tạo mới
            </a>
            @endif
        </div>
    </div>

    {{-- Filters --}}
    <div class="bg-white rounded-lg shadow-sm border mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">BỘ LỌC</h3>
        </div>
        <div class="p-6">
            <form action="{{ route('training-schedules.list') }}" method="GET" id="filter-form">
                <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-7 gap-4 items-end">
                    {{-- Search --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tìm kiếm</label>
                        <input type="text" name="search" data-live-search="1" value="{{ request('search') }}"
                            placeholder="Tên lịch, mã, mô tả..." class="w-full border border-gray-300 rounded-md px-3 py-2">
                    </div>

                    {{-- Ngành đào tạo --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Ngành đào tạo</label>
                        <select name="specialization_id" id="filter_specialization_id"
                                data-searchable="1" data-placeholder="Tất cả ngành"
                                class="w-full">
                            <option value="">Tất cả ngành đào tạo</option>
                            @foreach($specializations as $specialization)
                                <option value="{{ $specialization->id }}" {{ (string) request('specialization_id') === (string) $specialization->id ? 'selected' : '' }}>
                                    {{ $specialization->selection_label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Lớp --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Lớp</label>
                        <select name="class_id" id="filter_class_id"
                                data-searchable="1" data-placeholder="Tất cả lớp"
                                class="w-full">
                            <option value="">Tất cả lớp</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}"
                                        data-specialization-id="{{ $class->specialization_id }}"
                                        {{ (string) request('class_id') === (string) $class->id ? 'selected' : '' }}>
                                    {{ $class->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Thời gian --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Học kỳ</label>
                        <select name="semester" id="filter_semester" data-searchable="0" class="w-full">
                            <option value="">Tất cả học kỳ</option>
                            @foreach($semesters as $value => $label)
                                <option value="{{ $value }}" {{ (string) request('semester') === (string) $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Năm học --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Năm học</label>
                        <select name="academic_year" id="filter_academic_year" data-searchable="0" class="w-full">
                            <option value="">Tất cả năm học</option>
                            @foreach($academic_years as $value => $label)
                                <option value="{{ $value }}" {{ (string) request('academic_year') === (string) $value ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Trạng thái --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Trạng thái</label>
                        <select name="is_active" id="filter_is_active" data-searchable="0" class="w-full">
                            <option value="">Tất cả</option>
                            <option value="active" {{ request('is_active') == 'active' ? 'selected' : '' }}>
                                Hoạt động
                            </option>
                            <option value="inactive" {{ request('is_active') == 'inactive' ? 'selected' : '' }}>
                                Tạm dừng
                            </option>
                        </select>
                    </div>

                    {{-- Actions — cùng hàng/cùng cao với dropdown --}}
                    <div class="flex flex-col sm:flex-row sm:items-stretch gap-2">
                        <button type="submit"
                            class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-medium text-white shadow-sm hover:bg-blue-700">
                            <i class="bi bi-funnel leading-none"></i>
                            <span>Lọc</span>
                        </button>
                        <a href="{{ route('training-schedules.list') }}"
                            class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-lg bg-red-600 px-4 text-sm font-medium text-white shadow-sm hover:bg-red-700">
                            <i class="bi bi-x-circle leading-none"></i>
                            <span>Xóa lọc</span>
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Export Options --}}
    <div class="mb-6 flex justify-between items-center">
        <div class="flex items-center text-gray-600">
            <i class="bi bi-info-circle mr-2"></i>
            Tổng số: {{ $schedules->total() }} lịch đào tạo
        </div>
        <div class="flex space-x-2">
            <a href="{{ route('training-schedules.export', request()->query()) }}"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md text-sm">
                <i class="bi bi-download mr-1"></i>Xuất CSV
            </a>
        </div>
    </div>

    {{-- Training Schedules Table --}}
    <div class="bg-white rounded-lg shadow-sm border">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Thông tin cơ bản
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Ngành đào tạo
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Năm học / Học kỳ
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Thời gian
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Số tuần
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Trạng thái
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Thao tác
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($schedules as $schedule)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex flex-col">
                                    <div class="text-sm font-medium text-gray-900">
                                        {{ $schedule->name }}
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        Mã: {{ $schedule->code }}
                                        @if($schedule->abbreviation)
                                            <span class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded-full">
                                                {{ $schedule->abbreviation }}
                                            </span>
                                        @endif
                                    </div>
                                    @if($schedule->description)
                                        <div class="text-xs text-gray-400 mt-1">
                                            {{ Str::limit($schedule->description, 60) }}
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ $schedule->specialization?->report_label ?? 'N/A' }}
                                </div>
                                @if($schedule->classModel)
                                    <div class="text-xs text-gray-500">
                                        Lớp: {{ $schedule->classModel->name ?? 'N/A' }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ $schedule->academic_year }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $schedule->semester_text }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    {{ $schedule->start_date ? $schedule->start_date->format('d/m/Y') : 'N/A' }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    đến {{ $schedule->end_date ? $schedule->end_date->format('d/m/Y') : 'N/A' }}
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800"
                                    title="Quy đổi từ ngày bắt đầu → kết thúc">
                                    {{ $schedule->duration_weeks_text }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex flex-col space-y-1">
                                    @if($schedule->is_active)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            <i class="bi bi-check-circle mr-1"></i>Hoạt động
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            <i class="bi bi-pause-circle mr-1"></i>Tạm dừng
                                        </span>
                                    @endif

                                    @if($schedule->is_currently_active)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            <i class="bi bi-clock mr-1"></i>Đang diễn ra
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex space-x-2">
                                    @if(auth()->check() && auth()->user()->can('training-schedules.show'))
                                        <a href="{{ route('training-schedules.show', $schedule) }}"
                                            class="text-blue-600 hover:text-blue-900" title="Xem chi tiết">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    @endif

                                    @if(auth()->check() && auth()->user()->can('training-schedules.edit') && \App\Support\TrainingDept::canManageScheduleSkeleton())
                                        <a href="{{ route('training-schedules.edit', $schedule) }}"
                                            class="text-indigo-600 hover:text-indigo-900" title="Chỉnh sửa">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form action="{{ route('training-schedules.toggle-status', $schedule) }}" method="POST"
                                            class="inline" data-confirm='Bạn có chắc muốn thay đổi trạng thái?'>
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                class="{{ $schedule->is_active ? 'text-orange-600 hover:text-orange-900' : 'text-green-600 hover:text-green-900' }}"
                                                title="{{ $schedule->is_active ? 'Tạm dừng' : 'Kích hoạt' }}">
                                                <i class="bi bi-{{ $schedule->is_active ? 'pause' : 'play' }}-circle"></i>
                                            </button>
                                        </form>
                                    @endif
                                    @if(auth()->check() && auth()->user()->can('training-schedules.delete'))
                                        <form action="{{ route('training-schedules.destroy', $schedule) }}" method="POST"
                                            class="inline" data-confirm='Bạn có chắc muốn xóa lịch đào tạo này?'>
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900" title="Xóa">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <div class="flex flex-col items-center">
                                    <i class="bi bi-calendar-x text-4xl text-gray-300 mb-4"></i>
                                    <p class="text-lg font-medium">Không có lịch đào tạo nào</p>
                                    <p class="text-sm">Hãy tạo lịch đào tạo đầu tiên</p>
                                    @if(auth()->user()?->can('training-schedules.create') && \App\Support\TrainingDept::canManageScheduleSkeleton())
                                    <a href="{{ route('training-schedules.create') }}"
                                        class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                                        Tạo mới
                                    </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination (đã paginate ở controller — không gọi simplePaginate() lần nữa) --}}
        @if($schedules->hasPages())
            <div class="bg-gray-50 px-4 py-3 border-t border-gray-200">
                <div class="flex justify-center">
                    {{ $schedules->appends(request()->query())->links() }}
                </div>
            </div>
        @endif
    </div>
@include('partials.live-search')
@endsection

@push('scripts')
<script>
(function () {
    const classesUrl = @json(route('training-schedules.api.classes'));
    const currentClassId = @json((string) request('class_id', ''));

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

    function setClassOptions(classes, selected) {
        const classSelect = document.getElementById('filter_class_id');
        if (!classSelect) return;
        const items = [{ value: '', text: 'Tất cả lớp' }].concat(
            (classes || []).map(function (cls) {
                return { value: String(cls.id), text: cls.name };
            })
        );
        if (typeof window.setTomSelectOptions === 'function') {
            window.setTomSelectOptions(classSelect, items, {
                selected: selected || '',
                enabled: true,
            });
            return;
        }
        classSelect.innerHTML = '';
        items.forEach(function (item) {
            const opt = document.createElement('option');
            opt.value = item.value;
            opt.textContent = item.text;
            if (selected && String(item.value) === String(selected)) opt.selected = true;
            classSelect.appendChild(opt);
        });
    }

    function updateClassOptions(specializationId) {
        const url = specializationId
            ? classesUrl + '?specialization_id=' + encodeURIComponent(specializationId)
            : classesUrl;
        fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.json(); })
            .then(function (classes) {
                // Khi đổi ngành, không giữ class cũ nếu không thuộc ngành
                const keep = currentClassId && (classes || []).some(function (c) {
                    return String(c.id) === String(currentClassId);
                }) ? currentClassId : '';
                setClassOptions(classes, keep);
            })
            .catch(function (err) { console.error('Error fetching classes:', err); });
    }

    function boot() {
        const form = document.getElementById('filter-form');
        const specializationSelect = document.getElementById('filter_specialization_id');
        if (!form || form.dataset.filterBound === '1') return;
        form.dataset.filterBound = '1';

        if (typeof window.initTomSelects === 'function') {
            window.initTomSelects(form);
        }

        // Đổi ngành → nạp lại danh sách lớp ngay (không reload trang).
        bindChange(specializationSelect, function () {
            updateClassOptions(getVal(specializationSelect));
        });

        // Các điều kiện còn lại là điều kiện cuối chuỗi → tự lọc luôn,
        // không bắt người dùng bấm "Lọc" thêm một nhịp nữa.
        ['filter_class_id', 'filter_semester', 'filter_academic_year', 'filter_is_active']
            .forEach(function (id) {
                bindChange(document.getElementById(id), function () {
                    const page = form.querySelector('[name="page"]');
                    if (page) page.remove();
                    if (typeof form.requestSubmit === 'function') {
                        form.requestSubmit();
                    } else {
                        form.submit();
                    }
                });
            });
    }

    document.addEventListener('DOMContentLoaded', boot);
    document.addEventListener('turbo:load', boot);
    if (document.readyState !== 'loading') boot();
})();
</script>
@endpush
