@extends('layouts.admin')
@section('title','Duyệt đề')
@section('page-title','Duyệt đề')
@section('content')
<style>
    .essay-approval-shell { color:#1e293b; }
    .essay-approval-shell .bg-white.border.rounded-xl { border-color:#dbeafe; border-radius:1rem; box-shadow:0 8px 24px rgba(30,64,175,.07); }
    .essay-approval-shell .bg-blue-50 { background:#eff6ff !important; }
    .essay-approval-shell .bg-teal-50 { background:#eff6ff !important; border-color:#bfdbfe !important; }
    .essay-approval-shell .border-teal-200,
    .essay-approval-shell .border-teal-100 { border-color:#bfdbfe !important; }
    .essay-approval-shell .text-teal-900,
    .essay-approval-shell .text-teal-800,
    .essay-approval-shell .text-teal-700 { color:#1d4ed8 !important; }
    .essay-approval-shell button.bg-emerald-600,
    .essay-approval-shell button.bg-teal-600,
    .essay-approval-shell button.bg-teal-700,
    .essay-approval-shell button.bg-blue-600 { background:#2563eb !important; color:#fff !important; }
    .essay-approval-shell button.bg-amber-500 { background:#1d4ed8 !important; color:#fff !important; }
    .essay-approval-shell button:hover { filter:brightness(1.08); box-shadow:0 5px 12px rgba(37,99,235,.18); }
    .essay-approval-shell table thead { background:#eff6ff !important; }
    .essay-approval-shell input[type="checkbox"] { accent-color:#2563eb; }
    .essay-approval-shell .space-y-4 > .bg-white.border.rounded-xl { padding:1rem 1.1rem; transition:border-color .18s, box-shadow .18s; }
    .essay-approval-shell .space-y-4 > .bg-white.border.rounded-xl:hover { border-color:#93c5fd; box-shadow:0 10px 25px rgba(37,99,235,.1); }
    .essay-approval-shell .space-y-4 > .bg-white.border.rounded-xl h2 { color:#172033; font-size:1rem; }
</style>
<div class="essay-approval-shell">
@include('partials.module-menu', ['module' => 'exam'])
@php($formatAnswer = static fn ($answer) => preg_replace('/\R\s*(\[[^\x5D\r\n]*(?:\x{0111}i\x{1EC3}m|diem)[^\x5D\r\n]*\])/iu', ' $1', trim((string) $answer)) ?: trim((string) $answer))
@php($stageLabel = ['PENDING_DEPT' => 'Khoa', 'PENDING_EXAM_OFFICE' => 'Khảo thí', 'PENDING_BGH' => 'Ban Giám hiệu', 'APPROVED' => 'Đã duyệt'][$stage] ?? $stage)
@if(auth()->user()?->hasAnyRole(['super-admin','system-manager','manager']))<div class="bg-white border rounded-xl p-3 mb-4 flex gap-2"><span class="font-medium">Cấp duyệt:</span><a class="px-3 py-1 rounded bg-slate-100" href="?stage=PENDING_DEPT">Khoa</a><a class="px-3 py-1 rounded bg-slate-100" href="?stage=PENDING_EXAM_OFFICE">Khảo thí</a><a class="px-3 py-1 rounded bg-slate-100" href="?stage=PENDING_BGH">BGH</a></div>@endif
<x-breadcrumb :items="[['title'=>'Đề thi tự luận','url'=>route('essay-exams.index')],['title'=>'Duyệt đề']]" />
@php($currentDigitalSignature = \App\Models\DigitalSignature::query()->active()->forUser((int) auth()->id())->orderByDesc('is_default')->orderBy('sort_order')->orderByDesc('id')->first())
<section id="bgh-signature-panel" class="bg-white border border-amber-200 rounded-xl p-4 mb-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="font-bold text-amber-900">In đề trong luồng duyệt</h2>
            <p class="text-sm text-slate-600">Các cấp duyệt in bản xem trước tại đây. Bước BGH có thể dùng chữ ký số đã lưu hoặc ký trực tiếp tại màn hình này.</p>
        </div>
        <span class="px-3 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm">Chữ ký số: {{ $currentDigitalSignature?->display_name ?: 'chưa cấu hình' }}</span>
    </div>
    <div class="flex flex-wrap items-center gap-2 mt-3">
        <select id="bgh-print-target" class="border rounded-lg px-3 py-2 text-sm min-w-[260px]"><option value="">Chọn bộ đề cần in</option>@foreach($exams as $exam)<option value="{{ $loop->index }}" data-pdf="{{ $exam->source_pdf_path ? route('essay-exams.source-pdf', $exam) : '' }}">{{ $exam->code }} — {{ $exam->title }}</option>@endforeach</select>
        <button type="button" id="bgh-print-unsigned" class="px-3 py-2 rounded-lg bg-slate-700 text-white text-sm">In PDF đã import</button>
        @if($stage === 'PENDING_BGH')
            <button type="button" id="bgh-print-digital" class="px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm">In ký số BGH</button>
            <button type="button" id="bgh-print-direct" class="px-3 py-2 rounded-lg bg-blue-600 text-white text-sm">In ký trực tiếp</button>
        @endif
    </div>
    @if($stage === 'PENDING_BGH' && ! $currentDigitalSignature?->imageUrl())
        <p class="text-xs text-red-700 mt-2">Tài khoản duyệt BGH chưa có chữ ký số. Có thể bổ sung chữ ký số hoặc dùng ký trực tiếp bên dưới.</p>
    @elseif($stage === 'PENDING_BGH')
        <p class="text-xs text-emerald-700 mt-2">Sẽ dùng chữ ký số đang bật của tài khoản: {{ $currentDigitalSignature->display_name ?: auth()->user()?->name }}</p>
    @endif
</section>
<div id="missing-signature-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/45 p-4">
    <div class="w-full max-w-md rounded-xl bg-white p-5 shadow-xl">
        <h3 class="text-lg font-bold text-slate-900">Chưa có chữ ký số</h3>
        <p class="mt-2 text-sm text-slate-600">Tài khoản của bạn chưa có chữ ký số đang bật. Hãy bổ sung chữ ký số hoặc dùng chức năng ký trực tiếp trên màn hình duyệt.</p>
        <div class="mt-4 flex flex-wrap justify-end gap-2">
            <button type="button" id="missing-signature-close" class="rounded-lg border px-4 py-2 text-slate-700">Đóng</button>
            <a id="missing-signature-go" href="{{ route('signatures.index') }}" class="rounded-lg bg-blue-600 px-4 py-2 text-white">Bổ sung chữ ký số</a>
        </div>
    </div>
</div>
<div id="direct-signature-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/45 p-4">
    <div class="w-full max-w-2xl rounded-xl bg-white p-5 shadow-xl">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Ký trực tiếp</h3>
                <p class="mt-1 text-sm text-slate-600">Ký trong khung bên dưới. Hệ thống sẽ lấy nét ký nền trong suốt để dùng cho bản in.</p>
            </div>
            <button type="button" id="direct-signature-close" class="rounded-lg border px-3 py-1.5 text-slate-700">Đóng</button>
        </div>
        <canvas id="bgh-signature-canvas" width="720" height="220" class="mt-4 w-full rounded-lg border bg-white touch-none"></canvas>
        <p id="bgh-signature-status" class="mt-2 text-xs text-slate-600">Ký trong khung rồi bấm “Xác nhận và in”.</p>
        <div class="mt-4 flex flex-wrap justify-end gap-2">
            <button type="button" id="bgh-signature-clear" class="rounded-lg border px-4 py-2 text-slate-700">Xóa chữ ký</button>
            <button type="button" id="direct-signature-confirm" class="rounded-lg bg-blue-600 px-4 py-2 text-white" disabled>Xác nhận và in</button>
        </div>
    </div>
</div>
<script>
(() => {
    const panel = document.getElementById('bgh-signature-panel');
    if (!panel) return;
    const target = document.getElementById('bgh-print-target');
    const printUnsigned = document.getElementById('bgh-print-unsigned');
    const printDigital = document.getElementById('bgh-print-digital');
    const printDirect = document.getElementById('bgh-print-direct');
    const canvas = document.getElementById('bgh-signature-canvas');
    const clear = document.getElementById('bgh-signature-clear');
    const signatureStatus = document.getElementById('bgh-signature-status');
    const directModal = document.getElementById('direct-signature-modal');
    const directClose = document.getElementById('direct-signature-close');
    const directConfirm = document.getElementById('direct-signature-confirm');
    const missingModal = document.getElementById('missing-signature-modal');
    const missingClose = document.getElementById('missing-signature-close');
    const missingGo = document.getElementById('missing-signature-go');
    const digitalSignatureUrl = @json($currentDigitalSignature?->imageUrl());
    const isBghStage = @json($stage === 'PENDING_BGH');
    let directSignatureData = '';
    let directSignatureDrawn = false;
    if (canvas) {
        const ctx = canvas.getContext('2d');
        let drawing = false;
        ctx.lineWidth = 2.4;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#111827';
        const point = (event) => {
            const rect = canvas.getBoundingClientRect();
            return { x: (event.clientX - rect.left) * canvas.width / rect.width, y: (event.clientY - rect.top) * canvas.height / rect.height };
        };
        const finish = () => {
            drawing = false;
            if (!directSignatureDrawn) return;
            directSignatureData = canvas.toDataURL('image/png');
            if (signatureStatus) signatureStatus.textContent = 'Đã lấy chữ ký trực tiếp nền trong suốt.';
            if (directConfirm) directConfirm.disabled = false;
            updatePrintButtons();
        };
        canvas.addEventListener('pointerdown', (event) => {
            drawing = true;
            canvas.setPointerCapture(event.pointerId);
            const p = point(event);
            ctx.beginPath();
            ctx.moveTo(p.x, p.y);
        });
        canvas.addEventListener('pointermove', (event) => {
            if (!drawing) return;
            const p = point(event);
            ctx.lineTo(p.x, p.y);
            ctx.stroke();
            directSignatureDrawn = true;
        });
        canvas.addEventListener('pointerup', finish);
        canvas.addEventListener('pointercancel', () => { drawing = false; });
        clear?.addEventListener('click', () => {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            directSignatureData = '';
            directSignatureDrawn = false;
            if (directConfirm) directConfirm.disabled = true;
            if (signatureStatus) signatureStatus.textContent = 'Ký trong khung rồi bấm “Xác nhận và in”.';
            updatePrintButtons();
        });
    }
    const showDirectSignature = () => {
        if (!directModal) return;
        directModal.classList.remove('hidden');
        directModal.classList.add('flex');
    };
    const hideDirectSignature = () => {
        if (!directModal) return;
        directModal.classList.add('hidden');
        directModal.classList.remove('flex');
    };
    directClose?.addEventListener('click', hideDirectSignature);
    directConfirm?.addEventListener('click', () => {
        hideDirectSignature();
        printPreview('direct');
    });
    const showMissingSignature = () => {
        if (!missingModal) { alert('Tài khoản chưa có chữ ký số. Vui lòng cập nhật chữ ký số trước khi in ký số.'); return; }
        missingModal.classList.remove('hidden');
        missingModal.classList.add('flex');
    };
    const hideMissingSignature = () => {
        missingModal.classList.add('hidden');
        missingModal.classList.remove('flex');
    };
    missingClose?.addEventListener('click', hideMissingSignature);
    missingGo?.addEventListener('click', hideMissingSignature);
    const updatePrintButtons = () => {
        const hasForm = !!selectedForm();
        const hasPdf = !!selectedPdfUrl();
        printUnsigned.disabled = !hasForm || !hasPdf;
        if (printDigital) printDigital.disabled = !hasForm || !hasPdf;
        if (printDirect) printDirect.disabled = !hasForm || !hasPdf;
    };
    const forms = () => [...document.querySelectorAll('form')].filter(form => form.querySelector('input[name="paper_numbers[]"]'));
    const selectedForm = () => target?.value === '' ? null : (forms()[Number(target?.value)] || null);
    const selectedPdfUrl = () => target?.selectedOptions?.[0]?.dataset?.pdf || '';
    const savePrintUrl = @json(route('essay-exams.approval.print', ['essayExam' => '__EXAM__']));
    const savePrintedDocument = async (mode) => {
        const form = selectedForm(); const match = form?.action?.match(/\/([0-9]+)\/approve(?:$|\?)/); if (!match) return;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const body = { print_mode: mode, _token: csrf };
        if (mode === 'direct') body.signature_data = directSignatureData;
        try {
            const response = await fetch(savePrintUrl.replace('__EXAM__', match[1]), { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'}, body:JSON.stringify(body) });
            const payload = await response.json().catch(() => ({}));
            if (!response.ok) throw new Error(payload.message || 'Không lưu được bản in.');
            return payload.print_url || '';
        } catch (error) {
            console.error('Không lưu được bản in:', error);
            alert(error.message || 'Không lưu được bản in có chữ ký.');
            return '';
        }
    };
    const printPreview = async (mode) => {
        const form = selectedForm();
        if (!form) { alert('Vui lòng chọn bộ đề cần in.'); return; }
        const pdfUrl = selectedPdfUrl();
        if (!pdfUrl) { alert('Bộ đề này chưa có file PDF được chuyển từ Word import.'); return; }
        if (mode === 'digital' && !digitalSignatureUrl) {
            showMissingSignature();
            return;
        }
        if (mode === 'direct' && !directSignatureData) {
            alert('Vui lòng ký trực tiếp trong khung trước khi in.');
            return;
        }
        if ((mode === 'digital' || mode === 'direct') && !isBghStage) {
            alert('Chỉ bước BGH mới in bản có chữ ký.');
            return;
        }
        const win = window.open('', '_blank');
        const printUrl = (mode === 'digital' || mode === 'direct')
            ? (await savePrintedDocument(mode) || pdfUrl)
            : pdfUrl;
        if (!win) {
            window.location.href = printUrl;
            return;
        }
        win.location.href = printUrl;
        win.focus();
    };
    const bindForms = () => {
        const all = forms(); if (!all.length || !target) return false;
        if (!target.dataset.bound) {
            target.dataset.bound='1';
            // Dự phòng cho trường hợp trình duyệt giữ HTML cũ chưa có option server.
            if (target.options.length === 1) {
                all.forEach((form, index) => { const option=document.createElement('option'); option.value=String(index); option.textContent=form.closest('.bg-white.border.rounded-xl')?.querySelector('h2')?.textContent?.trim() || ('Bộ đề '+(index+1)); target.appendChild(option); });
            }
            target.addEventListener('change', updatePrintButtons);
            printUnsigned.addEventListener('click', () => printPreview('unsigned'));
            if (printDigital) printDigital.addEventListener('click', () => printPreview('digital'));
            if (printDirect) printDirect.addEventListener('click', showDirectSignature);
            all.forEach(form => form.addEventListener('submit', event => {
                if (isBghStage && !digitalSignatureUrl && !directSignatureData) { event.preventDefault(); showMissingSignature(); }
                if (isBghStage && directSignatureData) {
                    let input = form.querySelector('input[name="signature_data"]');
                    if (!input) {
                        input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'signature_data';
                        form.appendChild(input);
                    }
                    input.value = directSignatureData;
                }
            }));
        }
        updatePrintButtons();
        return true;
    };
    const bindWhenReady = () => {
        if (bindForms()) return;
        let attempts = 0;
        const timer = setInterval(() => {
            attempts++;
            if (bindForms() || attempts >= 20) clearInterval(timer);
        }, 150);
    };
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', bindWhenReady, { once: true });
    else bindWhenReady();
    window.addEventListener('pageshow', bindWhenReady);
})();
</script>
<div class="flex justify-between items-center mb-5"><div><h1 class="text-2xl font-bold">Duyệt đề</h1><p class="text-sm text-slate-500">Đang ở cấp: {{ $stageLabel }}. Bao gồm đề tự luận và ngân hàng trắc nghiệm LMS.</p></div><a href="{{ route(auth()->user()->can('essay-exams.index') ? 'essay-exams.index' : 'essay-exams.mine') }}" class="text-blue-600">{{ auth()->user()->can('essay-exams.index') ? 'Danh sách đề' : 'Đề của tôi' }}</a></div>
@if(session('success'))<div class="mb-4 rounded-lg bg-emerald-50 text-emerald-800 px-4 py-3">{{ session('success') }}</div>@endif
<form id="essay-approval-filter" class="bg-white border rounded-xl p-4 mb-4 grid md:grid-cols-4 gap-3"><input type="hidden" name="stage" value="{{ $stage }}"><select name="specialization_id" class="border rounded-lg px-3 py-2"><option value="">Tất cả ngành đào tạo</option>@foreach($specializations as $item)<option value="{{ $item->id }}" @selected(request('specialization_id')==$item->id)>{{ $item->selection_label }}</option>@endforeach</select><select name="subject_id" class="border rounded-lg px-3 py-2"><option value="">Tất cả môn học</option>@foreach($subjects as $item)<option value="{{ $item->id }}" @selected(request('subject_id')==$item->id)>{{ $item->code }} — {{ $item->name }}</option>@endforeach</select><input name="teacher" value="{{ request('teacher') }}" placeholder="Tên hoặc tài khoản giáo viên" class="border rounded-lg px-3 py-2"><select name="exam_id" class="border rounded-lg px-3 py-2"><option value="">Tất cả bộ đề</option>@foreach($examOptions as $item)<option value="{{ $item->id }}" data-subject="{{ $item->subject_id }}" data-teacher="{{ mb_strtolower(($item->created_by_display_name ?: '').' '.($item->created_by_username ?: '')) }}" @selected(request('exam_id')==$item->id)>{{ $item->code }} — {{ $item->title }} — {{ $item->created_by_display_name ?: $item->created_by_username }}</option>@endforeach</select></form>
<script>
(() => {
    const form = document.getElementById('essay-approval-filter');
    if (!form) return;
    const subject = form.querySelector('[name="subject_id"]');
    const teacher = form.querySelector('[name="teacher"]');
    const exam = form.querySelector('[name="exam_id"]');
    const options = Array.from(exam.options).slice(1);
    let teacherTimer = null;
    const autoSubmit = () => form.requestSubmit ? form.requestSubmit() : form.submit();
    const filterExamOptions = () => {
        const subjectId = subject.value;
        const teacherText = teacher.value.trim().toLowerCase();
        let selectedVisible = !exam.value;
        options.forEach(option => {
            const okSubject = !subjectId || option.dataset.subject === subjectId;
            const okTeacher = !teacherText || (option.dataset.teacher || '').includes(teacherText);
            option.hidden = !(okSubject && okTeacher);
            if (option.value === exam.value && !option.hidden) selectedVisible = true;
        });
        if (!selectedVisible) exam.value = '';
    };
    form.querySelector('[name="specialization_id"]').addEventListener('change', autoSubmit);
    subject.addEventListener('change', () => { filterExamOptions(); autoSubmit(); });
    exam.addEventListener('change', autoSubmit);
    teacher.addEventListener('input', () => {
        filterExamOptions();
        clearTimeout(teacherTimer);
        teacherTimer = setTimeout(autoSubmit, 600);
    });
    filterExamOptions();
})();
</script>
@if($lmsBanks->isNotEmpty())<section class="mb-5 bg-teal-50 border border-teal-200 rounded-xl p-4"><div class="flex items-center justify-between mb-3"><div><h2 class="font-bold text-teal-900">Ngân hàng trắc nghiệm LMS</h2><p class="text-sm text-teal-700">Các ngân hàng đang ở cấp {{ $stageLabel }}.</p></div><span class="px-2 py-1 rounded-full bg-white text-teal-800 text-xs font-semibold">{{ $lmsBanks->count() }} ngân hàng</span></div><form method="POST" action="{{ route('essay-exams.approval.lms-banks.bulk-approve') }}" class="mb-3 flex flex-wrap items-center gap-2">@csrf @foreach($lmsBanks as $bank)<label class="inline-flex items-center gap-1 rounded-lg bg-white border border-teal-100 px-2 py-1 text-xs"><input type="checkbox" name="bank_ids[]" value="{{ $bank->id }}">{{ $bank->title }}</label>@endforeach<button name="approve_all" value="1" class="px-3 py-2 rounded-lg bg-teal-700 text-white text-sm font-semibold">Duyệt tất cả</button><button class="px-3 py-2 rounded-lg bg-teal-600 text-white text-sm font-semibold">Duyệt đã chọn</button></form><div class="space-y-3">@foreach($lmsBanks as $bank)<div class="bg-white border border-teal-100 rounded-lg p-3 flex flex-wrap items-center justify-between gap-3"><div><div class="font-semibold text-slate-900">{{ $bank->title }}</div><div class="text-xs text-slate-500">{{ $bank->course?->title }} · {{ $bank->questions_count ?? $bank->questions->count() }} câu · {{ $bank->questions->groupBy('lms_lesson_id')->count() }} bài</div></div><form method="POST" action="{{ route('essay-exams.approval.lms-banks.approve', $bank) }}">@csrf<button class="px-3 py-2 rounded-lg bg-teal-600 text-white text-sm font-semibold">Duyệt ngân hàng trắc nghiệm</button></form></div>@endforeach</div></section>@endif
<div class="space-y-4">@forelse($exams as $exam)<div class="bg-white border rounded-xl p-5"><div class="flex flex-wrap justify-between gap-3"><div><h2 class="font-semibold text-lg">{{ $exam->code }} — {{ $exam->title }}</h2><p class="text-sm text-slate-500">{{ $exam->subject->code }} — {{ $exam->subject->name }} · {{ $exam->questions->where('paper_status',$stage)->groupBy('paper_number')->count() }} đề đang chờ</p><p class="text-sm text-slate-600">Giáo viên đề xuất: <b>{{ $exam->created_by_display_name ?: $exam->created_by_username }}</b></p></div><span class="px-2 py-1 rounded-full bg-amber-100 text-amber-800 text-xs">{{ $exam->status_label }}</span></div>
<form method="POST" action="{{ route('essay-exams.approve',$exam) }}">@csrf<input type="hidden" name="stage" value="{{ $stage }}"><details class="mt-3" open><summary class="cursor-pointer text-blue-600">Xem và chọn đề số để duyệt</summary><div class="mt-3 space-y-4">@foreach($exam->questions->where('paper_status',$stage)->groupBy('paper_number') as $paper => $questions)<section class="border rounded-lg overflow-hidden"><div class="px-4 py-3 bg-blue-50 border-b flex items-center justify-between"><div><b>Đề số {{ $paper }}</b><span class="text-sm text-slate-600"> — {{ $questions->count() }} câu · {{ number_format($questions->sum('points'),2,',','.') }} điểm</span></div><label class="text-sm"><input type="checkbox" name="paper_numbers[]" value="{{ $paper }}" checked> Chọn duyệt</label></div><div class="p-4 space-y-3">@foreach($questions as $q)<div class="border-l-2 border-slate-300 pl-3"><b>Câu {{ $q->question_number }} ({{ $q->points }} điểm)</b><div class="mt-1 whitespace-pre-line"><span class="font-medium">Câu hỏi:</span> {{ $q->content }}</div><div class="mt-1 text-sm text-slate-600 whitespace-pre-line"><span class="font-medium">Đáp án / barem:</span> {{ $q->answer ? $formatAnswer($q->answer) : 'Chưa nhập' }}</div></div>@endforeach</div></section>@endforeach</div></details><div class="flex flex-wrap gap-2 mt-4"><button class="px-4 py-2 rounded-lg bg-emerald-600 text-white">Duyệt các đề đã chọn</button></form><form method="POST" action="{{ route('essay-exams.return',$exam) }}" class="flex gap-2">@csrf<input name="return_note" required placeholder="Lý do trả lại" class="border rounded-lg px-3 py-2"><button class="px-4 py-2 rounded-lg bg-amber-500 text-white">Trả lại</button></form></div></div>@empty<div class="bg-white border rounded-xl p-10 text-center text-slate-500">Không có đề đang chờ ở cấp này.</div>@endforelse</div>
</div>
@endsection
