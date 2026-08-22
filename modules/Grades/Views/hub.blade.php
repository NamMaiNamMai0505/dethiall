@extends('layouts.grades')

@section('title', 'Chọn lớp · Quản lý điểm')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-3 mb-6">
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Chọn lớp</h1>
        <p class="text-sm text-slate-500 mt-1">
            Chỉ hiện <strong>lớp bạn đang dạy</strong> (theo lịch đào tạo / LMS). Mỗi lớp kèm <strong>môn</strong> phụ trách.
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        @if(Route::has('export-templates.index'))
            <a href="{{ route('export-templates.index', ['scope' => 'grades']) }}" class="grades-btn grades-btn-teal">
                <i class="bi bi-file-earmark-spreadsheet"></i> Mẫu xuất
            </a>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-6">
    <div class="grades-card p-4">
        <p class="text-xs font-semibold text-slate-500 uppercase">Lớp đang dạy</p>
        <p class="text-3xl font-bold text-orange-700 mt-1">{{ $stats['classes'] }}</p>
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

@if($classes->isEmpty())
    <div class="grades-card p-10 text-center text-slate-500 text-sm">
        Chưa có lớp nào gắn với tài khoản của bạn.
        @if(auth()->user()->isInstructor())
            <p class="mt-2">Lớp hiện khi bạn được xếp lịch dạy (Lịch đào tạo) hoặc phụ trách khóa LMS (lớp + môn).</p>
        @endif
    </div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        @foreach($classes as $c)
            @php
                $nBooks = (int) ($bookCounts[$c->id] ?? 0);
                $nPending = (int) ($pendingByClass[$c->id] ?? 0);
                $subjectNames = $subjectsByClass[$c->id] ?? [];
            @endphp
            <a href="{{ route('grades.classes.show', $c) }}"
               class="grades-card p-4 block hover:border-orange-300 hover:shadow-md transition border border-transparent no-underline text-inherit">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <div class="font-bold text-slate-900 text-lg">{{ $c->name }}</div>
                        @if($c->code)
                            <div class="text-xs text-slate-500 font-mono mt-0.5">{{ $c->code }}</div>
                        @endif
                    </div>
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-gradient-to-br from-orange-500 to-teal-600 text-white shrink-0">
                        <i class="bi bi-people"></i>
                    </span>
                </div>

                @if(count($subjectNames))
                    <div class="mt-3">
                        <p class="text-[10px] font-bold uppercase tracking-wide text-slate-400 mb-1">Môn phụ trách</p>
                        <div class="flex flex-wrap gap-1.5">
                            @foreach($subjectNames as $sn)
                                <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-semibold bg-teal-50 text-teal-800 border border-teal-100">{{ $sn }}</span>
                            @endforeach
                        </div>
                    </div>
                @elseif(auth()->user()?->isSuperAdmin() || auth()->user()?->can('grades.manage'))
                    <p class="mt-3 text-xs text-slate-400">Toàn quyền — mọi môn trong lớp</p>
                @endif

                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    <span class="grades-chip grades-chip-open">{{ $nBooks }} bảng điểm</span>
                    @if($nPending > 0)
                        <span class="grades-chip grades-chip-wait">{{ $nPending }} chờ duyệt</span>
                    @endif
                </div>
                <div class="mt-3 text-sm font-semibold text-teal-700">Vào lớp →</div>
            </a>
        @endforeach
    </div>
@endif
@endsection
