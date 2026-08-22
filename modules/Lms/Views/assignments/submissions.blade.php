@extends('layouts.admin')
@section('title', 'Chấm — '.$assignment->title)
@section('page-title', 'Chấm bài tập')
@section('content')
<a href="{{ route('lms.courses.assignments.index', $course) }}" class="text-sm text-blue-600">← Bài tập</a>
<h1 class="text-xl font-bold mt-2 mb-4">{{ $assignment->title }} <span class="text-sm font-normal text-slate-500">/ max {{ $assignment->max_score }}</span></h1>
<div class="space-y-4">
@forelse($subs as $sub)
    <div class="bg-white border rounded-xl p-4 space-y-2">
        <div class="flex justify-between text-sm">
            <strong>{{ $sub->user?->name }}</strong>
            <span class="text-slate-500">{{ $sub->submitted_at?->format('d/m/Y H:i') }} · {{ $sub->status }}</span>
        </div>
        @if($sub->text_answer)<div class="text-sm bg-slate-50 rounded-lg p-3 whitespace-pre-wrap">{{ $sub->text_answer }}</div>@endif
        @if($sub->file_path)
            <a class="text-sm text-blue-600" href="{{ \Illuminate\Support\Facades\Storage::disk($sub->disk ?? 'public')->url($sub->file_path) }}" target="_blank">📎 {{ $sub->file_name }}</a>
        @endif
        <form method="POST" action="{{ route('lms.courses.assignments.grade', [$course, $assignment, $sub]) }}" class="grid sm:grid-cols-3 gap-2 border-t pt-2">
            @csrf
            <input type="number" step="0.1" name="score" value="{{ $sub->score }}" min="0" max="{{ $assignment->max_score }}" required class="border rounded-lg text-sm px-3 py-2" placeholder="Điểm">
            <input name="feedback" value="{{ $sub->feedback }}" class="border rounded-lg text-sm px-3 py-2" placeholder="Nhận xét">
            <button class="bg-blue-600 text-white rounded-lg text-sm px-3 py-2">Lưu điểm</button>
        </form>
    </div>
@empty
    <p class="text-slate-500">Chưa có bài nộp.</p>
@endforelse
</div>
@endsection
