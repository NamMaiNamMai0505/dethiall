@extends('layouts.admin')

@section('title', 'Lịch học của tôi')
@section('page-title', 'Lịch học của tôi')

@section('content')
    <x-breadcrumb :items="[
            ['title' => 'Trang chủ'],
            ['title' => 'Lịch học của tôi']
        ]" />

    @if($noClass ?? false)
        {{-- No Class Assigned State --}}
        <div class="bg-white rounded-lg shadow-sm border p-8 text-center">
            <i class="bi bi-info-circle text-gray-400 text-6xl mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">Chưa có lớp học</h3>
            <p class="text-gray-500">Bạn chưa được phân vào lớp học nào. Vui lòng liên hệ phòng Đào tạo.</p>
        </div>
    @elseif($noSchedule ?? false)
        {{-- No Active Schedule State --}}
        <div class="bg-white rounded-lg shadow-sm border p-8 text-center">
            <i class="bi bi-calendar-x text-gray-400 text-6xl mb-4"></i>
            <h3 class="text-xl font-semibold text-gray-700 mb-2">Chưa có lịch học</h3>
            <p class="text-gray-500">Lớp của bạn chưa có lịch học nào được kích hoạt cho học kỳ hiện tại.</p>
        </div>
    @else
        {{-- Header: Student Info --}}
        <div class="bg-gradient-to-r from-blue-500 to-blue-700 rounded-lg shadow-lg p-6 mb-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold mb-2">{{ $student->name }}</h1>
                    <p class="text-blue-100">
                        <i class="bi bi-mortarboard mr-2"></i>
                        Lớp: {{ $student->class->code ?? $student->class->name ?? 'Chưa có lớp' }}
                    </p>
                    <p class="text-blue-100">
                        <i class="bi bi-envelope mr-2"></i>
                        Email: {{ $student->email ?? 'Chưa có email' }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Statistics Cards for Current Week --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow-sm border p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="rounded-md bg-green-500 p-3">
                            <i class="bi bi-book text-white text-2xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <dt class="text-sm font-medium text-gray-500 truncate">Số môn học</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ $stats['total_subjects'] }}</dd>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="rounded-md bg-indigo-500 p-3">
                            <i class="bi bi-journal-text text-white text-2xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <dt class="text-sm font-medium text-gray-500 truncate">Tiết lý thuyết</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ $stats['theory_hours'] }}</dd>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="rounded-md bg-orange-500 p-3">
                            <i class="bi bi-laptop text-white text-2xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <dt class="text-sm font-medium text-gray-500 truncate">Tiết thực hành</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ $stats['practice_hours'] }}</dd>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-sm border p-4">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <div class="rounded-md bg-blue-500 p-3">
                            <i class="bi bi-clock text-white text-2xl"></i>
                        </div>
                    </div>
                    <div class="ml-4">
                        <dt class="text-sm font-medium text-gray-500 truncate">Tổng số tiết</dt>
                        <dd class="mt-1 text-3xl font-semibold text-gray-900">{{ $stats['total_hours'] }}</dd>
                    </div>
                </div>
            </div>
        </div>

        {{-- Date Range Filter & Export Section --}}
        <div class="bg-white rounded-lg shadow-sm border p-4 mb-4">
            <div class="flex items-end gap-4 flex-wrap">
                <form method="GET" action="{{ route('student-schedule.index') }}" class="flex items-end gap-4 flex-wrap flex-1" id="filterForm">
                    <div class="flex-1 min-w-[200px]">
                        <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Từ ngày</label>
                        <input type="date" id="date_from" name="date_from"
                               value="{{ request('date_from', $dateRange['start'] ?? '') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="flex-1 min-w-[200px]">
                        <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Đến ngày</label>
                        <input type="date" id="date_to" name="date_to"
                               value="{{ request('date_to', $dateRange['end'] ?? '') }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors font-medium">
                            <i class="bi bi-filter mr-2"></i>Lọc
                        </button>
                        <a href="{{ route('student-schedule.index') }}"
                           class="px-6 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors font-medium">
                            <i class="bi bi-arrow-clockwise mr-2"></i>Đặt lại
                        </a>
                    </div>
                </form>

                <form method="POST" action="{{ route('student-schedule.export') }}" class="inline-block">
                    @csrf
                    <input type="hidden" name="start_date" value="{{ $dateRange['start'] ?? '' }}">
                    <input type="hidden" name="end_date" value="{{ $dateRange['end'] ?? '' }}">
                    <button type="submit" class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors font-medium">
                        <i class="bi bi-file-earmark-excel mr-2"></i>Xuất Excel
                    </button>
                </form>
            </div>
        </div>

        <script>
            document.getElementById('filterForm')?.addEventListener('submit', function(e) {
                const dateFrom = document.getElementById('date_from')?.value || '';
                const dateTo = document.getElementById('date_to')?.value || '';

                console.log('Form submitting...');
                console.log('Date from:', dateFrom);
                console.log('Date to:', dateTo);

                // Kiểm tra nếu cả 2 đều trống thì cho phép submit (sẽ dùng week navigation)
                if (!dateFrom && !dateTo) {
                    return true;
                }

                // Nếu chỉ có 1 field thì báo lỗi
                if ((dateFrom && !dateTo) || (!dateFrom && dateTo)) {
                    e.preventDefault();
                    Notify.warning('Vui lòng chọn cả Từ ngày và Đến ngày');
                    return false;
                }

                // Kiểm tra date_to >= date_from
                if (dateFrom && dateTo && dateTo < dateFrom) {
                    e.preventDefault();
                    Notify.warning('Đến ngày phải lớn hơn hoặc bằng Từ ngày');
                    return false;
                }

                return true;
            });
        </script>

        {{-- Week Navigation --}}
        <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
            <div class="flex items-center justify-between">
                <a href="{{ route('student-schedule.index', ['week_offset' => $weekOffset - 1]) }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">
                    <i class="bi bi-chevron-left mr-2"></i>
                    Tuần trước
                </a>

                <div class="text-center">
                    <div class="text-sm text-gray-500">{{ request()->filled('date_from') ? 'Khoảng thời gian' : 'Tuần hiện tại' }}</div>
                    <div class="text-lg font-semibold text-gray-900">{{ $dateRangeLabel }}</div>
                </div>

                <a href="{{ route('student-schedule.index', ['week_offset' => $weekOffset + 1]) }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-700 rounded-lg transition-colors">
                    Tuần sau
                    <i class="bi bi-chevron-right ml-2"></i>
                </a>
            </div>
        </div>

        {{-- Weekly Calendar Table --}}
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="overflow-x-auto max-h-[calc(100vh-300px)] overflow-y-auto">
                <table class="min-w-full border-collapse">
                    {{-- Table Header: Days of the Week --}}
                    <thead>
                        <tr class="bg-gradient-to-r from-blue-600 to-blue-700">
                            {{-- Period Label Column (Sticky) --}}
                            <th class="px-4 py-3 border-r border-blue-500 sticky left-0 top-0 bg-blue-600 z-20 w-24">
                                <span class="text-white font-semibold text-sm uppercase">Tiết</span>
                            </th>

                            {{-- Each Day of the Week --}}
                            @foreach($calendar as $dayData)
                                <th class="px-3 py-3 border-r border-blue-500 last:border-r-0 min-w-[200px] sticky top-0 {{ $dayData['is_today'] ? 'bg-yellow-500 z-10' : 'bg-blue-700 z-10' }}">
                                    <div class="text-center">
                                        {{-- Date (DD/MM) --}}
                                        <div class="font-bold text-base {{ $dayData['is_today'] ? 'text-gray-900' : 'text-white' }}">
                                            {{ $dayData['day_number'] }}/{{ $dayData['month_number'] }}
                                        </div>
                                        {{-- Weekday Name --}}
                                        <div class="text-xs mt-1 font-medium {{ $dayData['is_today'] ? 'text-gray-700' : 'text-blue-100' }}">
                                            {{ $dayData['weekday'] }}
                                        </div>
                                        @if($dayData['is_today'])
                                            <div class="text-xs font-bold text-gray-900 mt-1">
                                                • HÔM NAY •
                                            </div>
                                        @endif
                                    </div>
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    {{-- Table Body: 9 Periods --}}
                    <tbody>
                        @for($period = 1; $period <= 9; $period++)
                            <tr class="hover:bg-blue-50/50 transition-colors duration-150 {{ $period % 2 == 0 ? 'bg-gray-50/50' : 'bg-white' }}">
                                {{-- Period Number (Sticky Left) --}}
                                <td class="border px-3 py-4 text-center sticky left-0 {{ $period % 2 == 0 ? 'bg-gray-50' : 'bg-white' }} z-10">
                                    <div class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 text-white font-bold shadow-sm">
                                        {{ $period }}
                                    </div>
                                </td>

                                {{-- Each Cell: Day × Period Intersection --}}
                                @foreach($calendar as $dayData)
                                    @php
                                        $detail = $dayData['periods'][$period];
                                    @endphp
                                    <td class="border px-3 py-4 align-top {{ $dayData['is_today'] ? 'bg-yellow-50/30' : '' }}">
                                        @if($detail)
                                            <div class="space-y-2">
                                                {{-- Subject Name + Lesson Type --}}
                                                <div class="font-semibold text-sm leading-tight text-gray-900">
                                                    {{ $detail->subject->name ?? 'N/A' }}
                                                    <span class="inline-block px-2 py-0.5 text-xs font-medium rounded ml-1
                                                        @if($detail->lesson_type === 'theory') bg-blue-100 text-blue-700
                                                        @elseif($detail->lesson_type === 'practice') bg-green-100 text-green-700
                                                        @elseif($detail->lesson_type === 'self_study') bg-gray-100 text-gray-700
                                                        @else bg-red-100 text-red-700
                                                        @endif">
                                                        @if($detail->lesson_type === 'theory')
                                                            LT
                                                        @elseif($detail->lesson_type === 'practice')
                                                            TH
                                                        @elseif($detail->lesson_type === 'self_study')
                                                            Ôn
                                                        @elseif($detail->lesson_type === 'final_exam')
                                                            Thi/KT
                                                        @endif
                                                    </span>
                                                </div>

                                                {{-- Classroom --}}
                                                <div class="text-sm text-gray-700">
                                                    Phòng:
                                                    <span class="text-blue-600 font-medium">
                                                        @if($detail->classroom)
                                                            {{ $detail->classroom->name }}
                                                        @else
                                                            N/A
                                                        @endif
                                                    </span>
                                                </div>
                                            </div>
                                        @else
                                            {{-- Empty Cell --}}
                                            <div class="text-center py-6 text-gray-300 text-sm">
                                                Trống
                                            </div>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Legend --}}
        <div class="mt-6 bg-white rounded-lg shadow-sm border p-4">
            <div class="flex items-center gap-6 text-sm">
                <div class="font-semibold text-gray-700">Chú thích:</div>
                <div class="flex items-center gap-2">
                    <span class="inline-block px-3 py-1 rounded bg-blue-100 text-blue-700 font-medium">Lý thuyết</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block px-3 py-1 rounded bg-green-100 text-green-700 font-medium">Thực hành</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block px-3 py-1 rounded bg-gray-100 text-gray-700 font-medium">Tự học</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-block px-3 py-1 rounded bg-red-100 text-red-700 font-medium">Thi/Kiểm tra</span>
                </div>
                <div class="flex items-center gap-2 ml-auto">
                    <span class="inline-block px-3 py-1 rounded bg-yellow-100 text-gray-900 font-medium border-2 border-yellow-400">Ngày hôm nay</span>
                </div>
            </div>
        </div>
    @endif
@endsection

@push('styles')
<style>
    /* Ensure sticky positioning works properly */
    .sticky {
        position: sticky;
    }

    /* Smooth transitions for hover effects */
    tbody tr:hover td {
        transition: background-color 0.15s ease;
    }

    /* Better scrollbar styling */
    .overflow-x-auto::-webkit-scrollbar,
    .overflow-y-auto::-webkit-scrollbar {
        width: 8px;
        height: 8px;
    }

    .overflow-x-auto::-webkit-scrollbar-track,
    .overflow-y-auto::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    .overflow-x-auto::-webkit-scrollbar-thumb,
    .overflow-y-auto::-webkit-scrollbar-thumb {
        background: #2563eb;
        border-radius: 4px;
    }

    .overflow-x-auto::-webkit-scrollbar-thumb:hover,
    .overflow-y-auto::-webkit-scrollbar-thumb:hover {
        background: #1d4ed8;
    }
</style>
@endpush
