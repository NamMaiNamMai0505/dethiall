@extends('layouts.lms-learner')

@section('title', 'Chi tiết kê khai giờ chuẩn')

@section('content')
@php
    $dateRange = \Modules\StandardHours\Support\YearlyResultFormatter::yearDateRange(
        $yearlyResult->year,
        $yearlyResult->declaration_from_date?->toDateString(),
        $yearlyResult->declaration_to_date?->toDateString(),
    );
@endphp

<div class="max-w-5xl mx-auto space-y-6">
    <div class="lms-card rounded-2xl p-5 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-lg font-bold text-slate-900">Chi tiết sổ giờ chuẩn hằng năm</h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $yearlyResult->period_mode === 'academic_year' ? 'Năm học' : 'Năm' }} {{ $yearlyResult->period_label }}
                ({{ $dateRange['from'] }} → {{ $dateRange['to'] }})
            </p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('lms.teach.standard-hours.my-results.index') }}"
               class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-medium transition-colors">
                <i class="bi bi-arrow-left"></i> Quay lại
            </a>
            <a href="{{ route('lms.teach.standard-hours.my-results.export', ['year' => $yearlyResult->year]) }}"
               class="px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium transition-colors">
                <i class="bi bi-file-earmark-excel"></i> Xuất Excel
            </a>
        </div>
    </div>

    @include('lms::teach.standard-hours.my-results._detail', ['yearlyResult' => $yearlyResult])
</div>
@endsection
