@extends('layouts.admin')

@section('title', 'Tài liệu LMS')
@section('page-title', 'Tài liệu & SCORM')

@section('content')
    <x-breadcrumb :items="[
        ['title' => 'LMS', 'url' => route('lms.hub')],
        ['title' => $course->title, 'url' => route('lms.courses.show', $course)],
        ['title' => 'Tài liệu'],
    ]" />

    <div class="flex flex-wrap justify-between gap-3 mb-6">
        <div>
            <h1 class="text-2xl font-bold">Tài liệu — {{ $course->title }}</h1>
            <p class="text-sm text-slate-500">PDF, slide, video, tài liệu, ZIP · gói SCORM (extract + launch)</p>
        </div>
        <a href="{{ route('lms.courses.show', $course) }}" class="px-4 py-2 border rounded-lg text-sm">← Khóa học</a>
    </div>

    @can('lms.edit')
        <div class="bg-white border rounded-xl p-5 mb-6 shadow-sm">
            <h2 class="font-semibold mb-3">Tải lên</h2>
            <form method="POST" action="{{ route('lms.courses.materials.store', $course) }}" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-3">
                @csrf
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-slate-600">File * (tối đa {{ \Modules\Lms\Services\LmsMaterialService::MAX_MB }} MB)</label>
                    <input type="file" name="file" required class="w-full text-sm border rounded-lg p-2 mt-1">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Tiêu đề</label>
                    <input type="text" name="title" class="w-full border rounded-lg text-sm px-3 py-2 mt-1" placeholder="Để trống = tên file">
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-600">Gắn bài học (tuỳ chọn)</label>
                    <select name="lms_lesson_id" class="w-full border rounded-lg text-sm px-3 py-2 mt-1">
                        <option value="">— Không —</option>
                        @foreach($course->lessons as $les)
                            <option value="{{ $les->id }}">{{ $les->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label class="text-xs font-semibold text-slate-600">Mô tả</label>
                    <input type="text" name="description" class="w-full border rounded-lg text-sm px-3 py-2 mt-1">
                </div>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="as_scorm" value="1" class="rounded border-slate-300">
                    Đây là gói SCORM (.zip) — bung và đọc imsmanifest.xml
                </label>
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_published" value="1" checked class="rounded border-slate-300">
                    Công khai cho học viên
                </label>
                <div class="md:col-span-2">
                    <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold">Upload</button>
                </div>
            </form>
        </div>
    @endcan

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
        <div class="bg-white border rounded-xl overflow-hidden shadow-sm">
            <div class="px-4 py-3 border-b font-semibold bg-slate-50">Tài liệu ({{ $course->materials->count() }})</div>
            <ul class="divide-y">
                @forelse($course->materials as $m)
                    <li class="px-4 py-3 flex justify-between gap-2 text-sm">
                        <div>
                            <div class="font-medium">{{ $m->title }}</div>
                            <div class="text-xs text-slate-500">{{ $m->kindLabel() }} · {{ $m->humanSize() }} · {{ $m->original_name }}</div>
                        </div>
                        <div class="flex gap-2 shrink-0 items-start">
                            <a href="{{ route('lms.courses.materials.download', [$course, $m]) }}" class="text-blue-600" target="_blank">Mở</a>
                            @can('lms.edit')
                                <form method="POST" action="{{ route('lms.courses.materials.destroy', [$course, $m]) }}" data-confirm="Xoá tài liệu này?" data-confirm-danger="1" data-confirm-title="Xoá tài liệu">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 text-xs">Xoá</button>
                                </form>
                            @endcan
                        </div>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-slate-500 text-sm">Chưa có file.</li>
                @endforelse
            </ul>
        </div>
        <div class="bg-white border rounded-xl overflow-hidden shadow-sm">
            <div class="px-4 py-3 border-b font-semibold bg-slate-50">SCORM ({{ $course->scormPackages->count() }})</div>
            <ul class="divide-y">
                @forelse($course->scormPackages as $sc)
                    <li class="px-4 py-3 flex justify-between gap-2 text-sm">
                        <div>
                            <div class="font-medium">{{ $sc->title }}</div>
                            <div class="text-xs text-slate-500">v{{ $sc->version ?? '?' }} · launch: {{ $sc->launch_path ?? '—' }}</div>
                        </div>
                        <div class="flex gap-2 shrink-0">
                            @if($sc->launchUrl())
                                <a href="{{ $sc->launchUrl() }}" target="_blank" class="text-blue-600">Chạy</a>
                            @endif
                            @can('lms.edit')
                                <form method="POST" action="{{ route('lms.courses.materials.destroy-scorm', [$course, $sc]) }}" data-confirm="Xoá gói SCORM này?" data-confirm-danger="1" data-confirm-title="Xoá SCORM">
                                    @csrf @method('DELETE')
                                    <button class="text-red-600 text-xs">Xoá</button>
                                </form>
                            @endcan
                        </div>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-slate-500 text-sm">Chưa có SCORM.</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
