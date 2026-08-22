@extends('layouts.admin')

@section('title', 'Chi tiết môn học')
@section('page-title', 'Chi tiết môn học')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Môn học', 'url' => route('subjects.index')],
    ['title' => $subject->name]
]" />

{{-- Page Header --}}
<x-page-header
    title="CHI TIẾT MÔN HỌC"
    :actions="[
        [
            'url' => route('subjects.index'),
            'label' => 'Quay lại',
            'icon' => 'arrow-left',
            'color' => 'gray'
        ],
        [
            'url' => route('subjects.edit', $subject),
            'label' => 'Chỉnh sửa',
            'icon' => 'pencil',
            'color' => 'blue'
        ]
    ]" />

{{-- Content --}}
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Main Info --}}
    <div class="lg:col-span-2">
        <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
            <div class="flex items-start justify-between mb-6">
                <div>
                    <h2 class="text-2xl font-bold text-gray-900">{{ $subject->name }}</h2>
                    <div class="mt-2 flex items-center space-x-4">
                        <code class="bg-blue-100 text-blue-800 px-3 py-1 rounded text-sm font-mono" title="Full: {{ $subject->code }}">{{ $subject->display_code }}</code>
                        @if($subject->abbreviation)
                            <span class="bg-amber-100 text-amber-900 px-3 py-1 rounded text-sm font-mono font-semibold" title="Viết tắt (xuất lịch)">
                                {{ $subject->abbreviation }}
                            </span>
                        @endif
                        <x-custom-badge :color="$subject->subject_type_color" :text="$subject->subject_type_text" />
                        <x-status-badge :is-active="$subject->is_active" />
                    </div>
                </div>
                <div class="text-right">
                    <div class="text-3xl font-bold text-green-600">{{ $subject->credits }} TC</div>
                    <div class="text-sm text-gray-600">Tín chỉ</div>
                </div>
            </div>

            @if($subject->description)
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Mô tả</h3>
                <p class="text-gray-700">{{ $subject->description }}</p>
            </div>
            @endif

            {{-- Learning Hours --}}
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Thông tin học tập</h3>
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
                    <div class="text-center bg-blue-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600">{{ $subject->theory_hours }}</div>
                        <div class="text-sm text-gray-600">Lý thuyết (tiết)</div>
                    </div>
                    <div class="text-center bg-green-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-green-600">{{ $subject->practice_hours }}</div>
                        <div class="text-sm text-gray-600">Thực hành (tiết)</div>
                    </div>
                    <div class="text-center bg-purple-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-purple-600">{{ $subject->self_study_hours }}</div>
                        <div class="text-sm text-gray-600">Tự học (tiết)</div>
                    </div>
                    <div class="text-center bg-red-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-red-600">{{ $subject->exam_hours }}</div>
                        <div class="text-sm text-gray-600">Thi/kiểm tra (tiết)</div>
                    </div>
                    <div class="text-center bg-orange-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-orange-600">{{ $subject->total_hours }}</div>
                        <div class="text-sm text-gray-600">Tổng (tiết)</div>
                    </div>
                </div>
            </div>

            {{-- Prerequisites --}}
            @if($subject->prerequisites && count($subject->prerequisites) > 0)
            <div class="mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">Môn học tiên quyết</h3>
                <div class="space-y-2">
                    @foreach($subject->prerequisites as $prerequisite)
                        <span class="inline-block bg-gray-100 text-gray-800 px-3 py-1 rounded-full text-sm">
                            {{ $prerequisite }}
                        </span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- Chi tiết môn học (bài học) --}}
        <div class="bg-white rounded-lg shadow-sm border p-6 mb-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-10 h-10 bg-indigo-100 rounded-lg mr-3">
                        <i class="bi bi-journal-text text-indigo-600 text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-gray-900">Chi tiết môn học</h2>
                        <p class="text-xs text-gray-500">Danh sách mã bài học (dùng lọc theo ngành/môn ở các chức năng sau)</p>
                    </div>
                </div>
                <a href="{{ route('subjects.lessons.import.template') }}"
                   class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 text-sm">
                    <i class="bi bi-download mr-1"></i> Tải mẫu
                </a>
            </div>

            @can('subjects.edit')
            <form action="{{ route('subjects.lessons.import') }}" method="POST" enctype="multipart/form-data"
                  data-lesson-import-form data-import-stay="true"
                  class="mb-5 p-4 bg-indigo-50 border border-indigo-100 rounded-xl space-y-3">
                @csrf
                <input type="hidden" name="specialization_id" value="{{ $subject->specialization_id }}">
                <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Ngành đào tạo</label>
                        <input type="text" class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-white text-gray-700" readonly
                               value="{{ $subject->specialization->name ?? '—' }}">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Môn học</label>
                        <input type="text" class="w-full px-3 py-2 border border-gray-200 rounded-lg bg-white text-gray-700" readonly
                               value="{{ $subject->name }} ({{ $subject->code }})">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">File Excel bài học <span class="text-red-500">*</span></label>
                    <input type="file" name="file" accept=".xlsx,.xls" required
                           class="block w-full text-sm text-gray-700 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-indigo-600 file:text-white hover:file:bg-indigo-700">
                    <p class="text-xs text-gray-500 mt-1">Cột bắt buộc: <strong>Mã bài học</strong>. Có thể thêm Tên / Thứ tự.</p>
                </div>
                @include('subject::partials.lesson-import-feedback')
                <button type="submit" data-import-submit data-idle-label="Import bài học"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 disabled:pointer-events-none text-sm font-medium transition">
                    <i data-import-submit-icon class="bi bi-upload"></i>
                    <span data-import-submit-label>Import bài học</span>
                </button>
            </form>
            @endcan

            <div data-subject-lessons-container>
                @if($subject->lessons && $subject->lessons->count() > 0)
                    <div class="flex flex-wrap gap-2">
                        @foreach($subject->lessons as $lesson)
                            <span class="inline-flex items-center px-3 py-1.5 rounded-lg bg-slate-100 border border-slate-200 font-mono text-sm text-slate-800"
                                  title="{{ $lesson->name ?: $lesson->code }}">
                                {{ $lesson->code }}
                            </span>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-400 mt-3">{{ $subject->lessons->count() }} mã bài học</p>
                @else
                    <div class="text-center py-6 text-gray-500 text-sm">
                        Chưa có bài học. Tải mẫu và import để tạo danh sách mã bài học.
                    </div>
                @endif
            </div>
        </div>

        {{-- Instructors / Teaching Assignments --}}
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center">
                    <div class="flex items-center justify-center w-10 h-10 bg-blue-100 rounded-lg mr-3">
                        <i class="bi bi-person-video3 text-blue-600 text-xl"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900">Giảng viên phụ trách</h2>
                </div>

                {{-- NEW: Add assign button --}}
                @can('teaching-assignments.create')
                    <a href="{{ route('subjects.assign-instructors', $subject) }}"
                       class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="bi bi-plus-circle mr-2"></i>
                        Phân công giảng viên
                    </a>
                @endcan
            </div>

            @if($subject->instructors && $subject->instructors->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @foreach($subject->instructors as $instructor)
                        <div class="group border border-gray-200 rounded-lg p-4 hover:border-blue-400 hover:shadow-md transition-all duration-200">
                            <div class="flex items-start space-x-3">
                                {{-- Avatar placeholder --}}
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-lg">
                                        {{ strtoupper(mb_substr($instructor->name, 0, 1)) }}
                                    </div>
                                </div>

                                {{-- Instructor info --}}
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center space-x-2 mb-1">
                                        <a href="{{ route('teaching-assignments.show', $instructor) }}"
                                           class="font-semibold text-gray-900 hover:text-blue-600 transition-colors group-hover:text-blue-600">
                                            {{ $instructor->name }}
                                        </a>
                                        @if($instructor->status === 'active')
                                            <span class="flex-shrink-0 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                                <i class="bi bi-check-circle-fill mr-1"></i>
                                                Hoạt động
                                            </span>
                                        @endif
                                    </div>

                                    @if($instructor->code)
                                        <div class="text-xs text-gray-500 mb-1">
                                            <i class="bi bi-person-badge"></i>
                                            <code class="bg-gray-100 px-1.5 py-0.5 rounded">{{ $instructor->code }}</code>
                                        </div>
                                    @endif

                                    @if($instructor->email)
                                        <div class="text-xs text-gray-600 mb-1 truncate">
                                            <i class="bi bi-envelope"></i>
                                            <a href="mailto:{{ $instructor->email }}" class="hover:text-blue-600">{{ $instructor->email }}</a>
                                        </div>
                                    @endif

                                    @if($instructor->phone)
                                        <div class="text-xs text-gray-600 mb-1">
                                            <i class="bi bi-telephone"></i>
                                            <a href="tel:{{ $instructor->phone }}" class="hover:text-blue-600">{{ $instructor->phone }}</a>
                                        </div>
                                    @endif

                                    @if($instructor->unit)
                                        <div class="text-xs text-gray-500 mt-2 pt-2 border-t border-gray-100">
                                            <i class="bi bi-building"></i>
                                            {{ $instructor->unit->name }}
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                        <i class="bi bi-person-x text-gray-400 text-2xl"></i>
                    </div>
                    <p class="text-gray-500 text-sm">Chưa phân công giảng viên cho môn học này</p>
                </div>
            @endif
        </div>
    </div>

    

    {{-- Sidebar --}}
    <div class="space-y-6">
        {{-- Basic Info --}}
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <h3 class="text-lg font-medium text-gray-900 mb-4">Thông tin cơ bản</h3>
            <dl class="space-y-3">
                <div>
                    <dt class="text-sm font-medium text-gray-500">Ngành đào tạo</dt>
                    <dd class="mt-1">
                        <div class="font-medium text-blue-600">{{ $subject->specialization->name ?? 'N/A' }}</div>
                        @if($subject->specialization)
                            <div class="text-sm text-gray-500">Mã ngành: {{ $subject->specialization->major_code ?? '—' }}</div>
                        @endif
                    </dd>
                </div>
                {{-- semester --}}
                <div>
                    <dt class="text-sm font-medium text-gray-500">Học kỳ</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                            {{ $subject->semester_text }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Cấp độ</dt>
                    <dd class="mt-1">
                        <x-level-badge :color="$subject->level_color" :text="$subject->level_text" />
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">Phương pháp đánh giá</dt>
                    <dd class="mt-1">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                            {{ $subject->assessment_method_text }}
                        </span>
                    </dd>
                </div>
                
            </dl>
        </div>

        {{-- Metadata --}}
        <x-metadata-card
            title="Thông tin hệ thống"
            icon="info"
            icon-color="gray"
            :data="[
                ['label' => 'Người tạo', 'value' => $subject->creator->name ?? 'N/A'],
                ['label' => 'Ngày tạo', 'value' => $subject->created_at->format('d/m/Y H:i')],
                ...($subject->updater && $subject->updated_at != $subject->created_at ? [
                    ['label' => 'Cập nhật cuối', 'value' => $subject->updated_at->format('d/m/Y H:i')],
                    ['label' => 'Người cập nhật', 'value' => $subject->updater->name ?? 'N/A']
                ] : [])
            ]" />
    </div>
</div>

@endsection

@include('subject::partials.lesson-import-ajax-script')
