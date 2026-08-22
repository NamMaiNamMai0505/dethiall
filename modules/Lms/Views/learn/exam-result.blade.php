@extends('layouts.lms-learner')
@section('title', 'Kết quả thi')
@section('content')
@php
    $maxScore = (float) ($attempt->max_score ?? 0);
    $rawScore = (float) ($attempt->score ?? 0);
    $scale10 = $maxScore > 0 ? round(($rawScore / $maxScore) * 10, 2) : 0;
    $passScore = (float) ($exam->pass_score ?? 5);
    $isGraded = $attempt->status === 'graded';
    $canSeeScore = $canSeeScore ?? true;
    $isPass = $isGraded && $scale10 >= $passScore;
    $statusLabel = match ($attempt->status) {
        'graded' => $isPass ? 'Đạt' : 'Chưa đạt',
        'submitted' => 'Đã nộp — đang chấm',
        'in_progress' => 'Đang làm bài',
        default => $attempt->status,
    };
    $correctCount = $details->where('is_correct', true)->count();
@endphp
<div class="max-w-md mx-auto space-y-4">
    <div class="lms-card rounded-2xl overflow-hidden">
        <div class="px-6 pt-6 pb-8 text-center space-y-1 bg-gradient-to-b from-teal-50 to-white">
            <div class="text-xs text-slate-500">{{ $course->title }}</div>
            <h1 class="text-lg font-bold text-slate-800">{{ $exam->title }}</h1>
        </div>

        <div class="px-6 -mt-6 pb-6 text-center space-y-4">
            <div class="mx-auto flex flex-col items-center justify-center h-36 w-36 rounded-full bg-white shadow-lg ring-8 {{ $isGraded ? ($isPass ? 'ring-teal-100' : 'ring-rose-100') : 'ring-slate-100' }}">
                <div class="text-4xl font-extrabold {{ $isGraded ? ($isPass ? 'text-teal-700' : 'text-rose-600') : 'text-slate-500' }}">{{ number_format($scale10, 2) }}</div>
                <div class="text-xs text-slate-400 -mt-1">/ 10 điểm</div>
            </div>

            @if($isGraded)
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $isPass ? 'bg-teal-50 text-teal-700 border border-teal-200' : 'bg-rose-50 text-rose-700 border border-rose-200' }}">
                    <i class="bi {{ $isPass ? 'bi-check-circle-fill' : 'bi-x-circle-fill' }}"></i>
                    {{ $statusLabel }} (điểm đạt: {{ number_format($passScore, 1) }}/10)
                </span>
                <p class="text-xs text-slate-500">Đúng {{ $correctCount }}/{{ $details->count() }} câu</p>
            @else
                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                    <i class="bi bi-hourglass-split"></i> {{ $statusLabel }}
                </span>
            @endif

            <div class="grid grid-cols-2 gap-3 pt-2">
                <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5">
                    <div class="text-[11px] text-slate-400">Điểm thô</div>
                    <div class="text-sm font-semibold text-slate-700">{{ number_format($rawScore, 2) }} / {{ number_format($maxScore, 2) }}</div>
                </div>
                <div class="rounded-xl border border-slate-100 bg-slate-50 px-3 py-2.5">
                    <div class="text-[11px] text-slate-400">Nộp lúc</div>
                    <div class="text-sm font-semibold text-slate-700">{{ $attempt->submitted_at?->format('d/m/Y H:i') ?? '—' }}</div>
                </div>
            </div>

            <div class="flex flex-col gap-2 pt-2">
                @if($isGraded && $details->isNotEmpty())
                    <button type="button" id="btn-toggle-detail" class="w-full px-4 py-2.5 rounded-xl border border-teal-200 text-teal-700 hover:bg-teal-50 text-sm font-medium transition-colors">
                        <i class="bi bi-list-check"></i> Xem chi tiết bài thi
                    </button>
                @endif
                <a href="{{ route('lms.learn.courses.show', $course) }}" class="inline-block w-full px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium no-underline transition-colors">
                    Về phòng học
                </a>
            </div>
        </div>
    </div>

    @if($isGraded && $details->isNotEmpty())
        <div id="exam-detail" class="space-y-3 hidden">
            @foreach($details as $i => $row)
                <div class="lms-card p-4 rounded-2xl border-l-4 {{ $row['is_correct'] ? 'border-l-teal-500' : 'border-l-rose-500' }}">
                    <div class="flex items-start gap-3">
                        <span class="shrink-0 h-7 w-7 rounded-full text-xs font-bold flex items-center justify-center {{ $row['is_correct'] ? 'bg-teal-50 text-teal-700' : 'bg-rose-50 text-rose-600' }}">
                            <i class="bi {{ $row['is_correct'] ? 'bi-check-lg' : 'bi-x-lg' }}"></i>
                        </span>
                        <div class="min-w-0 flex-1 space-y-1.5">
                            <div class="text-sm font-medium text-slate-800 whitespace-pre-wrap">Câu {{ $i + 1 }}. {{ $row['question']->stem }}</div>
                            <div class="text-xs text-slate-500">
                                Bạn chọn:
                                <span class="font-medium {{ $row['is_correct'] ? 'text-teal-700' : 'text-rose-600' }}">
                                    {{ $row['given_label'] ?? '— (bỏ trống)' }}
                                </span>
                            </div>
                            @if(!$row['is_correct'])
                                <div class="text-xs text-slate-500">
                                    Đáp án đúng: <span class="font-medium text-teal-700">{{ $row['correct_label'] }}</span>
                                </div>
                            @endif
                            <div class="text-[11px] text-slate-400">{{ $row['points'] }} điểm</div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
@push('scripts')
<script>
(function(){
    const btn = document.getElementById('btn-toggle-detail');
    const panel = document.getElementById('exam-detail');
    if (!btn || !panel) return;
    btn.addEventListener('click', function(){
        const open = panel.classList.toggle('hidden');
        btn.innerHTML = open
            ? '<i class="bi bi-list-check"></i> Xem chi tiết bài thi'
            : '<i class="bi bi-chevron-up"></i> Ẩn chi tiết bài thi';
        if (!open) panel.scrollIntoView({behavior: 'smooth', block: 'start'});
    });
})();
if (!@json($canSeeScore)) {
    const resultBody = document.querySelector('.max-w-md > .lms-card > .px-6.-mt-6');
    const scoreCircle = resultBody?.querySelector('.mx-auto.flex.flex-col');
    const scoreGrid = resultBody?.querySelector('.grid.grid-cols-2');
    scoreCircle?.remove();
    scoreGrid?.remove();
    resultBody?.insertAdjacentHTML('afterbegin', '<div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">Bài thi đã được nộp. Điểm sẽ được công bố sau.</div>');
}
</script>
@endpush
@endsection
