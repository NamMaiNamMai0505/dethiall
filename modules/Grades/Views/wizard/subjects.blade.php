@extends('layouts.grades')
@section('title', 'Chọn môn · Quản lý điểm')

@section('content')
<div class="mb-6">
    <p class="text-xs font-bold uppercase tracking-wide text-orange-600/80 mb-1">Bước 1 / 2 · Giảng viên</p>
    <h1 class="text-2xl font-bold text-slate-900">Chọn môn</h1>
    <p class="text-sm text-slate-500 mt-1">Chọn môn bạn đang dạy, sau đó chọn lớp để nhập điểm.</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
    <div class="grades-card p-4">
        <p class="text-xs font-semibold text-slate-500 uppercase">Môn đang dạy</p>
        <p class="text-3xl font-bold text-orange-700 mt-1">{{ $stats['subjects'] }}</p>
    </div>
    <div class="grades-card p-4">
        <p class="text-xs font-semibold text-slate-500 uppercase">Bảng điểm</p>
        <p class="text-3xl font-bold text-teal-700 mt-1">{{ $stats['books'] }}</p>
    </div>
    <div class="grades-card p-4">
        <p class="text-xs font-semibold text-slate-500 uppercase">Chờ duyệt</p>
        <p class="text-3xl font-bold text-amber-700 mt-1">{{ $stats['pending_approve'] }}</p>
    </div>
</div>

@if($subjects->isEmpty())
    <div class="grades-card p-10 text-center text-sm text-slate-500">
        Chưa có môn gắn với lịch dạy / LMS của bạn.
    </div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        @foreach($subjects as $s)
            <a href="{{ route('grades.subjects.classes', $s) }}"
               class="grades-card p-4 block hover:border-orange-300 hover:shadow-md transition border border-transparent no-underline text-inherit">
                <div class="font-bold text-slate-900 text-lg">{{ $s->name }}</div>
                @if($s->code)
                    <div class="text-xs font-mono text-slate-500 mt-0.5">{{ $s->code }}</div>
                @endif
                <div class="mt-3 text-xs">
                    <span class="grades-chip grades-chip-open">{{ (int)($classCountBySubject[$s->id] ?? 0) }} lớp</span>
                </div>
                <div class="mt-3 text-sm font-semibold text-teal-700">Chọn lớp →</div>
            </a>
        @endforeach
    </div>
@endif
@endsection
