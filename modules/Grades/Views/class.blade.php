@extends('layouts.grades')

@section('title', $class->name.' · Điểm')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-3 mb-6">
    <div>
        <a href="{{ route('grades.hub') }}" class="text-sm text-teal-700 font-semibold hover:underline">← Tất cả lớp</a>
        <h1 class="text-2xl font-bold text-slate-900 mt-1">{{ $class->name }}</h1>
        <p class="text-sm text-slate-500 mt-1">
            @if($class->code)<span class="font-mono">{{ $class->code }}</span> · @endif
            Điểm theo <strong>lớp + môn</strong> bạn phụ trách
        </p>
        @if(isset($subjects) && $subjects->isNotEmpty())
            <div class="mt-2 flex flex-wrap gap-1.5">
                @foreach($subjects as $sid => $sname)
                    <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-semibold bg-teal-50 text-teal-800 border border-teal-100">{{ $sname }}</span>
                @endforeach
            </div>
        @endif
    </div>
    <div class="flex flex-wrap gap-2">
        <a href="{{ route('grades.books.create', ['class_id' => $class->id]) }}" class="grades-btn grades-btn-solid">
            <i class="bi bi-plus-lg"></i> Tạo bảng điểm
        </a>
        <a href="{{ route('grades.books.index', ['class_id' => $class->id]) }}" class="grades-btn grades-btn-ghost">Danh sách</a>
    </div>
</div>

@if($pendingRequests->count())
    <div class="grades-card p-4 mb-4 text-sm space-y-2">
        <div class="font-semibold text-slate-800">Ý kiến mở khóa (lớp này)</div>
        @foreach($pendingRequests as $r)
            <div class="border border-slate-100 rounded-lg p-3">
                <div class="text-xs text-slate-500">{{ $r->requester?->name }} · {{ $r->statusLabel() }}</div>
                <div class="font-medium">{{ $r->book?->title }}</div>
                <p class="text-slate-700 mt-1">{{ \Illuminate\Support\Str::limit($r->reason, 140) }}</p>
                <a href="{{ route('grades.books.show', $r->grade_book_id) }}" class="text-teal-700 text-xs font-semibold">Xử lý →</a>
            </div>
        @endforeach
    </div>
@endif

<div class="grades-card overflow-hidden">
    <div class="px-4 py-3 border-b border-slate-100 font-semibold text-slate-800">Bảng điểm · Lớp / Môn</div>
    @if($books->isEmpty())
        <div class="px-4 py-10 text-center text-sm text-slate-500">
            Chưa có bảng điểm cho các môn bạn dạy trong lớp này.
            <a href="{{ route('grades.books.create', ['class_id' => $class->id]) }}" class="text-orange-700 font-semibold">Tạo bảng đầu tiên</a>
        </div>
    @else
        <ul class="divide-y divide-slate-100">
            @foreach($books as $b)
                <li>
                    <a href="{{ route('grades.books.show', $b) }}" class="block px-4 py-3 hover:bg-orange-50/50">
                        <div class="font-medium text-slate-900">{{ $b->title }}</div>
                        <div class="text-xs text-slate-500 mt-0.5">
                            <span class="font-semibold text-teal-800">{{ $b->subject?->name ?? '—' }}</span>
                            @if($b->instructor) · GV {{ $b->instructor->name }} @endif
                            · <span class="grades-chip grades-chip-{{ $b->status === 'approved' ? 'ok' : ($b->isLocked() ? 'lock' : 'open') }}">{{ $b->statusLabel() }}</span>
                        </div>
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</div>
@endsection
