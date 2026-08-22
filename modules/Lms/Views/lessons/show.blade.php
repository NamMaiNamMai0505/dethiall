@extends('layouts.admin')

@section('title', $lesson->title)
@section('page-title', 'Bài học LMS')

@section('content')
    <x-breadcrumb :items="[
        ['title' => 'LMS', 'url' => route('lms.hub')],
        ['title' => $course->title, 'url' => route('lms.courses.show', $course)],
        ['title' => $lesson->title],
    ]" />

    <div class="flex flex-wrap justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $lesson->title }}</h1>
            <p class="text-sm text-slate-500 mt-1">
                {{ $course->title }}
                @if($lesson->week_number) · Tuần {{ $lesson->week_number }} @endif
                · {{ $lesson->is_published ? 'Công khai' : 'Nháp' }}
            </p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('lms.courses.lessons.index', $course) }}" class="px-4 py-2 border rounded-lg text-sm">Danh sách bài</a>
            @can('lms.edit')
                <a href="{{ route('lms.courses.lessons.edit', [$course, $lesson]) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm">Sửa</a>
            @endcan
        </div>
    </div>

    @if($lesson->summary)
        <div class="bg-blue-50 border border-blue-100 rounded-xl p-4 mb-4 text-sm text-blue-950">{{ $lesson->summary }}</div>
    @endif

    @if($lesson->subjectLesson)
        <p class="text-xs text-slate-500 mb-4">Map khung CTĐT: <strong>{{ $lesson->subjectLesson->display_label }}</strong></p>
    @endif

    <div class="bg-white border rounded-xl shadow-sm p-6 prose max-w-none text-sm text-slate-800 whitespace-pre-wrap">
        {!! nl2br(e($lesson->content ?: 'Chưa có nội dung.')) !!}
    </div>
@endsection
