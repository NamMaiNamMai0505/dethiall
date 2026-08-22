@extends('layouts.admin')

@section('title', 'Giờ chuẩn giảng viên')
@section('page-title', 'Giờ chuẩn giảng viên')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Giờ chuẩn GV']
]" />

<x-page-header title="GIỜ CHUẨN GIẢNG VIÊN" :actions="[
    [
        'url' => route('standard-hours.guide'),
        'label' => 'Hướng dẫn sử dụng', 'icon' => 'book', 'color' => 'blue'
    ],
    [
        'url' => url()->previous() !== url()->current() ? url()->previous() : route('dashboard'),
        'label' => 'Quay lại', 'icon' => 'arrow-left', 'color' => 'gray'
    ]
]" />

@if(!empty($setupWarning ?? null))
    <div class="mb-6 rounded-lg border border-amber-300 bg-amber-50 text-amber-900 px-4 py-3 text-sm">
        <i class="bi bi-exclamation-triangle mr-1"></i>{{ $setupWarning }}
    </div>
@endif

<div class="grid grid-cols-1 gap-4 mb-8 md:grid-cols-2 xl:grid-cols-4">
    <div class="bg-white rounded-lg shadow-sm border p-5 border-l-4 border-blue-500">
        <p class="text-sm text-gray-600">Kê khai HĐ chuyên môn</p>
        <p class="text-2xl font-bold text-blue-700 mt-1">{{ $stats['conversion']['approved'] }} <span class="text-sm font-normal text-gray-500">đã thẩm định</span></p>
        <p class="text-sm text-gray-500 mt-2">{{ number_format($stats['conversion']['total_hours'], 0) }} giờ quy đổi</p>
        <p class="text-xs text-gray-400 mt-1">Tổng: {{ $stats['conversion']['total'] }} · Chờ duyệt: {{ $stats['conversion']['submitted'] }}</p>
    </div>
    @unless($isResearchRestricted ?? false)
    <div class="bg-white rounded-lg shadow-sm border p-5 border-l-4 border-indigo-500">
        <p class="text-sm text-gray-600">Kê khai NCKH</p>
        <p class="text-2xl font-bold text-indigo-700 mt-1">{{ $stats['research']['approved'] }} <span class="text-sm font-normal text-gray-500">đã thẩm định</span></p>
        <p class="text-sm text-gray-500 mt-2">{{ number_format($stats['research']['total_hours'], 0) }} giờ quy đổi</p>
        <p class="text-xs text-gray-400 mt-1">Tổng: {{ $stats['research']['total'] }} · Chờ duyệt: {{ $stats['research']['submitted'] }}</p>
    </div>
    @endunless
    <div class="bg-white rounded-lg shadow-sm border p-5 border-l-4 border-amber-500">
        <p class="text-sm text-gray-600">Hoạt động ngoài HĐCM</p>
        <p class="text-2xl font-bold text-amber-700 mt-1">{{ $stats['external']['approved'] }} <span class="text-sm font-normal text-gray-500">đã duyệt</span></p>
        <p class="text-sm text-gray-500 mt-2">Theo dõi riêng · không cộng giờ chuẩn</p>
        <p class="text-xs text-gray-400 mt-1">Tổng: {{ $stats['external']['total'] }} · Chờ duyệt: {{ $stats['external']['submitted'] }}</p>
    </div>
    @unless($isInstructorOnly)
    <div class="bg-white rounded-lg shadow-sm border p-5 border-l-4 border-green-500">
        <p class="text-sm text-gray-600">Đã tính giờ chuẩn</p>
        <p class="text-2xl font-bold text-green-700 mt-1">{{ $stats['calculated']['total'] }} <span class="text-sm font-normal text-gray-500">bản ghi</span></p>
        <p class="text-sm text-gray-500 mt-2">{{ $stats['calculated']['passed'] }} đạt định mức</p>
    </div>
    @else
    <div class="bg-white rounded-lg shadow-sm border p-5 border-l-4 border-green-500">
        <p class="text-sm text-gray-600">Kết quả của tôi</p>
        <p class="text-lg font-semibold text-green-700 mt-2">Xem kết quả giờ chuẩn đã tính</p>
        <a href="{{ route('standard-hours.my-results.index') }}" class="inline-flex mt-3 text-sm text-blue-600 hover:underline">Đến Kê khai giờ chuẩn →</a>
    </div>
    @endunless
</div>

<div class="mb-8">
    <h2 class="text-lg font-semibold text-gray-900 mb-1">Thống kê tổng hợp</h2>
    <p class="text-sm text-gray-500 mb-4">Dữ liệu từ kê khai đã duyệt{{ $chartData['year'] ? ' · '.app(\Modules\StandardHours\Services\PeriodService::class)->modeLabel().' '.app(\Modules\StandardHours\Services\PeriodService::class)->label($chartData['year']).' (top GV)' : '' }}</p>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-6">
        @unless($isInstructorOnly)
        <div class="bg-white rounded-xl shadow-sm border p-5 xl:col-span-2">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-semibold text-gray-900">Top 10 giảng viên — giờ chuẩn cao nhất</h3>
                    <p class="text-xs text-gray-500 mt-0.5">
                        @if(($chartData['top_instructors']['source'] ?? '') === 'yearly')
                            Theo kết quả tính giờ {{ mb_strtolower(app(\Modules\StandardHours\Services\PeriodService::class)->modeLabel()) }} {{ app(\Modules\StandardHours\Services\PeriodService::class)->label($chartData['year']) }}
                        @else
                            Theo tổng giờ quy đổi kê khai đã duyệt
                        @endif
                    </p>
                </div>
                <i class="bi bi-bar-chart-line text-2xl text-blue-500"></i>
            </div>
            @if(count($chartData['top_instructors']['labels'] ?? []) > 0)
                <div class="relative h-72 md:h-80">
                    <canvas id="topInstructorsChart"></canvas>
                </div>
            @else
                <div class="h-48 flex items-center justify-center text-gray-400 text-sm">
                    <i class="bi bi-bar-chart mr-2"></i> Chưa có dữ liệu giờ chuẩn để thống kê
                </div>
            @endif
        </div>
        @endunless

        <div class="bg-white rounded-xl shadow-sm border p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-semibold text-gray-900">Phân bổ HĐ chuyên môn</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Theo danh mục hoạt động (giờ quy đổi)</p>
                </div>
                <i class="bi bi-pie-chart text-2xl text-emerald-500"></i>
            </div>
            @if(count($chartData['conversion_by_category']['labels'] ?? []) > 0)
                <div class="relative h-64 flex items-center justify-center">
                    <canvas id="conversionPieChart"></canvas>
                </div>
            @else
                <div class="h-48 flex items-center justify-center text-gray-400 text-sm">
                    <i class="bi bi-pie-chart mr-2"></i> Chưa có kê khai HĐ CM đã duyệt
                </div>
            @endif
        </div>

        @unless($isResearchRestricted ?? false)
        <div class="bg-white rounded-xl shadow-sm border p-5">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-semibold text-gray-900">Phân bổ NCKH</h3>
                    <p class="text-xs text-gray-500 mt-0.5">Theo danh mục nghiên cứu (giờ quy đổi)</p>
                </div>
                <i class="bi bi-pie-chart-fill text-2xl text-indigo-500"></i>
            </div>
            @if(count($chartData['research_by_category']['labels'] ?? []) > 0)
                <div class="relative h-64 flex items-center justify-center">
                    <canvas id="researchPieChart"></canvas>
                </div>
            @else
                <div class="h-48 flex items-center justify-center text-gray-400 text-sm">
                    <i class="bi bi-pie-chart mr-2"></i> Chưa có kê khai NCKH đã duyệt
                </div>
            @endif
        </div>
        @endunless
    </div>
</div>

<div class="mb-4">
    <h2 class="text-lg font-semibold text-gray-900">Chức năng</h2>
    <p class="text-sm text-gray-500">Chọn tính năng cần thao tác</p>
</div>

@php
    $canSh = function (string $perm) {
        if (\App\Support\PermissionCheck::userCan($perm)) {
            return true;
        }
        // Manager: vẫn thấy menu SH nếu có quyền index
        $user = auth()->user();

        return $user
            && $perm === 'standard-hours.view'
            && method_exists($user, 'isManagementActor')
            && $user->isManagementActor()
            && (\App\Support\PermissionCheck::userCan('standard-hours.view')
                || \App\Support\PermissionCheck::userCan('standard-hours.index'));
    };

    $menuItems = [];
    if (!$isInstructorOnly) {
        $menuItems = array_merge($menuItems, [
            ['route' => 'standard-hours.object-types.index', 'icon' => 'bi-tags', 'label' => 'Đối tượng (định mức GC + NCKH)', 'perm' => 'standard-hours.object-types.view', 'iconBg' => 'bg-blue-100 text-blue-700'],
            ['route' => 'standard-hours.positions.index', 'icon' => 'bi-person-workspace', 'label' => 'Chức danh (tỉ lệ %)', 'perm' => 'standard-hours.positions.view', 'iconBg' => 'bg-gray-100 text-gray-700'],
            ['route' => 'standard-hours.departments.index', 'icon' => 'bi-diagram-3', 'label' => 'Bộ môn (khoa)', 'perm' => 'standard-hours.departments.view', 'iconBg' => 'bg-slate-100 text-slate-700'],
            ['route' => 'standard-hours.department-overtime.index', 'icon' => 'bi-people', 'label' => 'Vượt DM bộ môn (Đ.17)', 'perm' => 'standard-hours.department-overtime.view', 'iconBg' => 'bg-amber-100 text-amber-800'],
            ['route' => 'standard-hours.norm-reductions.index', 'icon' => 'bi-calendar-minus', 'label' => 'Giảm trừ định mức (Đ.11.3)', 'perm' => 'standard-hours.norm-reductions.view', 'iconBg' => 'bg-rose-100 text-rose-700'],
            ['route' => 'standard-hours.conversion-categories.index', 'icon' => 'bi-list-check', 'label' => 'HĐ chuyên môn', 'perm' => 'standard-hours.conversion-categories.view', 'iconBg' => 'bg-teal-100 text-teal-700'],
            ['route' => 'standard-hours.research-categories.index', 'icon' => 'bi-book', 'label' => 'Danh mục NCKH', 'perm' => 'standard-hours.research-categories.view', 'iconBg' => 'bg-purple-100 text-purple-700'],
            ['route' => 'standard-hours.calculations.index', 'icon' => 'bi-calculator', 'label' => 'Tính giờ chuẩn', 'perm' => 'standard-hours.calculations.view', 'iconBg' => 'bg-orange-100 text-orange-700'],
            ['route' => 'standard-hours.reports.index', 'icon' => 'bi-file-bar-graph', 'label' => 'Báo cáo thống kê', 'perm' => 'standard-hours.reports.view', 'iconBg' => 'bg-green-100 text-green-700'],
        ]);
        if ($canSh('standard-hours.override-approved')) {
            $menuItems[] = ['route' => 'standard-hours.settings.period-mode', 'icon' => 'bi-calendar2-range', 'label' => 'Kỳ tính Năm / Năm học', 'perm' => 'standard-hours.override-approved', 'iconBg' => 'bg-cyan-100 text-cyan-800'];
            $menuItems[] = ['route' => 'standard-hours.settings.research-rules', 'icon' => 'bi-sliders', 'label' => 'Luật quy đổi NCKH', 'perm' => 'standard-hours.override-approved', 'iconBg' => 'bg-red-100 text-red-700'];
        }
    }
    $menuItems = array_merge($menuItems, [
        ['route' => 'standard-hours.conversion-records.index', 'icon' => 'bi-clipboard-check', 'label' => 'Kê khai HĐ CM', 'perm' => 'standard-hours.conversion-records.view', 'iconBg' => 'bg-blue-100 text-blue-700'],
        ['route' => 'standard-hours.research-records.index', 'icon' => 'bi-mortarboard', 'label' => 'Kê khai NCKH', 'perm' => 'standard-hours.research-records.view', 'iconBg' => 'bg-indigo-100 text-indigo-700'],
        ['route' => 'standard-hours.external-activities.index', 'icon' => 'bi-activity', 'label' => 'Hoạt động ngoài HĐCM', 'perm' => 'standard-hours.external-activities.view', 'iconBg' => 'bg-amber-100 text-amber-800'],
    ]);
    if (!$isInstructorOnly) {
        $menuItems[] = ['route' => 'standard-hours.hour-exchanges.index', 'icon' => 'bi-arrow-left-right', 'label' => 'Quyết định bù giờ', 'perm' => 'standard-hours.hour-exchanges.view', 'iconBg' => 'bg-amber-100 text-amber-800'];
    }
    $menuItems[] = [
        'route' => 'standard-hours.my-results.index',
        'icon' => 'bi-award',
        'label' => $isInstructorOnly ? 'Kê khai giờ chuẩn' : 'Hồ sơ kê khai giờ chuẩn',
        'perm' => 'standard-hours.view',
        'iconBg' => 'bg-green-100 text-green-700',
    ];
    $menuItems[] = ['route' => 'standard-hours.guide', 'icon' => 'bi-journal-check', 'label' => 'Hướng dẫn sử dụng', 'perm' => 'standard-hours.view', 'iconBg' => 'bg-cyan-100 text-cyan-700'];

    if ($isResearchRestricted ?? false) {
        $menuItems = array_values(array_filter(
            $menuItems,
            fn (array $item) => !in_array($item['route'], [
                'standard-hours.research-categories.index',
                'standard-hours.research-records.index',
                'standard-hours.settings.research-rules',
            ], true)
        ));
    }
@endphp

<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
    @foreach($menuItems as $item)
        @if($canSh($item['perm']))
        <a href="{{ route($item['route']) }}"
           class="group bg-white rounded-xl shadow-sm border hover:shadow-md hover:border-blue-200 transition p-5 flex flex-col">
            <div class="w-10 h-10 rounded-lg {{ $item['iconBg'] }} flex items-center justify-center mb-3">
                <i class="{{ $item['icon'] }} text-xl"></i>
            </div>
            <span class="font-semibold text-gray-900 group-hover:text-blue-700">{{ $item['label'] }}</span>
            <span class="text-xs text-gray-400 mt-2">Nhấn để mở</span>
        </a>
        @endif
    @endforeach
</div>

@include('partials.chart-js')

@push('scripts')
<script>
(function () {
    const CANVAS_IDS = ['topInstructorsChart', 'conversionPieChart', 'researchPieChart'];

    function destroyHubCharts() {
        if (typeof Chart === 'undefined' || !Chart.getChart) return;
        CANVAS_IDS.forEach(function (id) {
            const el = document.getElementById(id);
            if (!el) return;
            const existing = Chart.getChart(el);
            if (existing) existing.destroy();
        });
    }

    function bootHubCharts(attempt) {
        // Turbo back về hub: canvas mới + Chart CDN có thể chưa kịp (hiếm) → retry ngắn
        if (typeof Chart === 'undefined') {
            if ((attempt || 0) < 20) {
                setTimeout(function () { bootHubCharts((attempt || 0) + 1); }, 50);
            }
            return;
        }

        // Chỉ chạy khi đang ở hub (có ít nhất 1 canvas)
        if (!document.getElementById('topInstructorsChart')
            && !document.getElementById('conversionPieChart')
            && !document.getElementById('researchPieChart')) {
            return;
        }

        // Xóa instance cũ trước khi vẽ lại (Turbo Drive giữ global Chart)
        destroyHubCharts();

        const chartData = @json($chartData);

        const pieOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { padding: 12, font: { size: 11 }, boxWidth: 12 } },
                tooltip: {
                    callbacks: {
                        label: (ctx) => {
                            const total = ctx.dataset.data.reduce((a, b) => a + b, 0);
                            const pct = total ? ((ctx.raw / total) * 100).toFixed(1) : 0;
                            return ` ${ctx.label}: ${ctx.raw} giờ (${pct}%)`;
                        }
                    }
                }
            }
        };

        const topEl = document.getElementById('topInstructorsChart');
        if (topEl && chartData.top_instructors?.labels?.length) {
            new Chart(topEl.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: chartData.top_instructors.labels,
                    datasets: [{
                        label: 'Tổng giờ chuẩn',
                        data: chartData.top_instructors.hours,
                        backgroundColor: '#3b82f6',
                        borderRadius: 6,
                        maxBarThickness: 48,
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            callbacks: {
                                label: (ctx) => ` ${ctx.raw} giờ`
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            title: { display: true, text: 'Giờ chuẩn' },
                            ticks: { precision: 0 }
                        },
                        y: { ticks: { font: { size: 11 } } }
                    }
                }
            });
        }

        const conversionEl = document.getElementById('conversionPieChart');
        if (conversionEl && chartData.conversion_by_category?.labels?.length) {
            new Chart(conversionEl.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: chartData.conversion_by_category.labels,
                    datasets: [{
                        data: chartData.conversion_by_category.hours,
                        backgroundColor: chartData.conversion_by_category.colors,
                        borderWidth: 2,
                        borderColor: '#fff',
                    }]
                },
                options: pieOptions,
            });
        }

        const researchEl = document.getElementById('researchPieChart');
        if (researchEl && chartData.research_by_category?.labels?.length) {
            new Chart(researchEl.getContext('2d'), {
                type: 'pie',
                data: {
                    labels: chartData.research_by_category.labels,
                    datasets: [{
                        data: chartData.research_by_category.hours,
                        backgroundColor: chartData.research_by_category.colors,
                        borderWidth: 2,
                        borderColor: '#fff',
                    }]
                },
                options: pieOptions,
            });
        }

        // Layout Turbo: resize sau 1 frame để canvas không bị 0×0
        requestAnimationFrame(function () {
            CANVAS_IDS.forEach(function (id) {
                const el = document.getElementById(id);
                if (!el || !Chart.getChart) return;
                const ch = Chart.getChart(el);
                if (ch) ch.resize();
            });
        });
    }

    // Tránh gắn listener trùng khi Turbo re-eval script
    if (!window.__standardHoursHubChartsBound) {
        window.__standardHoursHubChartsBound = true;
        document.addEventListener('turbo:load', function () { bootHubCharts(0); });
        document.addEventListener('turbo:before-cache', destroyHubCharts);
        document.addEventListener('DOMContentLoaded', function () { bootHubCharts(0); });
    }
    // Lần đầu eval script (cả full load lẫn Turbo visit)
    bootHubCharts(0);
})();
</script>
@endpush
@endsection
