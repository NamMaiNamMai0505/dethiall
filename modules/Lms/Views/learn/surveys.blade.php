@extends('layouts.lms-learner')
@section('title', 'Khảo sát — '.$course->title)
@section('content')
<a href="{{ route('lms.learn.courses.show', $course) }}" class="text-sm text-teal-700">← Phòng học</a>
<h1 class="text-xl font-bold mt-2 mb-4">Khảo sát chất lượng</h1>
<div class="space-y-3">
@forelse($surveys->where('is_published', true) as $s)
    <a href="{{ route('lms.learn.surveys.show', [$course, $s]) }}" class="lms-card p-4 block no-underline text-slate-900 hover:border-teal-300">
        <strong>{{ $s->title }}</strong>
        <div class="text-xs text-slate-500 mt-1">{{ $s->questions_count }} câu hỏi</div>
    </a>
@empty
    <p class="text-slate-500">Chưa có khảo sát đang mở.</p>
@endforelse
</div>
@endsection
