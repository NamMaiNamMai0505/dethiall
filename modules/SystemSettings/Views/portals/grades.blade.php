@extends('layouts.grades')

@section('title', 'Cài đặt Quản lý điểm')

@push('styles')
    @include('system-settings::partials.styles')
@endpush

@section('content')
    <div class="mb-6">
        <p class="text-xs font-bold uppercase tracking-wide text-orange-600/80 mb-1">Quản lý điểm</p>
        <h1 class="text-2xl font-bold text-slate-900">Cài đặt Quản lý điểm</h1>
        <p class="text-sm text-slate-500 mt-1">Chuẩn hóa thang điểm, cách hiển thị và năm học của phân hệ điểm.</p>
    </div>

    @php
        $portalLabel = 'Cài đặt nghiệp vụ Quản lý điểm';
        $portalShortLabel = 'Quản lý điểm';
        $portalDescription = 'Thiết lập thang điểm và quy tắc hiển thị theo nhận diện cam - teal của phân hệ.';
        $portalIcon = 'bi-clipboard-data';
    @endphp

    <div class="system-settings system-settings--grades">
        @include('system-settings::partials.body')
    </div>
@endsection
