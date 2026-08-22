@extends('layouts.admin')

@section('title', 'Khóa học LMS')
@section('page-title', 'Khóa học LMS')

@section('content')
    <x-breadcrumb :items="[
        ['title' => 'Trang chủ'],
        ['title' => 'LMS', 'url' => route('lms.hub')],
        ['title' => 'Khóa học'],
    ]" />

    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Khóa học LMS</h1>
            <p class="text-sm text-slate-500">Môn được ghi danh tự động theo ngành của lớp; Khoa chỉ cần phân công giảng viên.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('lms.hub') }}" class="px-4 py-2 rounded-lg border text-sm">Hub LMS</a>
            @canany(['lms.create', 'lms.manage'])
                <a href="{{ route('lms.gradebook.export-multi') }}" class="px-4 py-2 rounded-lg border text-sm text-slate-700 hover:bg-slate-50">
                    <i class="bi bi-download mr-1"></i> Export điểm
                </a>
            @endcanany
            @can('lms.create')
                @can('teaching-assignments.index')
                    <a href="{{ route('teaching-assignments.index') }}" class="px-4 py-2 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 text-sm font-medium hover:bg-emerald-100">
                        <i class="bi bi-person-check mr-1"></i> Phân công GV
                    </a>
                @endcan
                <a href="{{ route('lms.provisioning.index') }}" class="px-4 py-2 rounded-lg border border-blue-200 bg-blue-50 text-blue-700 text-sm font-medium hover:bg-blue-100">
                    <i class="bi bi-arrow-repeat mr-1"></i> Đồng bộ lịch
                </a>
                <a href="{{ route('lms.courses.create') }}" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-medium hover:bg-blue-700">
                    <i class="bi bi-plus-lg mr-1"></i> Lớp HP độc lập
                </a>
            @endcan
        </div>
    </div>

    <div class="bg-white rounded-xl border shadow-sm overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-100 text-left text-xs uppercase text-slate-600">
                <tr>
                    <th class="px-4 py-3">Khóa học</th>
                    <th class="px-4 py-3">Môn</th>
                    <th class="px-4 py-3">Lớp</th>
                    <th class="px-4 py-3">GV</th>
                    <th class="px-4 py-3">Năm / nguồn</th>
                    <th class="px-4 py-3">Trạng thái</th>
                    <th class="px-4 py-3">Bài / TV</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($courses as $course)
                    <tr class="hover:bg-slate-50">
                        <td class="px-4 py-3">
                            <div class="font-semibold text-slate-900">{{ $course->title }}</div>
                            @if($course->code)
                                <code class="text-xs text-slate-500">{{ $course->code }}</code>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div>{{ $course->subject->name ?? '—' }}</div>
                            <code class="text-xs bg-blue-50 text-blue-800 px-1 rounded">{{ $course->subject->display_code ?? '' }}</code>
                        </td>
                        <td class="px-4 py-3">{{ $course->classModel->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $course->instructor->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div>{{ $course->academicYear?->code ?: '—' }}{{ $course->term ? ' · '.str_replace('semester_', 'HK', $course->term) : '' }}</div>
                            <span class="mt-1 inline-flex rounded-full bg-slate-100 px-2 py-0.5 text-[11px] text-slate-600">{{ match($course->source_type) { 'training_schedule' => 'Đồng bộ lịch đào tạo', 'class_curriculum' => 'Tự động theo ngành/lớp', default => 'Lớp học phần độc lập' } }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold
                                {{ $course->status === 'published' ? 'bg-green-100 text-green-800' : ($course->status === 'archived' ? 'bg-slate-200 text-slate-700' : 'bg-amber-100 text-amber-900') }}">
                                {{ $course->statusLabel() }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">{{ $course->lessons_count }} / {{ $course->members_count }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('lms.courses.show', $course) }}" class="text-blue-600 hover:underline text-sm font-medium">Mở</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-slate-500">
                            Chưa có khóa học LMS.
                            @can('lms.create')
                                Hãy kiểm tra ngành của lớp và chạy đồng bộ chương trình.
                            @endcan
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        <div class="p-3 border-t">{{ $courses->links() }}</div>
    </div>
@endsection
