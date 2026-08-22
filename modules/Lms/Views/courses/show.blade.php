@extends('layouts.admin')

@section('title', $course->title)
@section('page-title', 'Khóa học LMS')

@section('content')
    <x-breadcrumb :items="[
        ['title' => 'Trang chủ'],
        ['title' => 'LMS', 'url' => route('lms.hub')],
        ['title' => 'Khóa học', 'url' => route('lms.courses.index')],
        ['title' => $course->title],
    ]" />

    <div class="flex flex-wrap items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $course->title }}</h1>
            <p class="text-sm text-slate-500 mt-1">
                {{ $course->subject->name ?? '' }}
                · {{ $course->classModel->name ?? '' }}
                · {{ $course->instructor->name ?? 'Chưa gán GV' }}
                @if($course->academicYear) · {{ $course->academicYear->code }} @endif
            </p>
            <span class="inline-flex mt-2 rounded-full px-2.5 py-0.5 text-xs font-semibold
                {{ $course->status === 'published' ? 'bg-green-100 text-green-800' : 'bg-amber-100 text-amber-900' }}">
                {{ $course->statusLabel() }}
            </span>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('lms.courses.lessons.index', $course) }}" class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-medium">
                Bài học ({{ $course->lessons->count() }})
            </a>
            <a href="{{ route('lms.courses.materials.index', $course) }}" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium">Tài liệu / SCORM</a>
            <a href="{{ route('lms.courses.assignments.index', $course) }}" class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-sm font-medium">Bài tập</a>
            <a href="{{ route('lms.courses.exams.index', $course) }}" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-sm font-medium">Thi online</a>
            <a href="{{ route('lms.courses.gradebook', $course) }}" class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-lg text-sm font-medium">Bảng điểm</a>
            <a href="{{ route('lms.courses.attendance.index', $course) }}" class="px-4 py-2 border rounded-lg text-sm">Điểm danh</a>
            <a href="{{ route('lms.courses.progress.index', $course) }}" class="px-4 py-2 border rounded-lg text-sm">Tiến độ</a>
            <a href="{{ route('lms.courses.alerts.index', $course) }}" class="px-4 py-2 border rounded-lg text-sm">Cảnh báo</a>
            <a href="{{ route('lms.courses.certificates.index', $course) }}" class="px-4 py-2 border border-emerald-200 text-emerald-800 rounded-lg text-sm">Chứng chỉ</a>
            <a href="{{ route('lms.courses.surveys.index', $course) }}" class="px-4 py-2 border border-violet-200 text-violet-800 rounded-lg text-sm">Khảo sát</a>
            <a href="{{ route('lms.courses.forum.index', $course) }}" class="px-4 py-2 border rounded-lg text-sm">Diễn đàn</a>
            <a href="{{ route('lms.courses.chat.index', $course) }}" class="px-4 py-2 border rounded-lg text-sm">Chat</a>
            <a href="{{ route('lms.learn.courses.show', $course) }}" class="px-4 py-2 border border-teal-200 text-teal-800 rounded-lg text-sm" title="Xem như học viên">Cổng học</a>
            @can('lms.edit')
                <a href="{{ route('lms.courses.edit', $course) }}" class="px-4 py-2 border rounded-lg text-sm">Sửa</a>
                <form method="POST" action="{{ route('lms.courses.sync-members', $course) }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 border border-blue-200 text-blue-700 rounded-lg text-sm hover:bg-blue-50">Đồng bộ TV</button>
                </form>
                <form method="POST" action="{{ route('lms.courses.sync-content', $course) }}">
                    @csrf
                    <button type="submit" class="px-4 py-2 border border-teal-200 text-teal-700 rounded-lg text-sm hover:bg-teal-50">Đồng bộ bài &amp; lịch</button>
                </form>
            @endcan
        </div>
    </div>

    @if($course->description)
        <div class="bg-white border rounded-xl p-4 mb-6 text-sm text-slate-700 whitespace-pre-line">{{ $course->description }}</div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white border rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b bg-slate-50 font-semibold text-slate-800 flex justify-between">
                <span>Bài học trong khóa</span>
                @can('lms.edit')
                    <a href="{{ route('lms.courses.lessons.create', $course) }}" class="text-sm text-blue-600 font-medium">+ Thêm</a>
                @endcan
            </div>
            <ul class="divide-y max-h-96 overflow-y-auto">
                @forelse($course->lessons as $lesson)
                    <li class="px-4 py-3 hover:bg-slate-50 flex justify-between gap-2">
                        <div>
                            <a href="{{ route('lms.courses.lessons.show', [$course, $lesson]) }}" class="font-medium text-slate-900 hover:text-blue-700">
                                {{ $lesson->sort_order }}. {{ $lesson->title }}
                            </a>
                            @if($lesson->subjectLesson)
                                <p class="text-xs text-slate-500 mt-0.5">Map CTĐT: {{ $lesson->subjectLesson->display_label }}</p>
                            @endif
                            @if($lesson->content_type === 'orphaned')
                                <p class="text-xs text-amber-700 mt-0.5">Nguồn chương trình không còn tồn tại — nội dung LMS được giữ lại.</p>
                            @endif
                        </div>
                        <span class="text-xs {{ $lesson->is_published ? 'text-green-700' : 'text-slate-400' }}">
                            {{ $lesson->is_published ? 'Công khai' : 'Nháp' }}
                        </span>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-slate-500 text-sm">Chưa có bài học LMS.</li>
                @endforelse
            </ul>
        </div>

        <div class="bg-white border rounded-xl shadow-sm overflow-hidden">
            <div class="px-4 py-3 border-b bg-slate-50 font-semibold text-slate-800 flex justify-between items-center">
                <span>Thành viên ({{ $course->members->count() }})
                    @if($course->is_standalone)
                        <span class="ml-1 text-xs font-normal text-violet-700 bg-violet-50 px-2 py-0.5 rounded-full">Lớp HP độc lập</span>
                    @endif
                </span>
            </div>
            <ul class="divide-y max-h-96 overflow-y-auto">
                @forelse($course->members as $member)
                    <li class="px-4 py-2.5 flex justify-between text-sm gap-2">
                        <span>{{ $member->user->name ?? 'User #'.$member->user_id }}</span>
                        <span class="flex items-center gap-2">
                            <span class="text-xs uppercase tracking-wide text-slate-500">{{ $member->role }}</span>
                            @can('lms.edit')
                            <form method="POST" action="{{ route('lms.courses.members.destroy', [$course, $member]) }}" data-confirm="Gỡ thành viên khỏi khóa?" data-confirm-danger="1" data-confirm-title="Gỡ thành viên">
                                @csrf @method('DELETE')
                                <button class="text-xs text-red-600">Gỡ</button>
                            </form>
                            @endcan
                        </span>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-slate-500 text-sm">Chưa có thành viên — bấm Đồng bộ TV hoặc ghi danh thủ công.</li>
                @endforelse
            </ul>
            @can('lms.edit')
            <form method="POST" action="{{ route('lms.courses.members.store', $course) }}" class="p-3 border-t flex flex-wrap gap-2">
                @csrf
                <input type="email" name="email" placeholder="Email học viên / GV" class="flex-1 min-w-[12rem] border rounded-lg text-sm px-3 py-2">
                <select name="role" class="border rounded-lg text-sm px-2 py-2">
                    <option value="student">student</option>
                    <option value="lecturer">lecturer</option>
                    <option value="assistant">assistant</option>
                </select>
                <button class="px-3 py-2 bg-violet-600 text-white rounded-lg text-sm">Ghi danh</button>
            </form>
            @endcan
        </div>
    </div>
@endsection
