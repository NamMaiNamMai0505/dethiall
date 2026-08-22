@extends('layouts.admin')

@section('title', 'Tạo khóa LMS')
@section('page-title', 'Tạo khóa LMS')

@section('content')
    <x-breadcrumb :items="[
        ['title' => 'Trang chủ'],
        ['title' => 'LMS', 'url' => route('lms.hub')],
        ['title' => 'Khóa học', 'url' => route('lms.courses.index')],
        ['title' => 'Tạo thủ công'],
    ]" />

    <div class="max-w-3xl mx-auto">
        <div class="bg-white rounded-xl border shadow-sm p-6">
            <h1 class="text-xl font-bold text-slate-900 mb-1">Tạo khóa học LMS thủ công</h1>
            <p class="text-sm text-slate-500 mb-5">
                Chọn <strong>môn, lớp, năm học và giảng viên</strong>. Với khóa sinh từ lịch đào tạo, nên dùng màn hình <a href="{{ route('lms.provisioning.index') }}" class="font-semibold text-blue-700 hover:underline">Khởi tạo từ lịch</a>.
            </p>

            {{-- Step indicator --}}
            <ol class="flex flex-wrap gap-2 mb-6 text-xs font-semibold" id="wizard-steps">
                <li class="wizard-step is-active px-3 py-1.5 rounded-full bg-blue-600 text-white" data-step="1">1. Môn &amp; lớp</li>
                <li class="wizard-step px-3 py-1.5 rounded-full bg-slate-100 text-slate-600" data-step="2">2. Giảng viên</li>
                <li class="wizard-step px-3 py-1.5 rounded-full bg-slate-100 text-slate-600" data-step="3">3. Xác nhận &amp; tạo</li>
            </ol>

            <form method="POST" action="{{ route('lms.courses.store') }}" class="space-y-4" id="wizard-form">
                @csrf

                {{-- Step 1 --}}
                <div class="wizard-panel" data-panel="1">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Môn học <span class="text-red-500">*</span></label>
                            <div class="ui-select-field">
                                <select name="subject_id" id="wiz_subject_id" required data-searchable="1" data-placeholder="Chọn môn...">
                                    <option value="">— Chọn môn —</option>
                                    @foreach($subjects as $s)
                                        <option value="{{ $s->id }}"
                                                data-name="{{ $s->name }}"
                                                data-code="{{ $s->display_code ?? $s->code }}"
                                                @selected(old('subject_id') == $s->id)>
                                            {{ $s->display_code ?? $s->code }} — {{ $s->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('subject_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>

                        <label class="flex items-center gap-2 text-sm font-medium text-slate-700">
                            <input type="checkbox" name="is_standalone" value="1" id="is_standalone" @checked(old('is_standalone'))>
                            Lớp học phần độc lập (không map lớp hành chính — ghi danh tay)
                        </label>

                        <div id="class_field">
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Lớp hành chính <span class="text-slate-400 font-normal" id="class_required_hint">*</span></label>
                            <div class="ui-select-field">
                                <select name="class_id" id="wiz_class_id" data-searchable="1" data-placeholder="Chọn lớp...">
                                    <option value="">— Chọn lớp —</option>
                                    @foreach($classes as $c)
                                        <option value="{{ $c->id }}"
                                                data-name="{{ $c->name }}"
                                                data-code="{{ $c->code }}"
                                                @selected(old('class_id') == $c->id)>
                                            {{ $c->name }}@if($c->code) ({{ $c->code }})@endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @error('class_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                            <p class="text-xs text-teal-700 mt-1" id="class_student_count_hint"></p>
                        </div>

                        <div class="flex justify-end">
                            <button type="button" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold" data-next="2">
                                Tiếp: chọn GV →
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Step 2 --}}
                <div class="wizard-panel hidden" data-panel="2">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Giảng viên phụ trách</label>
                            <div class="ui-select-field">
                                <select name="instructor_id" id="wiz_instructor_id" data-searchable="1" data-placeholder="Chọn giảng viên...">
                                    <option value="">— Chưa gán giảng viên —</option>
                                    @foreach(($instructors ?? []) as $ins)
                                        <option value="{{ $ins->id }}" @selected(old('instructor_id') == $ins->id)>
                                            {{ $ins->name }}@if($ins->code) ({{ $ins->code }})@endif
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <p class="text-xs text-slate-400 mt-1">
                                Danh sách gợi ý theo phân công môn và giới hạn đúng phạm vi đơn vị.
                            </p>
                            @error('instructor_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div class="grid sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Mã khóa (tuỳ chọn)</label>
                                <input type="text" name="code" value="{{ old('code') }}" class="w-full border rounded-lg text-sm px-3 py-2 font-mono" id="wiz_code">
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Mã học phần / section</label>
                                <input type="text" name="section_code" value="{{ old('section_code') }}" class="w-full border rounded-lg text-sm px-3 py-2 font-mono" placeholder="VD: HP-2026-01">
                            </div>
                        </div>
                        <div class="grid sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Năm học</label>
                                <select name="academic_year_id" class="w-full border rounded-lg text-sm px-3 py-2" data-searchable="1">
                                    <option value="">— Chưa xác định —</option>
                                    @foreach($academicYears as $year)
                                        <option value="{{ $year->id }}" @selected(old('academic_year_id', $year->is_current ? $year->id : null) == $year->id)>{{ $year->code }}{{ $year->is_current ? ' · hiện hành' : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-semibold text-slate-700 mb-1">Học kỳ</label>
                                <select name="term" class="w-full border rounded-lg text-sm px-3 py-2">
                                    <option value="">— Chưa xác định —</option>
                                    @foreach(['semester_1' => 'Học kỳ 1', 'semester_2' => 'Học kỳ 2', 'semester_3' => 'Học kỳ 3', 'semester_4' => 'Học kỳ 4', 'semester_5' => 'Học kỳ 5', 'semester_6' => 'Học kỳ 6', 'summer' => 'Học kỳ hè'] as $termValue => $termLabel)
                                        <option value="{{ $termValue }}" @selected(old('term') === $termValue)>{{ $termLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="flex justify-between gap-2">
                            <button type="button" class="px-4 py-2 border rounded-lg text-sm" data-prev="1">← Quay lại</button>
                            <button type="button" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold" data-next="3">
                                Tiếp: xác nhận →
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Step 3 --}}
                <div class="wizard-panel hidden" data-panel="3">
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Tiêu đề khóa học <span class="text-red-500">*</span></label>
                            <input type="text" name="title" id="wiz_title" value="{{ old('title') }}" required
                                   class="w-full border rounded-lg text-sm px-3 py-2" placeholder="Tự gợi ý từ môn · lớp">
                            @error('title')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Trạng thái</label>
                            <select name="status" class="w-full border rounded-lg text-sm px-3 py-2">
                                <option value="draft" @selected(old('status', \Modules\Lms\Support\LmsSettings::courseStatus()) === 'draft')>Nháp</option>
                                <option value="published" @selected(old('status', \Modules\Lms\Support\LmsSettings::courseStatus()) === 'published')>Đang mở (sync members ngay)</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1">Mô tả</label>
                            <textarea name="description" rows="3" class="w-full border rounded-lg text-sm px-3 py-2">{{ old('description') }}</textarea>
                        </div>

                        <div class="rounded-lg bg-slate-50 border border-slate-100 p-4 text-sm space-y-1" id="wiz_summary">
                            <p class="font-semibold text-slate-800 mb-2">Tóm tắt trước khi tạo</p>
                            <p><span class="text-slate-500">Môn:</span> <span id="sum_subject">—</span></p>
                            <p><span class="text-slate-500">Lớp:</span> <span id="sum_class">—</span></p>
                            <p><span class="text-slate-500">GV:</span> <span id="sum_instructor">—</span></p>
                            <p class="text-xs text-teal-700 mt-2">
                                <i class="bi bi-people"></i> <span id="sum_student_count">Sẽ tự đồng bộ học viên theo lớp và giảng viên đã chọn.</span>
                            </p>
                        </div>

                        <div class="flex justify-between gap-2 pt-1">
                            <button type="button" class="px-4 py-2 border rounded-lg text-sm" data-prev="2">← Quay lại</button>
                            <div class="flex gap-2">
                                <a href="{{ route('lms.courses.index') }}" class="px-4 py-2 border rounded-lg text-sm">Huỷ</a>
                                <button type="submit" class="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-semibold">
                                    Tạo khóa &amp; đồng bộ TV
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    function showStep(n) {
        document.querySelectorAll('.wizard-panel').forEach(function (p) {
            p.classList.toggle('hidden', String(p.dataset.panel) !== String(n));
        });
        document.querySelectorAll('.wizard-step').forEach(function (s) {
            var active = String(s.dataset.step) === String(n);
            s.classList.toggle('is-active', active);
            s.classList.toggle('bg-blue-600', active);
            s.classList.toggle('text-white', active);
            s.classList.toggle('bg-slate-100', !active);
            s.classList.toggle('text-slate-600', !active);
        });
        if (n === 3) refreshSummary();
    }

    document.querySelectorAll('[data-next]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (btn.dataset.next === '2') {
                var subj = document.getElementById('wiz_subject_id');
                if (!subj || !subj.value) {
                    window.PortalPopup.error('Chọn môn học trước.');
                    return;
                }
                var stand = document.getElementById('is_standalone');
                var cls = document.getElementById('wiz_class_id');
                if ((!stand || !stand.checked) && cls && !cls.value) {
                    window.PortalPopup.error('Chọn lớp hoặc bật lớp độc lập.');
                    return;
                }
            }
            showStep(btn.dataset.next);
        });
    });
    document.querySelectorAll('[data-prev]').forEach(function (btn) {
        btn.addEventListener('click', function () { showStep(btn.dataset.prev); });
    });

    var stand = document.getElementById('is_standalone');
    if (stand) {
        stand.addEventListener('change', function () {
            var field = document.getElementById('class_field');
            var hint = document.getElementById('class_required_hint');
            if (field) field.style.opacity = stand.checked ? '0.65' : '1';
            if (hint) hint.style.display = stand.checked ? 'none' : '';
        });
    }

    function selectedText(sel) {
        if (!sel || !sel.value) return '—';
        var opt = sel.options[sel.selectedIndex];
        return opt ? opt.textContent.trim() : '—';
    }

    function suggestTitle() {
        var subj = document.getElementById('wiz_subject_id');
        var cls = document.getElementById('wiz_class_id');
        var title = document.getElementById('wiz_title');
        if (!title || title.dataset.touched === '1') return;
        var sn = '', cn = '';
        if (subj && subj.value) {
            var so = subj.options[subj.selectedIndex];
            sn = so ? (so.dataset.name || so.textContent.split('—').pop().trim()) : '';
        }
        if (cls && cls.value) {
            var co = cls.options[cls.selectedIndex];
            cn = co ? (co.dataset.name || co.textContent.trim()) : '';
        }
        if (sn && cn) title.value = sn + ' · ' + cn;
        else if (sn) title.value = sn;
    }

    ['wiz_subject_id', 'wiz_class_id'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('change', suggestTitle);
    });
    var titleEl = document.getElementById('wiz_title');
    if (titleEl) {
        titleEl.addEventListener('input', function () { titleEl.dataset.touched = '1'; });
    }

    function refreshSummary() {
        document.getElementById('sum_subject').textContent = selectedText(document.getElementById('wiz_subject_id'));
        var stand = document.getElementById('is_standalone');
        document.getElementById('sum_class').textContent = (stand && stand.checked)
            ? 'Độc lập (không map lớp)'
            : selectedText(document.getElementById('wiz_class_id'));
        document.getElementById('sum_instructor').textContent = selectedText(document.getElementById('wiz_instructor_id'));
        suggestTitle();
        refreshStudentCount();
    }

    // Sprint 44 / C1 — nêu rõ sẽ ghi danh bao nhiêu học viên trước khi tạo,
    // thay vì hứa chung chung "tự đồng bộ". Đếm bằng đúng điều kiện server
    // dùng khi ghi danh thật (xem CourseController::classStudentCount()).
    var classCountUrl = @json(route('lms.courses.class-student-count'));
    function refreshStudentCount() {
        var stand = document.getElementById('is_standalone');
        var cls = document.getElementById('wiz_class_id');
        var hint = document.getElementById('class_student_count_hint');
        var summary = document.getElementById('sum_student_count');

        if (stand && stand.checked) {
            if (hint) hint.textContent = '';
            if (summary) summary.textContent = 'Lớp học phần độc lập — ghi danh học viên thủ công sau khi tạo.';
            return;
        }
        if (!cls || !cls.value) {
            if (hint) hint.textContent = '';
            if (summary) summary.textContent = 'Chọn lớp để xem sẽ ghi danh bao nhiêu học viên.';
            return;
        }

        fetch(classCountUrl + '?class_id=' + encodeURIComponent(cls.value), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) { return r.ok ? r.json() : null; }).then(function (data) {
            var count = data && typeof data.count === 'number' ? data.count : null;
            var text = count === null
                ? 'Không lấy được số học viên — vẫn tạo được, kiểm tra lại sau.'
                : 'Sẽ ghi danh ' + count + ' học viên của lớp này.';
            if (hint) hint.textContent = count === null ? '' : text;
            if (summary) summary.textContent = text;
        }).catch(function () {
            if (summary) summary.textContent = 'Không lấy được số học viên — vẫn tạo được, kiểm tra lại sau.';
        });
    }

    var wizClassSel = document.getElementById('wiz_class_id');
    if (wizClassSel) wizClassSel.addEventListener('change', refreshStudentCount);
    if (stand) stand.addEventListener('change', refreshStudentCount);

    // Load instructors for subject via existing suggest (optional AJAX — fallback list full)
    var subjectSel = document.getElementById('wiz_subject_id');
    if (subjectSel) {
        subjectSel.addEventListener('change', function () {
            var sid = subjectSel.value;
            var url = @json(route('lms.courses.suggest-instructors'));
            if (!sid) return;
            fetch(url + '?subject_id=' + encodeURIComponent(sid), {
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            }).then(function (r) { return r.ok ? r.json() : null; }).then(function (data) {
                if (!data || !data.data) return;
                var sel = document.getElementById('wiz_instructor_id');
                if (!sel) return;
                var cur = sel.value;
                sel.innerHTML = '<option value="">— Chưa gán giảng viên —</option>';
                data.data.forEach(function (ins) {
                    var o = document.createElement('option');
                    o.value = ins.id;
                    o.textContent = ins.name + (ins.code ? ' (' + ins.code + ')' : '');
                    if (String(cur) === String(ins.id)) o.selected = true;
                    sel.appendChild(o);
                });
                if (sel.tomselect) {
                    sel.tomselect.destroy();
                    if (window.initTomSelects) window.initTomSelects(sel.parentElement || document);
                }
            }).catch(function () {});
        });
    }

    // If validation errors, jump to step 3
    @if($errors->any())
        showStep(3);
    @endif
})();
</script>
@endpush
