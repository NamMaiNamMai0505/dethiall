@extends('layouts.admin')

@section('title', 'Tạo môn học mới')
@section('page-title', 'Tạo môn học mới')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Môn học', 'url' => route('subjects.index')],
    ['title' => 'Tạo mới']
]" />

{{-- Page Header --}}
<x-page-header
    title="TẠO MÔN HỌC MỚI"
    :actions="[
        [
            'url' => route('subjects.index'),
            'label' => 'Quay lại',
            'icon' => 'arrow-left',
            'color' => 'gray'
        ]
    ]" />

{{-- Import Excel: chọn file → xem trước → xác nhận --}}
<div class="bg-white rounded-lg shadow-sm border p-6 mb-6" id="importExcelPanel">
    <div class="mb-4">
        <h3 class="text-lg font-semibold text-gray-900">
            <i class="bi bi-file-earmark-excel text-green-600 mr-1"></i>
            Import môn học từ Excel
        </h3>
        <p class="text-sm text-gray-500 mt-1">
            Chọn ngành → mã ngành → chọn file Excel → <strong>kiểm tra bảng xem trước</strong> → bấm xác nhận mới lưu.
            Mã chuẩn = mã ngành + <code class="bg-gray-100 px-1 rounded">_</code> + mã môn trong file
            (vd <strong>B_CDDD_M009K2</strong>).
        </p>
    </div>

    <div class="space-y-4">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="import_specialization_id" class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="bi bi-mortarboard text-indigo-600 mr-1"></i>
                    Ngành đào tạo <span class="text-red-500">*</span>
                </label>
                <div class="ui-select-field">
                    <select id="import_specialization_id"
                            data-placeholder="Chọn ngành đào tạo..."
                            data-searchable="1"
                            class="w-full">
                        <option value="">Chọn ngành đào tạo...</option>
                        @foreach($specializations as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="import_code_prefix" class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="bi bi-tag text-blue-600 mr-1"></i>
                    Tiền tố mã môn (nội bộ)
                </label>
                <input type="text"
                       id="import_code_prefix"
                       maxlength="50"
                       placeholder="VD: B_6720301"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm uppercase font-mono"
                       autocomplete="off">
                <p class="mt-2 text-xs text-gray-600 leading-relaxed bg-slate-50 border border-slate-200 rounded-lg p-3">
                    {{ $codePrefixNote }}
                </p>
            </div>
        </div>

        <div>
            <label for="import_file" class="block text-sm font-semibold text-gray-700 mb-2">
                <i class="bi bi-upload text-green-600 mr-1"></i>
                File Excel <span class="text-red-500">*</span>
            </label>
            <div class="flex flex-col sm:flex-row sm:items-center gap-3">
                <input type="file"
                       id="import_file"
                       accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
                       class="block w-full text-sm text-gray-700 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-green-50 file:text-green-700 hover:file:bg-green-100 border border-gray-300 rounded-lg">
                <a href="{{ route('subjects.import.template') }}"
                   class="inline-flex items-center justify-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium rounded-lg shrink-0 whitespace-nowrap">
                    <i class="bi bi-download"></i> Lấy file mẫu
                </a>
            </div>
            <p class="mt-1 text-xs text-gray-500">Chỉ nhận <strong>.xlsx</strong> / <strong>.xls</strong>. Sau khi chọn file, hệ thống hiện bảng để bạn kiểm tra.</p>
            <p id="importFileMeta" class="mt-1 text-xs text-gray-700 hidden"></p>
            <div id="importMessage" class="hidden mt-3 p-3 rounded-lg text-sm"></div>
        </div>

        {{-- Preview --}}
        <div id="importPreviewWrap" class="hidden border border-blue-200 rounded-lg overflow-hidden">
            <div class="bg-blue-50 px-4 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2 border-b border-blue-200">
                <div>
                    <h4 class="font-semibold text-blue-900">
                        Kiểm tra dữ liệu trước khi cập nhật
                        (<span class="font-normal text-sm">(<span id="importPreviewCount">0</span> môn)</span>
                    </h4>
                    <p class="text-xs text-blue-800 mt-0.5">
                        Mã chuẩn dùng prefix: <code class="font-mono font-semibold" id="importPreviewPrefix">—</code>
                        · Mã đã có trong hệ thống sẽ được <strong>cập nhật</strong>, mã mới sẽ <strong>thêm mới</strong>.
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button" id="importClearBtn"
                            class="inline-flex items-center gap-1 px-3 py-2 bg-white border border-gray-300 text-gray-700 text-sm rounded-lg hover:bg-gray-50">
                        <i class="bi bi-x-circle"></i> Hủy / chọn lại
                    </button>
                    <button type="button" id="importConfirmBtn"
                            class="inline-flex items-center gap-1 px-4 py-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium rounded-lg disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="bi bi-check2-circle"></i> Xác nhận cập nhật
                    </button>
                </div>
            </div>
            <div class="overflow-x-auto max-h-[28rem] overflow-y-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-100 sticky top-0 z-10">
                        <tr class="text-left text-xs uppercase text-gray-600">
                            <th class="px-3 py-2">STT</th>
                            <th class="px-3 py-2">Mã trong file</th>
                            <th class="px-3 py-2">Mã chuẩn</th>
                            <th class="px-3 py-2">Tên môn học</th>
                            <th class="px-3 py-2">Viết tắt</th>
                            <th class="px-3 py-2">Màu</th>
                            <th class="px-3 py-2">Mô tả</th>
                            <th class="px-3 py-2">TC</th>
                            <th class="px-3 py-2">LT</th>
                            <th class="px-3 py-2">TH</th>
                            <th class="px-3 py-2">Tự học</th>
                            <th class="px-3 py-2">Thi</th>
                            <th class="px-3 py-2">Cấp độ</th>
                            <th class="px-3 py-2">Học kỳ</th>
                            <th class="px-3 py-2">Đánh giá</th>
                        </tr>
                    </thead>
                    <tbody id="importPreviewBody" class="divide-y divide-gray-200 bg-white"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- Form tạo 1 môn --}}
<div class="bg-white rounded-lg shadow-sm border p-6">
    <form method="POST" action="{{ route('subjects.store') }}" id="subjectForm">
        @csrf
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Thông tin cơ bản --}}
            <div class="lg:col-span-2">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Thông tin cơ bản (tạo từng môn)</h3>
            </div>
            <div>
                <x-form.input name="name" label="Tên môn học" :value="old('name')" required placeholder="Nhập tên môn học..." />
            </div>
            <div>
                <x-form.input name="code" label="Mã môn học" :value="old('code')" placeholder="Để trống để tự động tạo mã..." help="Chỉ mã ngắn (vd M009K2); hệ thống ghép mã ngành khi lưu" />
            </div>
            <div>
                <x-form.input name="abbreviation" label="Viết tắt" :value="old('abbreviation')" placeholder="VD: TTT" help="Để trống sẽ tự lấy chữ cái đầu (Thuốc thông thường → TTT). Dùng khi xuất lịch." />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Màu nhận diện</label>
                <div class="flex items-center gap-3">
                    <input type="color" id="color_picker" value="{{ old('color', '#4EA1FF') }}"
                           class="h-10 w-14 rounded border border-gray-300 cursor-pointer"
                           oninput="document.getElementById('color_hex').value=this.value.toUpperCase()">
                    <input type="text" name="color" id="color_hex" value="{{ old('color', '#4EA1FF') }}"
                           maxlength="7" placeholder="#4EA1FF"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                           oninput="if(/^#[0-9A-Fa-f]{6}$/.test(this.value)) document.getElementById('color_picker').value=this.value">
                </div>
                <p class="mt-1 text-xs text-gray-500">Dùng khi xuất lịch huấn luyện (ô môn tô màu theo mẫu Excel).</p>
                @error('color')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="training_system_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Hệ đào tạo <span class="text-red-500">*</span>
                </label>
                <div class="ui-select-field">
                    <select id="training_system_id" name="training_system_id" data-placeholder="Chọn hệ..." data-searchable="1" class="w-full" required>
                        <option value="">Chọn hệ đào tạo...</option>
                        @foreach($trainingSystems ?? [] as $id => $name)
                            <option value="{{ $id }}" @selected(old('training_system_id') == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label for="specialization_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Ngành đào tạo <span class="text-red-500">*</span>
                </label>
                <div class="ui-select-field">
                    <select id="specialization_id" name="specialization_id" data-placeholder="Chọn ngành (sau khi chọn hệ)..." data-searchable="1" class="w-full" required>
                        <option value="">Chọn ngành đào tạo...</option>
                        @foreach($specializations as $id => $name)
                            @php
                                $sysId = null;
                                foreach (($specializationsBySystem ?? []) as $sid => $map) {
                                    if (isset($map[$id])) { $sysId = $sid; break; }
                                }
                            @endphp
                            <option value="{{ $id }}" data-system="{{ $sysId }}" @selected(old('specialization_id') == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label for="faculty_code" class="block text-sm font-medium text-gray-700 mb-2">Khoa phụ trách</label>
                <div class="ui-select-field">
                    <select id="faculty_code" name="faculty_code" data-placeholder="Chưa phân công..." data-searchable="1" class="w-full">
                        <option value="">Chưa phân công...</option>
                        @foreach($facultyUnits ?? [] as $unit)
                            <option value="{{ $unit->faculty_code }}" @selected(old('faculty_code') == $unit->faculty_code)>
                                {{ $unit->faculty_code }} — {{ $unit->abbreviation ?: $unit->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <p class="mt-1 text-xs text-gray-500">Quyết định khoa nào được lọc GV/lịch cho môn này. Để trống sẽ tạm suy ra từ 2 ký tự cuối mã môn (nếu có).</p>
            </div>
            <div>
                <x-form.input name="absence_limit_percent" label="Ngưỡng vắng (%)" type="number" :value="old('absence_limit_percent')" min="0" max="100" placeholder="Để trống dùng mức chung của hệ thống" help="Vắng có phép không tính vào ngưỡng này. Vượt ngưỡng chỉ cảnh báo, không tự đánh trượt." />
            </div>
            <div>
                <x-form.input name="credits" label="Số tín chỉ" type="number" :value="old('credits', 1)" required min="1" max="20" />
            </div>
            <div class="lg:col-span-2">
                <x-form.textarea name="description" label="Mô tả" :value="old('description')" placeholder="Nhập mô tả môn học..." rows="3" />
            </div>
            {{-- Thông tin học tập --}}
            <div class="lg:col-span-2">
                <h3 class="text-lg font-medium text-gray-900 mb-4 mt-6">Thông tin học tập</h3>
            </div>
            <div>
                <x-form.input name="theory_hours" label="Số tiết lý thuyết" type="number" :value="old('theory_hours', 0)" required min="0" max="500" />
            </div>
            <div>
                <x-form.input name="practice_hours" label="Số tiết thực hành" type="number" :value="old('practice_hours', 0)" required min="0" max="500" />
            </div>
            <div>
                <x-form.input name="self_study_hours" label="Số tiết tự học" type="number" :value="old('self_study_hours', 0)" required min="0" max="500" />
            </div>
            <div>
                <x-form.input name="exam_hours" label="Số tiết thi/kiểm tra" type="number" :value="old('exam_hours', 0)" required min="0" max="500" />
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Tổng số tiết</label>
                <div class="px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600" id="totalHours">0 tiết</div>
            </div>
            {{-- Thông tin khác --}}
            <div class="lg:col-span-2">
                <h3 class="text-lg font-medium text-gray-900 mb-4 mt-6">Thông tin khác</h3>
            </div>
            <div>
                <x-form.select name="level" label="Cấp độ" :options="$levels" :value="old('level', 'basic')" required />
            </div>
            <div>
                <x-form.select name="assessment_method" label="Phương pháp đánh giá" :options="$assessmentMethods" :value="old('assessment_method', 'exam')" required />
            </div>
            <div>
                <x-form.select name="semester" label="Học kỳ" :options="$semesters" :value="old('semester')" placeholder="Chọn học kỳ..." />
            </div>
            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">Môn học tiên quyết</label>
                <div id="prerequisitesContainer">
                    @if(old('prerequisites'))
                        @foreach(old('prerequisites') as $index => $prerequisite)
                            <div class="flex items-center space-x-2 mb-2">
                                <input type="text" name="prerequisites[]" value="{{ $prerequisite }}" class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="Nhập tên môn học tiên quyết...">
                                <button type="button" class="px-3 py-2 bg-red-500 text-white rounded-md hover:bg-red-600" onclick="removePrerequisite(this)"><i class="bi bi-trash"></i></button>
                            </div>
                        @endforeach
                    @endif
                </div>
                <button type="button" class="mt-2 px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600" onclick="addPrerequisite()"><i class="bi bi-plus mr-2"></i>Thêm môn học tiên quyết</button>
            </div>
            <div class="lg:col-span-2">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-form.checkbox name="is_required" label="Môn học bắt buộc" :checked="old('is_required', true)" />
                    </div>
                    <div>
                        <x-form.checkbox name="is_active" label="Kích hoạt ngay" :checked="old('is_active', true)" />
                    </div>
                </div>
            </div>
        </div>
        <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
            <a href="{{ route('subjects.index') }}" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Hủy</a>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700"><i class="bi bi-check mr-2"></i>Tạo môn học</button>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
<script>
(function () {
    'use strict';
    // IIFE: tránh Turbo redeclare const (prefixMap, …) trên global lexical scope

    // Calculate total hours
    function calculateTotalHours() {
        const theory = parseInt(document.querySelector('#subjectForm input[name="theory_hours"]').value) || 0;
        const practice = parseInt(document.querySelector('#subjectForm input[name="practice_hours"]').value) || 0;
        const selfStudy = parseInt(document.querySelector('#subjectForm input[name="self_study_hours"]').value) || 0;
        const exam = parseInt(document.querySelector('#subjectForm input[name="exam_hours"]').value) || 0;

        const total = theory + practice + selfStudy + exam;
        document.getElementById('totalHours').textContent = total + ' tiết';
    }

    const theoryEl = document.querySelector('#subjectForm input[name="theory_hours"]');
    const practiceEl = document.querySelector('#subjectForm input[name="practice_hours"]');
    const selfStudyEl = document.querySelector('#subjectForm input[name="self_study_hours"]');
    const examEl = document.querySelector('#subjectForm input[name="exam_hours"]');
    theoryEl?.addEventListener('input', calculateTotalHours);
    practiceEl?.addEventListener('input', calculateTotalHours);
    selfStudyEl?.addEventListener('input', calculateTotalHours);
    examEl?.addEventListener('input', calculateTotalHours);
    if (document.getElementById('totalHours')) calculateTotalHours();

    // Cascade Hệ → Ngành (Tom Select nếu có)
    const specsBySystem = @json($specializationsBySystem ?? []);
    const systemSel = document.getElementById('training_system_id');
    const specSel = document.getElementById('specialization_id');

    function rebuildSpecOptions(systemId, keepId) {
        if (!specSel) return;
        const map = systemId && specsBySystem[systemId] ? specsBySystem[systemId] : {};
        const entries = Object.entries(map || {});
        // Tom Select destroy/rebuild
        if (specSel.tomselect) {
            const ts = specSel.tomselect;
            ts.clear(true);
            ts.clearOptions();
            ts.addOption({ value: '', text: 'Chọn ngành đào tạo...' });
            entries.forEach(([id, name]) => ts.addOption({ value: String(id), text: name }));
            ts.refreshOptions(false);
            if (keepId && map[keepId]) {
                ts.setValue(String(keepId), true);
            }
        } else {
            specSel.innerHTML = '<option value="">Chọn ngành đào tạo...</option>';
            entries.forEach(([id, name]) => {
                const opt = document.createElement('option');
                opt.value = id;
                opt.textContent = name;
                if (keepId && String(keepId) === String(id)) opt.selected = true;
                specSel.appendChild(opt);
            });
        }
    }

    systemSel?.addEventListener('change', function () {
        rebuildSpecOptions(this.value, null);
    });
    // Khi Tom Select bọc select — listen event change trên wrapper
    document.addEventListener('change', function (e) {
        if (e.target && e.target.id === 'training_system_id') {
            rebuildSpecOptions(e.target.value, null);
        }
    });
    // Initial: nếu đã chọn hệ (old input) thì lọc ngành
    if (systemSel?.value) {
        rebuildSpecOptions(systemSel.value, specSel?.value || null);
    }

    function addPrerequisite() {
        const container = document.getElementById('prerequisitesContainer');
        const div = document.createElement('div');
        div.className = 'flex items-center space-x-2 mb-2';
        div.innerHTML = `
            <input type="text" name="prerequisites[]"
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                   placeholder="Nhập tên môn học tiên quyết...">
            <button type="button" class="px-3 py-2 bg-red-500 text-white rounded-md hover:bg-red-600"
                    onclick="removePrerequisite(this)"><i class="bi bi-trash"></i></button>`;
        container.appendChild(div);
    }

    function removePrerequisite(button) {
        button.parentElement.remove();
    }

    document.querySelector('#subjectForm input[name="name"]')?.addEventListener('input', function () {
        const codeInput = document.querySelector('#subjectForm input[name="code"]');
        if (codeInput && !codeInput.value) {
            codeInput.value = this.value
                .normalize('NFD').replace(/[\u0300-\u036f]/g, '')
                .replace(/đ/g, 'd').replace(/Đ/g, 'D')
                .replace(/[^a-zA-Z0-9\s]/g, '').replace(/\s+/g, '_')
                .toUpperCase().substring(0, 10);
        }
    });

    // ===== Import Excel: preview → confirm =====
    const prefixMap = @json($specializationPrefixMap ?? []);
    const importSpec = document.getElementById('import_specialization_id');
    const importPrefix = document.getElementById('import_code_prefix');
    const importFile = document.getElementById('import_file');
    const importPreviewWrap = document.getElementById('importPreviewWrap');
    const importPreviewBody = document.getElementById('importPreviewBody');
    const importPreviewCount = document.getElementById('importPreviewCount');
    const importPreviewPrefix = document.getElementById('importPreviewPrefix');
    const importConfirmBtn = document.getElementById('importConfirmBtn');
    const importClearBtn = document.getElementById('importClearBtn');
    const importMessage = document.getElementById('importMessage');
    const importFileMeta = document.getElementById('importFileMeta');
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content
        || document.querySelector('input[name="_token"]')?.value
        || '{{ csrf_token() }}';

    let prefixTouched = false;
    let parsedSubjects = [];

    function escapeHtml(str) {
        return String(str ?? '')
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function currentPrefix() {
        return (importPrefix.value || '').trim().toUpperCase().replace(/[^A-Z0-9_]/g, '');
    }

    function buildFullCode(prefix, localCode) {
        const p = (prefix || '').toUpperCase().replace(/^_+|_+$/g, '');
        const c = String(localCode || '').trim();
        if (!c) return p || '';
        if (!p) return c;
        const upper = c.toUpperCase();
        if (upper.startsWith(p + '_')) return upper;
        if (/^[AB]_[A-Z0-9]+_/i.test(c)) return upper;
        return p + '_' + c;
    }

    function showImportMessage(html, type) {
        const colors = {
            error: 'bg-red-50 text-red-800 border border-red-200',
            success: 'bg-green-50 text-green-800 border border-green-200',
            warning: 'bg-amber-50 text-amber-900 border border-amber-200',
            info: 'bg-blue-50 text-blue-800 border border-blue-200',
        };
        importMessage.className = 'mt-3 p-3 rounded-lg text-sm ' + (colors[type] || colors.info);
        importMessage.innerHTML = html;
        importMessage.classList.remove('hidden');
    }

    function hideImportMessage() {
        importMessage.classList.add('hidden');
        importMessage.innerHTML = '';
    }

    function applySuggestedPrefix() {
        if (prefixTouched && importPrefix.value) {
            refreshPreviewIfNeeded();
            return;
        }
        const id = importSpec.value;
        if (id && prefixMap[id]) {
            importPrefix.value = prefixMap[id];
        }
        refreshPreviewIfNeeded();
    }

    function refreshPreviewIfNeeded() {
        if (parsedSubjects.length) {
            renderPreview();
        }
    }

    function clearImportPreview() {
        parsedSubjects = [];
        importPreviewBody.innerHTML = '';
        importPreviewWrap.classList.add('hidden');
        importPreviewCount.textContent = '0';
        importFile.value = '';
        importFileMeta.classList.add('hidden');
        importFileMeta.textContent = '';
        importConfirmBtn.disabled = true;
        hideImportMessage();
    }

    function renderPreview() {
        const prefix = currentPrefix();
        importPreviewPrefix.textContent = prefix || '—';
        importPreviewBody.innerHTML = '';

        parsedSubjects.forEach((row, index) => {
            const fullCode = buildFullCode(prefix, row.code);
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50';
            tr.innerHTML = `
                <td class="px-3 py-2 text-gray-700">${index + 1}</td>
                <td class="px-3 py-2 font-mono text-xs">${escapeHtml(row.code) || '<span class="text-gray-400 italic">tự tạo</span>'}</td>
                <td class="px-3 py-2 font-mono text-xs font-semibold text-blue-700">${escapeHtml(fullCode)}</td>
                <td class="px-3 py-2 font-medium text-gray-900">${escapeHtml(row.name)}</td>
                <td class="px-3 py-2 font-mono text-xs font-semibold text-amber-800">${escapeHtml(row.abbreviation) || '—'}</td>
                <td class="px-3 py-2">${row.color ? `<span class="inline-flex items-center gap-1 font-mono text-xs"><span style="display:inline-block;width:12px;height:12px;border-radius:3px;background:${escapeHtml(row.color)};border:1px solid #cbd5e1"></span>${escapeHtml(row.color)}</span>` : '—'}</td>
                <td class="px-3 py-2 text-gray-600 max-w-[14rem] truncate" title="${escapeHtml(row.description)}">${escapeHtml(row.description) || '—'}</td>
                <td class="px-3 py-2 text-center">${row.credits}</td>
                <td class="px-3 py-2 text-center">${row.theory_hours}</td>
                <td class="px-3 py-2 text-center">${row.practice_hours}</td>
                <td class="px-3 py-2 text-center">${row.self_study_hours}</td>
                <td class="px-3 py-2 text-center">${row.exam_hours}</td>
                <td class="px-3 py-2">${escapeHtml(row.level)}</td>
                <td class="px-3 py-2">${escapeHtml(row.semester) || '—'}</td>
                <td class="px-3 py-2">${escapeHtml(row.assessment_method)}</td>`;
            importPreviewBody.appendChild(tr);
        });

        importPreviewCount.textContent = String(parsedSubjects.length);
        importPreviewWrap.classList.remove('hidden');
        importConfirmBtn.disabled = parsedSubjects.length === 0 || !importSpec.value || !prefix;
    }

    function parseExcelFile(file) {
        if (typeof XLSX === 'undefined') {
            showImportMessage('Không tải được thư viện đọc Excel. Vui lòng tải lại trang.', 'error');
            return;
        }

        const reader = new FileReader();
        showImportMessage('Đang đọc file Excel...', 'info');

        reader.onload = function (e) {
            try {
                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: 'array' });
                let sheet = workbook.Sheets['Dữ liệu môn học'];
                if (!sheet) {
                    sheet = workbook.Sheets[workbook.SheetNames[0]];
                }
                const jsonData = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '' });

                // Template mới: Mã | Tên | Viết tắt | Màu | Mô tả | TC | LT | TH | Tự học | Thi | Cấp độ | Học kỳ | ĐG
                // Template cũ: Mã | Tên | [Viết tắt?] | Mô tả | ...
                const headerRow = jsonData[0] || [];
                const hasAbbreviationCol = headerRow.some(function (h) {
                    return /vi[eế]t\s*t[aắ]t|abbreviation/i.test(String(h || ''));
                });
                const hasColorCol = headerRow.some(function (h) {
                    return /^m[aà]u|color/i.test(String(h || '').trim());
                });
                let col = 2; // sau Mã, Tên

                parsedSubjects = [];
                for (let i = 1; i < jsonData.length; i++) {
                    const row = jsonData[i];
                    if (!row || row.length === 0) continue;
                    const name = String(row[1] ?? '').trim();
                    if (!name) continue;

                    col = 2;
                    let abbreviation = '';
                    let color = '';
                    if (hasAbbreviationCol) {
                        abbreviation = String(row[col++] ?? '').trim().toUpperCase();
                    }
                    if (hasColorCol) {
                        color = String(row[col++] ?? '').trim().toUpperCase();
                        if (color && color.charAt(0) !== '#') color = '#' + color;
                    }

                    parsedSubjects.push({
                        code: String(row[0] ?? '').trim(),
                        name: name,
                        abbreviation: abbreviation,
                        color: color,
                        description: String(row[col++] ?? '').trim(),
                        credits: parseInt(row[col++], 10) || 1,
                        theory_hours: parseInt(row[col++], 10) || 0,
                        practice_hours: parseInt(row[col++], 10) || 0,
                        self_study_hours: parseInt(row[col++], 10) || 0,
                        exam_hours: parseInt(row[col++], 10) || 0,
                        level: String(row[col++] ?? 'Cơ bản').trim() || 'Cơ bản',
                        semester: String(row[col++] ?? '').trim(),
                        assessment_method: String(row[col++] ?? 'Thi viết').trim() || 'Thi viết',
                    });
                }

                if (!parsedSubjects.length) {
                    showImportMessage('File không có dòng môn học hợp lệ (cần cột Tên môn học).', 'error');
                    importPreviewWrap.classList.add('hidden');
                    return;
                }

                hideImportMessage();
                renderPreview();
                showImportMessage(
                    'Đã đọc <strong>' + parsedSubjects.length + '</strong> môn. Kiểm tra bảng bên dưới rồi bấm <strong>Xác nhận cập nhật</strong>.',
                    'info'
                );
            } catch (err) {
                console.error(err);
                showImportMessage('Lỗi đọc file Excel: ' + escapeHtml(err.message), 'error');
            }
        };

        reader.onerror = function () {
            showImportMessage('Không đọc được file.', 'error');
        };

        reader.readAsArrayBuffer(file);
    }

    function confirmImport() {
        const specializationId = importSpec.value;
        const prefix = currentPrefix();

        if (!specializationId) {
            showImportMessage('Vui lòng chọn ngành đào tạo.', 'error');
            return;
        }
        if (!prefix) {
            showImportMessage('Vui lòng nhập mã ngành (prefix).', 'error');
            return;
        }
        if (!parsedSubjects.length) {
            showImportMessage('Chưa có dữ liệu để cập nhật.', 'error');
            return;
        }

        uiConfirm('Xác nhận import ' + parsedSubjects.length + ' môn học với mã ngành ' + prefix + '?', {
            title: 'Xác nhận import',
            confirmText: 'Import',
        }).then((ok) => {
            if (!ok) return;

            importConfirmBtn.disabled = true;
            importConfirmBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Đang cập nhật...';
            showImportMessage('Đang lưu dữ liệu...', 'info');

            fetch('/specializations/' + specializationId + '/subjects/import', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    code_prefix: prefix,
                    subjects: parsedSubjects,
                }),
            })
                .then(async (res) => {
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.success) {
                        throw new Error(data.message || 'Import thất bại.');
                    }
                    return data;
                })
                .then((data) => {
                    showImportMessage(escapeHtml(data.message || 'Import thành công!'), 'success');
                    Notify.success(data.message || 'Import thành công!');
                    setTimeout(function () {
                        window.location.href = @json(route('subjects.index')) + '?specialization_id=' + encodeURIComponent(specializationId);
                    }, 1200);
                })
                .catch((err) => {
                    showImportMessage(escapeHtml(err.message || 'Có lỗi xảy ra.'), 'error');
                    Notify.error(err.message || 'Có lỗi xảy ra.');
                    importConfirmBtn.disabled = false;
                    importConfirmBtn.innerHTML = '<i class="bi bi-check2-circle"></i> Xác nhận cập nhật';
                });
        });
    }

    if (importSpec && importPrefix) {
        importPrefix.addEventListener('input', function () {
            prefixTouched = true;
            this.value = this.value.toUpperCase().replace(/[^A-Z0-9_]/g, '');
            refreshPreviewIfNeeded();
        });
        importSpec.addEventListener('change', function () {
            prefixTouched = false;
            applySuggestedPrefix();
        });
        applySuggestedPrefix();

        if (typeof window.initTomSelects === 'function') {
            window.initTomSelects(document.getElementById('importExcelPanel'));
        }
    }

    importFile?.addEventListener('change', function () {
        const file = this.files && this.files[0];
        hideImportMessage();
        if (!file) return;

        const name = (file.name || '').toLowerCase();
        if (!name.endsWith('.xlsx') && !name.endsWith('.xls')) {
            showImportMessage('Chỉ chấp nhận file Excel (.xlsx, .xls).', 'error');
            this.value = '';
            return;
        }
        if (file.size > 10 * 1024 * 1024) {
            showImportMessage('File tối đa 10MB.', 'error');
            this.value = '';
            return;
        }
        if (!importSpec.value) {
            showImportMessage('Hãy chọn ngành đào tạo trước khi chọn file.', 'error');
            this.value = '';
            return;
        }
        if (!currentPrefix()) {
            applySuggestedPrefix();
        }

        importFileMeta.textContent = 'File: ' + file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
        importFileMeta.classList.remove('hidden');
        parseExcelFile(file);
    });

    importClearBtn?.addEventListener('click', clearImportPreview);
    importConfirmBtn?.addEventListener('click', confirmImport);
})();
</script>
@endpush
