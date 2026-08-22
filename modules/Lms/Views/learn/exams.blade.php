@extends('layouts.lms-learner')

@section('title', 'Thi — '.$course->title)

@section('content')
    <a href="{{ route('lms.learn.courses.show', $course) }}" class="text-sm text-teal-700">← Phòng học</a>
    <h1 class="text-xl font-bold mt-2 mb-4">Bài thi online</h1>

    <div class="space-y-3">
        @forelse($exams as $exam)
            <div class="lms-card p-4 flex flex-wrap justify-between gap-3 items-center">
                <div>
                    <strong>{{ $exam->title }}</strong>
                    <div class="text-xs text-slate-500">
                        {{ $exam->duration_minutes }} phút · {{ $exam->max_attempts }} lần
                        @if($exam->proctor_basic)
                            · giám sát cơ bản
                        @endif
                    </div>
                </div>

                @if($exam->isOpenNow())
                    <form method="POST" action="{{ route('lms.learn.exams.start', [$course, $exam]) }}">
                        @csrf
                        <button type="submit" class="lms-btn lms-btn-danger">
                            <i class="bi bi-play-fill"></i> Làm bài
                        </button>
                    </form>
                @else
                    <span class="text-xs text-slate-400">Chưa mở / đã đóng</span>
                @endif
            </div>
        @empty
            <p class="text-slate-500">Chưa có bài thi.</p>
        @endforelse
    </div>
@endsection
