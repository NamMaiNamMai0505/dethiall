@extends('layouts.lms-learner')
@section('title', 'Cảnh báo — '.$course->title)
@section('content')
<a href="{{ route('lms.learn.courses.show', $course) }}" class="text-sm text-teal-700">← Phòng học</a>
<h1 class="text-xl font-bold mt-2 mb-4">Cảnh báo học tập</h1>
<div class="space-y-3">
@forelse($alerts as $alert)
    <div class="lms-card p-4 {{ $alert->severity === 'critical' ? 'border-rose-300' : '' }}">
        <div class="flex justify-between gap-2">
            <div>
                <div class="text-xs text-slate-400">{{ $alert->severity }}</div>
                <strong>{{ $alert->title }}</strong>
                <p class="text-sm text-slate-600 mt-1">{{ $alert->body }}</p>
            </div>
            @if(!$alert->is_read)
            <form method="POST" action="{{ route('lms.learn.alerts.read', [$course, $alert]) }}">@csrf
                <button class="text-sm text-teal-700">Đã đọc</button>
            </form>
            @endif
        </div>
    </div>
@empty
    <p class="text-slate-500">Không có cảnh báo.</p>
@endforelse
</div>
{{ $alerts->links() }}
@endsection
