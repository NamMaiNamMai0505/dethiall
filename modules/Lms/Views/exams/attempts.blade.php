@extends('layouts.admin')
@section('title', 'Lượt thi')
@section('page-title', 'Lượt thi')
@section('content')
<a href="{{ route('lms.courses.exams.index', $course) }}" class="text-sm text-blue-600">← Thi online</a>
<h1 class="text-xl font-bold mt-2 mb-4">{{ $exam->title }}</h1>
<div class="bg-white border rounded-xl overflow-hidden">
<table class="min-w-full text-sm">
<thead class="bg-slate-50"><tr>
<th class="text-left px-4 py-2">HV</th><th class="text-left px-4 py-2">Bắt đầu</th>
<th class="text-left px-4 py-2">Nộp</th><th class="text-left px-4 py-2">Điểm</th>
<th class="text-left px-4 py-2">Proctor</th><th class="text-left px-4 py-2">TT</th>
</tr></thead>
<tbody class="divide-y">
@forelse($rows as $att)
<tr>
<td class="px-4 py-2">{{ $att->user?->name }}</td>
<td class="px-4 py-2">{{ $att->started_at?->format('d/m H:i') }}</td>
<td class="px-4 py-2">{{ $att->submitted_at?->format('d/m H:i') ?? '—' }}</td>
<td class="px-4 py-2 font-semibold">{{ $att->score !== null ? $att->score.'/'.$att->max_score : '—' }}</td>
<td class="px-4 py-2 text-xs">{{ is_array($att->proctor_events) ? count($att->proctor_events) : 0 }} sk</td>
<td class="px-4 py-2">{{ $att->status }}</td>
</tr>
@empty
<tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Chưa có lượt.</td></tr>
@endforelse
</tbody></table></div>
@endsection
