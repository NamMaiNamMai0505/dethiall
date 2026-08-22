@extends('layouts.admin')

@section('title', 'Sửa bài học LMS')
@section('page-title', 'Sửa bài học LMS')

@section('content')
    <x-breadcrumb :items="[
        ['title' => 'LMS', 'url' => route('lms.hub')],
        ['title' => $course->title, 'url' => route('lms.courses.show', $course)],
        ['title' => 'Sửa bài'],
    ]" />

    <div class="max-w-2xl mx-auto bg-white border rounded-xl shadow-sm p-6">
        <h1 class="text-xl font-bold mb-4">Sửa bài học</h1>
        <form method="POST" action="{{ route('lms.courses.lessons.update', [$course, $lesson]) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-semibold mb-1">Tiêu đề *</label>
                <input type="text" name="title" value="{{ old('title', $lesson->title) }}" required class="w-full border rounded-lg text-sm px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Map bài khung CTĐT</label>
                <select name="subject_lesson_id" class="w-full border rounded-lg text-sm px-3 py-2">
                    <option value="">— Không map —</option>
                    @foreach($subjectLessons as $sl)
                        <option value="{{ $sl->id }}" @selected(old('subject_lesson_id', $lesson->subject_lesson_id) == $sl->id)>{{ $sl->display_label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-semibold mb-1">Thứ tự</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order', $lesson->sort_order) }}" class="w-full border rounded-lg text-sm px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Tuần</label>
                    <input type="number" name="week_number" value="{{ old('week_number', $lesson->week_number) }}" class="w-full border rounded-lg text-sm px-3 py-2">
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Tóm tắt</label>
                <textarea name="summary" rows="2" class="w-full border rounded-lg text-sm px-3 py-2">{{ old('summary', $lesson->summary) }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Nội dung</label>
                <textarea name="content" rows="8" class="w-full border rounded-lg text-sm px-3 py-2 font-mono">{{ old('content', $lesson->content) }}</textarea>
            </div>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_published" value="1" class="rounded border-slate-300" @checked(old('is_published', $lesson->is_published))>
                Công khai
            </label>
            <div class="flex justify-between pt-2">
                @can('lms.edit')
                    <button type="submit" form="del-lesson" class="text-red-600 text-sm">Xoá</button>
                @endcan
                <div class="flex gap-2">
                    <a href="{{ route('lms.courses.lessons.show', [$course, $lesson]) }}" class="px-4 py-2 border rounded-lg text-sm">Huỷ</a>
                    <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold">Lưu</button>
                </div>
            </div>
        </form>
        <form id="del-lesson" method="POST" action="{{ route('lms.courses.lessons.destroy', [$course, $lesson]) }}" class="hidden"
              data-confirm="Xoá bài học này?"
              data-confirm-danger="1"
              data-confirm-title="Xoá bài học"
              data-confirm-ok="Xoá">
            @csrf
            @method('DELETE')
        </form>
    </div>
@endsection
