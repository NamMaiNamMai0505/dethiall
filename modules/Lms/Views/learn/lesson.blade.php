@extends('layouts.lms-learner')

@section('title', $lesson->title)

@section('content')
    <div class="mb-4">
        <a href="{{ route('lms.learn.courses.show', $course) }}" class="text-sm text-teal-700 hover:underline">← {{ $course->title }}</a>
    </div>

    <article class="lms-card p-6 mb-5">
        <h1 class="text-2xl font-bold">{{ $lesson->title }}</h1>
        @if($lesson->summary)
            <p class="text-sm text-slate-600 mt-2">{{ $lesson->summary }}</p>
        @endif
        <div class="mt-5 text-sm text-slate-800 whitespace-pre-wrap leading-relaxed">
            {!! nl2br(e($lesson->content ?: 'Chưa có nội dung chi tiết.')) !!}
        </div>
    </article>

    @if($materials->isNotEmpty())
        <section class="lms-card overflow-hidden">
            <div class="px-4 py-3 border-b font-semibold">Tài liệu liên quan</div>
            <ul class="divide-y">
                @foreach($materials as $m)
                    <li class="px-4 py-3 flex justify-between text-sm">
                        <span>{{ $m->title }} <span class="text-slate-400">({{ $m->kindLabel() }})</span></span>
                        <a href="{{ route('lms.learn.materials.open', [$course, $m]) }}" class="text-teal-700 font-medium" target="_blank">Tải / mở</a>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
