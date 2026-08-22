@extends('layouts.admin')

@section('title', 'Chi tiết Lịch Đào tạo')
@section('page-title', 'Chi tiết Lịch Đào tạo')

@section('content')
    {{-- Breadcrumb --}}
    <x-breadcrumb :items="[
            ['title' => 'Trang chủ'],
            ['title' => 'Lịch Đào Tạo', 'url' => route('training-schedules.index')],
            ['title' => 'Chi tiết']
        ]" />

    {{-- Flash: chỉ render ở layouts.admin — không include trùng --}}

    {{-- Page Header --}}
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900 uppercase">{{$trainingSchedule->name}}</h1>
        <div class="flex space-x-3">
            @if(auth()->check() && auth()->user()->can('training-schedules.edit') && !empty($canManageSkeleton))
                <a href="{{ route('training-schedules.edit', $trainingSchedule) }}"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                    <i class="bi bi-pencil mr-2"></i>Chỉnh sửa
                </a>
                <form action="{{ route('training-schedules.toggle-status', $trainingSchedule) }}" method="POST" class="inline"
                    data-confirm='Bạn có chắc muốn thay đổi trạng thái?'>
                    @csrf
                    @method('PATCH')
                    <button type="submit"
                        class="{{ $trainingSchedule->is_active ? 'bg-orange-600 hover:bg-orange-700' : 'bg-green-600 hover:bg-green-700' }} text-white px-4 py-2 rounded-lg font-medium">
                        <i class="bi bi-{{ $trainingSchedule->is_active ? 'pause' : 'play' }}-circle mr-2"></i>
                        {{ $trainingSchedule->is_active ? 'Tạm dừng' : 'Kích hoạt' }}
                    </button>
                </form>
            @endif
            <a href="{{ route('training-schedules.index') }}"
                class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium">
                <i class="bi bi-arrow-left mr-2"></i>Quay lại
            </a>
        </div>
    </div>

    {{-- Main Info Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
        {{-- Status Card --}}
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Trạng thái</p>
                    <div class="mt-2">
                        @if($trainingSchedule->is_active)
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                <i class="bi bi-check-circle mr-1"></i>Hoạt động
                            </span>
                        @else
                            <span
                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800">
                                <i class="bi bi-pause-circle mr-1"></i>Tạm dừng
                            </span>
                        @endif
                    </div>
                </div>
                <div class="p-3 rounded-lg {{ $trainingSchedule->is_active ? 'bg-green-50' : 'bg-gray-50' }}">
                    <i
                        class="bi bi-{{ $trainingSchedule->is_active ? 'check-circle' : 'pause-circle' }} text-2xl {{ $trainingSchedule->is_active ? 'text-green-600' : 'text-gray-600' }}"></i>
                </div>
            </div>
        </div>

        {{-- Weeks Count Card --}}
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Tên lớp</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">
                        {{ $trainingSchedule->classModel->name ?? 'Chưa phân lớp' }}
                    </p>
                    <p class="text-sm text-gray-500">Lớp</p>
                </div>
                <div class="p-3 bg-orange-50 rounded-lg">
                    <i class="bi bi-calendar3 text-2xl text-orange-600"></i>
                </div>
            </div>
        </div>
        {{-- Academic Year Card --}}
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Năm học</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">{{ $trainingSchedule->academic_year }}</p>
                    <p class="text-sm text-gray-500">{{ $trainingSchedule->semester_text }}</p>
                </div>
                <div class="p-3 bg-blue-50 rounded-lg">
                    <i class="bi bi-calendar-event text-2xl text-blue-600"></i>
                </div>
            </div>
        </div>

        {{-- Duration Card --}}
        <div class="bg-white rounded-lg shadow-sm border p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600">Thời lượng</p>
                    <p class="text-2xl font-bold text-gray-900 mt-2">{{ $trainingSchedule->duration_text }}</p>
                    <p class="text-sm text-gray-500">
                        {{ $trainingSchedule->start_date ? $trainingSchedule->start_date->format('d/m/Y') : 'N/A' }} -
                        {{ $trainingSchedule->end_date ? $trainingSchedule->end_date->format('d/m/Y') : 'N/A' }}
                    </p>
                </div>
                <div class="p-3 bg-purple-50 rounded-lg">
                    <i class="bi bi-clock text-2xl text-purple-600"></i>
                </div>
            </div>
        </div>


    </div>

    {{-- Main Content Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Left Column - Main Details --}}
        <div class="lg:col-span-2 space-y-6">
            {{-- Basic Information --}}
            <div class="bg-white rounded-lg shadow-sm border">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">THÔNG TIN CƠ BẢN</h3>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tên lịch đào tạo</label>
                            <p class="text-sm text-gray-900">{{ $trainingSchedule->name }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mã lịch</label>
                            <code
                                class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm font-mono">{{ $trainingSchedule->code }}</code>
                        </div>
                        @if($trainingSchedule->abbreviation)
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Tên viết tắt</label>
                                <span
                                    class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                    {{ $trainingSchedule->abbreviation }}
                                </span>
                            </div>
                        @endif
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Ngành đào tạo</label>
                            <p class="text-sm text-gray-900">{{ $trainingSchedule->specialization?->report_label ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Lớp học</label>
                            <p class="text-sm text-gray-900">
                                @if($trainingSchedule->classModel)
                                <a href="{{ route('classes.show', $trainingSchedule->classModel->id) }}"
                                    class="inline-flex items-center text-indigo-600 hover:text-indigo-800 font-medium transition-all duration-200 hover:underline underline-offset-4 group"
                                    title="Xem chi tiết lớp {{ $trainingSchedule->classModel->name }}">
                                    {{ $trainingSchedule->classModel->name }}
                                    <svg class="w-3 h-3 ml-1 opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </a>
                                @else
                                <span class="text-gray-500">Chưa phân lớp</span>
                                @endif
                            </p>
                            {{-- <p class="text-sm text-gray-900">{{ $trainingSchedule->classModel->name ?? 'Chưa phân lớp' }} --}}
                            </p>
                        </div>
                        {{-- <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Giảng đường chính</label>
                            <p class="text-sm text-gray-900">{{ $trainingSchedule->classroom->name ?? 'Chưa chọn' }}</p>
                        </div> --}}
                        @if($trainingSchedule->description)
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 mb-1">Mô tả</label>
                                <p class="text-sm text-gray-900">{{ $trainingSchedule->description }}</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Pagination Controls --}}
            <div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <h3 class="text-lg font-semibold text-gray-800">Lịch học chi tiết</h3>
                        <span class="text-sm text-gray-600">
                            ({{ $pagination['page_start_date'] }} - {{ $pagination['page_end_date'] }})
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($pagination['has_previous'])
                            <a href="{{ route('training-schedules.show', ['trainingSchedule' => $trainingSchedule->id, 'page' => $pagination['current_page'] - 1]) }}"
                               class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                4 tuần trước
                            </a>
                        @else
                            <button disabled class="flex items-center gap-2 px-4 py-2 bg-gray-100 border border-gray-300 text-gray-400 rounded-lg cursor-not-allowed">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                                </svg>
                                4 tuần trước
                            </button>
                        @endif

                        <span class="px-4 py-2 bg-blue-50 text-blue-700 rounded-lg font-medium">
                            Trang {{ $pagination['current_page'] }} / {{ $pagination['total_pages'] }}
                        </span>

                        @if($pagination['has_next'])
                            <a href="{{ route('training-schedules.show', ['trainingSchedule' => $trainingSchedule->id, 'page' => $pagination['current_page'] + 1]) }}"
                               class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors">
                                4 tuần tiếp theo
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </a>
                        @else
                            <button disabled class="flex items-center gap-2 px-4 py-2 bg-gray-100 border border-gray-300 text-gray-400 rounded-lg cursor-not-allowed">
                                4 tuần tiếp theo
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Chi tiết lịch học schedule detail --}}
            @foreach($weeks as $weekIndex => $week)
                @php
                    $weekStart = \Carbon\Carbon::parse($week['start']);
                    $scheduleStart = $trainingSchedule->start_date->copy()->startOfWeek(\Carbon\CarbonInterface::MONDAY);
                    $actualWeekNumber = $scheduleStart->diffInWeeks($weekStart) + 1;
                    $isCurrentWeek = \Carbon\Carbon::today()->between($weekStart, \Carbon\Carbon::parse($week['end']));
                @endphp
                <div class="mb-6 border rounded-lg shadow {{ $isCurrentWeek ? 'ring-2 ring-blue-500' : '' }}">
                    <div class="flex justify-between items-center {{ $isCurrentWeek ? 'bg-blue-50' : 'bg-gray-100' }} px-4 py-2 cursor-pointer"
                        onclick="toggleWeek('{{ $weekIndex }}')">
                        <h3 class="font-semibold flex items-center gap-2">
                            Tuần {{ $actualWeekNumber }} ({{ $weekStart->format('d/m') }} -
                            {{ \Carbon\Carbon::parse($week['end'])->format('d/m') }})
                            @if($isCurrentWeek)
                                <span class="px-2 py-1 bg-blue-600 text-white text-xs rounded-full">Tuần hiện tại</span>
                            @endif
                        </h3>
                        <i id="arrow-{{ $weekIndex }}"
                            class="bi bi-chevron-down text-gray-500 transition-transform duration-300"></i>
                    </div>
                    <div id="week-{{ $weekIndex }}" class="p-4 space-y-4 hidden">
                        @foreach($week['days'] as $day)
                            @php
                                // Lưới tuần được đệm cho tròn Thứ Hai-Chủ Nhật nên có thể
                                // vẽ cả những ngày nằm ngoài start_date/end_date thật của
                                // lịch đào tạo; các ngày đệm này không được thao tác (server
                                // sẽ trả 422 nếu cố tạo lịch học ở đó).
                                $isDayWithinSchedule = $day['date']->betweenIncluded(
                                    $trainingSchedule->start_date,
                                    $trainingSchedule->end_date
                                );
                            @endphp
                            <div class="bg-white border rounded-md p-3">
                                <div class="flex justify-between items-center mb-3 border-b pb-2">
                                    <h4 class="font-medium text-gray-700">
                                        {{ ucfirst($day['weekday']) }} ({{ $day['date']->format('d/m/Y') }})
                                        @if(!$isDayWithinSchedule)
                                            <span class="text-xs text-gray-400 font-normal">(ngoài lịch đào tạo)</span>
                                        @endif
                                    </h4>

                                    {{-- Wrapper cho buttons: Flex row để đặt edit + delete cạnh nhau --}}
                                    @if (auth()->check() && (!empty($canManageSkeleton) || !empty($isFacultyManager)))
                                        <div class="flex items-center space-x-2">
                                            @if($day['details']->isNotEmpty())
                                                            {{-- Nút Chỉnh sửa --}}
                                                            @if(auth()->user()->can('schedule-details.edit'))
                                                            <a href="{{ route('training-schedules.schedule-details.edit', [
                                                    'trainingSchedule' => $trainingSchedule->id,
                                                    'date' => $day['date']->format('Y-m-d')
                                                ]) }}"
                                                                data-turbo="false"
                                                                class="bg-blue-100 text-blue-700 hover:bg-blue-200 text-xs px-3 py-1 rounded flex items-center">
                                                                <i class="bi bi-pencil mr-1"></i>Chỉnh sửa / Bổ sung lịch
                                                            </a>
                                                            @endif

                                                            @php
                                                                $canDeleteDayScope = !empty($canManageSkeleton)
                                                                    || (!empty($isFacultyManager) && $day['details']->whereIn('subject_id', collect($facultySubjectIds ?? [])->all())->isNotEmpty());
                                                            @endphp
                                                            @if(auth()->user()->can('schedule-details.edit') && $canDeleteDayScope)
                                                            <form action="{{ route('training-schedules.schedule-details.destroy', [
                                                    'trainingSchedule' => $trainingSchedule->id,
                                                    'date' => $day['date']->format('Y-m-d')
                                                ]) }}" method="POST" class="inline"
                                                                data-confirm="{{ !empty($isFacultyManager) ? 'Chỉ xóa phân công thuộc khoa của bạn' : 'Xóa toàn bộ lịch học' }} ngày {{ $day['date']->format('d/m/Y') }}?">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button type="submit"
                                                                    class="bg-red-100 text-red-700 hover:bg-red-200 text-xs px-2 py-1 rounded flex items-center"
                                                                    title="{{ !empty($isFacultyManager) ? 'Xóa phân công của khoa' : 'Xóa toàn bộ lịch ngày này' }}">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </form>
                                                            @endif
                                            @else
                                                            {{-- Nút Thêm: chỉ hiện khi ngày còn trong phạm vi lịch đào tạo --}}
                                                            @if($isDayWithinSchedule && !empty($canManageSkeleton) && auth()->user()->can('schedule-details.create'))
                                                            <a href="{{ route('training-schedules.schedule-details.create', [
                                                    'trainingSchedule' => $trainingSchedule->id,
                                                    'date' => $day['date']->format('Y-m-d')
                                                ]) }}"
                                                                data-turbo="false"
                                                                class="bg-green-100 text-green-700 hover:bg-green-200 text-xs px-3 py-1 rounded flex items-center">
                                                                <i class="bi bi-plus mr-1"></i>Thêm lịch học
                                                            </a>
                                                            @endif
                                            @endif
                                        </div>
                                    @endif

                                </div>

                                {{-- Group details theo sáng/chiều --}}
                                @php
                                    $details = $day['details']->sortBy('period');
                                    $morning = $details->where('period', '<=', 5);
                                    $afternoon = $details->where('period', '>', 5)->where('period', '<=', 9);
                                @endphp

                                {{-- Buổi sáng --}}
                                @if($morning->isNotEmpty())
                                    <div class="mb-4">
                                        <h5 class="font-semibold text-sm text-gray-600 mb-2 flex items-center">☀️ Buổi sáng (Tiết 1-5)
                                        </h5>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                            @foreach($morning as $detail)
                                                <div class="bg-blue-50 border border-blue-200 rounded p-2 text-sm flex flex-col">
                                                    <span class="font-medium text-blue-800">Tiết {{ $detail->period }}:
                                                        {{ $detail->subject->name ?? 'Chưa có môn' }}({{  $detail->lesson_type_label ?? 'Chưa xác định' }})</span>
                                                    <span class="text-gray-600">GV: {{ $detail->instructor->name ?? 'N/A' }}</span>
                                                    <span class="text-gray-600">Phòng: {{ $detail->classroom->name ?? 'N/A' }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Buổi chiều --}}
                                @if($afternoon->isNotEmpty())
                                    <div class="mb-4">
                                        <h5 class="font-semibold text-sm text-gray-600 mb-2 flex items-center">🌅 Buổi chiều (Tiết 6-9)
                                        </h5>
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                                            @foreach($afternoon as $detail)
                                                <div class="bg-orange-50 border border-orange-200 rounded p-2 text-sm flex flex-col">
                                                    <span class="font-medium text-orange-800">Tiết {{ $detail->period }}:
                                                        {{ $detail->subject->name ?? 'Chưa có môn' }}({{  $detail->lesson_type_label ?? 'Chưa xác định' }})</span>
                                                    <span class="text-gray-600">GV: {{ $detail->instructor->name ?? 'N/A' }}</span>
                                                    <span class="text-gray-600">Phòng: {{ $detail->classroom->name ?? 'N/A' }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                                {{-- Không có lịch --}}
                                @if($day['details']->isEmpty())
                                    <p class="text-sm text-gray-500 text-center py-4 italic">Chưa có lịch học trong ngày này.</p>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            <script>
                function toggleWeek(id) {
                    const el = document.getElementById('week-' + id);
                    el.classList.toggle('hidden');
                    el.style.transition = 'all 0.3s ease';
                }
            </script>



        </div>

        {{-- Right Column - Sidebar Info --}}
        <div class="space-y-6">
            {{-- Quick Stats --}}
            {{-- <div class="bg-white rounded-lg shadow-sm border">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">THỐNG KÊ NHANH</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Tổng số tuần:</span>
                        <span class="text-sm font-medium text-gray-900">{{ $trainingSchedule->duration_text}}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-sm text-gray-600">Giảng viên tham gia:</span>
                        <span class="text-sm font-medium text-gray-900">{{ $trainingSchedule->instructors->count() }}</span>
                    </div>
                    @if($trainingSchedule->is_currently_active)
                        <div class="pt-3 border-t border-gray-200">
                            <span
                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                <i class="bi bi-clock mr-1"></i>Đang diễn ra
                            </span>
                        </div>
                    @endif
                </div>
            </div> --}}

            {{-- Instructors List --}}
            @if($trainingSchedule->instructors->count() > 0)
                <div class="bg-white rounded-lg shadow-sm border">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">GIẢNG VIÊN THAM GIA</h3>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            @foreach($trainingSchedule->instructors->unique('id') as $instructor)
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                        <span class="text-white text-sm font-medium">
                                            {{ strtoupper(substr($instructor->name, 0, 1)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">{{ $instructor->name }}</p>
                                        <p class="text-xs text-gray-500">{{ $instructor->email }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
            
            {{-- Quick Actions --}}
            <div class="bg-white rounded-lg shadow-sm border">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">THAO TÁC NHANH</h3>
                </div>
                <div class="p-6 space-y-3">
                    @if(!empty($canManageSkeleton))
                    <a href="{{ route('training-schedules.edit', $trainingSchedule) }}"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium text-center block">
                        <i class="bi bi-pencil mr-2"></i>Chỉnh sửa
                    </a>
                    @endif
                    <a href="{{ route('training-schedules.calendar') }}?specialization_id={{ $trainingSchedule->specialization_id }}&academic_year={{ $trainingSchedule->academic_year }}&semester={{ $trainingSchedule->semester }}"
                        class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium text-center block">
                        <i class="bi bi-calendar3 mr-2"></i>Xem trong lịch
                    </a>
                    <form action="{{ route('training-schedules.destroy', $trainingSchedule) }}" method="POST"
                        data-confirm='Bạn có chắc muốn xóa lịch đào tạo này? Hành động này không thể hoàn tác.'>
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="w-full bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium">
                            <i class="bi bi-trash mr-2"></i>Xóa lịch
                        </button>
                    </form>
                </div>
            </div>

            {{-- Metadata --}}
            <div class="bg-white rounded-lg shadow-sm border">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">THÔNG TIN HỆ THỐNG</h3>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Người tạo</label>
                        <p class="text-sm text-gray-900">{{ $trainingSchedule->creator->name ?? 'N/A' }}</p>
                        <p class="text-xs text-gray-500">{{ $trainingSchedule->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    @if($trainingSchedule->updater && $trainingSchedule->updated_at != $trainingSchedule->created_at)
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Người cập nhật</label>
                            <p class="text-sm text-gray-900">{{ $trainingSchedule->updater->name ?? 'N/A' }}</p>
                            <p class="text-xs text-gray-500">{{ $trainingSchedule->updated_at->format('d/m/Y H:i') }}</p>
                        </div>
                    @endif
                </div>
            </div>

            
        </div>
    </div>
@endsection

@push('styles')
    <style>
        @media print {
            .no-print {
                display: none !important;
            }

            .print-full-width {
                width: 100% !important;
                max-width: none !important;
            }
        }
    </style>
@endpush
