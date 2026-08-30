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
@if(auth()->user()?->hasAnyRole(['super-admin','system-manager','manager']))<div class="bg-white border rounded-xl p-3 mb-4 flex gap-2"><span class="font-medium">Cấp duyệt:</span><a class="px-3 py-1 rounded bg-slate-100" href="?stage=PENDING_DEPT">Khoa</a><a class="px-3 py-1 rounded bg-slate-100" href="?stage=PENDING_EXAM_OFFICE">Khảo thí</a><a class="px-3 py-1 rounded bg-slate-100" href="?stage=PENDING_BGH">BGH</a></div>@endif
<x-breadcrumb :items="[['title'=>'Đề thi tự luận','url'=>route('essay-exams.index')],['title'=>'Duyệt đề']]" />
@if($stage === 'PENDING_BGH')
<section id="bgh-signature-panel" class="bg-white border border-amber-200 rounded-xl p-4 mb-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><h2 class="font-bold text-amber-900">Ký văn bản phê duyệt trước khi duyệt cuối</h2><p class="text-sm text-slate-600">Chọn tải ảnh chữ ký hoặc ký trực tiếp. Hệ thống tự xóa nền sáng, chỉ lưu phần chữ ký.</p></div>
        <span class="px-3 py-2 rounded-lg bg-slate-100 text-slate-700 text-sm">In đề đang chờ ký</span>
    </div>
    <div class="grid md:grid-cols-2 gap-4 mt-3">
        <div>
            <label class="block text-sm font-semibold">Tải ảnh chữ ký</label>
            <input id="bgh-signature-file" type="file" accept="image/png,image/jpeg" class="mt-1 w-full border rounded-lg px-3 py-2">
            <p class="text-xs text-slate-500 mt-1">Ảnh nền trắng/sáng sẽ được chuyển thành PNG nền trong suốt.</p>
        </div>
        <div>
            <label class="block text-sm font-semibold">Ký trực tiếp</label>
            <canvas id="bgh-signature-canvas" width="520" height="170" class="mt-1 w-full border rounded-lg bg-white touch-none"></canvas>
            <button type="button" id="bgh-signature-clear" class="mt-1 px-3 py-1 rounded bg-slate-100 text-slate-700 text-sm">Xóa chữ ký</button>
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-2 mt-3">
        <select id="bgh-print-target" class="border rounded-lg px-3 py-2 text-sm min-w-[260px]"><option value="">Chọn bộ đề cần in</option>@foreach($exams as $exam)<option value="{{ $loop->index }}">{{ $exam->code }} — {{ $exam->title }}</option>@endforeach</select>
        <button type="button" id="bgh-print-unsigned" class="px-3 py-2 rounded-lg bg-slate-700 text-white text-sm">In đề chưa ký</button>
        <button type="button" id="bgh-print-image" class="px-3 py-2 rounded-lg bg-blue-600 text-white text-sm" disabled>In đề ký ảnh</button>
        <button type="button" id="bgh-print-direct" class="px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm" disabled>In đề ký trực tiếp</button>
    </div>
    <p id="bgh-signature-status" class="text-xs text-emerald-700 mt-2">Chưa có chữ ký.</p>
</section>
<script>
(() => {
    const panel = document.getElementById('bgh-signature-panel');
    const canvas = document.getElementById('bgh-signature-canvas');
    const file = document.getElementById('bgh-signature-file');
    const clear = document.getElementById('bgh-signature-clear');
    const status = document.getElementById('bgh-signature-status');
    if (!panel || !canvas) return;
    const ctx = canvas.getContext('2d'); let drawing = false; let signatureData = ''; let method = 'draw';
    window.bghSignatureData = ''; window.bghSignatureMethod = 'draw';
    ctx.lineWidth = 2.2; ctx.lineCap = 'round'; ctx.strokeStyle = '#111827';
    const point = (event) => { const r = canvas.getBoundingClientRect(); return { x:(event.clientX-r.left)*canvas.width/r.width, y:(event.clientY-r.top)*canvas.height/r.height }; };
    canvas.addEventListener('pointerdown', e => { drawing=true; canvas.setPointerCapture(e.pointerId); const p=point(e); ctx.beginPath(); ctx.moveTo(p.x,p.y); method='draw'; });
    canvas.addEventListener('pointermove', e => { if(!drawing)return; const p=point(e); ctx.lineTo(p.x,p.y); ctx.stroke(); signatureData=canvas.toDataURL('image/png'); window.bghSignatureData=signatureData; window.bghSignatureMethod=method; status.textContent='Đã tạo chữ ký trực tiếp.'; window.dispatchEvent(new Event('bgh-signature-ready')); });
    canvas.addEventListener('pointerup', () => { drawing=false; signatureData=canvas.toDataURL('image/png'); window.bghSignatureData=signatureData; window.bghSignatureMethod=method; window.dispatchEvent(new Event('bgh-signature-ready')); });
    clear.addEventListener('click', () => { ctx.clearRect(0,0,canvas.width,canvas.height); signatureData=''; method='draw'; window.bghSignatureData=''; window.bghSignatureMethod=method; status.textContent='Chưa có chữ ký.'; });
    file.addEventListener('change', () => {
        const selected=file.files?.[0]; if(!selected)return; const image=new Image(); const reader=new FileReader();
        reader.onload=()=>{ image.onload=()=>{ canvas.width=520; canvas.height=Math.max(120,Math.min(260,Math.round(520*image.height/image.width))); ctx.clearRect(0,0,canvas.width,canvas.height); ctx.drawImage(image,0,0,canvas.width,canvas.height); const pixels=ctx.getImageData(0,0,canvas.width,canvas.height); for(let i=0;i<pixels.data.length;i+=4){ const r=pixels.data[i],g=pixels.data[i+1],b=pixels.data[i+2]; const light=Math.min(r,g,b); if(r>225&&g>225&&b>225)pixels.data[i+3]=0; else if(light>170)pixels.data[i+3]=Math.max(0,255-Math.round((light-170)*3)); } ctx.putImageData(pixels,0,0); signatureData=canvas.toDataURL('image/png'); method='upload'; window.bghSignatureData=signatureData; window.bghSignatureMethod=method; status.textContent='Đã xử lý ảnh, nền sáng đã được loại bỏ.'; window.dispatchEvent(new Event('bgh-signature-ready')); }; image.src=reader.result; }; reader.readAsDataURL(selected);
    });
    const target = document.getElementById('bgh-print-target');
    const printUnsigned = document.getElementById('bgh-print-unsigned');
    const printImage = document.getElementById('bgh-print-image');
    const printDirect = document.getElementById('bgh-print-direct');
    const updatePrintButtons = () => {
        const hasForm = !!selectedForm();
        printUnsigned.disabled = !hasForm;
        printImage.disabled = !hasForm || !window.bghSignatureData || window.bghSignatureMethod !== 'upload';
        printDirect.disabled = !hasForm || !window.bghSignatureData || window.bghSignatureMethod !== 'draw';
    };
    const forms = () => [...document.querySelectorAll('form')].filter(form => form.querySelector('input[name="paper_numbers[]"]'));
    const selectedForm = () => forms()[Number(target?.value || 0)] || null;
    const savePrintUrl = @json(route('essay-exams.approval.print', ['essayExam' => '__EXAM__']));
    const savePrintedDocument = async (mode) => {
        const form = selectedForm(); const match = form?.action?.match(/\/([0-9]+)\/approve(?:$|\?)/); if (!match) return;
        const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
        const body = { print_mode: mode, _token: csrf };
        if (mode !== 'unsigned') { body.signature_method = window.bghSignatureMethod; body.signature_data = window.bghSignatureData; }
        try { await fetch(savePrintUrl.replace('__EXAM__', match[1]), { method:'POST', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'}, body:JSON.stringify(body) }); } catch (error) { console.error('Không lưu được bản in:', error); }
    };
    const printPreview = (withSignature, expectedMethod = null) => {
        const form = selectedForm();
        if (!form) { alert('Vui lòng chọn bộ đề cần in.'); return; }
        if (withSignature && window.bghSignatureMethod !== expectedMethod) {
            alert(expectedMethod === 'upload' ? 'Vui lòng tải ảnh chữ ký trước.' : 'Vui lòng ký trực tiếp trước.');
            return;
        }
        const clone = form.cloneNode(true);
        clone.querySelectorAll('button, input, summary').forEach(node => node.remove());
        clone.querySelectorAll('details').forEach(node => node.open = true);
        // Giữ nguyên bố cục từng câu: câu hỏi trước, đáp án/barem ngay bên dưới.
        const questionHtml = clone.querySelector('details')?.innerHTML || clone.innerHTML;
        const card = form.closest('.bg-white.border.rounded-xl');
        const heading = card?.querySelector('h2')?.textContent?.trim() || 'Bộ đề thi';
        const subject = card?.querySelector('p.text-sm.text-slate-500')?.textContent?.split('·')[0]?.trim() || heading;
        const safeSubject = subject.replace(/[&<>]/g, character => ({'&':'&amp;','<':'&lt;','>':'&gt;'}[character]));
        const signatureMethod = window.bghSignatureMethod === 'upload' ? 'Ký bằng ảnh chữ ký' : 'Ký trực tiếp';
        const image = withSignature && window.bghSignatureData ? '<div class="signature-method">'+signatureMethod+'</div><img src="'+window.bghSignatureData+'" style="display:block;width:180px;height:90px;object-fit:contain;margin:8px auto">' : '<div style="height:100px"></div>';
        const preview = document.createElement('div'); preview.id='essay-print-preview';
        preview.innerHTML = '<div class="official-head"><div><b>TRƯỜNG CAO ĐẲNG HẬU CẦN 2</b><br><b>KHOA ĐIỀU DƯỠNG</b></div><div><b>CỘNG HÒA XÃ HỘI CHỦ NGHĨA VIỆT NAM</b><br>Độc lập - Tự do - Hạnh phúc</div></div><h1>BỘ CÂU HỎI - ĐÁP ÁN THI TỰ LUẬN</h1><div class="meta"><div>Môn: '+safeSubject+'</div><div>Thời gian: 60 phút</div></div><h2>BỘ ĐỀ VÀ ĐÁP ÁN</h2><div class="exam-questions">'+questionHtml+'</div><div class="signature"><div>BAN GIÁM HIỆU</div><div>(Ký, xác nhận)</div>'+image+'<strong>Người phê duyệt</strong></div>';
        const style = document.createElement('style'); style.id='essay-print-style'; style.textContent='@page{size:A4;margin:15mm}body.essay-printing>*:not(#essay-print-preview){display:none!important}#essay-print-preview{display:block!important;font-family:Arial,sans-serif;font-size:13px;line-height:1.45;color:#111827}#essay-print-preview h1{text-align:center;font-size:18px}#essay-print-preview h2{font-size:16px;border-bottom:2px solid #1e3a8a;padding-bottom:6px}#essay-print-preview section{page-break-inside:avoid;border:1px solid #cbd5e1;margin:12px 0;padding:10px}#essay-print-preview button,#essay-print-preview input,#essay-print-preview summary{display:none!important}#essay-print-preview .signature{margin-left:62%;text-align:center;margin-top:30px}#essay-print-preview .signature strong{display:block}';
        style.textContent += '#essay-print-preview{font-family:"Times New Roman",serif;font-size:14px}#essay-print-preview .official-head{display:block;text-align:center;line-height:1.35;margin-bottom:8px}#essay-print-preview .official-head>div{margin:0}#essay-print-preview .meta{display:block;text-align:left;margin:12px 0 10px;font-weight:700;line-height:1.35}#essay-print-preview .meta div{margin:2px 0}#essay-print-preview h1{margin:8px 0 12px;font-size:17px}#essay-print-preview h2{text-align:left;border:0;padding:0;margin:10px 0 8px;font-size:15px}#essay-print-preview section{border:0;margin:10px 0;padding:0}#essay-print-preview section>div:first-child{background:transparent!important;border:0!important;padding:5px 0!important;font-weight:700}#essay-print-preview .exam-questions section{page-break-inside:avoid}#essay-print-preview .signature-method{font-style:italic;font-size:12px;margin:4px 0}';
        document.head.appendChild(style); document.body.appendChild(preview); document.body.classList.add('essay-printing'); window.print(); savePrintedDocument(withSignature ? (expectedMethod === 'upload' ? 'image' : 'direct') : 'unsigned'); setTimeout(() => { document.body.classList.remove('essay-printing'); preview.remove(); style.remove(); }, 1000);
    };
    const bindForms = () => {
        const all = forms(); if (!all.length || !target) return;
        if (!target.dataset.bound) {
            target.dataset.bound='1';
            // Dự phòng cho trường hợp trình duyệt giữ HTML cũ chưa có option server.
            if (target.options.length === 1) {
                all.forEach((form, index) => { const option=document.createElement('option'); option.value=String(index); option.textContent=form.closest('.bg-white.border.rounded-xl')?.querySelector('h2')?.textContent?.trim() || ('Bộ đề '+(index+1)); target.appendChild(option); });
            }
            target.addEventListener('change', updatePrintButtons);
            printUnsigned.addEventListener('click', () => printPreview(false));
            printImage.addEventListener('click', () => printPreview(true, 'upload'));
            printDirect.addEventListener('click', () => printPreview(true, 'draw'));
            all.forEach(form => form.addEventListener('submit', event => {
                if (!window.bghSignatureData || window.bghSignatureData.length < 100) { event.preventDefault(); alert('Vui lòng tải ảnh chữ ký hoặc ký trực tiếp trước khi duyệt BGH.'); return; }
                [['signature_method',window.bghSignatureMethod],['signature_data',window.bghSignatureData]].forEach(([name,value])=>{ let input=form.querySelector('input[name="'+name+'"]'); if(!input){input=document.createElement('input'); input.type='hidden'; input.name=name; form.appendChild(input);} input.value=value; });
            }));
        }
        updatePrintButtons();
    };
    window.addEventListener('bgh-signature-ready', updatePrintButtons);
    document.addEventListener('DOMContentLoaded', bindForms, { once: true });
})();
</script>
@endif
<div class="flex justify-between items-center mb-5"><div><h1 class="text-2xl font-bold">Duyệt đề</h1><p class="text-sm text-slate-500">Đang ở cấp: {{ $stage }}. Bao gồm đề tự luận và ngân hàng trắc nghiệm LMS.</p></div><a href="{{ route('essay-exams.index') }}" class="text-blue-600">Đề của tôi</a></div>
@if(session('success'))<div class="mb-4 rounded-lg bg-emerald-50 text-emerald-800 px-4 py-3">{{ session('success') }}</div>@endif
<form id="essay-approval-filter" class="bg-white border rounded-xl p-4 mb-4 grid md:grid-cols-4 gap-3"><input type="hidden" name="stage" value="{{ $stage }}"><select name="specialization_id" class="border rounded-lg px-3 py-2"><option value="">Tất cả ngành đào tạo</option>@foreach($specializations as $item)<option value="{{ $item->id }}" @selected(request('specialization_id')==$item->id)>{{ $item->code }} — {{ $item->name }}</option>@endforeach</select><select name="subject_id" class="border rounded-lg px-3 py-2"><option value="">Tất cả môn học</option>@foreach($subjects as $item)<option value="{{ $item->id }}" @selected(request('subject_id')==$item->id)>{{ $item->code }} — {{ $item->name }}</option>@endforeach</select><input name="teacher" value="{{ request('teacher') }}" placeholder="Tên hoặc tài khoản giáo viên" class="border rounded-lg px-3 py-2"><select name="exam_id" class="border rounded-lg px-3 py-2"><option value="">Tất cả bộ đề</option>@foreach($examOptions as $item)<option value="{{ $item->id }}" data-subject="{{ $item->subject_id }}" data-teacher="{{ mb_strtolower(($item->created_by_display_name ?: '').' '.($item->created_by_username ?: '')) }}" @selected(request('exam_id')==$item->id)>{{ $item->code }} — {{ $item->title }} — {{ $item->created_by_display_name ?: $item->created_by_username }}</option>@endforeach</select></form>
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
@if($lmsBanks->isNotEmpty())<section class="mb-5 bg-teal-50 border border-teal-200 rounded-xl p-4"><div class="flex items-center justify-between mb-3"><div><h2 class="font-bold text-teal-900">Ngân hàng trắc nghiệm LMS</h2><p class="text-sm text-teal-700">Các ngân hàng đang ở cấp {{ $stage }}.</p></div><span class="px-2 py-1 rounded-full bg-white text-teal-800 text-xs font-semibold">{{ $lmsBanks->count() }} ngân hàng</span></div><form method="POST" action="{{ route('essay-exams.approval.lms-banks.bulk-approve') }}" class="mb-3 flex flex-wrap items-center gap-2">@csrf @foreach($lmsBanks as $bank)<label class="inline-flex items-center gap-1 rounded-lg bg-white border border-teal-100 px-2 py-1 text-xs"><input type="checkbox" name="bank_ids[]" value="{{ $bank->id }}">{{ $bank->title }}</label>@endforeach<button name="approve_all" value="1" class="px-3 py-2 rounded-lg bg-teal-700 text-white text-sm font-semibold">Duyệt tất cả</button><button class="px-3 py-2 rounded-lg bg-teal-600 text-white text-sm font-semibold">Duyệt đã chọn</button></form><div class="space-y-3">@foreach($lmsBanks as $bank)<div class="bg-white border border-teal-100 rounded-lg p-3 flex flex-wrap items-center justify-between gap-3"><div><div class="font-semibold text-slate-900">{{ $bank->title }}</div><div class="text-xs text-slate-500">{{ $bank->course?->title }} · {{ $bank->questions_count ?? $bank->questions->count() }} câu · {{ $bank->questions->groupBy('lms_lesson_id')->count() }} bài</div></div><form method="POST" action="{{ route('essay-exams.approval.lms-banks.approve', $bank) }}">@csrf<button class="px-3 py-2 rounded-lg bg-teal-600 text-white text-sm font-semibold">Duyệt ngân hàng trắc nghiệm</button></form></div>@endforeach</div></section>@endif
<div class="space-y-4">@forelse($exams as $exam)<div class="bg-white border rounded-xl p-5"><div class="flex flex-wrap justify-between gap-3"><div><h2 class="font-semibold text-lg">{{ $exam->code }} — {{ $exam->title }}</h2><p class="text-sm text-slate-500">{{ $exam->subject->code }} — {{ $exam->subject->name }} · {{ $exam->questions->where('paper_status',$stage)->groupBy('paper_number')->count() }} đề đang chờ</p><p class="text-sm text-slate-600">Giáo viên đề xuất: <b>{{ $exam->created_by_display_name ?: $exam->created_by_username }}</b></p></div><span class="px-2 py-1 rounded-full bg-amber-100 text-amber-800 text-xs">{{ $exam->status_label }}</span></div>
<form method="POST" action="{{ route('essay-exams.approve',$exam) }}">@csrf<input type="hidden" name="stage" value="{{ $stage }}"><details class="mt-3" open><summary class="cursor-pointer text-blue-600">Xem và chọn đề số để duyệt</summary><div class="mt-3 space-y-4">@foreach($exam->questions->where('paper_status',$stage)->groupBy('paper_number') as $paper => $questions)<section class="border rounded-lg overflow-hidden"><div class="px-4 py-3 bg-blue-50 border-b flex items-center justify-between"><div><b>Đề số {{ $paper }}</b><span class="text-sm text-slate-600"> — {{ $questions->count() }} câu · {{ number_format($questions->sum('points'),2,',','.') }} điểm</span></div><label class="text-sm"><input type="checkbox" name="paper_numbers[]" value="{{ $paper }}" checked> Chọn duyệt</label></div><div class="p-4 space-y-3">@foreach($questions as $q)<div class="border-l-2 border-slate-300 pl-3"><b>Câu {{ $q->question_number }} ({{ $q->points }} điểm)</b><div class="mt-1 whitespace-pre-line"><span class="font-medium">Câu hỏi:</span> {{ $q->content }}</div><div class="mt-1 text-sm text-slate-600 whitespace-pre-line"><span class="font-medium">Đáp án / barem:</span> {{ $q->answer ? $formatAnswer($q->answer) : 'Chưa nhập' }}</div></div>@endforeach</div></section>@endforeach</div></details><div class="flex flex-wrap gap-2 mt-4"><button class="px-4 py-2 rounded-lg bg-emerald-600 text-white">Duyệt các đề đã chọn</button></form><form method="POST" action="{{ route('essay-exams.return',$exam) }}" class="flex gap-2">@csrf<input name="return_note" required placeholder="Lý do trả lại" class="border rounded-lg px-3 py-2"><button class="px-4 py-2 rounded-lg bg-amber-500 text-white">Trả lại</button></form></div></div>@empty<div class="bg-white border rounded-xl p-10 text-center text-slate-500">Không có đề đang chờ ở cấp này.</div>@endforelse</div>
</div>
@endsection
