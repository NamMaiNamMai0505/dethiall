{{-- Sprint 8 M5 — Widget LMS trên Dashboard --}}
@php
    $lms = $lms_stats ?? ['ready' => false];
@endphp

<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-sm p-5 border-l-4 border-teal-500">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                    <i class="bi bi-mortarboard text-teal-600"></i>
                    Thống kê LMS
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    Chuyên cần · tiến độ · bài chờ chấm · {{ $dashboard_scope['label'] }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @if(auth()->user()->isInstructor() && Route::has('lms.teach.home'))
                    <a href="{{ route('lms.teach.home') }}" data-turbo="false"
                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold bg-teal-50 text-teal-800 hover:bg-teal-100">
                        <i class="bi bi-easel"></i> Khóa giảng dạy
                    </a>
                @else
                    <a href="{{ route('lms.courses.index') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold bg-teal-50 text-teal-800 hover:bg-teal-100">
                        <i class="bi bi-collection"></i> Khóa LMS
                    </a>
                    <a href="{{ route('lms.courses.create') }}" class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-sm font-semibold bg-blue-600 text-white hover:bg-blue-700">
                        <i class="bi bi-plus-lg"></i> Wizard tạo khóa
                    </a>
                @endif
            </div>
        </div>
    </div>

    @if(empty($lms['ready']))
        <div class="bg-amber-50 border border-amber-200 rounded-xl p-5 text-sm text-amber-900">
            Module LMS chưa sẵn sàng (thiếu bảng <code>lms_courses</code>). Chạy migration rồi tải lại.
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
            <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-teal-500">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Khóa học</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $lms['courses_total'] }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $lms['courses_published'] }} đang mở</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-blue-500">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">HV ghi danh</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $lms['students_enrolled'] }}</p>
                <p class="text-xs text-gray-500 mt-1">distinct theo member</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-amber-500">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Chờ chấm</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">{{ $lms['pending_grades'] }}</p>
                <p class="text-xs text-gray-500 mt-1">bài nộp status submitted</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-emerald-500">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Chuyên cần TB</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">
                    {{ $lms['attendance_pct'] !== null ? $lms['attendance_pct'].'%' : '—' }}
                </p>
                <p class="text-xs text-gray-500 mt-1">present/late/excused</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-purple-500">
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Tiến độ TB</p>
                <p class="text-2xl font-bold text-gray-900 mt-1">
                    {{ $lms['progress_pct'] !== null ? $lms['progress_pct'].'%' : '—' }}
                </p>
                <p class="text-xs text-gray-500 mt-1">overall_pct</p>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-4 border-l-4 border-slate-400">
                @if(auth()->user()->isInstructor() && Route::has('instructor-schedule.index'))
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Lịch cá nhân</p>
                    <a href="{{ route('instructor-schedule.index') }}" class="inline-flex mt-2 text-sm font-semibold text-blue-700 hover:underline">
                        Xem lịch dạy →
                    </a>
                @else
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wide">Export điểm</p>
                    <a href="{{ route('lms.gradebook.export-multi') }}" class="inline-flex mt-2 text-sm font-semibold text-blue-700 hover:underline">
                        Nhiều khóa → CSV
                    </a>
                @endif
            </div>
        </div>

        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 flex items-center justify-between">
                <h3 class="font-semibold text-gray-900">
                    {{ $dashboard_scope['is_global'] ? 'Theo khoa (GV phụ trách khóa)' : 'LMS trong phạm vi tài khoản' }}
                </h3>
                <span class="text-xs text-gray-400">{{ count($lms['by_unit'] ?? []) }} khoa có khóa LMS</span>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                        <tr>
                            <th class="px-4 py-2.5 font-semibold">Khoa / đơn vị</th>
                            <th class="px-4 py-2.5 font-semibold text-right">Khóa</th>
                            <th class="px-4 py-2.5 font-semibold text-right">HV</th>
                            <th class="px-4 py-2.5 font-semibold text-right">Chuyên cần</th>
                            <th class="px-4 py-2.5 font-semibold text-right">Tiến độ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse(($lms['by_unit'] ?? []) as $row)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-4 py-2.5 font-medium text-gray-900">{{ $row['unit'] }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums">{{ $row['courses'] }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums">{{ $row['students'] }}</td>
                                <td class="px-4 py-2.5 text-right tabular-nums">
                                    {{ $row['attendance_pct'] !== null ? $row['attendance_pct'].'%' : '—' }}
                                </td>
                                <td class="px-4 py-2.5 text-right tabular-nums">
                                    {{ $row['progress_pct'] !== null ? $row['progress_pct'].'%' : '—' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                    Chưa có khóa LMS gắn giảng viên theo khoa.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
