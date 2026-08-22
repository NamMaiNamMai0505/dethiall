@extends('layouts.lms-learner')

@section('title', $course->title)

@section('content')
@php
    $teachMode = !empty($teachMode);
    $canTeach = !empty($canTeach);
    $backHome = ($teachMode || \Modules\Lms\Support\LmsAccess::isInstructorUser())
        ? route('lms.teach.home')
        : route('lms.learn.home');

    $tabs = [
        'overview' => ['label' => 'Tổng quan', 'icon' => 'bi-grid'],
        'lessons' => ['label' => 'Bài học', 'icon' => 'bi-journal-text'],
        'materials' => ['label' => 'Tài liệu', 'icon' => 'bi-folder2-open'],
        'assignments' => ['label' => 'Bài tập', 'icon' => 'bi-pencil-square'],
        'exams' => ['label' => 'Thi', 'icon' => 'bi-ui-checks-grid'],
        'attendance' => ['label' => 'Điểm danh', 'icon' => 'bi-calendar-check'],
        'progress' => ['label' => 'Tiến độ', 'icon' => 'bi-graph-up'],
        'grades' => ['label' => 'Điểm', 'icon' => 'bi-bar-chart'],
        'certificates' => ['label' => 'Chứng chỉ', 'icon' => 'bi-award'],
        'surveys' => ['label' => 'Khảo sát', 'icon' => 'bi-clipboard-data'],
        'forum' => ['label' => 'Diễn đàn', 'icon' => 'bi-chat-dots'],
        'chat' => ['label' => 'Chat', 'icon' => 'bi-chat-left-text'],
    ];
    if ($teachMode) {
        $tabs = array_merge(
            [
                'author' => ['label' => 'Soạn bài', 'icon' => 'bi-journal-plus'],
                'assign' => ['label' => 'Giao & chấm', 'icon' => 'bi-clipboard-check'],
                'exam' => ['label' => 'Thi online', 'icon' => 'bi-ui-checks-grid'],
                'attend' => ['label' => 'Điểm danh lớp', 'icon' => 'bi-calendar-check'],
                'class' => ['label' => 'Lớp học', 'icon' => 'bi-people'],
                'engage' => ['label' => 'Tương tác', 'icon' => 'bi-megaphone'],
            ],
            $tabs
        );
        // Ẩn tab thi HV khi mode dạy — dùng tab «Thi online» GV-4
        unset($tabs['exams']);
        // Ẩn tab điểm danh HV (lịch tự check-in) khi mode dạy — dùng tab "Điểm danh lớp"
        unset($tabs['attendance']);
    }
    $activeTab = in_array($tab ?? '', array_keys($tabs), true) ? $tab : ($teachMode ? 'author' : 'overview');
    $teachSubmissions = $teachSubmissions ?? collect();
    $teachStudents = $teachStudents ?? collect();
    $teachSession = $teachSession ?? null;
    $teachSessionRecords = $teachSessionRecords ?? collect();
    $teachAttendanceStats = $teachAttendanceStats ?? [];
    $classProgress = $classProgress ?? collect();
    $classAlerts = $classAlerts ?? collect();
    $classCerts = $classCerts ?? collect();
    $classCertEligibility = $classCertEligibility ?? [];
    $classSurveyStats = $classSurveyStats ?? [];
    $gradeMatrix = $gradeMatrix ?? ['columns' => [], 'rows' => []];
    $questionBanks = $questionBanks ?? collect();
@endphp

<div class="mb-3 flex flex-wrap items-center justify-between gap-2">
    <a href="{{ $backHome }}" class="text-sm text-teal-700 hover:underline">
        ← {{ $teachMode || \Modules\Lms\Support\LmsAccess::isInstructorUser() ? 'Khóa dạy' : 'Khóa của tôi' }}
    </a>
    @if($canTeach)
        @if($teachMode)
            <a href="{{ route('lms.learn.courses.show', $course) }}" class="lms-btn lms-btn-ghost text-xs" style="padding:0.35rem 0.7rem">
                <i class="bi bi-eye"></i> Xem như học viên
            </a>
        @else
            <a href="{{ route('lms.learn.courses.show', $course) }}?mode=teach" class="lms-btn-solid text-xs" style="padding:0.35rem 0.7rem">
                <i class="bi bi-easel"></i> Chế độ dạy
            </a>
        @endif
    @endif
</div>

@if($teachMode)
    <div class="lms-notice lms-notice-teach mb-3">
        <span class="lms-notice-icon"><i class="bi bi-easel"></i></span>
        <div>
            <strong>Chế độ dạy</strong>
            <span class="text-slate-600"> — Soạn bài · Giao &amp; chấm · Thi · Điểm danh · Lớp học · Tương tác</span>
        </div>
    </div>
@endif

{{-- 1) Card khóa (trên) --}}
<div class="lms-card p-5 mb-4">
    <div class="flex flex-wrap gap-2 items-start justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $course->title }}</h1>
            <p class="text-sm text-slate-500 mt-1">
                {{ $course->subject->name ?? '' }}
                @if($course->classModel) · {{ $course->classModel->name }} @endif
                · GV: {{ $course->instructor->name ?? '—' }}
            </p>
        </div>
        @if($teachMode)
            <span class="lms-chip lms-chip-teal">Mode dạy</span>
        @endif
    </div>
    @if($course->description)
        <p class="text-sm text-slate-700 mt-3 whitespace-pre-line">{{ $course->description }}</p>
    @endif
</div>

{{-- 2) Tabs ngay dưới card — tĩnh, scroll không dính theo --}}
<nav class="lms-tabs mb-5" id="lms-course-tabs" role="tablist">
    @foreach($tabs as $key => $meta)
        <button type="button"
                class="lms-tab {{ $activeTab === $key ? 'is-active' : '' }}"
                data-tab="{{ $key }}"
                role="tab">
            <i class="{{ $meta['icon'] }}" style="margin-right:0.25rem"></i>{{ $meta['label'] }}
        </button>
    @endforeach
</nav>

@if($teachMode)
{{-- ========== AUTHOR (GV-2 soạn bài + tài liệu) ========== --}}
<section class="lms-panel {{ $activeTab === 'author' ? 'is-active' : '' }}" data-panel="author">
    <div class="grid lg:grid-cols-2 gap-4">
        {{-- Lessons --}}
        <div class="space-y-4">
            <div class="lms-card p-4">
                <div class="font-semibold text-slate-800 mb-3"><i class="bi bi-plus-circle text-teal-700"></i> Thêm bài học</div>
                <form method="POST" action="{{ route('lms.teach.lessons.store', $course) }}" class="space-y-2" data-turbo="false">
                    @csrf
                    <input type="text" name="title" required maxlength="255" placeholder="Tiêu đề bài *"
                           class="w-full border border-slate-200 rounded-lg text-sm px-3 py-2">
                    <input type="number" name="week_number" min="1" max="52" placeholder="Tuần (tuỳ chọn)"
                           class="w-full border border-slate-200 rounded-lg text-sm px-3 py-2">
                    {{-- Sprint 8 G8: map CTĐT --}}
                    @if(isset($subjectLessons) && $subjectLessons->isNotEmpty())
                        <select name="subject_lesson_id" class="tom-select w-full border rounded-lg text-sm px-3 py-2" data-tom-select data-placeholder="Map bài CTĐT (tuỳ chọn)">
                            <option value="">— Không map CTĐT —</option>
                            @foreach($subjectLessons as $sl)
                                <option value="{{ $sl->id }}">{{ $sl->display_label }}</option>
                            @endforeach
                        </select>
                    @endif
                    <textarea name="summary" rows="2" placeholder="Tóm tắt ngắn" class="w-full border border-slate-200 rounded-lg text-sm px-3 py-2"></textarea>
                    <textarea name="content" rows="3" placeholder="Nội dung HTML/text" class="w-full border border-slate-200 rounded-lg text-sm px-3 py-2"></textarea>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="is_published" value="1" checked> Công bố ngay cho HV
                    </label>
                    <button type="submit" class="lms-btn-solid w-full sm:w-auto">Lưu bài học</button>
                </form>
            </div>

            <div class="lms-card overflow-hidden">
                <div class="lms-card-head">Danh sách bài ({{ $course->lessons->count() }})</div>
                <ul class="divide-y divide-slate-100">
                    @forelse($course->lessons as $lesson)
                        <li class="p-3" id="lesson-row-{{ $lesson->id }}">
                            <div class="flex flex-wrap gap-2 justify-between items-start">
                                <div class="min-w-0 flex-1">
                                    <div class="font-semibold text-slate-900">
                                        {{ $lesson->sort_order }}. {{ $lesson->title }}
                                        @if(!$lesson->is_published)
                                            <span class="text-[11px] text-amber-700 bg-amber-50 px-1.5 py-0.5 rounded ml-1">Nháp</span>
                                        @else
                                            <span class="text-[11px] text-emerald-700 bg-emerald-50 px-1.5 py-0.5 rounded ml-1">Công bố</span>
                                        @endif
                                    </div>
                                    @if($lesson->week_number)
                                        <div class="text-xs text-slate-400">Tuần {{ $lesson->week_number }}</div>
                                    @endif
                                    @if($lesson->subject_lesson_id && $lesson->subjectLesson)
                                        <div class="text-[11px] text-teal-700 mt-0.5">
                                            CTĐT: {{ $lesson->subjectLesson->display_label }}
                                        </div>
                                    @endif
                                </div>
                                <div class="flex flex-wrap gap-1">
                                    <form method="POST" action="{{ route('lms.teach.lessons.move', [$course, $lesson]) }}" data-turbo="false">
                                        @csrf
                                        <input type="hidden" name="direction" value="up">
                                        <button class="lms-btn lms-btn-ghost text-xs" style="padding:0.25rem 0.45rem" title="Lên">↑</button>
                                    </form>
                                    <form method="POST" action="{{ route('lms.teach.lessons.move', [$course, $lesson]) }}" data-turbo="false">
                                        @csrf
                                        <input type="hidden" name="direction" value="down">
                                        <button class="lms-btn lms-btn-ghost text-xs" style="padding:0.25rem 0.45rem" title="Xuống">↓</button>
                                    </form>
                                    <form method="POST" action="{{ route('lms.teach.lessons.toggle', [$course, $lesson]) }}" data-turbo="false">
                                        @csrf
                                        <button class="lms-btn lms-btn-ghost text-xs" style="padding:0.25rem 0.45rem">
                                            {{ $lesson->is_published ? 'Ẩn' : 'Công bố' }}
                                        </button>
                                    </form>
                                    <button type="button" class="lms-btn lms-btn-ghost text-xs" style="padding:0.25rem 0.45rem"
                                            onclick="document.getElementById('edit-lesson-{{ $lesson->id }}').classList.toggle('hidden')">Sửa</button>
                                    <form method="POST" action="{{ route('lms.teach.lessons.destroy', [$course, $lesson]) }}" data-turbo="false"
                                          data-confirm="Xoá bài «{{ $lesson->title }}»?" data-confirm-danger="1" data-confirm-title="Xoá bài học">
                                        @csrf
                                        @method('DELETE')
                                        <button class="lms-btn lms-btn-ghost text-xs text-rose-600" style="padding:0.25rem 0.45rem">Xoá</button>
                                    </form>
                                </div>
                            </div>
                            <div id="edit-lesson-{{ $lesson->id }}" class="hidden mt-3 pt-3 border-t border-slate-100">
                                <form method="POST" action="{{ route('lms.teach.lessons.update', [$course, $lesson]) }}" class="space-y-2" data-turbo="false">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="title" value="{{ $lesson->title }}" required class="w-full border rounded-lg text-sm px-3 py-2">
                                    <input type="number" name="week_number" value="{{ $lesson->week_number }}" min="1" max="52" class="w-full border rounded-lg text-sm px-3 py-2" placeholder="Tuần">
                                    @if(isset($subjectLessons) && $subjectLessons->isNotEmpty())
                                        <select name="subject_lesson_id" class="tom-select w-full border rounded-lg text-sm px-3 py-2" data-tom-select>
                                            <option value="">— Không map CTĐT —</option>
                                            @foreach($subjectLessons as $sl)
                                                <option value="{{ $sl->id }}" @selected((int)$lesson->subject_lesson_id === (int)$sl->id)>{{ $sl->display_label }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                    <textarea name="summary" rows="2" class="w-full border rounded-lg text-sm px-3 py-2" placeholder="Tóm tắt">{{ $lesson->summary }}</textarea>
                                    <textarea name="content" rows="3" class="w-full border rounded-lg text-sm px-3 py-2" placeholder="Nội dung">{{ $lesson->content }}</textarea>
                                    <label class="inline-flex items-center gap-2 text-sm">
                                        <input type="checkbox" name="is_published" value="1" @checked($lesson->is_published)> Công bố
                                    </label>
                                    <button type="submit" class="lms-btn-solid text-sm" style="padding:0.4rem 0.9rem">Cập nhật</button>
                                </form>
                            </div>
                        </li>
                    @empty
                        <li class="px-4 py-8 text-center text-sm text-slate-500">Chưa có bài — thêm form bên trên.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        {{-- Materials --}}
        <div class="space-y-4">
            <div class="lms-card p-4">
                <div class="font-semibold text-slate-800 mb-3"><i class="bi bi-cloud-upload text-teal-700"></i> Upload tài liệu</div>
                <form method="POST" action="{{ route('lms.teach.materials.store', $course) }}" enctype="multipart/form-data" class="space-y-2" data-turbo="false" id="author-material-form">
                    @csrf
                    <input type="text" name="title" maxlength="255" placeholder="Tiêu đề (để trống = tên file)"
                           class="w-full border border-slate-200 rounded-lg text-sm px-3 py-2">
                    <div>
                        <label class="text-[11px] font-medium text-slate-500 mb-1 block">Gắn bài học</label>
                        <select name="lms_lesson_id" class="tom-select w-full" data-tom-select data-placeholder="Gắn bài học">
                            <option value="">— Không gắn bài (chung khóa) —</option>
                            @foreach($course->lessons as $lesson)
                                <option value="{{ $lesson->id }}">{{ $lesson->sort_order }}. {{ $lesson->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="text-[11px] font-medium text-slate-500 mb-1 block">Loại tải lên</label>
                        <select name="as_scorm" class="tom-select w-full" data-tom-select data-placeholder="Loại tải lên">
                            <option value="0">Tài liệu thường (PDF, video, doc…)</option>
                            <option value="1">Gói SCORM (.zip)</option>
                        </select>
                    </div>
                    <input type="file" name="file" required class="w-full text-sm">
                    <p class="text-[11px] text-slate-400">PDF, video, ảnh, doc, txt… · PPTX nên SCORM hoặc PDF. Max {{ \Modules\Lms\Services\LmsMaterialService::MAX_MB }} MB.</p>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="is_published" value="1" checked> Công bố cho HV
                    </label>
                    <button type="submit" class="lms-btn-solid w-full sm:w-auto">Tải lên</button>
                </form>
            </div>

            <div class="lms-card overflow-hidden">
                <div class="lms-card-head">Tài liệu ({{ $course->materials->count() }}) · SCORM ({{ $course->scormPackages->count() }})</div>
                <ul class="divide-y divide-slate-100">
                    @forelse($course->materials as $m)
                        <li class="px-3 py-2.5 flex flex-wrap gap-2 justify-between items-center text-sm">
                            <div class="min-w-0">
                                <div class="font-medium text-slate-900">{{ $m->title }}</div>
                                <div class="text-xs text-slate-400">
                                    {{ $m->kindLabel() }} · {{ $m->humanSize() }}
                                    @if($m->lms_lesson_id)
                                        · Bài #{{ $m->lms_lesson_id }}
                                    @endif
                                    · {{ $m->is_published ? 'Công bố' : 'Nháp' }}
                                </div>
                            </div>
                            <div class="flex gap-1">
                                <form method="POST" action="{{ route('lms.teach.materials.toggle', [$course, $m]) }}" data-turbo="false">
                                    @csrf
                                    <button class="lms-btn lms-btn-ghost text-xs" style="padding:0.25rem 0.45rem">
                                        {{ $m->is_published ? 'Ẩn' : 'Công bố' }}
                                    </button>
                                </form>
                                <form method="POST" action="{{ route('lms.teach.materials.destroy', [$course, $m]) }}" data-turbo="false"
                                      data-confirm="Xoá tài liệu này?" data-confirm-danger="1" data-confirm-title="Xoá tài liệu">
                                    @csrf
                                    @method('DELETE')
                                    <button class="lms-btn lms-btn-ghost text-xs text-rose-600" style="padding:0.25rem 0.45rem">Xoá</button>
                                </form>
                            </div>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-sm text-slate-500">Chưa có tài liệu.</li>
                    @endforelse
                    @foreach($course->scormPackages as $sc)
                        <li class="px-3 py-2.5 flex flex-wrap gap-2 justify-between items-center text-sm bg-slate-50/50">
                            <div>
                                <div class="font-medium">{{ $sc->title }} <span class="text-xs text-teal-700">SCORM</span></div>
                            </div>
                            <form method="POST" action="{{ route('lms.teach.scorm.destroy', [$course, $sc]) }}" data-turbo="false"
                                  data-confirm="Xoá gói SCORM này?" data-confirm-danger="1" data-confirm-title="Xoá SCORM">
                                @csrf
                                @method('DELETE')
                                <button class="lms-btn lms-btn-ghost text-xs text-rose-600" style="padding:0.25rem 0.45rem">Xoá</button>
                            </form>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- ========== ASSIGN + GRADE (GV-3) ========== --}}
<section class="lms-panel {{ $activeTab === 'assign' ? 'is-active' : '' }}" data-panel="assign">
    <div class="grid lg:grid-cols-5 gap-4">
        <div class="lg:col-span-2 space-y-4">
            <div class="lms-card p-4">
                <div class="font-semibold text-slate-800 mb-3"><i class="bi bi-plus-lg text-teal-700"></i> Giao bài tập mới</div>
                <form method="POST" action="{{ route('lms.teach.assignments.store', $course) }}" class="space-y-2" data-turbo="false">
                    @csrf
                    <input type="text" name="title" required maxlength="255" placeholder="Tên bài tập *"
                           class="w-full border border-slate-200 rounded-lg text-sm px-3 py-2">
                    <select name="lms_lesson_id" class="tom-select w-full border border-slate-200 rounded-lg text-sm px-3 py-2" data-tom-select>
                        <option value="">— Không gắn bài (chung) —</option>
                        @foreach($course->lessons as $lesson)
                            <option value="{{ $lesson->id }}">{{ $lesson->sort_order }}. {{ $lesson->title }}</option>
                        @endforeach
                    </select>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" step="0.1" name="max_score" value="{{ \Modules\Lms\Support\LmsSettings::assignmentMaxScore() }}" min="0" placeholder="Điểm tối đa"
                               class="w-full border border-slate-200 rounded-lg text-sm px-3 py-2">
                        <input type="text" name="due_at" placeholder="Hạn nộp"
                               class="flatpickr-datetime w-full border border-slate-200 rounded-lg text-sm px-3 py-2"
                               autocomplete="off">
                    </div>
                    <textarea name="description" rows="2" placeholder="Mô tả / yêu cầu nộp"
                              class="w-full border border-slate-200 rounded-lg text-sm px-3 py-2"></textarea>
                    <input type="hidden" name="allow_late" value="0">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="allow_late" value="1" @checked(\Modules\Lms\Support\LmsSettings::allowLateByDefault())> Cho nộp muộn
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="is_published" value="1" checked> Công bố cho HV
                    </label>
                    <button type="submit" class="lms-btn-solid w-full sm:w-auto">Tạo bài tập</button>
                </form>
            </div>

            <div class="lms-card overflow-hidden">
                <div class="lms-card-head">Bài tập ({{ $assignments->count() }})</div>
                <ul class="divide-y divide-slate-100">
                    @forelse($assignments as $a)
                        @php
                            $subs = $teachSubmissions[$a->id] ?? collect();
                            $pending = $subs->filter(fn ($s) => $s->status !== 'graded' || $s->score === null)->count();
                        @endphp
                        <li class="p-3 text-sm">
                            <div class="flex flex-wrap justify-between gap-2">
                                <div>
                                    <div class="font-semibold text-slate-900">{{ $a->title }}</div>
                                    <div class="text-xs text-slate-400 mt-0.5">
                                        Max {{ $a->max_score }}
                                        @if($a->due_at) · Hạn {{ $a->due_at->format('d/m H:i') }} @endif
                                        @if($a->lesson) · {{ $a->lesson->title }} @endif
                                        · {{ $a->is_published ? 'Công bố' : 'Nháp' }}
                                        @if($pending > 0)
                                            · <span class="text-amber-700 font-semibold">{{ $pending }} chờ chấm</span>
                                        @endif
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-1">
                                    <form method="POST" action="{{ route('lms.teach.assignments.toggle', [$course, $a]) }}" data-turbo="false">
                                        @csrf
                                        <button class="lms-btn lms-btn-ghost text-xs" style="padding:0.25rem 0.45rem">{{ $a->is_published ? 'Ẩn' : 'Công bố' }}</button>
                                    </form>
                                    <button type="button" class="lms-btn lms-btn-ghost text-xs" style="padding:0.25rem 0.45rem"
                                            onclick="document.getElementById('edit-asg-{{ $a->id }}').classList.toggle('hidden')">Sửa</button>
                                    <form method="POST" action="{{ route('lms.teach.assignments.destroy', [$course, $a]) }}" data-turbo="false"
                                          data-confirm="Xoá bài tập «{{ $a->title }}»?" data-confirm-danger="1" data-confirm-title="Xoá bài tập">
                                        @csrf
                                        @method('DELETE')
                                        <button class="lms-btn lms-btn-ghost text-xs text-rose-600" style="padding:0.25rem 0.45rem">Xoá</button>
                                    </form>
                                </div>
                            </div>
                            <div id="edit-asg-{{ $a->id }}" class="hidden mt-2 pt-2 border-t border-slate-100 space-y-2">
                                <form method="POST" action="{{ route('lms.teach.assignments.update', [$course, $a]) }}" class="space-y-2" data-turbo="false">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="title" value="{{ $a->title }}" required class="w-full border rounded-lg text-sm px-3 py-2">
                                    <select name="lms_lesson_id" class="tom-select w-full border rounded-lg text-sm px-3 py-2" data-tom-select>
                                        <option value="">— Không gắn bài —</option>
                                        @foreach($course->lessons as $lesson)
                                            <option value="{{ $lesson->id }}" @selected($a->lms_lesson_id == $lesson->id)>{{ $lesson->sort_order }}. {{ $lesson->title }}</option>
                                        @endforeach
                                    </select>
                                    <div class="grid grid-cols-2 gap-2">
                                        <input type="number" step="0.1" name="max_score" value="{{ $a->max_score }}" class="w-full border rounded-lg text-sm px-3 py-2">
                                        <input type="text" name="due_at"
                                               value="{{ $a->due_at?->format('Y-m-d H:i') }}"
                                               class="flatpickr-datetime w-full border rounded-lg text-sm px-3 py-2"
                                               autocomplete="off" placeholder="Hạn nộp">
                                    </div>
                                    <textarea name="description" rows="2" class="w-full border rounded-lg text-sm px-3 py-2">{{ $a->description }}</textarea>
                                    <label class="inline-flex items-center gap-2 text-xs"><input type="checkbox" name="allow_late" value="1" @checked($a->allow_late)> Nộp muộn</label>
                                    <label class="inline-flex items-center gap-2 text-xs"><input type="checkbox" name="is_published" value="1" @checked($a->is_published)> Công bố</label>
                                    <button type="submit" class="lms-btn-solid text-sm" style="padding:0.35rem 0.8rem">Lưu</button>
                                </form>
                            </div>
                        </li>
                    @empty
                        <li class="px-4 py-8 text-center text-slate-500 text-sm">Chưa có bài tập.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        <div class="lg:col-span-3">
            <div class="lms-card overflow-hidden">
                <div class="lms-card-head flex flex-wrap items-center justify-between gap-2">
                    <span>Chấm bài nộp</span>
                    {{-- Sprint 8 G7: filter chờ chấm --}}
                    <div class="flex gap-1 text-xs font-normal">
                        @php
                            $baseAssignUrl = route('lms.learn.courses.show', $course).'?mode=teach&tab=assign';
                        @endphp
                        <a href="{{ $baseAssignUrl }}"
                           class="px-2 py-1 rounded {{ empty($pendingOnly) ? 'bg-teal-100 text-teal-900 font-semibold' : 'text-slate-500 hover:bg-slate-50' }}">
                            Tất cả
                        </a>
                        <a href="{{ $baseAssignUrl }}&pending_only=1"
                           class="px-2 py-1 rounded {{ !empty($pendingOnly) ? 'bg-amber-100 text-amber-900 font-semibold' : 'text-slate-500 hover:bg-slate-50' }}">
                            Chỉ chờ chấm
                        </a>
                    </div>
                </div>
                <div class="p-3 space-y-4 max-h-[36rem] overflow-y-auto">
                    @php $anySub = false; @endphp
                    @foreach($assignments as $a)
                        @php $subs = $teachSubmissions[$a->id] ?? collect(); @endphp
                        @if($subs->isEmpty())
                            @continue
                        @endif
                        @php $anySub = true; @endphp
                        <div>
                            <div class="text-sm font-semibold text-slate-800 mb-2 border-b border-slate-100 pb-1 flex flex-wrap items-center justify-between gap-2">
                                <span>{{ $a->title }}
                                    <span class="text-xs font-normal text-slate-400">(max {{ $a->max_score }} · {{ $subs->count() }} bài nộp)</span>
                                </span>
                                <a href="{{ route('lms.teach.assignments.download-all', [$course, $a]) }}"
                                   class="lms-btn lms-btn-ghost text-xs" style="padding:0.25rem 0.55rem" data-turbo="false">
                                    <i class="bi bi-file-earmark-zip"></i> Tải ZIP cả lớp
                                </a>
                            </div>
                            <div class="space-y-3">
                                @foreach($subs as $sub)
                                    <div class="rounded-xl border border-slate-100 p-3 {{ $sub->status === 'graded' ? 'bg-emerald-50/40' : 'bg-amber-50/30' }}">
                                        <div class="flex flex-wrap justify-between gap-2 text-sm">
                                            <div>
                                                <strong>{{ $sub->user->name ?? 'HV #'.$sub->user_id }}</strong>
                                                <span class="text-xs text-slate-400 ml-1">
                                                    nộp {{ $sub->submitted_at?->format('d/m H:i') ?? '—' }}
                                                    · {{ $sub->status === 'graded' ? 'Đã chấm' : 'Chờ chấm' }}
                                                </span>
                                            </div>
                                            @if($sub->status === 'graded')
                                                <span class="font-bold text-teal-800">{{ $sub->score }}/{{ $a->max_score }}</span>
                                            @endif
                                        </div>
                                        @if($sub->text_answer)
                                            <div class="mt-2 text-xs text-slate-600 whitespace-pre-wrap bg-white/80 rounded-lg px-2 py-1.5 border border-slate-100 max-h-28 overflow-y-auto">{{ $sub->text_answer }}</div>
                                        @endif
                                        @if($sub->file_path)
                                            <div class="mt-1 flex flex-wrap gap-2 items-center">
                                                <a href="{{ route('lms.teach.assignments.download-one', [$course, $a, $sub]) }}"
                                                   class="text-xs text-slate-700 font-medium" data-turbo="false">
                                                    <i class="bi bi-shield-lock"></i> Tải an toàn: {{ $sub->file_name ?? 'Tệp đính kèm' }}
                                                </a>
                                            </div>
                                        @endif
                                        @if($sub->feedback && $sub->status === 'graded')
                                            <div class="mt-1 text-xs text-slate-500 italic">Feedback: {{ $sub->feedback }}</div>
                                        @endif
                                        <form method="POST"
                                              action="{{ route('lms.teach.assignments.grade', [$course, $a, $sub]) }}"
                                              class="mt-2 flex flex-wrap gap-2 items-end"
                                              data-turbo="false">
                                            @csrf
                                            <div>
                                                <label class="text-[10px] text-slate-500 block">Điểm</label>
                                                <input type="number" step="0.1" min="0" max="{{ $a->max_score }}" name="score"
                                                       value="{{ $sub->score }}" required
                                                       class="w-24 border rounded-lg text-sm px-2 py-1.5">
                                            </div>
                                            <div class="flex-1 min-w-[10rem]">
                                                <label class="text-[10px] text-slate-500 block">Nhận xét</label>
                                                <input type="text" name="feedback" value="{{ $sub->feedback }}" maxlength="5000"
                                                       placeholder="Feedback cho HV"
                                                       class="w-full border rounded-lg text-sm px-2 py-1.5">
                                            </div>
                                            <button type="submit" class="lms-btn-solid text-xs" style="padding:0.4rem 0.75rem">
                                                {{ $sub->status === 'graded' ? 'Cập nhật' : 'Chấm' }}
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    @if(!$anySub)
                        <p class="text-center text-sm text-slate-500 py-10">
                            {{ !empty($pendingOnly) ? 'Không còn bài chờ chấm.' : 'Chưa có bài nộp nào để chấm.' }}
                        </p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</section>

{{-- ========== EXAM (GV-4) ========== --}}
<section class="lms-panel {{ $activeTab === 'exam' ? 'is-active' : '' }}" data-panel="exam">
    <div class="grid lg:grid-cols-2 gap-4">
        {{-- NHCH --}}
        <div class="space-y-4">
            <div class="lms-card p-4">
                <div class="font-semibold text-slate-800 mb-3"><i class="bi bi-collection text-teal-700"></i> Ngân hàng câu hỏi</div>
                <form method="POST" action="{{ route('lms.teach.exam-banks.store', $course) }}" class="flex flex-wrap gap-2 mb-3" data-turbo="false">
                    @csrf
                    <input name="title" required maxlength="200" placeholder="Tên NHCH *"
                           class="flex-1 min-w-[10rem] border border-slate-200 rounded-lg text-sm px-3 py-2">
                    <button class="lms-btn-solid text-sm" style="padding:0.45rem 0.9rem">Tạo NHCH</button>
                </form>
                @forelse($questionBanks as $bank)
                    <div class="border border-slate-100 rounded-xl p-3 mb-3">
                        <div class="font-medium text-slate-900 text-sm mb-2">
                            {{ $bank->title }}
                            <span class="text-xs text-slate-400 font-normal">({{ $bank->questions_count }} câu)</span>
                        </div>

                        {{-- Danh sách câu: sửa / xóa / reorder (G2) --}}
                        <ul class="divide-y divide-slate-50 mb-3 max-h-72 overflow-y-auto">
                            @forelse($bank->questions as $qi => $q)
                                @php
                                    $optsText = is_array($q->options) ? implode("\n", $q->options) : '';
                                @endphp
                                <li class="py-2 text-xs" id="q-row-{{ $q->id }}">
                                    <div class="flex flex-wrap gap-1 justify-between items-start">
                                        <div class="min-w-0 flex-1 pr-2">
                                            <span class="text-slate-400 font-mono">#{{ $qi + 1 }}</span>
                                            <span class="font-semibold text-teal-800 uppercase">{{ $q->type }}</span>
                                            <span class="text-slate-400">· {{ $q->points }}đ</span>
                                            <div class="text-slate-800 mt-0.5 whitespace-pre-wrap">{{ \Illuminate\Support\Str::limit($q->stem, 120) }}</div>
                                            <div class="text-slate-400 mt-0.5">Đáp án: <code>{{ $q->correct_answer }}</code></div>
                                        </div>
                                        <div class="flex flex-wrap gap-0.5">
                                            <form method="POST" action="{{ route('lms.teach.exam-questions.move', [$course, $bank, $q]) }}" data-turbo="false">
                                                @csrf
                                                <input type="hidden" name="direction" value="up">
                                                <button class="lms-btn lms-btn-ghost text-xs" style="padding:0.15rem 0.35rem" title="Lên">↑</button>
                                            </form>
                                            <form method="POST" action="{{ route('lms.teach.exam-questions.move', [$course, $bank, $q]) }}" data-turbo="false">
                                                @csrf
                                                <input type="hidden" name="direction" value="down">
                                                <button class="lms-btn lms-btn-ghost text-xs" style="padding:0.15rem 0.35rem" title="Xuống">↓</button>
                                            </form>
                                            <button type="button" class="lms-btn lms-btn-ghost text-xs" style="padding:0.15rem 0.4rem"
                                                    onclick="document.getElementById('edit-q-{{ $q->id }}').classList.toggle('hidden')">Sửa</button>
                                            <form method="POST" action="{{ route('lms.teach.exam-questions.destroy', [$course, $bank, $q]) }}" data-turbo="false"
                                                  data-confirm="Xoá câu hỏi này?" data-confirm-danger="1" data-confirm-title="Xoá câu hỏi">
                                                @csrf
                                                @method('DELETE')
                                                <button class="lms-btn lms-btn-ghost text-xs text-rose-600" style="padding:0.15rem 0.4rem">Xoá</button>
                                            </form>
                                        </div>
                                    </div>
                                    <div id="edit-q-{{ $q->id }}" class="hidden mt-2 pt-2 border-t border-slate-100 space-y-1.5">
                                        <form method="POST" action="{{ route('lms.teach.exam-questions.update', [$course, $bank, $q]) }}" class="space-y-1.5" data-turbo="false">
                                            @csrf
                                            @method('PUT')
                                            <select name="type" class="w-full border rounded-lg text-sm px-2 py-1.5">
                                                <option value="mcq" @selected($q->type === 'mcq')>Trắc nghiệm (MCQ)</option>
                                                <option value="true_false" @selected($q->type === 'true_false')>Đúng / Sai</option>
                                                <option value="short" @selected($q->type === 'short')>Điền ngắn</option>
                                            </select>
                                            <textarea name="stem" required rows="2" class="w-full border rounded-lg text-sm px-2 py-1.5">{{ $q->stem }}</textarea>
                                            <textarea name="options" rows="2" class="w-full border rounded-lg text-sm px-2 py-1.5" placeholder="MCQ: mỗi dòng 1 PA">{{ $optsText }}</textarea>
                                            <input name="correct_answer" required value="{{ $q->correct_answer }}"
                                                   class="w-full border rounded-lg text-sm px-2 py-1.5" placeholder="Đáp án">
                                            <div class="flex gap-2 items-center">
                                                <input type="number" step="0.1" name="points" value="{{ $q->points }}" min="0"
                                                       class="w-20 border rounded-lg text-sm px-2 py-1.5">
                                                <button type="submit" class="lms-btn-solid text-xs" style="padding:0.3rem 0.65rem">Lưu câu</button>
                                            </div>
                                        </form>
                                    </div>
                                </li>
                            @empty
                                <li class="py-3 text-center text-slate-400">Chưa có câu — thêm form bên dưới.</li>
                            @endforelse
                        </ul>

                        <form method="POST" action="{{ route('lms.teach.exam-questions.store', [$course, $bank]) }}" class="space-y-2" data-turbo="false">
                            @csrf
                            <div class="text-[11px] font-semibold text-slate-500 uppercase tracking-wide">+ Thêm câu</div>
                            <select name="type" class="tom-select w-full" data-tom-select>
                                <option value="mcq">Trắc nghiệm (MCQ)</option>
                                <option value="true_false">Đúng / Sai</option>
                                <option value="short">Điền ngắn</option>
                            </select>
                            <textarea name="stem" required rows="2" placeholder="Nội dung câu hỏi *"
                                      class="w-full border rounded-lg text-sm px-3 py-2"></textarea>
                            <textarea name="options" rows="3" placeholder="MCQ: mỗi dòng 1 phương án"
                                      class="w-full border rounded-lg text-sm px-3 py-2"></textarea>
                            <input name="correct_answer" required
                                   placeholder="Đáp án: index MCQ (0,1,…) / true|false / text"
                                   class="w-full border rounded-lg text-sm px-3 py-2">
                            <div class="flex flex-wrap gap-2 items-center">
                                <input type="number" step="0.1" name="points" value="1" min="0"
                                       class="w-24 border rounded-lg text-sm px-2 py-1.5" title="Điểm">
                                <button type="submit" class="lms-btn-solid text-xs" style="padding:0.35rem 0.7rem">Thêm câu</button>
                            </div>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 text-center py-6">Tạo NHCH trước, rồi thêm câu hỏi.</p>
                @endforelse
            </div>
        </div>

        {{-- Exams --}}
        <div class="space-y-4">
            <div class="lms-card p-4">
                <div class="font-semibold text-slate-800 mb-3"><i class="bi bi-journal-check text-teal-700"></i> Tạo bài thi</div>
                <form method="POST" action="{{ route('lms.teach.exams.store', $course) }}" class="space-y-2" data-turbo="false" id="teach-exam-create-form">
                    @csrf
                    <input name="title" required maxlength="255" placeholder="Tên bài thi *"
                           class="w-full border rounded-lg text-sm px-3 py-2">
                    <select name="bank_id" id="exam-bank-pick" class="tom-select w-full" data-tom-select>
                        <option value="">— Lọc theo NHCH (tuỳ chọn) —</option>
                        @foreach($questionBanks as $bank)
                            <option value="{{ $bank->id }}">{{ $bank->title }} ({{ $bank->questions_count }} câu)</option>
                        @endforeach
                    </select>
                    <div class="rounded-xl border border-slate-100 p-3 max-h-48 overflow-y-auto bg-slate-50/50">
                        <div class="text-[11px] font-semibold text-slate-500 mb-2">G1 · Chọn câu lẻ (tick). Để trống + chọn NHCH = lấy cả bank.</div>
                        @php $allQs = $questionBanks->flatMap(fn ($b) => $b->questions->map(fn ($q) => [$b, $q])); @endphp
                        @forelse($allQs as [$b, $q])
                            <label class="flex gap-2 items-start text-xs py-1 border-b border-slate-100/80 last:border-0 cursor-pointer hover:bg-white/80 rounded px-1"
                                   data-bank-id="{{ $b->id }}">
                                <input type="checkbox" name="question_ids[]" value="{{ $q->id }}" class="mt-0.5 exam-q-pick">
                                <span>
                                    <span class="text-teal-800 font-semibold">{{ $b->title }}</span>
                                    <span class="text-slate-400">· {{ $q->type }} · {{ $q->points }}đ</span>
                                    <span class="block text-slate-700">{{ \Illuminate\Support\Str::limit($q->stem, 80) }}</span>
                                </span>
                            </label>
                        @empty
                            <p class="text-xs text-slate-400 text-center py-3">Chưa có câu trong NHCH.</p>
                        @endforelse
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="number" name="duration_minutes" value="{{ \Modules\Lms\Support\LmsSettings::examDurationMinutes() }}" min="5" max="480"
                               class="w-full border rounded-lg text-sm px-3 py-2" placeholder="Phút">
                        <input type="number" name="max_attempts" value="{{ \Modules\Lms\Support\LmsSettings::examAttempts() }}" min="1" max="20"
                               class="w-full border rounded-lg text-sm px-3 py-2" placeholder="Số lần">
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <input type="text" name="opens_at" class="flatpickr-datetime w-full border rounded-lg text-sm px-3 py-2"
                               placeholder="Mở lúc" autocomplete="off">
                        <input type="text" name="closes_at" class="flatpickr-datetime w-full border rounded-lg text-sm px-3 py-2"
                               placeholder="Đóng lúc" autocomplete="off">
                    </div>
                    <input type="number" step="0.1" name="pass_score" value="{{ \Modules\Lms\Support\LmsSettings::examPassScore() }}" class="w-full border rounded-lg text-sm px-3 py-2" placeholder="Điểm đạt">
                    <input type="hidden" name="shuffle_questions" value="0">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="shuffle_questions" value="1" @checked(\Modules\Lms\Support\LmsSettings::shuffleQuestions())> Xáo câu hỏi
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="proctor_basic" value="1" checked> Proctor (ghi rời tab)
                    </label>
                    <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                        <input type="checkbox" name="require_fullscreen" value="1"> Bắt buộc fullscreen
                    </label>
                    <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                        <input type="checkbox" name="auto_submit_on_leave" value="1"> Tự nộp khi rời tab/FS
                    </label>
                    <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                        Max blur
                        <input type="number" name="max_blur_events" min="1" max="50" placeholder="vd 5" class="w-16 border rounded px-1 py-0.5">
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="is_published" value="1"> Công bố cho HV
                    </label>
                    <button type="submit" class="lms-btn-solid w-full sm:w-auto">Tạo bài thi</button>
                </form>
            </div>

            <div class="lms-card overflow-hidden">
                <div class="lms-card-head">Bài thi ({{ $exams->count() }})</div>
                <ul class="divide-y divide-slate-100">
                    @forelse($exams as $exam)
                        <li class="p-3 text-sm">
                            <div class="flex flex-wrap justify-between gap-2">
                                <div>
                                    <div class="font-semibold text-slate-900">{{ $exam->title }}</div>
                                    <div class="text-xs text-slate-400 mt-0.5">
                                        {{ $exam->questions_count }} câu · {{ $exam->duration_minutes }}'
                                        · {{ $exam->max_attempts }} lần
                                        · {{ $exam->is_published ? 'Công bố' : 'Nháp' }}
                                        · {{ $exam->attempts_count }} lượt
                                        @if($exam->proctor_basic) · proctor @endif
                                    </div>
                                </div>
                                <div class="flex flex-wrap gap-1">
                                    <a href="{{ route('lms.teach.exams.attempts', [$course, $exam]) }}"
                                       class="lms-btn lms-btn-ghost text-xs" style="padding:0.25rem 0.5rem">Lượt làm</a>
                                    <a href="{{ route('lms.teach.exams.export', [$course, $exam]) }}"
                                       class="lms-btn lms-btn-ghost text-xs" style="padding:0.25rem 0.5rem" data-turbo="false">CSV</a>
                                    <form method="POST" action="{{ route('lms.teach.exams.toggle', [$course, $exam]) }}" data-turbo="false">
                                        @csrf
                                        <button class="lms-btn lms-btn-ghost text-xs" style="padding:0.25rem 0.5rem">
                                            {{ $exam->is_published ? 'Ẩn' : 'Công bố' }}
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('lms.teach.exams.destroy', [$course, $exam]) }}" data-turbo="false"
                                          data-confirm="Xoá bài thi «{{ $exam->title }}»?" data-confirm-danger="1" data-confirm-title="Xoá bài thi">
                                        @csrf
                                        @method('DELETE')
                                        <button class="lms-btn lms-btn-ghost text-xs text-rose-600" style="padding:0.25rem 0.5rem">Xoá</button>
                                    </form>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="px-4 py-8 text-center text-slate-500 text-sm">Chưa có bài thi.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</section>

{{-- ========== ATTENDANCE CLASS (GV-5) ========== --}}
<section class="lms-panel {{ $activeTab === 'attend' ? 'is-active' : '' }}" data-panel="attend">
    <div class="grid lg:grid-cols-5 gap-4">
        {{-- Left: create + session list --}}
        <div class="lg:col-span-2 space-y-4">
            <div class="lms-card p-4">
                <div class="font-semibold text-slate-800 mb-3"><i class="bi bi-plus-circle text-teal-700"></i> Mở điểm danh theo ngày</div>
                <form method="POST" action="{{ route('lms.teach.attendance.store', $course) }}" class="space-y-2" data-turbo="false">
                    @csrf
                    <input type="text" name="title" required maxlength="255" placeholder="Tiêu đề (VD: Học lý thuyết)"
                           value="Điểm danh {{ now()->format('d/m') }}"
                           class="w-full border border-slate-200 rounded-lg text-sm px-3 py-2">
                    <input type="text" name="session_date" value="{{ now()->toDateString() }}" required
                           class="flatpickr-date w-full border border-slate-200 rounded-lg text-sm px-3 py-2"
                           autocomplete="off" placeholder="Ngày điểm danh">
                    <select name="mode" class="tom-select w-full border border-slate-200 rounded-lg text-sm px-3 py-2" data-tom-select>
                        <option value="manual">Thủ công / miệng (GV điểm)</option>
                        <option value="self">HV tự check-in (app)</option>
                        <option value="qr">QR + Wi‑Fi trường</option>
                        <option value="gps">GPS campus (P2)</option>
                    </select>
                    <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                        <input type="checkbox" name="require_campus_wifi" value="1" id="require-wifi-cb">
                        Bắt buộc Wi‑Fi trường (mặc định bật với mode QR)
                    </label>
                    <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                        <input type="checkbox" name="require_gps" value="1" id="require-gps-cb">
                        Bắt buộc GPS trong bán kính (mặc định bật với mode GPS)
                    </label>
                    <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                        <input type="checkbox" name="allow_gps_bypass" value="1" id="gps-bypass-cb">
                        Cho GPS cứu khi Wi‑Fi/IP fail (bypass)
                    </label>
                    <div class="flex flex-wrap items-center gap-2">
                        <label class="text-xs text-slate-600 whitespace-nowrap">TTL mã QR (phút)</label>
                        <input type="number" name="qr_ttl_minutes" min="5" max="1440" value="120"
                               class="w-28 border border-slate-200 rounded-lg text-sm px-3 py-2"
                               title="P1: hết hạn token; GV có thể làm mới QR">
                    </div>
                    <input type="text" name="open_until" class="flatpickr-datetime w-full border border-slate-200 rounded-lg text-sm px-3 py-2"
                           autocomplete="off" placeholder="Hết hạn buổi (tuỳ chọn)">
                    <p class="text-[11px] text-slate-400">QR: IP/CIDR + probe. GPS: toạ độ trong ~{{ \Modules\Lms\Support\LmsCampus::radiusMeters() }}m. Bypass: IP fail nhưng GPS OK vẫn chấm. TTL QR 120p — «Làm mới QR» vô hiệu link cũ.</p>
                    <button type="submit" class="lms-btn-solid w-full sm:w-auto">Tạo ngày điểm danh</button>
                </form>
            </div>

            <div class="lms-card p-4 text-xs text-slate-600 space-y-1">
                <div class="font-semibold text-slate-800"><i class="bi bi-wifi text-teal-700"></i> Wi‑Fi trường</div>
                <p>MAC AP / dải IP do <strong>admin</strong> cấu hình tại dashboard (sidebar «Wi‑Fi trường»). Mode QR vẫn kiểm tra mạng trường khi HV quét mã.</p>
            </div>

            <div class="lms-card overflow-hidden">
                <div class="lms-card-head">Các ngày ({{ $sessions->count() }})</div>
                <ul class="divide-y divide-slate-100 max-h-80 overflow-y-auto">
                    @forelse($sessions->sortByDesc(fn($s) => optional($s->session_date)->format('Y-m-d').'-'.$s->id) as $s)
                        <li>
                            <a href="{{ route('lms.learn.courses.show', $course) }}?mode=teach&tab=attend&session={{ $s->id }}"
                               class="block px-3 py-2.5 text-sm hover:bg-teal-50/60 {{ $teachSession && $teachSession->id === $s->id ? 'bg-teal-50 border-l-4 border-teal-600' : '' }}">
                                <div class="font-semibold text-slate-900">{{ $s->session_date?->format('d/m/Y') }} · {{ $s->title }}</div>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    {{ $s->mode }} ·
                                    <span class="{{ $s->status === 'open' ? 'text-emerald-700' : 'text-slate-400' }}">{{ $s->status === 'open' ? 'Đang mở' : 'Đã đóng' }}</span>
                                    · {{ $s->records->count() }} HV đã điểm
                                    @if($s->schedule_detail_id)
                                        · <span class="text-blue-700">Từ lịch tiết {{ $s->scheduleDetail?->period }}</span>
                                    @endif
                                </div>
                            </a>
                        </li>
                    @empty
                        <li class="px-4 py-8 text-center text-sm text-slate-500">Chưa có ngày điểm danh.</li>
                    @endforelse
                </ul>
            </div>

            {{-- % chuyên cần --}}
            <div class="lms-card overflow-hidden">
                <div class="lms-card-head">% Chuyên cần</div>
                <ul class="divide-y divide-slate-100 max-h-64 overflow-y-auto text-sm">
                    @forelse($teachStudents as $m)
                        @php $st = $teachAttendanceStats[$m->user_id] ?? ['pct' => 0, 'present' => 0, 'total' => 0]; @endphp
                        <li class="px-3 py-2 flex justify-between gap-2">
                            <span class="truncate">{{ $m->user->name }}</span>
                            <span class="font-semibold tabular-nums {{ ($st['pct'] ?? 0) >= 80 ? 'text-emerald-700' : (($st['pct'] ?? 0) >= 50 ? 'text-amber-700' : 'text-rose-600') }}">
                                {{ $st['pct'] ?? 0 }}%
                                <span class="text-xs font-normal text-slate-400">({{ $st['present'] ?? 0 }}/{{ $st['total'] ?? 0 }})</span>
                            </span>
                        </li>
                    @empty
                        <li class="px-4 py-6 text-center text-slate-500 text-sm">Chưa có HV trong khóa.</li>
                    @endforelse
                </ul>
            </div>
        </div>

        {{-- Right: roster mark --}}
        <div class="lg:col-span-3">
            @if(!$teachSession)
                <div class="lms-card p-10 text-center text-sm text-slate-500">
                    Chọn hoặc tạo một ngày điểm danh để chấm danh sách.
                </div>
            @else
                <div class="lms-card overflow-hidden">
                    <div class="lms-card-head flex flex-wrap justify-between gap-2 items-center">
                        <span>
                            {{ $teachSession->session_date?->format('d/m/Y') }} — {{ $teachSession->title }}
                            <span class="text-xs font-normal text-slate-500 ml-1">({{ $teachSession->mode }})</span>
                        </span>
                        <div class="flex flex-wrap gap-1">
                            @if($teachSession->status === 'open')
                                <form method="POST" action="{{ route('lms.teach.attendance.close', [$course, $teachSession]) }}" data-turbo="false">
                                    @csrf
                                    <button class="lms-btn lms-btn-ghost text-xs" style="padding:0.3rem 0.55rem">Đóng ngày</button>
                                </form>
                                <button type="button" class="lms-btn-solid text-xs" style="padding:0.3rem 0.55rem" id="att-set-all-present" title="Chỉ đổi trên form — bấm Lưu để ghi">
                                    Cả lớp có mặt
                                </button>
                                <button type="button" class="lms-btn lms-btn-ghost text-xs" style="padding:0.3rem 0.55rem" id="att-set-all-absent" title="Chỉ đổi trên form — bấm Lưu để ghi">
                                    Cả lớp vắng
                                </button>
                            @else
                                <form method="POST" action="{{ route('lms.teach.attendance.reopen', [$course, $teachSession]) }}" data-turbo="false">
                                    @csrf
                                    <button class="lms-btn-solid text-xs" style="padding:0.3rem 0.55rem">Mở lại</button>
                                </form>
                            @endif
                        </div>
                    </div>

                    @if($teachSession->checkin_token && in_array($teachSession->mode, ['self', 'qr'], true))
                        @php
                            $qrCheckUrl = route('lms.learn.attendance.checkin', [$course, $teachSession]).'?token='.$teachSession->checkin_token;
                            $tokenExpired = $teachSession->isTokenExpired();
                            $ttlLabel = $teachSession->token_expires_at
                                ? $teachSession->token_expires_at->format('H:i d/m/Y')
                                : null;
                        @endphp
                        <div class="px-4 py-3 bg-slate-50 border-b border-slate-100 text-xs text-slate-600 space-y-2">
                            <div class="flex flex-wrap items-center gap-2">
                                <span>Link / token check-in:</span>
                                <button type="button"
                                        class="lms-btn-solid text-xs"
                                        style="padding:0.3rem 0.65rem"
                                        data-qr-popup
                                        data-qr-url="{{ $qrCheckUrl }}"
                                        data-qr-title="{{ $teachSession->title }} · {{ $teachSession->session_date?->format('d/m/Y') }}">
                                    <i class="bi bi-qr-code"></i> Hiện mã QR
                                </button>
                                <button type="button" class="lms-btn lms-btn-ghost text-xs" style="padding:0.3rem 0.55rem"
                                        data-copy-text="{{ $qrCheckUrl }}">
                                    <i class="bi bi-clipboard"></i> Copy link
                                </button>
                                <form method="POST" action="{{ route('lms.teach.attendance.rotate-token', [$course, $teachSession]) }}" data-turbo="false" class="inline-flex items-center gap-1">
                                    @csrf
                                    <input type="number" name="qr_ttl_minutes" min="5" max="1440"
                                           value="{{ $teachSession->qr_ttl_minutes ?? 120 }}"
                                           class="w-20 border border-slate-200 rounded px-2 py-1 text-xs"
                                           title="TTL phút sau khi làm mới">
                                    <button type="submit" class="lms-btn lms-btn-ghost text-xs" style="padding:0.3rem 0.55rem"
                                            data-confirm="Làm mới QR sẽ vô hiệu link/token hiện tại. Tiếp tục?"
                                            data-confirm-title="Làm mới mã QR">
                                        <i class="bi bi-arrow-clockwise"></i> Làm mới QR
                                    </button>
                                </form>
                            </div>
                            <div class="font-mono break-all bg-white px-2 py-1.5 rounded border border-slate-200 text-[11px]">{{ $qrCheckUrl }}</div>
                            <p class="{{ $tokenExpired ? 'text-rose-700 bg-rose-50 border-rose-100' : 'text-slate-600 bg-white border-slate-100' }} border rounded-lg px-2 py-1.5">
                                <i class="bi bi-hourglass-split"></i>
                                TTL QR:
                                @if($ttlLabel)
                                    hết hạn <strong>{{ $ttlLabel }}</strong>
                                    ({{ $teachSession->qr_ttl_minutes ?? '—' }} phút)
                                    @if($tokenExpired)
                                        — <strong>ĐÃ HẾT HẠN</strong>, hãy làm mới.
                                    @endif
                                @else
                                    không giới hạn theo TTL (chỉ đóng buổi).
                                @endif
                            </p>
                            @if($teachSession->require_campus_wifi || $teachSession->mode === 'qr')
                                <p class="text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-2 py-1.5">
                                    <i class="bi bi-wifi"></i> Check-in QR yêu cầu HV trên <strong>Wi‑Fi trường</strong> (IP/CIDR
                                    @if(\Modules\Lms\Support\CampusNetwork::isProbeRequired())
                                        + <strong>probe LAN</strong>
                                    @endif
                                    ). Không kết nối được → điểm miệng bên dưới.
                                </p>
                            @endif
                        </div>
                    @endif

                    @php
                        $wifiOkCount = $teachSessionRecords->filter(fn ($r) => $r->network_ok === true)->count();
                        $wifiFailCount = $teachSessionRecords->filter(fn ($r) => $r->network_ok === false)->count();
                        $qrMethodCount = $teachSessionRecords->filter(fn ($r) => in_array($r->method, ['qr', 'self'], true))->count();
                        $manualMethodCount = $teachSessionRecords->filter(fn ($r) => $r->method === 'manual')->count();
                    @endphp
                    <div class="px-3 py-2 bg-slate-50/80 border-b border-slate-100 text-[11px] text-slate-600 flex flex-wrap items-center gap-x-3 gap-y-1">
                        <span>Tự/QR <strong class="text-teal-800">{{ $qrMethodCount }}</strong></span>
                        <span class="text-slate-300">·</span>
                        <span>Miệng <strong>{{ $manualMethodCount }}</strong></span>
                        <span class="text-slate-300">·</span>
                        <span class="text-emerald-700">Wi‑Fi OK <strong>{{ $wifiOkCount }}</strong></span>
                        @if($wifiFailCount > 0)
                            <span class="text-slate-300">·</span>
                            <span class="text-rose-600">Ngoài mạng <strong>{{ $wifiFailCount }}</strong></span>
                        @endif
                        <span class="text-slate-400 ml-auto">Mạng = IP check-in + dải trường</span>
                    </div>

                    <form method="POST" action="{{ route('lms.teach.attendance.mark', [$course, $teachSession]) }}" data-turbo="false" id="att-mark-form">
                        @csrf
                        <div class="px-3 py-2 text-xs text-slate-500 border-b border-slate-100 bg-slate-50/80">
                            Bấm <strong>Có mặt / Vắng</strong> từng HV (hoặc «Cả lớp…» trên đầu) → rồi <strong>Lưu điểm danh</strong>. Không cần popup xác nhận.
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <thead class="bg-slate-50 text-left text-xs text-slate-500">
                                <tr>
                                    <th class="px-3 py-2 font-semibold">Học viên</th>
                                    <th class="px-3 py-2 font-semibold min-w-[16rem]">Trạng thái</th>
                                    <th class="px-3 py-2 font-semibold">Cách điểm</th>
                                    <th class="px-3 py-2 font-semibold">Mạng (IP)</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                @forelse($teachStudents as $i => $m)
                                    @php
                                        $rec = $teachSessionRecords[$m->user_id] ?? null;
                                        $cur = $rec->status ?? 'absent';
                                        $methodLabel = match ($rec->method ?? null) {
                                            'qr' => 'QR',
                                            'self' => 'Tự check-in',
                                            'manual' => 'Miệng / GV',
                                            'gps' => 'GPS',
                                            default => $rec ? ($rec->method ?: '—') : '—',
                                        };
                                    @endphp
                                    <tr class="att-row" data-status="{{ $cur }}">
                                        <td class="px-3 py-2">
                                            <input type="hidden" name="records[{{ $i }}][user_id]" value="{{ $m->user_id }}">
                                            <div class="font-medium text-slate-900">{{ $m->user->name }}</div>
                                            <div class="text-[11px] text-slate-400">{{ $m->user->email }}</div>
                                        </td>
                                        <td class="px-3 py-2">
                                            <input type="hidden" name="records[{{ $i }}][status]" value="{{ $cur }}" class="att-status-input" data-native-select>
                                            <div class="att-status-switch" role="group" aria-label="Trạng thái điểm danh">
                                                <button type="button" class="att-chip {{ $cur === 'present' ? 'is-on is-present' : '' }}" data-att-val="present">Có mặt</button>
                                                <button type="button" class="att-chip {{ $cur === 'absent' ? 'is-on is-absent' : '' }}" data-att-val="absent">Vắng</button>
                                                <button type="button" class="att-chip {{ $cur === 'late' ? 'is-on is-late' : '' }}" data-att-val="late">Muộn</button>
                                                <button type="button" class="att-chip {{ $cur === 'excused' ? 'is-on is-excused' : '' }}" data-att-val="excused">Phép</button>
                                            </div>
                                        </td>
                                        <td class="px-3 py-2 text-xs">
                                            @if($rec?->checked_in_at)
                                                <div class="font-medium text-slate-700">{{ $methodLabel }}</div>
                                                <div class="text-slate-400">{{ $rec->checked_in_at->format('H:i d/m') }}</div>
                                            @else
                                                <span class="text-slate-400">Chưa điểm</span>
                                            @endif
                                        </td>
                                        <td class="px-3 py-2 text-xs">
                                            @if(!$rec || ($rec->network_ok === null && ! $rec->client_ip))
                                                @if(($rec->method ?? null) === 'manual')
                                                    <span class="text-slate-500">Miệng (không check Wi‑Fi)</span>
                                                @else
                                                    <span class="text-slate-400">—</span>
                                                @endif
                                            @elseif($rec->network_ok === true)
                                                <div class="text-emerald-700 font-medium"><i class="bi bi-wifi"></i> OK</div>
                                                @if($rec->client_ip)
                                                    <div class="font-mono text-[11px] text-slate-500">{{ $rec->client_ip }}</div>
                                                @endif
                                            @elseif($rec->network_ok === false)
                                                <div class="text-rose-600 font-medium"><i class="bi bi-wifi-off"></i> Ngoài mạng</div>
                                                @if($rec->client_ip)
                                                    <div class="font-mono text-[11px] text-slate-500">{{ $rec->client_ip }}</div>
                                                @endif
                                            @else
                                                @if($rec->client_ip)
                                                    <span class="font-mono text-slate-500">{{ $rec->client_ip }}</span>
                                                @else
                                                    <span class="text-slate-400">—</span>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-10 text-center text-slate-500">Khóa chưa có học viên.</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($teachStudents->isNotEmpty())
                            <div class="p-3 border-t border-slate-100 flex justify-end">
                                <button type="submit" class="lms-btn-solid">
                                    <i class="bi bi-save"></i> Lưu điểm danh
                                </button>
                            </div>
                        @endif
                    </form>
                </div>
            @endif
        </div>
    </div>
</section>

{{-- ========== CLASS DASHBOARD (GV-6) ========== --}}
<section class="lms-panel {{ $activeTab === 'class' ? 'is-active' : '' }}" data-panel="class">
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <div class="lms-card p-4">
            <div class="text-xs text-slate-500">Học viên</div>
            <div class="text-2xl font-bold text-slate-900">{{ $teachStudents->count() }}</div>
        </div>
        <div class="lms-card p-4">
            <div class="text-xs text-slate-500">Cảnh báo mở</div>
            <div class="text-2xl font-bold text-amber-700">{{ $classAlerts->count() }}</div>
        </div>
        <div class="lms-card p-4">
            <div class="text-xs text-slate-500">Chứng chỉ đã cấp</div>
            <div class="text-2xl font-bold text-teal-700">{{ $classCerts->count() }}</div>
        </div>
        <div class="lms-card p-4">
            <div class="text-xs text-slate-500">Bài tập · phiên ĐD</div>
            <div class="text-2xl font-bold text-slate-800">{{ $assignments->count() }} · {{ $sessions->count() }}</div>
        </div>
    </div>

    <div class="grid lg:grid-cols-5 gap-4 mb-4">
        {{-- Roster progress --}}
        <div class="lms-card overflow-hidden lg:col-span-3">
            <div class="lms-card-head">Tiến độ &amp; chuyên cần theo HV</div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-xs text-slate-500">
                    <tr>
                        <th class="px-3 py-2 font-semibold">Học viên</th>
                        <th class="px-3 py-2 font-semibold">Tiến độ</th>
                        <th class="px-3 py-2 font-semibold">Chuyên cần</th>
                        <th class="px-3 py-2 font-semibold">Điểm TB</th>
                        <th class="px-3 py-2 font-semibold">CC</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @forelse($teachStudents as $m)
                        @php
                            $ps = $classProgress[$m->user_id] ?? null;
                            $att = $teachAttendanceStats[$m->user_id] ?? ['pct' => 0];
                            $gr = $gradeMatrix['rows'][$m->user_id] ?? null;
                            $hasCert = isset($classCerts[$m->user_id]);
                            $pct = (float) ($ps->overall_pct ?? 0);
                        @endphp
                        <tr>
                            <td class="px-3 py-2">
                                <div class="font-medium text-slate-900">{{ $m->user->name }}</div>
                                <div class="text-[11px] text-slate-400">{{ $m->user->email }}</div>
                            </td>
                            <td class="px-3 py-2">
                                <div class="flex items-center gap-2 min-w-[7rem]">
                                    <div class="flex-1 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full bg-teal-600" style="width:{{ min(100, $pct) }}%"></div>
                                    </div>
                                    <span class="text-xs font-semibold tabular-nums">{{ number_format($pct, 0) }}%</span>
                                </div>
                            </td>
                            <td class="px-3 py-2">
                                <span class="font-semibold tabular-nums {{ ($att['pct'] ?? 0) >= 80 ? 'text-emerald-700' : (($att['pct'] ?? 0) >= 50 ? 'text-amber-700' : 'text-rose-600') }}">
                                    {{ $att['pct'] ?? 0 }}%
                                </span>
                            </td>
                            <td class="px-3 py-2 tabular-nums">
                                {{ $gr['final_score'] ?? $gr['computed_score'] ?? '—' }}
                            </td>
                            <td class="px-3 py-2">
                                @if($hasCert)
                                    <span class="text-xs text-emerald-700 font-medium"><i class="bi bi-award"></i> Có</span>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Chưa có học viên.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Alerts G6 --}}
        <div class="lms-card p-4 lg:col-span-2">
            <div class="flex flex-wrap justify-between gap-2 items-center mb-3">
                <div class="font-semibold text-slate-800"><i class="bi bi-exclamation-triangle text-amber-600"></i> Cảnh báo lớp</div>
                <form method="POST" action="{{ route('lms.teach.alerts.evaluate', $course) }}" data-turbo="false">
                    @csrf
                    <input type="hidden" name="teach" value="1">
                    <button type="submit" class="lms-btn-solid text-xs" style="padding:0.3rem 0.6rem">
                        <i class="bi bi-radar"></i> Quét cảnh báo
                    </button>
                </form>
            </div>
            <div class="space-y-2 max-h-80 overflow-y-auto">
                @forelse($classAlerts as $alert)
                    <div class="rounded-xl border px-3 py-2 text-sm
                        {{ ($alert->severity ?? '') === 'critical' ? 'border-rose-200 bg-rose-50' : 'border-amber-200 bg-amber-50' }}">
                        <div class="text-xs text-slate-500">{{ $alert->user->name ?? 'HV' }}</div>
                        <div class="font-semibold text-slate-900">{{ $alert->title }}</div>
                        @if($alert->body)
                            <div class="text-xs mt-0.5 text-slate-600">{{ \Illuminate\Support\Str::limit($alert->body, 120) }}</div>
                        @endif
                        <form method="POST" action="{{ route('lms.teach.alerts.resolve', [$course, $alert]) }}"
                              class="mt-2 flex flex-wrap gap-1 items-center" data-turbo="false">
                            @csrf
                            <input type="hidden" name="teach" value="1">
                            <input type="text" name="note" maxlength="500" placeholder="Ghi chú xử lý (tuỳ chọn)"
                                   class="flex-1 min-w-[8rem] border border-slate-200 rounded-lg text-xs px-2 py-1.5">
                            <button type="submit" class="lms-btn lms-btn-ghost text-xs" style="padding:0.25rem 0.5rem">Đã xử lý</button>
                        </form>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Không có cảnh báo mở. Bấm «Quét cảnh báo» để đánh giá lại lớp.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Gradebook matrix + override (Sprint 6 G3) --}}
    <div class="lms-card overflow-hidden mb-4">
        <div class="lms-card-head flex flex-wrap justify-between gap-2 items-center">
            <span>Bảng điểm lớp</span>
            <div class="flex flex-wrap gap-2">
                <form method="POST" action="{{ route('lms.teach.gradebook.refresh', $course) }}" data-turbo="false">
                    @csrf
                    <input type="hidden" name="teach" value="1">
                    <button type="submit" class="lms-btn-solid text-xs" style="padding:0.3rem 0.65rem">
                        <i class="bi bi-arrow-repeat"></i> Làm mới snapshot
                    </button>
                </form>
                <form method="POST" action="{{ route('lms.teach.gradebook.transfer', $course) }}" data-turbo="false">
                    @csrf
                    <button type="submit" class="lms-btn lms-btn-ghost text-xs" style="padding:0.3rem 0.65rem">
                        <i class="bi bi-arrow-left-right"></i> Chuyển Quản lý điểm
                    </button>
                </form>
            </div>
        </div>
        @php
            $gRows = $gradeMatrix['rows'] ?? [];
            $gAssignments = $gradeMatrix['assignments'] ?? collect();
            $gExams = $gradeMatrix['exams'] ?? collect();
            if (! $gAssignments instanceof \Illuminate\Support\Collection) {
                $gAssignments = collect($gAssignments);
            }
            if (! $gExams instanceof \Illuminate\Support\Collection) {
                $gExams = collect($gExams);
            }
        @endphp
        @if(empty($gRows) && $teachStudents->isEmpty())
            <p class="p-6 text-sm text-slate-500 text-center">Chưa có dữ liệu gradebook. HV nộp bài / thi / điểm danh sẽ hiện tại đây.</p>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs">
                    <thead class="bg-slate-50 text-left text-slate-500">
                    <tr>
                        <th class="px-3 py-2 font-semibold sticky left-0 bg-slate-50 z-10">HV</th>
                        @foreach($gAssignments as $a)
                            <th class="px-2 py-2 font-semibold whitespace-nowrap" title="{{ $a->title }}">BT: {{ \Illuminate\Support\Str::limit($a->title, 14) }}</th>
                        @endforeach
                        @foreach($gExams as $ex)
                            <th class="px-2 py-2 font-semibold whitespace-nowrap" title="{{ $ex->title }}">Thi: {{ \Illuminate\Support\Str::limit($ex->title, 14) }}</th>
                        @endforeach
                        <th class="px-2 py-2 font-semibold">TB BT</th>
                        <th class="px-2 py-2 font-semibold">TB Thi</th>
                        <th class="px-2 py-2 font-semibold">CC%</th>
                        <th class="px-2 py-2 font-semibold">Tự động</th>
                        <th class="px-3 py-2 font-semibold min-w-[12rem]">Tổng kết (override)</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                    @foreach($teachStudents as $m)
                        @php $row = $gRows[$m->user_id] ?? null; @endphp
                        <tr>
                            <td class="px-3 py-2 font-medium sticky left-0 bg-white z-10 whitespace-nowrap">{{ $m->user->name }}</td>
                            @foreach($gAssignments as $a)
                                @php $cell = $row['assignment_cells'][$a->id] ?? null; @endphp
                                <td class="px-2 py-2 tabular-nums text-center">{{ $cell['score'] ?? '—' }}</td>
                            @endforeach
                            @foreach($gExams as $ex)
                                @php $cell = $row['exam_cells'][$ex->id] ?? null; @endphp
                                <td class="px-2 py-2 tabular-nums text-center">{{ $cell['score'] ?? '—' }}</td>
                            @endforeach
                            <td class="px-2 py-2 tabular-nums text-center">{{ $row['assignment_avg'] ?? '—' }}</td>
                            <td class="px-2 py-2 tabular-nums text-center">{{ $row['exam_avg'] ?? '—' }}</td>
                            <td class="px-2 py-2 tabular-nums text-center">{{ isset($row['attendance_pct']) && $row['attendance_pct'] !== null ? $row['attendance_pct'].'%' : '—' }}</td>
                            <td class="px-2 py-2 tabular-nums text-center font-semibold text-slate-700">{{ $row['computed_score'] ?? '—' }}</td>
                            <td class="px-2 py-2">
                                <form method="POST" action="{{ route('lms.teach.gradebook.override', [$course, $m->user]) }}"
                                      class="flex flex-wrap gap-1 items-center" data-turbo="false">
                                    @csrf
                                    <input type="hidden" name="teach" value="1">
                                    <input type="number" step="0.1" min="0" max="10" name="final_score"
                                           value="{{ $row['final_score'] ?? '' }}"
                                           placeholder="{{ $row['computed_score'] ?? '—' }}"
                                           class="w-16 border border-slate-200 rounded-lg px-1.5 py-1 text-xs">
                                    <input type="text" name="note" value="{{ $row['note'] ?? '' }}"
                                           placeholder="Ghi chú" maxlength="2000"
                                           class="w-24 border border-slate-200 rounded-lg px-1.5 py-1 text-xs">
                                    <button type="submit" class="lms-btn-solid text-[11px]" style="padding:0.25rem 0.5rem">Lưu</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <p class="px-3 py-2 text-[11px] text-slate-400 border-t border-slate-100">
                Tổng kết trống = dùng điểm tự động. Lưu override ghi vào gradebook (giống admin).
            </p>
        @endif
    </div>

    {{-- Survey manage G4 + averages --}}
    <div class="grid lg:grid-cols-2 gap-4 mb-4">
        <div class="lms-card p-4">
            <div class="font-semibold text-slate-800 mb-2"><i class="bi bi-plus-circle text-teal-700"></i> Tạo khảo sát (G4)</div>
            <p class="text-xs text-slate-500 mb-3">Tạo kèm bộ câu mặc định (rating + góp ý). Có thể thêm câu / công bố ngay.</p>
            <form method="POST" action="{{ route('lms.teach.surveys.store', $course) }}" class="space-y-2" data-turbo="false">
                @csrf
                <input type="hidden" name="teach" value="1">
                <input type="text" name="title" required maxlength="255" placeholder="Tiêu đề khảo sát *"
                       class="w-full border border-slate-200 rounded-lg text-sm px-3 py-2"
                       value="Khảo sát chất lượng · {{ $course->title }}">
                <textarea name="description" rows="2" placeholder="Mô tả (tuỳ chọn)"
                          class="w-full border border-slate-200 rounded-lg text-sm px-3 py-2"></textarea>
                <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                    <input type="checkbox" name="is_anonymous" value="1"> Ẩn danh
                </label>
                <label class="inline-flex items-center gap-2 text-xs text-slate-600">
                    <input type="checkbox" name="is_published" value="1"> Công bố ngay cho HV
                </label>
                <button type="submit" class="lms-btn-solid text-sm">Tạo khảo sát</button>
            </form>
            @if(!empty($surveyTemplates) && $surveyTemplates->isNotEmpty())
                <div class="mt-4 pt-3 border-t border-slate-100">
                    <div class="text-xs font-semibold text-slate-600 mb-2">Áp dụng mẫu khảo sát</div>
                    <form method="POST" action="{{ route('lms.teach.surveys.apply-template', $course) }}" class="space-y-2" data-turbo="false">
                        @csrf
                        <input type="hidden" name="teach" value="1">
                        <select name="template_id" required class="tom-select w-full border rounded-lg text-sm px-3 py-2" data-tom-select>
                            <option value="">— Chọn template —</option>
                            @foreach($surveyTemplates as $tpl)
                                <option value="{{ $tpl->id }}">{{ $tpl->title }} ({{ $tpl->questions_count ?? $tpl->questions->count() }} câu)</option>
                            @endforeach
                        </select>
                        <label class="inline-flex items-center gap-2 text-xs"><input type="checkbox" name="is_published" value="1"> Công bố ngay</label>
                        <button type="submit" class="lms-btn lms-btn-ghost text-sm">Áp template vào khóa</button>
                    </form>
                </div>
            @endif
            <ul class="mt-4 divide-y divide-slate-100 text-sm">
                @forelse($surveys as $sv)
                    <li class="py-2 flex flex-wrap justify-between gap-2 items-start">
                        <div>
                            <div class="font-medium text-slate-900">{{ $sv->title }}</div>
                            <div class="text-xs text-slate-400">
                                {{ $sv->questions_count ?? $sv->questions->count() }} câu
                                · {{ $sv->responses_count ?? 0 }} phản hồi
                                · {{ $sv->is_published ? 'Công bố' : 'Nháp' }}
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-1">
                            <form method="POST" action="{{ route('lms.teach.surveys.publish', [$course, $sv]) }}" data-turbo="false">
                                @csrf
                                <input type="hidden" name="teach" value="1">
                                <button class="lms-btn lms-btn-ghost text-xs" style="padding:0.25rem 0.5rem">
                                    {{ $sv->is_published ? 'Ẩn' : 'Công bố' }}
                                </button>
                            </form>
                        </div>
                    </li>
                    <li class="pb-3">
                        <form method="POST" action="{{ route('lms.teach.surveys.questions.store', [$course, $sv]) }}"
                              class="flex flex-wrap gap-1 items-end" data-turbo="false">
                            @csrf
                            <input type="hidden" name="teach" value="1">
                            <select name="type" class="border rounded-lg text-xs px-2 py-1.5" data-native-select>
                                <option value="rating_1_5">Rating 1–5</option>
                                <option value="text">Text</option>
                                <option value="mcq">MCQ</option>
                            </select>
                            <input name="stem" required placeholder="Thêm câu hỏi…"
                                   class="flex-1 min-w-[10rem] border rounded-lg text-xs px-2 py-1.5">
                            <button class="lms-btn-solid text-xs" style="padding:0.3rem 0.55rem">Thêm</button>
                        </form>
                    </li>
                @empty
                    <li class="py-4 text-center text-slate-400 text-sm">Chưa có khảo sát.</li>
                @endforelse
            </ul>
        </div>
        <div class="lms-card p-4">
            <div class="font-semibold text-slate-800 mb-3"><i class="bi bi-clipboard-data text-teal-700"></i> Khảo sát — TB rating</div>
            @if(empty($classSurveyStats))
                <p class="text-sm text-slate-500">Chưa có phản hồi rating.</p>
            @else
                <div class="space-y-3">
                    @foreach($classSurveyStats as $sv)
                        <div class="rounded-xl border border-slate-100 p-3">
                            <div class="font-medium text-slate-900 text-sm">{{ $sv['title'] }}</div>
                            <div class="text-xs text-slate-400 mb-2">{{ $sv['responses'] }} phản hồi</div>
                            @forelse($sv['ratings'] as $r)
                                <div class="flex justify-between text-xs py-1 border-t border-slate-50">
                                    <span class="text-slate-600 truncate mr-2">{{ $r['stem'] }}</span>
                                    <span class="font-bold text-teal-800 tabular-nums">{{ $r['avg'] }}/5 <span class="font-normal text-slate-400">(n={{ $r['n'] }})</span></span>
                                </div>
                            @empty
                                <p class="text-xs text-slate-400">Không có câu rating.</p>
                            @endforelse
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Certificates G5 --}}
    <div class="lms-card overflow-hidden mb-4">
        <div class="lms-card-head flex flex-wrap justify-between gap-2 items-center">
            <span><i class="bi bi-award text-teal-700"></i> Chứng chỉ (G5 — cấp khi đủ điều kiện)</span>
            <form method="POST" action="{{ route('lms.teach.certificates.issue-eligible', $course) }}" data-turbo="false"
                  data-confirm="Cấp chứng chỉ cho tất cả HV đủ điều kiện?"
                  data-confirm-title="Cấp hàng loạt"
                  data-confirm-ok="Cấp đủ ĐK">
                @csrf
                <input type="hidden" name="teach" value="1">
                <button type="submit" class="lms-btn-solid text-xs" style="padding:0.3rem 0.65rem">
                    Cấp hàng loạt (đủ ĐK)
                </button>
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs text-slate-500">
                <tr>
                    <th class="px-3 py-2 font-semibold">Học viên</th>
                    <th class="px-3 py-2 font-semibold">Đủ ĐK?</th>
                    <th class="px-3 py-2 font-semibold">Đã cấp</th>
                    <th class="px-3 py-2 font-semibold">Thao tác</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                @forelse($teachStudents as $m)
                    @php
                        $elig = $classCertEligibility[$m->user_id] ?? ['eligible' => false, 'reasons' => []];
                        $cert = $classCerts[$m->user_id] ?? null;
                    @endphp
                    <tr>
                        <td class="px-3 py-2">
                            <div class="font-medium">{{ $m->user->name }}</div>
                            <div class="text-[11px] text-slate-400">{{ $m->user->email }}</div>
                        </td>
                        <td class="px-3 py-2 text-xs">
                            @if(!empty($elig['eligible']))
                                <span class="text-emerald-700 font-semibold">Đủ</span>
                            @else
                                <span class="text-amber-700">Chưa</span>
                                @if(!empty($elig['reasons']))
                                    <div class="text-slate-400 mt-0.5">{{ implode('; ', $elig['reasons']) }}</div>
                                @endif
                            @endif
                        </td>
                        <td class="px-3 py-2 text-xs">
                            @if($cert)
                                <span class="font-mono text-teal-800">{{ $cert->code }}</span>
                                <div class="text-slate-400">{{ $cert->issued_at?->format('d/m/Y') }}</div>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2">
                            @if(!$cert)
                                <form method="POST" action="{{ route('lms.teach.certificates.issue-one', [$course, $m->user]) }}" data-turbo="false">
                                    @csrf
                                    <input type="hidden" name="teach" value="1">
                                    <button type="submit" class="lms-btn-solid text-xs" style="padding:0.25rem 0.55rem"
                                        @disabled(empty($elig['eligible']))
                                        title="{{ !empty($elig['eligible']) ? 'Cấp khi đủ ĐK' : 'Chưa đủ điều kiện' }}">
                                        Cấp CC
                                    </button>
                                </form>
                            @else
                                <span class="text-xs text-slate-400">Đã có</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-500">Chưa có HV.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="px-3 py-2 text-[11px] text-slate-400 border-t border-slate-100">
            Giảng viên chỉ cấp khi học viên <strong>đủ điều kiện</strong>; quản trị viên có thể xử lý ngoại lệ tại màn hình quản trị.
        </p>
    </div>
</section>

{{-- ========== ENGAGE (GV-7) ========== --}}
<section class="lms-panel {{ $activeTab === 'engage' ? 'is-active' : '' }}" data-panel="engage">
    <div class="grid lg:grid-cols-2 gap-4">
        <div class="lms-card p-4">
            <div class="font-semibold text-slate-800 mb-1"><i class="bi bi-megaphone text-teal-700"></i> Thông báo cả lớp</div>
            <p class="text-xs text-slate-500 mb-3">Gửi tới chuông thông báo LMS của mọi học viên trong khóa (system_notifications).</p>
            <form method="POST" action="{{ route('lms.teach.announce', $course) }}" class="space-y-2" data-turbo="false">
                @csrf
                <input type="text" name="title" required maxlength="200" placeholder="Tiêu đề *"
                       class="w-full border border-slate-200 rounded-lg text-sm px-3 py-2"
                       value="{{ old('title', 'Thông báo từ GV · '.$course->title) }}">
                <textarea name="message" required rows="4" maxlength="2000" placeholder="Nội dung thông báo *"
                          class="w-full border border-slate-200 rounded-lg text-sm px-3 py-2">{{ old('message') }}</textarea>
                <div>
                    <label class="text-[11px] font-medium text-slate-500 mb-1 block">Mở tab khi HV bấm chuông (H1)</label>
                    <select name="link_tab" class="tom-select w-full" data-tom-select>
                        <option value="overview">Tổng quan</option>
                        <option value="assignments">Bài tập</option>
                        <option value="exams">Thi</option>
                        <option value="attendance">Điểm danh</option>
                        <option value="materials">Tài liệu</option>
                        <option value="surveys">Khảo sát</option>
                        <option value="forum">Diễn đàn</option>
                        <option value="chat">Chat</option>
                    </select>
                </div>
                <button type="submit" class="lms-btn-solid">
                    <i class="bi bi-send"></i> Gửi thông báo lớp
                </button>
            </form>
        </div>
        <div class="space-y-4">
            <div class="lms-card p-4 text-sm text-slate-600 space-y-2">
                <div class="font-semibold text-slate-800">Công cụ điều hành</div>
                <ul class="list-disc pl-5 space-y-1 text-sm">
                    <li><strong>Chat:</strong> tab Chat — khóa/mở chat khóa · xóa tin (nút Xóa dưới bubble).</li>
                    <li><strong>Diễn đàn:</strong> tab Diễn đàn — Ghim / Khóa từng chủ đề.</li>
                    <li>HV nhận thông báo ở chuông trên navbar LMS.</li>
                </ul>
                <div class="flex flex-wrap gap-2 pt-2">
                    <button type="button" class="lms-btn lms-btn-ghost text-xs" data-tab-jump="chat">Mở tab Chat</button>
                    <button type="button" class="lms-btn lms-btn-ghost text-xs" data-tab-jump="forum">Mở tab Diễn đàn</button>
                </div>
            </div>
            <div class="lms-card p-4">
                <div class="font-semibold text-slate-800 mb-2">Khóa chat nhanh</div>
                <form method="POST" action="{{ route('lms.learn.chat.toggle-lock', $course) }}" data-turbo="false">
                    @csrf
                    <button class="lms-btn-solid text-sm" type="submit">
                        {{ $course->chat_locked ? 'Mở lại chat khóa học' : 'Khóa chat khóa học' }}
                    </button>
                    <p class="text-xs text-slate-400 mt-2">
                        Trạng thái: <strong>{{ $course->chat_locked ? 'Đang khóa' : 'Đang mở' }}</strong>
                    </p>
                </form>
            </div>
        </div>
    </div>
</section>
@endif

{{-- QR popup modal (GV) — z-index cao, append body khi mở --}}
<div id="lms-qr-modal" aria-hidden="true">
    <div class="lms-qr-dialog" role="dialog" aria-modal="true" aria-labelledby="lms-qr-title">
        <button type="button" id="lms-qr-close" class="absolute top-2.5 right-3 text-slate-400 hover:text-slate-700 text-2xl leading-none p-1" aria-label="Đóng">&times;</button>
        <div class="text-center">
            <div class="font-bold text-slate-900 mb-1 pr-6" id="lms-qr-title">Mã QR điểm danh</div>
            <p class="text-xs text-slate-500 mb-3">HV đăng nhập LMS + kết nối Wi‑Fi trường rồi quét</p>
            <div id="lms-qr-box" aria-label="Mã QR"></div>
            <a id="lms-qr-link" href="#" target="_blank" rel="noopener" class="text-[11px] text-teal-700 break-all block mt-3"></a>
        </div>
    </div>
</div>

{{-- ========== OVERVIEW ========== --}}
<section class="lms-panel {{ $activeTab === 'overview' ? 'is-active' : '' }}" data-panel="overview">
    <div class="grid sm:grid-cols-3 gap-3 mb-4">
        <div class="lms-card p-4">
            <div class="text-xs text-slate-500">Tiến độ</div>
            <div class="text-2xl font-bold text-teal-700">{{ number_format($progressSummary->overall_pct ?? 0, 1) }}%</div>
        </div>
        <div class="lms-card p-4">
            <div class="text-xs text-slate-500">Điểm tổng hợp</div>
            <div class="text-2xl font-bold text-slate-800">{{ $myGrade['final_score'] ?? $myGrade['computed_score'] ?? '—' }}</div>
        </div>
        <div class="lms-card p-4">
            <div class="text-xs text-slate-500">Cảnh báo</div>
            <div class="text-2xl font-bold text-amber-700">{{ $alerts->count() }}</div>
        </div>
    </div>
    <div class="lms-card p-4 text-sm text-slate-600">
        Chọn tab trên thanh điều hướng để xem nội dung ngay bên dưới.
        <strong>Bài học</strong> và <strong>thi online</strong> sẽ mở trang riêng sau khi xác nhận.
    </div>
</section>

{{-- ========== LESSONS (navigate after confirm) ========== --}}
<section class="lms-panel {{ $activeTab === 'lessons' ? 'is-active' : '' }}" data-panel="lessons">
    <div class="lms-card overflow-hidden">
        <div class="lms-card-head">Bài học</div>
        <ul class="divide-y divide-slate-100 p-2">
            @forelse($course->lessons as $lesson)
                <li>
                    <button type="button"
                            class="lms-click-row w-full text-left px-4 py-3"
                            data-confirm-nav
                            data-title="Mở bài học"
                            data-message="Bạn muốn vào bài «{{ $lesson->title }}»?"
                            data-href="{{ route('lms.learn.lessons.show', [$course, $lesson]) }}"
                            data-ok="Vào học">
                        <span class="font-medium text-slate-900">{{ $lesson->sort_order }}. {{ $lesson->title }}</span>
                        @if($lesson->week_number)
                            <span class="text-xs text-slate-400 ml-1">Tuần {{ $lesson->week_number }}</span>
                        @endif
                    </button>
                </li>
            @empty
                <li class="px-4 py-8 text-center text-sm text-slate-500">Chưa có bài học công khai.</li>
            @endforelse
        </ul>
    </div>
</section>

{{-- ========== MATERIALS ========== --}}
<section class="lms-panel {{ $activeTab === 'materials' ? 'is-active' : '' }}" data-panel="materials">
    <div class="lms-card overflow-hidden">
        <div class="lms-card-head">Tài liệu & SCORM</div>
        <ul class="divide-y divide-slate-100 p-2">
            @foreach($course->materials as $m)
                <li>
                    <button type="button"
                            class="lms-click-row w-full text-left px-4 py-3 flex justify-between gap-2 items-center"
                            data-confirm-nav
                            data-title="Xem tài liệu"
                            data-message="Mở «{{ $m->title }}» ({{ $m->kindLabel() }})?"
                            data-href="{{ route('lms.learn.materials.open', [$course, $m]) }}"
                            data-ok="Xem ngay">
                        <span>
                            <span class="font-medium block">{{ $m->title }}</span>
                            <span class="text-xs text-slate-400">{{ $m->kindLabel() }} · {{ $m->humanSize() }}</span>
                        </span>
                        <i class="bi bi-chevron-right text-slate-400"></i>
                    </button>
                </li>
            @endforeach
            @foreach($course->scormPackages as $sc)
                <li>
                    <button type="button"
                            class="lms-click-row w-full text-left px-4 py-3 flex justify-between gap-2 items-center"
                            data-confirm-nav
                            data-title="Chạy SCORM"
                            data-message="Mở gói SCORM «{{ $sc->title }}»?"
                            data-href="{{ route('lms.learn.scorm.play', [$course, $sc]) }}"
                            data-ok="Chạy">
                        <span>
                            <span class="font-medium block">{{ $sc->title }}</span>
                            <span class="text-xs text-slate-400">SCORM {{ $sc->version ?? '' }}</span>
                        </span>
                        <i class="bi bi-chevron-right text-slate-400"></i>
                    </button>
                </li>
            @endforeach
            @if($course->materials->isEmpty() && $course->scormPackages->isEmpty())
                <li class="px-4 py-8 text-center text-sm text-slate-500">Chưa có tài liệu.</li>
            @endif
        </ul>
    </div>
</section>

{{-- ========== ASSIGNMENTS (by lesson) ========== --}}
<section class="lms-panel {{ $activeTab === 'assignments' ? 'is-active' : '' }}" data-panel="assignments">
    @php
        $byLesson = $assignments->groupBy(fn ($a) => $a->lms_lesson_id ?: 0);
    @endphp
    <div class="space-y-4">
        @forelse($byLesson as $lessonId => $list)
            <div class="lms-card overflow-hidden">
                <div class="lms-card-head">
                    @if($lessonId)
                        Bài: {{ optional($list->first()->lesson)->title ?? ('#'.$lessonId) }}
                    @else
                        Bài tập chung (không gắn bài)
                    @endif
                </div>
                <div class="p-3 space-y-3">
                    @foreach($list as $a)
                        @php $sub = $mySubs[$a->id] ?? null; @endphp
                        <div class="lms-click-row p-3 border border-slate-100 rounded-xl"
                             data-assign-toggle="{{ $a->id }}">
                            <div class="flex justify-between gap-2">
                                <strong class="text-slate-900">{{ $a->title }}</strong>
                                <span class="text-xs text-slate-500">Max {{ $a->max_score }}
                                    @if($a->due_at) · Hạn {{ $a->due_at->format('d/m H:i') }} @endif
                                </span>
                            </div>
                            @if($sub)
                                <div class="text-xs text-emerald-700 mt-1">
                                    Đã nộp {{ $sub->submitted_at?->format('d/m H:i') }}
                                    · v{{ $sub->version_count ?? $sub->attempt_no ?? 1 }}
                                    @if($sub->status === 'graded') · Điểm <strong>{{ $sub->score }}</strong> @endif
                                    @if($sub->status === 'submitted') · <span class="text-amber-700">Chờ chấm</span> @endif
                                </div>
                            @endif
                        </div>
                        <div id="assign-form-{{ $a->id }}" class="hidden px-1 pb-2">
                            @if($a->description)
                                <p class="text-sm text-slate-600 mb-2 whitespace-pre-wrap">{{ $a->description }}</p>
                            @endif
                            {{-- Sprint 9 H3: timeline versions (chỉ khi đã có submission) --}}
                            @if($sub)
                                @php
                                    $vers = $sub->relationLoaded('versions')
                                        ? $sub->versions
                                        : $sub->versions()->orderBy('version_no')->get();
                                @endphp
                                @if($vers->isNotEmpty())
                                    <div class="mb-2 rounded-lg border border-slate-100 bg-slate-50 px-3 py-2 text-xs space-y-1">
                                        <div class="font-semibold text-slate-600 uppercase tracking-wide">Lịch sử nộp (H3)</div>
                                        @foreach($vers as $v)
                                            <div class="flex flex-wrap gap-2 justify-between">
                                                <span>v{{ $v->version_no }} · {{ $v->submitted_at?->format('d/m H:i') ?? '—' }} · {{ $v->status }}</span>
                                                <span>
                                                    @if($v->score !== null) điểm {{ $v->score }} @endif
                                                    @if($v->feedback) · {{ \Illuminate\Support\Str::limit($v->feedback, 60) }} @endif
                                                </span>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            @endif
                            @if($sub && $sub->feedback && $sub->status === 'graded')
                                <div class="mb-2 rounded-lg border border-amber-100 bg-amber-50/60 px-3 py-2 text-sm text-amber-950">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-amber-700 mb-0.5">Feedback GV</div>
                                    {{ $sub->feedback }}
                                    @if($sub->score !== null)
                                        <div class="text-xs mt-1 text-slate-600">Điểm hiện tại: <strong>{{ $sub->score }}/{{ $a->max_score }}</strong></div>
                                    @endif
                                </div>
                            @endif
                            @if($sub?->file_path)
                                <a href="{{ route('lms.learn.assignments.download-own', [$course, $a]) }}"
                                   class="inline-flex items-center gap-1 text-xs font-semibold text-teal-700 hover:text-teal-900"
                                   data-turbo="false">
                                    <i class="bi bi-shield-lock"></i>
                                    Tải lại file đã nộp: {{ $sub->file_name ?? 'Tệp đính kèm' }}
                                </a>
                            @endif
                            @php
                                $canResubmit = $sub && $sub->status === 'graded';
                                $showForm = $a->isOpen() || $a->allow_late;
                            @endphp
                            @if($showForm)
                                <form method="POST" action="{{ route('lms.learn.assignments.submit', [$course, $a]) }}" enctype="multipart/form-data" class="space-y-2">
                                    @csrf
                                    <textarea name="text_answer" rows="3" class="w-full border rounded-lg text-sm px-3 py-2" placeholder="Nội dung nộp">{{ old('text_answer', $sub->text_answer ?? '') }}</textarea>
                                    <input type="file" name="file" class="text-sm">
                                    <p class="text-[11px] text-slate-400">Dung lượng tối đa {{ \Modules\Lms\Support\LmsSettings::submissionMaxMegabytes() }} MB.</p>
                                    <button type="submit" class="lms-btn lms-btn-primary">
                                        {{ $canResubmit ? 'Nộp lại (tạo version mới)' : ($sub ? 'Cập nhật / version mới' : 'Nộp bài') }}
                                    </button>
                                </form>
                            @else
                                <p class="text-sm text-slate-400">Đã đóng nộp bài.</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="lms-card p-8 text-center text-slate-500 text-sm">Chưa có bài tập.</div>
        @endforelse
    </div>
</section>

{{-- ========== EXAMS ========== --}}
<section class="lms-panel {{ $activeTab === 'exams' ? 'is-active' : '' }}" data-panel="exams">
    <div class="space-y-3">
        @forelse($exams as $exam)
            @php $best = ($myExamAttempts[$exam->id] ?? collect())->first(); @endphp
            <button type="button"
                    class="lms-card lms-click-row w-full text-left p-4 flex flex-wrap justify-between gap-3 items-center"
                    @if($exam->isOpenNow())
                        data-confirm-nav
                        data-title="Bắt đầu làm bài thi"
                        data-message="Bạn sắp làm «{{ $exam->title }}» ({{ $exam->duration_minutes }} phút, tối đa {{ $exam->max_attempts }} lần). Xác nhận?"
                        data-href="{{ route('lms.learn.exams.start', [$course, $exam]) }}"
                        data-method="POST"
                        data-ok="Vào làm bài"
                    @endif>
                <div>
                    <strong>{{ $exam->title }}</strong>
                    <div class="text-xs text-slate-500 mt-0.5">
                        {{ $exam->duration_minutes }}' · {{ $exam->questions_count ?? 0 }} câu · {{ $exam->max_attempts }} lần
                        @if($exam->proctor_basic) · giám sát cơ bản @endif
                    </div>
                    @if($best)
                        <div class="text-xs text-emerald-700 mt-1">Điểm gần nhất: {{ $best->score }}/{{ $best->max_score }}</div>
                    @endif
                </div>
                <span class="text-xs font-semibold {{ $exam->isOpenNow() ? 'text-teal-700' : 'text-slate-400' }}">
                    {{ $exam->isOpenNow() ? 'Nhấn để làm bài →' : 'Chưa mở / đã đóng' }}
                </span>
            </button>
        @empty
            <div class="lms-card p-8 text-center text-slate-500 text-sm">Chưa có bài thi.</div>
        @endforelse
    </div>
</section>

{{-- ========== ATTENDANCE CALENDAR ========== --}}
<section class="lms-panel {{ $activeTab === 'attendance' ? 'is-active' : '' }}" data-panel="attendance">
    @php
        $month = request('m') ? \Carbon\Carbon::parse(request('m').'-01') : now()->startOfMonth();
        $start = $month->copy()->startOfMonth();
        $end = $month->copy()->endOfMonth();
        $pad = $start->dayOfWeekIso % 7; // Mon=1..Sun=7 → 0..6 with Sun=0 style use Mon start: dayOfWeekIso-1
        $pad = $start->dayOfWeekIso - 1;
    @endphp
    <div class="lms-card p-5">
        <div class="flex items-center justify-between mb-4">
            <button type="button" class="lms-btn lms-btn-ghost" data-cal-nav data-month="{{ $month->copy()->subMonth()->format('Y-m') }}">←</button>
            <h2 class="font-bold text-slate-800">{{ $month->translatedFormat('F Y') }}</h2>
            <button type="button" class="lms-btn lms-btn-ghost" data-cal-nav data-month="{{ $month->copy()->addMonth()->format('Y-m') }}">→</button>
        </div>
        <div class="lms-cal mb-2">
            @foreach(['T2','T3','T4','T5','T6','T7','CN'] as $d)
                <div class="lms-cal-dow">{{ $d }}</div>
            @endforeach
            @for($i = 0; $i < $pad; $i++)
                <div class="lms-cal-day is-empty"></div>
            @endfor
            @for($day = 1; $day <= $end->day; $day++)
                @php
                    $date = $month->copy()->day($day);
                    $key = $date->format('Y-m-d');
                    $info = $attendanceByDate[$key] ?? null;
                    $cls = ['lms-cal-day'];
                    if ($date->isToday()) $cls[] = 'is-today';
                    if ($info) {
                        $cls[] = 'has-session';
                        if (($info['my_status'] ?? null) === 'present') $cls[] = 'is-present';
                        if (($info['my_status'] ?? null) === 'absent') $cls[] = 'is-absent';
                    }
                @endphp
                <button type="button"
                        class="{{ implode(' ', $cls) }}"
                        @if($info)
                            data-att-day
                            data-date="{{ $key }}"
                            data-title="{{ $info['title'] }}"
                            data-status="{{ $info['my_status'] ?? '' }}"
                            data-status-label="{{ $info['my_status_label'] ?? '' }}"
                            data-open="{{ $info['open'] ? '1' : '0' }}"
                            data-can-checkin="{{ !empty($info['can_checkin']) ? '1' : '0' }}"
                            data-session="{{ $info['session_id'] }}"
                            data-token="{{ $info['token'] }}"
                            data-mode="{{ $info['mode'] }}"
                            data-method="{{ $info['method'] ?? '' }}"
                            data-ip="{{ $info['client_ip'] ?? '' }}"
                            data-net-ok="{{ isset($info['network_ok']) ? ($info['network_ok'] ? '1' : '0') : '' }}"
                            data-net-note="{{ e($info['network_note'] ?? '') }}"
                            data-checked-at="{{ $info['checked_in_at'] ?? '' }}"
                        @endif>
                    {{ $day }}
                </button>
            @endfor
        </div>
        <p class="text-xs text-slate-500 mt-3">
            Chấm xanh = có lịch điểm danh. Bấm ngày chưa điểm → nút <strong>Điểm danh</strong>.
        </p>
        <div id="att-detail" class="mt-4 hidden lms-card p-4 border border-teal-100"></div>
    </div>
</section>

{{-- ========== PROGRESS + ALERTS ========== --}}
<section class="lms-panel {{ $activeTab === 'progress' ? 'is-active' : '' }}" data-panel="progress">
    <div class="grid lg:grid-cols-5 gap-4">
        <div class="lms-card p-6 lg:col-span-2 text-center">
            <div class="text-sm text-slate-500 mb-1">Hoàn thành khóa</div>
            <div class="text-5xl font-bold text-teal-700">{{ number_format($progressSummary->overall_pct ?? 0, 1) }}%</div>
            <div class="mt-3 h-2.5 bg-slate-100 rounded-full overflow-hidden max-w-xs mx-auto">
                <div class="h-full bg-teal-600" style="width:{{ min(100, $progressSummary->overall_pct ?? 0) }}%"></div>
            </div>
            <ul class="text-sm text-left mt-5 space-y-2 max-w-xs mx-auto text-slate-600">
                <li class="flex justify-between"><span>Bài học</span><span>{{ ($progressSummary->lessons_done ?? 0).'/'.($progressSummary->lessons_total ?? 0) }}</span></li>
                <li class="flex justify-between"><span>Học liệu</span><span>{{ ($progressSummary->materials_done ?? 0).'/'.($progressSummary->materials_total ?? 0) }}</span></li>
                <li class="flex justify-between"><span>Bài tập</span><span>{{ ($progressSummary->assignments_done ?? 0).'/'.($progressSummary->assignments_total ?? 0) }}</span></li>
                <li class="flex justify-between"><span>Thi</span><span>{{ ($progressSummary->exams_done ?? 0).'/'.($progressSummary->exams_total ?? 0) }}</span></li>
            </ul>
        </div>
        <div class="lms-card p-5 lg:col-span-3">
            <div class="font-semibold text-slate-800 mb-3">Cảnh báo học tập</div>
            <div class="space-y-2">
                @forelse($alerts as $alert)
                    <div class="rounded-xl border px-3 py-2.5 text-sm
                        {{ $alert->severity === 'critical' ? 'border-rose-200 bg-rose-50 text-rose-900' : 'border-amber-200 bg-amber-50 text-amber-900' }}">
                        <div class="font-semibold">{{ $alert->title }}</div>
                        <div class="text-xs mt-0.5 opacity-90">{{ $alert->body }}</div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">Không có cảnh báo — bạn đang theo kịp tiến độ.</p>
                @endforelse
            </div>
        </div>
    </div>
</section>

{{-- ========== GRADES ========== --}}
<section class="lms-panel {{ $activeTab === 'grades' ? 'is-active' : '' }}" data-panel="grades">
    <div class="lms-card p-6 max-w-2xl">
        @if(!$myGrade)
            <p class="text-slate-500 text-sm">Chưa có dữ liệu điểm.</p>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
                <div class="rounded-xl bg-slate-50 p-3 text-center">
                    <div class="text-[11px] text-slate-500">TB bài tập</div>
                    <div class="text-xl font-bold">{{ $myGrade['assignment_avg'] ?? '—' }}</div>
                </div>
                <div class="rounded-xl bg-slate-50 p-3 text-center">
                    <div class="text-[11px] text-slate-500">TB thi</div>
                    <div class="text-xl font-bold">{{ $myGrade['exam_avg'] ?? '—' }}</div>
                </div>
                <div class="rounded-xl bg-slate-50 p-3 text-center">
                    <div class="text-[11px] text-slate-500">Chuyên cần</div>
                    <div class="text-xl font-bold">{{ $myGrade['attendance_pct'] !== null ? $myGrade['attendance_pct'].'%' : '—' }}</div>
                </div>
                <div class="rounded-xl bg-teal-50 p-3 text-center border border-teal-100">
                    <div class="text-[11px] text-teal-700">Tổng hợp</div>
                    <div class="text-xl font-bold text-teal-800">{{ $myGrade['final_score'] ?? $myGrade['computed_score'] ?? '—' }}</div>
                </div>
            </div>
            <div class="text-sm space-y-1.5">
                <div class="font-semibold text-slate-700 mb-1">Chi tiết bài tập</div>
                @foreach($gradeMatrix['assignments'] as $a)
                    @php $c = $myGrade['assignment_cells'][$a->id] ?? null; @endphp
                    <div class="flex justify-between border-b border-slate-100 py-1.5">
                        <span>{{ $a->title }}</span>
                        <span class="font-medium">{{ $c && $c['score'] !== null ? $c['score'].'/'.$a->max_score : ($c ? 'Đã nộp' : '—') }}</span>
                    </div>
                @endforeach
                <div class="font-semibold text-slate-700 mt-3 mb-1">Chi tiết thi</div>
                @foreach($gradeMatrix['exams'] as $e)
                    @php $c = $myGrade['exam_cells'][$e->id] ?? null; @endphp
                    <div class="flex justify-between border-b border-slate-100 py-1.5">
                        <span>{{ $e->title }}</span>
                        <span class="font-medium">{{ $c ? $c['score'].'/'.$c['max'] : '—' }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>

{{-- ========== CERTIFICATES ========== --}}
<section class="lms-panel {{ $activeTab === 'certificates' ? 'is-active' : '' }}" data-panel="certificates">
    <div class="lms-card p-6 max-w-xl">
        @if($certificate && $certificate->isIssued())
            <div class="text-center space-y-3">
                <div class="inline-flex h-14 w-14 items-center justify-center rounded-2xl bg-teal-50 text-teal-700 text-2xl border border-teal-100">
                    <i class="bi bi-award"></i>
                </div>
                <div class="font-bold text-lg text-slate-900">Đã cấp chứng chỉ</div>
                <div class="font-mono text-sm text-slate-600">{{ $certificate->code }}</div>
                <a href="{{ route('lms.learn.certificates.show', [$course, $certificate]) }}" class="lms-btn lms-btn-primary">Xem / In</a>
            </div>
        @else
            <div class="space-y-3 text-sm">
                <div class="font-semibold text-slate-800">Điều kiện nhận chứng chỉ</div>
                <ul class="list-disc pl-5 text-slate-600 space-y-1">
                    <li>Tiến độ ≥ {{ $certEligibility['template']->min_progress_pct ?? 80 }}% (hiện {{ number_format($certEligibility['progress_pct'] ?? 0, 1) }}%)</li>
                    @if(($certEligibility['template']->min_score ?? null) !== null)
                        <li>Điểm ≥ {{ $certEligibility['template']->min_score }} (hiện {{ $certEligibility['final_score'] ?? '—' }})</li>
                    @endif
                    @if($certEligibility['template']->require_survey ?? false)
                        <li>Hoàn thành khảo sát chất lượng</li>
                    @endif
                </ul>
                @if(!empty($certEligibility['reasons']))
                    <div class="rounded-xl bg-amber-50 border border-amber-100 px-3 py-2 text-amber-900 text-xs">
                        @foreach($certEligibility['reasons'] as $r) <div>• {{ $r }}</div> @endforeach
                    </div>
                @endif
                @if($certEligibility['eligible'] ?? false)
                    <form method="POST" action="{{ route('lms.learn.certificates.request', $course) }}">
                        @csrf
                        <button class="lms-btn lms-btn-primary">Nhận chứng chỉ</button>
                    </form>
                @endif
            </div>
        @endif
    </div>
</section>

{{-- ========== SURVEYS ========== --}}
<section class="lms-panel {{ $activeTab === 'surveys' ? 'is-active' : '' }}" data-panel="surveys">
    <div class="space-y-5 max-w-2xl">
        @forelse($surveys as $survey)
            @php $done = $mySurveyResponses[$survey->id] ?? null; @endphp
            <div class="lms-card overflow-hidden">
                <div class="lms-card-head flex flex-wrap justify-between gap-2 items-center">
                    <span>{{ $survey->title }}</span>
                    @if($done)
                        <span class="text-xs font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full">Đã gửi</span>
                    @elseif($survey->isOpen())
                        <span class="text-xs font-semibold text-teal-700 bg-teal-50 px-2 py-0.5 rounded-full">Đang mở</span>
                    @endif
                </div>
                <div class="p-5">
                    @if($survey->description)
                        <p class="text-sm text-slate-500 mb-4">{{ $survey->description }}</p>
                    @endif

                    @if($done)
                        <p class="text-sm text-emerald-700 mb-3">Cảm ơn bạn — đã gửi lúc {{ $done->submitted_at?->format('d/m/Y H:i') }}</p>
                        <div class="space-y-2">
                            @foreach($survey->questions as $q)
                                @php $ans = $done->answers[$q->id] ?? $done->answers[(string)$q->id] ?? null; @endphp
                                <div class="rounded-xl border border-slate-100 px-3 py-2 text-sm">
                                    <div class="text-slate-600">{{ $q->stem }}</div>
                                    <div class="mt-1 font-semibold text-slate-900">
                                        @if($ans === null)
                                            —
                                        @elseif($q->type === 'rating_1_5')
                                            <span class="text-amber-500 text-lg tracking-tight">{{ str_repeat('★', (int)$ans) }}{{ str_repeat('☆', max(0, 5-(int)$ans)) }}</span>
                                            <span class="text-slate-500 text-xs ml-1">{{ $ans }}/5</span>
                                        @else
                                            {{ is_array($ans) ? implode(', ', $ans) : $ans }}
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @elseif($survey->isOpen() && $survey->questions->isNotEmpty())
                        <form method="POST"
                              action="{{ route('lms.learn.surveys.submit', [$course, $survey]) }}"
                              class="space-y-4"
                              id="survey-form-{{ $survey->id }}">
                            @csrf
                            @foreach($survey->questions as $qi => $q)
                                <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                                    <div class="text-sm font-semibold text-slate-800 mb-3">
                                        <span class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-teal-50 text-teal-800 text-xs mr-1">{{ $qi + 1 }}</span>
                                        {{ $q->stem }}
                                        @if($q->is_required)<span class="text-rose-500">*</span>@endif
                                    </div>
                                    @if($q->type === 'rating_1_5')
                                        <div class="lms-rate" data-rate-group>
                                            @for($i = 1; $i <= 5; $i++)
                                                <label class="lms-rate-opt">
                                                    <input type="radio"
                                                           name="answers[{{ $q->id }}]"
                                                           value="{{ $i }}"
                                                           {{ $q->is_required ? 'required' : '' }}>
                                                    <span class="lms-rate-star" aria-hidden="true">★</span>
                                                    <span class="lms-rate-num">{{ $i }}</span>
                                                </label>
                                            @endfor
                                        </div>
                                        <p class="text-[11px] text-slate-400 mt-2">1 = rất kém · 5 = rất tốt</p>
                                    @elseif($q->type === 'mcq' && is_array($q->options))
                                        <div class="space-y-2 text-sm">
                                            @foreach($q->options as $opt)
                                                <label class="flex items-center gap-2 cursor-pointer rounded-lg border border-slate-100 px-3 py-2 hover:bg-teal-50/50">
                                                    <input type="radio" name="answers[{{ $q->id }}]" value="{{ $opt }}" {{ $q->is_required ? 'required' : '' }}>
                                                    <span>{{ $opt }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    @else
                                        <textarea name="answers[{{ $q->id }}]"
                                                  rows="3"
                                                  class="w-full border border-slate-200 rounded-lg text-sm px-3 py-2"
                                                  placeholder="Nhập góp ý của bạn..."
                                                  {{ $q->is_required ? 'required' : '' }}></textarea>
                                    @endif
                                </div>
                            @endforeach
                            <button type="submit" class="lms-btn-solid w-full sm:w-auto">
                                <i class="bi bi-send"></i> Gửi đánh giá
                            </button>
                        </form>
                    @elseif($survey->isOpen())
                        <p class="text-sm text-slate-400">Khảo sát chưa có câu hỏi.</p>
                    @else
                        <p class="text-sm text-slate-400">Khảo sát chưa mở / đã đóng.</p>
                    @endif
                </div>
            </div>
        @empty
            <div class="lms-card p-8 text-center text-slate-500 text-sm">Chưa có khảo sát.</div>
        @endforelse
    </div>
</section>

{{-- ========== FORUM ========== --}}
<section class="lms-panel {{ $activeTab === 'forum' ? 'is-active' : '' }}" data-panel="forum">
    <div class="lms-card p-4 mb-3">
        <form method="POST" action="{{ route('lms.learn.forum.store', $course) }}" class="space-y-2">
            @csrf
            <input name="title" required placeholder="Tiêu đề chủ đề" class="w-full border rounded-lg text-sm px-3 py-2">
            <textarea name="body" required rows="2" placeholder="Nội dung" class="w-full border rounded-lg text-sm px-3 py-2"></textarea>
            <button type="submit" class="lms-btn-solid">
                <i class="bi bi-send"></i> Đăng chủ đề
            </button>
        </form>
    </div>
    <div class="space-y-2">
        @forelse($forumTopics as $topic)
            <div class="lms-card p-4">
                <div class="flex flex-wrap gap-2 justify-between items-start">
                    <a href="{{ route('lms.learn.forum.show', [$course, $topic]) }}" class="min-w-0 flex-1 no-underline text-inherit hover:opacity-90">
                        <div class="font-semibold text-slate-900">
                            @if($topic->is_pinned)<span class="text-amber-600 text-xs mr-1"><i class="bi bi-pin-angle-fill"></i> Ghim</span>@endif
                            @if($topic->is_locked)<span class="text-slate-500 text-xs mr-1"><i class="bi bi-lock-fill"></i> Khóa</span>@endif
                            {{ $topic->title }}
                        </div>
                        <div class="text-xs text-slate-400 mt-1">{{ $topic->author->name ?? '' }} · {{ $topic->created_at?->diffForHumans() }}
                            · {{ $topic->replies_count ?? 0 }} trả lời
                        </div>
                    </a>
                    @if($canTeach || $teachMode)
                        <div class="flex flex-wrap gap-1">
                            <form method="POST" action="{{ route('lms.learn.forum.pin', [$course, $topic]) }}" data-turbo="false">
                                @csrf
                                <button class="lms-btn lms-btn-ghost text-xs" style="padding:0.25rem 0.5rem" type="submit">
                                    {{ $topic->is_pinned ? 'Bỏ ghim' : 'Ghim' }}
                                </button>
                            </form>
                            <form method="POST" action="{{ route('lms.learn.forum.lock', [$course, $topic]) }}" data-turbo="false">
                                @csrf
                                <button class="lms-btn lms-btn-ghost text-xs" style="padding:0.25rem 0.5rem" type="submit">
                                    {{ $topic->is_locked ? 'Mở khóa' : 'Khóa' }}
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>
        @empty
            <div class="lms-card p-6 text-center text-sm text-slate-500">Chưa có chủ đề.</div>
        @endforelse
    </div>
</section>

{{-- ========== CHAT (group + DM qua nút +) ========== --}}
<section class="lms-panel {{ $activeTab === 'chat' ? 'is-active' : '' }}" data-panel="chat">
    <div class="flex flex-wrap justify-between items-center gap-2 mb-2">
        <p class="text-sm text-slate-500">Chat chung khóa · bấm <strong>+</strong> để chọn người nhắn riêng.</p>
        @if($canModerateChat)
            <form method="POST" action="{{ route('lms.learn.chat.toggle-lock', $course) }}" class="inline">
                @csrf
                <button class="lms-btn lms-btn-ghost" style="padding:0.3rem 0.6rem;font-size:0.75rem">
                    {{ $course->chat_locked ? 'Mở chat khóa' : 'Khóa chat khóa' }}
                </button>
            </form>
        @endif
    </div>
    <div class="lms-chat-shell" id="lms-chat-app"
         data-store="{{ route('lms.learn.chat.store', $course) }}"
         data-poll="{{ route('lms.learn.chat.poll', $course) }}"
         data-history="{{ route('lms.learn.chat.history', $course) }}"
         data-destroy-base="{{ url('/lms/hoc/courses/'.$course->id.'/chat') }}"
         data-can-moderate="{{ $canModerateChat ? '1' : '0' }}"
         data-locked="{{ $course->chat_locked && !$canModerateChat ? '1' : '0' }}">
        <aside class="lms-chat-sidebar">
            <div class="lms-chat-sidebar-head">
                <span>Cuộc trò chuyện</span>
                <button type="button" class="lms-chat-add-btn" id="chat-add-btn" title="Thêm người chat">+</button>
            </div>
            <div class="lms-chat-peer-list" id="chat-peer-list">
                <button type="button" class="lms-chat-person is-active" data-peer="" data-name="Chat khóa học">
                    <i class="bi bi-people" style="margin-right:0.35rem"></i>Chat khóa học
                </button>
                {{-- DM threads được thêm bằng JS khi chọn từ + --}}
            </div>
        </aside>
        <div class="flex flex-col min-h-0" style="min-height:0;height:100%">
            <div class="px-3 py-2 border-b border-slate-100 text-sm font-semibold text-slate-800" id="chat-title">Chat khóa học</div>
            <div class="lms-chat-messages" id="chat-box">
                @forelse($chatMessages as $msg)
                    <div class="lms-chat-row {{ (int)$msg->user_id === (int)auth()->id() ? 'is-mine' : 'is-theirs' }}" data-id="{{ $msg->id }}">
                        @if((int)$msg->user_id !== (int)auth()->id())
                            <div class="lms-chat-meta">{{ $msg->author->name ?? 'User' }}</div>
                        @endif
                        <div class="lms-chat-bubble">{{ $msg->body }}</div>
                        <div class="lms-chat-meta flex items-center gap-2">
                            <span>{{ $msg->created_at?->format('H:i d/m') }}</span>
                            @if($canModerateChat)
                                <button type="button" class="lms-chat-del text-[11px] text-rose-600 hover:underline" data-del-msg="{{ $msg->id }}" title="Xóa tin">Xóa</button>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-center text-slate-400 text-sm py-8" id="chat-empty">Chưa có tin nhắn — hãy bắt đầu trò chuyện.</p>
                @endforelse
            </div>
            <div class="lms-chat-composer" id="chat-composer-wrap">
                @if($course->chat_locked && !$canModerateChat)
                    <p class="text-sm text-amber-800 w-full">Chat đang bị khóa bởi giảng viên / quản trị.</p>
                @else
                    <input type="text" id="chat-input" maxlength="2000" placeholder="Nhập tin nhắn..." autocomplete="off">
                    <button type="button" class="lms-btn-solid" id="chat-send" style="padding:0.55rem 1rem">Gửi</button>
                @endif
                <span id="chat-status" class="text-xs text-slate-500" role="status" aria-live="polite"></span>
            </div>
        </div>
    </div>

    {{-- Modal chọn người chat --}}
    <div class="lms-modal-backdrop" id="chat-pick-modal" aria-hidden="true">
        <div class="lms-modal" style="max-width:22rem">
            <div class="text-xs font-semibold uppercase tracking-wide text-teal-700 mb-1">Tin nhắn mới</div>
            <p class="text-sm text-slate-600 mb-2">Chọn người trong khóa để nhắn riêng:</p>
            <input type="search" id="chat-pick-search" class="w-full border rounded-lg text-sm px-3 py-2 mb-2" placeholder="Tìm tên…">
            <div class="lms-chat-pick-list" id="chat-pick-list">
                @foreach($chatMembers ?? [] as $m)
                    <button type="button"
                            class="lms-chat-pick-item"
                            data-peer="{{ $m->user_id }}"
                            data-name="{{ $m->user->name }}"
                            data-role="{{ $m->role }}">
                        <strong>{{ $m->user->name }}</strong>
                        <span class="block text-[11px] text-slate-400">{{ $m->role }}</span>
                    </button>
                @endforeach
                @if(empty($chatMembers) || (is_countable($chatMembers) && count($chatMembers) === 0))
                    <p class="text-sm text-slate-400 text-center py-4">Chưa có thành viên khác trong khóa.</p>
                @endif
            </div>
            <div class="flex justify-end mt-3">
                <button type="button" class="lms-btn lms-btn-ghost" id="chat-pick-close">Đóng</button>
            </div>
        </div>
    </div>
</section>

{{-- Confirm modal --}}
<div class="lms-modal-backdrop" id="lms-confirm-modal" aria-hidden="true">
    <div class="lms-modal">
        <div class="text-xs font-semibold uppercase tracking-wide text-teal-700 mb-1" id="lms-confirm-title">Xác nhận</div>
        <p class="text-sm text-slate-700 mb-4" id="lms-confirm-message">Bạn có chắc không?</p>
        <div class="flex justify-end gap-2">
            <button type="button" class="lms-btn lms-btn-ghost" id="lms-confirm-cancel">Hủy</button>
            <button type="button" class="lms-btn lms-btn-primary" id="lms-confirm-ok">Đồng ý</button>
        </div>
    </div>
</div>

<form id="lms-post-form" method="POST" class="hidden">@csrf</form>
@endsection

@push('scripts')
<script>
(function () {
    function bootCourseRoom() {
    const tabBar = document.getElementById('lms-course-tabs');
    if (!tabBar || tabBar.dataset.bound === '1') return;
    tabBar.dataset.bound = '1';

    const tabs = document.querySelectorAll('#lms-course-tabs .lms-tab');
    const panels = document.querySelectorAll('.lms-panel');
    function activate(name) {
        tabs.forEach(t => t.classList.toggle('is-active', t.dataset.tab === name));
        panels.forEach(p => p.classList.toggle('is-active', p.dataset.panel === name));
        const url = new URL(window.location.href);
        url.searchParams.set('tab', name);
        history.replaceState({}, '', url);
        if (name === 'chat') {
            scrollChatBottom();
            pollChat(true);
        }
        // Tom Select / Flatpickr: re-init khi panel vừa hiện (tránh dropdown native vì init lúc hidden)
        const panel = document.querySelector('.lms-panel[data-panel="' + name + '"]');
        if (panel && typeof window.initLmsWidgets === 'function') {
            window.setTimeout(function () { window.initLmsWidgets(panel); }, 30);
        }
    }
    tabs.forEach(t => t.addEventListener('click', () => activate(t.dataset.tab)));
    // Init widgets cho tab đang active
    const activePanel = document.querySelector('.lms-panel.is-active');
    if (activePanel && typeof window.initLmsWidgets === 'function') {
        window.setTimeout(function () { window.initLmsWidgets(activePanel); }, 50);
    }

    document.querySelectorAll('[data-assign-toggle]').forEach(el => {
        el.addEventListener('click', () => {
            const id = el.getAttribute('data-assign-toggle');
            const box = document.getElementById('assign-form-' + id);
            if (box) box.classList.toggle('hidden');
        });
    });

    const modal = document.getElementById('lms-confirm-modal');
    const titleEl = document.getElementById('lms-confirm-title');
    const msgEl = document.getElementById('lms-confirm-message');
    const okBtn = document.getElementById('lms-confirm-ok');
    const cancelBtn = document.getElementById('lms-confirm-cancel');
    const postForm = document.getElementById('lms-post-form');
    let pending = null;

    function openConfirm(opts) {
        pending = opts;
        titleEl.textContent = opts.title || 'Xác nhận';
        msgEl.textContent = opts.message || '';
        okBtn.textContent = opts.ok || 'Đồng ý';
        modal.classList.add('is-open');
    }
    function closeConfirm() {
        pending = null;
        modal.classList.remove('is-open');
    }
    cancelBtn.addEventListener('click', closeConfirm);
    modal.addEventListener('click', (e) => { if (e.target === modal) closeConfirm(); });
    okBtn.addEventListener('click', () => {
        if (!pending) return;
        if ((pending.method || 'GET').toUpperCase() === 'POST') {
            postForm.action = pending.href;
            postForm.submit();
        } else {
            window.location.href = pending.href;
        }
    });

    document.querySelectorAll('[data-confirm-nav]').forEach(el => {
        el.addEventListener('click', (e) => {
            e.preventDefault();
            if (!el.dataset.href) return;
            openConfirm({
                title: el.dataset.title,
                message: el.dataset.message,
                href: el.dataset.href,
                method: el.dataset.method || 'GET',
                ok: el.dataset.ok || 'Đồng ý',
            });
        });
    });

    document.querySelectorAll('[data-cal-nav]').forEach(btn => {
        btn.addEventListener('click', () => {
            const m = btn.dataset.month;
            const url = new URL(window.location.href);
            url.searchParams.set('tab', 'attendance');
            url.searchParams.set('m', m);
            window.location.href = url.toString();
        });
    });

    // —— Attendance: bấm nút điểm danh (không GPS) ——
    const attDetail = document.getElementById('att-detail');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    @php
        $checkinBaseUrl = url('/lms/hoc/courses/'.$course->id.'/attendance');
    @endphp
    const checkinBase = @json($checkinBaseUrl);
    const campusProbeUrls = @json(\Modules\Lms\Support\CampusNetwork::activeProbeUrls());
    const campusMeta = @json(\Modules\Lms\Support\LmsCampus::meta());

    async function tryCampusProbe(urls, timeoutMs = 2500) {
        if (!urls || !urls.length) return { ok: true, note: 'no_probe' };
        for (const url of urls) {
            const ctrl = new AbortController();
            const t = setTimeout(() => ctrl.abort(), timeoutMs);
            try {
                await fetch(url, { mode: 'no-cors', cache: 'no-store', signal: ctrl.signal, credentials: 'omit' });
                clearTimeout(t);
                return { ok: true, note: 'probe_ok', url };
            } catch (e) {
                clearTimeout(t);
            }
        }
        return { ok: false, note: 'probe_failed' };
    }

    function getDeviceGps(timeoutMs = 12000) {
        return new Promise((resolve) => {
            if (!navigator.geolocation) {
                resolve({ ok: false, error: 'no_geolocation' });
                return;
            }
            navigator.geolocation.getCurrentPosition(
                (pos) => resolve({
                    ok: true,
                    lat: pos.coords.latitude,
                    lng: pos.coords.longitude,
                    accuracy: Math.round(pos.coords.accuracy || 0),
                }),
                (err) => resolve({ ok: false, error: err && err.message ? err.message : 'gps_denied' }),
                { enableHighAccuracy: true, timeout: timeoutMs, maximumAge: 15000 }
            );
        });
    }

    async function postCheckin(sessionId, payload) {
        const res = await fetch(checkinBase + '/' + sessionId + '/checkin', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        });
        const data = await res.json().catch(() => ({}));
        return { res, data };
    }

    document.querySelectorAll('[data-att-day]').forEach(day => {
        day.addEventListener('click', () => {
            if (!attDetail) return;
            document.querySelectorAll('.lms-cal-day.is-selected').forEach(d => d.classList.remove('is-selected'));
            day.classList.add('is-selected');
            const open = day.dataset.open === '1';
            const canCheckinFlag = day.dataset.canCheckin === '1';
            const st = (day.dataset.status || '').trim();
            const mode = day.dataset.mode || '';
            const notDone = !st || st === 'chưa điểm' || st === 'null' || st === 'undefined';
            const statusLabel = notDone ? 'Chưa điểm danh' : (({ present: 'Có mặt', absent: 'Vắng', late: 'Muộn', excused: 'Có phép' })[st] || st);
            const canCheck = notDone && (canCheckinFlag || (open && ['self', 'qr', 'gps'].includes(mode)));
            attDetail.classList.remove('hidden');
            let html = '<div class="font-semibold text-slate-800 mb-1">' + day.dataset.date + ' · ' + (day.dataset.title || '') + '</div>' +
                '<div class="text-sm text-slate-600 mb-2">Trạng thái: <strong>' + statusLabel + '</strong>' +
                (mode ? ' · mode ' + mode : '') + '</div>';
            // Sprint 9 H2 — chi tiết IP/mạng (chỉ của chính HV)
            if (!notDone) {
                html += '<div class="text-xs text-slate-600 bg-slate-50 border border-slate-100 rounded-lg px-3 py-2 mb-3 space-y-0.5">' +
                    '<div><span class="text-slate-400">Giờ check-in:</span> ' + (day.dataset.checkedAt || '—') + '</div>' +
                    '<div><span class="text-slate-400">Phương thức:</span> ' + (day.dataset.method || '—') + '</div>' +
                    '<div><span class="text-slate-400">IP:</span> <code>' + (day.dataset.ip || '—') + '</code></div>' +
                    '<div><span class="text-slate-400">Mạng trường:</span> ' +
                    (day.dataset.netOk === '1' ? '<span class="text-emerald-700 font-semibold">OK</span>' :
                        (day.dataset.netOk === '0' ? '<span class="text-rose-600 font-semibold">FAIL</span>' : '—')) +
                    (day.dataset.netNote ? ' · ' + day.dataset.netNote : '') +
                    '</div></div>';
            }
            if (canCheck) {
                const needWifi = mode === 'qr';
                html += (needWifi
                    ? '<p class="text-xs text-amber-800 bg-amber-50 border border-amber-100 rounded-lg px-2 py-1.5 mb-2"><i class="bi bi-wifi"></i> Mode QR: cần Wi‑Fi trường. Quét mã GV chiếu hoặc bấm nút khi đang online mạng trường.</p>'
                    : '') +
                    '<button type="button" class="lms-btn-solid" id="att-check-btn" data-session="' + day.dataset.session + '" data-token="' + (day.dataset.token || '') + '" data-mode="' + mode + '">' +
                    '<i class="bi bi-check2-circle"></i> Điểm danh</button>' +
                    '<span id="att-check-status" class="text-xs text-slate-500 ml-2"></span>';
            } else if (!notDone) {
                html += '<p class="text-xs text-emerald-700">Bạn đã điểm danh ngày này (' + statusLabel + ').</p>';
            } else {
                html += '<p class="text-xs text-slate-400">Ngày này không mở tự điểm danh. Chọn ngày chấm xanh còn mở hoặc nhờ GV điểm miệng.</p>';
            }
            attDetail.innerHTML = html;
            const btn = document.getElementById('att-check-btn');
            if (btn) {
                btn.addEventListener('click', async () => {
                    const statusEl = document.getElementById('att-check-status');
                    btn.disabled = true;
                    if (statusEl) statusEl.textContent = 'Đang kiểm tra…';
                    try {
                        const payload = {};
                        if (btn.dataset.token) payload.token = btn.dataset.token;
                        const mode = btn.dataset.mode || '';

                        // P2: GPS trước (mode gps hoặc soft attach)
                        if (mode === 'gps' || mode === 'qr') {
                            if (statusEl) statusEl.textContent = 'Lấy GPS…';
                            const g = await getDeviceGps();
                            if (g.ok) {
                                payload.lat = g.lat;
                                payload.lng = g.lng;
                                payload.accuracy = g.accuracy;
                            } else if (mode === 'gps') {
                                btn.disabled = false;
                                if (statusEl) statusEl.textContent = '';
                                const msg = 'Cần bật định vị GPS (bán kính ~' + (campusMeta.radius_m || 450) + 'm). ' + (g.error || '');
                                window.LmsPopup.error(msg);
                                return;
                            }
                        }

                        if (statusEl) statusEl.textContent = 'Đang gửi…';
                        let { data } = await postCheckin(btn.dataset.session, payload);

                        // P1 probe retry
                        if (data.need_probe && Array.isArray(data.probe_urls)) {
                            if (statusEl) statusEl.textContent = 'Probe LAN…';
                            const pr = await tryCampusProbe(data.probe_urls);
                            if (!pr.ok) {
                                btn.disabled = false;
                                if (statusEl) statusEl.textContent = '';
                                const msg = 'Không reach probe LAN. Kết nối Wi‑Fi trường hoặc nhờ GV điểm miệng.';
                                window.LmsPopup.error(msg);
                                return;
                            }
                            payload.probe_ok = true;
                            if (statusEl) statusEl.textContent = 'Đang gửi…';
                            ({ data } = await postCheckin(btn.dataset.session, payload));
                        }

                        // P2 GPS retry if server asks
                        if (data.need_gps && !payload.lat) {
                            if (statusEl) statusEl.textContent = 'Lấy GPS…';
                            const g2 = await getDeviceGps();
                            if (!g2.ok) {
                                btn.disabled = false;
                                if (statusEl) statusEl.textContent = '';
                                const msg = data.message || ('Cần GPS trong bán kính ' + (campusMeta.radius_m || 450) + 'm');
                                window.LmsPopup.error(msg);
                                return;
                            }
                            payload.lat = g2.lat;
                            payload.lng = g2.lng;
                            payload.accuracy = g2.accuracy;
                            ({ data } = await postCheckin(btn.dataset.session, payload));
                        }

                        if (data.ok) {
                            day.classList.add('is-present');
                            day.dataset.status = 'present';
                            day.dataset.canCheckin = '0';
                            attDetail.innerHTML = '<div class="font-semibold text-emerald-800"><i class="bi bi-check-circle"></i> ' +
                                (data.message || 'Đã điểm danh thành công') + '</div>';
                            if (window.Notify) window.Notify.success(data.message || 'Đã điểm danh thành công');
                        } else {
                            btn.disabled = false;
                            if (statusEl) statusEl.textContent = '';
                            window.LmsPopup.error(data.message || 'Không điểm danh được');
                        }
                    } catch (err) {
                        btn.disabled = false;
                        if (statusEl) statusEl.textContent = '';
                        window.LmsPopup.error('Lỗi mạng khi điểm danh');
                    }
                });
            }
        });
    });

    // —— Chat realtime + DM (+ chọn người) ——
    const chatApp = document.getElementById('lms-chat-app');
    const chatBox = document.getElementById('chat-box');
    const chatInput = document.getElementById('chat-input');
    const chatSend = document.getElementById('chat-send');
    const chatTitle = document.getElementById('chat-title');
    const chatPeerList = document.getElementById('chat-peer-list');
    const chatPickModal = document.getElementById('chat-pick-modal');
    const chatAddBtn = document.getElementById('chat-add-btn');
    const chatPickClose = document.getElementById('chat-pick-close');
    const chatPickSearch = document.getElementById('chat-pick-search');
    const chatPickList = document.getElementById('chat-pick-list');
    const chatStatus = document.getElementById('chat-status');
    let peerId = '';
    let lastId = 0;
    let pollTimer = null;
    let pollBlockedUntil = 0;
    let historyRequest = 0;

    function showChatStatus(message, tone = 'muted') {
        if (!chatStatus) return;
        chatStatus.textContent = message || '';
        chatStatus.className = 'text-xs ' + (tone === 'error'
            ? 'text-rose-600'
            : (tone === 'ok' ? 'text-emerald-700' : 'text-slate-500'));
    }

    function showChatError(message) {
        showChatStatus(message, 'error');
        if (window.LmsPopup && typeof window.LmsPopup.error === 'function') {
            window.LmsPopup.error(message);
        }
    }

    function applyChatState(data) {
        if (!chatApp || !data) return;
        chatApp.dataset.locked = data.chat_locked && !data.can_moderate ? '1' : '0';
        const canSend = data.can_send !== false;
        if (chatInput) chatInput.disabled = !canSend;
        if (chatSend) chatSend.disabled = !canSend;
        if (!canSend) showChatStatus('Chat đang bị khóa bởi giảng viên / quản trị.', 'error');
    }

    async function chatJson(url, options = {}) {
        const res = await fetch(url, {
            credentials: 'same-origin',
            cache: 'no-store',
            ...options,
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                ...(options.headers || {}),
            },
        });
        const data = await res.json().catch(() => ({}));
        if (!res.ok) {
            const error = new Error(data.message || (res.status === 419
                ? 'Phiên đăng nhập đã hết hạn. Hãy tải lại trang.'
                : (res.status === 429 || res.status === 468
                    ? 'Chat đang tạm giới hạn kết nối. Hệ thống sẽ tự thử lại.'
                    : 'Không kết nối được dịch vụ chat.')));
            error.status = res.status;
            throw error;
        }

        return data;
    }

    function scrollChatBottom() {
        if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
    }

    const canModChat = chatApp && chatApp.dataset.canModerate === '1';
    const destroyBase = chatApp ? (chatApp.dataset.destroyBase || '') : '';

    function appendMsg(m) {
        if (!chatBox || !m || !m.id) return;
        if (chatBox.querySelector('[data-id="' + m.id + '"]')) return;
        const empty = document.getElementById('chat-empty');
        if (empty) empty.remove();
        const row = document.createElement('div');
        row.className = 'lms-chat-row ' + (m.mine ? 'is-mine' : 'is-theirs');
        row.dataset.id = m.id;
        if (!m.mine) {
            const author = document.createElement('div');
            author.className = 'lms-chat-meta';
            author.textContent = m.user || '';
            row.appendChild(author);
        }
        const bubble = document.createElement('div');
        bubble.className = 'lms-chat-bubble';
        bubble.textContent = m.body || '';
        row.appendChild(bubble);
        const meta = document.createElement('div');
        meta.className = 'lms-chat-meta flex items-center gap-2';
        const at = document.createElement('span');
        at.textContent = m.at || '';
        meta.appendChild(at);
        if (canModChat || m.can_delete) {
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'lms-chat-del text-[11px] text-rose-600 hover:underline';
            remove.dataset.delMsg = String(m.id);
            remove.textContent = 'Xóa';
            meta.appendChild(remove);
        }
        row.appendChild(meta);
        chatBox.appendChild(row);
        lastId = Math.max(lastId, Number(m.id) || 0);
        scrollChatBottom();
    }

    async function deleteChatMsg(id) {
        if (!destroyBase || !id) return;
        if (!window.LmsPopup || typeof window.LmsPopup.confirm !== 'function') return;
        const ok = await window.LmsPopup.confirm('Xóa tin nhắn này khỏi cuộc trò chuyện?', {
            danger: true,
            title: 'Xóa tin nhắn',
            confirmText: 'Xóa',
        });
        if (!ok) return;
        try {
            const data = await chatJson(destroyBase + '/' + id, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': csrf,
                },
            });
            if (data.ok) {
                const el = chatBox && chatBox.querySelector('[data-id="' + id + '"]');
                if (el) el.remove();
                window.LmsPopup.success('Đã xóa tin nhắn.');
            } else {
                showChatError(data.message || 'Không xóa được tin.');
            }
        } catch (e) {
            showChatError(e.message || 'Lỗi mạng khi xóa tin.');
        }
    }
    chatApp?.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-del-msg]');
        if (!btn) return;
        e.preventDefault();
        deleteChatMsg(btn.getAttribute('data-del-msg'));
    });

    document.querySelectorAll('[data-tab-jump]').forEach(btn => {
        btn.addEventListener('click', () => {
            const name = btn.getAttribute('data-tab-jump');
            if (name) activate(name);
        });
    });

    function seedLastId() {
        if (!chatBox) return;
        chatBox.querySelectorAll('[data-id]').forEach(el => {
            lastId = Math.max(lastId, Number(el.dataset.id) || 0);
        });
    }
    seedLastId();

    function bindPeerButtons() {
        if (!chatPeerList) return;
        chatPeerList.querySelectorAll('.lms-chat-person').forEach(btn => {
            btn.onclick = () => {
                chatPeerList.querySelectorAll('.lms-chat-person').forEach(b => b.classList.remove('is-active'));
                btn.classList.add('is-active');
                peerId = btn.dataset.peer || '';
                if (chatTitle) chatTitle.textContent = btn.dataset.name || 'Chat';
                loadHistory();
            };
        });
    }
    bindPeerButtons();

    function ensurePeerThread(id, name) {
        if (!chatPeerList || !id) return;
        let btn = chatPeerList.querySelector('.lms-chat-person[data-peer="' + id + '"]');
        if (!btn) {
            btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'lms-chat-person';
            btn.dataset.peer = String(id);
            btn.dataset.name = name || ('User #' + id);
            const icon = document.createElement('i');
            icon.className = 'bi bi-person';
            icon.style.marginRight = '0.35rem';
            btn.append(icon, document.createTextNode(name || ('User #' + id)));
            chatPeerList.appendChild(btn);
            bindPeerButtons();
        }
        btn.click();
    }

    async function loadHistory() {
        if (!chatApp || !chatBox) return;
        const requestId = ++historyRequest;
        showChatStatus('Đang tải cuộc trò chuyện…');
        try {
            const url = new URL(chatApp.dataset.history, window.location.origin);
            if (peerId) url.searchParams.set('recipient_user_id', peerId);
            const data = await chatJson(url.toString());
            if (requestId !== historyRequest) return;
            chatBox.innerHTML = '';
            lastId = 0;
            (data.messages || []).forEach(appendMsg);
            if (!(data.messages || []).length) {
                chatBox.innerHTML = '<p class="text-center text-slate-400 text-sm py-8" id="chat-empty">Chưa có tin nhắn.</p>';
            }
            applyChatState(data);
            showChatStatus('Đã đồng bộ', 'ok');
            scrollChatBottom();
        } catch (e) {
            if (requestId === historyRequest) showChatError(e.message || 'Không tải được cuộc trò chuyện.');
        }
    }

    async function pollChat(force = false) {
        if (!chatApp || document.hidden || Date.now() < pollBlockedUntil) return;
        const chatPanel = chatApp.closest('[data-panel="chat"]');
        if (!force && chatPanel && !chatPanel.classList.contains('is-active')) return;
        try {
            const url = new URL(chatApp.dataset.poll, window.location.origin);
            url.searchParams.set('after_id', String(lastId));
            if (peerId) url.searchParams.set('recipient_user_id', peerId);
            const data = await chatJson(url.toString());
            applyChatState(data);
            (data.messages || []).forEach(appendMsg);
        } catch (e) {
            if (e.status === 429 || e.status === 468) pollBlockedUntil = Date.now() + 30000;
            showChatStatus(e.message || 'Mất kết nối chat, đang thử lại…', 'error');
        }
    }

    async function sendChat() {
        if (!chatApp || !chatInput || !chatInput.value.trim()) return;
        if (chatApp.dataset.locked === '1') return;
        const body = chatInput.value.trim();
        chatInput.value = '';
        const payload = { body };
        if (peerId) payload.recipient_user_id = Number(peerId);
        chatSend.disabled = true;
        showChatStatus('Đang gửi…');
        try {
            const data = await chatJson(chatApp.dataset.store, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify(payload),
            });
            if (data.ok && data.message) {
                appendMsg(data.message);
                showChatStatus('Đã gửi', 'ok');
            } else {
                showChatError(data.message || 'Không gửi được tin nhắn');
                chatInput.value = body;
            }
        } catch (e) {
            showChatError(e.message || 'Lỗi mạng khi gửi tin nhắn');
            chatInput.value = body;
        } finally {
            chatSend.disabled = chatApp.dataset.locked === '1';
            if (!chatSend.disabled) chatInput.focus();
        }
    }

    function openPickModal() {
        if (!chatPickModal) return;
        chatPickModal.classList.add('is-open');
        chatPickModal.setAttribute('aria-hidden', 'false');
        if (chatPickSearch) {
            chatPickSearch.value = '';
            filterPickList('');
            chatPickSearch.focus();
        }
    }
    function closePickModal() {
        if (!chatPickModal) return;
        chatPickModal.classList.remove('is-open');
        chatPickModal.setAttribute('aria-hidden', 'true');
    }
    function filterPickList(q) {
        const term = (q || '').toLowerCase().trim();
        chatPickList?.querySelectorAll('.lms-chat-pick-item').forEach(item => {
            const name = (item.dataset.name || '').toLowerCase();
            item.style.display = !term || name.includes(term) ? '' : 'none';
        });
    }

    chatAddBtn?.addEventListener('click', openPickModal);
    chatPickClose?.addEventListener('click', closePickModal);
    chatPickModal?.addEventListener('click', (e) => { if (e.target === chatPickModal) closePickModal(); });
    chatPickSearch?.addEventListener('input', () => filterPickList(chatPickSearch.value));
    chatPickList?.querySelectorAll('.lms-chat-pick-item').forEach(item => {
        item.addEventListener('click', () => {
            ensurePeerThread(item.dataset.peer, item.dataset.name);
            closePickModal();
        });
    });

    chatSend?.addEventListener('click', sendChat);
    chatInput?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendChat();
        }
    });
    if (window.__lmsChatPoll) {
        clearInterval(window.__lmsChatPoll);
    }
    pollTimer = setInterval(pollChat, 5000);
    window.__lmsChatPoll = pollTimer;

    // —— Điểm danh miệng: chip Có mặt / Vắng / Muộn / Phép ——
    function setAttChip(switchEl, value) {
        if (!switchEl) return;
        const input = switchEl.parentElement && switchEl.parentElement.querySelector('.att-status-input');
        const row = switchEl.closest('tr.att-row');
        if (input) input.value = value;
        if (row) row.dataset.status = value;
        switchEl.querySelectorAll('.att-chip').forEach(function (btn) {
            const v = btn.getAttribute('data-att-val');
            btn.classList.remove('is-on', 'is-present', 'is-absent', 'is-late', 'is-excused');
            if (v === value) {
                btn.classList.add('is-on', 'is-' + v);
            }
        });
    }
    document.querySelectorAll('.att-status-switch').forEach(function (sw) {
        sw.querySelectorAll('.att-chip').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                setAttChip(sw, btn.getAttribute('data-att-val'));
            });
        });
    });
    function setAllAttStatus(value) {
        document.querySelectorAll('.att-status-switch').forEach(function (sw) {
            setAttChip(sw, value);
        });
        if (window.Notify) {
            window.Notify.info(value === 'present'
                ? 'Đã chọn Có mặt cả lớp — bấm Lưu điểm danh để ghi.'
                : 'Đã chọn Vắng cả lớp — bấm Lưu điểm danh để ghi.');
        }
    }
    document.getElementById('att-set-all-present')?.addEventListener('click', function () {
        setAllAttStatus('present');
    });
    document.getElementById('att-set-all-absent')?.addEventListener('click', function () {
        setAllAttStatus('absent');
    });

    // —— QR popup + copy link ——
    const qrModal = document.getElementById('lms-qr-modal');
    const qrBox = document.getElementById('lms-qr-box');
    const qrTitle = document.getElementById('lms-qr-title');
    const qrLink = document.getElementById('lms-qr-link');
    const qrClose = document.getElementById('lms-qr-close');
    function closeQr() {
        if (!qrModal) return;
        qrModal.classList.remove('is-open');
        qrModal.setAttribute('aria-hidden', 'true');
        document.documentElement.classList.remove('lms-scroll-lock');
        document.body.classList.remove('lms-scroll-lock');
        document.body.style.overflow = '';
        document.documentElement.style.overflow = '';
        if (typeof window.unlockLmsScroll === 'function') {
            try { window.unlockLmsScroll(); } catch (e) {}
        }
        if (qrBox) qrBox.innerHTML = '';
    }
    function openQr(url, title) {
        if (!qrModal || !qrBox) return;
        // Đưa ra body để không bị stacking context (card/tab/overflow) đè
        if (qrModal.parentElement !== document.body) {
            document.body.appendChild(qrModal);
        }
        if (qrTitle) qrTitle.textContent = title || 'Mã QR điểm danh';
        if (qrLink) {
            qrLink.href = url;
            qrLink.textContent = url;
        }
        qrBox.innerHTML = '';
        if (window.QRCode) {
            try {
                new QRCode(qrBox, {
                    text: String(url || ''),
                    width: 200,
                    height: 200,
                    colorDark: '#0f172a',
                    colorLight: '#ffffff',
                    correctLevel: QRCode.CorrectLevel.M,
                });
            } catch (err) {
                qrBox.innerHTML = '<p class="text-sm text-rose-600">Không tạo được QR. Dùng link bên dưới.</p>';
            }
        } else {
            qrBox.innerHTML = '<p class="text-sm text-slate-500">Thiếu thư viện QR. Dùng link bên dưới.</p>';
        }
        qrModal.classList.add('is-open');
        qrModal.setAttribute('aria-hidden', 'false');
        document.documentElement.classList.add('lms-scroll-lock');
        document.body.classList.add('lms-scroll-lock');
    }
    document.querySelectorAll('[data-qr-popup]').forEach(btn => {
        if (btn.dataset.qrBound === '1') return;
        btn.dataset.qrBound = '1';
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            openQr(btn.dataset.qrUrl || '', btn.dataset.qrTitle || '');
        });
    });
    if (qrClose && qrClose.dataset.bound !== '1') {
        qrClose.dataset.bound = '1';
        qrClose.addEventListener('click', closeQr);
    }
    if (qrModal && qrModal.dataset.bound !== '1') {
        qrModal.dataset.bound = '1';
        qrModal.addEventListener('click', (e) => { if (e.target === qrModal) closeQr(); });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && qrModal.classList.contains('is-open')) closeQr();
        });
    }
    document.querySelectorAll('[data-copy-text]').forEach(btn => {
        if (btn.dataset.copyBound === '1') return;
        btn.dataset.copyBound = '1';
        btn.addEventListener('click', async () => {
            const t = btn.getAttribute('data-copy-text') || '';
            try {
                await navigator.clipboard.writeText(t);
                const old = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check2"></i> Đã copy';
                setTimeout(() => { btn.innerHTML = old; }, 1500);
            } catch (e) {
                const fallback = document.createElement('textarea');
                fallback.value = t;
                fallback.setAttribute('readonly', '');
                fallback.style.position = 'fixed';
                fallback.style.opacity = '0';
                document.body.appendChild(fallback);
                fallback.select();
                fallback.setSelectionRange(0, fallback.value.length);

                let copied = false;
                try {
                    copied = document.execCommand('copy');
                } catch (copyError) {
                    copied = false;
                }
                fallback.remove();

                if (copied) {
                    const old = btn.innerHTML;
                    btn.innerHTML = '<i class="bi bi-check2"></i> Đã copy';
                    setTimeout(() => { btn.innerHTML = old; }, 1500);
                    window.LmsPopup.success('Đã sao chép liên kết.');
                } else {
                    window.LmsPopup.error('Không thể sao chép liên kết trên trình duyệt này.');
                }
            }
        });
    });

    // Re-init Tom Select cho select trạng thái vừa render (tab attend)
    if (typeof window.initLmsWidgets === 'function') {
        window.initLmsWidgets(document.querySelector('[data-panel="attend"]') || document);
    }
    } // end bootCourseRoom

    document.addEventListener('turbo:load', bootCourseRoom);
    document.addEventListener('DOMContentLoaded', bootCourseRoom);
    if (document.readyState !== 'loading') {
        bootCourseRoom();
    }
    document.addEventListener('turbo:before-cache', function () {
        if (window.__lmsChatPoll) {
            clearInterval(window.__lmsChatPoll);
            window.__lmsChatPoll = null;
        }
        const tb = document.getElementById('lms-course-tabs');
        if (tb) tb.dataset.bound = '';
    });
})();
</script>
@endpush
