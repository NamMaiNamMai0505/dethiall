@extends('layouts.admin')

@section('title', 'Tính giờ chuẩn')
@section('page-title', 'Tính giờ chuẩn')

@section('content')
@php
    $periodService = app(\Modules\StandardHours\Services\PeriodService::class);
    $periodModeLabel = $periodService->modeLabel();
@endphp
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Giờ chuẩn GV', 'url' => route('standard-hours.hub')],
    ['title' => 'Tính giờ chuẩn']
]" />

<x-page-header title="TÍNH GIỜ CHUẨN GIẢNG VIÊN" :actions="[\Modules\StandardHours\Support\HubNavigation::backAction()]" />

@php
    $selectedYear = request('year', $year);
    $isLocked = $yearSummary['is_locked'] ?? false;
@endphp

<div class="grid grid-cols-1 lg:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-blue-500">
        <p class="text-sm text-gray-600">Tổng kết quả</p>
        <p class="text-2xl font-bold text-gray-900">{{ $yearSummary['total'] ?? 0 }}</p>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-green-500">
        <p class="text-sm text-gray-600">Đạt</p>
        <p class="text-2xl font-bold text-green-700">{{ $yearSummary['passed'] ?? 0 }}</p>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-red-500">
        <p class="text-sm text-gray-600">Không đạt</p>
        <p class="text-2xl font-bold text-red-700">{{ $yearSummary['failed'] ?? 0 }}</p>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-yellow-500">
        <p class="text-sm text-gray-600">Trạng thái năm</p>
        <p class="text-lg font-semibold {{ $isLocked ? 'text-yellow-700' : 'text-gray-700' }}">
            {{ $isLocked ? 'Đã khóa' : 'Chưa khóa' }}
        </p>
    </div>
</div>

@can('standard-hours.calculations.run')
<div class="bg-white rounded-lg shadow-sm p-6 mb-6">
    <h3 class="font-semibold text-gray-900 mb-4">Thao tác tính giờ</h3>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
        <div>
            <label class="block text-sm font-medium mb-1" for="action_year">{{ $periodModeLabel }}</label>
            <div class="ui-select-field">
                <select id="action_year" data-placeholder="Chọn {{ mb_strtolower($periodModeLabel) }}" class="w-full" required>
                    @foreach($years as $year => $label)
                        <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1" for="action_unit_id">Đơn vị (tùy chọn)</label>
            <div class="ui-select-field">
                <select id="action_unit_id" data-placeholder="Tất cả đơn vị" class="w-full">
                    <option value="">Tất cả đơn vị</option>
                    @foreach($units as $id => $name)
                        <option value="{{ $id }}" {{ request('unit_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="flex flex-wrap gap-2">
            <form method="POST" action="{{ route('standard-hours.calculations.preview') }}" class="calc-run-form inline">
                @csrf
                <input type="hidden" name="year" class="calc-year-input" value="{{ $selectedYear }}">
                <input type="hidden" name="unit_id" class="calc-unit-input" value="{{ request('unit_id') }}">
                <button type="submit" class="{{ \Modules\StandardHours\Support\ActionButton::classes('secondary') }}"><i class="bi bi-eye"></i> Xem trước</button>
            </form>
            @unless($isLocked)
            <form method="POST" action="{{ route('standard-hours.calculations.calculate') }}" class="calc-run-form inline"
                  data-confirm="Tính giờ chuẩn cho {{ mb_strtolower($periodModeLabel) }} đã chọn?">
                @csrf
                <input type="hidden" name="year" class="calc-year-input" value="{{ $selectedYear }}">
                <input type="hidden" name="unit_id" class="calc-unit-input" value="{{ request('unit_id') }}">
                <button type="submit" class="{{ \Modules\StandardHours\Support\ActionButton::classes('primary') }}"><i class="bi bi-calculator"></i> Tính giờ</button>
            </form>
            <form method="POST" action="{{ route('standard-hours.calculations.rollback') }}" class="calc-run-form inline"
                  data-confirm="Hoàn tác tất cả kết quả chưa khóa?">
                @csrf @method('PATCH')
                <input type="hidden" name="year" class="calc-year-input" value="{{ $selectedYear }}">
                <button type="submit" class="{{ \Modules\StandardHours\Support\ActionButton::classes('warning') }}"><i class="bi bi-arrow-counterclockwise"></i> Hoàn tác</button>
            </form>
            @endunless
            @if(($yearSummary['total'] ?? 0) > 0 && ! $isLocked)
            <form method="POST" action="{{ route('standard-hours.calculations.lock') }}" class="calc-run-form inline"
                  data-confirm="Khóa dữ liệu năm? Sau khi khóa không thể sửa.">
                @csrf @method('PATCH')
                <input type="hidden" name="year" class="calc-year-input" value="{{ $selectedYear }}">
                <button type="submit" class="{{ \Modules\StandardHours\Support\ActionButton::classes('danger') }}"><i class="bi bi-lock"></i> Khóa</button>
            </form>
            @endif
        </div>
    </div>
    <p class="text-sm text-gray-500 mt-3">
        Công thức: Giờ chuẩn = Trực tiếp giảng dạy (tự động từ lịch) + Giờ quy đổi HĐ CM. Đạt khi đủ định mức, đủ tỷ lệ trực tiếp giảng dạy và đủ NCKH.
    </p>
</div>
@push('scripts')
<script>
    function syncCalcInputs() {
        const yearEl = document.getElementById('action_year');
        const unitEl = document.getElementById('action_unit_id');
        const year = typeof window.getSelectValue === 'function' ? window.getSelectValue(yearEl) : (yearEl?.value || '');
        const unit = typeof window.getSelectValue === 'function' ? window.getSelectValue(unitEl) : (unitEl?.value || '');
        document.querySelectorAll('.calc-year-input').forEach(el => el.value = year);
        document.querySelectorAll('.calc-unit-input').forEach(el => el.value = unit);
    }
    if (typeof window.onTomChange === 'function') {
        window.onTomChange('action_year', syncCalcInputs);
        window.onTomChange('action_unit_id', syncCalcInputs);
    } else {
        document.getElementById('action_year')?.addEventListener('change', syncCalcInputs);
        document.getElementById('action_unit_id')?.addEventListener('change', syncCalcInputs);
    }
    document.querySelectorAll('.calc-run-form').forEach(form => form.addEventListener('submit', syncCalcInputs));
</script>
@endpush
@endcan

@if(!empty($previewData))
<div class="bg-white rounded-lg shadow-sm border border-blue-200 mb-6 overflow-hidden">
    <div class="px-4 py-3 bg-blue-50 border-b border-blue-100">
        <h3 class="font-semibold text-blue-900">
            Kết quả xem trước — {{ $previewData['processed'] }} GV
            @if($previewData['skipped'] > 0)
                <span class="text-sm font-normal text-orange-700">(bỏ qua {{ $previewData['skipped'] }})</span>
            @endif
        </h3>
    </div>
    @if(count($previewData['previews']) > 0)
    <div class="overflow-x-auto max-h-96 overflow-y-auto">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 sticky top-0">
                <tr>
                    <th class="px-3 py-2 text-left">GV</th>
                    <th class="px-3 py-2 text-left">Trực tiếp GD</th>
                    <th class="px-3 py-2 text-left">HĐ CM</th>
                    <th class="px-3 py-2 text-left">Tổng GC</th>
                    <th class="px-3 py-2 text-left">Định mức</th>
                    <th class="px-3 py-2 text-left">NCKH</th>
                    <th class="px-3 py-2 text-left">Kết quả</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @foreach($previewData['previews'] as $row)
                <tr>
                    <td class="px-3 py-2">{{ $row['instructor_name'] }} <span class="text-gray-500">({{ $row['instructor_code'] }})</span></td>
                    <td class="px-3 py-2">{{ number_format($row['teaching_hours'], 0) }}</td>
                    <td class="px-3 py-2">{{ number_format($row['conversion_hours'], 0) }}</td>
                    <td class="px-3 py-2 font-medium">{{ number_format($row['total_standard_hours'], 0) }}</td>
                    <td class="px-3 py-2">{{ number_format($row['standard_norm_hours'], 0) }}</td>
                    <td class="px-3 py-2">{{ number_format($row['research_hours'], 0) }}/{{ number_format($row['research_norm_hours'], 0) }}</td>
                    <td class="px-3 py-2">
                        <span class="px-2 py-0.5 rounded-full text-xs {{ $row['meets_overall'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                            {{ $row['meets_overall'] ? 'Đạt' : 'Không đạt' }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endif

<x-filter-form
    :action="route('standard-hours.calculations.index')"
    :clear-url="route('standard-hours.calculations.index')"
    :filters="[
        ['type' => 'search', 'name' => 'search', 'placeholder' => 'Tìm theo tên, mã GV...'],
        ['type' => 'select', 'name' => 'year', 'placeholder' => 'Tất cả năm', 'options' => $years],
        ['type' => 'select', 'name' => 'unit_id', 'placeholder' => 'Tất cả đơn vị', 'options' => $units],
        ['type' => 'select', 'name' => 'overall_result', 'placeholder' => 'Tất cả kết quả', 'options' => $overallResults],
        ['type' => 'select', 'name' => 'status', 'placeholder' => 'Tất cả trạng thái', 'options' => $statuses]
    ]">

@if($results->total() > 0)
    <div class="mb-4 text-sm text-gray-600">{{ $results->firstItem() }}-{{ $results->lastItem() }} / {{ $results->total() }}</div>
@endif
</x-filter-form>

<div class="bg-white rounded-lg shadow-sm border overflow-hidden mb-6">
    @if($results->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-100 text-slate-800 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left">STT</th>
                        <th class="px-4 py-3 text-left">Giảng viên</th>
                        <th class="px-4 py-3 text-left">{{ $periodModeLabel }}</th>
                        <th class="px-4 py-3 text-left">Trực tiếp GD</th>
                        <th class="px-4 py-3 text-left">HĐ CM</th>
                        <th class="px-4 py-3 text-left">Tổng GC</th>
                        <th class="px-4 py-3 text-left">NCKH</th>
                        <th class="px-4 py-3 text-left">Kết quả</th>
                        <th class="px-4 py-3 text-left">Trạng thái</th>
                        <th class="px-4 py-3 text-left">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($results as $i => $result)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">{{ $results->firstItem() + $i }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $result->instructor->name }}</div>
                            <div class="text-xs text-gray-500">{{ $result->instructor->code }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $result->period_label }}</td>
                        <td class="px-4 py-3">{{ number_format($result->teaching_hours, 0) }}</td>
                        <td class="px-4 py-3">{{ number_format($result->conversion_hours, 0) }}</td>
                        <td class="px-4 py-3 font-medium text-blue-700">{{ number_format($result->total_standard_hours, 0) }}</td>
                        <td class="px-4 py-3">{{ number_format($result->research_hours, 0) }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs {{ $result->meets_overall ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $result->overall_result_text }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm {{ $result->isLocked() ? 'text-yellow-700' : 'text-gray-600' }}">{{ $result->status_text }}</span>
                        </td>
                        <td class="px-4 py-3">
                            @canPermission('standard-hours.calculations.view')
                                <a href="{{ route('standard-hours.calculations.show', $result) }}" class="text-blue-600 hover:text-blue-800" title="Chi tiết">
                                    <i class="bi bi-eye"></i>
                                </a>
                            @endcanPermission
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($results->hasPages())
        <div class="px-4 py-3 border-t flex justify-center">{{ $results->appends(request()->query())->links() }}</div>
        @endif
    @else
        <div class="text-center py-12">
            <i class="bi bi-calculator text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 mb-2">Chưa có kết quả tính giờ.</p>
            <p class="text-sm text-gray-400">Chọn {{ mb_strtolower($periodModeLabel) }} và nhấn "Xem trước" hoặc "Tính giờ".</p>
        </div>
    @endif
</div>

<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    <div class="px-4 py-3 border-b bg-gray-50">
        <h3 class="font-semibold text-gray-900">Lịch sử tính giờ</h3>
    </div>
    @if($history->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left">Thời gian</th>
                        <th class="px-4 py-2 text-left">{{ $periodModeLabel }}</th>
                        <th class="px-4 py-2 text-left">Thao tác</th>
                        <th class="px-4 py-2 text-left">Xử lý</th>
                        <th class="px-4 py-2 text-left">Bỏ qua</th>
                        <th class="px-4 py-2 text-left">Người thực hiện</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($history as $log)
                    <tr>
                        <td class="px-4 py-2">{{ $log->created_at->format('d/m/Y H:i') }}</td>
                        <td class="px-4 py-2">{{ $log->period_label }}</td>
                        <td class="px-4 py-2">{{ $log->action_text }}</td>
                        <td class="px-4 py-2">{{ $log->instructors_processed }}</td>
                        <td class="px-4 py-2">{{ $log->instructors_skipped }}</td>
                        <td class="px-4 py-2">{{ $log->performer->name ?? '—' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="text-center text-gray-500 py-8">Chưa có lịch sử.</p>
    @endif
</div>
@endsection
