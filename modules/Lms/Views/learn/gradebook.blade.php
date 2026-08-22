@extends('layouts.lms-learner')
@section('title', 'Điểm — '.$course->title)
@section('content')
<a href="{{ route('lms.learn.courses.show', $course) }}" class="text-sm text-teal-700">← Phòng học</a>
<h1 class="text-xl font-bold mt-2 mb-4">Bảng điểm của tôi</h1>
@php $r = $rows[auth()->id()] ?? null; @endphp
@if(!$r)
    <p class="text-slate-500">Chưa có dữ liệu điểm.</p>
@else
<div class="lms-card p-6 space-y-4 max-w-xl">
    <div class="grid grid-cols-2 gap-3 text-sm">
        <div class="rounded-lg bg-slate-50 p-3"><div class="text-xs text-slate-500">TB bài tập</div><div class="text-lg font-bold">{{ $r['assignment_avg'] ?? '—' }}</div></div>
        <div class="rounded-lg bg-slate-50 p-3"><div class="text-xs text-slate-500">TB thi</div><div class="text-lg font-bold">{{ $r['exam_avg'] ?? '—' }}</div></div>
        <div class="rounded-lg bg-slate-50 p-3"><div class="text-xs text-slate-500">Chuyên cần</div><div class="text-lg font-bold">{{ $r['attendance_pct'] !== null ? $r['attendance_pct'].'%' : '—' }}</div></div>
        <div class="rounded-lg bg-slate-50 p-3"><div class="text-xs text-slate-500">Tiến độ</div><div class="text-lg font-bold">{{ $r['progress_pct'] !== null ? $r['progress_pct'].'%' : '—' }}</div></div>
    </div>
    <div class="text-center py-4 border-t">
        <div class="text-sm text-slate-500">Điểm tổng hợp</div>
        <div class="text-4xl font-bold text-teal-700">{{ $r['final_score'] ?? $r['computed_score'] ?? '—' }}</div>
        @if($r['letter'])<div class="text-sm mt-1">Xếp loại: <strong>{{ $r['letter'] }}</strong></div>@endif
        @if($r['note'])<p class="text-xs text-slate-500 mt-2">{{ $r['note'] }}</p>@endif
    </div>
    <div class="text-sm space-y-1 border-t pt-3">
        <div class="font-medium mb-1">Chi tiết bài tập</div>
        @foreach($assignments as $a)
            @php $c = $r['assignment_cells'][$a->id] ?? null; @endphp
            <div class="flex justify-between"><span>{{ $a->title }}</span><span>{{ $c && $c['score'] !== null ? $c['score'].'/'.$a->max_score : ($c ? 'Đã nộp' : '—') }}</span></div>
        @endforeach
        <div class="font-medium mt-3 mb-1">Chi tiết thi</div>
        @foreach($exams as $e)
            @php $c = $r['exam_cells'][$e->id] ?? null; @endphp
            <div class="flex justify-between"><span>{{ $e->title }}</span><span>{{ $c ? $c['score'].'/'.$c['max'] : '—' }}</span></div>
        @endforeach
    </div>
</div>
@endif
@endsection
