@extends('layouts.admin')

@section('title', 'Thời khóa biểu tổng hợp')
@section('page-title', 'Thời khóa biểu tổng hợp')

@include('partials.chart-js')

@push('styles')
<style>
    /* Filter fields: no layout jump animation (Tom Select control height) */
    .filter-field .ui-select-field {
        min-width: 0;
    }
    #optionalFilters .ts-wrapper,
    #tabContentExport .ts-wrapper {
        width: 100%;
    }
    #optionalFilters .ts-control,
    #tabContentExport .ts-control {
        min-height: 2.5rem;
    }
    /* Multi: chip list cuộn trong ô, dropdown fixed bám control (reposition khi scroll) */
    #tabContentExport .ts-wrapper.multi .ts-control {
        max-height: 8.5rem;
        overflow-y: auto;
        align-items: flex-start !important;
    }
    #tabContentExport .ui-select-field {
        min-width: 0;
    }

    /* Fallback tường minh để nút PDF không mất nền khi trình duyệt/prod còn
       giữ bundle Tailwind cũ chưa có các utility rose/red mới. */
    #submitExportLhlPdf {
        color: #fff !important;
        background-color: #dc2626 !important;
        background-image: linear-gradient(135deg, #e11d48 0%, #dc2626 100%) !important;
        border: 1px solid rgba(190, 18, 60, 0.45);
    }
    #submitExportLhlPdf:not(:disabled):hover {
        background-color: #b91c1c !important;
        background-image: linear-gradient(135deg, #be123c 0%, #b91c1c 100%) !important;
        box-shadow: 0 10px 22px -10px rgba(190, 18, 60, 0.75), 0 0 0 3px rgba(244, 63, 94, 0.14);
        transform: translateY(-1px);
    }
    #submitExportLhlPdf:disabled {
        opacity: 0.62 !important;
        color: rgba(255, 255, 255, 0.88) !important;
        background-color: #e58b96 !important;
        background-image: linear-gradient(135deg, #e98a9f 0%, #e17e7e 100%) !important;
    }

    /* Smooth transition for view mode buttons */
    .view-mode-btn div {
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }

    /* Loading animation */
    @keyframes spin {
        to { transform: rotate(360deg); }
    }

    /* Focus glow for non-date inputs and selects */
    select:focus,
    input:not([type="date"]):focus {
        transform: none;
        box-shadow: 0 0 0 3px rgba(78, 161, 255, 0.22), 0 0 12px rgba(78, 161, 255, 0.18);
    }

    /* Gradient animation for header */
    @keyframes gradientShift {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }

    /* Panel tab Chi tiết / Xuất: bo góc + glow (overflow-hidden clip header/body) */
    #tabContentDetail .detail-panel,
    #tabContentExport .export-panel {
        border-radius: 0.75rem;
        overflow: hidden;
    }
    #tabContentDetail .detail-panel.hover-glow:hover,
    #tabContentExport .export-panel.hover-glow:hover {
        border-radius: 0.75rem;
    }

    /* Accordion biểu mẫu xuất — thu gọn mặc định */
    .export-acc {
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
        background: #fff;
        overflow: hidden;
        transition: box-shadow 0.2s ease, border-color 0.2s ease;
    }
    .export-acc.is-open {
        border-color: rgba(78, 161, 255, 0.45);
        box-shadow: 0 0 0 1px rgba(78, 161, 255, 0.12), 0 10px 24px -16px rgba(78, 161, 255, 0.35);
    }
    .export-acc-btn {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding: 0.85rem 1rem;
        text-align: left;
        background: linear-gradient(180deg, #f8fafc 0%, #fff 100%);
        border: 0;
        cursor: pointer;
        transition: background 0.15s ease;
    }
    .export-acc-btn:hover {
        background: #f0f7ff;
    }
    .export-acc.is-open .export-acc-btn {
        background: linear-gradient(180deg, #eef6ff 0%, #f8fbff 100%);
        border-bottom: 1px solid #e2e8f0;
    }
    .export-acc-btn .export-acc-chevron {
        transition: transform 0.2s ease;
        color: #64748b;
        flex-shrink: 0;
    }
    .export-acc.is-open .export-acc-btn .export-acc-chevron {
        transform: rotate(180deg);
        color: #358fee;
    }
    .export-acc-body {
        display: none;
        padding: 1rem;
        background: #fff;
    }
    .export-acc.is-open .export-acc-body {
        display: block;
        animation: exportAccIn 0.18s ease;
    }
    @keyframes exportAccIn {
        from { opacity: 0; transform: translateY(-4px); }
        to { opacity: 1; transform: none; }
    }

    .animate-gradient {
        background-size: 200% 200%;
        animation: gradientShift 3s ease infinite;
    }
</style>
@endpush

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ', 'url' => route('dashboard')],
    ['title' => 'Lịch đào tạo', 'url' => route('training-schedules.index')],
    ['title' => 'Thời khóa biểu tổng hợp']
]" />

                    {{-- Tabs: Chi tiết / Tổng quan / Xuất lịch học --}}
                    <div class="mb-4">
                        <div class="flex space-x-2">
                            <button id="tabOverview" type="button"
                                class="tab-btn px-4 py-2 rounded-t-lg font-semibold text-gray-600 bg-gray-100 border-b-2 border-transparent focus:outline-none"
                                onclick="showTab('overview')">
                                <i class="bi bi-bar-chart mr-1"></i> Tổng quan
                            </button>
                            <button id="tabDetail" type="button"
                                class="tab-btn px-4 py-2 rounded-t-lg font-semibold text-blue-700 bg-white border-b-2 border-blue-600 focus:outline-none"
                                onclick="showTab('detail')">
                                <i class="bi bi-grid-3x3-gap mr-1"></i> Chi tiết
                            </button>
                            
                            <button id="tabExport" type="button"
                                class="tab-btn px-4 py-2 rounded-t-lg font-semibold text-gray-600 bg-gray-100 border-b-2 border-transparent focus:outline-none"
                                onclick="showTab('export')">
                                <i class="bi bi-file-earmark-excel mr-1"></i> Xuất lịch học
                            </button>
                        </div>
                    </div>

                    {{-- Tab content --}}
                    <div id="tabContentDetail">
                        {{-- Filters and Summary Section --}}
                        <div class="mt-6 mb-6">
                            {{-- Filter Controls --}}
                            <div class="detail-panel hover-glow bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl shadow-lg border border-blue-100 mb-6 overflow-hidden">
                                {{-- Header with gradient --}}
                                <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-xl font-bold text-white flex items-center">
                                            <i class="bi bi-funnel-fill mr-3 text-blue-100"></i>
                                            Bộ Lọc Hiển Thị
                                        </h4>
                                        <div class="flex items-center space-x-2">
                                            <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-white text-xs font-medium">
                                                <i class="bi bi-info-circle mr-1"></i>
                                                Chọn bộ lọc phù hợp
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                {{-- Filter Body --}}
                                <div class="p-6 bg-white rounded-b-xl">
                                    {{-- View Mode Section --}}
                                    <div class="mb-6 pb-6 border-b border-gray-200">
                                        <label class="block text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wide">
                                            <i class="bi bi-eye text-blue-600 mr-2"></i>
                                            Chế độ xem
                                        </label>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                            <button type="button" class="view-mode-btn relative group" data-view="training_schedule" onclick="selectViewMode('training_schedule')">
                                                <div class="flex items-center justify-center p-4 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all duration-200 cursor-pointer">
                                                    <div class="text-center">
                                                        <i class="bi bi-calendar-check text-2xl text-gray-500 group-hover:text-blue-600 mb-2"></i>
                                                        <div class="text-sm font-medium text-gray-700 group-hover:text-blue-700">Lịch đào tạo</div>
                                                    </div>
                                                </div>
                                            </button>
                                            <button type="button" class="view-mode-btn relative group" data-view="spec_day" onclick="selectViewMode('spec_day')">
                                                <div class="flex items-center justify-center p-4 border-2 border-gray-200 rounded-lg hover:border-blue-500 hover:bg-blue-50 transition-all duration-200 cursor-pointer">
                                                    <div class="text-center">
                                                        <i class="bi bi-calendar-day text-2xl text-gray-500 group-hover:text-blue-600 mb-2"></i>
                                                        <div class="text-sm font-medium text-gray-700 group-hover:text-blue-700">Ngành đào tạo + Ngày</div>
                                                    </div>
                                                </div>
                                            </button>
                                            
                                        </div>
                                        <input type="hidden" id="viewType" value="training_schedule">
                                    </div>

                                    {{-- Dynamic Filters Section --}}
                                    <div id="optionalFilters" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        {{-- Filter Specialization --}}
                                        <div id="filterSpec" style="display:none;" class="filter-field">
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                <i class="bi bi-mortarboard text-indigo-600 mr-1"></i>
                                                Ngành đào tạo
                                            </label>
                                            <div class="ui-select-field">
                                                <select id="specFilter" name="specialization_id"
                                                    data-placeholder="Tất cả ngành đào tạo"
                                                    data-searchable="1"
                                                    class="w-full">
                                                    <option value="">Tất cả ngành đào tạo</option>
                                                    @foreach($specializations ?? [] as $spec)
                                                        <option value="{{ $spec->id }}">{{ $spec->selection_label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Filter Class --}}
                                        <div id="filterClass" style="display:none;" class="filter-field">
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                <i class="bi bi-people text-green-600 mr-1"></i>
                                                Lớp
                                            </label>
                                            <div class="ui-select-field">
                                                <select id="classFilter" name="class_id"
                                                    data-placeholder="Chọn lớp"
                                                    data-searchable="1"
                                                    class="w-full">
                                                    <option value="">Chọn lớp</option>
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Filter Date --}}
                                        <div id="filterDate" style="display:none;" class="filter-field">
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                <i class="bi bi-calendar-event text-blue-600 mr-1"></i>
                                                Ngày
                                            </label>
                                            <div class="date-input-field">
                                                <div class="date-input-control">
                                                    <input type="date" id="dateFilter" name="date"
                                                        class="date-input date-input--ready w-full"
                                                        value="{{ $currentDate ?? '' }}" />
                                                    <i class="bi bi-calendar3 date-input-icon" aria-hidden="true"></i>
                                                </div>
                                            </div>
                                        </div>

                                        {{-- Filter Date Range --}}
                                        <div id="filterDateRange" style="display:none;" class="filter-field md:col-span-2 lg:col-span-3">
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                <i class="bi bi-calendar2-range text-purple-600 mr-1"></i>
                                                Khoảng ngày (Tùy chọn)
                                            </label>
                                            <div class="date-range-field">
                                                <div>
                                                    <label class="block text-xs text-gray-500 mb-1">Từ ngày</label>
                                                    <div class="date-input-field">
                                                        <div class="date-input-control">
                                                            <input type="date" id="dateFrom" name="date_from" class="date-input date-input--ready w-full" />
                                                            <i class="bi bi-calendar3 date-input-icon" aria-hidden="true"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                                <span class="date-range-field__sep self-end pb-2">–</span>
                                                <div>
                                                    <label class="block text-xs text-gray-500 mb-1">Đến ngày</label>
                                                    <div class="date-input-field">
                                                        <div class="date-input-control">
                                                            <input type="date" id="dateTo" name="date_to" class="date-input date-input--ready w-full" />
                                                            <i class="bi bi-calendar3 date-input-icon" aria-hidden="true"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <p class="text-xs text-gray-500 mt-1 italic">* Nếu không chọn, mặc định hiển thị tuần hiện tại.</p>
                                        </div>

                                        {{-- Filter Training Schedule --}}
                                        <div id="filterTrainingSchedule" style="display:none;" class="filter-field md:col-span-2 lg:col-span-3">
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                <i class="bi bi-calendar-check text-orange-600 mr-1"></i>
                                                Lịch đào tạo
                                            </label>
                                            <div class="ui-select-field">
                                                <select id="trainingScheduleFilter" name="training_schedule_id"
                                                    data-placeholder="Chọn lịch đào tạo"
                                                    data-searchable="1"
                                                    class="w-full">
                                                    <option value="">Chọn lịch đào tạo</option>
                                                    @foreach($trainingSchedules ?? [] as $ts)
                                                        @php
                                                            $startDate = isset($ts->start_date) && $ts->start_date ? $ts->start_date->format('Y-m-d') : '';
                                                            $endDate = isset($ts->end_date) && $ts->end_date ? $ts->end_date->format('Y-m-d') : '';
                                                        @endphp
                                                        <option value="{{ $ts->id }}"
                                                            data-start="{{ $startDate }}"
                                                            data-end="{{ $endDate }}">
                                                            {{ $ts->name }} ({{ $ts->code ?? '' }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        {{-- Additional filters for Training Schedule mode --}}
                                        <div id="filterTrainingScheduleSpecialization" style="display:none;" class="filter-field">
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                <i class="bi bi-mortarboard text-indigo-600 mr-1"></i>
                                                Ngành đào tạo
                                            </label>
                                            <div class="ui-select-field">
                                                <select id="tsSpecFilter" name="ts_specialization_id"
                                                    data-placeholder="Tất cả ngành đào tạo"
                                                    data-searchable="1"
                                                    class="w-full">
                                                    <option value="">Tất cả ngành đào tạo</option>
                                                    @foreach($specializations ?? [] as $spec)
                                                        <option value="{{ $spec->id }}">{{ $spec->selection_label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div id="filterTrainingScheduleClass" style="display:none;" class="filter-field">
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                <i class="bi bi-people text-green-600 mr-1"></i>
                                                Lớp
                                            </label>
                                            <div class="ui-select-field">
                                                <select id="tsClassFilter" name="ts_class_id"
                                                    data-placeholder="Tất cả lớp"
                                                    data-searchable="1"
                                                    class="w-full">
                                                    <option value="">Tất cả lớp</option>
                                                    @foreach($classes ?? [] as $class)
                                                        @if(is_object($class))
                                                            <option value="{{ $class->id }}" data-specialization-id="{{ $class->specialization_id ?? '' }}">{{ $class->name }}</option>
                                                        @endif
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div id="filterTrainingScheduleSemester" style="display:none;" class="filter-field">
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                <i class="bi bi-clock-history text-purple-600 mr-1"></i>
                                                Thời gian
                                            </label>
                                            <div class="ui-select-field">
                                                <select id="tsSemesterFilter" name="ts_semester"
                                                    data-placeholder="Tất cả học kỳ"
                                                    data-searchable="0"
                                                    class="w-full">
                                                    <option value="">Tất cả học kỳ</option>
                                                    @foreach($filterOptions['semesters'] ?? [] as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div id="filterTrainingScheduleAcademicYear" style="display:none;" class="filter-field">
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                <i class="bi bi-calendar3 text-cyan-600 mr-1"></i>
                                                Năm học
                                            </label>
                                            <div class="ui-select-field">
                                                <select id="tsAcademicYearFilter" name="ts_academic_year"
                                                    data-placeholder="Tất cả năm học"
                                                    data-searchable="0"
                                                    class="w-full">
                                                    <option value="">Tất cả năm học</option>
                                                    @foreach($filterOptions['academic_years'] ?? [] as $value => $label)
                                                        <option value="{{ $value }}">{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>

                                        <div id="filterTrainingScheduleStatus" style="display:none;" class="filter-field">
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                <i class="bi bi-toggle-on text-emerald-600 mr-1"></i>
                                                Trạng thái
                                            </label>
                                            <div class="ui-select-field">
                                                <select id="tsStatusFilter" name="ts_status"
                                                    data-placeholder="Tất cả"
                                                    data-searchable="0"
                                                    class="w-full">
                                                    <option value="">Tất cả</option>
                                                    <option value="active">Hoạt động</option>
                                                    <option value="inactive">Tạm dừng</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Action Buttons --}}
                                    <div class="mt-6 pt-6 border-t border-gray-200 flex flex-wrap gap-3">
                                        <button id="filterBtn"
                                            class="hover-glow flex-1 md:flex-none inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 text-white font-semibold rounded-lg shadow-md hover:from-blue-700 hover:to-indigo-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200">
                                            <i class="bi bi-funnel-fill mr-2"></i>
                                            Áp dụng lọc
                                        </button>
                                        <button id="resetFilters"
                                            class="hover-glow flex-1 md:flex-none inline-flex items-center justify-center px-6 py-3 bg-gradient-to-r from-gray-500 to-gray-600 text-white font-semibold rounded-lg shadow-md hover:from-gray-600 hover:to-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-all duration-200">
                                            <i class="bi bi-arrow-clockwise mr-2"></i>
                                            Đặt lại
                                        </button>
                                        <div class="hidden md:flex flex-1 items-center justify-end text-sm text-gray-500">
                                            <i class="bi bi-info-circle mr-2"></i>
                                            Chọn bộ lọc phù hợp để xem thời khóa biểu
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Content Container --}}
                        <div class="mt-6">
                            {{-- Kết quả lịch học động --}}
                            <div id="calendarResult">
                                <div
                                    class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
                                    <i class="bi bi-calendar3 text-6xl text-gray-400 mb-4"></i>
                                    <p class="text-gray-500 text-lg">Vui lòng chọn bộ lọc và nhấn "Lọc" để xem thời khóa
                                        biểu</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div id="tabContentOverview" style="display:none;">
                        @include('training-schedule::calendar-overview', ['calendarOverview' => $calendarOverview ?? []])
                    </div>

                    {{-- Export Tab Content --}}
                    <div id="tabContentExport" style="display:none;">
                        <div class="export-panel hover-glow bg-gradient-to-r from-blue-50 to-indigo-50 rounded-xl shadow-lg border border-blue-100 overflow-hidden">
                            {{-- Header --}}
                            <div class="bg-gradient-to-r from-blue-600 to-indigo-600 px-6 py-4">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <h4 class="text-xl font-bold text-white flex items-center">
                                            <i class="bi bi-file-earmark-excel-fill mr-3 text-blue-100"></i>
                                            Xuất Lịch Học Ra Excel
                                        </h4>
                                        <p class="text-blue-100 text-sm mt-1">Chọn các lịch đào tạo và khoảng thời gian để xuất file Excel</p>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <span class="px-3 py-1 bg-white/20 backdrop-blur-sm rounded-full text-white text-xs font-medium">
                                            <i class="bi bi-download mr-1"></i>
                                            Tối đa 50 lịch
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Filter Form --}}
                            <div class="p-6 bg-white rounded-b-xl">
                                <form id="exportForm">
                                    @csrf

                                    {{-- Step 1-2: Basic Filters Section --}}
                                    <div class="mb-6 pb-6 border-b border-gray-200">
                                        <label class="block text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wide">
                                            <i class="bi bi-funnel text-blue-600 mr-2"></i>
                                            Bước 1-2: Lọc theo ngành và lớp
                                        </label>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            {{-- Ngành đào tạo — list hết vào khung (mặc định chọn tất cả) --}}
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                    <i class="bi bi-mortarboard text-indigo-600 mr-1"></i>
                                                    Ngành đào tạo
                                                    <span class="text-xs font-normal text-gray-500">(<span id="specCount">0</span>)</span>
                                                </label>
                                                <div class="ui-select-field">
                                                    <select id="exportSpecializations" name="specializations[]" multiple
                                                            data-placeholder="Chọn ngành đào tạo..."
                                                            data-searchable="1"
                                                            class="w-full">
                                                        @foreach($specializations ?? [] as $spec)
                                                            <option value="{{ $spec->id }}" selected>{{ $spec->selection_label }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            {{-- Lớp học — list hết vào khung (mặc định chọn tất cả theo ngành) --}}
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                    <i class="bi bi-people text-green-600 mr-1"></i>
                                                    Lớp học
                                                    <span class="text-xs font-normal text-gray-500">(<span id="classCount">0</span>)</span>
                                                </label>
                                                <div class="ui-select-field">
                                                    <select id="exportClasses" name="classes[]" multiple
                                                            data-placeholder="Chọn lớp học..."
                                                            data-searchable="1"
                                                            class="w-full">
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Step 3-5: Additional Filters Section --}}
                                    <div class="mb-6 pb-6 border-b border-gray-200">
                                        <label class="block text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wide">
                                            <i class="bi bi-sliders text-blue-600 mr-2"></i>
                                            Bước 3-5: Lọc theo năm học, học kỳ và trạng thái
                                        </label>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            {{-- Năm học --}}
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                    <i class="bi bi-calendar-range text-blue-600 mr-1"></i>
                                                    Năm học
                                                </label>
                                                <div class="ui-select-field">
                                                    <select id="exportAcademicYear" name="academic_year"
                                                            data-placeholder="Tất cả năm học"
                                                            data-searchable="0"
                                                            class="w-full">
                                                        <option value="">Tất cả</option>
                                                        @foreach($filterOptions['academic_years'] ?? [] as $year)
                                                            <option value="{{ $year }}">{{ $year }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                            </div>

                                            {{-- Học kỳ --}}
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                    <i class="bi bi-calendar3 text-purple-600 mr-1"></i>
                                                    Học kỳ
                                                </label>
                                                <div class="ui-select-field">
                                                    <select id="exportSemester" name="semester"
                                                            data-placeholder="Tất cả học kỳ"
                                                            data-searchable="0"
                                                            class="w-full">
                                                        <option value="">Tất cả</option>
                                                        <option value="semester_1">Học kỳ 1</option>
                                                        <option value="semester_2">Học kỳ 2</option>
                                                        <option value="summer">Học kỳ hè</option>
                                                    </select>
                                                </div>
                                            </div>

                                            {{-- Trạng thái --}}
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                    <i class="bi bi-toggle-on text-orange-600 mr-1"></i>
                                                    Trạng thái
                                                </label>
                                                <div class="flex gap-3 mt-3">
                                                    <label class="flex items-center cursor-pointer">
                                                        <input type="radio" name="status" value="1" class="mr-2 text-blue-600 focus:ring-blue-600" checked>
                                                        <span class="text-sm font-medium text-gray-700">Đang hoạt động</span>
                                                    </label>
                                                    <label class="flex items-center cursor-pointer">
                                                        <input type="radio" name="status" value="0" class="mr-2 text-blue-600 focus:ring-blue-600">
                                                        <span class="text-sm font-medium text-gray-700">Ngưng hoạt động</span>
                                                    </label>
                                                    <label class="flex items-center cursor-pointer">
                                                        <input type="radio" name="status" value="" class="mr-2 text-blue-600 focus:ring-blue-600">
                                                        <span class="text-sm font-medium text-gray-700">Tất cả</span>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Step 6: Training Schedules Selection --}}
                                    <div class="mb-6 pb-6 border-b border-gray-200">
                                        <label class="block text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wide">
                                            <i class="bi bi-calendar-check text-blue-600 mr-2"></i>
                                            Bước 6: Chọn lịch đào tạo
                                        </label>
                                        <div>
                                            <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                <i class="bi bi-calendar-check text-red-600 mr-1"></i>
                                                Lịch đào tạo <span class="text-xs text-gray-500">(tối đa 50)</span>
                                            </label>
                                            <div class="ui-select-field">
                                                <select id="exportTrainingSchedules" name="training_schedule_ids[]" multiple
                                                        data-placeholder="Chọn lịch đào tạo (tối đa 50)"
                                                        data-searchable="1"
                                                        data-max-items="50"
                                                        class="w-full">
                                                </select>
                                            </div>
                                            <div class="flex items-center justify-between mt-2">
                                                <p class="text-xs text-gray-500">
                                                    <i class="bi bi-info-circle mr-1"></i>
                                                    Chọn tối thiểu 1 lịch, tối đa 50 lịch
                                                </p>
                                                <p class="text-xs font-semibold text-blue-600">
                                                    <span id="scheduleCount">0</span>/50 lịch
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Step 7: Date Range --}}
                                    <div class="mb-6">
                                        <label class="block text-sm font-semibold text-gray-700 mb-3 uppercase tracking-wide">
                                            <i class="bi bi-calendar-range text-blue-600 mr-2"></i>
                                            Bước 7: Chọn khoảng thời gian
                                        </label>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                    <i class="bi bi-calendar-date text-teal-600 mr-1"></i>
                                                    Từ ngày
                                                </label>
                                                <div class="date-input-field">
                                                    <div class="date-input-control">
                                                        <input type="date" id="exportStartDate" name="start_date" class="date-input date-input--ready w-full">
                                                        <i class="bi bi-calendar3 date-input-icon" aria-hidden="true"></i>
                                                    </div>
                                                </div>
                                            </div>

                                            <div>
                                                <label class="block text-sm font-semibold text-gray-700 mb-2">
                                                    <i class="bi bi-calendar-date-fill text-teal-600 mr-1"></i>
                                                    Đến ngày
                                                </label>
                                                <div class="date-input-field">
                                                    <div class="date-input-control">
                                                        <input type="date" id="exportEndDate" name="end_date" class="date-input date-input--ready w-full">
                                                        <i class="bi bi-calendar3 date-input-icon" aria-hidden="true"></i>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-2">
                                            <i class="bi bi-lightbulb mr-1"></i>
                                            Khoảng ngày sẽ tự động điền dựa trên lịch đã chọn, bạn có thể chỉnh sửa nếu cần
                                        </p>
                                    </div>

                                    {{-- Biểu mẫu: accordion thu gọn — mở khi cần xuất --}}
                                    <div class="mt-6 pt-6 border-t border-gray-200 space-y-3" id="exportFormAccordions">
                                        <div class="export-acc" data-export-acc="word-lich-hoc">
                                            <button type="button" class="export-acc-btn" data-export-acc-btn aria-expanded="false">
                                                <span class="min-w-0">
                                                    <span class="flex items-center gap-2 text-sm font-bold text-slate-800">
                                                        <i class="bi bi-file-earmark-word text-blue-700"></i>
                                                        Biểu mẫu lịch học / KHHL Khoa (Word)
                                                    </span>
                                                    <span class="block text-[11px] font-normal text-slate-500 mt-0.5">
                                                        Header / footer / preview — dùng khi xuất lịch học hoặc KHHL Khoa
                                                    </span>
                                                </span>
                                                <i class="bi bi-chevron-down export-acc-chevron text-lg"></i>
                                            </button>
                                            <div class="export-acc-body" data-export-acc-body>
                                                @include('partials.word-export-template-editor', [
                                                    'editorId' => 'calendarWordExportEditor',
                                                    'defaultTitle' => 'LỊCH HỌC',
                                                ])
                                            </div>
                                        </div>

                                        <div class="export-acc" data-export-acc="lhl-hk2">
                                            <button type="button" class="export-acc-btn" data-export-acc-btn aria-expanded="false">
                                                <span class="min-w-0">
                                                    <span class="flex items-center gap-2 text-sm font-bold text-slate-800">
                                                        <i class="bi bi-calendar3-range text-teal-700"></i>
                                                        Biểu mẫu Lịch huấn luyện (chuẩn HK2 25-26)
                                                    </span>
                                                    <span class="block text-[11px] font-normal text-slate-500 mt-0.5">
                                                        Preview LHL, chữ ký số, meta lớp — dùng khi xuất LHL Word/Excel
                                                    </span>
                                                </span>
                                                <i class="bi bi-chevron-down export-acc-chevron text-lg"></i>
                                            </button>
                                            <div class="export-acc-body" data-export-acc-body>
                                                @include('partials.lhl-export-editor', [
                                                    'editorId' => 'lhlExportEditor',
                                                ])
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Action Buttons --}}
                                    <div class="mt-8 pt-6 border-t border-gray-200">
                                        <div class="flex flex-col gap-3">
                                            <div class="flex flex-col sm:flex-row flex-wrap gap-3 justify-end">
                                                <button type="button" id="resetExportForm"
                                                    class="hover-glow group px-6 py-3 bg-gradient-to-r from-gray-500 to-gray-600 hover:from-gray-600 hover:to-gray-700 text-white font-semibold rounded-lg transition-all duration-200 shadow-md">
                                                    <i class="bi bi-arrow-clockwise mr-2"></i>
                                                    Đặt lại bộ lọc
                                                </button>
                                                <span class="inline-flex" title="Đang phát triển thêm — chức năng chưa được mở sử dụng">
                                                    <button type="button" id="submitExport"
                                                        data-development-lock="1" aria-disabled="true"
                                                        class="group px-6 py-3 bg-gradient-to-r from-green-600 to-emerald-600 text-white font-semibold rounded-lg opacity-50 cursor-not-allowed shadow-none"
                                                        disabled>
                                                        <i class="bi bi-file-earmark-word mr-2"></i>
                                                        Xuất lịch học (Word)
                                                    </button>
                                                </span>
                                                <span class="inline-flex" title="{{ !empty($canManageSkeleton) ? 'LHL Word — tự chọn mẫu theo nhóm tiết' : 'Chỉ Phòng Đào tạo hoặc quản trị hệ thống được xuất LHL Word' }}">
                                                    <button type="button" id="submitExportLhlWord"
                                                        data-can-export="{{ !empty($canManageSkeleton) ? '1' : '0' }}"
                                                        class="hover-glow group px-6 py-3 bg-gradient-to-r from-blue-600 to-indigo-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold rounded-lg transition-all duration-200 shadow-md disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none"
                                                        disabled>
                                                        <i class="bi bi-file-earmark-word mr-2"></i>
                                                        Xuất LHL (Word)
                                                    </button>
                                                </span>
                                                <span class="inline-flex" title="{{ !empty($canManageSkeleton) ? 'Xuất PDF từ đúng mẫu LHL Word đang áp dụng' : 'Chỉ Phòng Đào tạo hoặc quản trị hệ thống được xuất LHL PDF' }}">
                                                    <button type="button" id="submitExportLhlPdf"
                                                        data-can-export="{{ !empty($canManageSkeleton) ? '1' : '0' }}"
                                                        class="hover-glow group px-6 py-3 bg-gradient-to-r from-rose-600 to-red-600 hover:from-rose-700 hover:to-red-700 text-white font-semibold rounded-lg transition-all duration-200 shadow-md disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none"
                                                        disabled>
                                                        <i class="bi bi-file-earmark-pdf mr-2"></i>
                                                        Xuất LHL (PDF)
                                                    </button>
                                                </span>
                                                <span class="inline-flex" title="Đang phát triển thêm — chức năng chưa được mở sử dụng">
                                                    <button type="button" id="submitExportLhl"
                                                        data-development-lock="1" aria-disabled="true"
                                                        class="group px-6 py-3 bg-gradient-to-r from-cyan-500 to-blue-600 text-white font-semibold rounded-lg opacity-50 cursor-not-allowed shadow-none"
                                                        disabled>
                                                        <i class="bi bi-file-earmark-excel mr-2"></i>
                                                        Xuất LHL (Excel)
                                                    </button>
                                                </span>
                                            </div>
                                            <div class="flex flex-col sm:flex-row flex-wrap gap-3 justify-end items-end border-t border-dashed border-gray-200 pt-4">
                                                <div class="ui-select-field w-full sm:w-64">
                                                    <label class="block text-xs font-semibold text-gray-600 mb-1">Lớp (xuất KHHL Khoa)</label>
                                                    <select id="exportFacultyClass" data-placeholder="Chọn lớp..." data-searchable="1" class="w-full">
                                                        <option value="">Chọn lớp...</option>
                                                        @foreach($classes ?? [] as $class)
                                                            @if(is_object($class))
                                                                <option value="{{ $class->id }}">{{ $class->name }}@if(!empty($class->code)) ({{ $class->code }})@endif</option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <button type="button" id="submitExportFaculty"
                                                    class="hover-glow group px-6 py-3 bg-gradient-to-r from-purple-500 to-indigo-600 text-white font-semibold rounded-lg transition-all duration-200 shadow-md disabled:opacity-50 disabled:cursor-not-allowed disabled:shadow-none"
                                                    disabled
                                                    title="Chọn lớp để xuất kế hoạch HL (Khoa)">
                                                    <i class="bi bi-file-earmark-word mr-2"></i>
                                                    Xuất kế hoạch HL (Word)
                                                </button>
                                            </div>
                                        </div>
                                        <p class="text-xs text-gray-500 text-right mt-3">
                                            <i class="bi bi-info-circle mr-1"></i>
                                            Bấm mở <strong>biểu mẫu</strong> phía trên khi cần chỉnh — thu gọn mặc định cho gọn màn hình.
                                        </p>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    // State local to this page load — IIFE tránh Turbo redeclare let/const
    var exportDropdownsReady = false;
    var exportHandlersBound = false;
    var exportClassFetchSeq = 0;
    var exportScheduleFetchSeq = 0;
    var exportLoadingClasses = false;
    var detailHandlersBound = false;
    var detailTsFetchSeq = 0;
    var detailClassFetchSeq = 0;
    var calendarOverviewChartsReady = false;
    var calendarOverviewChartInstances = {};

        window.showTab = function showTab(tab) {
            // Hide all tabs
            document.getElementById('tabContentDetail').style.display = 'none';
            document.getElementById('tabContentOverview').style.display = 'none';
            document.getElementById('tabContentExport').style.display = 'none';

            // Reset all tab buttons
            ['tabDetail', 'tabOverview', 'tabExport'].forEach(tabId => {
                const tabBtn = document.getElementById(tabId);
                tabBtn.classList.remove('text-blue-700', 'bg-white', 'border-blue-600');
                tabBtn.classList.add('text-gray-600', 'bg-gray-100', 'border-transparent');
            });

            // Show selected tab
            if (tab === 'detail') {
                document.getElementById('tabContentDetail').style.display = '';
                document.getElementById('tabDetail').classList.add('text-blue-700', 'bg-white', 'border-blue-600');
                document.getElementById('tabDetail').classList.remove('text-gray-600', 'bg-gray-100', 'border-transparent');
                // Init Tom Select sau khi tab hiện (tránh dropdown dính / không mở)
                requestAnimationFrame(function () {
                    syncDetailTomSelects();
                });
            } else if (tab === 'overview') {
                document.getElementById('tabContentOverview').style.display = '';
                document.getElementById('tabOverview').classList.add('text-blue-700', 'bg-white', 'border-blue-600');
                document.getElementById('tabOverview').classList.remove('text-gray-600', 'bg-gray-100', 'border-transparent');
                if (typeof window.closeAllTomSelects === 'function') window.closeAllTomSelects();
                if (typeof window.initCalendarOverviewCharts === 'function') {
                    window.initCalendarOverviewCharts();
                }
            } else if (tab === 'export') {
                document.getElementById('tabContentExport').style.display = '';
                document.getElementById('tabExport').classList.add('text-blue-700', 'bg-white', 'border-blue-600');
                document.getElementById('tabExport').classList.remove('text-gray-600', 'bg-gray-100', 'border-transparent');
                if (typeof window.closeAllTomSelects === 'function') window.closeAllTomSelects();
                // rAF: đợi tab display xong rồi mới init Tom Select (tránh skip vì ẩn)
                requestAnimationFrame(function () {
                    initializeExportDropdowns();
                });
            }
        };

        // ========== Detail filters — Tom Select (đồng bộ admin) ==========
        var DETAIL_SELECT_IDS = [
            'specFilter', 'classFilter', 'trainingScheduleFilter',
            'tsSpecFilter', 'tsClassFilter', 'tsSemesterFilter',
            'tsAcademicYearFilter', 'tsStatusFilter'
        ];
        var allClasses = @json($classes ?? []);
        var allClassesForTS = @json($classes ?? []);

        function detailVal(id) {
            if (typeof window.getSelectValue === 'function') {
                return String(window.getSelectValue(id) || '');
            }
            var el = document.getElementById(id);
            return el ? String(el.value || '') : '';
        }

        function setDetailVal(id, value) {
            if (typeof window.setTomValues === 'function') {
                window.setTomValues(id, value || '', true);
                return;
            }
            var el = document.getElementById(id);
            if (el) el.value = value || '';
        }

        /** Đóng dropdown + destroy select đang ẩn; init lại select đang hiện */
        function syncDetailTomSelects() {
            if (typeof window.closeAllTomSelects === 'function') {
                window.closeAllTomSelects();
            }

            DETAIL_SELECT_IDS.forEach(function (id) {
                var el = document.getElementById(id);
                if (!el) return;
                var field = el.closest('.filter-field');
                var hidden = field && (field.style.display === 'none' || getComputedStyle(field).display === 'none');
                if (hidden) {
                    if (typeof window.destroyTomSelect === 'function') {
                        window.destroyTomSelect(el);
                    }
                }
            });

            var root = document.getElementById('optionalFilters') || document.getElementById('tabContentDetail');
            if (typeof window.initTomSelects === 'function' && root) {
                window.initTomSelects(root);
            }
        }

        window.selectViewMode = function selectViewMode(mode) {
            document.getElementById('viewType').value = mode;

            document.querySelectorAll('.view-mode-btn').forEach(function (btn) {
                var btnView = btn.getAttribute('data-view');
                var innerDiv = btn.querySelector('div');
                if (btnView === mode) {
                    innerDiv.classList.remove('border-gray-200', 'hover:border-blue-500', 'hover:bg-blue-50');
                    innerDiv.classList.add('border-blue-600', 'bg-blue-50', 'shadow-md');
                    btn.querySelector('i').classList.remove('text-gray-500', 'group-hover:text-blue-600');
                    btn.querySelector('i').classList.add('text-blue-600');
                    btn.querySelectorAll('.text-gray-700').forEach(function (el) {
                        el.classList.remove('text-gray-700', 'group-hover:text-blue-700');
                        el.classList.add('text-blue-700');
                    });
                } else {
                    innerDiv.classList.add('border-gray-200', 'hover:border-blue-500', 'hover:bg-blue-50');
                    innerDiv.classList.remove('border-blue-600', 'bg-blue-50', 'shadow-md');
                    btn.querySelector('i').classList.add('text-gray-500', 'group-hover:text-blue-600');
                    btn.querySelector('i').classList.remove('text-blue-600');
                    btn.querySelectorAll('.text-blue-700').forEach(function (el) {
                        el.classList.add('text-gray-700', 'group-hover:text-blue-700');
                        el.classList.remove('text-blue-700');
                    });
                }
            });

            updateFilterFields();
        };

        function updateFilterFields() {
            var viewType = document.getElementById('viewType').value;

            [
                'filterClass', 'filterSpec', 'filterDate', 'filterDateRange',
                'filterTrainingSchedule', 'filterTrainingScheduleSpecialization',
                'filterTrainingScheduleClass', 'filterTrainingScheduleSemester',
                'filterTrainingScheduleAcademicYear', 'filterTrainingScheduleStatus'
            ].forEach(function (id) {
                var el = document.getElementById(id);
                if (el) el.style.display = 'none';
            });

            if (viewType === 'spec_day') {
                document.getElementById('filterSpec').style.display = '';
                document.getElementById('filterClass').style.display = '';
                document.getElementById('filterDate').style.display = '';
            } else if (viewType === 'training_schedule') {
                document.getElementById('filterTrainingSchedule').style.display = '';
                document.getElementById('filterTrainingScheduleSpecialization').style.display = '';
                document.getElementById('filterTrainingScheduleClass').style.display = '';
                document.getElementById('filterTrainingScheduleSemester').style.display = '';
                document.getElementById('filterTrainingScheduleAcademicYear').style.display = '';
                document.getElementById('filterTrainingScheduleStatus').style.display = '';
                document.getElementById('filterDateRange').style.display = '';
            }

            // Đợi layout hiện xong rồi mới init Tom Select (tránh nhảy / dính dropdown)
            requestAnimationFrame(function () {
                syncDetailTomSelects();
            });
        }

        function loadClassesBySpecialization(specId) {
            var seq = ++detailClassFetchSeq;
            var items = [{ value: '', text: 'Đang tải...' }];
            if (typeof window.setTomSelectOptions === 'function') {
                window.setTomSelectOptions('classFilter', items, { selected: '', enabled: false });
            }

            var url = '{{ route("training-schedules.api.classes", [], false) }}';
            if (specId) url += '?specialization_id=' + encodeURIComponent(specId);

            fetch(url)
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (seq !== detailClassFetchSeq) return;
                    var next = [{ value: '', text: 'Tất cả lớp' }];
                    (data || []).forEach(function (cls) {
                        next.push({
                            value: cls.code || cls.id,
                            text: cls.name + (cls.code ? ' (' + cls.code + ')' : '')
                        });
                    });
                    window.setTomSelectOptions('classFilter', next, { selected: '', enabled: true });
                })
                .catch(function (err) {
                    if (seq !== detailClassFetchSeq) return;
                    console.error('Error loading classes:', err);
                    window.setTomSelectOptions('classFilter', [{ value: '', text: 'Lỗi tải dữ liệu' }], {
                        selected: '',
                        enabled: true
                    });
                });
        }

        function loadFilteredTrainingSchedules() {
            var params = new URLSearchParams();
            var specId = detailVal('tsSpecFilter');
            var classId = detailVal('tsClassFilter');
            var semester = detailVal('tsSemesterFilter');
            var academicYear = detailVal('tsAcademicYearFilter');
            var status = detailVal('tsStatusFilter');

            if (specId) params.append('specialization_id', specId);
            if (classId) params.append('class_id', classId);
            if (semester) params.append('semester', semester);
            if (academicYear) params.append('academic_year', academicYear);
            if (status) params.append('is_active', status);

            var currentValue = detailVal('trainingScheduleFilter');
            var seq = ++detailTsFetchSeq;

            if (typeof window.setTomSelectOptions === 'function') {
                window.setTomSelectOptions('trainingScheduleFilter', [{ value: '', text: 'Đang tải...' }], {
                    selected: '',
                    enabled: false
                });
            }

            fetch('/training-schedules/api/filtered?' + params.toString())
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    if (seq !== detailTsFetchSeq) return;
                    var items = [{ value: '', text: 'Chọn lịch đào tạo' }];
                    var keep = '';
                    (data || []).forEach(function (ts) {
                        var id = String(ts.id);
                        items.push({
                            value: id,
                            text: ts.name + ' (' + (ts.code || '') + ')',
                            start: ts.start_date || '',
                            end: ts.end_date || ''
                        });
                        if (id === String(currentValue)) keep = id;
                    });
                    window.setTomSelectOptions('trainingScheduleFilter', items, {
                        selected: keep,
                        enabled: true
                    });
                })
                .catch(function (err) {
                    if (seq !== detailTsFetchSeq) return;
                    console.error('Error loading training schedules:', err);
                    window.setTomSelectOptions('trainingScheduleFilter', [{ value: '', text: 'Lỗi tải dữ liệu' }], {
                        selected: '',
                        enabled: true
                    });
                });
        }

        function filterClassesBySpecialization() {
            var specId = detailVal('tsSpecFilter');
            var currentValue = detailVal('tsClassFilter');
            var items = [{ value: '', text: 'Tất cả lớp' }];
            var keep = '';

            (allClassesForTS || []).forEach(function (cls) {
                if (!cls || typeof cls !== 'object') return;
                if (!specId || String(cls.specialization_id) === String(specId)) {
                    var id = String(cls.id);
                    items.push({
                        value: id,
                        text: cls.name,
                        specialization_id: cls.specialization_id || ''
                    });
                    if (id === String(currentValue)) keep = id;
                }
            });

            if (typeof window.setTomSelectOptions === 'function') {
                window.setTomSelectOptions('tsClassFilter', items, { selected: keep, enabled: true });
            }
        }

        function bindDetailFilterHandlers() {
            if (detailHandlersBound) return;
            detailHandlersBound = true;

            // Bind native <select> change — Tom Select vẫn fire event trên element gốc,
            // nên handler không mất khi destroy/rebuild options.
            function onSelectChange(id, handler) {
                var el = document.getElementById(id);
                if (el) el.addEventListener('change', handler);
            }

            onSelectChange('specFilter', function () {
                if (document.getElementById('viewType')?.value === 'spec_day') {
                    loadClassesBySpecialization(detailVal('specFilter'));
                }
            });

            onSelectChange('tsSpecFilter', function () {
                filterClassesBySpecialization();
                loadFilteredTrainingSchedules();
            });
            onSelectChange('tsClassFilter', loadFilteredTrainingSchedules);
            onSelectChange('tsSemesterFilter', loadFilteredTrainingSchedules);
            onSelectChange('tsAcademicYearFilter', loadFilteredTrainingSchedules);
            onSelectChange('tsStatusFilter', loadFilteredTrainingSchedules);

            document.getElementById('viewType')?.addEventListener('change', updateFilterFields);
        }

        // Xử lý nút Lọc
        var filterBtn = document.getElementById('filterBtn');
        if (filterBtn && filterBtn.dataset.bound !== '1') {
            filterBtn.dataset.bound = '1';
            filterBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (typeof window.closeAllTomSelects === 'function') window.closeAllTomSelects();

                var viewType = document.getElementById('viewType').value;
                var params = {};

                if (viewType === 'spec_day') {
                    params.specialization_id = detailVal('specFilter');
                    params.class_id = detailVal('classFilter');
                    params.date = document.getElementById('dateFilter').value;

                    if (!params.date) {
                        Notify.warning('Vui lòng chọn ngày!');
                        return;
                    }
                } else if (viewType === 'training_schedule') {
                    params.training_schedule_id = detailVal('trainingScheduleFilter');
                    params.start_date = document.getElementById('dateFrom').value;
                    params.end_date = document.getElementById('dateTo').value;

                    if (!params.training_schedule_id) {
                        Notify.warning('Vui lòng chọn lịch đào tạo!');
                        return;
                    }
                }

                document.getElementById('calendarResult').innerHTML = `
                <div class="bg-white rounded-lg p-8 text-center">
                    <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
                    <p class="mt-4 text-gray-600">Đang tải dữ liệu...</p>
                </div>
            `;

                fetch('/training-schedules/schedule-details?' + new URLSearchParams(params), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        if (data.html) {
                            document.getElementById('calendarResult').innerHTML = data.html;
                        } else {
                            document.getElementById('calendarResult').innerHTML = `
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                            <i class="bi bi-exclamation-triangle text-4xl text-yellow-600 mb-3"></i>
                            <p class="text-yellow-800">Không có dữ liệu phù hợp với bộ lọc đã chọn.</p>
                        </div>
                    `;
                        }
                    })
                    .catch(function (err) {
                        console.error('Error:', err);
                        document.getElementById('calendarResult').innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">
                        <i class="bi bi-x-circle text-4xl text-red-600 mb-3"></i>
                        <p class="text-red-800">Lỗi tải dữ liệu! Vui lòng thử lại.</p>
                    </div>
                `;
                    });
            });
        }

        // Xử lý nút Reset
        var resetBtn = document.getElementById('resetFilters');
        if (resetBtn && resetBtn.dataset.bound !== '1') {
            resetBtn.dataset.bound = '1';
            resetBtn.addEventListener('click', function () {
                if (typeof window.closeAllTomSelects === 'function') window.closeAllTomSelects();

                document.getElementById('viewType').value = 'training_schedule';
                setDetailVal('classFilter', '');
                setDetailVal('specFilter', '');
                setDetailVal('trainingScheduleFilter', '');
                setDetailVal('tsSpecFilter', '');
                setDetailVal('tsClassFilter', '');
                setDetailVal('tsSemesterFilter', '');
                setDetailVal('tsAcademicYearFilter', '');
                setDetailVal('tsStatusFilter', '');

                ['dateFilter', 'dateFrom', 'dateTo'].forEach(function (id) {
                    if (typeof window.setDateInputValue === 'function') {
                        window.setDateInputValue(id, '', true);
                    } else {
                        var el = document.getElementById(id);
                        if (el) {
                            el.value = '';
                            el.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    }
                });

                document.getElementById('calendarResult').innerHTML = `
                <div class="bg-gray-50 border-2 border-dashed border-gray-300 rounded-lg p-8 text-center">
                    <i class="bi bi-calendar3 text-6xl text-gray-400 mb-4"></i>
                    <p class="text-gray-500 text-lg">Vui lòng chọn bộ lọc và nhấn "Lọc" để xem thời khóa biểu</p>
                </div>
            `;
                window.selectViewMode('training_schedule');
            });
        }

        // ============================================
        // EXPORT TAB FUNCTIONS (Tom Select — đồng bộ admin)
        // List hết ngành/lớp vào khung (chip). Đổi ngành → load + chọn hết lớp realtime.
        // ============================================

        function exportVal(id) {
            if (typeof window.getTomValues === 'function') {
                return window.getTomValues(id).map(String).filter(Boolean);
            }
            const el = document.getElementById(id);
            if (!el) return [];
            if (el.multiple) return Array.from(el.selectedOptions).map(o => String(o.value)).filter(Boolean);
            return el.value ? [String(el.value)] : [];
        }

        function exportSingleVal(id) {
            const vals = exportVal(id);
            return vals[0] || '';
        }

        function exportAllOptionIds(id) {
            const el = document.getElementById(id);
            if (!el) return [];
            return Array.from(el.options).map(o => String(o.value)).filter(Boolean);
        }

        function setExportValues(id, values, silent) {
            var list = Array.isArray(values) ? values.map(String).filter(Boolean) : (values ? [String(values)] : []);
            var el = document.getElementById(id);
            if (!el) return;

            // Đảm bảo option selected trên DOM trước (Tom Select sync từ đây)
            if (el.multiple) {
                Array.from(el.options).forEach(function (o) {
                    o.selected = list.includes(String(o.value));
                });
            } else {
                el.value = list[0] || '';
            }

            if (typeof window.setTomValues === 'function' && el.tomselect) {
                window.setTomValues(id, list, silent !== false);
                return;
            }
            if (el.tomselect) {
                try {
                    el.tomselect.setValue(el.multiple ? list : (list[0] || ''), silent !== false);
                } catch (e) { /* ignore */ }
                return;
            }
            if (!silent) el.dispatchEvent(new Event('change', { bubbles: true }));
        }

        function rebuildExportOptions(id, items, selected) {
            var list = items || [];
            var selectedList = selected === undefined ? [] : (Array.isArray(selected) ? selected : [selected]);
            var el = document.getElementById(id);
            if (!el) return;

            // Luôn full rebuild — ổn định hơn in-place (tránh dropdown fixed lệch / dính khi scroll)
            if (typeof window.closeAllTomSelects === 'function') {
                window.closeAllTomSelects();
            }
            delete el.dataset.exportCloseBound;

            if (typeof window.setTomSelectOptions === 'function') {
                window.setTomSelectOptions(id, list, { selected: selectedList, enabled: true });
                return;
            }

            if (typeof window.destroyTomSelect === 'function') {
                window.destroyTomSelect(el);
            }
            el.innerHTML = '';
            list.forEach(function (item) {
                var opt = document.createElement('option');
                opt.value = String(item.value);
                opt.textContent = item.text != null ? String(item.text) : String(item.value);
                if (item.start !== undefined) opt.dataset.start = String(item.start ?? '');
                if (item.end !== undefined) opt.dataset.end = String(item.end ?? '');
                el.appendChild(opt);
            });
            if (typeof window.initTomSelects === 'function') {
                window.initTomSelects(el.closest('.ui-select-field') || el.parentElement);
            }
            setExportValues(id, selectedList, true);
        }

        function updateExportCount(elId, countElId) {
            const el = document.getElementById(countElId);
            if (!el) return;
            el.textContent = String(exportVal(elId).length);
        }

        function initializeExportDropdowns() {
            var exportRoot = document.getElementById('tabContentExport');
            if (!exportRoot) return;
            // style.display === '' khi hiện; 'none' khi ẩn
            if (exportRoot.style.display === 'none') return;

            if (typeof window.closeAllTomSelects === 'function') {
                window.closeAllTomSelects();
            }

            // Init Tom Select cho select chưa được gắn (tab ẩn lúc boot bị skip)
            if (typeof window.initTomSelects === 'function') {
                window.initTomSelects(exportRoot);
            }

            bindExportHandlersOnce();

            // Luôn đồng bộ: chọn hết ngành + cập nhật (N) trên label
            var allSpecIds = exportAllOptionIds('exportSpecializations');
            if (allSpecIds.length) {
                setExportValues('exportSpecializations', allSpecIds, true);
            }
            // Fallback: nếu Tom chưa nhận multi-value, đếm option selected trên DOM
            var specCount = exportVal('exportSpecializations').length;
            if (!specCount && allSpecIds.length) {
                specCount = allSpecIds.length;
            }
            var specCountEl = document.getElementById('specCount');
            if (specCountEl) specCountEl.textContent = String(specCount);

            // Lần đầu / mất lớp → load lại lớp + lịch
            var classCount = exportVal('exportClasses').length;
            if (!exportDropdownsReady || classCount === 0) {
                exportDropdownsReady = true;
                loadExportClasses();
            } else {
                updateExportCount('exportClasses', 'classCount');
                var sch = document.getElementById('scheduleCount');
                if (sch) sch.textContent = String(exportVal('exportTrainingSchedules').length);
            }
        }

        function loadExportClasses() {
            const specializationIds = exportVal('exportSpecializations');
            updateExportCount('exportSpecializations', 'specCount');

            const params = new URLSearchParams();
            specializationIds.forEach(id => params.append('specialization_id[]', id));
            // Không có ngành nào được chọn → API trả tất cả lớp

            const seq = ++exportClassFetchSeq;
            exportLoadingClasses = true;
            const url = '/training-schedules/classes' + (params.toString() ? '?' + params.toString() : '');

            fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(res => res.json())
                .then(classes => {
                    if (seq !== exportClassFetchSeq) return;

                    const items = (classes || []).map(cls => ({
                        value: String(cls.id),
                        text: cls.name,
                    }));
                    const allClassIds = items.map(i => i.value);

                    // List hết lớp vào khung (chọn tất cả)
                    rebuildExportOptions('exportClasses', items, allClassIds);
                    updateExportCount('exportClasses', 'classCount');
                    exportLoadingClasses = false;
                    loadExportSchedules();
                })
                .catch(() => {
                    if (seq !== exportClassFetchSeq) return;
                    rebuildExportOptions('exportClasses', [], []);
                    updateExportCount('exportClasses', 'classCount');
                    exportLoadingClasses = false;
                    loadExportSchedules();
                });
        }

        function loadExportSchedules() {
            if (exportLoadingClasses) return;

            const specializationIds = exportVal('exportSpecializations');
            const classIds = exportVal('exportClasses');
            updateExportCount('exportClasses', 'classCount');

            const academicYear = exportSingleVal('exportAcademicYear');
            const semester = exportSingleVal('exportSemester');
            const status = document.querySelector('input[name="status"]:checked')?.value ?? '';

            const params = new URLSearchParams();
            specializationIds.forEach(id => params.append('specialization_id[]', id));
            classIds.forEach(id => params.append('class_id[]', id));
            if (academicYear) params.append('academic_year', academicYear);
            if (semester) params.append('semester', semester);
            params.append('is_active', status);

            const seq = ++exportScheduleFetchSeq;
            const url = '/training-schedules/api/filtered' + (params.toString() ? '?' + params.toString() : '');

            fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(res => res.json())
                .then(schedules => {
                    if (seq !== exportScheduleFetchSeq) return;

                    const items = (schedules || []).map(ts => ({
                        value: String(ts.id),
                        text: `${ts.name} (${ts.start_date} → ${ts.end_date})`,
                        start: ts.start_date,
                        end: ts.end_date,
                    }));
                    rebuildExportOptions('exportTrainingSchedules', items, []);
                    document.getElementById('scheduleCount').textContent = '0';
                    document.getElementById('submitExport').disabled = true;
                    setLhlExportButtonsDisabled(true);
                    window.__exportScheduleMeta = {};
                    items.forEach(i => {
                        window.__exportScheduleMeta[i.value] = { start: i.start, end: i.end };
                    });
                })
                .catch(() => {
                    if (seq !== exportScheduleFetchSeq) return;
                    rebuildExportOptions('exportTrainingSchedules', [], []);
                    document.getElementById('scheduleCount').textContent = '0';
                    document.getElementById('submitExport').disabled = true;
                    setLhlExportButtonsDisabled(true);
                });
        }

        function onExportSchedulesChanged() {
            const selectedIds = exportVal('exportTrainingSchedules');
            document.getElementById('scheduleCount').textContent = selectedIds.length;
            var noSel = selectedIds.length === 0;
            document.getElementById('submitExport').disabled = true;
            setLhlExportButtonsDisabled(noSel);
            if (!selectedIds.length) return;

            const meta = window.__exportScheduleMeta || {};
            const dates = selectedIds
                .map(id => meta[id])
                .filter(Boolean)
                .map(d => ({ start: new Date(d.start), end: new Date(d.end) }));

            if (!dates.length) return;
            const earliestStart = dates.reduce((min, d) => d.start < min ? d.start : min, dates[0].start);
            const latestEnd = dates.reduce((max, d) => d.end > max ? d.end : max, dates[0].end);
            const toYmd = (d) => d.toISOString().split('T')[0];
            var startYmd = toYmd(earliestStart);
            var endYmd = toYmd(latestEnd);
            if (typeof window.setDateInputValue === 'function') {
                window.setDateInputValue('exportStartDate', startYmd, true);
                window.setDateInputValue('exportEndDate', endYmd, true);
            } else {
                document.getElementById('exportStartDate').value = startYmd;
                document.getElementById('exportEndDate').value = endYmd;
                document.getElementById('exportStartDate').dispatchEvent(new Event('change', { bubbles: true }));
                document.getElementById('exportEndDate').dispatchEvent(new Event('change', { bubbles: true }));
            }
        }

        function bindExportHandlersOnce() {
            if (exportHandlersBound) return;
            exportHandlersBound = true;

            // Bind native change — Tom Select fire trên <select> gốc, sống sót qua rebuild
            function onExportSelectChange(id, handler) {
                var el = document.getElementById(id);
                if (el && el.dataset.exportChangeBound !== '1') {
                    el.dataset.exportChangeBound = '1';
                    el.addEventListener('change', handler);
                }
            }

            onExportSelectChange('exportSpecializations', loadExportClasses);
            onExportSelectChange('exportClasses', loadExportSchedules);
            onExportSelectChange('exportAcademicYear', loadExportSchedules);
            onExportSelectChange('exportSemester', loadExportSchedules);
            onExportSelectChange('exportTrainingSchedules', onExportSchedulesChanged);
            onExportSelectChange('exportFacultyClass', syncExportFacultyButton);
            document.querySelectorAll('input[name="status"]').forEach(function (r) {
                if (r.dataset.exportChangeBound === '1') return;
                r.dataset.exportChangeBound = '1';
                r.addEventListener('change', loadExportSchedules);
            });
            syncExportFacultyButton();
        }

        function syncExportFacultyButton() {
            var btn = document.getElementById('submitExportFaculty');
            if (!btn) return;
            var classId = '';
            if (typeof window.getSelectValue === 'function') {
                classId = window.getSelectValue('exportFacultyClass') || '';
            } else {
                classId = document.getElementById('exportFacultyClass')?.value || '';
            }
            btn.disabled = !String(classId).trim();
        }

        function destroyCalendarOverviewCharts() {
            Object.values(calendarOverviewChartInstances).forEach(function (chart) {
                if (chart && typeof chart.destroy === 'function') chart.destroy();
            });
            Object.keys(calendarOverviewChartInstances).forEach(function (key) {
                delete calendarOverviewChartInstances[key];
            });
        }

        window.initCalendarOverviewCharts = function initCalendarOverviewCharts() {
            if (typeof Chart === 'undefined' || !window.calendarOverviewChartData) return;

            destroyCalendarOverviewCharts();

            var brand = '#4ea1ff';
            var palette = ['#4ea1ff', '#6eb5ff', '#358fee', '#8ec5ff', '#1d4ed8', '#a8d4ff'];
            var data = window.calendarOverviewChartData;

            var classStatus = document.getElementById('overviewClassStatusChart');
            if (classStatus) {
                calendarOverviewChartInstances.classStatus = new Chart(classStatus, {
                    type: 'doughnut',
                    data: {
                        labels: data.class_status?.labels || [],
                        datasets: [{
                            data: data.class_status?.values || [],
                            backgroundColor: palette,
                            borderWidth: 2,
                            borderColor: '#faf8f4',
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } },
                    },
                });
            }

            var sessionsWeek = document.getElementById('overviewSessionsWeekChart');
            if (sessionsWeek) {
                calendarOverviewChartInstances.sessionsWeek = new Chart(sessionsWeek, {
                    type: 'bar',
                    data: {
                        labels: data.sessions_by_day?.labels || [],
                        datasets: [{
                            label: 'Số buổi học',
                            data: data.sessions_by_day?.values || [],
                            backgroundColor: brand,
                            borderRadius: 8,
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } },
                        plugins: { legend: { display: false } },
                    },
                });
            }

            var coursesSpec = document.getElementById('overviewCoursesSpecChart');
            if (coursesSpec) {
                calendarOverviewChartInstances.coursesSpec = new Chart(coursesSpec, {
                    type: 'bar',
                    data: {
                        labels: data.courses_by_specialization?.labels || [],
                        datasets: [{
                            label: 'Số khóa',
                            data: data.courses_by_specialization?.values || [],
                            backgroundColor: '#358fee',
                            borderRadius: 8,
                        }],
                    },
                    options: {
                        indexAxis: 'y',
                        responsive: true,
                        maintainAspectRatio: false,
                        scales: { x: { beginAtZero: true, ticks: { precision: 0 } } },
                        plugins: { legend: { display: false } },
                    },
                });
            }

            var lessonType = document.getElementById('overviewLessonTypeChart');
            if (lessonType) {
                calendarOverviewChartInstances.lessonType = new Chart(lessonType, {
                    type: 'polarArea',
                    data: {
                        labels: data.lesson_types_week?.labels || [],
                        datasets: [{
                            data: data.lesson_types_week?.values || [],
                            backgroundColor: ['#4ea1ff', '#6eb5ff', '#358fee', '#8ec5ff'],
                        }],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: { legend: { position: 'bottom' } },
                    },
                });
            }

            calendarOverviewChartsReady = true;
        };

        // Bind nút export 1 lần / page instance (dataset tránh double-bind nếu script chạy 2 lần)
        var resetExportBtn = document.getElementById('resetExportForm');
        if (resetExportBtn && resetExportBtn.dataset.bound !== '1') {
            resetExportBtn.dataset.bound = '1';
            resetExportBtn.addEventListener('click', function () {
                if (typeof window.closeAllTomSelects === 'function') window.closeAllTomSelects();
                var allSpecIds = exportAllOptionIds('exportSpecializations');
                setExportValues('exportSpecializations', allSpecIds, true);
                setExportValues('exportAcademicYear', '', true);
                setExportValues('exportSemester', '', true);
                var statusActive = document.querySelector('input[name="status"][value="1"]');
                if (statusActive) statusActive.checked = true;
                if (typeof window.setDateInputValue === 'function') {
                    window.setDateInputValue('exportStartDate', '', true);
                    window.setDateInputValue('exportEndDate', '', true);
                } else {
                    document.getElementById('exportStartDate').value = '';
                    document.getElementById('exportEndDate').value = '';
                }
                document.getElementById('submitExport').disabled = true;
                setLhlExportButtonsDisabled(true);
                document.getElementById('scheduleCount').textContent = '0';
                updateExportCount('exportSpecializations', 'specCount');
                loadExportClasses();
            });
        }

        function setLhlExportButtonsDisabled(disabled) {
            var wordButton = document.getElementById('submitExportLhlWord');
            if (wordButton) {
                wordButton.disabled = !!disabled || wordButton.dataset.canExport !== '1';
            }
            var pdfButton = document.getElementById('submitExportLhlPdf');
            if (pdfButton) {
                pdfButton.disabled = !!disabled || pdfButton.dataset.canExport !== '1';
            }
            var excelButton = document.getElementById('submitExportLhl');
            if (excelButton) excelButton.disabled = true;
        }

        function postExportForm(action, selectedIds, startDate, endDate, options) {
            options = options || {};
            var form = document.createElement('form');
            form.method = 'POST';
            form.action = action;

            var csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = document.querySelector('meta[name="csrf-token"]')?.content || '';
            form.appendChild(csrfInput);

            selectedIds.forEach(function (id) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'training_schedule_ids[]';
                input.value = id;
                form.appendChild(input);
            });

            var startInput = document.createElement('input');
            startInput.type = 'hidden';
            startInput.name = 'start_date';
            startInput.value = startDate;
            form.appendChild(startInput);

            var endInput = document.createElement('input');
            endInput.type = 'hidden';
            endInput.name = 'end_date';
            endInput.value = endDate;
            form.appendChild(endInput);

            if (options.format) {
                var formatInput = document.createElement('input');
                formatInput.type = 'hidden';
                formatInput.name = 'format';
                formatInput.value = options.format;
                form.appendChild(formatInput);
            }

            var editorIds = options.editorIds || ['calendarWordExportEditor'];
            var meta = {};
            editorIds.forEach(function (eid) {
                var editor = document.getElementById(eid);
                if (editor && typeof editor.getMeta === 'function') {
                    Object.assign(meta, editor.getMeta());
                }
            });
            Object.keys(meta).forEach(function (k) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = k;
                input.value = meta[k] || '';
                form.appendChild(input);
            });

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);
        }

        function validateScheduleExportSelection() {
            var selectedIds = exportVal('exportTrainingSchedules');
            var startDate = document.getElementById('exportStartDate').value;
            var endDate = document.getElementById('exportEndDate').value;

            if (selectedIds.length === 0) {
                Notify.warning('Vui lòng chọn ít nhất 1 lịch đào tạo');
                return null;
            }
            if (!startDate || !endDate) {
                Notify.warning('Vui lòng chọn khoảng thời gian');
                return null;
            }
            if (new Date(startDate) > new Date(endDate)) {
                Notify.warning('Ngày bắt đầu phải nhỏ hơn hoặc bằng ngày kết thúc');
                return null;
            }
            return { selectedIds: selectedIds, startDate: startDate, endDate: endDate };
        }

        // Accordion biểu mẫu xuất (thu gọn mặc định)
        function setExportAccOpen(acc, open) {
            if (!acc) return;
            var btn = acc.querySelector('[data-export-acc-btn]');
            acc.classList.toggle('is-open', !!open);
            if (btn) btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        }
        function openExportAcc(name) {
            document.querySelectorAll('#exportFormAccordions .export-acc').forEach(function (acc) {
                setExportAccOpen(acc, acc.getAttribute('data-export-acc') === name);
            });
            var target = document.querySelector('#exportFormAccordions .export-acc[data-export-acc="' + name + '"]');
            if (target) {
                try { target.scrollIntoView({ behavior: 'smooth', block: 'nearest' }); } catch (e) {}
            }
        }
        (function bindExportAccordions() {
            var root = document.getElementById('exportFormAccordions');
            if (!root || root.dataset.bound === '1') return;
            root.dataset.bound = '1';
            root.querySelectorAll('.export-acc').forEach(function (acc) {
                setExportAccOpen(acc, false);
                var btn = acc.querySelector('[data-export-acc-btn]');
                if (!btn) return;
                btn.addEventListener('click', function () {
                    var willOpen = !acc.classList.contains('is-open');
                    // Chỉ mở 1 cái tại một thời điểm cho gọn
                    root.querySelectorAll('.export-acc').forEach(function (other) {
                        setExportAccOpen(other, willOpen && other === acc);
                    });
                });
            });
        })();

        var submitExportBtn = document.getElementById('submitExport');
        if (submitExportBtn && submitExportBtn.dataset.bound !== '1') {
            submitExportBtn.dataset.bound = '1';
            submitExportBtn.addEventListener('click', function () {
                Notify.info('Xuất lịch học Word đang được phát triển thêm.');
            });
        }

        var submitExportLhlBtn = document.getElementById('submitExportLhl');
        if (submitExportLhlBtn && submitExportLhlBtn.dataset.bound !== '1') {
            submitExportLhlBtn.dataset.bound = '1';
            submitExportLhlBtn.addEventListener('click', function () {
                Notify.info('Xuất LHL Excel đang được phát triển thêm.');
            });
        }

        var submitExportLhlWordBtn = document.getElementById('submitExportLhlWord');
        if (submitExportLhlWordBtn && submitExportLhlWordBtn.dataset.bound !== '1') {
            submitExportLhlWordBtn.dataset.bound = '1';
            submitExportLhlWordBtn.addEventListener('click', function () {
                var sel = validateScheduleExportSelection();
                if (!sel) return;
                openExportAcc('lhl-hk2');
                postExportForm(
                    '{{ route("training-schedules.export-training-plan") }}',
                    sel.selectedIds,
                    sel.startDate,
                    sel.endDate,
                    { format: 'docx', editorIds: ['lhlExportEditor'] }
                );
            });
        }

        var submitExportLhlPdfBtn = document.getElementById('submitExportLhlPdf');
        if (submitExportLhlPdfBtn && submitExportLhlPdfBtn.dataset.bound !== '1') {
            submitExportLhlPdfBtn.dataset.bound = '1';
            submitExportLhlPdfBtn.addEventListener('click', function () {
                if (submitExportLhlPdfBtn.dataset.exporting === '1') {
                    Notify.info('Hệ thống đang tạo PDF, vui lòng chờ hoàn tất.');
                    return;
                }
                var sel = validateScheduleExportSelection();
                if (!sel) return;
                openExportAcc('lhl-hk2');
                var originalHtml = submitExportLhlPdfBtn.innerHTML;
                submitExportLhlPdfBtn.dataset.exporting = '1';
                submitExportLhlPdfBtn.disabled = true;
                submitExportLhlPdfBtn.innerHTML = '<i class="bi bi-arrow-repeat mr-2 animate-spin"></i>Đang tạo PDF...';
                postExportForm(
                    '{{ route("training-schedules.export-training-plan") }}',
                    sel.selectedIds,
                    sel.startDate,
                    sel.endDate,
                    { format: 'pdf', editorIds: ['lhlExportEditor'] }
                );
                window.setTimeout(function () {
                    submitExportLhlPdfBtn.dataset.exporting = '0';
                    submitExportLhlPdfBtn.innerHTML = originalHtml;
                    setLhlExportButtonsDisabled(false);
                }, 60000);
            });
        }

        var submitExportFacultyBtn = document.getElementById('submitExportFaculty');
        if (submitExportFacultyBtn && submitExportFacultyBtn.dataset.bound !== '1') {
            submitExportFacultyBtn.dataset.bound = '1';
            submitExportFacultyBtn.addEventListener('click', function () {
                openExportAcc('word-lich-hoc');
                var classId = '';
                if (typeof window.getSelectValue === 'function') {
                    classId = window.getSelectValue('exportFacultyClass') || '';
                } else {
                    classId = document.getElementById('exportFacultyClass')?.value || '';
                }
                if (!classId) {
                    Notify.warning('Vui lòng chọn lớp để xuất kế hoạch huấn luyện (Khoa)');
                    return;
                }
                var startDate = document.getElementById('exportStartDate').value;
                var endDate = document.getElementById('exportEndDate').value;

                var form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("training-schedules.export-faculty-plan") }}';

                var csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = document.querySelector('meta[name="csrf-token"]')?.content || '';
                form.appendChild(csrfInput);

                [['class_id', classId], ['start_date', startDate], ['end_date', endDate]].forEach(function (pair) {
                    if (!pair[1]) return;
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = pair[0];
                    input.value = pair[1];
                    form.appendChild(input);
                });

                var editor = document.getElementById('calendarWordExportEditor');
                var meta = (editor && typeof editor.getMeta === 'function') ? editor.getMeta() : {};
                Object.keys(meta).forEach(function (k) {
                    var input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = k;
                    input.value = meta[k] || '';
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
                document.body.removeChild(form);
            });
        }

        document.addEventListener('turbo:before-cache', destroyCalendarOverviewCharts);

        // Boot page
        function bootCalendarPage() {
            if (!document.getElementById('tabContentDetail')) return;
            bindDetailFilterHandlers();
            window.selectViewMode('training_schedule');
            window.showTab('detail');
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bootCalendarPage);
        } else {
            bootCalendarPage();
        }
})();
</script>
@endpush
