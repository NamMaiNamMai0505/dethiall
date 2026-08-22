@extends('layouts.admin')

@section('title', 'Cài đặt Dashboard')
@section('page-title', 'Cài đặt Dashboard')

@push('styles')
    @include('system-settings::partials.styles')
@endpush

@section('content')
    <x-breadcrumb :items="[
        ['title' => 'Trang chủ', 'url' => route('dashboard')],
        ['title' => 'Cài đặt Dashboard'],
    ]" />

    <x-page-header
        title="CÀI ĐẶT DASHBOARD"
        subtitle="Cấu hình hệ thống dùng chung và danh mục năm học tập trung"
    />

    @php
        $portalLabel = 'Cài đặt hệ thống Dashboard';
        $portalShortLabel = 'Dashboard';
        $portalDescription = 'Quản lý dữ liệu nền dùng chung từ giao diện quản trị trung tâm.';
        $portalIcon = 'bi-speedometer2';
    @endphp

    <div class="system-settings system-settings--dashboard">
        @include('system-settings::partials.body')
    </div>
@endsection
