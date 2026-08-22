@extends('layouts.lms-learner')
@section('title', 'Chứng chỉ — '.$course->title)
@section('content')
<a href="{{ route('lms.learn.courses.show', $course) }}" class="text-sm text-teal-700">← Phòng học</a>
<h1 class="text-xl font-bold mt-2 mb-4">Chứng chỉ khóa học</h1>

@if($mine && $mine->isIssued())
    <div class="lms-card p-5 max-w-lg space-y-3">
        <div class="text-emerald-700 font-semibold">Bạn đã được cấp chứng chỉ</div>
        <div class="font-mono text-sm">{{ $mine->code }}</div>
        <div class="text-sm text-slate-600">Điểm {{ $mine->final_score ?? '—' }} · Tiến độ {{ $mine->progress_pct ?? '—' }}%</div>
        <a href="{{ route('lms.learn.certificates.show', [$course, $mine]) }}" class="inline-block px-4 py-2 bg-teal-600 text-white rounded-lg text-sm no-underline">Xem / In chứng chỉ</a>
    </div>
@else
    <div class="lms-card p-5 max-w-lg space-y-3">
        <div class="text-sm text-slate-600">
            Điều kiện: tiến độ ≥ {{ $eligibility['template']->min_progress_pct ?? 80 }}%
            @if(($eligibility['template']->min_score ?? null) !== null)
                · điểm ≥ {{ $eligibility['template']->min_score }}
            @endif
            @if($eligibility['template']->require_survey ?? false)
                · hoàn thành khảo sát
            @endif
        </div>
        <div class="text-sm">Tiến độ hiện tại: <strong>{{ $eligibility['progress_pct'] }}%</strong>
            · Điểm: <strong>{{ $eligibility['final_score'] ?? '—' }}</strong>
        </div>
        @if(!empty($eligibility['reasons']))
            <ul class="text-sm text-amber-800 bg-amber-50 rounded-lg p-3 list-disc pl-5">
                @foreach($eligibility['reasons'] as $r)<li>{{ $r }}</li>@endforeach
            </ul>
        @endif
        @if($eligibility['eligible'])
            <form method="POST" action="{{ route('lms.learn.certificates.request', $course) }}">@csrf
                <button class="px-4 py-2 bg-teal-600 text-white rounded-lg text-sm">Nhận chứng chỉ</button>
            </form>
        @else
            <p class="text-xs text-slate-500">Hoàn thành điều kiện còn lại để nhận chứng chỉ.</p>
        @endif
    </div>
@endif
@endsection
