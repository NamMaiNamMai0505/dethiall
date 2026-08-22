@extends('layouts.lms-learner')

@section('title', 'Kê khai giờ chuẩn hằng năm')

@section('content')
@php
    $isLocked = (bool) ($declaration?->isLocked() ?? false);
    $selectedObjectType = old('object_type_id', $declaration->object_type_id ?? $instructor->object_type_id);
    $selectedPosition = old('position_id', $declaration->position_id ?? $instructor->position_id);
    $fromDate = old('declaration_from_date', $defaultFromDate);
    $toDate = old('declaration_to_date', $defaultToDate);
    $otherHours = old('other_teaching_hours', $declaration->other_teaching_hours ?? 0);
    $snapshot = array_merge([
        'total_hours' => (float) ($declaration->schedule_teaching_hours ?? 0),
        'total_periods' => (int) ($declaration->schedule_teaching_hours ?? 0),
        'rows' => [],
        'by_subject' => [],
        'conversion_hours' => (float) ($declaration->conversion_hours ?? 0),
        'research_hours' => (float) ($declaration->research_hours ?? 0),
    ], $scheduleSnapshot ?? []);
@endphp

<div class="max-w-5xl mx-auto space-y-6">
    <div class="lms-card rounded-2xl p-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-lg font-bold text-slate-900">Hồ sơ kê khai giờ chuẩn {{ mb_strtoupper($periodModeLabel) }} {{ $defaultPeriodLabel }}</h1>
        </div>
        <a href="{{ route('lms.teach.standard-hours.my-results.index', ['year' => $defaultYear]) }}"
           class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-medium transition-colors">
            <i class="bi bi-arrow-left"></i> Quay lại
        </a>
    </div>

    @if($isLocked)
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            <i class="bi bi-lock-fill mr-1"></i>
            Bảng giờ chuẩn {{ mb_strtolower($periodModeLabel) }} {{ $defaultPeriodLabel }} đã được cấp quản lý khóa. Bạn có thể xem nhưng không thể thay đổi.
        </div>
    @endif

    <form action="{{ route('lms.teach.standard-hours.my-results.store-declaration') }}" method="POST" data-turbo="false">
        @csrf

        <div class="lms-card rounded-2xl overflow-hidden mb-6">
            <div class="bg-gradient-to-r from-teal-600 to-teal-500 px-5 py-4 text-white">
                <h2 class="text-base font-semibold">Thông tin giảng viên kê khai</h2>
                <p class="mt-1 text-sm text-teal-50">Thông tin được lấy tự động từ tài khoản đang đăng nhập.</p>
            </div>
            <div class="grid grid-cols-1 gap-4 p-5 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Mã số giảng viên</span>
                    <div class="mt-1 text-lg font-bold text-teal-800">{{ $instructor->code }}</div>
                </div>
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Họ và tên</span>
                    <div class="mt-1 font-semibold text-slate-900">{{ $instructor->name }}</div>
                </div>
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Đơn vị</span>
                    <div class="mt-1 font-medium text-slate-900">{{ $instructor->unit?->name ?? 'Chưa phân đơn vị' }}</div>
                </div>
                <div>
                    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">{{ $periodModeLabel }} hồ sơ</span>
                    <div id="declaration-year-label" class="mt-1 font-semibold text-slate-900">{{ $defaultPeriodLabel }}</div>
                </div>
            </div>
        </div>

        <div class="lms-card rounded-2xl p-5 mb-6">
            <div class="mb-5 flex items-center gap-2">
                <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-teal-50 text-teal-700"><i class="bi bi-calendar-range"></i></span>
                <div>
                    <h2 class="font-semibold text-slate-900">Thời gian và định mức áp dụng</h2>
                    <p class="text-sm text-slate-500">Mỗi hồ sơ chỉ nằm trong một {{ mb_strtolower($periodModeLabel) }}.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">
                <x-form.date-input name="declaration_from_date" label="Từ ngày" :value="$fromDate" :min="$periodStartDate" :max="$periodEndDate" required />
                <x-form.date-input name="declaration_to_date" label="Đến ngày" :value="$toDate" :min="$periodStartDate" :max="$periodEndDate" required />

                <div>
                    <label class="mb-2 block font-medium text-slate-800" for="object_type_id">Đối tượng <span class="text-rose-500">*</span></label>
                    <select name="object_type_id" id="object_type_id" data-placeholder="Chọn đối tượng" data-searchable="1"
                            class="ui-select-field w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none" required>
                        <option value="">Chọn đối tượng</option>
                        @foreach($objectTypes as $objectType)
                            <option value="{{ $objectType->id }}"
                                    data-code="{{ $objectType->code }}"
                                    data-hours="{{ (float) $objectType->standard_hours }}"
                                    data-research-hours="{{ (float) $objectType->research_hours }}"
                                    {{ (string) $selectedObjectType === (string) $objectType->id ? 'selected' : '' }}>
                                {{ $objectType->code }} — {{ $objectType->name }} ({{ number_format((float) $objectType->standard_hours, 0) }} giờ)
                            </option>
                        @endforeach
                    </select>
                    @error('object_type_id')<span class="mt-1 block text-sm text-rose-600">{{ $message }}</span>@enderror
                </div>

                <div>
                    <label class="mb-2 block font-medium text-slate-800" for="position_id">Chức danh <span class="text-rose-500">*</span></label>
                    <select name="position_id" id="position_id" data-placeholder="Chọn chức danh" data-searchable="1"
                            class="ui-select-field w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none" required>
                        <option value="">Chọn chức danh</option>
                        @foreach($positions as $position)
                            <option value="{{ $position->id }}"
                                    data-ratio="{{ (float) $position->ratio_percent }}"
                                    data-min-classroom="{{ (float) $position->min_classroom_percent }}"
                                    {{ (string) $selectedPosition === (string) $position->id ? 'selected' : '' }}>
                                {{ $position->name }} — {{ number_format((float) $position->ratio_percent, 0) }}%
                            </option>
                        @endforeach
                    </select>
                    @error('position_id')<span class="mt-1 block text-sm text-rose-600">{{ $message }}</span>@enderror
                </div>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-3 rounded-xl border border-teal-100 bg-teal-50 p-4 md:grid-cols-4">
                <div>
                    <span class="text-xs uppercase tracking-wide text-slate-500">Định mức đối tượng</span>
                    <div class="mt-1 font-semibold text-slate-900"><span id="base-norm-hours">—</span> giờ</div>
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wide text-slate-500">Tỷ lệ chức danh</span>
                    <div class="mt-1 font-semibold text-slate-900"><span id="position-ratio">—</span>%</div>
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wide text-slate-500">Công thức áp dụng</span>
                    <div id="norm-formula" class="mt-1 font-medium text-slate-700">—</div>
                </div>
                <div>
                    <span class="text-xs uppercase tracking-wide text-teal-700">Số giờ chuẩn quy định</span>
                    <div class="mt-1 text-2xl font-bold text-teal-800"><span id="effective-norm-hours">—</span> giờ</div>
                </div>
            </div>
        </div>

        <div class="lms-card rounded-2xl p-5 mb-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h2 class="font-semibold text-slate-900"><i class="bi bi-calendar2-check mr-1 text-indigo-600"></i> Giờ giảng dạy từ Lịch huấn luyện</h2>
                    <p class="mt-1 text-sm text-slate-500">Chọn khoảng ngày tra cứu tự do, kể cả qua nhiều năm/năm học.</p>
                </div>
                <button type="button" id="retrieve-schedule-button"
                        class="px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium transition-colors"
                        {{ $isLocked ? 'disabled' : '' }}>
                    <i class="bi bi-arrow-repeat"></i>
                    <span>Truy xuất số giờ giảng từ lịch huấn luyện</span>
                </button>
            </div>

            <div class="mt-5 grid grid-cols-1 gap-4 rounded-xl border border-indigo-100 bg-indigo-50 p-4 md:grid-cols-2">
                <x-form.date-input name="schedule_filter_from_date" label="Lọc lịch từ ngày" :value="$fromDate" />
                <x-form.date-input name="schedule_filter_to_date" label="Lọc lịch đến ngày" :value="$toDate" />
            </div>

            <div id="retrieve-error" class="mt-4 hidden rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"></div>

            <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-4">
                <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4">
                    <p class="text-sm text-slate-600">Số tiết từ lịch</p>
                    <p class="mt-1 text-2xl font-bold text-indigo-700"><span id="schedule-hours">{{ number_format((float) $snapshot['total_hours'], 2, '.', '') }}</span></p>
                </div>
                <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-4">
                    <p class="text-sm text-slate-600">Giờ HĐCM quy đổi</p>
                    <p class="mt-1 text-2xl font-bold text-emerald-700"><span id="conversion-hours">{{ number_format((float) ($snapshot['conversion_hours'] ?? 0), 2, '.', '') }}</span></p>
                </div>
                <div class="rounded-xl border border-purple-100 bg-purple-50 p-4">
                    <p class="text-sm text-slate-600">Giờ NCKH đã duyệt</p>
                    <p class="mt-1 text-2xl font-bold text-purple-700"><span id="research-hours">{{ number_format((float) ($snapshot['research_hours'] ?? 0), 2, '.', '') }}</span></p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-sm text-slate-600">Lần truy xuất gần nhất</p>
                    <p id="retrieved-at" class="mt-1 font-semibold text-slate-800">{{ $declaration?->schedule_retrieved_at?->format('d/m/Y H:i') ?? 'Chưa truy xuất' }}</p>
                </div>
            </div>

            <div id="subject-statistics-panel" class="mt-5 {{ empty($snapshot['by_subject']) ? 'hidden' : '' }}">
                <h3 class="mb-2 text-sm font-semibold uppercase tracking-wide text-slate-600">Tổng hợp theo môn học</h3>
                <div class="overflow-x-auto rounded-xl border border-slate-200">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600">
                            <tr><th class="px-3 py-2 text-left">Môn học</th><th class="px-3 py-2 text-right">Lý thuyết</th><th class="px-3 py-2 text-right">Thực hành</th><th class="px-3 py-2 text-right">Tổng tiết</th></tr>
                        </thead>
                        <tbody id="subject-statistics-body" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>
            </div>

            <div id="schedule-detail-panel" class="mt-5 {{ empty($snapshot['rows']) ? 'hidden' : '' }}">
                <div class="mb-2 flex items-center justify-between gap-2">
                    <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-600">Thống kê chi tiết lịch giảng dạy</h3>
                    <span id="schedule-row-count" class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600"></span>
                </div>
                <div class="max-h-[420px] overflow-auto rounded-xl border border-slate-200">
                    <table class="w-full min-w-[900px] text-sm">
                        <thead class="sticky top-0 bg-slate-50 text-slate-600">
                            <tr>
                                <th class="px-3 py-2 text-left">Ngày</th>
                                <th class="px-3 py-2 text-center">Tiết</th>
                                <th class="px-3 py-2 text-left">Môn học</th>
                                <th class="px-3 py-2 text-left">Loại tiết</th>
                                <th class="px-3 py-2 text-left">Lớp</th>
                                <th class="px-3 py-2 text-left">Lịch huấn luyện</th>
                                <th class="px-3 py-2 text-left">Phòng</th>
                            </tr>
                        </thead>
                        <tbody id="schedule-detail-body" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>
            </div>

            <div id="empty-schedule-state" class="mt-5 hidden rounded-xl border border-dashed border-slate-300 px-4 py-8 text-center text-sm text-slate-500">
                Không tìm thấy tiết Lý thuyết/Thực hành được phân công trong khoảng thời gian này.
            </div>
        </div>

        <div class="lms-card rounded-2xl p-5 mb-6">
            <div class="mb-4">
                <h2 class="font-semibold text-slate-900"><i class="bi bi-pencil-square mr-1 text-amber-600"></i> Giờ giảng dạy khác được tính trực tiếp</h2>
                <p class="mt-1 text-sm text-slate-500">Chỉ nhập phần giờ hợp lệ nhưng chưa có trong Lịch huấn luyện.</p>
            </div>
            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">
                <div>
                    <label class="mb-2 block font-medium text-slate-800" for="other_teaching_hours">Số giờ giảng dạy khác</label>
                    <div class="flex items-center gap-2">
                        <input type="number" id="other_teaching_hours" name="other_teaching_hours" min="0" max="100000" step="0.01"
                               value="{{ $otherHours }}"
                               class="w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none"
                               {{ $isLocked ? 'readonly' : '' }} required>
                        <span class="font-medium text-slate-600">giờ</span>
                    </div>
                    @error('other_teaching_hours')<span class="mt-1 block text-sm text-rose-600">{{ $message }}</span>@enderror
                </div>
                <div class="lg:col-span-2">
                    <label class="mb-2 block font-medium text-slate-800" for="other_teaching_notes">Nội dung/giải trình giờ giảng khác</label>
                    <textarea id="other_teaching_notes" name="other_teaching_notes" rows="3"
                              placeholder="Ghi rõ hoạt động, thời gian, căn cứ tính giờ..."
                              class="w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none"
                              {{ $isLocked ? 'readonly' : '' }}>{{ old('other_teaching_notes', $declaration->other_teaching_notes ?? '') }}</textarea>
                    @error('other_teaching_notes')<span class="mt-1 block text-sm text-rose-600">{{ $message }}</span>@enderror
                </div>
            </div>
        </div>

        <div class="lms-card rounded-2xl p-5 mb-6 bg-gradient-to-r from-teal-50 to-indigo-50">
            <h2 class="mb-4 font-semibold text-slate-900">Kết quả kê khai tạm tính</h2>
            <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
                <div class="rounded-xl bg-white p-3"><p class="text-xs text-slate-500">Từ lịch</p><p class="mt-1 text-xl font-bold text-indigo-700"><span id="result-schedule-hours">0</span></p></div>
                <div class="rounded-xl bg-white p-3"><p class="text-xs text-slate-500">Giảng khác</p><p class="mt-1 text-xl font-bold text-amber-700"><span id="result-other-hours">0</span></p></div>
                <div class="rounded-xl bg-white p-3"><p class="text-xs text-slate-500">Tổng trực tiếp</p><p class="mt-1 text-xl font-bold text-teal-800"><span id="result-direct-hours">0</span></p></div>
                <div class="rounded-xl bg-white p-3"><p class="text-xs text-slate-500">HĐCM quy đổi</p><p class="mt-1 text-xl font-bold text-emerald-700"><span id="result-conversion-hours">0</span></p></div>
                <div class="col-span-2 rounded-xl bg-teal-700 p-3 text-white lg:col-span-1"><p class="text-xs text-teal-100">Tổng giờ chuẩn</p><p class="mt-1 text-2xl font-bold"><span id="result-total-hours">0</span></p></div>
            </div>
            <p class="mt-3 text-sm text-slate-600">
                Tổng giờ chuẩn = Giờ trực tiếp (lịch + giảng khác) + Giờ HĐCM quy đổi đã được duyệt trong khoảng kê khai.
            </p>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('lms.teach.standard-hours.my-results.index', ['year' => $defaultYear]) }}"
               class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-medium transition-colors">Hủy</a>
            @unless($isLocked)
                <button type="submit" class="px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium transition-colors">
                    <i class="bi bi-save"></i> Lưu bảng kê khai {{ mb_strtolower($periodModeLabel) }}
                </button>
            @endunless
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    'use strict';

    @php
        $retrieveUrl = route('lms.teach.standard-hours.my-results.retrieve-schedule');
        $initialConversionHours = (float) ($declaration->conversion_hours ?? 0);
    @endphp
    const endpoint = @json($retrieveUrl);
    const initialSnapshot = @json($snapshot);
    const fromDate = document.getElementById('schedule_filter_from_date');
    const toDate = document.getElementById('schedule_filter_to_date');
    const objectType = document.getElementById('object_type_id');
    const position = document.getElementById('position_id');
    const otherHours = document.getElementById('other_teaching_hours');
    const retrieveButton = document.getElementById('retrieve-schedule-button');
    const errorBox = document.getElementById('retrieve-error');
    let scheduleHours = Number(initialSnapshot.total_hours || 0);
    let conversionHours = Number(initialSnapshot.conversion_hours || @json($initialConversionHours));

    function selectedOption(select) {
        if (!select) return null;
        return typeof window.getSelectOption === 'function'
            ? window.getSelectOption(select)
            : select.options[select.selectedIndex];
    }

    function valueNumber(value) {
        const parsed = parseFloat(value);
        return Number.isFinite(parsed) ? parsed : 0;
    }

    function format(value) {
        return valueNumber(value).toLocaleString('vi-VN', { minimumFractionDigits: 0, maximumFractionDigits: 2 });
    }

    function escapeHtml(value) {
        return String(value ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function updateNorm() {
        const objectOption = selectedOption(objectType);
        const positionOption = selectedOption(position);
        const base = valueNumber(objectOption?.dataset.hours);
        const ratio = valueNumber(positionOption?.dataset.ratio);
        const effective = Math.round(base * ratio) / 100;

        document.getElementById('base-norm-hours').textContent = objectOption?.value ? format(base) : '—';
        document.getElementById('position-ratio').textContent = positionOption?.value ? format(ratio) : '—';
        document.getElementById('effective-norm-hours').textContent = objectOption?.value && positionOption?.value ? format(effective) : '—';
        document.getElementById('norm-formula').textContent = objectOption?.value && positionOption?.value
            ? (format(base) + ' × ' + format(ratio) + '% = ' + format(effective))
            : '—';
    }

    function updateTotals() {
        const other = valueNumber(otherHours?.value);
        const direct = scheduleHours + other;
        const total = direct + conversionHours;
        document.getElementById('result-schedule-hours').textContent = format(scheduleHours);
        document.getElementById('result-other-hours').textContent = format(other);
        document.getElementById('result-direct-hours').textContent = format(direct);
        document.getElementById('result-conversion-hours').textContent = format(conversionHours);
        document.getElementById('result-total-hours').textContent = format(total);
    }

    function renderSubjectStatistics(rows) {
        const panel = document.getElementById('subject-statistics-panel');
        const body = document.getElementById('subject-statistics-body');
        body.innerHTML = '';
        panel.classList.toggle('hidden', !rows.length);
        rows.forEach(function (row) {
            body.insertAdjacentHTML('beforeend',
                '<tr>' +
                    '<td class="px-3 py-2"><div class="font-medium text-slate-900">' + escapeHtml(row.subject_name) + '</div><div class="text-xs text-slate-500">' + escapeHtml(row.subject_code || '') + '</div></td>' +
                    '<td class="px-3 py-2 text-right">' + format(row.theory_hours) + '</td>' +
                    '<td class="px-3 py-2 text-right">' + format(row.practice_hours) + '</td>' +
                    '<td class="px-3 py-2 text-right font-semibold text-indigo-700">' + format(row.total_hours) + '</td>' +
                '</tr>'
            );
        });
    }

    function renderScheduleDetails(rows) {
        const panel = document.getElementById('schedule-detail-panel');
        const empty = document.getElementById('empty-schedule-state');
        const body = document.getElementById('schedule-detail-body');
        body.innerHTML = '';
        panel.classList.toggle('hidden', !rows.length);
        empty.classList.toggle('hidden', !!rows.length);
        document.getElementById('schedule-row-count').textContent = rows.length + ' tiết';

        rows.forEach(function (row) {
            body.insertAdjacentHTML('beforeend',
                '<tr class="hover:bg-slate-50">' +
                    '<td class="px-3 py-2"><div class="font-medium">' + escapeHtml(row.date_label) + '</div><div class="text-xs text-slate-500">' + escapeHtml(row.weekday) + '</div></td>' +
                    '<td class="px-3 py-2 text-center font-semibold">' + escapeHtml(row.period) + '</td>' +
                    '<td class="px-3 py-2"><div class="font-medium">' + escapeHtml(row.subject_name) + '</div><div class="text-xs text-slate-500">' + escapeHtml(row.subject_code || '') + '</div></td>' +
                    '<td class="px-3 py-2">' + escapeHtml(row.lesson_type_label) + '</td>' +
                    '<td class="px-3 py-2">' + escapeHtml(row.class_name || row.class_code || '—') + '</td>' +
                    '<td class="px-3 py-2"><div>' + escapeHtml(row.schedule_name || '—') + '</div><div class="text-xs text-slate-500">' + escapeHtml(row.schedule_code || '') + '</div></td>' +
                    '<td class="px-3 py-2">' + escapeHtml(row.classroom || '—') + '</td>' +
                '</tr>'
            );
        });
    }

    function renderResult(result) {
        scheduleHours = valueNumber(result.total_hours);
        conversionHours = valueNumber(result.conversion_hours);
        document.getElementById('schedule-hours').textContent = format(scheduleHours);
        document.getElementById('conversion-hours').textContent = format(conversionHours);
        document.getElementById('research-hours').textContent = format(result.research_hours);
        document.getElementById('retrieved-at').textContent = new Date().toLocaleString('vi-VN');
        renderSubjectStatistics(result.by_subject || []);
        renderScheduleDetails(result.rows || []);
        updateTotals();
    }

    async function retrieveSchedule() {
        errorBox.classList.add('hidden');
        if (!fromDate?.value || !toDate?.value) {
            errorBox.textContent = 'Vui lòng chọn đầy đủ Từ ngày và Đến ngày.';
            errorBox.classList.remove('hidden');
            return;
        }

        const original = retrieveButton.innerHTML;
        retrieveButton.disabled = true;
        retrieveButton.innerHTML = '<span class="inline-block animate-spin"><i class="bi bi-arrow-repeat"></i></span> Đang truy xuất...';

        try {
            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                },
                body: JSON.stringify({
                    from_date: fromDate.value,
                    to_date: toDate.value,
                }),
            });
            const data = await response.json();
            if (!response.ok) {
                const firstError = data.errors ? Object.values(data.errors).flat()[0] : null;
                throw new Error(firstError || data.message || 'Không thể truy xuất lịch giảng dạy.');
            }
            renderResult(data);
            if (typeof window.uiAlert === 'function') {
                window.uiAlert('Đã truy xuất ' + data.total_periods + ' tiết giảng dạy từ Lịch huấn luyện.', 'success');
            }
        } catch (error) {
            errorBox.textContent = error.message || 'Không thể truy xuất lịch giảng dạy.';
            errorBox.classList.remove('hidden');
        } finally {
            retrieveButton.disabled = false;
            retrieveButton.innerHTML = original;
        }
    }

    objectType?.addEventListener('change', updateNorm);
    position?.addEventListener('change', updateNorm);
    otherHours?.addEventListener('input', updateTotals);
    retrieveButton?.addEventListener('click', retrieveSchedule);
    window.addEventListener('app:tom-select-ready', updateNorm, { once: true });
    requestAnimationFrame(function () {
        updateNorm();
        renderResult(initialSnapshot);
    });
})();
</script>
@endpush
