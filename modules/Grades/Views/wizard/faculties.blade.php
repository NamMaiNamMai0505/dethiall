@extends('layouts.grades')
@section('title', 'Chọn khoa · Quản lý điểm')

@section('content')
<div class="mb-6">
    <p class="text-xs font-bold uppercase tracking-wide text-teal-700/90 mb-1">Bước 1 / 3 · Phòng đào tạo</p>
    <h1 class="text-2xl font-bold text-slate-900">Chọn khoa</h1>
    <p class="text-sm text-slate-500 mt-1">Duyệt / sửa điểm: Khoa → Môn → Lớp.</p>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
    <div class="grades-card p-4">
        <p class="text-xs font-semibold text-slate-500 uppercase">Khoa</p>
        <p class="text-3xl font-bold text-orange-700 mt-1">{{ $stats['faculties'] }}</p>
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

@if($faculties->isEmpty())
    <div class="grades-card p-10 text-center text-sm text-slate-500">Chưa có khoa / đơn vị.</div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        @foreach($faculties as $f)
            <a href="{{ route('grades.faculties.subjects', $f) }}"
               class="grades-card p-4 block hover:border-orange-300 hover:shadow-md transition border border-transparent no-underline text-inherit">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="font-bold text-slate-900 text-lg">{{ $f->name }}</div>
                        @if($f->code)
                            <div class="text-xs font-mono text-slate-500 mt-0.5">{{ $f->code }}</div>
                        @endif
                    </div>
                    <span class="inline-flex h-12 w-12 min-w-[3rem] items-center justify-center rounded-2xl bg-gradient-to-br from-orange-500 to-teal-600 text-white text-base font-extrabold tracking-wide shadow-md ring-2 ring-orange-200/60"
                          title="{{ $f->code ?: $f->name }}">
                        {{ strtoupper(substr((string)($f->code ?: $f->name), 0, 2)) }}
                    </span>
                </div>
                <div class="mt-3 text-sm font-semibold text-teal-700">Chọn môn →</div>
            </a>
        @endforeach
    </div>
@endif
@endsection
