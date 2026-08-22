@extends('layouts.lms-learner')
@section('title', 'Điểm danh — '.$course->title)
@section('content')
<a href="{{ route('lms.learn.courses.show', $course) }}" class="text-sm text-teal-700">← Phòng học</a>
<h1 class="text-xl font-bold mt-2 mb-4">Điểm danh</h1>
<div class="space-y-3">
@forelse($sessions as $s)
    <div class="lms-card p-4 flex flex-wrap justify-between gap-3 items-center">
        <div>
            <strong>{{ $s->title }}</strong>
            <div class="text-xs text-slate-500">{{ $s->session_date?->format('d/m/Y') }} · {{ $s->mode }} · {{ $s->status }}</div>
        </div>
        @if($s->isOpen() && in_array($s->mode, ['self','qr'], true))
            <form method="POST" action="{{ route('lms.learn.attendance.checkin', [$course, $s]) }}" class="flex gap-2 items-center">
                @csrf
                @if($s->mode === 'qr')
                    <input name="token" placeholder="Nhập token QR" class="border rounded-lg text-sm px-2 py-1.5" required>
                @endif
                <button class="px-3 py-2 rounded-lg bg-teal-600 text-white text-sm">Check-in</button>
            </form>
        @else
            <span class="text-xs text-slate-400">{{ $s->status === 'closed' ? 'Đã đóng' : 'Chờ GV điểm' }}</span>
        @endif
    </div>
@empty
    <p class="text-slate-500">Chưa có buổi điểm danh.</p>
@endforelse
</div>
@endsection
