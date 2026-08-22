{{-- Modules/Dashboard/Views/dashboard_stat_instructor.blade.php --}}
<div class="dashboard-stat-view dashboard-stat-view--instructor space-y-6">
    {{-- Filter --}}
    <div class="dashboard-stat-filter bg-white rounded-xl shadow-sm p-5">
        <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i class="bi {{ $dashboard_scope['type'] === 'instructor' ? 'bi-person-check' : 'bi-funnel' }} text-blue-600"></i>
            {{ $dashboard_scope['type'] === 'instructor' ? 'Thống kê giảng dạy của tôi' : 'Bộ lọc thống kê' }}
        </h3>
        <form id="filterFormInstructor"
              class="space-y-4"
              @if($dashboard_scope['type'] === 'instructor') data-personal-instructor="1" @endif
              @if(!$dashboard_scope['is_global']) data-dashboard-auto-submit="1" @endif>
            @if($dashboard_scope['type'] === 'instructor')
                <input type="hidden" name="unit_id" id="unitSelect"
                       value="{{ $stat_instructor['filters']['unit_id'] }}">
                <input type="hidden" name="instructor_id" id="instructorSelect"
                       value="{{ $stat_instructor['filters']['instructor_id'] }}">

                <div class="dash-fixed-instructor">
                    <div class="dash-fixed-instructor__avatar">
                        {{ mb_strtoupper(mb_substr($stat_instructor['profile']?->name ?? auth()->user()->name, 0, 1)) }}
                    </div>
                    <div class="dash-fixed-instructor__copy">
                        <span>Giảng viên đang đăng nhập</span>
                        <strong>{{ $stat_instructor['profile']?->name ?? auth()->user()->name }}</strong>
                        <div>
                            <span><i class="bi bi-person-vcard"></i>{{ $stat_instructor['profile']?->code ?? 'Chưa có mã GV' }}</span>
                            <span><i class="bi bi-building"></i>{{ $stat_instructor['profile']?->unit?->name ?? 'Chưa gán đơn vị' }}</span>
                        </div>
                    </div>
                    <span class="dash-fixed-instructor__lock">
                        <i class="bi bi-shield-lock"></i>
                        Dữ liệu cá nhân
                    </span>
                </div>

                <div class="dash-personal-period">
                    <div>
                        <span>Khoảng thống kê</span>
                        <strong>
                            {{ \Carbon\Carbon::parse($stat_instructor['filters']['start_date'])->format('d/m/Y') }}
                            – <span data-dashboard-end-date-label>{{ \Carbon\Carbon::parse($stat_instructor['filters']['end_date'])->format('d/m/Y') }}</span>
                        </strong>
                    </div>
                    <div class="min-w-0 flex flex-col">
                        <label for="endDateInstructor" class="block text-sm font-medium text-gray-700 mb-1.5 leading-5">
                            Thống kê đến ngày
                        </label>
                        <div class="date-input-field w-full">
                            <div class="date-input-control">
                                <input type="date" name="end_date" id="endDateInstructor"
                                    value="{{ $stat_instructor['filters']['end_date'] }}"
                                    class="date-input date-input--ready w-full">
                                <i class="bi bi-calendar3 date-input-icon" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                {{-- Hàng filter: label + control cùng baseline (date picker thẳng hàng Tom Select) --}}
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 items-end">
                    <div class="min-w-0 flex flex-col">
                        <label for="unitSelect" class="block text-sm font-medium text-gray-700 mb-1.5 leading-5">
                            Đơn vị/Khoa <span class="text-red-500">*</span>
                        </label>
                        <div class="ui-select-field w-full">
                            <select name="unit_id" id="unitSelect"
                                data-placeholder="Chọn đơn vị..."
                                class="w-full" required>
                                <option value="">Chọn đơn vị...</option>
                                @foreach($stat_instructor['units'] as $unit)
                                    <option value="{{ $unit->id }}" @selected((int) $stat_instructor['filters']['unit_id'] === (int) $unit->id)>
                                        {{ $unit->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="min-w-0 flex flex-col">
                        <label for="instructorSelect" class="block text-sm font-medium text-gray-700 mb-1.5 leading-5">
                            Giảng viên
                        </label>
                        <div class="ui-select-field w-full">
                            <select name="instructor_id" id="instructorSelect"
                                data-placeholder="Tất cả giảng viên"
                                data-instructor-select
                                class="w-full">
                                <option value="">Tất cả giảng viên</option>
                                @foreach($stat_instructor['instructors'] as $instructorOption)
                                    <option value="{{ $instructorOption->id }}"
                                            @selected((int) $stat_instructor['filters']['instructor_id'] === (int) $instructorOption->id)>
                                        {{ $instructorOption->code }} - {{ $instructorOption->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="min-w-0 flex flex-col">
                        <label for="endDateInstructor" class="block text-sm font-medium text-gray-700 mb-1.5 leading-5">
                            Đến ngày
                        </label>
                        <div class="date-input-field w-full">
                            <div class="date-input-control">
                                <input type="date" name="end_date" id="endDateInstructor"
                                    value="{{ $stat_instructor['filters']['end_date'] }}"
                                    class="date-input date-input--ready w-full">
                                <i class="bi bi-calendar3 date-input-icon" aria-hidden="true"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="text-xs text-gray-500 leading-4 -mt-1">
                    @if($dashboard_scope['is_global'])
                        Chọn đơn vị trước để lọc GV · để trống GV = tất cả giảng viên
                    @else
                        Dữ liệu được tự động giới hạn trong phạm vi {{ mb_strtolower($dashboard_scope['label']) }}.
                    @endif
                </p>
            @endif

            <input type="hidden" name="start_date" id="startDateInstructor" value="{{ $stat_instructor['filters']['start_date'] }}">

            <div class="flex flex-wrap gap-2 pt-0.5">
                <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white shadow-sm">
                    <i class="bi {{ $dashboard_scope['type'] === 'instructor' ? 'bi-arrow-repeat' : 'bi-search' }}"></i>
                    {{ $dashboard_scope['type'] === 'instructor' ? 'Cập nhật thống kê' : 'Xem thống kê' }}
                </button>
                @if($dashboard_scope['is_global'])
                    <button type="button" id="resetFilterInstructor"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-200">
                        <i class="bi bi-arrow-counterclockwise"></i>
                        Đặt lại
                    </button>
                @endif
            </div>
        </form>
    </div>

    <div id="loadingInstructor" class="hidden">
        <div class="dashboard-stat-loading bg-white rounded-xl shadow-sm p-12 text-center">
            <div class="inline-block animate-spin rounded-full h-10 w-10 border-2 border-blue-200 border-t-blue-600"></div>
            <p class="mt-4 text-gray-600 text-sm">Đang tải dữ liệu...</p>
        </div>
    </div>

    <div id="emptyStateInstructor" class="dashboard-stat-empty bg-white rounded-xl shadow-sm p-12 text-center">
        <i class="bi bi-person-x text-5xl text-gray-300"></i>
        <h3 class="mt-3 text-lg font-medium text-gray-900">Chưa có dữ liệu</h3>
        <p class="mt-1 text-sm text-gray-500">
            {{ $dashboard_scope['type'] === 'instructor'
                ? 'Số liệu giảng dạy của bạn sẽ được tải tự động.'
                : 'Chọn đơn vị/khoa và nhấn "Xem thống kê"' }}
        </p>
    </div>

    <div id="statisticsContentInstructor" class="dashboard-stat-results hidden space-y-6">
        <div class="dashboard-stat-kpi-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            @if($dashboard_scope['type'] === 'instructor')
                <div class="dashboard-stat-kpi bg-white rounded-xl shadow-sm p-5 border-l-4 border-cyan-500">
                    <p class="text-sm text-gray-500 font-medium">Số lớp đã giảng</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" id="totalClasses">0</p>
                    <p class="text-xs text-gray-500 mt-1">Trong khoảng thống kê</p>
                </div>
                <div class="dashboard-stat-kpi bg-white rounded-xl shadow-sm p-5 border-l-4 border-indigo-500">
                    <p class="text-sm text-gray-500 font-medium">Tổng tiết đã giảng</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" id="totalLessonsInstructor">0</p>
                    <p class="text-xs text-gray-500 mt-1">Tất cả loại tiết trực tiếp</p>
                </div>
                <div class="dashboard-stat-kpi bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500">
                    <p class="text-sm text-gray-500 font-medium">Tiết lý thuyết</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" id="theoryLessonsInstructor">0</p>
                    <p class="text-xs text-gray-500 mt-1">Tiết giảng lý thuyết</p>
                </div>
                <div class="dashboard-stat-kpi bg-white rounded-xl shadow-sm p-5 border-l-4 border-emerald-500">
                    <p class="text-sm text-gray-500 font-medium">Tiết thực hành</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" id="practiceLessonsInstructor">0</p>
                    <p class="text-xs text-gray-500 mt-1">Tiết giảng thực hành</p>
                </div>
            @else
                <div class="dashboard-stat-kpi bg-white rounded-xl shadow-sm p-5 border-l-4 border-indigo-500">
                    <p class="text-sm text-gray-500 font-medium">Tổng số giảng viên</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" id="totalInstructors">0</p>
                    <p class="text-xs text-gray-500 mt-1">Đang giảng dạy</p>
                </div>
                <div class="dashboard-stat-kpi bg-white rounded-xl shadow-sm p-5 border-l-4 border-cyan-500">
                    <p class="text-sm text-gray-500 font-medium">Tổng số lớp được dạy</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" id="totalClasses">0</p>
                    <p class="text-xs text-gray-500 mt-1">Lớp học</p>
                </div>
                <div class="dashboard-stat-kpi bg-white rounded-xl shadow-sm p-5 border-l-4 border-emerald-500">
                    <p class="text-sm text-gray-500 font-medium">Tổng số tiết đã giảng</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" id="totalLessonsInstructor">0</p>
                    <div class="mt-2 text-xs text-gray-500 space-y-1">
                        <div class="flex justify-between"><span>📘 Lý thuyết</span><span class="font-semibold text-gray-800" id="theoryLessonsInstructor">0</span></div>
                        <div class="flex justify-between"><span>🔬 Thực hành</span><span class="font-semibold text-gray-800" id="practiceLessonsInstructor">0</span></div>
                    </div>
                </div>
                <div class="dashboard-stat-kpi bg-white rounded-xl shadow-sm p-5 border-l-4 border-pink-500">
                    <p class="text-sm text-gray-500 font-medium">TB tiết/giảng viên</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2" id="avgLessonsPerInstructor">0</p>
                    <p class="text-xs text-gray-500 mt-1">tiết/người</p>
                </div>
            @endif
        </div>

        <div class="dashboard-stat-chart-grid grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="dashboard-stat-chart-card bg-white rounded-xl shadow-sm p-5">
                <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="bi bi-pie-chart text-blue-600"></i>
                    Phân bố loại tiết giảng dạy
                </h3>
                <div class="relative h-[280px]">
                    <canvas id="lessonTypePieChartInstructor"></canvas>
                </div>
            </div>
            <div class="dashboard-stat-chart-card bg-white rounded-xl shadow-sm p-5">
                <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="bi bi-bar-chart text-green-600"></i>
                    {{ $dashboard_scope['type'] === 'instructor' ? 'Tổng hợp tiết giảng của tôi' : 'Top 10 giảng viên theo số tiết' }}
                </h3>
                <div class="relative h-[280px]">
                    <canvas id="instructorWorkloadBarChart"></canvas>
                </div>
            </div>
        </div>

        <div class="dashboard-stat-chart-card dashboard-stat-chart-card--wide bg-white rounded-xl shadow-sm p-5">
                <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="bi bi-graph-up text-purple-600"></i>
                    {{ $dashboard_scope['type'] === 'instructor' ? 'Chi tiết loại tiết của tôi' : 'Chi tiết loại tiết theo giảng viên (Top 10)' }}
            </h3>
            <div class="relative h-[360px]">
                <canvas id="instructorLessonTypeStackedChart"></canvas>
            </div>
        </div>

        <div class="dashboard-stat-table bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-800 flex items-center gap-2">
                    <i class="bi bi-table text-indigo-600"></i>
                    {{ $dashboard_scope['type'] === 'instructor' ? 'Tổng hợp giảng dạy cá nhân' : 'Chi tiết từng giảng viên' }}
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">STT</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Giảng viên</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Đơn vị</th>
                            <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase">Lý thuyết</th>
                            <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thực hành</th>
                            <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase">Tổng tiết</th>
                            <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase">Số lớp</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Danh sách lớp</th>
                        </tr>
                    </thead>
                    <tbody id="instructorTableBody" class="bg-white divide-y divide-gray-200"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    let charts = {};
    const defaultEndDate = @json($stat_instructor['filters']['end_date']);
    const boundForms = window.__dashboardInstructorStatsBoundForms
        || (window.__dashboardInstructorStatsBoundForms = new WeakSet());

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content;
    }

    function setInstructorOptions(items, selected) {
        if (typeof window.setTomSelectOptions === 'function') {
            window.setTomSelectOptions('instructorSelect', items, {
                selected: selected || '',
                enabled: true,
            });
            return;
        }
        const el = document.getElementById('instructorSelect');
        if (!el) return;
        el.disabled = false;
        el.innerHTML = '';
        (items || []).forEach(function (item) {
            const opt = document.createElement('option');
            opt.value = item.value === null || item.value === undefined ? '' : String(item.value);
            opt.textContent = item.text;
            if (item.code) opt.dataset.code = item.code;
            if (item.unit) opt.dataset.unit = item.unit;
            el.appendChild(opt);
        });
        el.value = selected || '';
    }

    function bindInstructorStats() {
        const form = document.getElementById('filterFormInstructor');
        if (!form || boundForms.has(form)) return;
        boundForms.add(form);
        const isPersonalInstructor = form.dataset.personalInstructor === '1';

        const onUnitChange = function () {
            const unitId = typeof window.getSelectValue === 'function'
                ? window.getSelectValue('unitSelect')
                : (document.getElementById('unitSelect')?.value || '');

            if (!unitId) {
                setInstructorOptions([{ value: '', text: 'Tất cả giảng viên' }], '');
                return;
            }

            setInstructorOptions([{ value: '', text: 'Đang tải giảng viên...' }], '');

            fetch('/dashboard/ajax/instructors-by-unit?unit_id=' + encodeURIComponent(unitId), {
                headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    const items = [{ value: '', text: 'Tất cả giảng viên' }].concat(
                        (data.data || []).map(function (instructor) {
                            return {
                                value: instructor.id,
                                text: instructor.code + ' - ' + instructor.name,
                                code: instructor.code || '',
                                unit: instructor.unit_name || '',
                            };
                        })
                    );
                    setInstructorOptions(items, '');
                })
                .catch(function (err) {
                    console.error(err);
                    setInstructorOptions([{ value: '', text: 'Tất cả giảng viên' }], '');
                    Notify.error('Có lỗi khi tải danh sách giảng viên');
                });
        };

        document.getElementById('unitSelect')?.addEventListener('change', onUnitChange);

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const unitId = typeof window.getSelectValue === 'function'
                ? window.getSelectValue('unitSelect')
                : (document.getElementById('unitSelect')?.value || '');

            if (!isPersonalInstructor && !unitId) {
                Notify.warning('Vui lòng chọn đơn vị/khoa');
                return;
            }

            const instructorId = typeof window.getSelectValue === 'function'
                ? window.getSelectValue('instructorSelect')
                : (document.getElementById('instructorSelect')?.value || '');
            const endDate = document.getElementById('endDateInstructor')?.value || '';
            const startDate = document.getElementById('startDateInstructor')?.value || '';

            const params = new URLSearchParams();
            if (unitId) params.set('unit_id', unitId);
            if (instructorId) params.set('instructor_id', instructorId);
            if (endDate) params.set('end_date', endDate);
            if (startDate) params.set('start_date', startDate);

            document.getElementById('loadingInstructor').classList.remove('hidden');
            document.getElementById('emptyStateInstructor').classList.add('hidden');
            document.getElementById('statisticsContentInstructor').classList.add('hidden');

            fetch('/dashboard/ajax/instructor-statistics?' + params.toString(), {
                headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            })
                .then(function (r) { return r.json(); })
                .then(function (result) {
                    document.getElementById('loadingInstructor').classList.add('hidden');
                    if (result.success) {
                        renderStatistics(result.data);
                        document.getElementById('statisticsContentInstructor').classList.remove('hidden');
                    } else {
                        document.getElementById('emptyStateInstructor').classList.remove('hidden');
                    }
                })
                .catch(function (err) {
                    console.error(err);
                    document.getElementById('loadingInstructor').classList.add('hidden');
                    document.getElementById('emptyStateInstructor').classList.remove('hidden');
                    Notify.error('Có lỗi khi tải dữ liệu thống kê');
                });
        });

        document.getElementById('resetFilterInstructor')?.addEventListener('click', function () {
            const unit = document.getElementById('unitSelect');
            if (unit) {
                if (unit.tomselect) unit.tomselect.clear(true);
                else unit.value = '';
            }

            setInstructorOptions([{ value: '', text: 'Tất cả giảng viên' }], '');

            const end = document.getElementById('endDateInstructor');
            if (end) {
                if (end._flatpickr) end._flatpickr.setDate(defaultEndDate, true);
                else end.value = defaultEndDate;
            }

            document.getElementById('statisticsContentInstructor').classList.add('hidden');
            document.getElementById('emptyStateInstructor').classList.remove('hidden');
            Object.values(charts).forEach(function (c) { c.destroy(); });
            charts = {};
        });

        document.getElementById('endDateInstructor')?.addEventListener('change', function (event) {
            const label = form.querySelector('[data-dashboard-end-date-label]');
            if (!label || !event.target.value) return;

            const parts = event.target.value.split('-');
            if (parts.length === 3) {
                label.textContent = parts[2] + '/' + parts[1] + '/' + parts[0];
            }
        });
    }

    function renderStatistics(data) {
        const overview = data.overview;
        const instructorDetails = data.instructor_details;
        const chartData = data.chart_data;

        setText('totalInstructors', overview.total_instructors);
        setText('totalClasses', overview.total_classes);
        setText('totalLessonsInstructor', overview.total_lessons);
        setText('theoryLessonsInstructor', overview.theory_lessons);
        setText('practiceLessonsInstructor', overview.practice_lessons);

        const avg = overview.total_instructors > 0
            ? Math.round(overview.total_lessons / overview.total_instructors)
            : 0;
        setText('avgLessonsPerInstructor', avg);

        renderCharts(chartData);
        renderInstructorTable(instructorDetails);
    }

    function setText(id, value) {
        const element = document.getElementById(id);
        if (element) element.textContent = value;
    }

    function renderCharts(chartData) {
        if (typeof Chart === 'undefined') return;
        Object.values(charts).forEach(function (c) { c.destroy(); });
        charts = {};

        charts.pie = new Chart(document.getElementById('lessonTypePieChartInstructor').getContext('2d'), {
            type: 'pie',
            data: {
                labels: chartData.lesson_type_pie.labels,
                datasets: [{ data: chartData.lesson_type_pie.data, backgroundColor: chartData.lesson_type_pie.colors }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom', labels: { padding: 10, font: { size: 12 } } } },
            },
        });

        charts.bar = new Chart(document.getElementById('instructorWorkloadBarChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartData.instructor_workload_bar.labels,
                datasets: [{
                    label: 'Số tiết',
                    data: chartData.instructor_workload_bar.data,
                    backgroundColor: '#10b981',
                    borderRadius: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, ticks: { stepSize: 5 } },
                    x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 45 } },
                },
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: function (ctx) { return 'Số tiết: ' + ctx.parsed.y; } } },
                },
            },
        });

        charts.stacked = new Chart(document.getElementById('instructorLessonTypeStackedChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartData.instructor_lesson_type_stacked.labels,
                datasets: chartData.instructor_lesson_type_stacked.datasets.map(function (ds) {
                    return Object.assign({}, ds, { borderRadius: 4 });
                }),
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    x: { stacked: true, ticks: { autoSkip: false, maxRotation: 45, minRotation: 45 } },
                    y: { stacked: true, beginAtZero: true, ticks: { stepSize: 5 } },
                },
                plugins: {
                    legend: { position: 'bottom', labels: { padding: 10, font: { size: 12 } } },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) { return ctx.dataset.label + ': ' + ctx.parsed.y + ' tiết'; },
                        },
                    },
                },
            },
        });
    }

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderInstructorTable(instructors) {
        const tbody = document.getElementById('instructorTableBody');
        tbody.innerHTML = '';
        (instructors || []).forEach(function (instructor, index) {
            const classNames = Array.isArray(instructor.class_names) && instructor.class_names.length > 0
                ? instructor.class_names.join(', ')
                : 'Chưa có lớp';

            tbody.insertAdjacentHTML('beforeend', `
                <tr class="hover:bg-blue-50/40">
                    <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-500">${index + 1}</td>
                    <td class="px-5 py-3 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">${escapeHtml(instructor.name)}</div>
                        <div class="text-xs text-gray-500">${escapeHtml(instructor.code)}</div>
                    </td>
                    <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-900">${escapeHtml(instructor.unit)}</td>
                    <td class="px-5 py-3 whitespace-nowrap text-center">
                        <span class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full bg-blue-100 text-blue-800">${instructor.theory_lessons} tiết</span>
                    </td>
                    <td class="px-5 py-3 whitespace-nowrap text-center">
                        <span class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full bg-green-100 text-green-800">${instructor.practice_lessons} tiết</span>
                    </td>
                    <td class="px-5 py-3 whitespace-nowrap text-center text-sm font-bold text-gray-900">${instructor.total_lessons} tiết</td>
                    <td class="px-5 py-3 whitespace-nowrap text-center">
                        <span class="px-2 py-0.5 inline-flex text-xs font-semibold rounded-full bg-purple-100 text-purple-800">${instructor.class_count} lớp</span>
                    </td>
                    <td class="px-5 py-3">
                        <div class="text-sm text-gray-900 max-w-xs truncate" title="${escapeHtml(classNames)}">${escapeHtml(classNames)}</div>
                    </td>
                </tr>
            `);
        });
    }

    function boot() {
        charts = {};
        bindInstructorStats();
    }

    if (!window.__dashboardInstructorStatsBoot) {
        window.__dashboardInstructorStatsBoot = true;
        document.addEventListener('turbo:load', boot);
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
@endpush
