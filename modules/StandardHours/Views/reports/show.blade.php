@extends('layouts.admin')

@section('title', 'Chi tiết báo cáo GV')
@section('page-title', 'Chi tiết báo cáo GV')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Báo cáo', 'url' => route('standard-hours.reports.index', ['year' => $yearlyResult->year])],
    ['title' => $yearlyResult->instructor->name]
]" />

<x-page-header title="CHI TIẾT BÁO CÁO GIẢNG VIÊN" :actions="[
    ['url' => route('standard-hours.reports.index', array_filter(['year' => $yearlyResult->year, 'report_type' => request('report_type')])), 'label' => 'Quay lại', 'icon' => 'arrow-left', 'color' => 'gray'],
    ['url' => route('standard-hours.calculations.show', $yearlyResult), 'label' => 'Kết quả tính giờ', 'icon' => 'calculator', 'color' => 'blue'],
]" />

@include('standardhours::calculations._detail', ['yearlyResult' => $yearlyResult])
@endsection