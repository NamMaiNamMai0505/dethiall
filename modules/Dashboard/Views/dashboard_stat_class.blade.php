{{-- Modules/Dashboard/Views/dashboard_stat_class.blade.php --}}
<div class="dashboard-stat-view dashboard-stat-view--class space-y-6">
    {{-- Filter --}}
    <div class="dashboard-stat-filter bg-white rounded-xl shadow-sm p-5">
        <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
            <i class="bi bi-funnel text-blue-600"></i>
            Bộ lọc thống kê
        </h3>
        <form id="filterFormClass" class="space-y-4">
            {{-- Hàng filter: label + control cùng baseline (date picker thẳng hàng Tom Select) --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 items-end">
                <div class="min-w-0 flex flex-col">
                    <label for="specializationSelect" class="block text-sm font-medium text-gray-700 mb-1.5 leading-5">
                        Ngành đào tạo <span class="text-red-500">*</span>
                    </label>
                    <div class="ui-select-field w-full">
                        <select name="specialization_id" id="specializationSelect"
                            data-placeholder="Chọn ngành đào tạo..."
                            class="w-full" required>
                            <option value="">Chọn ngành đào tạo...</option>
                            @foreach($stat_class['specializations'] as $spec)
                                <option value="{{ $spec->id }}">{{ $spec->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="min-w-0 flex flex-col">
                    <label for="classSelect" class="block text-sm font-medium text-gray-700 mb-1.5 leading-5">
                        Lớp học
                    </label>
                    <div class="ui-select-field w-full">
                        <select name="class_code" id="classSelect"
                            data-placeholder="Tất cả lớp"
                            class="w-full">
                            <option value="">Tất cả lớp</option>
                        </select>
                    </div>
                </div>

                <div class="min-w-0 flex flex-col">
                    <label for="endDateClass" class="block text-sm font-medium text-gray-700 mb-1.5 leading-5">
                        Đến ngày
                    </label>
                    <div class="date-input-field w-full">
                        <div class="date-input-control">
                            <input type="date" name="end_date" id="endDateClass"
                                value="{{ $stat_class['filters']['end_date'] }}"
                                class="date-input date-input--ready w-full">
                            <i class="bi bi-calendar3 date-input-icon" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
            </div>

            <p class="text-xs text-gray-500 leading-4 -mt-1">
                Chọn ngành trước để lọc lớp · để trống lớp = tất cả lớp
            </p>

            <input type="hidden" name="start_date" id="startDateClass" value="{{ $stat_class['filters']['start_date'] }}">

            <div class="flex flex-wrap gap-2 pt-0.5">
                <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium bg-blue-600 hover:bg-blue-700 text-white shadow-sm">
                    <i class="bi bi-search"></i>
                    Xem thống kê
                </button>
                <button type="button" id="resetFilterClass"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-lg text-sm font-medium bg-gray-100 hover:bg-gray-200 text-gray-700 border border-gray-200">
                    <i class="bi bi-arrow-counterclockwise"></i>
                    Đặt lại
                </button>
            </div>
        </form>
    </div>

    <div id="loadingClass" class="hidden">
        <div class="dashboard-stat-loading bg-white rounded-xl shadow-sm p-12 text-center">
            <div class="inline-block animate-spin rounded-full h-10 w-10 border-2 border-blue-200 border-t-blue-600"></div>
            <p class="mt-4 text-gray-600 text-sm">Đang tải dữ liệu...</p>
        </div>
    </div>

    <div id="emptyStateClass" class="dashboard-stat-empty bg-white rounded-xl shadow-sm p-12 text-center">
        <i class="bi bi-inbox text-5xl text-gray-300"></i>
        <h3 class="mt-3 text-lg font-medium text-gray-900">Chưa có dữ liệu</h3>
        <p class="mt-1 text-sm text-gray-500">Chọn ngành đào tạo và nhấn "Xem thống kê"</p>
    </div>

    <div id="statisticsContentClass" class="dashboard-stat-results hidden space-y-6">
        <div id="scheduleInfo" class="dashboard-stat-context bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500">
            <h3 class="text-base font-semibold text-gray-800 mb-3 flex items-center gap-2">
                <i class="bi bi-info-circle text-blue-600"></i>
                Thông tin lịch học
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="flex items-center gap-3">
                    <div class="rounded-full bg-blue-100 p-2.5">
                        <i class="bi bi-people-fill text-blue-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Lớp học</p>
                        <p class="text-sm font-semibold text-gray-900" id="infoClassName">-</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="rounded-full bg-purple-100 p-2.5">
                        <i class="bi bi-mortarboard-fill text-purple-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Ngành đào tạo</p>
                        <p class="text-sm font-semibold text-gray-900" id="infoSpecialization">-</p>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <div class="rounded-full bg-green-100 p-2.5">
                        <i class="bi bi-calendar-check-fill text-green-600"></i>
                    </div>
                    <div>
                        <p class="text-xs text-gray-500">Học kỳ / Năm học</p>
                        <p class="text-sm font-semibold text-gray-900" id="infoSemester">-</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="dashboard-stat-kpi-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="dashboard-stat-kpi bg-white rounded-xl shadow-sm p-5 border-l-4 border-blue-500">
                <p class="text-sm text-gray-500 font-medium">Tổng số môn học</p>
                <p class="text-3xl font-bold text-gray-900 mt-2" id="totalSubjects">0</p>
            </div>
            <div class="dashboard-stat-kpi bg-white rounded-xl shadow-sm p-5 border-l-4 border-green-500">
                <p class="text-sm text-gray-500 font-medium">Tổng số tiết đã học</p>
                <p class="text-3xl font-bold text-gray-900 mt-2" id="totalLessons">0</p>
                <div class="mt-2 text-xs text-gray-500 space-y-1">
                    <div class="flex justify-between"><span>📘 Lý thuyết</span><span class="font-semibold text-gray-800" id="theoryLessons">0</span></div>
                    <div class="flex justify-between"><span>🔬 Thực hành</span><span class="font-semibold text-gray-800" id="practiceLessons">0</span></div>
                    <div class="flex justify-between"><span>📝 Tự học</span><span class="font-semibold text-gray-800" id="selfStudyLessons">0</span></div>
                </div>
            </div>
            <div class="dashboard-stat-kpi bg-white rounded-xl shadow-sm p-5 border-l-4 border-purple-500">
                <p class="text-sm text-gray-500 font-medium">Môn thi</p>
                <p class="text-3xl font-bold text-gray-900 mt-2"><span id="examSubjects">0</span></p>
                <p class="text-xs text-gray-500 mt-1">Tổng <span id="examLessons">0</span> buổi thi</p>
            </div>
            <div class="dashboard-stat-kpi bg-white rounded-xl shadow-sm p-5 border-l-4 border-orange-500">
                <p class="text-sm text-gray-500 font-medium">Tiến độ học tập</p>
                <p class="text-3xl font-bold text-gray-900 mt-2" id="progressPercent">0%</p>
                <p class="text-xs text-gray-500 mt-1" id="progressDetail">0/0 tiết</p>
                <div class="mt-3 bg-gray-100 rounded-full h-2 overflow-hidden">
                    <div id="progressBar" class="bg-orange-500 h-full rounded-full transition-all duration-500" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <div class="dashboard-stat-chart-grid grid grid-cols-1 lg:grid-cols-2 gap-6">
            <div class="dashboard-stat-chart-card bg-white rounded-xl shadow-sm p-5">
                <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="bi bi-pie-chart text-blue-600"></i>
                    Phân bố loại tiết học
                </h3>
                <div class="relative h-[280px]">
                    <canvas id="lessonTypePieChart"></canvas>
                </div>
            </div>
            <div class="dashboard-stat-chart-card bg-white rounded-xl shadow-sm p-5">
                <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="bi bi-bar-chart text-green-600"></i>
                    Top 10 môn học theo tiến độ
                </h3>
                <div class="relative h-[280px]">
                    <canvas id="subjectProgressBarChart"></canvas>
                </div>
            </div>
        </div>

        <div class="dashboard-stat-chart-card dashboard-stat-chart-card--wide bg-white rounded-xl shadow-sm p-5">
            <h3 class="text-base font-semibold text-gray-800 mb-4 flex items-center gap-2">
                <i class="bi bi-graph-up text-purple-600"></i>
                Tiến độ chi tiết từng môn (Top 10)
            </h3>
            <div class="relative h-[360px]">
                <canvas id="subjectCompletionStackedChart"></canvas>
            </div>
        </div>

        <div class="dashboard-stat-table bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-200">
                <h3 class="text-base font-semibold text-gray-800 flex items-center gap-2">
                    <i class="bi bi-table text-indigo-600"></i>
                    Chi tiết từng môn học
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">STT</th>
                            <th class="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">Môn học</th>
                            <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase">Lý thuyết</th>
                            <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase">Thực hành</th>
                            <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase">Tự học</th>
                            <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase">Tổng tiến độ</th>
                            <th class="px-5 py-3 text-center text-xs font-medium text-gray-500 uppercase">Tiến độ</th>
                        </tr>
                    </thead>
                    <tbody id="subjectTableBody" class="bg-white divide-y divide-gray-200"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    let charts = {};
    const defaultEndDate = @json($stat_class['filters']['end_date']);
    const boundForms = window.__dashboardClassStatsBoundForms
        || (window.__dashboardClassStatsBoundForms = new WeakSet());

    function csrfToken() {
        return document.querySelector('meta[name="csrf-token"]')?.content;
    }

    function setClassOptions(items, selected) {
        if (typeof window.setTomSelectOptions === 'function') {
            window.setTomSelectOptions('classSelect', items, {
                selected: selected || '',
                enabled: true,
            });
            return;
        }
        const el = document.getElementById('classSelect');
        if (!el) return;
        el.disabled = false;
        el.innerHTML = '';
        (items || []).forEach(function (item) {
            const opt = document.createElement('option');
            opt.value = item.value === null || item.value === undefined ? '' : String(item.value);
            opt.textContent = item.text;
            el.appendChild(opt);
        });
        el.value = selected || '';
    }

    function getFormParams() {
        const params = new URLSearchParams();
        const specializationId = typeof window.getSelectValue === 'function'
            ? window.getSelectValue('specializationSelect')
            : (document.getElementById('specializationSelect')?.value || '');
        const classCode = typeof window.getSelectValue === 'function'
            ? window.getSelectValue('classSelect')
            : (document.getElementById('classSelect')?.value || '');
        const endDate = document.getElementById('endDateClass')?.value || '';
        const startDate = document.getElementById('startDateClass')?.value || '';

        if (specializationId) params.set('specialization_id', specializationId);
        if (classCode) params.set('class_code', classCode);
        if (endDate) params.set('end_date', endDate);
        if (startDate) params.set('start_date', startDate);
        return params;
    }

    function bindClassStats() {
        const form = document.getElementById('filterFormClass');
        if (!form || boundForms.has(form)) return;
        boundForms.add(form);

        const onSpecChange = function () {
            const specializationId = typeof window.getSelectValue === 'function'
                ? window.getSelectValue('specializationSelect')
                : (document.getElementById('specializationSelect')?.value || '');

            if (!specializationId) {
                setClassOptions([{ value: '', text: 'Tất cả lớp' }], '');
                return;
            }

            setClassOptions([{ value: '', text: 'Đang tải lớp...' }], '');

            fetch('/dashboard/ajax/classes-by-specialization?specialization_id=' + encodeURIComponent(specializationId), {
                headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    const items = [{ value: '', text: 'Tất cả lớp' }].concat(
                        (data.data || []).map(function (cls) {
                            return { value: cls.code, text: cls.code + ' - ' + cls.name };
                        })
                    );
                    setClassOptions(items, '');
                })
                .catch(function (err) {
                    console.error(err);
                    setClassOptions([{ value: '', text: 'Tất cả lớp' }], '');
                    Notify.error('Có lỗi khi tải danh sách lớp');
                });
        };

        // Tom Select sync value + fire change trên <select> gốc
        document.getElementById('specializationSelect')?.addEventListener('change', onSpecChange);

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const specializationId = typeof window.getSelectValue === 'function'
                ? window.getSelectValue('specializationSelect')
                : (document.getElementById('specializationSelect')?.value || '');

            if (!specializationId) {
                Notify.warning('Vui lòng chọn ngành đào tạo');
                return;
            }

            const params = getFormParams();
            document.getElementById('loadingClass').classList.remove('hidden');
            document.getElementById('emptyStateClass').classList.add('hidden');
            document.getElementById('statisticsContentClass').classList.add('hidden');

            fetch('/dashboard/ajax/class-statistics?' + params.toString(), {
                headers: { 'X-CSRF-TOKEN': csrfToken(), 'Accept': 'application/json' },
            })
                .then(function (r) { return r.json(); })
                .then(function (result) {
                    document.getElementById('loadingClass').classList.add('hidden');
                    if (result.success) {
                        renderStatistics(result.data);
                        document.getElementById('statisticsContentClass').classList.remove('hidden');
                    } else {
                        document.getElementById('emptyStateClass').classList.remove('hidden');
                    }
                })
                .catch(function (err) {
                    console.error(err);
                    document.getElementById('loadingClass').classList.add('hidden');
                    document.getElementById('emptyStateClass').classList.remove('hidden');
                    Notify.error('Có lỗi khi tải dữ liệu thống kê');
                });
        });

        document.getElementById('resetFilterClass')?.addEventListener('click', function () {
            const spec = document.getElementById('specializationSelect');
            if (spec) {
                if (spec.tomselect) spec.tomselect.clear(true);
                else spec.value = '';
            }
            setClassOptions([{ value: '', text: 'Tất cả lớp' }], '');

            const end = document.getElementById('endDateClass');
            if (end) {
                if (end._flatpickr) end._flatpickr.setDate(defaultEndDate, true);
                else end.value = defaultEndDate;
            }

            document.getElementById('statisticsContentClass').classList.add('hidden');
            document.getElementById('emptyStateClass').classList.remove('hidden');
            Object.values(charts).forEach(function (c) { c.destroy(); });
            charts = {};
        });
    }

    function renderStatistics(data) {
        const overview = data.overview;
        const subjectDetails = data.subject_details;
        const chartData = data.chart_data;
        const scheduleMetadata = data.schedule_metadata;

        if (scheduleMetadata) {
            const className = scheduleMetadata.class_code
                ? (scheduleMetadata.class_code + ' - ' + (scheduleMetadata.class_name || ''))
                : 'Tất cả lớp';
            const semester = scheduleMetadata.semester
                ? (scheduleMetadata.semester + ' / ' + (scheduleMetadata.academic_year || ''))
                : '-';
            document.getElementById('infoClassName').textContent = className;
            document.getElementById('infoSpecialization').textContent = scheduleMetadata.specialization_name || '-';
            document.getElementById('infoSemester').textContent = semester;
        } else {
            document.getElementById('infoClassName').textContent = 'Tất cả lớp';
            document.getElementById('infoSpecialization').textContent = '-';
            document.getElementById('infoSemester').textContent = '-';
        }

        document.getElementById('totalSubjects').textContent = overview.total_subjects;
        document.getElementById('theoryLessons').textContent = overview.theory_lessons;
        document.getElementById('practiceLessons').textContent = overview.practice_lessons;
        document.getElementById('selfStudyLessons').textContent = overview.self_study_lessons;
        document.getElementById('totalLessons').textContent = overview.total_completed;
        document.getElementById('examSubjects').textContent = overview.exam_subjects;
        document.getElementById('examLessons').textContent = overview.exam_lessons;
        document.getElementById('progressPercent').textContent = overview.progress_percent + '%';
        document.getElementById('progressDetail').textContent = overview.total_completed + '/' + overview.total_planned + ' tiết';
        document.getElementById('progressBar').style.width = overview.progress_percent + '%';

        renderCharts(chartData);
        renderSubjectTable(subjectDetails);
    }

    function renderCharts(chartData) {
        if (typeof Chart === 'undefined') return;
        Object.values(charts).forEach(function (c) { c.destroy(); });
        charts = {};

        charts.pie = new Chart(document.getElementById('lessonTypePieChart').getContext('2d'), {
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

        charts.bar = new Chart(document.getElementById('subjectProgressBarChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartData.subject_progress_bar.labels,
                datasets: [{
                    label: 'Tiến độ (%)',
                    data: chartData.subject_progress_bar.data,
                    backgroundColor: '#10b981',
                    borderRadius: 4,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: { beginAtZero: true, max: 100, ticks: { callback: function (v) { return v + '%'; } } },
                    x: { ticks: { autoSkip: false, maxRotation: 45, minRotation: 45 } },
                },
                plugins: {
                    legend: { display: false },
                    tooltip: { callbacks: { label: function (ctx) { return 'Tiến độ: ' + ctx.parsed.y + '%'; } } },
                },
            },
        });

        charts.stacked = new Chart(document.getElementById('subjectCompletionStackedChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: chartData.subject_completion_stacked.labels,
                datasets: chartData.subject_completion_stacked.datasets.map(function (ds) {
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

    function progressColor(percent) {
        if (percent >= 80) return 'text-green-600';
        if (percent >= 50) return 'text-yellow-600';
        return 'text-red-600';
    }

    function progressBg(percent) {
        if (percent >= 80) return 'bg-green-500';
        if (percent >= 50) return 'bg-yellow-500';
        return 'bg-red-500';
    }

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function renderSubjectTable(subjects) {
        const tbody = document.getElementById('subjectTableBody');
        tbody.innerHTML = '';
        (subjects || []).forEach(function (subject, index) {
            tbody.insertAdjacentHTML('beforeend', `
                <tr class="hover:bg-blue-50/40">
                    <td class="px-5 py-3 whitespace-nowrap text-sm text-gray-500">${index + 1}</td>
                    <td class="px-5 py-3 whitespace-nowrap">
                        <div class="text-sm font-medium text-gray-900">${escapeHtml(subject.name)}</div>
                        <div class="text-xs text-gray-500">${escapeHtml(subject.code)}</div>
                    </td>
                    <td class="px-5 py-3 whitespace-nowrap text-center text-sm">
                        <div class="text-gray-900">${subject.theory.done}/${subject.theory.total}</div>
                        <div class="text-xs text-gray-500">${subject.theory.percent}%</div>
                    </td>
                    <td class="px-5 py-3 whitespace-nowrap text-center text-sm">
                        <div class="text-gray-900">${subject.practice.done}/${subject.practice.total}</div>
                        <div class="text-xs text-gray-500">${subject.practice.percent}%</div>
                    </td>
                    <td class="px-5 py-3 whitespace-nowrap text-center text-sm">
                        <div class="text-gray-900">${subject.self_study.done}/${subject.self_study.total}</div>
                        <div class="text-xs text-gray-500">${subject.self_study.percent}%</div>
                    </td>
                    <td class="px-5 py-3 whitespace-nowrap text-center text-sm font-semibold text-gray-900">
                        ${subject.total_done}/${subject.total_planned}
                    </td>
                    <td class="px-5 py-3 whitespace-nowrap text-center">
                        <div class="flex items-center justify-center">
                            <div class="w-20">
                                <div class="flex justify-between mb-1">
                                    <span class="text-xs font-semibold ${progressColor(subject.progress_percent)}">${subject.progress_percent}%</span>
                                </div>
                                <div class="w-full bg-gray-200 rounded-full h-2">
                                    <div class="h-2 rounded-full ${progressBg(subject.progress_percent)}" style="width: ${subject.progress_percent}%"></div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
            `);
        });
    }

    function boot() {
        charts = {};
        bindClassStats();
    }

    if (!window.__dashboardClassStatsBoot) {
        window.__dashboardClassStatsBoot = true;
        document.addEventListener('turbo:load', boot);
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
</script>
@endpush
