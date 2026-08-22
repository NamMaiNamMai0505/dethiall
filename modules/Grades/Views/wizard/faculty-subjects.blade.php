@extends('layouts.grades')
@section('title', 'Chọn môn · '.$unit->name)

@section('content')
<div class="mb-6">
    <a href="{{ route('grades.hub') }}" class="text-sm text-teal-700 font-semibold hover:underline">← Chọn khoa</a>
    <p class="text-xs font-bold uppercase tracking-wide text-teal-700/90 mt-2 mb-1">Bước 2 / 3 · {{ $unit->name }}</p>
    <h1 class="text-2xl font-bold text-slate-900">Chọn môn</h1>
    <p class="text-sm text-slate-500 mt-1">Môn thuộc khoa, sau đó chọn lớp để duyệt / sửa điểm.</p>
</div>

@if($subjects->isEmpty())
    <div class="grades-card p-10 text-center text-sm text-slate-500">Chưa có môn trong khoa này.</div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        @foreach($subjects as $s)
            <a href="{{ route('grades.faculties.classes', [$unit, $s]) }}"
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
