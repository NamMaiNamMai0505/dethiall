@extends('layouts.admin')

@section('title', 'Chi tiết kết quả tính giờ')
@section('page-title', 'Chi tiết kết quả tính giờ')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Tính giờ chuẩn', 'url' => route('standard-hours.calculations.index', ['year' => $yearlyResult->year])],
    ['title' => $yearlyResult->instructor->name]
]" />

@php
    $headerActions = [[
        'url' => route('standard-hours.calculations.index', ['year' => $yearlyResult->year]),
        'label' => 'Quay lại', 'icon' => 'arrow-left', 'color' => 'gray',
    ]];
@endphp

<x-page-header title="CHI TIẾT KẾT QUẢ TÍNH GIỜ" :actions="$headerActions" />

@can('standard-hours.calculations.view')
<div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h3 class="font-semibold text-gray-900">Xuất báo cáo giảng viên</h3>
            @php $dateRange = \Modules\StandardHours\Support\YearlyResultFormatter::yearDateRange(
                $yearlyResult->year,
                $yearlyResult->declaration_from_date?->toDateString(),
                $yearlyResult->declaration_to_date?->toDateString()
            ); @endphp
            <p class="text-sm text-gray-500 mt-1">
                {{ $yearlyResult->instructor->name }} —
                {{ $yearlyResult->period_mode === 'academic_year' ? 'Năm học' : 'Năm' }}
                {{ $yearlyResult->period_label }}
                ({{ $dateRange['from'] }} → {{ $dateRange['to'] }})
            </p>
        </div>
        <a href="{{ route('standard-hours.reports.export', [
            'year' => $yearlyResult->year,
            'instructor_id' => $yearlyResult->instructor_id,
        ]) }}"
           class="{{ \Modules\StandardHours\Support\ActionButton::classes('success') }}">
            <i class="bi bi-file-earmark-excel"></i> Xuất Excel
        </a>
    </div>
</div>
@endcan

@include('standardhours::calculations._detail', ['yearlyResult' => $yearlyResult])
@endsection
