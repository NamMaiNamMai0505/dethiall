@extends('layouts.lms-learner')

@section('title', 'Quy đổi giờ NCKH ↔ HĐCM')

@section('content')
@php
    $balResearch = $balances['research_hours'] ?? 0;
    $balConversion = $balances['conversion_hours'] ?? 0;
@endphp

<div class="max-w-5xl mx-auto space-y-6">
    <div class="lms-card rounded-2xl overflow-hidden">
        <div class="bg-gradient-to-r from-teal-600 to-teal-500 px-6 py-5 text-white flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-bold"><i class="bi bi-arrow-left-right mr-2"></i>Quy đổi giờ NCKH ↔ HĐCM</h1>
                <p class="mt-1 text-sm text-teal-50">Tra cứu số dư giờ NCKH / hoạt động chuyên môn và lịch sử bù giờ.</p>
            </div>
            <a href="{{ route('lms.teach.standard-hours.hub') }}" class="px-4 py-2.5 rounded-xl border border-white/30 text-white hover:bg-white/10 text-sm font-medium transition-colors">
                <i class="bi bi-arrow-left"></i> Về trang Giờ chuẩn
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('lms.teach.standard-hours.hour-exchanges.index') }}" class="lms-card rounded-2xl p-5 flex flex-wrap items-end gap-4">
        <div>
            <label class="mb-2 block font-medium text-slate-800" for="filter_year">{{ $periodModeLabel }}</label>
            <select name="year" id="filter_year" class="border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none">
                @foreach($years as $key => $label)
                    <option value="{{ $key }}" {{ (string) $year === (string) $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <button type="submit" class="px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium transition-colors">
            <i class="bi bi-search"></i> Xem số dư
        </button>
    </form>

    <div class="rounded-xl border border-amber-200 bg-amber-50 text-amber-900 px-4 py-3 text-sm">
        <i class="bi bi-info-circle mr-1"></i>
        Theo Thông tư nội bộ: <strong>3 giờ hành chính NCKH = 1 giờ chuẩn HĐ CM</strong>.
        Việc quyết định bù giờ do cấp quản lý (Khoa/PĐT) thực hiện từng trường hợp và bắt buộc ghi căn cứ —
        đây là trang tra cứu số dư và lịch sử cho giảng viên.
    </div>

    @if(!$balances)
        <div class="lms-card rounded-2xl p-10 text-center text-slate-500">
            <i class="bi bi-arrow-left-right text-4xl text-slate-300 mb-3 block"></i>
            Chọn {{ mb_strtolower($periodModeLabel) }} rồi bấm <strong>Xem số dư</strong>.
        </div>
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="lms-card rounded-2xl border-l-4 border-l-indigo-500 p-5">
                <p class="text-sm text-slate-500">Giờ NCKH hiện có</p>
                <p class="mt-1 text-3xl font-bold text-indigo-700">{{ number_format($balResearch, 2) }} <span class="text-base font-medium text-slate-500">giờ</span></p>
                <p class="mt-2 text-xs text-slate-400">Từ kê khai NCKH đã duyệt ± quy đổi</p>
            </div>
            <div class="lms-card rounded-2xl border-l-4 border-l-teal-500 p-5">
                <p class="text-sm text-slate-500">Giờ HĐ chuyên môn hiện có</p>
                <p class="mt-1 text-3xl font-bold text-teal-700">{{ number_format($balConversion, 2) }} <span class="text-base font-medium text-slate-500">giờ</span></p>
                <p class="mt-2 text-xs text-slate-400">Từ kê khai HĐ CM đã duyệt ± quy đổi</p>
            </div>
        </div>

        <div class="lms-card rounded-2xl overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-200">
                <h3 class="font-semibold text-slate-900">Lịch sử quy đổi — {{ $year }}</h3>
            </div>
            @if($exchanges->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-slate-50 text-slate-600 border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3 text-left">Thời gian</th>
                                <th class="px-4 py-3 text-left">Chiều</th>
                                <th class="px-4 py-3 text-left">Hao hụt</th>
                                <th class="px-4 py-3 text-left">Nhận</th>
                                <th class="px-4 py-3 text-left">Người thực hiện</th>
                                <th class="px-4 py-3 text-left">Ghi chú</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($exchanges as $ex)
                                <tr class="hover:bg-slate-50">
                                    <td class="px-4 py-3 whitespace-nowrap">{{ $ex->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $ex->direction === 'nckh_to_cm' ? 'bg-indigo-100 text-indigo-800' : 'bg-teal-100 text-teal-800' }}">
                                            {{ $ex->direction_text }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-rose-600 font-medium">{{ number_format($ex->source_hours, 2) }} giờ</td>
                                    <td class="px-4 py-3 text-teal-700 font-medium">{{ number_format($ex->target_hours, 2) }} giờ</td>
                                    <td class="px-4 py-3">{{ $ex->creator?->name ?? '—' }}</td>
                                    <td class="px-4 py-3 text-slate-600">{{ $ex->notes ?: '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="p-8 text-center text-slate-500 text-sm">Chưa có lần quy đổi nào trong {{ mb_strtolower($periodModeLabel) }} này.</div>
            @endif
        </div>
    @endif
</div>
@endsection
