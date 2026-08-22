@extends('layouts.admin')

@section('title', 'Báo cáo giờ chuẩn')
@section('page-title', 'Báo cáo giờ chuẩn')

@section('content')
@php
    $reportType = $reportType ?? 'total';
    $exportQuery = array_filter(array_merge(
        request()->only(['unit_id', 'instructor_id', 'overall_result', 'search', 'from_date', 'to_date', 'report_type']),
        $reportType === 'total' ? ['year' => $defaultYear] : ['report_type' => $reportType]
    ), static fn ($v) => $v !== null && $v !== '');
    $hasYears = !empty($years);
    $periodService = app(\Modules\StandardHours\Services\PeriodService::class);
    $periodModeLabel = $periodService->modeLabel();
@endphp

<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Giờ chuẩn GV', 'url' => route('standard-hours.hub')],
    ['title' => 'Báo cáo']
]" />

<x-page-header title="BÁO CÁO THỐNG KÊ GIỜ CHUẨN" :actions="[[
    'url' => route('standard-hours.hub'), 'label' => 'Quay lại', 'icon' => 'arrow-left', 'color' => 'gray'
]]" />

<div class="bg-white rounded-lg shadow-sm border mb-6">
    <div class="flex flex-wrap border-b">
        @foreach([
            'total' => 'Tổng giờ chuẩn GV',
            'conversion' => 'Theo HĐ chuyên môn',
            'research' => 'Theo NCKH',
        ] as $type => $label)
        <a href="{{ route('standard-hours.reports.index', array_merge(request()->except('page'), ['report_type' => $type])) }}"
           class="px-5 py-3 text-sm font-medium border-b-2 -mb-px {{ $reportType === $type ? 'border-blue-600 text-blue-700' : 'border-transparent text-gray-500 hover:text-gray-800' }}">
            {{ $label }}
        </a>
        @endforeach
    </div>
</div>

@if($reportType === 'total')
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-blue-500">
            <p class="text-sm text-gray-600">Tổng GV</p>
            <p class="text-2xl font-bold">{{ $summary['total'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-green-500">
            <p class="text-sm text-gray-600">Đạt</p>
            <p class="text-2xl font-bold text-green-700">{{ $summary['passed'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-red-500">
            <p class="text-sm text-gray-600">Không đạt</p>
            <p class="text-2xl font-bold text-red-700">{{ $summary['failed'] ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-indigo-500">
            <p class="text-sm text-gray-600">TB giờ chuẩn</p>
            <p class="text-2xl font-bold text-indigo-700">{{ number_format($summary['avg_standard_hours'] ?? 0, 0) }}</p>
        </div>
    </div>

    @if($hasYears)
        @include('standardhours::reports._export-panel', [
            'exportRoute' => route('standard-hours.reports.export'),
            'exportQuery' => array_merge($exportQuery, ['report_type' => 'total']),
            'units' => $units,
            'showPrint' => true,
            'printRoute' => 'standard-hours.reports.print',
            'printQuery' => array_merge($exportQuery, ['report_type' => 'total']),
            'hint' => $periodModeLabel.': '.$periodService->label($defaultYear),
            'panelId' => 'export-total',
        ])
    @endif

    <x-filter-form
        :action="route('standard-hours.reports.index', ['report_type' => 'total'])"
        :clear-url="route('standard-hours.reports.index', ['report_type' => 'total'])"
        :filters="[
            ['type' => 'search', 'name' => 'search', 'placeholder' => 'Tìm theo tên, mã GV...'],
            ['type' => 'select', 'name' => 'year', 'placeholder' => 'Chọn '.mb_strtolower($periodModeLabel), 'options' => $years],
            ['type' => 'select', 'name' => 'unit_id', 'placeholder' => 'Tất cả đơn vị', 'options' => $units],
            ['type' => 'instructor-select', 'name' => 'instructor_id', 'placeholder' => 'Tất cả giảng viên', 'options' => $instructors],
            ['type' => 'select', 'name' => 'overall_result', 'placeholder' => 'Tất cả kết quả', 'options' => $overallResults]
        ]" />

    @if($reports->total() > 0)
        <div class="mb-4 text-sm text-gray-600">{{ $reports->firstItem() }}-{{ $reports->lastItem() }} / {{ $reports->total() }}</div>
    @endif

    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        @if($reports->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-100 text-slate-800 border-b border-slate-200">
                        <tr>
                            <th class="px-3 py-3 text-left">STT</th>
                            <th class="px-3 py-3 text-left">Giảng viên</th>
                            <th class="px-3 py-3 text-left">Đơn vị</th>
                            <th class="px-3 py-3 text-left">Trực tiếp giảng dạy</th>
                            <th class="px-3 py-3 text-left">HĐ CM</th>
                            <th class="px-3 py-3 text-left">Tổng GC</th>
                            <th class="px-3 py-3 text-left">NCKH</th>
                            <th class="px-3 py-3 text-left">KQ</th>
                            <th class="px-3 py-3 text-left"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($reports as $i => $report)
                        <tr class="hover:bg-gray-50">
                            <td class="px-3 py-2">{{ $reports->firstItem() + $i }}</td>
                            <td class="px-3 py-2">
                                <div class="font-medium">{{ $report->instructor->name }}</div>
                                <div class="text-xs text-gray-500">{{ $report->instructor->code }}</div>
                            </td>
                            <td class="px-3 py-2">{{ $report->instructor->unit->name ?? '—' }}</td>
                            <td class="px-3 py-2">{{ number_format($report->teaching_hours, 0) }}</td>
                            <td class="px-3 py-2">{{ number_format($report->conversion_hours, 0) }}</td>
                            <td class="px-3 py-2 font-medium text-blue-700">{{ number_format($report->total_standard_hours, 0) }}</td>
                            <td class="px-3 py-2">{{ number_format($report->research_hours, 0) }}</td>
                            <td class="px-3 py-2">
                                <span class="px-2 py-0.5 rounded-full text-xs {{ $report->meets_overall ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $report->overall_result_text }}
                                </span>
                            </td>
                            <td class="px-3 py-2">
                                <a href="{{ route('standard-hours.reports.show', $report) }}" class="text-blue-600 hover:text-blue-800"><i class="bi bi-eye"></i></a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($reports->hasPages())
            <div class="px-4 py-3 border-t flex justify-center">{{ $reports->appends(request()->query())->links() }}</div>
            @endif
        @else
            <div class="text-center py-12 text-gray-500">Chưa có dữ liệu báo cáo.</div>
        @endif
    </div>

@elseif($reportType === 'conversion')
    @include('standardhours::reports._export-panel', [
        'exportRoute' => route('standard-hours.reports.export-conversion'),
        'exportQuery' => $exportQuery,
        'units' => $units,
        'hint' => 'Thống kê các kê khai hoạt động chuyên môn đã duyệt',
        'panelId' => 'export-conversion',
    ])

    <x-filter-form
        :action="route('standard-hours.reports.index', ['report_type' => 'conversion'])"
        :clear-url="route('standard-hours.reports.index', ['report_type' => 'conversion'])"
        :filters="[
            ['type' => 'date', 'name' => 'from_date', 'label' => 'Từ ngày'],
            ['type' => 'date', 'name' => 'to_date', 'label' => 'Đến ngày'],
            ['type' => 'select', 'name' => 'unit_id', 'placeholder' => 'Tất cả đơn vị', 'options' => $units],
            ['type' => 'instructor-select', 'name' => 'instructor_id', 'placeholder' => 'Tất cả giảng viên', 'options' => $instructors],
        ]" />

    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        @if($conversionRows->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-emerald-600 text-white">
                    <tr>
                        <th class="px-3 py-3 text-left">STT</th>
                        <th class="px-3 py-3 text-left">Giảng viên</th>
                        <th class="px-3 py-3 text-left">Hoạt động</th>
                        <th class="px-3 py-3 text-left">Danh mục</th>
                        <th class="px-3 py-3 text-left">Giờ QĐ</th>
                        <th class="px-3 py-3 text-left">Ngày</th>
                        <th class="px-3 py-3 text-left"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($conversionRows as $i => $row)
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2">{{ $i + 1 }}</td>
                        <td class="px-3 py-2">{{ $row->instructor->name }}</td>
                        <td class="px-3 py-2">{{ $row->activity_name }}</td>
                        <td class="px-3 py-2">{{ $row->conversionCategory->name ?? '—' }}</td>
                        <td class="px-3 py-2">{{ number_format($row->converted_hours, 2) }}</td>
                        <td class="px-3 py-2">{{ $row->activity_date?->format('d/m/Y') }}</td>
                        <td class="px-3 py-2">
                            @can('standard-hours.reports.view')
                            <a href="{{ route('standard-hours.conversion-records.show', $row) }}"
                               class="action-btn inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 text-sm font-medium"
                               title="Xem kê khai">
                                <i class="bi bi-eye"></i> Xem
                            </a>
                            @endcan
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-12 text-gray-500">Không có dữ liệu trong khoảng thời gian đã chọn.</div>
        @endif
    </div>

@else
    @include('standardhours::reports._export-panel', [
        'exportRoute' => route('standard-hours.reports.export-research'),
        'exportQuery' => $exportQuery,
        'units' => $units,
        'hint' => 'Mỗi dòng = một thành viên tham gia sản phẩm NCKH (một sản phẩm có thể nhiều dòng)',
        'panelId' => 'export-research',
    ])

    <x-filter-form
        :action="route('standard-hours.reports.index', ['report_type' => 'research'])"
        :clear-url="route('standard-hours.reports.index', ['report_type' => 'research'])"
        :filters="[
            ['type' => 'date', 'name' => 'from_date', 'label' => 'Từ ngày'],
            ['type' => 'date', 'name' => 'to_date', 'label' => 'Đến ngày'],
            ['type' => 'select', 'name' => 'unit_id', 'placeholder' => 'Tất cả đơn vị', 'options' => $units],
            ['type' => 'instructor-select', 'name' => 'instructor_id', 'placeholder' => 'Tất cả giảng viên', 'options' => $instructors],
        ]" />

    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        @if($researchRows->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-indigo-600 text-white">
                    <tr>
                        <th class="px-3 py-3 text-left">STT</th>
                        <th class="px-3 py-3 text-left">Họ tên GV</th>
                        <th class="px-3 py-3 text-left">Tên sản phẩm</th>
                        <th class="px-3 py-3 text-left">Vai trò</th>
                        <th class="px-3 py-3 text-left">Tỷ lệ (%)</th>
                        <th class="px-3 py-3 text-left">Nghiệm thu</th>
                        <th class="px-3 py-3 text-left">Nơi XB</th>
                        <th class="px-3 py-3 text-left">Danh mục</th>
                        <th class="px-3 py-3 text-left">Giờ QĐ</th>
                        <th class="px-3 py-3 text-left"></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @php $lastRecordId = null; @endphp
                    @foreach($researchRows as $i => $member)
                    @php
                        $record = $member->researchRecord;
                        $showProduct = $lastRecordId !== $record->id;
                        $lastRecordId = $record->id;
                        $convertedHours = $member->converted_hours ?? $record->converted_hours;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-3 py-2">{{ $i + 1 }}</td>
                        <td class="px-3 py-2">{{ $member->instructor->name }}</td>
                        <td class="px-3 py-2">{{ $showProduct ? $record->product_name : '' }}</td>
                        <td class="px-3 py-2">{{ $member->role ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $member->contribution_percent !== null ? number_format($member->contribution_percent, 0) : '—' }}</td>
                        <td class="px-3 py-2">{{ $record->acceptance_date?->format('d/m/Y') }}</td>
                        <td class="px-3 py-2">{{ $record->publication_place ?? '—' }}</td>
                        <td class="px-3 py-2">{{ $record->researchCategory->name ?? '—' }}</td>
                        <td class="px-3 py-2">{{ number_format((float) $convertedHours, 2) }}</td>
                        <td class="px-3 py-2">
                            @can('standard-hours.reports.view')
                            <a href="{{ route('standard-hours.research-records.show', $record) }}"
                               class="action-btn inline-flex items-center gap-1 text-blue-600 hover:text-blue-800 text-sm font-medium"
                               title="Xem kê khai">
                                <i class="bi bi-eye"></i> Xem
                            </a>
                            @endcan
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="text-center py-12 text-gray-500">Không có dữ liệu NCKH trong khoảng thời gian đã chọn.</div>
        @endif
    </div>
@endif
@endsection

@push('scripts')
<script>
document.querySelectorAll('[data-export-panel]').forEach(function (panel) {
    var levelSelect = panel.querySelector('[data-export-level]');
    var unitWrap = panel.querySelector('[data-unit-ids-wrap]');
    var unitSelect = panel.querySelector('[data-export-units]');
    var unitCountEl = panel.querySelector('[data-unit-count]');
    if (!levelSelect || !unitWrap || !unitSelect) return;

    function initPanelTomSelects() {
        if (typeof window.initTomSelects === 'function') {
            window.initTomSelects(panel);
        }
    }

    function updateUnitCount() {
        if (!unitCountEl) return;
        var count = 0;
        if (typeof window.getTomValues === 'function') {
            count = window.getTomValues(unitSelect.id).filter(Boolean).length;
        } else if (unitSelect.tomselect) {
            count = (unitSelect.tomselect.getValue() || []).length;
        } else {
            count = Array.from(unitSelect.selectedOptions || []).length;
        }
        unitCountEl.textContent = String(count);
    }

    function setUnitEnabled(enabled) {
        if (unitSelect.tomselect) {
            if (enabled) {
                unitSelect.tomselect.enable();
            } else {
                unitSelect.tomselect.disable();
            }
        } else {
            unitSelect.disabled = !enabled;
        }
    }

    function selectAllUnitsIfEmpty() {
        var values = typeof window.getTomValues === 'function'
            ? window.getTomValues(unitSelect.id).filter(Boolean)
            : Array.from(unitSelect.selectedOptions || []).map(function (o) { return o.value; }).filter(Boolean);

        if (values.length > 0) {
            updateUnitCount();
            return;
        }

        var allIds = Array.from(unitSelect.options || []).map(function (o) {
            return String(o.value);
        }).filter(Boolean);

        if (typeof window.setTomValues === 'function') {
            window.setTomValues(unitSelect.id, allIds, true);
        } else if (unitSelect.tomselect) {
            unitSelect.tomselect.setValue(allIds, true);
        } else {
            Array.from(unitSelect.options).forEach(function (o) { o.selected = true; });
        }
        updateUnitCount();
    }

    function syncUnitVisibility() {
        var isUnit = levelSelect.value === 'unit';
        unitWrap.classList.toggle('hidden', !isUnit);
        setUnitEnabled(isUnit);
        if (isUnit) {
            // Tom Select trong khung ẩn có thể cần refresh layout
            initPanelTomSelects();
            selectAllUnitsIfEmpty();
            if (unitSelect.tomselect) {
                try { unitSelect.tomselect.refreshOptions(false); } catch (e) {}
            }
        }
        updateUnitCount();
    }

    initPanelTomSelects();
    unitSelect.addEventListener('change', updateUnitCount);
    levelSelect.addEventListener('change', syncUnitVisibility);
    syncUnitVisibility();
});
</script>
@endpush
