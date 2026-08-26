@extends('layouts.admin')
@section('title','Rút đề thi')
@section('page-title','Rút đề thi')
@section('content')
@include('partials.module-menu', ['module' => 'exam'])
<style>
    /* The history table gains four columns via JavaScript; keep all 11 columns aligned. */
    table.min-w-\[980px\].table-fixed { table-layout: fixed !important; min-width: 1450px; }
    table.min-w-\[980px\].table-fixed th { white-space: nowrap; }
    table.min-w-\[980px\].table-fixed td a { white-space: nowrap; }
    table.min-w-\[980px\].table-fixed th, table.min-w-\[980px\].table-fixed td { vertical-align: middle; overflow: hidden; text-overflow: ellipsis; }
    form[action*="essay-exams/draw"] { position: relative; overflow: visible; border: 1px solid #dbeafe !important; border-radius: 24px !important; background: linear-gradient(145deg,#ffffff 0%,#f8fbff 55%,#eff6ff 100%); box-shadow: 0 18px 45px rgba(30,64,175,.12); }
    form[action*="essay-exams/draw"]::before { content: ""; position: absolute; width: 280px; height: 280px; right: -120px; top: -150px; border-radius: 999px; background: radial-gradient(circle,#bfdbfe 0%,rgba(219,234,254,0) 70%); pointer-events: none; }
    form[action*="essay-exams/draw"] label { display:block; color:#334155; font-size:.88rem; font-weight:650; letter-spacing:.01em; }
    form[action*="essay-exams/draw"] select, form[action*="essay-exams/draw"] input { margin-top:.45rem; min-height:46px; border:1px solid #cbd5e1; border-radius:13px; background:rgba(255,255,255,.9); transition: border-color .2s, box-shadow .2s, transform .2s; }
    form[action*="essay-exams/draw"] select:focus, form[action*="essay-exams/draw"] input:focus { border-color:#3b82f6; box-shadow:0 0 0 4px rgba(59,130,246,.13); outline:0; }
    form[action*="essay-exams/draw"] .ts-wrapper { margin-top:.45rem; }
    form[action*="essay-exams/draw"] .ts-wrapper .ts-control { min-height:46px; border:1px solid #cbd5e1; border-radius:13px; background:rgba(255,255,255,.92); box-shadow:none; padding:.72rem 2.5rem .72rem .9rem; }
    form[action*="essay-exams/draw"] .ts-wrapper.focus .ts-control, form[action*="essay-exams/draw"] .ts-wrapper.dropdown-active .ts-control { border-color:#3b82f6; box-shadow:0 0 0 4px rgba(59,130,246,.13); }
    form[action*="essay-exams/draw"] .ts-wrapper .ts-dropdown { z-index:80; border:1px solid #bfdbfe; border-radius:14px; box-shadow:0 16px 32px rgba(15,23,42,.16); overflow:hidden; }
    form[action*="essay-exams/draw"] .ts-wrapper .option, form[action*="essay-exams/draw"] .ts-wrapper .no-results { padding:.7rem .85rem; }
    form[action*="essay-exams/draw"] .ts-wrapper .option.active { background:#eff6ff; color:#1d4ed8; }
    form[action*="essay-exams/draw"] .date-input-field .flatpickr-calendar,
    form[action*="essay-exams/draw"] .flatpickr-calendar { width:min(360px, calc(100vw - 24px)) !important; min-width:0 !important; }
    form[action*="essay-exams/draw"] button { min-height:50px; border-radius:14px; background:linear-gradient(135deg,#2563eb,#06b6d4); box-shadow:0 10px 22px rgba(37,99,235,.22); font-weight:750; letter-spacing:.01em; transition:transform .2s, box-shadow .2s; }
    form[action*="essay-exams/draw"] button:hover { transform:translateY(-2px); box-shadow:0 14px 26px rgba(37,99,235,.3); }
    .draw-form-hero { position:relative; z-index:1; grid-column:1 / -1; display:flex; align-items:center; justify-content:space-between; gap:1rem; margin:-.25rem -.25rem .4rem; padding:1rem 1.1rem; border-radius:17px; background:linear-gradient(120deg,#0f172a,#1d4ed8); color:#fff; box-shadow:0 10px 24px rgba(15,23,42,.16); }
    .draw-form-hero strong { display:block; font-size:1.08rem; }
    .draw-form-hero span { display:block; margin-top:.18rem; color:#bfdbfe; font-size:.78rem; }
    .draw-form-hero .hero-badge { margin:0; padding:.45rem .7rem; border:1px solid rgba(255,255,255,.24); border-radius:999px; color:#dbeafe; font-size:.72rem; white-space:nowrap; }
    form[action*="essay-exams/draw"] > div:not(.draw-form-hero):not(#draw-state-box):not(#integrated-plan-box) { padding:.2rem .15rem; }
    form[action*="essay-exams/draw"] > div:not(.draw-form-hero) label { position:relative; }
    form[action*="essay-exams/draw"] > div:not(.draw-form-hero) label::before { content:""; display:inline-block; width:6px; height:6px; margin:0 .42rem .12rem 0; border-radius:50%; background:#38bdf8; }
    form[action*="essay-exams/draw"] > div:has(input[type="date"]), form[action*="essay-exams/draw"] > div:has(input[type="time"]) { padding:.72rem .82rem !important; border:1px solid #e0e7ff; border-radius:16px; background:rgba(255,255,255,.68); }
    #draw-state-box > div { border-color:#bfdbfe !important; background:rgba(239,246,255,.72); border-radius:13px; }
    form[action*="essay-exams/draw"] #integrated-source-wrap,
    form[action*="essay-exams/draw"] #multiple-choice-count-wrap,
    form[action*="essay-exams/draw"] #self-essay-count-wrap,
    form[action*="essay-exams/draw"] #draw-state-box { grid-column:1 / -1 !important; width:100%; }
    form[action*="essay-exams/draw"] #integrated-source-wrap { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); align-items:start; }
    @media (max-width: 767px) {
        form[action*="essay-exams/draw"] #integrated-source-wrap { grid-template-columns:1fr; }
    }
</style>
<h1 class="text-2xl font-bold mb-5">Rút đề thi</h1>
<form method="POST" action="{{ route('essay-exams.draw.store') }}" class="bg-white border rounded-xl p-5 grid md:grid-cols-2 gap-4">@csrf<div><label>Ngành đào tạo</label><select data-native-select="1" id="sp" name="specialization_id" required class="w-full border rounded px-3 py-2"><option value="">Chọn ngành</option>@foreach($specializations as $x)<option value="{{ $x->id }}">{{ $x->code }} — {{ $x->name }}</option>@endforeach</select></div><div><label>Lớp phụ trách</label><select data-native-select="1" id="cl" name="class_id" required class="w-full border rounded px-3 py-2"><option value="">Chọn lớp</option>@foreach($classes as $x)<option value="{{ $x->id }}" data-sp="{{ $x->specialization_id }}">{{ $x->name }} ({{ $x->code }})</option>@endforeach</select></div><div><label>Môn đang dạy ở lớp</label><select data-native-select="1" id="su" name="subject_id" required class="w-full border rounded px-3 py-2"><option value="">Chọn môn</option>@foreach($subjects as $x)@php($ids=collect($classSubjectMap)->filter(fn($a)=>in_array($x->id,$a))->keys()->implode(','))<option value="{{ $x->id }}" data-sp="{{ $x->specialization_id }}" data-classes="{{ $ids }}">{{ $x->code }} — {{ $x->name }}</option>@endforeach</select></div><div><label>Loại phiếu</label><select name="draw_type" class="w-full border rounded px-3 py-2"><option value="EVEN">Chẵn</option><option value="ODD">Lẻ</option></select></div><div><label>Ngày thi</label><input type="date" name="exam_date" class="w-full border rounded px-3 py-2"></div><div><label>Giờ thi</label><input type="time" name="exam_time" class="w-full border rounded px-3 py-2"></div><button class="md:col-span-2 px-5 py-2 rounded bg-blue-600 text-white">Rút đề ngẫu nhiên</button></form>
<div class="bg-white border rounded-xl overflow-hidden mt-6"><div class="p-4 font-semibold">Lịch sử bốc đề</div><div class="overflow-x-auto"><table class="w-full min-w-[980px] table-fixed text-sm"><colgroup><col class="w-[19%]"><col class="w-[12%]"><col class="w-[8%]"><col class="w-[18%]"><col class="w-[16%]"><col class="w-[13.5%]"><col class="w-[13.5%]"></colgroup><thead class="bg-slate-100"><tr><th class="p-3 text-left whitespace-nowrap">Mã bốc</th><th class="p-3 text-left whitespace-nowrap">Mã đề</th><th class="p-3 text-center whitespace-nowrap">Đề số</th><th class="p-3 text-left">Lớp thi</th><th class="p-3 text-left whitespace-nowrap">Ngày bốc</th><th class="p-3 text-center whitespace-nowrap">In đề</th><th class="p-3 text-center whitespace-nowrap">In đáp án</th></tr></thead><tbody class="divide-y">@forelse($draws as $draw)<tr><td class="p-3 font-mono whitespace-nowrap">{{ $draw->draw_code }}</td><td class="p-3 whitespace-nowrap">{{ $draw->exam->code }}</td><td class="p-3 text-center">{{ $draw->paper_number }}</td><td class="p-3 truncate" title="{{ $draw->class_name }}">{{ $draw->class_name }}</td><td class="p-3 whitespace-nowrap">{{ $draw->drawn_at?->format('d/m/Y H:i') }}</td><td class="p-3 text-center"><a target="_blank" class="inline-flex px-3 py-1 rounded bg-slate-800 text-white" href="{{ route('essay-exams.draw.print',$draw) }}">In đề</a></td><td class="p-3 text-center"><a target="_blank" class="inline-flex px-3 py-1 rounded bg-blue-600 text-white" href="{{ route('essay-exams.draw.print',$draw) }}?answers=1">In đáp án</a></td></tr>@empty<tr><td colspan="7" class="p-8 text-center">Chưa có lịch sử bốc đề.</td></tr>@endforelse</tbody></table></div></div>
<script>
(() => {
    const sp = document.getElementById('sp');
    const cl = document.getElementById('cl');
    const su = document.getElementById('su');
    if (!sp || !cl || !su) return;
    const allClasses = [...cl.options].map((option) => option.cloneNode(true));
    const allSubjects = [...su.options].map((option) => option.cloneNode(true));
    const clone = (option) => option.cloneNode(true);

    const filterCourseFields = () => {
        const specializationId = sp.value;
        const oldClass = cl.value;
        const oldSubject = su.value;

        cl.replaceChildren(...allClasses
            .filter((option) => !option.value || !specializationId || option.dataset.sp === specializationId)
            .map(clone));
        if ([...cl.options].some((option) => option.value === oldClass)) cl.value = oldClass;

        const classId = cl.value;
        su.replaceChildren(...allSubjects.filter((option) => {
            if (!option.value) return true;
            if (specializationId && option.dataset.sp !== specializationId) return false;
            if (!classId) return true;
            const classIds = (option.dataset.classes || '').split(',').filter(Boolean);
            return classIds.includes(classId);
        }).map(clone));
        if ([...su.options].some((option) => option.value === oldSubject)) su.value = oldSubject;
    };

    sp.addEventListener('change', filterCourseFields);
    cl.addEventListener('change', filterCourseFields);
    filterCourseFields();
})();
</script>
<script>
const paperCounts = @json($paperCounts);
const drawState = @json($drawState);
const subjectSelect = document.getElementById('su');
const classSelect = document.getElementById('cl');
if (subjectSelect) {
    const info = document.createElement('p'); info.className = 'text-xs text-blue-700 mt-1'; subjectSelect.parentElement.appendChild(info);
    const updateDrawInfo = function () {
        const drawType = document.querySelector('select[name="draw_type"]');
        if (!drawType) return;
        const count = paperCounts[subjectSelect.value] || 0;
        info.textContent = 'Ngân hàng môn này hiện có ' + count + ' đề đã duyệt.';
        const className = (classSelect?.selectedOptions[0]?.textContent || '').split(' (')[0].trim();
        const examType = document.querySelector('select[name="exam_type"]')?.value || 'Tự luận';
        const state = drawState[className + '|' + subjectSelect.value + '|' + examType] || {};
        [...drawType.options].forEach(function (option) { option.disabled = !!state[option.value]; });
        if (state[drawType.value]) { const next = [...drawType.options].find(function (option) { return !option.disabled; }); if (next) drawType.value = next.value; }
        let box = document.getElementById('draw-state-box');
        if (!box) { box = document.createElement('div'); box.id = 'draw-state-box'; box.className = 'md:col-span-2 grid md:grid-cols-2 gap-2'; subjectSelect.closest('form').insertBefore(box, subjectSelect.closest('form').querySelector('button')); }
        box.innerHTML = '<div class="border rounded-lg px-3 py-2 text-sm">Đề chẵn: <b>' + (state.EVEN ? 'Đã rút' : 'Chưa rút') + '</b></div><div class="border rounded-lg px-3 py-2 text-sm">Đề lẻ: <b>' + (state.ODD ? 'Đã rút' : 'Chưa rút') + '</b></div>';
    };
    subjectSelect.addEventListener('change', updateDrawInfo); classSelect.addEventListener('change', updateDrawInfo); document.addEventListener('change', function (event) { if (event.target?.name === 'exam_type') updateDrawInfo(); }); setTimeout(updateDrawInfo, 0);
}
</script>
<script>
document.querySelectorAll('a[href*="/draw/"]').forEach(function (link) {
    try {
        const url = new URL(link.href, window.location.origin);
        if (url.pathname.endsWith('/print')) {
            url.searchParams.set('auto', '1');
            link.href = url.toString();
            link.addEventListener('click', function (event) {
                event.preventDefault();
                const popup = window.open('', '_blank');
                if (!popup) { window.location.href = link.href; return; }
                fetch(link.href, { credentials: 'same-origin' }).then(r => r.text()).then(html => {
                    popup.document.open();
                    popup.document.write(html);
                    popup.document.close();
                }).catch(() => { popup.close(); window.location.href = link.href; });
            });
        }
    } catch (e) {}
});
const drawType = document.querySelector('select[name="draw_type"]');
const drawForm = document.querySelector('form:has(select[name="draw_type"])') || document.querySelector('form[action*="essay-exams/draw"]') || document.querySelector('form');
if (drawForm) drawForm.setAttribute('data-turbo', 'false');
if (drawForm && !drawForm.querySelector('.draw-form-hero')) drawForm.insertAdjacentHTML('afterbegin', '<div class="draw-form-hero"><div><strong>Thiết lập lượt rút đề</strong><span>Chọn nguồn câu hỏi, lớp và lịch thi để tạo đề nhanh chóng.</span></div><div class="hero-badge">BƯỚC 1 / 3</div></div>');
if (drawForm) {
    drawForm.querySelectorAll('select[data-native-select]').forEach((select) => select.removeAttribute('data-native-select'));
    window.initTomSelects?.(drawForm);
}
if (drawForm && drawType && !drawForm.querySelector('[name="exam_type"]')) {
    const examTypeWrap = document.createElement('div');
    examTypeWrap.innerHTML = '<label>Dạng đề</label><select name="exam_type" required class="w-full border rounded px-3 py-2"><option value="">Chọn dạng đề</option><option value="Tự luận">Tự luận</option><option value="Tích hợp">Tích hợp</option></select>';
    const firstDrawField = [...drawForm.children].find((child) => child.tagName !== 'INPUT' || child.type !== 'hidden');
    drawForm.insertBefore(examTypeWrap, firstDrawField || drawForm.firstElementChild);
    examTypeWrap.querySelector('[name="exam_type"]').removeAttribute('data-native-select');
    const examTypeSelect = examTypeWrap.querySelector('select');
    const mcqBanks = @json($integratedMcqBanks);
    const essayPools = @json($integratedEssayPools);
    const sourceWrap = document.createElement('div');
    sourceWrap.id = 'integrated-source-wrap';
    sourceWrap.className = 'md:col-span-2 grid md:grid-cols-2 gap-3 rounded-xl border border-teal-200 bg-teal-50 p-3';
    sourceWrap.innerHTML = '<label class="text-sm font-semibold text-teal-900">Ngân hàng trắc nghiệm theo môn/lớp<select name="mcq_bank_id" class="mt-1 w-full border rounded px-3 py-2 bg-white"><option value="">Chọn ngân hàng trắc nghiệm</option></select></label><label class="text-sm font-semibold text-teal-900">Ngân hàng tự luận theo môn/lớp<select name="essay_pool_id" class="mt-1 w-full border rounded px-3 py-2 bg-white"><option value="">Chọn ngân hàng tự luận</option></select></label>';
     // Đặt hai ngân hàng ngay dưới cặp Môn học / Loại phiếu để người dùng
     // chọn nguồn đề sau khi đã xác định môn và loại phiếu.
     drawType.parentElement.insertAdjacentElement('afterend', sourceWrap);
    const mcqSelect = sourceWrap.querySelector('[name="mcq_bank_id"]');
    const essaySelect = sourceWrap.querySelector('[name="essay_pool_id"]');
    mcqBanks.forEach((bank) => mcqSelect.insertAdjacentHTML('beforeend', `<option value="${bank.id}" data-subject="${bank.subject_id}" data-class="${bank.class_id}">${bank.title} (${bank.count} câu)</option>`));
    essayPools.forEach((pool) => essaySelect.insertAdjacentHTML('beforeend', `<option value="${pool.id}" data-subject="${pool.subject_id}" data-class="${pool.class_id}">${pool.code} · ${pool.title} (${pool.count} câu)</option>`));
     essayPools.forEach((pool) => { const option = essaySelect.querySelector(`option[value="${pool.id}"]`); if (option) { option.dataset.count = pool.count; option.textContent = `${pool.code} · ${pool.title} (${pool.papers || 1} đề, ${pool.count} câu)`; } });
      const refreshSources = () => {
         const visible = examTypeSelect.value === 'Tích hợp';
         const readValue = (select) => select?.tomselect ? select.tomselect.getValue() : (select?.value || '');
         const subjectId = String(readValue(document.getElementById('su')) || '');
         const classId = String(readValue(document.getElementById('cl')) || '');
         sourceWrap.style.display = visible ? '' : 'none';
         [
             [mcqSelect, mcqBanks, 'Chọn ngân hàng trắc nghiệm'],
             [essaySelect, essayPools, 'Chọn ngân hàng tự luận'],
         ].forEach(([select, banks, placeholder]) => {
             const selected = String(readValue(select) || '');
             const allowed = banks.filter((bank) => String(bank.subject_id || '') === String(subjectId)
                 && (!bank.class_id || String(bank.class_id) === String(classId)));
             const optionData = allowed.map((bank) => ({
                 value: String(bank.id),
                 text: bank.code ? `${bank.code} · ${bank.title} (${bank.papers || 1} đề, ${bank.count} câu)` : `${bank.title} (${bank.count} câu)`,
             }));
             const emptyText = subjectId && classId && !allowed.length
                 ? 'Chưa có ngân hàng phù hợp với môn/lớp này'
                 : placeholder;
             if (select.tomselect && typeof window.setTomSelectOptions === 'function') {
                 window.setTomSelectOptions(select, [{ value: '', text: emptyText }, ...optionData], {
                     selected: optionData.some((option) => option.value === selected) ? selected : '',
                     enabled: visible,
                 });
             } else {
                 select.innerHTML = '';
                 select.appendChild(new Option(emptyText, ''));
                 optionData.forEach((option) => select.appendChild(new Option(option.text, option.value)));
                 if (optionData.some((option) => option.value === String(selected))) select.value = selected;
                 else select.value = '';
             }
             select.required = visible;
             window.initTomSelects?.(drawForm);
         });
     };
    examTypeSelect.addEventListener('change', refreshSources);
    document.getElementById('su')?.addEventListener('change', refreshSources);
    document.getElementById('cl')?.addEventListener('change', refreshSources);
     window.initTomSelects?.(drawForm);
     refreshSources();
    const countWrap = document.createElement('div');
    countWrap.id = 'multiple-choice-count-wrap';
    countWrap.innerHTML = '<label>Tổng số câu trắc nghiệm cần rút</label><input id="question-count" type="number" name="question_count" min="1" max="200" class="w-full border rounded px-3 py-2" placeholder="Ví dụ: 40"><label class="block mt-2">Điểm mỗi câu</label><input id="question-points" type="number" name="question_points" min="0.01" max="100" step="0.25" value="0.25" class="w-full border rounded px-3 py-2"><p id="question-total-points" class="text-xs font-semibold text-blue-700 mt-1">Tổng điểm: 0</p><p class="text-xs text-slate-500 mt-1">Hệ thống sẽ rút ngẫu nhiên, không trùng câu, trong toàn bộ ngân hàng.</p>';
    examTypeWrap.insertAdjacentElement('afterend', countWrap);
    const updateQuestionCount = function () { countWrap.style.display = examTypeSelect.value === 'Trắc nghiệm' ? '' : 'none'; const inputs = countWrap.querySelectorAll('input'); inputs.forEach(input => input.required = examTypeSelect.value === 'Trắc nghiệm'); if (examTypeSelect.value !== 'Trắc nghiệm') inputs.forEach(input => input.value = ''); updateQuestionTotal(); };
    const updateQuestionTotal = function () { const count = Number(document.getElementById('question-count')?.value || 0); const points = Number(document.getElementById('question-points')?.value || 0); const total = document.getElementById('question-total-points'); if (total) total.textContent = 'Tổng điểm: ' + (count * points).toFixed(2).replace(/\.00$/, ''); };
    countWrap.addEventListener('input', updateQuestionTotal);
    examTypeSelect.addEventListener('change', updateQuestionCount); updateQuestionCount();
}
if (drawType) {
    drawType.parentElement.insertAdjacentHTML('beforeend', '<p class="text-xs text-slate-500 mt-1">Chọn Chẵn hoặc Lẻ; mỗi loại chỉ được rút một lần trong 3 ngày.</p>');
}
</script>
<script>
const drawParams = new URLSearchParams(window.location.search);
const savedSp = drawParams.get('specialization_id');
const savedCl = drawParams.get('class_id');
const savedSu = drawParams.get('subject_id');
if (savedSp) {
    const spSelect = document.getElementById('sp');
    const clSelect = document.getElementById('cl');
    const suSelect = document.getElementById('su');
    spSelect.value = savedSp;
    spSelect.dispatchEvent(new Event('change'));
    if (savedCl) { clSelect.value = savedCl; clSelect.dispatchEvent(new Event('change')); }
    if (savedSu) suSelect.value = savedSu;
}
const printDrawId = drawParams.get('print_draw');
if (printDrawId) {
    const printUrl = @json(route('essay-exams.draw.print', ['draw' => '__DRAW__'])) .replace('__DRAW__', printDrawId);
    window.open(printUrl, '_blank');
}
const nextType = drawParams.get('next_type');
const nextTypeSelect = document.querySelector('select[name="draw_type"]');
if (nextType && nextTypeSelect) { nextTypeSelect.value = nextType; nextTypeSelect.dispatchEvent(new Event('change')); }
if (drawParams.get('print_minutes') === '1') {
    const minutesUrl = @json(route('essay-exams.draw.minutes')) + '?class_name=' + encodeURIComponent(drawParams.get('minutes_class') || '') + '&subject_id=' + encodeURIComponent(savedSu || '');
    window.open(minutesUrl, '_blank');
}
if (printDrawId || drawParams.get('print_minutes') === '1') window.history.replaceState({}, document.title, window.location.pathname);
</script>
<script>
const historyDrawTypes = @json($draws->pluck('draw_type')->values());
const historySubjects = @json($draws->map(fn($d) => $d->exam->subject->name ?? '—')->values());
const historyExamTypes = @json($draws->map(fn($d) => $d->exam->exam_type ?? '—')->values());
const historyClassNames = @json($draws->pluck('class_name')->values());
const historySubjectIds = @json($draws->map(fn($d) => $d->exam->subject_id ?? 0)->values());
const minuteKeys = @json($minuteKeys);
const historyHeading = document.querySelector('.bg-white.border.rounded-xl.overflow-hidden.mt-6 > div.p-4.font-semibold');
if (historyHeading) historyHeading.classList.add('border-b', 'bg-slate-50');
const historyTable = document.querySelector('.bg-white.border.rounded-xl.overflow-hidden.mt-6 table');
if (historyTable) {
    historyTable.style.tableLayout = 'fixed';
    historyTable.style.minWidth = '1450px';
    // Thứ tự: mã bốc, mã đề, đề số, lớp, ngày, in đề, in đáp án,
    // loại phiếu, dạng đề, môn, biên bản.
    const fixedWidths = [165, 125, 70, 170, 140, 100, 120, 105, 150, 180, 125];
    const colgroup = historyTable.querySelector('colgroup');
    if (colgroup) colgroup.innerHTML = fixedWidths.map(width => '<col style="width:'+width+'px">').join('');
    historyTable.querySelectorAll('th,td').forEach(cell => { cell.style.paddingLeft = '10px'; cell.style.paddingRight = '10px'; cell.style.verticalAlign = 'middle'; });
    const header = historyTable.querySelector('thead tr');
    const heading = document.createElement('th');
    heading.className = 'p-3 text-center whitespace-nowrap';
    heading.textContent = 'Loại phiếu';
    header.appendChild(heading);
    historyTable.querySelectorAll('tbody tr').forEach(function (row, index) {
        if (row.children.length === 1) { row.children[0].setAttribute('colspan', '11'); return; }
        const cell = document.createElement('td');
        cell.className = 'p-3 text-center font-semibold';
        cell.textContent = historyDrawTypes[index] === 'ODD' ? 'Lẻ' : 'Chẵn';
        row.appendChild(cell);
    });
    const examTypeHead = document.createElement('th'); examTypeHead.className = 'p-3 text-left whitespace-nowrap'; examTypeHead.textContent = 'Dạng đề'; header.appendChild(examTypeHead);
    historyTable.querySelectorAll('tbody tr').forEach(function (row, index) { if (row.children.length === 1) return; const cell=document.createElement('td'); cell.className='p-3'; cell.textContent=historyExamTypes[index] || '—'; row.appendChild(cell); });
    const subjectHead = document.createElement('th'); subjectHead.className = 'p-3 text-left whitespace-nowrap'; subjectHead.textContent = 'Môn'; header.appendChild(subjectHead);
    historyTable.querySelectorAll('tbody tr').forEach(function (row, index) { if (row.children.length === 1) return; const cell=document.createElement('td'); cell.className='p-3'; cell.textContent=historySubjects[index] || '—'; row.appendChild(cell); });
    const minutesHead=document.createElement('th'); minutesHead.className='p-3 text-center'; minutesHead.textContent='Biên bản'; header.appendChild(minutesHead);
    const minuteShown = {};
    historyTable.querySelectorAll('tbody tr').forEach(function (row,index){ if(row.children.length===1)return; const cell=document.createElement('td'); cell.className='p-3 text-center'; const key=historyClassNames[index]+'|'+historySubjectIds[index]; if(minuteKeys.includes(key) && !minuteShown[key]){ const a=document.createElement('a'); a.className='px-3 py-1 rounded bg-emerald-600 text-white'; a.target='_blank'; a.href=@json(route('essay-exams.draw.minutes'))+'?class_name='+encodeURIComponent(historyClassNames[index])+'&subject_id='+historySubjectIds[index]; a.textContent='In biên bản'; cell.appendChild(a); minuteShown[key]=true;} row.appendChild(cell); });
    let lastGroup = null;
    [...historyTable.querySelectorAll('tbody tr')].forEach(function(row,index){ if(row.children.length===1)return; const group=historyClassNames[index]+'|'+historySubjectIds[index]; if(group!==lastGroup){ const label=document.createElement('tr'); label.className='bg-blue-50'; const cell=document.createElement('td'); cell.colSpan=header.children.length; cell.className='p-3 font-semibold text-blue-900'; cell.textContent='Lớp: '+historyClassNames[index]+' — Môn: '+(historySubjects[index]||'—'); label.appendChild(cell); row.parentNode.insertBefore(label,row); lastGroup=group; } });
}
</script>
<div class="bg-white border rounded-xl overflow-hidden mt-4">
    <div class="px-4 py-3 font-semibold border-b bg-slate-50">Kho đề đã sử dụng</div>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[900px] text-sm">
            <thead class="bg-slate-100"><tr><th class="p-3 text-left">Mã đề</th><th class="p-3 text-left">Mã bốc</th><th class="p-3 text-left">Lớp thi</th><th class="p-3 text-left">Ngày thi</th><th class="p-3 text-left">QR</th><th class="p-3">In</th></tr></thead>
            <tbody class="divide-y">@forelse($draws as $usedDraw)<tr><td class="p-3 font-semibold">{{ $usedDraw->exam->code }}-D{{ str_pad($usedDraw->paper_number,2,'0',STR_PAD_LEFT) }}</td><td class="p-3 font-mono">{{ $usedDraw->draw_code }}</td><td class="p-3">{{ $usedDraw->class_name }}</td><td class="p-3">{{ $usedDraw->exam_date?->format('d/m/Y') ?? '—' }}</td><td class="p-3 font-mono">{{ $usedDraw->qr_code }}</td><td class="p-3 text-center"><a target="_blank" class="px-3 py-1 rounded bg-slate-800 text-white" href="{{ route('essay-exams.draw.print',$usedDraw) }}?auto=1">In đề</a></td></tr>@empty<tr><td colspan="6" class="p-6 text-center text-slate-500">Chưa có đề đã sử dụng.</td></tr>@endforelse</tbody>
        </table>
    </div>
</div>
<div class="bg-amber-50 border border-amber-300 rounded-xl overflow-hidden mt-4">
    <div class="px-4 py-3 font-semibold text-amber-900 border-b border-amber-300">Cảnh báo đề đã rút quá 3 ngày</div>
    <div class="overflow-x-auto"><table class="w-full min-w-[760px] text-sm"><thead><tr><th class="p-3 text-left">Mã đề</th><th class="p-3 text-left">Loại phiếu</th><th class="p-3 text-left">Lớp</th><th class="p-3 text-left">Ngày rút</th><th class="p-3 text-left">Trạng thái</th></tr></thead><tbody class="divide-y divide-amber-200">@foreach($expiredDraws as $expired)<tr><td class="p-3 font-semibold">{{ $expired->exam->code }}-D{{ str_pad($expired->paper_number,2,'0',STR_PAD_LEFT) }}</td><td class="p-3">{{ $expired->draw_type === 'ODD' ? 'Lẻ' : 'Chẵn' }}</td><td class="p-3">{{ $expired->class_name }}</td><td class="p-3">{{ $expired->drawn_at?->format('d/m/Y H:i') }}</td><td class="p-3 text-amber-800">Không còn được in — có thể rút lại</td></tr>@endforeach</tbody></table></div>
</div>
<script>
(() => {
    const stats = @json($lessonStats);
    const bankLessonStats = @json($mcqBankLessonStats);
    const form = document.querySelector('form[action*="essay-exams/draw"]');
    const subject = document.getElementById('su');
    if (!form || !subject) return;
    const build = () => {
        const type = form.querySelector('[name="exam_type"]');
        if (!type || type.value !== 'Tích hợp') { document.getElementById('integrated-plan-box')?.remove(); return; }
        let box = document.getElementById('integrated-plan-box');
        if (!box) { box = document.createElement('div'); box.id='integrated-plan-box'; box.className='md:col-span-2 border rounded-xl p-3 bg-blue-50'; form.insertBefore(box, form.querySelector('button')); }
         const bankSelect = form.querySelector('[name="mcq_bank_id"]');
         const bankId = String(bankSelect?.tomselect?.getValue() || bankSelect?.value || '');
         const selectedBankCounts = bankLessonStats.filter(x => String(x.bank_id) === bankId);
         const countByLesson = new Map(selectedBankCounts.map(x => [String(x.lesson_id), Number(x.count || 0)]));
         if (!bankId) {
             box.innerHTML = '<div class="font-semibold text-blue-900 mb-1">Cơ cấu đề theo bài học</div><p class="text-sm text-amber-700">Hãy chọn ngân hàng trắc nghiệm LMS phù hợp trước khi nhập cơ cấu câu hỏi.</p>';
             return;
         }
         const rows = stats.filter(x => String(x.subject_id) === String(subject.value) && countByLesson.has(String(x.id)))
             .map(x => ({ ...x, mcq_count: countByLesson.get(String(x.id)) || 0 }));
        const integrated = true;
        box.innerHTML = '<div class="font-semibold text-blue-900 mb-2">Cơ cấu đề theo bài học</div>' + (rows.length ? '<div class="overflow-x-auto"><table class="w-full text-sm bg-white"><thead><tr><th class="p-2 text-left">Bài học</th><th class="p-2">Số tiết</th><th class="p-2">TN hiện có</th><th class="p-2">TN đề nghị</th>'+ (integrated ? '<th class="p-2">TL hiện có</th><th class="p-2">TL đề nghị</th>' : '') +'<th class="p-2">Cộng</th></tr></thead><tbody>'+rows.map(x=>`<tr><td class="p-2">${x.title}</td><td class="p-2 text-center">${x.hours}</td><td class="p-2 text-center">${x.mcq_count}</td><td class="p-2"><input data-lesson="${x.id}" data-kind="mcq" type="number" min="0" max="${x.mcq_count}" value="0" class="w-20 border rounded px-2 py-1"></td>${integrated ? `<td class="p-2 text-center">${x.essay_count}</td><td class="p-2"><input data-lesson="${x.id}" data-kind="essay" type="number" min="0" max="${x.essay_count}" value="0" class="w-20 border rounded px-2 py-1"></td>` : ''}<td class="p-2 text-center font-semibold" data-total="${x.id}">0</td></tr>`).join('')+'</tbody><tfoot><tr class="font-bold"><td colspan="3" class="p-2 text-right">Tổng</td><td class="p-2" id="total-mcq">0</td>'+ (integrated ? '<td></td><td class="p-2" id="total-essay">0</td>' : '') +'<td class="p-2" id="total-all">0</td></tr></tfoot></table></div><input type="hidden" name="integrated_plan" id="integrated-plan">' : '<p class="text-sm text-amber-700">Môn này chưa có bài học/câu hỏi trong ngân hàng.</p>');
         const sync = () => { const plan={}; let mt=0,et=0; box.querySelectorAll('input[data-lesson]').forEach(i=>{ const id=i.dataset.lesson; plan[id]??={mcq:0,essay:0}; plan[id][i.dataset.kind]=Math.max(0,Number(i.value||0)); const row=box.querySelector(`[data-total="${id}"]`); if(row) row.textContent=plan[id].mcq+plan[id].essay; }); Object.values(plan).forEach(x=>{mt+=x.mcq;et+=x.essay}); const totalMcq=box.querySelector('#total-mcq'), totalEssay=box.querySelector('#total-essay'), totalAll=box.querySelector('#total-all'), planInput=box.querySelector('#integrated-plan'); if(totalMcq) totalMcq.textContent=mt; if(totalEssay) totalEssay.textContent=et; if(totalAll) totalAll.textContent=mt+et; if(planInput) planInput.value=JSON.stringify(plan); };
        box.addEventListener('input', sync); sync();
    };
     const watch = () => { const type=form.querySelector('[name="exam_type"]'); if(!type) return; if(!type.dataset.planBound){type.dataset.planBound='1'; type.addEventListener('change',build);} build(); };
      subject.addEventListener('change',build);
      form.addEventListener('change', (event) => { if (event.target?.name === 'mcq_bank_id') build(); });
      watch();
})();
</script>
<script>
(() => {
    const form = document.querySelector('form[action*="essay-exams/draw"]');
    if (!form) return;
    const syncEssayStructure = () => {
        const type = form.querySelector('[name="exam_type"]')?.value;
        const box = document.getElementById('integrated-plan-box');
        if (type !== 'Tích hợp' || !box) {
            box?.querySelector('#essay-total-wrap')?.remove();
            return;
        }
        const table = box.querySelector('table');
        if (table) table.querySelectorAll('tr').forEach(row => [4, 5].forEach(index => { if (row.children[index]) row.children[index].hidden = true; }));
        let wrap = box.querySelector('#essay-total-wrap');
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.id = 'essay-total-wrap';
            wrap.className = 'mt-3 rounded-lg border border-amber-200 bg-amber-50 p-3';
            wrap.innerHTML = '<div class="font-semibold text-amber-900">Cơ cấu tự luận từ ngân hàng Dashboard</div><div class="text-sm text-amber-800 mt-1">Hệ thống sẽ bốc ngẫu nhiên câu hỏi trong tất cả các đề của ngân hàng đã chọn và không lặp câu.</div><label class="block mt-2 text-sm font-semibold">Số câu tự luận đề nghị<input name="essay_question_count" type="number" min="1" max="200" class="mt-1 w-full border rounded px-3 py-2 bg-white" placeholder="Nhập số câu cần rút"></label><div class="mt-3 grid gap-3 md:grid-cols-2"><label class="text-sm font-semibold">Tổng điểm trắc nghiệm<input name="mcq_total_points" type="number" min="0" max="100" step="0.01" value="5" required class="mt-1 w-full border rounded px-3 py-2 bg-white" placeholder="Ví dụ: 5"></label><label class="text-sm font-semibold">Tổng điểm tự luận<input name="essay_total_points" type="number" min="0" max="100" step="0.01" value="5" required class="mt-1 w-full border rounded px-3 py-2 bg-white" placeholder="Ví dụ: 5"></label></div><p class="mt-2 text-xs text-amber-800">Điểm mỗi câu sẽ được tính bằng tổng điểm của phần chia cho số câu thực tế được rút.</p><div id="essay-available-count" class="text-xs text-amber-800 mt-1"></div>';
            box.appendChild(wrap);
        }
        const bank = form.querySelector('[name="essay_pool_id"]')?.selectedOptions[0];
        const available = Number(bank?.textContent.match(/(\d+)\s*c/)?.[1] || 0);
        const input = wrap.querySelector('[name="essay_question_count"]');
        input.max = available || 200;
        input.required = !!bank?.value;
        wrap.querySelector('#essay-available-count').textContent = bank?.value ? `Ngân hàng đang chọn có ${available} câu tự luận đã duyệt.` : 'Hãy chọn ngân hàng đề tự luận Dashboard.';
        const total = box.querySelector('#total-all');
        const mcq = Number(box.querySelector('#total-mcq')?.textContent || 0);
        if (total) total.textContent = mcq + Number(input.value || 0);
    };
    form.addEventListener('input', syncEssayStructure);
    form.addEventListener('change', syncEssayStructure);
    syncEssayStructure();
})();
</script>
<script>
(() => {
    const form = document.querySelector('form[action*="essay-exams/draw"]');
    if (!form) return;
    const syncSelfEssay = () => {
        const type = form.querySelector('[name="exam_type"]')?.value || '';
        const isSelf = type === 'Tự luận' || type.toLowerCase().includes('tự luận');
        const source = document.getElementById('integrated-source-wrap');
        const mcqLabel = source?.querySelector('[name="mcq_bank_id"]')?.closest('label');
        const essayLabel = source?.querySelector('[name="essay_pool_id"]')?.closest('label');
        if (source) source.style.display = isSelf ? '' : (type ? '' : 'none');
        if (mcqLabel) mcqLabel.hidden = isSelf;
        if (essayLabel) essayLabel.hidden = !isSelf && !type.includes('Tích') && !type.includes('TÃ­ch');
        const mcq = source?.querySelector('[name="mcq_bank_id"]');
        const essay = source?.querySelector('[name="essay_pool_id"]');
        if (mcq) mcq.required = !isSelf && !!type;
        if (essay) essay.required = isSelf || type.includes('Tích') || type.includes('TÃ­ch');
        let wrap = document.getElementById('self-essay-count-wrap');
        if (!isSelf) { wrap?.remove(); return; }
        if (!wrap) {
            wrap = document.createElement('div');
            wrap.id = 'self-essay-count-wrap';
            wrap.className = 'md:col-span-2 rounded-lg border border-amber-200 bg-amber-50 p-3';
            wrap.innerHTML = '<label class="text-sm font-semibold text-amber-900">Số câu tự luận<select name="essay_question_count" class="mt-1 w-full border rounded px-3 py-2 bg-white" required><option value="">Chọn số câu</option><option value="1">1 câu — chọn câu 4 điểm</option><option value="2">2 câu — chọn 2 câu, mỗi câu 2 điểm</option></select></label><p class="text-xs text-amber-800 mt-1">Tổng đề tự luận luôn là 4 điểm.</p>';
            source?.insertAdjacentElement('afterend', wrap) || form.insertBefore(wrap, form.querySelector('button'));
        }
    };
    form.addEventListener('change', syncSelfEssay);
    syncSelfEssay();
})();
</script>
<script>
(() => {
    const enhanceDrawSelects = () => {
        const form = document.querySelector('form[action*="essay-exams/draw"]');
        if (!form) return;
        form.querySelectorAll('select[data-native-select]').forEach((select) => select.removeAttribute('data-native-select'));
        if (typeof window.initTomSelects !== 'function') return false;
        window.initTomSelects(form);
        ['sp', 'cl', 'su'].forEach((id) => {
            const select = document.getElementById(id);
            if (select?.tomselect) {
                select.tomselect.sync();
                select.tomselect.refreshOptions(false);
            }
        });
        return true;
    };
    let attempts = 0;
    const retry = () => {
        if (enhanceDrawSelects() || ++attempts >= 30) return;
        window.setTimeout(retry, 50);
    };
    retry();
    window.addEventListener('load', enhanceDrawSelects, { once: true });
    const rebuildSelect = (select) => {
        if (!select || !select.tomselect) return;
        const value = select.value;
        const filteredOptions = [...select.options].map((option) => option.cloneNode(true));
        window.destroyTomSelect?.(select);
        select.replaceChildren(...filteredOptions.map((option) => option.cloneNode(true)));
        window.initTomSelects?.(select.parentElement || form);
        if (value && [...select.options].some((option) => option.value === value)) {
            select.value = value;
            select.tomselect?.setValue(value, true);
        }
    };
    document.addEventListener('change', (event) => {
        const target = event.target;
        if (!target?.closest('form[action*="essay-exams/draw"]')) return;
        window.setTimeout(() => {
            enhanceDrawSelects();
            if (target.id === 'sp' || target.id === 'cl') {
                rebuildSelect(document.getElementById('cl'));
                rebuildSelect(document.getElementById('su'));
            }
        }, 0);
    });
})();
</script>
@endsection
