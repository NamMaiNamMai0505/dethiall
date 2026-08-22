@extends('layouts.grades')
@section('title', 'Chọn lớp · '.$subject->name)

@section('content')
@php
    $isPdot = ($mode ?? '') === 'pdot';
@endphp
<div class="mb-6">
    @if($isPdot && $unit)
        <a href="{{ route('grades.faculties.subjects', $unit) }}" class="text-sm text-teal-700 font-semibold hover:underline">← Chọn môn</a>
        <p class="text-xs font-bold uppercase tracking-wide text-teal-700/90 mt-2 mb-1">
            Bước 3 / 3 · {{ $unit->name }} · {{ $subject->name }}
        </p>
    @else
        <a href="{{ route('grades.hub') }}" class="text-sm text-teal-700 font-semibold hover:underline">← Chọn môn</a>
        <p class="text-xs font-bold uppercase tracking-wide text-orange-600/80 mt-2 mb-1">
            Bước 2 / 2 · {{ $subject->name }}
        </p>
    @endif
    <h1 class="text-2xl font-bold text-slate-900">Chọn lớp</h1>
    <p class="text-sm text-slate-500 mt-1">
        Môn: <strong>{{ $subject->name }}</strong>
        @if($subject->code) <span class="font-mono text-xs">({{ $subject->code }})</span> @endif
    </p>
</div>

@if($classes->isEmpty())
    <div class="grades-card p-10 text-center text-sm text-slate-500">Chưa có lớp gắn với môn này.</div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        @foreach($classes as $c)
            @php
                $url = $isPdot && $unit
                    ? route('grades.faculties.room', [$unit, $subject, $c])
                    : route('grades.room', [$subject, $c]);
            @endphp
            <a href="{{ $url }}"
               class="grades-card p-4 block hover:border-orange-300 hover:shadow-md transition border border-transparent no-underline text-inherit">
                <div class="font-bold text-slate-900 text-lg">{{ $c->name }}</div>
                @if($c->code)
                    <div class="text-xs font-mono text-slate-500 mt-0.5">{{ $c->code }}</div>
                @endif
                <div class="mt-3 flex flex-wrap gap-2 text-xs">
                    <span class="grades-chip grades-chip-open">{{ (int)($bookCounts[$c->id] ?? 0) }} bảng điểm</span>
                    <span class="inline-flex px-2 py-0.5 rounded-md text-xs font-semibold bg-teal-50 text-teal-800 border border-teal-100">{{ $subject->name }}</span>
                </div>
                <div class="mt-3 text-sm font-semibold text-teal-700">Vào nhập / duyệt điểm →</div>
            </a>
        @endforeach
    </div>
@endif
@endsection
