@php
    $account = $account_stats;
    $typeItems = [
        ['key' => 'theory', 'label' => 'Lý thuyết', 'value' => $account['theory_lessons'], 'icon' => 'bi-journal-text'],
        ['key' => 'practice', 'label' => 'Thực hành', 'value' => $account['practice_lessons'], 'icon' => 'bi-tools'],
        ['key' => 'self', 'label' => 'Tự học', 'value' => $account['self_study_lessons'], 'icon' => 'bi-person-workspace'],
        ['key' => 'exam', 'label' => 'Thi/Kiểm tra', 'value' => $account['exam_lessons'], 'icon' => 'bi-clipboard2-check'],
    ];
@endphp

<section class="dash-account-section" aria-labelledby="dashboard-account-heading">
    <div class="dash-section-heading">
        <div>
            <span>Thống kê tự động</span>
            <h2 id="dashboard-account-heading">
                {{ $dashboard_scope['is_global'] ? 'Toàn cảnh hệ thống' : 'Số liệu trong phạm vi của bạn' }}
            </h2>
            <p>{{ $account['period_label'] }} · {{ $account['scope_label'] }}</p>
        </div>
        <span class="dash-scope-badge">
            <i class="bi {{ $dashboard_scope['is_global'] ? 'bi-globe2' : ($dashboard_scope['type'] === 'instructor' ? 'bi-person-check' : 'bi-building-check') }}"></i>
            {{ $dashboard_scope['short_label'] }}
        </span>
    </div>

    <div class="dash-auto-kpis">
        <article class="dash-auto-kpi dash-auto-kpi--primary">
            <span class="dash-auto-kpi__icon"><i class="bi bi-calendar-day"></i></span>
            <div>
                <span>Tiết hôm nay</span>
                <strong>{{ $account['today_lessons'] }}</strong>
                <small>theo lịch huấn luyện</small>
            </div>
        </article>
        <article class="dash-auto-kpi">
            <span class="dash-auto-kpi__icon"><i class="bi bi-clock-history"></i></span>
            <div>
                <span>Tổng tiết trong tháng</span>
                <strong>{{ $account['total_lessons'] }}</strong>
                <small>{{ $account['teaching_days'] }} ngày có lịch</small>
            </div>
        </article>
        <article class="dash-auto-kpi">
            <span class="dash-auto-kpi__icon"><i class="bi bi-people"></i></span>
            <div>
                <span>Lớp phụ trách</span>
                <strong>{{ $account['classes_count'] }}</strong>
                <small>{{ $account['subjects_count'] }} môn học</small>
            </div>
        </article>
        <article class="dash-auto-kpi">
            <span class="dash-auto-kpi__icon"><i class="bi bi-door-open"></i></span>
            <div>
                <span>Phòng sử dụng</span>
                <strong>{{ $account['rooms_count'] }}</strong>
                <small>
                    {{ $dashboard_scope['type'] === 'instructor'
                        ? 'trong lịch cá nhân'
                        : $account['instructors_count'].' giảng viên' }}
                </small>
            </div>
        </article>
    </div>

    <div class="dash-account-grid">
        <div class="dash-account-main">
            <div class="dash-type-strip">
                @foreach($typeItems as $item)
                    <div class="dash-type-chip dash-type-chip--{{ $item['key'] }}">
                        <i class="bi {{ $item['icon'] }}"></i>
                        <span>{{ $item['label'] }}</span>
                        <strong>{{ $item['value'] }}</strong>
                        <small>tiết</small>
                    </div>
                @endforeach
            </div>

            <div class="dash-chart-grid">
                <article class="dash-chart-card">
                    <div class="dash-card-heading">
                        <div><span>Cơ cấu tiết</span><strong>Phân loại trong tháng</strong></div>
                        <i class="bi bi-pie-chart"></i>
                    </div>
                    <div class="dash-chart-frame dash-chart-frame--compact">
                        <canvas id="dashboardAccountTypeChart"></canvas>
                    </div>
                </article>
                <article class="dash-chart-card">
                    <div class="dash-card-heading">
                        <div><span>Nhịp độ lịch</span><strong>Số tiết theo ngày</strong></div>
                        <i class="bi bi-graph-up"></i>
                    </div>
                    <div class="dash-chart-frame dash-chart-frame--compact">
                        <canvas id="dashboardAccountDailyChart"></canvas>
                    </div>
                </article>
            </div>
        </div>

        <aside class="dash-upcoming-card">
            <div class="dash-card-heading">
                <div><span>07 ngày tới</span><strong>Lịch sắp diễn ra</strong></div>
                @if(Route::has('instructor-schedule.index') && auth()->user()->isInstructor())
                    <a href="{{ route('instructor-schedule.index') }}">Xem lịch</a>
                @endif
            </div>

            <div class="dash-upcoming-list">
                @forelse($account['upcoming'] as $row)
                    <div class="dash-upcoming-row">
                        <div class="dash-upcoming-date">
                            <strong>{{ \Carbon\Carbon::parse($row['date'])->format('d') }}</strong>
                            <span>Th{{ \Carbon\Carbon::parse($row['date'])->format('m') }}</span>
                        </div>
                        <div class="dash-upcoming-copy">
                            <strong>{{ $row['subject'] }}</strong>
                            <span>Tiết {{ $row['period'] }} · {{ $row['class'] }} · {{ $row['room'] }}</span>
                            @if($dashboard_scope['type'] !== 'instructor')
                                <small><i class="bi bi-person"></i> {{ $row['instructor'] }}</small>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="dash-upcoming-empty">
                        <i class="bi bi-calendar2-check"></i>
                        <strong>Chưa có lịch sắp tới</strong>
                        <span>Không có tiết nào trong 07 ngày tới.</span>
                    </div>
                @endforelse
            </div>
        </aside>
    </div>
</section>

@push('scripts')
<script>
(function () {
    function bootAccountCharts() {
        if (typeof Chart === 'undefined') return;

        const typeCanvas = document.getElementById('dashboardAccountTypeChart');
        const dailyCanvas = document.getElementById('dashboardAccountDailyChart');
        if (!typeCanvas || !dailyCanvas) return;

        Chart.getChart?.(typeCanvas)?.destroy();
        Chart.getChart?.(dailyCanvas)?.destroy();

        const typeChart = @json($account['type_chart']);
        const dailyChart = @json($account['daily_chart']);

        new Chart(typeCanvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: typeChart.labels,
                datasets: [{
                    data: typeChart.data,
                    backgroundColor: ['#3b82f6', '#10b981', '#8b5cf6', '#f43f5e'],
                    borderWidth: 0,
                    hoverOffset: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '68%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { usePointStyle: true, boxWidth: 8, padding: 12, font: { size: 10 } },
                    },
                },
            },
        });

        new Chart(dailyCanvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: dailyChart.labels,
                datasets: [{
                    label: 'Số tiết',
                    data: dailyChart.data,
                    borderColor: '#4ea1ff',
                    backgroundColor: 'rgba(78, 161, 255, .13)',
                    pointBackgroundColor: '#2563eb',
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    borderWidth: 2,
                    fill: true,
                    tension: .35,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { grid: { display: false }, ticks: { font: { size: 10 } } },
                    y: { beginAtZero: true, ticks: { precision: 0, font: { size: 10 } } },
                },
                plugins: { legend: { display: false } },
            },
        });
    }

    if (!window.__dashboardAccountChartsBound) {
        window.__dashboardAccountChartsBound = true;
        document.addEventListener('turbo:load', bootAccountCharts);
    }
    bootAccountCharts();
})();
</script>
@endpush
