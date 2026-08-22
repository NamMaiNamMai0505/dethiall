@extends('layouts.lms-learner')

@section('title', 'Khóa học của tôi')

@section('content')
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Xin chào, {{ $user->name }}</h1>
        <p class="text-sm text-slate-500 mt-1">Cổng học tập riêng — không dùng bảng điều khiển quản trị. Lịch học có thể mở từ menu trên.</p>
        @if(!empty($isAdminPreview))
            <p class="text-xs mt-2 text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-3 py-2 inline-block">
                Bạn đang xem cổng học bằng tài khoản quản trị (preview). Quản lý course vẫn ở
                <a href="{{ route('lms.hub') }}" class="font-semibold underline">Hub LMS admin</a>.
            </p>
        @endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @forelse($courses as $course)
            <a href="{{ route('lms.learn.courses.show', $course) }}" class="lms-card lms-card-interactive p-5 block no-underline text-inherit">
                <div class="text-xs font-semibold uppercase tracking-wide text-teal-700 mb-2">
                    {{ $course->status === 'published' ? 'Đang mở' : $course->statusLabel() }}
                </div>
                <h2 class="text-lg font-bold text-slate-900 leading-snug">{{ $course->title }}</h2>
                <p class="text-sm text-slate-500 mt-2">
                    {{ $course->subject->name ?? '' }}
                    @if($course->classModel)
                        · {{ $course->classModel->name }}
                    @endif
                </p>
                <p class="text-xs text-slate-400 mt-3">
                    {{ $course->lessons_count ?? 0 }} bài · {{ $course->materials_count ?? 0 }} tài liệu · {{ $course->members_count ?? 0 }} TV
                </p>
            </a>
        @empty
            <div class="lms-card p-8 sm:col-span-2 lg:col-span-3 text-center text-slate-500 text-sm">
                Chưa có khóa học LMS trong phạm vi của bạn.
                @if($user->isStudent())
                    <p class="mt-2">Khi Phòng Đào tạo / Khoa mở course gắn lớp của bạn, khóa sẽ hiện tại đây.</p>
                @endif
            </div>
        @endforelse
    </div>

    <div class="mt-6">{{ $courses->links() }}</div>
@endsection
