@php
    $kpis = $calendarOverview['kpis'] ?? [];
    $charts = $calendarOverview['charts'] ?? [];
    $meta = $calendarOverview['meta'] ?? [];
    $fmt = fn ($n) => number_format((int) $n, 0, ',', '.');
@endphp

<div class="space-y-6">
    <div class="glass-panel rounded-xl p-5 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                <i class="bi bi-bar-chart-line text-[#4ea1ff]"></i>
                Tổng quan đào tạo
            </h2>
            <p class="text-sm text-gray-500 mt-1">
                Tuần {{ $meta['week_range'] ?? '' }} · Cập nhật {{ $meta['generated_at'] ?? '' }}
            </p>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <div class="glass-panel rounded-xl p-5 border-l-4 border-[#4ea1ff]">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Tổng số khóa học</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ $fmt($kpis['total_courses'] ?? 0) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Lịch đào tạo đang hoạt động</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-blue-50 flex items-center justify-center text-xl">📚</div>
            </div>
        </div>

        <div class="glass-panel rounded-xl p-5 border-l-4 border-indigo-400">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Tổng học viên</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ $fmt($kpis['total_students'] ?? 0) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Học viên trong hệ thống</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-indigo-50 flex items-center justify-center text-xl">👨‍🎓</div>
            </div>
        </div>

        <div class="glass-panel rounded-xl p-5 border-l-4 border-sky-400">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Lớp đang diễn ra</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ $fmt($kpis['active_classes'] ?? 0) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Đang trong thời gian học</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-sky-50 flex items-center justify-center text-xl">🏫</div>
            </div>
        </div>

        <div class="glass-panel rounded-xl p-5 border-l-4 border-emerald-400">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Tỷ lệ hoàn thành</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ number_format($kpis['completion_rate'] ?? 0, 1, ',', '.') }}%</p>
                    <p class="text-xs text-gray-400 mt-1">Khóa đã kết thúc / có ngày kết thúc</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-emerald-50 flex items-center justify-center text-xl">✅</div>
            </div>
        </div>

        <div class="glass-panel rounded-xl p-5 border-l-4 border-amber-400">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Số buổi học tuần này</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ $fmt($kpis['sessions_this_week'] ?? 0) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Theo chi tiết lịch học</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-amber-50 flex items-center justify-center text-xl">⏰</div>
            </div>
        </div>

        <div class="glass-panel rounded-xl p-5 border-l-4 border-orange-400">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm text-gray-500 font-medium">Lớp sắp khai giảng</p>
                    <p class="text-3xl font-bold text-gray-800 mt-2">{{ $fmt($kpis['upcoming_classes'] ?? 0) }}</p>
                    <p class="text-xs text-gray-400 mt-1">Trong 7 ngày tới</p>
                </div>
                <div class="w-11 h-11 rounded-xl bg-orange-50 flex items-center justify-center text-xl">⚠️</div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="glass-panel rounded-xl p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="bi bi-pie-chart text-[#4ea1ff]"></i>
                Phân bổ trạng thái lớp
            </h3>
            <div class="relative h-72">
                <canvas id="overviewClassStatusChart"></canvas>
            </div>
        </div>

        <div class="glass-panel rounded-xl p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="bi bi-bar-chart text-[#4ea1ff]"></i>
                Buổi học theo ngày (tuần này)
            </h3>
            <div class="relative h-72">
                <canvas id="overviewSessionsWeekChart"></canvas>
            </div>
        </div>

        <div class="glass-panel rounded-xl p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="bi bi-diagram-3 text-[#4ea1ff]"></i>
                Khóa học theo ngành đào tạo
            </h3>
            <div class="relative h-72">
                <canvas id="overviewCoursesSpecChart"></canvas>
            </div>
        </div>

        <div class="glass-panel rounded-xl p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="bi bi-graph-up text-[#4ea1ff]"></i>
                Cơ cấu tiết học tuần này
            </h3>
            <div class="relative h-72">
                <canvas id="overviewLessonTypeChart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
    window.calendarOverviewChartData = @json($charts);
</script>