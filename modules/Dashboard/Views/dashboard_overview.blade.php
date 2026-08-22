{{-- Modules/Dashboard/Views/dashboard_overview.blade.php --}}
<div class="dashboard-stat-view dashboard-stat-view--overview space-y-6">
    {{-- Header --}}
    <div class="dashboard-stat-banner bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
                    <i class="bi bi-calendar-day text-blue-600"></i>
                    Tổng quan hôm nay · {{ $dashboard_scope['short_label'] }}
                </h2>
                <p class="text-sm text-gray-500 mt-1">
                    {{ now()->format('d/m/Y') }} · {{ now()->locale('vi')->dayName }}
                    @if(!$dashboard_scope['is_global'])
                        · số liệu đã giới hạn theo tài khoản
                    @endif
                </p>
            </div>
            <div class="text-left sm:text-right">
                <p class="text-xs uppercase tracking-wide text-gray-400">Cập nhật lúc</p>
                <p class="text-lg font-semibold text-gray-800">{{ now('Asia/Ho_Chi_Minh')->format('H:i') }}</p>
            </div>
        </div>
    </div>

    {{-- Overview Cards --}}
    <div class="dashboard-stat-kpi-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="dashboard-stat-kpi bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-gray-500">Lớp học hôm nay</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $overview['class_count'] }}</p>
                </div>
                <div class="rounded-full bg-blue-100 p-3">
                    <i class="bi bi-grid-3x3-gap text-xl text-blue-600"></i>
                </div>
            </div>
        </div>

        <div class="dashboard-stat-kpi bg-white rounded-xl shadow-sm p-5 border-l-4 border-green-500">
            <p class="text-sm font-medium text-gray-500 mb-2">Môn học hôm nay</p>
            <div class="space-y-1.5 text-sm">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">📘 Lý thuyết</span>
                    <span class="font-semibold text-gray-900">{{ $overview['subject_theory'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">🔬 Thực hành</span>
                    <span class="font-semibold text-gray-900">{{ $overview['subject_practice'] }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600">📝 Tự học</span>
                    <span class="font-semibold text-gray-900">{{ $overview['subject_self'] }}</span>
                </div>
            </div>
        </div>

        <div class="dashboard-stat-kpi bg-white rounded-xl shadow-sm p-5 border-l-4 border-purple-500">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-gray-500">Giảng viên</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $overview['instructor_count'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">Đang giảng dạy</p>
                </div>
                <div class="rounded-full bg-purple-100 p-3">
                    <i class="bi bi-person-badge text-xl text-purple-600"></i>
                </div>
            </div>
        </div>

        <div class="dashboard-stat-kpi bg-white rounded-xl shadow-sm p-5 border-l-4 border-orange-500">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-medium text-gray-500">Phòng học</p>
                    <p class="text-3xl font-bold text-gray-900 mt-1">{{ $overview['room_count'] }}</p>
                    <p class="text-xs text-gray-500 mt-1">Đang sử dụng</p>
                </div>
                <div class="rounded-full bg-orange-100 p-3">
                    <i class="bi bi-door-open text-xl text-orange-600"></i>
                </div>
            </div>
        </div>
    </div>

    {{-- Charts --}}
    <div class="dashboard-stat-chart-grid grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="dashboard-stat-chart-card bg-white rounded-xl shadow-sm p-5">
            <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="bi bi-pie-chart text-blue-600"></i>
                Cơ cấu tiết học hôm nay
            </h3>
            <div class="relative h-[280px]">
                <canvas id="pieChartOverview"></canvas>
            </div>
        </div>
        <div class="dashboard-stat-chart-card bg-white rounded-xl shadow-sm p-5">
            <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="bi bi-bar-chart text-green-600"></i>
                Thống kê theo loại tiết
            </h3>
            <div class="relative h-[280px]">
                <canvas id="barChartOverview"></canvas>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="dashboard-stat-table bg-white rounded-xl shadow-sm overflow-hidden">
        <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between gap-3">
            <h3 class="text-base font-semibold text-gray-800 flex items-center gap-2">
                <i class="bi bi-table text-indigo-600"></i>
                Danh sách chi tiết hôm nay
            </h3>
            <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-blue-50 text-blue-700">
                {{ count($overview['table_rows']) }} buổi học
            </span>
        </div>
        <div class="overflow-x-auto">
            @if(count($overview['table_rows']) > 0)
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">STT</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Lớp</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Môn học</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Giảng viên</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Phòng</th>
                            <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Buổi</th>
                            <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Tiết</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($overview['table_rows'] as $index => $row)
                            <tr class="hover:bg-blue-50/40 transition-colors">
                                <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-500">{{ $index + 1 }}</td>
                                <td class="px-5 py-3 whitespace-nowrap">
                                    <span class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                        {{ $row['class'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-900">{{ $row['subject'] }}</td>
                                <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-900">{{ $row['instructor'] }}</td>
                                <td class="px-5 py-3 whitespace-nowrap">
                                    <span class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                        {{ $row['room'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap text-center">
                                    <span @class([
                                        'px-2 py-0.5 inline-flex text-xs font-semibold rounded-full',
                                        'bg-amber-100 text-amber-800' => $row['session'] === 'Sáng',
                                        'bg-blue-100 text-blue-800' => $row['session'] === 'Chiều',
                                        'bg-purple-100 text-purple-800' => ! in_array($row['session'], ['Sáng', 'Chiều'], true),
                                    ])>
                                        {{ $row['session'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-3 whitespace-nowrap text-center text-sm font-medium text-gray-900">
                                    {{ $row['periods'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <div class="dashboard-stat-empty p-12 text-center">
                    <i class="bi bi-calendar-x text-5xl text-gray-300"></i>
                    <p class="mt-3 text-gray-500">Không có buổi học nào hôm nay</p>
                </div>
            @endif
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    function bootOverviewCharts() {
        if (typeof Chart === 'undefined') return;
        if (window.__dashboardOverviewChartsReady) return;
        window.__dashboardOverviewChartsReady = true;

        const pieData = @json($overview['pie_chart_data']);
        const pieCanvas = document.getElementById('pieChartOverview');
        if (pieCanvas) {
            new Chart(pieCanvas.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: pieData.labels,
                    datasets: [{
                        data: pieData.data,
                        backgroundColor: ['#3b82f6', '#10b981', '#f59e0b', '#8b5cf6'],
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { padding: 12, font: { size: 12 } },
                        },
                    },
                },
            });
        }

        const barData = @json($overview['bar_chart_data']);
        const barCanvas = document.getElementById('barChartOverview');
        if (barCanvas) {
            new Chart(barCanvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: barData.labels,
                    datasets: [
                        { label: 'Số lớp', data: barData.classes, backgroundColor: '#3b82f6', borderRadius: 4 },
                        { label: 'Số môn', data: barData.subjects, backgroundColor: '#10b981', borderRadius: 4 },
                        { label: 'Số GV', data: barData.instructors, backgroundColor: '#8b5cf6', borderRadius: 4 },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 } },
                        x: { ticks: { maxRotation: 0, minRotation: 0 } },
                    },
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { padding: 12, font: { size: 12 } },
                        },
                    },
                },
            });
        }
    }

    function boot() {
        window.__dashboardOverviewChartsReady = false;
        bootOverviewCharts();
    }

    if (!window.__dashboardOverviewBoot) {
        window.__dashboardOverviewBoot = true;
        if (window.Turbo) {
            document.addEventListener('turbo:load', boot);
        } else {
            document.addEventListener('DOMContentLoaded', boot);
        }
    }
    if (document.readyState !== 'loading') {
        boot();
    }
})();
</script>
@endpush
