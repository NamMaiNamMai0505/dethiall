@extends('layouts.admin')
@section('title', 'Cảnh báo học tập')
@section('page-title', 'Cảnh báo học tập')
@section('content')
<div class="flex flex-wrap justify-between gap-2 mb-4">
    <div>
        <a href="{{ route('lms.courses.show', $course) }}" class="text-sm text-blue-600">← {{ $course->title }}</a>
        <h1 class="text-xl font-bold mt-1">Cảnh báo học tập</h1>
    </div>
    <form method="POST" action="{{ route('lms.courses.alerts.evaluate', $course) }}">@csrf
        <button class="px-3 py-2 bg-amber-600 text-white rounded-lg text-sm">Quét / cập nhật cảnh báo</button>
    </form>
</div>
<div class="space-y-2">
@forelse($alerts as $alert)
    <div class="bg-white border rounded-xl p-4 flex flex-wrap justify-between gap-3
        {{ $alert->severity === 'critical' ? 'border-rose-300' : ($alert->severity === 'warning' ? 'border-amber-300' : '') }}">
        <div>
            <div class="text-xs uppercase tracking-wide text-slate-400">{{ $alert->type }} · {{ $alert->severity }}</div>
            <div class="font-semibold">{{ $alert->title }} — {{ $alert->user?->name }}</div>
            <p class="text-sm text-slate-600">{{ $alert->body }}</p>
            @if($alert->resolved_at)<p class="text-xs text-emerald-600 mt-1">Đã xử lý {{ $alert->resolved_at->format('d/m H:i') }}</p>@endif
        </div>
        @if(!$alert->resolved_at)
        <form method="POST" action="{{ route('lms.courses.alerts.resolve', [$course, $alert]) }}">@csrf
            <button class="text-sm text-blue-600">Đánh dấu xử lý</button>
        </form>
        @endif
    </div>
@empty
    <p class="text-slate-500">Chưa có cảnh báo. Bấm quét để đánh giá tiến độ / chuyên cần / BT.</p>
@endforelse
</div>
{{ $alerts->links() }}
@endsection
