@extends('layouts.admin')

@section('title', 'Kê khai giờ chuẩn')
@section('page-title', 'Kê khai giờ chuẩn')

@section('content')
@php
    $periodService = app(\Modules\StandardHours\Services\PeriodService::class);
    $periodModeLabel = $periodService->modeLabel();
@endphp
@php
    $hasYears = !empty($years);
    $isManagementView = (bool) ($isManagementView ?? false);
    $selectedInstructorId = $selectedInstructorId ?? null;
    $declarationParams = array_filter([
        'year' => $defaultYear,
        'instructor_id' => $selectedInstructorId,
    ], fn ($value) => $value !== null && $value !== '');
    $exportParams = array_filter([
        'year' => $defaultYear,
        'instructor_id' => $selectedInstructorId,
        'from_date' => request('from_date'),
        'to_date' => request('to_date'),
    ], fn ($value) => $value !== null && $value !== '');
    $dateRange = $selectedResult
        ? \Modules\StandardHours\Support\YearlyResultFormatter::yearDateRange(
            $selectedResult->year,
            $selectedResult->declaration_from_date?->toDateString(),
            $selectedResult->declaration_to_date?->toDateString(),
        )
        : null;
@endphp

<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Giờ chuẩn GV', 'url' => route('standard-hours.hub')],
    ['title' => 'Kê khai giờ chuẩn']
]" />

<x-page-header title="{{ $isManagementView ? 'HỒ SƠ KÊ KHAI GIỜ CHUẨN' : 'KÊ KHAI GIỜ CHUẨN HẰNG NĂM' }}"
               :actions="array_values(array_filter([
                    \Modules\StandardHours\Support\HubNavigation::backAction(),
                    $selectedInstructorId ? [
                        'url' => route('standard-hours.my-results.declaration', $declarationParams),
                        'label' => $selectedResult ? 'Cập nhật kê khai' : 'Tạo bảng kê khai',
                        'icon' => 'pencil-square',
                        'color' => 'blue',
                    ] : null,
               ]))" />

<div class="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="border-b border-slate-200 bg-gradient-to-r from-slate-900 to-blue-900 px-5 py-4 text-white">
        <h2 class="text-lg font-semibold">{{ $isManagementView ? 'Sổ kê khai giờ chuẩn giảng viên' : 'Sổ giờ chuẩn cá nhân' }}</h2>
        <p class="mt-1 text-sm text-blue-100">
            Hệ thống liên kết lịch giảng dạy, hoạt động chuyên môn và NCKH để chốt kết quả theo từng
            {{ mb_strtolower(app(\Modules\StandardHours\Services\PeriodService::class)->modeLabel()) }}.
            @if($isManagementView)
                Chọn giảng viên để tạo/cập nhật hồ sơ thay mặt hoặc xem toàn bộ phạm vi được quản lý.
            @endif
        </p>
    </div>
    <div class="grid grid-cols-1 gap-3 p-4 md:grid-cols-3">
        <div class="rounded-lg border border-blue-100 bg-blue-50 p-4">
            <div class="flex items-center gap-2 font-semibold text-blue-900">
                <i class="bi bi-calendar2-week"></i> Trực tiếp giảng dạy
            </div>
            <p class="mt-2 text-sm leading-5 text-slate-600">
                Tự động lấy từ lịch đã phân công. Giảng viên không cần kê khai hoặc điểm danh cá nhân.
            </p>
        </div>
        <a href="{{ route('standard-hours.conversion-records.index') }}"
           class="group rounded-lg border border-emerald-100 bg-emerald-50 p-4 transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-center justify-between font-semibold text-emerald-900">
                <span><i class="bi bi-clipboard-check mr-1"></i> Hoạt động chuyên môn</span>
                <i class="bi bi-arrow-right transition group-hover:translate-x-1"></i>
            </div>
            <p class="mt-2 text-sm leading-5 text-slate-600">Kê khai các hoạt động ngoài giờ giảng dạy và gửi cấp quản lý duyệt.</p>
        </a>
        <a href="{{ route('standard-hours.research-records.index') }}"
           class="group rounded-lg border border-indigo-100 bg-indigo-50 p-4 transition hover:-translate-y-0.5 hover:shadow-md">
            <div class="flex items-center justify-between font-semibold text-indigo-900">
                <span><i class="bi bi-mortarboard mr-1"></i> Nghiên cứu khoa học</span>
                <i class="bi bi-arrow-right transition group-hover:translate-x-1"></i>
            </div>
            <p class="mt-2 text-sm leading-5 text-slate-600">Kê khai phần giờ của bản thân theo vai trò, số người, số năm và tỷ lệ đóng góp.</p>
        </a>
    </div>
</div>

@if($hasYears && $selectedResult)
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-blue-500">
        <p class="text-sm text-gray-600">Tổng giờ chuẩn</p>
        <p class="text-2xl font-bold text-blue-700">{{ number_format($selectedResult->total_standard_hours, 0) }}</p>
        <p class="text-xs text-gray-500 mt-1">Định mức: {{ number_format($selectedResult->standard_norm_hours, 0) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-indigo-500">
        <p class="text-sm text-gray-600">Trực tiếp giảng dạy</p>
        <p class="text-2xl font-bold">{{ number_format($selectedResult->teaching_hours, 0) }}</p>
        <p class="text-xs text-gray-500 mt-1">Tối thiểu: {{ number_format($selectedResult->min_classroom_hours, 0) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-purple-500">
        <p class="text-sm text-gray-600">Giờ NCKH</p>
        <p class="text-2xl font-bold">{{ number_format($selectedResult->research_hours, 0) }}</p>
        <p class="text-xs text-gray-500 mt-1">Định mức: {{ number_format($selectedResult->research_norm_hours, 0) }}</p>
    </div>
    <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 {{ $selectedResult->meets_overall ? 'border-green-500' : 'border-red-500' }}">
        <p class="text-sm text-gray-600">Kết quả</p>
        <p class="text-2xl font-bold {{ $selectedResult->meets_overall ? 'text-green-700' : 'text-red-700' }}">
            {{ $selectedResult->overall_result_text }}
        </p>
        <p class="text-xs text-gray-500 mt-1">{{ $selectedResult->status_text }}</p>
    </div>
</div>

<div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
        <div>
            <h3 class="font-semibold text-gray-900">Xuất báo cáo</h3>
            <p class="text-sm text-gray-500 mt-1">
                {{ $periodModeLabel }} <strong>{{ $defaultYear ? $periodService->label($defaultYear) : '—' }}</strong>
                @if($dateRange)
                    ({{ $dateRange['from'] }} → {{ $dateRange['to'] }})
                @endif
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('standard-hours.my-results.export', $exportParams) }}"
               class="{{ \Modules\StandardHours\Support\ActionButton::classes('success') }}">
                <i class="bi bi-file-earmark-excel"></i> Xuất Excel
            </a>
            <a href="{{ route('standard-hours.my-results.show', $selectedResult) }}"
               class="{{ \Modules\StandardHours\Support\ActionButton::classes('primary') }}">
                <i class="bi bi-eye"></i> Xem chi tiết
            </a>
            @unless($selectedResult->isLocked())
                <a href="{{ route('standard-hours.my-results.declaration', [
                    'year' => $selectedResult->year,
                    'instructor_id' => $selectedResult->instructor_id,
                ]) }}"
                   class="{{ \Modules\StandardHours\Support\ActionButton::classes('secondary') }}">
                    <i class="bi bi-pencil-square"></i> Cập nhật kê khai
                </a>
            @endunless
        </div>
    </div>
</div>
@endif

<x-filter-form
    :action="route('standard-hours.my-results.index')"
    :clear-url="route('standard-hours.my-results.index')"
    :columns="$isManagementView ? 5 : 4"
    :filters="array_values(array_filter([
        $isManagementView
            ? ['type' => 'select', 'name' => 'instructor_id', 'placeholder' => 'Tất cả giảng viên', 'options' => $instructors]
            : null,
        ['type' => 'select', 'name' => 'year', 'placeholder' => 'Chọn '.mb_strtolower($periodModeLabel), 'options' => $years],
        ['type' => 'date', 'name' => 'from_date', 'label' => 'Từ ngày'],
        ['type' => 'date', 'name' => 'to_date', 'label' => 'Đến ngày'],
    ]))" />

<div class="mb-5 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-900">
    <i class="bi bi-info-circle mr-1"></i>
    Bộ lọc ngày là khoảng tra cứu độc lập, có thể chọn không giới hạn qua nhiều năm hoặc năm học.
    Kỳ <strong>{{ mb_strtolower($periodModeLabel) }}</strong> vẫn dùng để phân loại và chốt từng hồ sơ.
</div>

@if($results->total() > 0)
    <div class="mb-4 text-sm text-gray-600">{{ $results->firstItem() }}-{{ $results->lastItem() }} / {{ $results->total() }}</div>
@endif

<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    @if($results->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-100 text-slate-800 border-b border-slate-200">
                    <tr>
                        @if($isManagementView)
                            <th class="px-4 py-3 text-left">Giảng viên</th>
                        @endif
                        <th class="px-4 py-3 text-left">{{ $periodModeLabel }}</th>
                        <th class="px-4 py-3 text-left">Trực tiếp giảng dạy</th>
                        <th class="px-4 py-3 text-left">HĐ CM</th>
                        <th class="px-4 py-3 text-left">Tổng GC</th>
                        <th class="px-4 py-3 text-left">Định mức</th>
                        <th class="px-4 py-3 text-left">NCKH</th>
                        <th class="px-4 py-3 text-left">Kết quả</th>
                        <th class="px-4 py-3 text-left">Trạng thái</th>
                        <th class="px-4 py-3 text-left">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($results as $result)
                    <tr class="hover:bg-gray-50">
                        @if($isManagementView)
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900">{{ $result->instructor?->name ?? '—' }}</div>
                                <div class="text-xs text-slate-500">
                                    {{ $result->instructor?->code ?? '—' }}
                                    @if($result->instructor?->unit?->name)
                                        · {{ $result->instructor->unit->name }}
                                    @endif
                                </div>
                            </td>
                        @endif
                        <td class="px-4 py-3 font-medium">{{ $result->period_label }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ number_format($result->teaching_hours, 2) }}</div>
                            <div class="text-xs text-gray-500">
                                Lịch {{ number_format((float) $result->schedule_teaching_hours, 2) }}
                                + khác {{ number_format((float) $result->other_teaching_hours, 2) }}
                            </div>
                        </td>
                        <td class="px-4 py-3">{{ number_format($result->conversion_hours, 0) }}</td>
                        <td class="px-4 py-3 font-medium text-blue-700">{{ number_format($result->total_standard_hours, 0) }}</td>
                        <td class="px-4 py-3">{{ number_format($result->standard_norm_hours, 0) }}</td>
                        <td class="px-4 py-3">{{ number_format($result->research_hours, 0) }}</td>
                        <td class="px-4 py-3">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs {{ $result->meets_overall ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $result->overall_result_text }}
                            </span>
                        </td>
                        <td class="px-4 py-3">{{ $result->status_text }}</td>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('standard-hours.my-results.show', $result) }}"
                                   class="text-blue-600 hover:text-blue-800" title="Chi tiết">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('standard-hours.my-results.export', array_filter([
                                    'year' => $result->year,
                                    'instructor_id' => $result->instructor_id,
                                    'from_date' => request('from_date'),
                                    'to_date' => request('to_date'),
                                ])) }}"
                                   class="text-green-600 hover:text-green-800" title="Xuất Excel">
                                    <i class="bi bi-file-earmark-excel"></i>
                                </a>
                                @unless($result->isLocked())
                                    <a href="{{ route('standard-hours.my-results.declaration', [
                                        'year' => $result->year,
                                        'instructor_id' => $result->instructor_id,
                                    ]) }}"
                                       class="text-amber-600 hover:text-amber-800" title="Cập nhật kê khai">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                @endunless
                            </div>
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
            <p class="text-gray-500 mb-2">Chưa có bảng giờ chuẩn được chốt.</p>
            <p class="mb-4 text-sm text-gray-400">Hãy tạo bảng kê khai, chọn đối tượng/chức danh và truy xuất giờ dạy từ Lịch huấn luyện.</p>
            @if($selectedInstructorId)
                <a href="{{ route('standard-hours.my-results.declaration', $declarationParams) }}"
                   class="{{ \Modules\StandardHours\Support\ActionButton::classes('primary') }}">
                    <i class="bi bi-plus-circle"></i> Tạo bảng kê khai {{ mb_strtolower($periodModeLabel) }} {{ $defaultYear }}
                </a>
            @elseif($isManagementView)
                <p class="mt-3 text-sm font-medium text-blue-700">Chọn một giảng viên ở bộ lọc để tạo hồ sơ kê khai.</p>
            @endif
        </div>
    @endif
</div>
@endsection
