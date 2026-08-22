@extends('layouts.admin')

@section('title', 'Chi tiết kê khai giờ chuẩn')
@section('page-title', 'Chi tiết kê khai giờ chuẩn')

@section('content')
@php
    $resultScopeParams = [
        'year' => $yearlyResult->year,
        'instructor_id' => auth()->user()?->isInstructor() ? null : $yearlyResult->instructor_id,
    ];
    $resultScopeParams = array_filter($resultScopeParams, fn ($value) => $value !== null);
    $dateRange = \Modules\StandardHours\Support\YearlyResultFormatter::yearDateRange(
        $yearlyResult->year,
        $yearlyResult->declaration_from_date?->toDateString(),
        $yearlyResult->declaration_to_date?->toDateString(),
    );
@endphp

<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Kê khai giờ chuẩn', 'url' => route('standard-hours.my-results.index', $resultScopeParams)],
    ['title' => $yearlyResult->period_label]
]" />

<x-page-header title="CHI TIẾT SỔ GIỜ CHUẨN HẰNG NĂM" :actions="[[
    'url' => route('standard-hours.my-results.index', $resultScopeParams),
    'label' => 'Quay lại', 'icon' => 'arrow-left', 'color' => 'gray',
]]" />

<div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h3 class="font-semibold text-gray-900">Xuất báo cáo</h3>
            <p class="text-sm text-gray-500 mt-1">
                {{ $yearlyResult->period_mode === 'academic_year' ? 'Năm học' : 'Năm' }}
                {{ $yearlyResult->period_label }}
                ({{ $dateRange['from'] }} → {{ $dateRange['to'] }})
            </p>
        </div>
        <a href="{{ route('standard-hours.my-results.export', $resultScopeParams) }}"
           class="{{ \Modules\StandardHours\Support\ActionButton::classes('success') }}">
            <i class="bi bi-file-earmark-excel"></i> Xuất Excel
        </a>
    </div>
</div>

@include('standardhours::calculations._detail', ['yearlyResult' => $yearlyResult])
@endsection
