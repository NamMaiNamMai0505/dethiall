@if(auth()->check() && (auth()->user()->can('training-schedules.edit') || auth()->user()->can('schedule-details.create') || auth()->user()->can('schedule-details.edit')))
@extends('layouts.admin')

@section('title', $mode === 'create' ? 'Thêm lịch học' : 'Chỉnh sửa lịch học')
@section('page-title', $mode === 'create' ? 'Thêm lịch học' : 'Chỉnh sửa lịch học')
@section('turbo-cache-control', 'no-cache')
@section('turbo-visit-control', 'reload')

@section('page-actions')
    <a href="{{ route('training-schedules.show', $trainingSchedule) }}" class="btn btn-secondary">
        Quay lại
    </a>
@endsection
@section('content')
    <div data-schedule-detail-page data-turbo="false">
    <x-breadcrumb :items="[
            ['title' => 'Trang chủ'],
            ['title' => 'Lịch đào tạo', 'url' => route('training-schedules.hub')],
            ['title' => $trainingSchedule->name, 'url' => route('training-schedules.show', $trainingSchedule)],
            ['title' => $mode === 'create' ? 'Thêm lịch học' : 'Chỉnh sửa lịch học']
        ]" />
    
    <x-page-header
        title="{{ $mode === 'create' ? 'Thêm lịch học ' : 'Chỉnh sửa lịch học' }} | {{ $trainingSchedule->name }} - Ngành đào tạo {{ $trainingSchedule->specialization->name }} "
        :actions="[
            [
                'url' => route('training-schedules.show', $trainingSchedule),
                'label' => 'Quay lại',
                'icon' => 'arrow-left',
                'color' => 'gray'
            ]
        ]" />

    @if(!empty($dateLocked))
        <div class="mb-4 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <i class="bi bi-lock-fill mt-0.5"></i>
            <div>
                <p class="font-semibold">Buổi học này đã kết thúc quá thời gian cho phép chỉnh sửa.</p>
                <p class="mt-0.5">Chỉ Super Admin sửa được lịch của ngày đã học xong.</p>
            </div>
        </div>
    @endif

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <form method="POST" action="{{ $mode === 'create'
        ? route('training-schedules.schedule-details.store', $trainingSchedule)
        : route('training-schedules.schedule-details.update', [$trainingSchedule, $date]) }}"
            data-turbo="false" data-schedule-detail-form>
            @csrf
            @if($mode === 'edit')
                @method('PUT')
            @endif
            {{-- Khoá cả khối field khi buổi học đã hết ân hạn sửa. Chặn thật nằm
                 ở server (ensureScheduleDetailDateEditable); khoá ở đây chỉ để
                 người dùng không nhập uổng công. --}}
            @if(!empty($dateLocked))
                <fieldset disabled>
            @endif

            <div class="p-4 border-b bg-gray-50">
                {{-- Hàng badge phạm vi: các pill có thể xuống dòng khi màn hình hẹp,
                     nên dòng chú thích bên dưới phải tách hẳn khối (block riêng +
                     khoảng cách trên) thay vì chỉ dựa vào margin của thẻ <p>. --}}
                <div class="flex flex-wrap items-center gap-x-2 gap-y-2 text-sm">
                    <span class="font-semibold text-slate-800">Phạm vi:</span>
                    <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-slate-800">{{ $scopeLabel ?? '—' }}</span>
                    @if(!empty($canManageSkeleton) && empty($isFacultyManager))
                        <span class="inline-flex text-xs text-blue-700 bg-blue-50 border border-blue-100 rounded-full px-2 py-0.5 leading-5">
                            PDOT: chỉnh <strong class="mx-1">Môn · Loại tiết · Địa điểm</strong> — Bài học &amp; GV do Khoa
                        </span>
                    @endif
                    @if(!empty($isFacultyManager))
                        <span class="inline-flex text-xs text-teal-800 bg-teal-50 border border-teal-100 rounded-full px-2 py-0.5 leading-5">
                            Khoa{{ !empty($facultyUnitCode) ? ' '.$facultyUnitCode : '' }}: chỉnh <strong class="mx-1">Bài học · GV</strong>
                            (môn …{{ $facultyUnitCode ?? 'Kn' }}) — khung Môn/Loại/Phòng chỉ xem
                        </span>
                    @endif
                </div>
                @if((!empty($canManageSkeleton) && empty($isFacultyManager)) || !empty($isFacultyManager))
                    <div class="mt-4 border-t border-slate-200 pt-3">
                        <p class="text-xs leading-5 text-slate-600">
                            @if(!empty($isFacultyManager))
                                Môn, loại tiết và địa điểm do Phòng Đào tạo đã xếp — bạn chỉ phân bổ bài học và giảng viên cho môn thuộc khoa.
                            @else
                                Bạn xếp khung lịch cho lớp. Sau khi lưu, cấp Khoa sẽ gán bài học và giảng viên trên cùng các tiết này.
                            @endif
                        </p>
                    </div>
                @endif
                @php
                    $previousDate = \Carbon\Carbon::parse($date)->subDay()->toDateString();
                    $nextDate = \Carbon\Carbon::parse($date)->addDay()->toDateString();
                    $canGoPrevious = \Carbon\Carbon::parse($previousDate)->gte(\Carbon\Carbon::parse($trainingSchedule->start_date));
                    $canGoNext = \Carbon\Carbon::parse($nextDate)->lte(\Carbon\Carbon::parse($trainingSchedule->end_date));
                @endphp
                <div class="mt-4 flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <h2 class="min-w-0 text-lg font-semibold text-gray-800">
                        {{ $mode === 'create' ? 'Thêm lịch học cho ngày ' : 'Chỉnh sửa lịch học ngày ' }}
                        <span class="text-blue-600">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }} - {{ ucfirst(\Carbon\Carbon::parse($date)->locale('vi')->translatedFormat('l')) }}</span>
                    </h2>
                    <div class="flex w-full shrink-0 items-center gap-2 overflow-x-auto pb-1 sm:w-auto sm:pb-0">
                        @if($canGoPrevious)
                        <a href="{{ route('training-schedules.schedule-details.navigate', [
                                'trainingSchedule' => $trainingSchedule,
                                'date' => $previousDate,
                            ]) }}"
                           data-turbo="false"
                           class="flex items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-700 transition-colors hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                            </svg>
                            Lùi ngày
                        </a>
                        @else
                        <span class="flex cursor-not-allowed items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-slate-200 bg-slate-100 px-4 py-2 text-slate-400" title="Đã ở ngày đầu của lịch">
                            <i class="bi bi-arrow-left"></i>Lùi ngày
                        </span>
                        @endif
                        @if($canGoNext)
                        <a href="{{ route('training-schedules.schedule-details.navigate', [
                                'trainingSchedule' => $trainingSchedule,
                                'date' => $nextDate,
                            ]) }}"
                           data-turbo="false"
                           class="flex items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-700 transition-colors hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700">
                            Ngày tiếp theo
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                        @else
                        <span class="flex cursor-not-allowed items-center justify-center gap-2 whitespace-nowrap rounded-lg border border-slate-200 bg-slate-100 px-4 py-2 text-slate-400" title="Đã ở ngày cuối của lịch">
                            Ngày tiếp theo <i class="bi bi-arrow-right"></i>
                        </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="p-4 space-y-6">
                {{-- BUỔI SÁNG --}}
                <div class="bg-blue-50 rounded-lg border border-blue-200">
                    <div class="flex items-center justify-between px-4 py-3 bg-blue-100 rounded-t-lg">
                        <h3 class="font-semibold text-blue-800 flex items-center">
                            ☀️ BUỔI SÁNG (Tiết 1-5)
                        </h3>
                    </div>
                    <div class="p-4 space-y-3">
                        @for($period = 1; $period <= 5; $period++)
                            @php
                                $detail = $scheduleDetails->firstWhere('period', $period);
                                $facultySubjects = collect($facultySubjectIds ?? []);
                                $hideForFaculty = !empty($isFacultyManager) && !empty($detail?->subject_id)
                                    && $facultySubjects->isNotEmpty()
                                    && !$facultySubjects->contains((int) $detail->subject_id);
                            @endphp
                            <div class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-sm transition-shadow {{ $hideForFaculty ? 'hidden' : '' }}">
                                <div class="grid grid-cols-[3.25rem_minmax(0,1fr)_auto] items-start gap-3 xl:grid-cols-[4rem_minmax(0,1fr)_auto] xl:gap-4">
                                    {{-- Tiết học --}}
                                    <div class="flex w-full items-start justify-center pt-1">
                                        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gray-100 text-base font-semibold leading-none text-gray-700 tabular-nums">
                                            {{ $period }}
                                        </span>
                                    </div>

                                    @include('training-schedule::partials.period-fields', ['period' => $period, 'detail' => $detail])

                                    {{-- Action buttons --}}
                                    <div class="col-auto flex shrink-0 items-center justify-end gap-2">
                                        {{-- Copy current row down --}}
                                        @if($period < 5)
                                            <button type="button"
                                                    class="copy-row p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-md transition-colors"
                                                    data-from="{{ $period }}"
                                                    title="Copy tiết này xuống các tiết sau (trong cùng buổi)">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                                </svg>
                                            </button>
                                        @endif
                                        
                                        {{-- Clear current row --}}
                                        <button type="button" 
                                                class="clear-row p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors"
                                                data-period="{{ $period }}" 
                                                title="Xóa dữ liệu tiết này">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- General error for this period (move lên trước stats để tránh overlap) --}}
                                @error("details.$period")
                                    <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-md">
                                        <p class="text-red-600 text-sm">{{ $message }}</p>
                                    </div>
                                @enderror

                                {{-- Stats/Info row --}}
                                <div class="mt-6 pt-3 px-3 pb-2 border rounded-md bg-blue-50 border-blue-200 stats-row" style="display: none;" data-period="{{ $period }}">
                                    <div class="text-xs text-gray-600 grid grid-cols-2 lg:grid-cols-4 gap-3">
                                        <div class="subject-stats"></div>
                                        <div class="instructor-stats"></div>
                                        <div class="classroom-stats"></div>
                                        <div class="lesson-stats"></div>
                                    </div>
                                </div>

                                {{-- Hidden fields --}}
                                <input type="hidden" name="details[{{ $period }}][period]" value="{{ $period }}">
                                <input type="hidden" name="details[{{ $period }}][date]" value="{{ $date }}">
                            </div>
                        @endfor
                    </div>
                </div>

                {{-- BUỔI CHIỀU --}}
                <div class="bg-amber-50 rounded-lg border border-amber-200">
                    <div class="flex items-center justify-between px-4 py-3 bg-amber-100 rounded-t-lg"> {{-- FIX: Đổi bg-amber-50 thành bg-amber-100 cho consistent với sáng --}}
                        <h3 class="font-semibold text-amber-800 flex items-center">
                            🌅 BUỔI CHIỀU (Tiết 6-9)
                        </h3>
                    </div>
                    <div class="p-4 space-y-3">
                        @for($period = 6; $period <= 9; $period++)
                            @php
                                $detail = $scheduleDetails->firstWhere('period', $period);
                                $facultySubjects = collect($facultySubjectIds ?? []);
                                $hideForFaculty = !empty($isFacultyManager) && !empty($detail?->subject_id)
                                    && $facultySubjects->isNotEmpty()
                                    && !$facultySubjects->contains((int) $detail->subject_id);
                            @endphp
                            <div class="bg-white rounded-lg border border-gray-200 p-4 hover:shadow-sm transition-shadow {{ $hideForFaculty ? 'hidden' : '' }}">
                                <div class="grid grid-cols-[3.25rem_minmax(0,1fr)_auto] items-start gap-3 xl:grid-cols-[4rem_minmax(0,1fr)_auto] xl:gap-4">
                                    {{-- Tiết học --}}
                                    <div class="flex w-full items-start justify-center pt-1">
                                        <span class="inline-flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gray-100 text-base font-semibold leading-none text-gray-700 tabular-nums">
                                            {{ $period }}
                                        </span>
                                    </div>

                                    @include('training-schedule::partials.period-fields', ['period' => $period, 'detail' => $detail])

                                    {{-- Action buttons --}}
                                    <div class="col-auto flex shrink-0 items-center justify-end gap-2">
                                        @if($period < 9)
                                            <button type="button"
                                                    class="copy-row p-2 text-gray-400 hover:text-green-600 hover:bg-green-50 rounded-md transition-colors"
                                                    data-from="{{ $period }}"
                                                    title="Copy tiết này xuống các tiết sau (trong cùng buổi)">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                                </svg>
                                            </button>
                                        @endif
                                        
                                        {{-- Clear current row --}}
                                        <button type="button" 
                                                class="clear-row p-2 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-md transition-colors"
                                                data-period="{{ $period }}" 
                                                title="Xóa dữ liệu tiết này">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                {{-- General error for this period --}}
                                @error("details.$period")
                                    <div class="mt-3 p-3 bg-red-50 border border-red-200 rounded-md">
                                        <p class="text-red-600 text-sm">{{ $message }}</p>
                                    </div>
                                @enderror

                                {{-- Stats/Info row --}}
                                <div class="mt-6 pt-3 px-3 pb-2 border rounded-md bg-blue-50 border-blue-200 stats-row" style="display: none;" data-period="{{ $period }}">
                                    <div class="text-xs text-gray-600 grid grid-cols-2 lg:grid-cols-4 gap-3">
                                        <div class="subject-stats"></div>
                                        <div class="instructor-stats"></div>
                                        <div class="classroom-stats"></div>
                                        <div class="lesson-stats"></div>
                                    </div>
                                </div>

                                {{-- Hidden fields --}}
                                <input type="hidden" name="details[{{ $period }}][period]" value="{{ $period }}">
                                <input type="hidden" name="details[{{ $period }}][date]" value="{{ $date }}">
                            </div>
                        @endfor
                    </div>
                </div>
            </div>

            @if(!empty($dateLocked))
                </fieldset>
            @endif

            <div class="flex flex-wrap items-center justify-between gap-3 border-t bg-gray-50 px-6 py-4">
                <a href="{{ route('training-schedules.show', $trainingSchedule) }}" class="text-gray-600 hover:text-gray-800 transition-colors">
                    Quay lại danh sách
                </a>
                @unless(!empty($dateLocked))
                    <div class="flex shrink-0 flex-nowrap gap-3 overflow-x-auto">
                        <button type="submit" name="action" value="save" class="whitespace-nowrap rounded-lg bg-gray-600 px-6 py-2 font-medium text-white transition-colors hover:bg-gray-700">
                            {{ $mode === 'create' ? 'Lưu lịch học' : 'Cập nhật lịch học' }}
                        </button>
                        @php
                            // Khoa không có quyền schedule-details.create nên không chắc
                            // đi tiếp được sang ngày kế tiếp nếu ngày đó chưa có khung PĐT.
                            // Đổi nhãn để không hứa hẹn điều có thể không xảy ra; server
                            // (storeScheduleDetail/updateScheduleDetail) đã tự xử lý an
                            // toàn — chỉ đưa Khoa quay lại trang lịch kèm cảnh báo.
                            $mayCreateNextDay = auth()->user()?->can('schedule-details.create');
                            $nextLabel = $canGoNext
                                ? ($mode === 'create' ? 'Lưu & Ngày tiếp theo' : 'Cập nhật & Ngày tiếp theo')
                                : ($mode === 'create' ? 'Lưu & Hoàn tất' : 'Cập nhật & Hoàn tất');
                            if ($canGoNext && ! $mayCreateNextDay) {
                                $nextLabel = $mode === 'create' ? 'Lưu' : 'Cập nhật';
                            }
                        @endphp
                        <button type="submit" name="action" value="save_and_next" class="flex shrink-0 items-center gap-2 whitespace-nowrap rounded-lg bg-blue-600 px-6 py-2 font-medium text-white transition-colors hover:bg-blue-700">
                            {{ $nextLabel }}
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                @endunless
            </div>
        </form>
    </div>
    </div>
@endsection

@include('training-schedule::partials.schedule-detail-form-scripts')
@else
    @extends('layouts.admin')
    @section('title', 'Không có quyền')
    @section('content')
        <div class="bg-white rounded-lg shadow-sm border p-8 text-center">
            <i class="bi bi-shield-lock text-4xl text-gray-300 mb-3"></i>
            <p class="text-lg font-medium text-gray-800">Bạn không có quyền chỉnh sửa lịch học chi tiết.</p>
            <a href="{{ route('training-schedules.show', $trainingSchedule) }}" class="inline-block mt-4 text-blue-600 hover:underline">
                Quay lại lịch đào tạo
            </a>
        </div>
    @endsection
@endif
