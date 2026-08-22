@extends('layouts.lms-learner')

@section('title', 'Cài đặt LMS')

@push('styles')
    @include('system-settings::partials.styles')
@endpush

@section('content')
    <div class="mb-6">
        <p class="text-xs font-semibold uppercase tracking-wide text-teal-700 mb-1">Không gian LMS</p>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Cài đặt LMS</h1>
        <p class="text-sm text-slate-500 mt-1">Thiết lập mặc định cho khóa học và dữ liệu năm học dùng chung.</p>
    </div>

    @php
        $portalLabel = 'Cài đặt vận hành LMS';
        $portalShortLabel = 'LMS';
        $portalDescription = 'Giao diện và cấu hình này chỉ phục vụ không gian dạy - học trực tuyến.';
        $portalIcon = 'bi-mortarboard';
    @endphp

    <div class="system-settings system-settings--lms">
        @include('system-settings::partials.body')
    </div>
@endsection
