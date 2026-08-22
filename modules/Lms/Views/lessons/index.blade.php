@extends('layouts.admin')

@section('title', 'Bài học LMS')
@section('page-title', 'Bài học LMS')

@section('content')
    <x-breadcrumb :items="[
        ['title' => 'LMS', 'url' => route('lms.hub')],
        ['title' => $course->title, 'url' => route('lms.courses.show', $course)],
        ['title' => 'Bài học'],
    ]" />

    <div class="flex flex-wrap justify-between items-center gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold">Bài học — {{ $course->title }}</h1>
            <p class="text-sm text-slate-500">Nội dung học tập liên kết chương trình môn; tài liệu và SCORM được quản lý theo từng khóa.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('lms.courses.show', $course) }}" class="px-4 py-2 border rounded-lg text-sm">← Khóa học</a>
            @can('lms.edit')
                <a href="{{ route('lms.courses.lessons.create', $course) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-medium">+ Thêm bài</a>
            @endcan
        </div>
    </div>

    <div class="bg-white border rounded-xl shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-100 text-xs uppercase text-slate-600 text-left">
                <tr>
                    <th class="px-4 py-3">#</th>
                    <th class="px-4 py-3">Tiêu đề</th>
                    <th class="px-4 py-3">Tuần</th>
                    <th class="px-4 py-3">Map CTĐT</th>
                    <th class="px-4 py-3">TT</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($course->lessons as $lesson)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3 font-mono text-xs">{{ $lesson->sort_order }}</td>
                        <td class="px-4 py-3 font-medium">{{ $lesson->title }}</td>
                        <td class="px-4 py-3">{{ $lesson->week_number ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-slate-600">{{ $lesson->subjectLesson->display_label ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <span class="text-xs {{ $lesson->is_published ? 'text-green-700' : 'text-slate-400' }}">
                                {{ $lesson->is_published ? 'Công khai' : 'Nháp' }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('lms.courses.lessons.show', [$course, $lesson]) }}" class="text-blue-600 text-sm font-medium">Xem</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">Chưa có bài học.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
