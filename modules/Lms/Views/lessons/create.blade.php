@extends('layouts.admin')

@section('title', 'Thêm bài học LMS')
@section('page-title', 'Thêm bài học LMS')

@section('content')
    <x-breadcrumb :items="[
        ['title' => 'LMS', 'url' => route('lms.hub')],
        ['title' => $course->title, 'url' => route('lms.courses.show', $course)],
        ['title' => 'Thêm bài'],
    ]" />

    <div class="max-w-2xl mx-auto bg-white border rounded-xl shadow-sm p-6">
        <h1 class="text-xl font-bold mb-4">Thêm bài học — {{ $course->title }}</h1>
        <form method="POST" action="{{ route('lms.courses.lessons.store', $course) }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-semibold mb-1">Tiêu đề *</label>
                <input type="text" name="title" value="{{ old('title') }}" required class="w-full border rounded-lg text-sm px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Map bài khung CTĐT (tuỳ chọn)</label>
                <select name="subject_lesson_id" class="w-full border rounded-lg text-sm px-3 py-2">
                    <option value="">— Không map —</option>
                    @foreach($subjectLessons as $sl)
                        <option value="{{ $sl->id }}" @selected(old('subject_lesson_id') == $sl->id)>{{ $sl->display_label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-semibold mb-1">Thứ tự</label>
                    <input type="number" name="sort_order" value="{{ old('sort_order') }}" min="0" class="w-full border rounded-lg text-sm px-3 py-2">
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Tuần</label>
                    <input type="number" name="week_number" value="{{ old('week_number') }}" min="1" max="52" class="w-full border rounded-lg text-sm px-3 py-2">
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Tóm tắt</label>
                <textarea name="summary" rows="2" class="w-full border rounded-lg text-sm px-3 py-2">{{ old('summary') }}</textarea>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Nội dung (text)</label>
                <textarea name="content" rows="6" class="w-full border rounded-lg text-sm px-3 py-2 font-mono">{{ old('content') }}</textarea>
                <p class="text-xs text-slate-500 mt-1">Tài liệu và gói SCORM có thể thêm tại khu vực nội dung của khóa học.</p>
            </div>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="is_published" value="1" class="rounded border-slate-300" @checked(old('is_published'))>
                Công khai cho học viên
            </label>
            <div class="flex justify-end gap-2">
                <a href="{{ route('lms.courses.lessons.index', $course) }}" class="px-4 py-2 border rounded-lg text-sm">Huỷ</a>
                <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold">Lưu</button>
            </div>
        </form>
    </div>
@endsection
