@extends('layouts.lms-learner')

@section('title', 'Khóa dạy của tôi')

@section('content')
<div class="mb-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-teal-700 mb-1">Portal giảng viên</p>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Xin chào, {{ $user->name }}</h1>
            <p class="text-sm text-slate-500 mt-1">
                Quản lý các khóa bạn phụ trách — soạn bài, chấm điểm, điểm danh (các sprint tiếp theo).
            </p>
        </div>
        @if(!empty($isAdminPreview))
            <a href="{{ route('lms.hub') }}" class="lms-btn lms-btn-ghost text-sm" data-turbo="false">← Hub admin</a>
        @endif
    </div>
</div>

@if($courses->isEmpty())
    <div class="lms-card p-10 text-center text-slate-500 text-sm">
        <i class="bi bi-easel text-3xl text-slate-300 block mb-3"></i>
        Chưa có khóa LMS được gán cho bạn.
        <p class="mt-2 text-xs">Khi admin/khoa gán bạn làm GV khóa, khóa sẽ hiện tại đây.</p>
    </div>
@else
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($courses as $course)
            @php
                $pending = (int) ($pendingByCourse[$course->id] ?? 0);
                $openExams = (int) ($openExamsByCourse[$course->id] ?? 0);
                $teachUrl = route('lms.learn.courses.show', $course).'?mode=teach';
            @endphp
            <div class="lms-card p-5 flex flex-col">
                <div class="flex flex-wrap gap-1.5 mb-2">
                    <span class="lms-chip lms-chip-teal text-[11px]">
                        {{ $course->status === 'published' ? 'Đang mở' : $course->statusLabel() }}
                    </span>
                    @if($pending > 0)
                        <span class="lms-chip lms-chip-amber text-[11px]">{{ $pending }} chờ chấm</span>
                    @endif
                    @if($openExams > 0)
                        <span class="lms-chip lms-chip-rose text-[11px]">{{ $openExams }} thi mở</span>
                    @endif
                </div>

                <h2 class="text-lg font-bold text-slate-900 leading-snug">{{ $course->title }}</h2>
                <p class="text-sm text-slate-500 mt-2 flex-1">
                    {{ $course->subject->name ?? '' }}
                    @if($course->classModel)
                        · {{ $course->classModel->name }}
                    @endif
                </p>

                <div class="flex flex-wrap gap-3 text-xs text-slate-500 mt-3 mb-4">
                    <span title="Học viên"><i class="bi bi-people"></i> {{ $course->students_count ?? 0 }} HV</span>
                    <span title="Bài học"><i class="bi bi-journal-text"></i> {{ $course->lessons_count ?? 0 }} bài</span>
                    <span class="font-mono text-slate-400">{{ $course->code }}</span>
                </div>

                <div class="flex flex-wrap gap-2 mt-auto">
                    <a href="{{ $teachUrl }}" class="lms-btn-solid flex-1 text-center text-sm" style="padding:0.55rem 0.75rem">
                        <i class="bi bi-easel"></i> Vào lớp (dạy)
                    </a>
                    <a href="{{ route('lms.learn.courses.show', $course) }}"
                       class="lms-btn lms-btn-ghost text-sm"
                       title="Xem như học viên"
                       style="padding:0.55rem 0.75rem">
                        <i class="bi bi-eye"></i>
                    </a>
                </div>
            </div>
        @endforeach
    </div>

    <div class="mt-6">{{ $courses->links() }}</div>
@endif

<div class="lms-card p-4 mt-6 text-sm text-slate-600">
    <div class="font-semibold text-slate-800 mb-1">Portal GV — đã sẵn sàng</div>
    <ul class="list-disc pl-5 space-y-0.5 text-slate-500">
        <li><strong class="text-teal-800">Soạn bài</strong> · <strong class="text-teal-800">Giao &amp; chấm</strong> · <strong class="text-teal-800">Thi online</strong></li>
        <li><strong class="text-teal-800">Điểm danh</strong> · <strong class="text-teal-800">Lớp học</strong> · <strong class="text-teal-800">Tương tác</strong></li>
        <li><strong class="text-teal-800">Lịch dạy</strong> (navbar) · Dashboard giờ chuẩn</li>
    </ul>
</div>
@endsection
