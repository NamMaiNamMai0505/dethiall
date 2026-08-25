@extends('layouts.module-portal')
@php($portalHome = route('leave-management.portal'))
@php($portalTitle = 'Cổng quản lý phép')
@php($portalIcon = 'bi-calendar2-check')
@section('title', 'Cổng quản lý phép')
@section('content')
<div class="mb-6 rounded-2xl bg-gradient-to-r from-blue-950 to-indigo-700 p-7 text-white">
    <p class="text-sm font-semibold uppercase tracking-widest text-blue-200">Cổng nghiệp vụ</p>
    <h1 class="mt-2 text-3xl font-extrabold">Quản lý phép</h1>
    <p class="mt-2 max-w-2xl text-blue-100">Quản lý nhân sự, đề xuất nghỉ phép, phê duyệt, hồ sơ và báo cáo trong một trang riêng.</p>
</div>
<div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-3">
    <div class="rounded-xl border border-blue-100 border-l-4 border-l-blue-500 bg-white p-5 shadow-sm"><p class="text-sm text-slate-600">Quân nhân – học viên</p><p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($stats['personnel']) }}</p></div>
    <div class="rounded-xl border border-emerald-100 border-l-4 border-l-emerald-500 bg-white p-5 shadow-sm"><p class="text-sm text-slate-600">Số đơn phép đã duyệt</p><p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($stats['approved']) }}</p><p class="text-xs text-slate-500">đơn</p></div>
    <div class="rounded-xl border border-indigo-100 border-l-4 border-l-indigo-500 bg-white p-5 shadow-sm"><p class="text-sm text-slate-600">Đơn đang chờ duyệt</p><p class="mt-1 text-3xl font-bold text-slate-900">{{ number_format($stats['pending']) }}</p></div>
</div>
<h2 class="mb-4 text-xl font-extrabold text-slate-900">Menu quản lý phép</h2>
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @foreach([
        ['route'=>'leave-management.portal','label'=>'Tổng quan phép','desc'=>'Theo dõi nhanh quy trình nghỉ phép','icon'=>'bi-grid-1x2'],
        ['route'=>'leave-management.personnel','label'=>'Nhân sự phép','desc'=>'Danh sách quân nhân, nhân sự','icon'=>'bi-people'],
        ['route'=>'leave-management.requests','label'=>'Đề xuất nghỉ phép','desc'=>'Tạo và theo dõi đơn phép','icon'=>'bi-file-earmark-plus'],
        ['route'=>'leave-management.approvals','label'=>'Duyệt phép','desc'=>'Xử lý các đơn đang chờ','icon'=>'bi-check2-circle'],
        ['route'=>'leave-management.records','label'=>'Hồ sơ phép','desc'=>'Lưu trữ và tra cứu hồ sơ','icon'=>'bi-archive'],
        ['route'=>'leave-management.reports','label'=>'Báo cáo phép','desc'=>'Tổng hợp ngày phép theo năm','icon'=>'bi-bar-chart-line'],
    ] as $item)
        <a href="{{ route($item['route']) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-md">
            <i class="{{ $item['icon'] }} text-2xl text-blue-700"></i><h2 class="mt-3 font-extrabold">{{ $item['label'] }}</h2><p class="mt-1 text-sm text-slate-500">{{ $item['desc'] }}</p>
        </a>
    @endforeach
</div>
@endsection
