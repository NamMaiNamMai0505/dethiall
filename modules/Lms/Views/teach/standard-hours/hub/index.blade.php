@extends('layouts.lms-learner')

@section('title', 'Giờ chuẩn giảng viên')

@section('content')
<div class="max-w-5xl mx-auto space-y-6">
    <div class="lms-card rounded-2xl overflow-hidden">
        <div class="bg-gradient-to-r from-teal-600 to-teal-500 px-6 py-5 text-white">
            <h1 class="text-lg font-bold"><i class="bi bi-hourglass-split mr-2"></i>Giờ chuẩn giảng viên</h1>
            <p class="mt-1 text-sm text-teal-50">Kê khai hoạt động chuyên môn, NCKH và theo dõi kết quả giờ chuẩn hằng năm của bạn.</p>
        </div>
        <div class="p-5">
            <a href="{{ route('lms.teach.standard-hours.guide') }}"
               class="inline-flex items-center gap-1.5 text-sm font-medium text-teal-700 hover:text-teal-800">
                <i class="bi bi-book"></i> Hướng dẫn sử dụng
            </a>
        </div>
    </div>

    @if(!empty($setupWarning))
        <div class="rounded-xl border border-amber-200 bg-amber-50 text-amber-900 px-4 py-3 text-sm">
            <i class="bi bi-exclamation-triangle mr-1"></i>{{ $setupWarning }}
        </div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="lms-card rounded-2xl p-5">
            <p class="text-sm text-slate-500">Kê khai HĐ chuyên môn</p>
            <p class="mt-1 text-2xl font-bold text-teal-700">{{ $stats['conversion']['approved'] }} <span class="text-sm font-normal text-slate-400">đã thẩm định</span></p>
            <p class="mt-2 text-sm text-slate-500">{{ number_format($stats['conversion']['total_hours'], 0) }} giờ quy đổi</p>
            <p class="mt-1 text-xs text-slate-400">Tổng: {{ $stats['conversion']['total'] }} · Chờ duyệt: {{ $stats['conversion']['submitted'] }}</p>
        </div>
        <div class="lms-card rounded-2xl p-5">
            <p class="text-sm text-slate-500">Kê khai NCKH</p>
            <p class="mt-1 text-2xl font-bold text-indigo-700">{{ $stats['research']['approved'] }} <span class="text-sm font-normal text-slate-400">đã thẩm định</span></p>
            <p class="mt-2 text-sm text-slate-500">{{ number_format($stats['research']['total_hours'], 0) }} giờ quy đổi</p>
            <p class="mt-1 text-xs text-slate-400">Tổng: {{ $stats['research']['total'] }} · Chờ duyệt: {{ $stats['research']['submitted'] }}</p>
        </div>
        <div class="lms-card rounded-2xl p-5">
            <p class="text-sm text-slate-500">Hoạt động ngoài HĐCM</p>
            <p class="mt-1 text-2xl font-bold text-amber-700">{{ $stats['external']['approved'] }} <span class="text-sm font-normal text-slate-400">đã duyệt</span></p>
            <p class="mt-2 text-sm text-slate-500">Theo dõi riêng · không cộng giờ chuẩn</p>
            <p class="mt-1 text-xs text-slate-400">Tổng: {{ $stats['external']['total'] }} · Chờ duyệt: {{ $stats['external']['submitted'] }}</p>
        </div>
    </div>

    <div>
        <h2 class="mb-3 text-base font-semibold text-slate-800">Chức năng</h2>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <a href="{{ route('lms.teach.standard-hours.my-results.index') }}"
               class="lms-card lms-card-interactive rounded-2xl p-5 flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-700"><i class="bi bi-award text-xl"></i></span>
                <span>
                    <span class="block font-semibold text-slate-900">Kê khai giờ chuẩn</span>
                    <span class="mt-1 block text-xs text-slate-500">Xem/kê khai bảng giờ chuẩn hằng năm</span>
                </span>
            </a>
            <a href="{{ route('lms.teach.standard-hours.conversion-records.index') }}"
               class="lms-card lms-card-interactive rounded-2xl p-5 flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50 text-blue-700"><i class="bi bi-clipboard-check text-xl"></i></span>
                <span>
                    <span class="block font-semibold text-slate-900">Kê khai HĐ chuyên môn</span>
                    <span class="mt-1 block text-xs text-slate-500">Kê khai, theo dõi hoạt động chuyên môn quy đổi giờ chuẩn</span>
                </span>
            </a>
            <a href="{{ route('lms.teach.standard-hours.research-records.index') }}"
               class="lms-card lms-card-interactive rounded-2xl p-5 flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700"><i class="bi bi-mortarboard text-xl"></i></span>
                <span>
                    <span class="block font-semibold text-slate-900">Kê khai NCKH</span>
                    <span class="mt-1 block text-xs text-slate-500">Kê khai, theo dõi sản phẩm/hoạt động nghiên cứu khoa học</span>
                </span>
            </a>
            <a href="{{ route('lms.teach.standard-hours.external-activities.index') }}"
               class="lms-card lms-card-interactive rounded-2xl p-5 flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700"><i class="bi bi-activity text-xl"></i></span>
                <span>
                    <span class="block font-semibold text-slate-900">Hoạt động ngoài HĐCM</span>
                    <span class="mt-1 block text-xs text-slate-500">Theo dõi hoạt động ngoài danh mục — không cộng giờ chuẩn</span>
                </span>
            </a>
            <a href="{{ route('lms.teach.standard-hours.hour-exchanges.index') }}"
               class="lms-card lms-card-interactive rounded-2xl p-5 flex items-start gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-teal-50 text-teal-700"><i class="bi bi-arrow-left-right text-xl"></i></span>
                <span>
                    <span class="block font-semibold text-slate-900">Quy đổi giờ NCKH ↔ HĐCM</span>
                    <span class="mt-1 block text-xs text-slate-500">Tra cứu số dư và lịch sử bù giờ</span>
                </span>
            </a>
        </div>
    </div>
</div>
@endsection
