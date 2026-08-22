@extends('layouts.lms-learner')

@section('title', 'Bài tập — '.$course->title)

@section('content')
    <a href="{{ route('lms.learn.courses.show', $course) }}" class="text-sm text-teal-700">← Phòng học</a>
    <h1 class="text-xl font-bold mt-2 mb-4">Bài tập</h1>

    <div class="space-y-4">
        @forelse($assignments as $a)
            @php $sub = $mySubs[$a->id] ?? null; @endphp
            <div class="lms-card p-4 space-y-2">
                <div class="flex justify-between gap-2">
                    <strong>{{ $a->title }}</strong>
                    <span class="text-xs text-slate-500">
                        Max {{ $a->max_score }}
                        @if($a->due_at)
                            · Hạn {{ $a->due_at->format('d/m H:i') }}
                        @endif
                    </span>
                </div>

                @if($a->description)
                    <p class="text-sm text-slate-600 whitespace-pre-wrap">{{ $a->description }}</p>
                @endif

                @if($sub)
                    <div class="text-sm rounded-lg px-3 py-2 bg-emerald-50 text-emerald-800">
                        Đã nộp {{ $sub->submitted_at?->format('d/m/Y H:i') }}
                        @if($sub->status === 'graded')
                            · Điểm: <strong>{{ $sub->score }}</strong>
                            @if($sub->feedback)
                                <div class="text-xs mt-1">{{ $sub->feedback }}</div>
                            @endif
                        @endif
                    </div>
                @endif

                @if($a->isOpen() || $a->allow_late)
                    <form method="POST"
                          action="{{ route('lms.learn.assignments.submit', [$course, $a]) }}"
                          enctype="multipart/form-data"
                          class="space-y-2">
                        @csrf
                        <textarea name="text_answer"
                                  rows="3"
                                  class="w-full border rounded-lg text-sm px-3 py-2"
                                  placeholder="Nội dung">{{ old('text_answer', $sub->text_answer ?? '') }}</textarea>
                        <input type="file" name="file" class="text-sm">
                        <p class="text-[11px] text-slate-400">Dung lượng tối đa {{ \Modules\Lms\Support\LmsSettings::submissionMaxMegabytes() }} MB.</p>
                        <button type="submit" class="lms-btn lms-btn-primary">
                            <i class="bi bi-send"></i> {{ $sub ? 'Nộp lại' : 'Nộp bài' }}
                        </button>
                    </form>
                @endif
            </div>
        @empty
            <p class="text-slate-500">Chưa có bài tập.</p>
        @endforelse
    </div>
@endsection
