@extends('layouts.admin')

@section('title', 'Phân công giảng dạy')
@section('page-title', 'Phân công giảng dạy')

@section('content')
    <x-breadcrumb :items="[
            ['title' => 'Trang chủ'],
            ['title' => 'Phân công giảng viên', 'url' => route('teaching-assignments.index')],
            ['title' => 'Tạo phân công']
        ]" />

    <x-page-header title="PHÂN CÔNG GIẢNG VIÊN"
        :actions="[
            [
                'url' => route('teaching-assignments.index'),
                'label' => 'Quay lại',
                'icon' => 'arrow-left',
                'color' => 'gray'
            ]
        ]" />

    <div class="bg-white shadow rounded-lg p-6">
        <form action="{{ route('teaching-assignments.store') }}" method="POST" class="space-y-6" id="teaching-assignment-form">
            @csrf

            {{-- Chọn giảng viên --}}
            <div>
                <label for="instructor_id" class="block text-sm font-medium text-gray-700 mb-2">Giảng viên *</label>
                <select id="instructor_id"
                        name="instructor_id"
                        data-searchable="1"
                        data-placeholder="Tìm giảng viên..."
                        class="w-full"
                        {{ $instructor ? 'disabled' : '' }}
                        required>
                    <option value="">-- Chọn giảng viên --</option>
                    @foreach(\Modules\Instructor\Models\Instructor::active()->orderBy('name')->get() as $ins)
                        <option value="{{ $ins->id }}"
                            {{ (old('instructor_id') == $ins->id || ($instructor && $instructor->id == $ins->id)) ? 'selected' : '' }}>
                            {{ $ins->name }}@if($ins->code) ({{ $ins->code }})@endif
                        </option>
                    @endforeach
                </select>

                @if($instructor)
                    <input type="hidden" name="instructor_id" value="{{ $instructor->id }}">
                @endif

                @error('instructor_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Chọn ngành đào tạo và học kì --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="specialization_filter" class="block text-sm font-medium text-gray-700 mb-2">Ngành đào tạo</label>
                    <select id="specialization_filter"
                            data-searchable="1"
                            data-placeholder="Tất cả ngành"
                            class="w-full">
                        <option value="">-- Tất cả ngành --</option>
                        @foreach($specializations as $specialization)
                            <option value="{{ $specialization->id }}">{{ $specialization->selection_label }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="semester_filter" class="block text-sm font-medium text-gray-700 mb-2">Học kỳ</label>
                    <select id="semester_filter"
                            data-searchable="0"
                            data-placeholder="Tất cả học kỳ"
                            class="w-full">
                        <option value="">-- Tất cả học kỳ --</option>
                        <option value="semester_1">Học kỳ 1</option>
                        <option value="semester_2">Học kỳ 2</option>
                        <option value="semester_3">Học kỳ 3</option>
                        <option value="semester_4">Học kỳ 4</option>
                        <option value="semester_5">Học kỳ 5</option>
                        <option value="semester_6">Học kỳ 6</option>
                        <option value="summer">Học kỳ hè</option>
                    </select>
                </div>
            </div>

            {{-- Danh sách môn học --}}
            <div>
                <div class="mb-4 space-y-3">
                    <label class="block text-sm font-medium text-gray-700">
                        Chọn môn học *
                        <span class="text-gray-500 font-normal ml-1">
                            (hiển thị <span id="visible-count">0</span>/<span id="total-count">0</span>)
                        </span>
                    </label>

                    {{-- Hàng công cụ: tìm kiếm + chọn/bỏ — cùng chiều cao, icon căn giữa --}}
                    <div class="flex flex-col sm:flex-row sm:items-stretch gap-2">
                        <div class="relative flex-1 min-w-0">
                            <span class="pointer-events-none absolute inset-y-0 left-0 z-[1] flex w-11 items-center justify-center text-gray-400">
                                <i class="bi bi-search text-[0.95rem] leading-none"></i>
                            </span>
                            <input type="text" id="subject_search"
                                placeholder="Tìm theo tên hoặc mã môn học..."
                                autocomplete="off"
                                class="block w-full h-11 rounded-lg border border-gray-300 bg-white pl-11 pr-11 text-sm leading-none
                                       shadow-sm placeholder:text-gray-400
                                       focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                            <button type="button" id="subject_search_clear"
                                    class="hidden absolute inset-y-0 right-0 z-[1] flex w-11 items-center justify-center text-gray-400 hover:text-gray-700"
                                    title="Xóa tìm kiếm" aria-label="Xóa tìm kiếm">
                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-md hover:bg-gray-100">
                                    <i class="bi bi-x-lg text-xs leading-none"></i>
                                </span>
                            </button>
                        </div>
                        <div class="flex shrink-0 gap-2">
                            <button type="button" id="select_all_visible"
                                class="inline-flex h-11 flex-1 sm:flex-none items-center justify-center gap-1.5 rounded-lg bg-blue-600 px-4 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700 whitespace-nowrap">
                                <i class="bi bi-check-all leading-none"></i>
                                <span>Chọn tất cả</span>
                            </button>
                            <button type="button" id="deselect_all"
                                class="inline-flex h-11 flex-1 sm:flex-none items-center justify-center gap-1.5 rounded-lg bg-slate-500 px-4 text-sm font-medium text-white shadow-sm transition-colors hover:bg-slate-600 whitespace-nowrap">
                                <i class="bi bi-x-lg leading-none"></i>
                                <span>Bỏ chọn</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 p-4 bg-gray-50 rounded-lg max-h-[32rem] overflow-y-auto border border-gray-200" id="subject_list">
                    @php $subjectTotal = 0; @endphp
                    @foreach($specializations as $specialization)
                        @foreach($specialization->subjects as $subject)
                            @php
                                $subjectTotal++;
                                $isChecked = false;
                                if (old('subject_ids')) {
                                    $isChecked = in_array($subject->id, old('subject_ids', []));
                                } elseif ($instructor && $instructor->subjects) {
                                    $isChecked = $instructor->subjects->contains('id', $subject->id);
                                }
                            @endphp
                            <label class="subject-item flex items-start space-x-2 p-2 hover:bg-white rounded cursor-pointer transition border border-transparent"
                                   data-spec-id="{{ $specialization->id }}"
                                   data-spec-name="{{ $specialization->name }}"
                                   data-semester="{{ $subject->semester }}"
                                   data-search="{{ mb_strtolower($subject->name.' '.$subject->code) }}">
                                <input type="checkbox"
                                    name="subject_ids[]"
                                    value="{{ $subject->id }}"
                                    data-subject-name="{{ $subject->name }}"
                                    data-subject-code="{{ $subject->code }}"
                                    data-spec-id="{{ $specialization->id }}"
                                    data-spec-name="{{ $specialization->name }}"
                                    data-semester="{{ $subject->semester }}"
                                    class="subject-checkbox form-checkbox text-blue-600 rounded focus:ring-blue-500 mt-0.5"
                                    {{ $isChecked ? 'checked' : '' }}>
                                <div class="flex-1 min-w-0">
                                    <span class="text-sm font-medium block truncate">{{ $subject->name }}</span>
                                    <span class="text-xs text-gray-500">{{ $subject->code }} · {{ $subject->semester_text }}</span>
                                </div>
                            </label>
                        @endforeach
                    @endforeach
                    @if($subjectTotal === 0)
                        <div class="col-span-full text-center text-sm text-gray-500 py-8">Chưa có môn học nào trong hệ thống.</div>
                    @endif
                </div>

                @error('subject_ids')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Danh sách môn đã chọn --}}
            <div class="border-t pt-4">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-medium text-gray-700">
                        Môn đã chọn (<span id="total-selected">0</span>)
                    </h3>
                    <div class="flex items-center gap-3">
                        <div class="flex items-center gap-2 bg-gray-100 rounded-lg p-1">
                            <button type="button" id="view-grouped" class="view-toggle-btn px-3 py-1 rounded text-xs font-medium bg-blue-600 text-white" data-view="grouped">
                                <i class="bi bi-layers mr-1"></i> Nhóm
                            </button>
                            <button type="button" id="view-list" class="view-toggle-btn px-3 py-1 rounded text-xs font-medium text-gray-600 hover:bg-white" data-view="list">
                                <i class="bi bi-list-ul mr-1"></i> Danh sách
                            </button>
                        </div>
                        <button type="button" id="clear-all" class="text-sm text-red-600 hover:text-red-800 hover:underline">
                            Xóa tất cả
                        </button>
                    </div>
                </div>

                <div id="selected-grouped" class="space-y-4 min-h-[100px] p-4 bg-gray-50 rounded-lg">
                    <div class="text-gray-500 text-sm text-center py-8">Chưa có môn học nào được chọn</div>
                </div>

                <div id="selected-list" class="hidden min-h-[100px] p-4 bg-gray-50 rounded-lg">
                    <div class="text-gray-500 text-sm text-center py-8">Chưa có môn học nào được chọn</div>
                </div>
            </div>

            <div class="pt-4 border-t text-right space-x-3">
                <button type="button" onclick="window.history.back()"
                    class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                    Hủy
                </button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    <i class="bi bi-save mr-2"></i>{{ $instructor ? 'Cập nhật phân công' : 'Lưu phân công' }}
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    function getVal(el) {
        if (!el) return '';
        if (typeof window.getSelectValue === 'function') return String(window.getSelectValue(el) || '');
        if (el.tomselect) {
            const v = el.tomselect.getValue();
            return String(Array.isArray(v) ? (v[0] || '') : (v || ''));
        }
        return String(el.value || '');
    }

    function bindChange(el, handler) {
        if (!el) return;
        if (typeof window.onTomChange === 'function') {
            window.onTomChange(el, handler);
            return;
        }
        el.addEventListener('change', handler);
        if (el.tomselect) el.tomselect.on('change', handler);
    }

    function boot() {
        const form = document.getElementById('teaching-assignment-form');
        if (!form || form.dataset.taBound === '1') return;
        form.dataset.taBound = '1';

        const specializationFilter = document.getElementById('specialization_filter');
        const semesterFilter = document.getElementById('semester_filter');
        const subjectSearch = document.getElementById('subject_search');
        const subjectSearchClear = document.getElementById('subject_search_clear');
        const subjectList = document.getElementById('subject_list');
        const selectedGrouped = document.getElementById('selected-grouped');
        const selectedList = document.getElementById('selected-list');
        const totalSelected = document.getElementById('total-selected');
        const visibleCountEl = document.getElementById('visible-count');
        const totalCountEl = document.getElementById('total-count');
        const clearAllBtn = document.getElementById('clear-all');
        const selectAllVisibleBtn = document.getElementById('select_all_visible');
        const deselectAllBtn = document.getElementById('deselect_all');
        const viewGroupedBtn = document.getElementById('view-grouped');
        const viewListBtn = document.getElementById('view-list');

        if (typeof window.initTomSelects === 'function') {
            window.initTomSelects(form);
        }

        const subjectItems = () => Array.from(document.querySelectorAll('#subject_list .subject-item'));
        const checkboxes = () => Array.from(document.querySelectorAll('#subject_list .subject-checkbox'));

        if (totalCountEl) totalCountEl.textContent = String(subjectItems().length);

        let currentView = 'grouped';

        function filterSubjects() {
            const specId = getVal(specializationFilter);
            const semester = getVal(semesterFilter);
            const searchTerm = (subjectSearch?.value || '').trim().toLowerCase();
            let visibleCount = 0;

            subjectItems().forEach(function (item) {
                const itemSpecId = String(item.dataset.specId || '');
                const itemSemester = String(item.dataset.semester || '');
                const haystack = String(item.dataset.search || '').toLowerCase();
                const checkbox = item.querySelector('.subject-checkbox');
                const name = String(checkbox?.dataset.subjectName || '').toLowerCase();
                const code = String(checkbox?.dataset.subjectCode || '').toLowerCase();

                let show = true;
                if (specId && itemSpecId !== String(specId)) show = false;
                if (show && semester && itemSemester !== String(semester)) show = false;
                if (show && searchTerm) {
                    const match = haystack.includes(searchTerm) || name.includes(searchTerm) || code.includes(searchTerm);
                    if (!match) show = false;
                }

                // Ẩn/hiện — không uncheck (giữ môn đã chọn khi lọc)
                item.style.display = show ? '' : 'none';
                item.classList.toggle('hidden', !show);
                if (show) visibleCount++;
            });

            if (visibleCountEl) visibleCountEl.textContent = String(visibleCount);
            if (selectAllVisibleBtn) {
                selectAllVisibleBtn.innerHTML = '<i class="bi bi-check-all mr-1"></i> Chọn tất cả (' + visibleCount + ')';
            }
        }

        function updateSelectedSubjects() {
            const selected = checkboxes().filter(function (cb) { return cb.checked; });
            if (totalSelected) totalSelected.textContent = String(selected.length);

            const grouped = {};
            selected.forEach(function (cb) {
                const specId = cb.dataset.specId || '0';
                const specName = cb.dataset.specName || 'Khác';
                if (!grouped[specId]) grouped[specId] = { name: specName, subjects: [] };
                grouped[specId].subjects.push({
                    id: cb.value,
                    name: cb.dataset.subjectName || '',
                    code: cb.dataset.subjectCode || '',
                });
            });

            if (selectedGrouped) {
                if (selected.length === 0) {
                    selectedGrouped.innerHTML = '<div class="text-gray-500 text-sm text-center py-8">Chưa có môn học nào được chọn</div>';
                } else {
                    selectedGrouped.innerHTML = Object.values(grouped).map(function (group) {
                        return '<div class="bg-white rounded-lg p-4 border border-gray-200">' +
                            '<h4 class="text-sm font-semibold text-gray-700 mb-3 flex items-center">' +
                            '<i class="bi bi-mortarboard text-blue-600 mr-2"></i>' +
                            escapeHtml(group.name) +
                            '<span class="ml-2 bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full text-xs">' + group.subjects.length + '</span>' +
                            '</h4><div class="flex flex-wrap gap-2">' +
                            group.subjects.map(function (s) {
                                return '<span class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm font-medium">' +
                                    '<span class="text-xs opacity-75 mr-2">' + escapeHtml(s.code) + '</span>' +
                                    escapeHtml(s.name) +
                                    '<button type="button" class="ml-2 text-white/80 hover:text-white remove-subject" data-subject-id="' + s.id + '">' +
                                    '<i class="bi bi-x-lg"></i></button></span>';
                            }).join('') +
                            '</div></div>';
                    }).join('');
                }
            }

            if (selectedList) {
                if (selected.length === 0) {
                    selectedList.innerHTML = '<div class="text-gray-500 text-sm text-center py-8">Chưa có môn học nào được chọn</div>';
                } else {
                    selectedList.innerHTML = '<div class="flex flex-wrap gap-2">' + selected.map(function (cb) {
                        return '<span class="inline-flex items-center px-3 py-1.5 bg-blue-600 text-white rounded-lg text-sm font-medium">' +
                            '<span class="text-xs opacity-75 mr-2">' + escapeHtml(cb.dataset.subjectCode || '') + '</span>' +
                            escapeHtml(cb.dataset.subjectName || '') +
                            '<button type="button" class="ml-2 text-white/80 hover:text-white remove-subject" data-subject-id="' + cb.value + '">' +
                            '<i class="bi bi-x-lg"></i></button></span>';
                    }).join('') + '</div>';
                }
            }
        }

        function escapeHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function switchView(view) {
            currentView = view;
            if (view === 'grouped') {
                selectedGrouped?.classList.remove('hidden');
                selectedList?.classList.add('hidden');
                viewGroupedBtn?.classList.add('bg-blue-600', 'text-white');
                viewGroupedBtn?.classList.remove('text-gray-600');
                viewListBtn?.classList.remove('bg-blue-600', 'text-white');
                viewListBtn?.classList.add('text-gray-600');
            } else {
                selectedGrouped?.classList.add('hidden');
                selectedList?.classList.remove('hidden');
                viewListBtn?.classList.add('bg-blue-600', 'text-white');
                viewListBtn?.classList.remove('text-gray-600');
                viewGroupedBtn?.classList.remove('bg-blue-600', 'text-white');
                viewGroupedBtn?.classList.add('text-gray-600');
            }
        }

        function selectAllVisible() {
            subjectItems().forEach(function (item) {
                if (item.style.display === 'none' || item.classList.contains('hidden')) return;
                const cb = item.querySelector('.subject-checkbox');
                if (cb) cb.checked = true;
            });
            updateSelectedSubjects();
        }

        function deselectAll() {
            checkboxes().forEach(function (cb) { cb.checked = false; });
            updateSelectedSubjects();
        }

        bindChange(specializationFilter, filterSubjects);
        bindChange(semesterFilter, filterSubjects);

        function onSearchInput() {
            filterSubjects();
            if (subjectSearchClear) {
                subjectSearchClear.classList.toggle('hidden', !(subjectSearch?.value || '').trim());
            }
        }
        subjectSearch?.addEventListener('input', onSearchInput);
        subjectSearch?.addEventListener('keyup', onSearchInput);
        subjectSearchClear?.addEventListener('click', function () {
            if (subjectSearch) subjectSearch.value = '';
            onSearchInput();
            subjectSearch?.focus();
        });

        subjectList?.addEventListener('change', function (e) {
            if (e.target && e.target.classList.contains('subject-checkbox')) {
                updateSelectedSubjects();
            }
        });

        selectAllVisibleBtn?.addEventListener('click', selectAllVisible);
        deselectAllBtn?.addEventListener('click', deselectAll);

        document.addEventListener('click', function (e) {
            const btn = e.target.closest && e.target.closest('.remove-subject');
            if (!btn || !form.contains(btn)) return;
            e.preventDefault();
            const subjectId = btn.getAttribute('data-subject-id');
            const checkbox = form.querySelector('.subject-checkbox[value="' + CSS.escape(String(subjectId)) + '"]');
            if (checkbox) {
                checkbox.checked = false;
                updateSelectedSubjects();
            }
        });

        clearAllBtn?.addEventListener('click', function () {
            window.PortalPopup.confirm('Bạn có chắc chắn muốn xóa tất cả môn học đã chọn?', {
                danger: true,
                confirmText: 'Xóa hết',
                title: 'Xóa lựa chọn',
            }).then(function (ok) {
                if (ok) deselectAll();
            });
        });

        viewGroupedBtn?.addEventListener('click', function () { switchView('grouped'); });
        viewListBtn?.addEventListener('click', function () { switchView('list'); });

        filterSubjects();
        updateSelectedSubjects();
    }

    document.addEventListener('DOMContentLoaded', boot);
    document.addEventListener('turbo:load', boot);
    if (document.readyState !== 'loading') boot();
})();
</script>
@endpush
