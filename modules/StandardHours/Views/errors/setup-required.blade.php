@extends('layouts.admin')

@section('title', 'Giờ chuẩn — cần cấu hình')
@section('page-title', 'Giờ chuẩn giảng viên')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Giờ chuẩn GV', 'url' => route('standard-hours.hub')],
    ['title' => 'Lỗi / Thiết lập']
]" />

<div class="max-w-2xl mx-auto bg-white rounded-xl border shadow-sm p-8 text-center">
    <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center">
        <i class="bi bi-exclamation-triangle text-2xl"></i>
    </div>
    <h1 class="text-xl font-semibold text-gray-900 mb-2">Không mở được chức năng</h1>
    <p class="text-gray-600 text-sm mb-4">{{ $message ?? 'Đã xảy ra lỗi khi tải dữ liệu giờ chuẩn.' }}</p>

    @if(!empty($detail))
        <pre class="text-left text-xs bg-slate-50 border rounded-lg p-3 overflow-x-auto text-slate-700 mb-4 whitespace-pre-wrap">{{ $detail }}</pre>
    @endif

    <div class="flex flex-col sm:flex-row gap-2 justify-center">
        <a href="{{ route('standard-hours.hub') }}"
           class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium">
            <i class="bi bi-arrow-left"></i> Về hub Giờ chuẩn
        </a>
        <a href="{{ route('dashboard') }}"
           class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-800 text-sm font-medium">
            Dashboard
        </a>
    </div>

    <p class="text-xs text-gray-400 mt-6">
        Nếu vừa deploy: chạy migration + <code class="bg-gray-100 px-1 rounded">php artisan permissions:sync</code>
    </p>
</div>
@endsection
