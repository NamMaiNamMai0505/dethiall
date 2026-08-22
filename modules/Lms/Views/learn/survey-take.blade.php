@extends('layouts.lms-learner')
@section('title', $survey->title)
@section('content')
<a href="{{ route('lms.learn.courses.show', $course) }}?tab=surveys" class="text-sm text-teal-700 hover:underline">← Phòng học · Khảo sát</a>

<div class="lms-card p-5 mt-3 max-w-xl">
    <h1 class="text-xl font-bold text-slate-900">{{ $survey->title }}</h1>
    @if($survey->description)
        <p class="text-sm text-slate-500 mt-1">{{ $survey->description }}</p>
    @endif

    @if($my)
        <p class="text-sm text-emerald-700 mt-4">Bạn đã gửi khảo sát lúc {{ $my->submitted_at?->format('d/m/Y H:i') }}.</p>
    @elseif($survey->isOpen())
        <form method="POST" action="{{ route('lms.learn.surveys.submit', [$course, $survey]) }}" class="mt-4 space-y-4">
            @csrf
            @foreach($survey->questions as $q)
                <div class="rounded-xl border border-slate-100 p-4 bg-slate-50/50">
                    <div class="text-sm font-medium mb-2">{{ $q->stem }}
                        @if($q->is_required)<span class="text-rose-500">*</span>@endif
                    </div>
                    @if($q->type === 'rating_1_5')
                        <div class="lms-stars" role="radiogroup">
                            @for($i = 5; $i >= 1; $i--)
                                <input type="radio" id="t-{{ $q->id }}-{{ $i }}" name="answers[{{ $q->id }}]" value="{{ $i }}" {{ $q->is_required ? 'required' : '' }}>
                                <label for="t-{{ $q->id }}-{{ $i }}">★</label>
                            @endfor
                        </div>
                    @elseif($q->type === 'mcq' && is_array($q->options))
                        <div class="space-y-1.5 text-sm">
                            @foreach($q->options as $opt)
                                <label class="flex items-center gap-2"><input type="radio" name="answers[{{ $q->id }}]" value="{{ $opt }}" {{ $q->is_required ? 'required' : '' }}> {{ $opt }}</label>
                            @endforeach
                        </div>
                    @else
                        <textarea name="answers[{{ $q->id }}]" rows="2" class="w-full border rounded-lg text-sm px-3 py-2" {{ $q->is_required ? 'required' : '' }}></textarea>
                    @endif
                </div>
            @endforeach
            <button type="submit" class="lms-btn-solid">Gửi đánh giá</button>
        </form>
    @else
        <p class="text-sm text-slate-400 mt-3">Khảo sát chưa mở / đã đóng.</p>
    @endif
</div>
@endsection
