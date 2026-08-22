@extends('layouts.lms-learner')

@section('title', 'Kê khai giờ chuẩn')

@section('content')
@php
    $periodService = app(\Modules\StandardHours\Services\PeriodService::class);
    $periodModeLabel = $periodService->modeLabel();
    $hasYears = !empty($years);
    $declarationParams = array_filter(['year' => $defaultYear], fn ($v) => $v !== null && $v !== '');
    $exportParams = array_filter([
        'year' => $defaultYear,
        'from_date' => request('from_date'),
        'to_date' => request('to_date'),
    ], fn ($v) => $v !== null && $v !== '');
    $dateRange = $selectedResult
        ? \Modules\StandardHours\Support\YearlyResultFormatter::yearDateRange(
            $selectedResult->year,
            $selectedResult->declaration_from_date?->toDateString(),
            $selectedResult->declaration_to_date?->toDateString(),
        )
        : null;
@endphp

<div class="max-w-5xl mx-auto space-y-6">
    <div class="lms-card rounded-2xl overflow-hidden">
        <div class="bg-gradient-to-r from-teal-600 to-teal-500 px-6 py-5 text-white">
            <h1 class="text-lg font-bold"><i class="bi bi-award mr-2"></i>Kê khai giờ chuẩn hằng năm</h1>
            <p class="mt-1 text-sm text-teal-50">
                Hệ thống liên kết lịch giảng dạy, hoạt động chuyên môn và NCKH để chốt kết quả theo từng
                {{ mb_strtolower($periodModeLabel) }}.
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3 p-5">
            <a href="{{ route('lms.teach.standard-hours.hub') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-medium transition-colors">
                <i class="bi bi-arrow-left"></i> Về trang Giờ chuẩn
            </a>
            <a href="{{ route('lms.teach.standard-hours.my-results.declaration', $declarationParams) }}"
               class="px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium transition-colors">
                <i class="bi bi-pencil-square"></i> {{ $selectedResult ? 'Cập nhật kê khai' : 'Tạo bảng kê khai' }}
            </a>
        </div>
    </div>

    @if($hasYears && $selectedResult)
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        <div class="lms-card rounded-2xl p-4">
            <p class="text-xs text-slate-500">Tổng giờ chuẩn</p>
            <p class="mt-1 text-xl font-bold text-teal-700">{{ number_format($selectedResult->total_standard_hours, 0) }}</p>
            <p class="mt-1 text-xs text-slate-400">Định mức: {{ number_format($selectedResult->standard_norm_hours, 0) }}</p>
        </div>
        <div class="lms-card rounded-2xl p-4">
            <p class="text-xs text-slate-500">Trực tiếp giảng dạy</p>
            <p class="mt-1 text-xl font-bold text-slate-800">{{ number_format($selectedResult->teaching_hours, 0) }}</p>
            <p class="mt-1 text-xs text-slate-400">Tối thiểu: {{ number_format($selectedResult->min_classroom_hours, 0) }}</p>
        </div>
        <div class="lms-card rounded-2xl p-4">
            <p class="text-xs text-slate-500">Giờ NCKH</p>
            <p class="mt-1 text-xl font-bold text-indigo-700">{{ number_format($selectedResult->research_hours, 0) }}</p>
            <p class="mt-1 text-xs text-slate-400">Định mức: {{ number_format($selectedResult->research_norm_hours, 0) }}</p>
        </div>
        <div class="lms-card rounded-2xl p-4">
            <p class="text-xs text-slate-500">Kết quả</p>
            <p class="mt-1 text-xl font-bold {{ $selectedResult->meets_overall ? 'text-teal-700' : 'text-rose-600' }}">{{ $selectedResult->overall_result_text }}</p>
            <p class="mt-1 text-xs text-slate-400">{{ $selectedResult->status_text }}</p>
        </div>
    </div>

    <div class="lms-card rounded-2xl p-5">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="font-semibold text-slate-900">Xuất báo cáo</h3>
                <p class="mt-1 text-sm text-slate-500">
                    {{ $periodModeLabel }} <strong>{{ $defaultYear ? $periodService->label($defaultYear) : '—' }}</strong>
                    @if($dateRange)
                        ({{ $dateRange['from'] }} → {{ $dateRange['to'] }})
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('lms.teach.standard-hours.my-results.export', $exportParams) }}"
                   class="px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium transition-colors">
                    <i class="bi bi-file-earmark-excel"></i> Xuất Excel
                </a>
                <a href="{{ route('lms.teach.standard-hours.my-results.show', $selectedResult) }}"
                   class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-medium transition-colors">
                    <i class="bi bi-eye"></i> Xem chi tiết
                </a>
            </div>
        </div>
    </div>
    @endif

    <form method="GET" action="{{ route('lms.teach.standard-hours.my-results.index') }}" class="lms-card rounded-2xl p-5">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
            <select name="year" class="border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none">
                <option value="">Chọn {{ mb_strtolower($periodModeLabel) }}</option>
                @foreach($years as $value => $label)
                    <option value="{{ $value }}" {{ (string) $defaultYear === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <input type="date" name="from_date" value="{{ request('from_date') }}" placeholder="Từ ngày"
                   class="border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none">
            <input type="date" name="to_date" value="{{ request('to_date') }}" placeholder="Đến ngày"
                   class="border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none">
            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium transition-colors">Lọc</button>
                <a href="{{ route('lms.teach.standard-hours.my-results.index') }}" class="flex-1 text-center px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-medium transition-colors">Xóa lọc</a>
            </div>
        </div>
    </form>

    <div class="rounded-xl border border-teal-100 bg-teal-50 px-4 py-3 text-sm text-teal-900">
        <i class="bi bi-info-circle mr-1"></i>
        Bộ lọc ngày là khoảng tra cứu độc lập. Kỳ <strong>{{ mb_strtolower($periodModeLabel) }}</strong> vẫn dùng để phân loại và chốt từng hồ sơ.
    </div>

    <div class="lms-card rounded-2xl overflow-hidden">
        @if($results->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left">{{ $periodModeLabel }}</th>
                            <th class="px-4 py-3 text-left">Trực tiếp giảng dạy</th>
                            <th class="px-4 py-3 text-left">HĐ CM</th>
                            <th class="px-4 py-3 text-left">Tổng GC</th>
                            <th class="px-4 py-3 text-left">NCKH</th>
                            <th class="px-4 py-3 text-left">Kết quả</th>
                            <th class="px-4 py-3 text-left">Trạng thái</th>
                            <th class="px-4 py-3 text-left">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($results as $result)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3 font-medium">{{ $result->period_label }}</td>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ number_format($result->teaching_hours, 2) }}</div>
                                <div class="text-xs text-slate-400">
                                    Lịch {{ number_format((float) $result->schedule_teaching_hours, 2) }}
                                    + khác {{ number_format((float) $result->other_teaching_hours, 2) }}
                                </div>
                            </td>
                            <td class="px-4 py-3">{{ number_format($result->conversion_hours, 0) }}</td>
                            <td class="px-4 py-3 font-medium text-teal-700">{{ number_format($result->total_standard_hours, 0) }}</td>
                            <td class="px-4 py-3">{{ number_format($result->research_hours, 0) }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $result->meets_overall ? 'bg-teal-50 text-teal-700' : 'bg-rose-50 text-rose-700' }}">
                                    {{ $result->overall_result_text }}
                                </span>
                            </td>
                            <td class="px-4 py-3">{{ $result->status_text }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('lms.teach.standard-hours.my-results.show', $result) }}" class="text-teal-600 hover:text-teal-800" title="Chi tiết"><i class="bi bi-eye"></i></a>
                                    <a href="{{ route('lms.teach.standard-hours.my-results.export', array_filter(['year' => $result->year, 'from_date' => request('from_date'), 'to_date' => request('to_date')])) }}" class="text-emerald-600 hover:text-emerald-800" title="Xuất Excel"><i class="bi bi-file-earmark-excel"></i></a>
                                    @unless($result->isLocked())
                                        <a href="{{ route('lms.teach.standard-hours.my-results.declaration', ['year' => $result->year]) }}" class="text-amber-600 hover:text-amber-800" title="Cập nhật kê khai"><i class="bi bi-pencil-square"></i></a>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($results->hasPages())
                <div class="px-4 py-3 border-t border-slate-100 flex justify-center">{{ $results->appends(request()->query())->links() }}</div>
            @endif
        @else
            <div class="text-center py-12 px-4">
                <i class="bi bi-calculator text-5xl text-slate-200 mb-4"></i>
                <p class="text-slate-500 mb-2">Chưa có bảng giờ chuẩn được chốt.</p>
                <p class="mb-4 text-sm text-slate-400">Hãy tạo bảng kê khai, chọn đối tượng/chức danh và truy xuất giờ dạy từ Lịch huấn luyện.</p>
                <a href="{{ route('lms.teach.standard-hours.my-results.declaration', $declarationParams) }}"
                   class="inline-flex px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium transition-colors">
                    <i class="bi bi-plus-circle"></i> Tạo bảng kê khai {{ mb_strtolower($periodModeLabel) }} {{ $defaultYear }}
                </a>
            </div>
        @endif
    </div>
</div>
@endsection
