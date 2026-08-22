@extends('layouts.admin')
@section('title', 'Tiến độ — '.$course->title)
@section('page-title', 'Tiến độ học tập')
@section('content')
<a href="{{ route('lms.courses.show', $course) }}" class="text-sm text-blue-600">← {{ $course->title }}</a>
<h1 class="text-xl font-bold mt-2 mb-4">Theo dõi tiến độ</h1>
<div class="bg-white border rounded-xl overflow-hidden">
<table class="min-w-full text-sm">
<thead class="bg-slate-50"><tr>
<th class="text-left px-4 py-2">HV</th>
<th class="text-left px-4 py-2">Bài học</th>
<th class="text-left px-4 py-2">Học liệu</th>
<th class="text-left px-4 py-2">BT</th>
<th class="text-left px-4 py-2">Thi</th>
<th class="text-left px-4 py-2">Tổng</th>
<th class="text-left px-4 py-2">Hoạt động</th>
</tr></thead>
<tbody class="divide-y">
@foreach($summaries as $s)
<tr>
<td class="px-4 py-2">{{ $s->user?->name }}</td>
<td class="px-4 py-2">{{ $s->lessons_done }}/{{ $s->lessons_total }}</td>
<td class="px-4 py-2">{{ $s->materials_done }}/{{ $s->materials_total }}</td>
<td class="px-4 py-2">{{ $s->assignments_done }}/{{ $s->assignments_total }}</td>
<td class="px-4 py-2">{{ $s->exams_done }}/{{ $s->exams_total }}</td>
<td class="px-4 py-2">
    <div class="flex items-center gap-2">
        <div class="w-24 h-2 bg-slate-100 rounded-full overflow-hidden"><div class="h-full bg-teal-500" style="width:{{ min(100, $s->overall_pct) }}%"></div></div>
        <span class="font-semibold">{{ $s->overall_pct }}%</span>
    </div>
</td>
<td class="px-4 py-2 text-xs text-slate-500">{{ $s->last_activity_at?->diffForHumans() ?? '—' }}</td>
</tr>
@endforeach
</tbody>
</table>
</div>
@endsection
