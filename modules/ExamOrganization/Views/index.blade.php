@extends('layouts.admin')
@section('title', 'Tổ chức thi')
@section('page-title', 'Tổ chức thi')
@section('content')
@include('partials.module-menu', ['module' => 'exam'])
@php($selectedPlan = $selectedPlan ?? null)
<style>
    .exam-page { max-width:1440px; margin:0 auto; color:#1e293b; }
    .exam-label { display:block; margin-bottom:.4rem; color:#1e293b; font-size:.9rem; font-weight:700; }
    .exam-input { min-height:44px; color:#0f172a; background:#fff; font-weight:600; border:1px solid #cbd5e1; border-radius:.65rem; padding:.65rem .85rem; width:100%; transition:.2s ease; }
    .exam-input:focus { outline:0; border-color:#2563eb; box-shadow:0 0 0 3px rgba(99,102,241,.15); }
    .exam-input:disabled { background:#f1f5f9; color:#94a3b8; cursor:not-allowed; }
    .exam-card { background:#fff; border:1px solid #dbe4ee; border-radius:.85rem; padding:1.25rem; box-shadow:0 5px 16px rgba(30,64,175,.07); }
    .exam-card:hover { border-color:#93c5fd; }
    .exam-step { min-height:92px; display:flex; flex-direction:column; justify-content:center; border-left:4px solid #bfdbfe; transition:.2s ease; }
    .exam-step:hover, .exam-step.active { border-left-color:#2563eb; background:#eff6ff; transform:translateY(-2px); }
    .exam-step .step-number { color:#2563eb; font-size:.8rem; font-weight:800; letter-spacing:.04em; }
    .exam-section-title { display:flex; align-items:center; gap:.7rem; margin-bottom:1.1rem; color:#0f172a; font-size:1.15rem; font-weight:800; }
    .exam-section-title:before { content:''; width:5px; height:24px; border-radius:999px; background:#2563eb; }
    .exam-card button { min-height:42px; transition:.2s ease; }
    .exam-card button:hover { filter:brightness(.96); transform:translateY(-1px); }
    .exam-card button:disabled { opacity:.55; cursor:not-allowed; transform:none; filter:none; }
    .exam-card table thead { background:#eef2ff; color:#1e40af; }
    .exam-card table tbody tr:hover { background:#f8fafc; }
    .exam-card table th { font-weight:800; white-space:nowrap; }
    .exam-card table td, .exam-card table th { padding:.8rem; }
    .exam-help { color:#64748b; font-size:.875rem; line-height:1.5; }
    .exam-guide { display:flex; align-items:flex-start; gap:.75rem; width:100%; max-width:none; margin:0 0 1.25rem; padding:1rem 1.15rem; border:1px solid #bfdbfe; border-left:5px solid #2563eb; border-radius:.85rem; background:#eff6ff; color:#1e3a8a; box-sizing:border-box; }
    .exam-guide strong { color:#172554; }
    .pre-exam-grid { grid-template-columns:repeat(2,minmax(0,1fr)) !important; max-width:none; margin:0; gap:1.25rem !important; }
    .pre-exam-grid > .exam-card { padding:1.35rem 1.5rem; }
    .pre-exam-grid > .exam-card h2 { display:flex; align-items:center; gap:.55rem; padding-bottom:.75rem; border-bottom:1px solid #e2e8f0; }
    .pre-exam-grid > .exam-card h2:before { content:' '; display:inline-flex; width:10px; height:10px; border-radius:50%; background:#2563eb; box-shadow:0 0 0 5px #dbeafe; }
    .exam-plan-picker { width:100%; max-width:none; margin:0 0 1.25rem !important; padding:1.35rem 1.5rem; background:linear-gradient(135deg,#eff6ff 0%,#fff 62%); border:2px solid #93c5fd; border-left:6px solid #2563eb; box-shadow:0 7px 20px rgba(37,99,235,.13); }
    .exam-page > .exam-plan-picker { display:block; width:100% !important; max-width:100% !important; margin-left:0 !important; margin-right:0 !important; align-self:stretch; box-sizing:border-box; }
    .exam-plan-picker .exam-label { display:flex; align-items:center; gap:.65rem; color:#0f172a; font-size:1rem; font-weight:800; margin-bottom:.5rem; }
    .exam-plan-picker .exam-label:before { content:'BƯỚC 1'; display:inline-flex; align-items:center; min-height:24px; padding:.15rem .55rem; border-radius:999px; background:#2563eb; color:#fff; font-size:.72rem; font-weight:800; letter-spacing:.04em; }
    .exam-plan-picker .exam-input { background:#fff; border-color:#93c5fd; color:#0f172a; font-size:1rem; font-weight:600; line-height:1.5; }
    .exam-plan-picker select.exam-input option { color:#0f172a; font-size:1rem; font-weight:600; }
    form.exam-card > h2, form.exam-card > h3 { color:#172554; font-size:1.05rem; line-height:1.35; }
    form.exam-card > h2:before, form.exam-card > h3:before { content:' '; display:inline-block; width:8px; height:8px; margin:0 .5rem .12rem 0; border-radius:50%; background:#3b82f6; }
    form.exam-card textarea { min-height:92px; resize:vertical; }
    form.exam-card > button { font-weight:800; box-shadow:0 4px 10px rgba(79,70,229,.16); }
    form.exam-card:has(input[name="process_type"]) { gap:1rem; }
    form.exam-card:has(input[name="process_type"]) select[multiple] { min-height:150px; }
    form.exam-card:has(input[name="process_type"]) input[type="file"] { padding:.5rem; background:#f8fafc; }
    form.exam-card:has(input[name="process_type"]) input[type="file"]::file-selector-button { margin-right:.75rem; border:0; border-radius:.45rem; padding:.5rem .75rem; color:#fff; background:#2563eb; font-weight:700; cursor:pointer; }
    .print-action { min-height:110px; display:flex; align-items:center; justify-content:center; background:linear-gradient(135deg,#f8faff,#eef2ff); }
    .print-action button { width:100%; min-height:54px; }
    .print-candidate-list { display:none; }
    .exam-card .bg-blue-600, .exam-card button.bg-blue-600 { background-color:#2563eb !important; }
    .exam-card .border-blue-300 { border-color:#93c5fd !important; }
    .exam-card .text-blue-700 { color:#1d4ed8 !important; }
    .exam-card .ring-blue-500 { --tw-ring-color:#3b82f6 !important; }
    .exam-page .bg-gradient-to-r { background:linear-gradient(90deg,#1e40af,#2563eb,#3b82f6) !important; }
    @media print { body * { visibility:hidden !important; } .print-candidate-list, .print-candidate-list * { visibility:visible !important; } .print-candidate-list { display:block !important; position:absolute; inset:0; padding:24px; color:#111; background:#fff; } .print-candidate-list h2 { text-align:center; } .print-candidate-list table { width:100%; border-collapse:collapse; margin-top:20px; } .print-candidate-list th, .print-candidate-list td { border:1px solid #333; padding:8px; text-align:left; } .print-candidate-list th { background:#eee; } .print-candidate-list .screen-only { display:none !important; } }
    .score-card { min-height:220px; display:flex; flex-direction:column; gap:.75rem; box-shadow:0 4px 14px rgba(15,23,42,.04); }
    .score-card h3 { color:#111827; font-size:1.05rem; }
    .score-card .exam-input { background:#f8fafc; }
    .score-card button { margin-top:auto; align-self:flex-start; }
    .verify-card { min-height:92px; display:flex; align-items:center; justify-content:center; background:#f8faff; }
    form.exam-card:has(input[name="candidate_number"]) { min-height:220px; display:flex; flex-direction:column; gap:.75rem; box-shadow:0 4px 14px rgba(15,23,42,.04); }
    form.exam-card:has(input[name="candidate_number"]) button { margin-top:auto; align-self:flex-start; }
    form.exam-card:has(input[name="process_type"][value="GRADING_ROOM"]) { grid-column:1 / -1; }
    form.exam-card:has(input[name="process_type"][value="GRADING_DIRECT"]), form.exam-card:has(input[name="process_type"][value="GRADING_PACKET"]), form.exam-card:has(input[name="process_type"][value="GRADING_ROOM"]), form.exam-card:has(input[name="process_type"][value="VERIFY_VIEW"]), form.exam-card:has(input[name="process_type"][value="VERIFY_PRINT"]) { display:none; }
    form:has(input[name="process_type"][value="ROOM_REGISTER"]) { display:none !important; }
    form:has(input[name="process_type"][value="CIPHER"]) { display:none !important; }
</style>
<div class="exam-page" data-turbo="false">
<div class="mb-6 rounded-2xl bg-gradient-to-r from-blue-700 via-blue-600 to-blue-600 p-6 text-white shadow-lg">
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div><p class="text-sm font-semibold uppercase tracking-wider text-blue-100">Quản lý kỳ thi</p><h1 class="mt-1 text-3xl font-extrabold">Tổ chức thi</h1><p class="mt-2 max-w-2xl text-blue-100">Lập kế hoạch, chuẩn bị danh sách, xử lý sau thi và in các tài liệu cần thiết.</p></div>
        <div class="hidden rounded-xl bg-white/15 px-5 py-4 text-center md:block"><div class="text-2xl font-extrabold">3.1 — 3.6</div><div class="text-xs text-blue-100">Quy trình tổ chức thi</div></div>
    </div>
</div>
<div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-7">
    @foreach([
        ['planning','3.1','Lập kế hoạch thi'],['pre_exam','3.2','Xử lý trước khi thi'],['post_exam','3.3','Xử lý sau thi'],
        ['grading','3.4','Nhập và kiểm dò điểm'],['marking_docs','3.5','Tài liệu chấm thi'],['results','3.6','Kết quả thi']
    ] as $item)
        <a href="{{ route('exam-organization.index',['section'=>$item[0]]) }}" class="exam-card exam-step {{ $section===$item[0]?'active ring-2 ring-blue-500':'' }}"><div class="step-number">{{ $item[1] }}</div><div class="mt-1 text-base font-bold text-slate-800">{{ $item[2] }}</div><div class="mt-2 text-xs text-slate-500">{{ $section===$item[0]?'Đang thao tác':'Mở chức năng' }} <span aria-hidden="true">→</span></div></a>
    @endforeach
</div>

@if($section === 'planning')
<div class="exam-section-title">Lập kế hoạch thi <span class="exam-help font-normal">Nhập thông tin chung để dùng cho các bước tiếp theo</span></div>
<div class="exam-guide"><span class="text-xl">ℹ️</span><div><strong>Trình tự tổ chức kỳ thi</strong><div class="mt-1 text-sm text-blue-900">Thực hiện lần lượt <b>3.1 lập kế hoạch thi</b>, <b>3.2 xử lý trước khi thi</b>, <b>3.3 xử lý sau thi</b> và <b>3.4 nhập, kiểm dò điểm</b>.</div></div></div>
<form method="POST" action="{{ route('exam-organization.plans.store') }}" class="exam-card grid md:grid-cols-2 gap-4 mb-6">@csrf
    <div><label class="exam-label">Loại kỳ thi</label><select name="exam_category" id="exam-category" required class="exam-input"><option value="REGULAR">Kiểm tra thường xuyên</option><option value="PERIODIC">Kiểm tra định kỳ</option><option value="FINAL_1">Thi kết thúc môn lần 1</option><option value="FINAL_2">Thi kết thúc môn lần 2</option><option value="OTHER">Khác</option></select></div>
    <div id="custom-exam-wrap" class="hidden"><label class="exam-label">Tên kỳ thi khác</label><input name="custom_exam_name" class="exam-input" placeholder="Nhập tên kỳ thi"></div>
    <input type="hidden" name="name" id="exam-name">
    <div><label class="exam-label">Môn thi</label><select name="subject_id" required class="exam-input"><option value="">Chọn môn thi</option>@foreach($subjects as $s)<option value="{{ $s->id }}">{{ $s->code }} — {{ $s->name }}</option>@endforeach</select></div>
    <div><label class="exam-label">Lớp thi</label><select name="class_id" required class="exam-input"><option value="">Chọn lớp thi</option>@foreach($classes as $c)<option value="{{ $c->id }}">{{ $c->code }} — {{ $c->name }}</option>@endforeach</select></div>
    <div><label class="exam-label">Dạng đề</label><select name="exam_type" required class="exam-input"><option>TRẮC NGHIỆM</option><option>THỰC HÀNH</option><option>TỰ LUẬN</option></select></div>
    <div><label class="exam-label">Ngày thi</label><input type="date" name="exam_date" required class="exam-input"></div>
    <div><label class="exam-label">Giờ thi</label><input type="time" name="exam_time" class="exam-input"></div>
    <input type="hidden" name="exam_form" value="{{ old('exam_type','TỰ LUẬN') }}">
    <textarea name="note" placeholder="Ghi chú" class="exam-input md:col-span-2"></textarea>
    <button class="md:col-span-2 px-5 py-2 rounded-lg bg-blue-600 text-white font-bold">Lập kế hoạch thi</button>
</form>
@elseif(in_array($section, ['pre_exam','post_exam'], true))
<form method="GET" class="exam-card exam-plan-picker mb-5"> <input type="hidden" name="section" value="{{ $section }}"><label class="exam-label">Chọn kế hoạch thi 3.1</label><select name="plan_id" onchange="this.form.submit()" required class="exam-input"><option value="">Chọn kỳ thi</option>@foreach($allPlans as $p)<option value="{{ $p->id }}" @selected($selectedPlanId===$p->id)>{{ $p->name }} — {{ $p->class->name ?? '' }} — {{ $p->subject->name ?? '' }}</option>@endforeach</select></form>
@if($selectedPlanId)
@if($section === 'pre_exam')
<div class="exam-guide"><span class="text-xl">ℹ️</span><div><strong>Trình tự xử lý trước khi thi</strong><div class="mt-1 text-sm text-blue-900">Bạn cần <b>đánh số báo danh</b> trước, sau đó <b>đăng ký phòng thi</b> và cuối cùng thực hiện <b>xếp phòng thi</b>.</div></div></div>
@elseif($section === 'post_exam')
<div class="exam-guide"><span class="text-xl">ℹ️</span><div><strong>Trình tự xử lý sau thi</strong><div class="mt-1 text-sm text-blue-900">Bạn cần <b>nhập SBD vắng/PQ</b>, sau đó <b>dồn túi</b> và cuối cùng <b>đánh phách</b>.</div></div></div>
@endif
<div class="grid md:grid-cols-2 gap-5">
    @if($section === 'pre_exam')
    <form method="POST" enctype="multipart/form-data" action="{{ route('exam-organization.process') }}" class="exam-card space-y-3">@csrf<input type="hidden" name="plan_id" value="{{ $selectedPlanId }}"><input type="hidden" name="process_type" value="CANDIDATE_NUMBER"><h2 class="font-bold text-lg">Đánh số báo danh</h2><label class="exam-label">Import danh sách học viên</label><input type="file" name="student_file" accept=".txt,.csv,.tsv" class="exam-input"><label class="exam-label">Cách đánh số</label><select name="method" class="exam-input"><option value="NAME_ASC">Theo vần tên</option><option value="CLASS_ASC">Theo thứ tự vần lớp</option><option value="RANDOM">Hoàn toàn ngẫu nhiên</option></select><button class="px-4 py-2 rounded-lg bg-blue-600 text-white font-bold">Tải lên và tạo SBD</button></form>
    <form method="POST" action="{{ route('exam-organization.process') }}" class="exam-card space-y-3">@csrf<input type="hidden" name="plan_id" value="{{ $selectedPlanId }}"><input type="hidden" name="process_type" value="ROOM_ASSIGN"><h2 class="font-bold text-lg">Đăng ký / xếp phòng thi</h2><label class="exam-label">Tên phòng thi</label><input name="room_name" class="exam-input" placeholder="Phòng 01"><label class="exam-label">Kiểu xếp</label><select name="method" class="exam-input"><option value="HORIZONTAL">Theo hàng ngang</option><option value="VERTICAL">Theo hàng dọc</option></select><button class="px-4 py-2 rounded-lg bg-blue-600 text-white font-bold">Xếp phòng và lưu log</button></form>
    @else
    <form method="POST" action="{{ route('exam-organization.process') }}" class="exam-card space-y-3">@csrf<input type="hidden" name="plan_id" value="{{ $selectedPlanId }}"><input type="hidden" name="process_type" value="ABSENT"><h2 class="font-bold text-lg">Nhập SBD vắng / PQ</h2><input name="from_number" required class="exam-input" placeholder="Nhập SBD vắng hoặc PQ"><button class="px-4 py-2 rounded-lg bg-blue-600 text-white font-bold">Lưu danh sách</button></form>
    <form method="POST" action="{{ route('exam-organization.process') }}" class="exam-card space-y-3">@csrf<input type="hidden" name="plan_id" value="{{ $selectedPlanId }}"><input type="hidden" name="process_type" value="CIPHER"><h2 class="font-bold text-lg">Đánh phách</h2><div class="grid grid-cols-2 gap-3"><input name="from_number" required class="exam-input" placeholder="Từ số"><input name="to_number" required class="exam-input" placeholder="Đến số"></div><button class="px-4 py-2 rounded-lg bg-blue-600 text-white font-bold">Đánh phách ngẫu nhiên và in</button></form>
    @endif
 </div>
@endif
@else
<form method="GET" class="exam-card exam-plan-picker mb-5"><input type="hidden" name="section" value="{{ $section }}"><label class="exam-label">Chọn kế hoạch thi 3.1</label><select name="plan_id" onchange="this.form.submit()" required class="exam-input"><option value="">Chọn kỳ thi</option>@foreach($allPlans as $p)<option value="{{ $p->id }}" @selected($selectedPlanId===$p->id)>{{ $p->name }} — {{ $p->class->name ?? '' }} — {{ $p->subject->name ?? '' }}</option>@endforeach</select></form>
 @if($selectedPlanId)
 @if($section === 'grading')
 <div class="exam-guide"><span class="text-xl">ℹ️</span><div><strong>Trình tự nhập và kiểm dò điểm</strong><div class="mt-1 text-sm text-blue-900">Chọn cách <b>nhập điểm</b>, lưu bảng điểm rồi thực hiện <b>kiểm dò điểm</b>.</div></div></div>
 @elseif($section === 'marking_docs')
 <div class="exam-guide"><span class="text-xl">ℹ️</span><div><strong>Tài liệu chấm thi</strong><div class="mt-1 text-sm text-blue-900">Chọn tài liệu cần xem và <b>in tài liệu</b>.</div></div></div>
 @elseif($section === 'results')
 <div class="exam-guide"><span class="text-xl">ℹ️</span><div><strong>Kết quả thi</strong><div class="mt-1 text-sm text-blue-900">Chọn loại kết quả cần xem và <b>in bảng kết quả</b>.</div></div></div>
 @endif
<div class="grid md:grid-cols-2 gap-4">
    @if($section === 'grading')
        @foreach([['GRADING_DIRECT','Nhập điểm trực tiếp vào danh sách'],['GRADING_PACKET','Nhập điểm vào từng túi'],['GRADING_ROOM','Nhập điểm theo phòng thi']] as $grade)
        <form method="POST" action="{{ route('exam-organization.process') }}" class="exam-card space-y-2">@csrf<input type="hidden" name="plan_id" value="{{ $selectedPlanId }}"><input type="hidden" name="process_type" value="{{ $grade[0] }}"><h3 class="font-bold">{{ $grade[1] }}</h3><input name="candidate_number" required class="exam-input" placeholder="Số báo danh"><input name="score" required type="number" step="0.01" min="0" max="10" class="exam-input" placeholder="Điểm"><button class="px-4 py-2 rounded-lg bg-blue-600 text-white font-bold">Lưu điểm</button></form>
        @endforeach
        @foreach([['VERIFY_VIEW','Kiểm dò điểm — Xem bảng'],['VERIFY_PRINT','Kiểm dò điểm — In bảng']] as $verify)<form method="POST" action="{{ route('exam-organization.process') }}" class="exam-card">@csrf<input type="hidden" name="plan_id" value="{{ $selectedPlanId }}"><input type="hidden" name="process_type" value="{{ $verify[0] }}"><button class="w-full px-4 py-2 rounded-lg border border-blue-300 text-blue-700 font-bold">{{ $verify[1] }}</button></form>@endforeach
    @elseif($section === 'marking_docs')
        @foreach([['DOC_PACKET','Hướng dẫn dồn túi'],['DOC_CIPHER','Hướng dẫn đánh số phách'],['DOC_MINUTES','Biên bản chấm thi']] as $doc)<form method="POST" action="{{ route('exam-organization.process') }}" class="exam-card">@csrf<input type="hidden" name="plan_id" value="{{ $selectedPlanId }}"><input type="hidden" name="process_type" value="{{ $doc[0] }}"><button class="w-full px-4 py-2 rounded-lg border border-blue-300 text-blue-700 font-bold">In {{ $doc[1] }}</button></form>@endforeach
    @else
        @foreach([['RESULT_CLASS','Kết quả lớp'],['RESULT_GOOD','Danh sách học viên giỏi'],['RESULT_WEAK','Danh sách học viên kém'],['RESULT_STATISTICS','Thống kê điểm thi']] as $report)<form method="POST" action="{{ route('exam-organization.process') }}" class="exam-card">@csrf<input type="hidden" name="plan_id" value="{{ $selectedPlanId }}"><input type="hidden" name="process_type" value="{{ $report[0] }}"><button class="w-full px-4 py-2 rounded-lg border border-blue-300 text-blue-700 font-bold">In {{ $report[1] }}</button></form>@endforeach
    @endif
</div>
@endif
@endif

<div class="exam-card overflow-auto mt-6"><h2 class="font-bold text-lg mb-3">Danh sách kế hoạch thi</h2><table class="w-full text-sm"><thead><tr class="border-b"><th class="text-left p-2">Kỳ thi</th><th class="text-left p-2">Môn</th><th class="text-left p-2">Lớp</th><th class="text-left p-2">Dạng đề</th><th class="text-left p-2">Ngày thi</th><th class="text-left p-2">Giờ thi</th></tr></thead><tbody>@forelse($plans as $p)<tr class="border-b"><td class="p-2">{{ $p->name }}</td><td class="p-2">{{ $p->subject->name ?? '—' }}</td><td class="p-2">{{ $p->class->name ?? '—' }}</td><td class="p-2">{{ $p->exam_type }}</td><td class="p-2">{{ $p->exam_date?->format('d/m/Y') }}</td><td class="p-2 font-semibold">{{ $p->exam_time ? \Illuminate\Support\Carbon::parse($p->exam_time)->format('H:i') : '—' }}</td></tr>@empty<tr><td colspan="6" class="p-5 text-center">Chưa có kế hoạch.</td></tr>@endforelse</tbody></table></div>
@if($section === 'grading' && $selectedPlanId)
<div class="exam-card mt-6">
    <h2 class="text-xl font-bold text-slate-900 mb-1">Bảng nhập điểm</h2>
    <p class="text-sm text-slate-500 mb-4">Chọn phạm vi nhập điểm. Sau khi nhập xong từng dòng, bấm <b>Lưu bảng điểm</b>.</p>
    <form method="GET" class="grid md:grid-cols-3 gap-3 mb-5"><input type="hidden" name="section" value="grading"><input type="hidden" name="plan_id" value="{{ $selectedPlanId }}"><div><label class="exam-label">Cách nhập điểm</label><select name="grading_mode" onchange="this.form.submit()" class="exam-input"><option value="direct" @selected($gradingMode==='direct')>Trực tiếp trên danh sách</option><option value="room" @selected($gradingMode==='room')>Theo phòng thi</option><option value="packet" @selected($gradingMode==='packet')>Theo túi</option></select></div><div><label class="exam-label">Phòng thi</label><select name="room_name" onchange="this.form.submit()" class="exam-input" @disabled($gradingMode!=='room')><option value="">Chọn phòng thi</option>@foreach($rooms as $room)<option value="{{ $room }}" @selected($gradingRoom===$room)>{{ $room }}</option>@endforeach</select></div><div><label class="exam-label">Túi</label><select name="packet_number" onchange="this.form.submit()" class="exam-input" @disabled($gradingMode!=='packet')><option value="">Chọn túi</option>@foreach($packets as $packet)<option value="{{ $packet }}" @selected($gradingPacket===$packet)>{{ $packet }}</option>@endforeach</select></div></form>
    @if($gradingMode==='direct' || ($gradingMode==='room' && $gradingRoom) || ($gradingMode==='packet' && $gradingPacket))
    <form method="POST" action="{{ route('exam-organization.process') }}">@csrf<input type="hidden" name="plan_id" value="{{ $selectedPlanId }}"><input type="hidden" name="process_type" value="{{ $gradingMode==='direct' ? 'GRADING_DIRECT' : ($gradingMode==='room' ? 'GRADING_ROOM' : 'GRADING_PACKET') }}"><div class="overflow-x-auto border rounded-lg"><table class="w-full text-sm"><thead class="bg-slate-100"><tr><th class="p-3 text-left">STT</th><th class="p-3 text-left">SBD</th><th class="p-3 text-left">Họ và tên</th><th class="p-3 text-left">Lớp</th><th class="p-3 text-left">Phòng / Túi</th><th class="p-3 text-left">Điểm</th></tr></thead><tbody>@forelse($candidates as $i=>$candidate)<tr class="border-t"><td class="p-3">{{ $i+1 }}</td><td class="p-3 font-bold">{{ $candidate->candidate_number }}</td><td class="p-3">{{ $candidate->student_name }}</td><td class="p-3">{{ $candidate->class_name }}</td><td class="p-3">{{ $candidate->room_name ?? $candidate->packet_number ?? '—' }}</td><td class="p-3"><input name="scores[{{ $candidate->id }}]" value="{{ $candidate->score }}" type="number" step="0.01" min="0" max="10" class="exam-input max-w-32" placeholder="Nhập điểm"></td></tr>@empty<tr><td colspan="6" class="p-6 text-center text-slate-500">Chưa có danh sách học viên. Hãy đánh SBD ở mục 3.2 trước.</td></tr>@endforelse</tbody></table></div><button class="mt-4 px-5 py-2 rounded-lg bg-blue-600 text-white font-bold">Lưu bảng điểm</button></form>
     @endif
     @php($gradingLogs = $allGradingLogs ?? collect())
     @if($gradingLogs->count())
     <div class="exam-card mt-5 overflow-auto">
         <h2 class="font-bold text-lg mb-1">Nhật ký nhập điểm</h2>
         <p class="exam-help mb-3">Bảng điểm chỉ được xem hoặc in sau khi đã bấm lưu.</p>
         <table class="w-full text-sm"><thead><tr><th class="text-left p-3">Tên kỳ thi</th><th class="text-left p-3">Lớp</th><th class="text-left p-3">Môn</th><th class="text-left p-3">Xem bảng điểm</th><th class="text-left p-3">In bảng điểm</th></tr></thead><tbody>@foreach($gradingLogs as $gradingLog)<tr class="border-t"><td class="p-3">{{ $gradingLog->plan->name ?? '—' }}</td><td class="p-3">{{ $gradingLog->plan->class->name ?? '—' }}</td><td class="p-3">{{ $gradingLog->plan->subject->name ?? '—' }}</td><td class="p-3"><button type="button" data-view-score data-plan-id="{{ $gradingLog->plan_id }}" class="px-3 py-2 rounded-lg border border-blue-300 text-blue-700 font-bold">Xem bảng điểm</button></td><td class="p-3"><button type="button" data-print-score data-plan-id="{{ $gradingLog->plan_id }}" class="px-3 py-2 rounded-lg bg-blue-600 text-white font-bold">In bảng điểm</button></td></tr>@endforeach</tbody></table>
     </div>
     @endif
 </div>
 @endif
@if($section === 'post_exam' && $selectedPlanId)
<form method="POST" action="{{ route('exam-organization.process') }}" class="exam-card mt-5">@csrf<input type="hidden" name="plan_id" value="{{ $selectedPlanId }}"><input type="hidden" name="process_type" value="PACKET"><h2 class="font-bold text-lg mb-3">Dồn túi / Đánh phách</h2><div class="space-y-4"></div><button class="mt-3 px-5 py-2 rounded-lg bg-blue-600 text-white font-bold">Lưu dồn túi / đánh phách</button></form>
  @if(false)
  <div class="exam-card mt-5 overflow-auto"><h2 class="font-bold text-lg mb-3">Nhật ký túi</h2><table class="w-full text-sm"><thead><tr><th class="text-left p-3">Tên kỳ thi</th><th class="text-left p-3">Lớp</th><th class="text-left p-3">Môn</th><th class="text-left p-3">Số túi</th><th class="text-left p-3">Số lượng học viên trong túi</th><th class="text-left p-3">Thời gian dồn túi</th></tr></thead><tbody>@foreach($packetLogs as $packetLog)<tr class="border-t"><td class="p-3">{{ ($selectedPlan ?? null)?->name ?? '—' }}</td><td class="p-3">{{ ($selectedPlan ?? null)?->class?->name ?? '—' }}</td><td class="p-3">{{ ($selectedPlan ?? null)?->subject?->name ?? '—' }}</td><td class="p-3 font-bold">{{ $packetLog->method ?? '—' }}</td><td class="p-3">{{ $packetCandidateCounts[$packetLog->method] ?? 0 }}</td><td class="p-3">{{ $packetLog->created_at?->format('d/m/Y H:i:s') }}</td></tr>@endforeach</tbody></table></div>
 @endif
 @endif
 @if($section === 'post_exam' && ($allPacketLogs ?? collect())->count())
 <div class="exam-card mt-5 overflow-auto"><h2 class="font-bold text-lg mb-3">Nhật ký túi</h2><p class="exam-help mb-3">Nhật ký dùng chung cho toàn bộ mục 3.3, không phụ thuộc kế hoạch đang chọn.</p><table class="w-full text-sm"><thead><tr><th class="text-left p-3">Tên kỳ thi</th><th class="text-left p-3">Lớp</th><th class="text-left p-3">Môn</th><th class="text-left p-3">Số túi</th><th class="text-left p-3">Số lượng học viên trong túi</th><th class="text-left p-3">Thời gian dồn túi</th><th class="text-left p-3">In bảng</th></tr></thead><tbody>@foreach($allPacketLogs as $packetLog)<tr class="border-t"><td class="p-3">{{ $packetLog->plan->name ?? '—' }}</td><td class="p-3">{{ $packetLog->plan->class->name ?? '—' }}</td><td class="p-3">{{ $packetLog->plan->subject->name ?? '—' }}</td><td class="p-3 font-bold">{{ $packetLog->method ?? '—' }}</td><td class="p-3">{{ $allPacketCandidateCounts[$packetLog->plan_id.'|'.$packetLog->method] ?? 0 }}</td><td class="p-3">{{ $packetLog->created_at?->format('d/m/Y H:i:s') }}</td><td class="p-3"><button type="button" data-print-packet data-packet-key="{{ $packetLog->plan_id.'|'.$packetLog->method }}" class="px-3 py-2 rounded-lg bg-blue-600 text-white font-bold">In bảng</button></td></tr>@endforeach</tbody></table></div>
 @endif
@if($selectedPlanId && $selectedLogs->count())
@if(false)
<div class="exam-card overflow-auto mt-6"><h2 class="font-bold text-lg mb-3">Nhật ký xử lý kế hoạch thi</h2><table class="w-full text-sm"><thead><tr class="border-b"><th class="p-2 text-left">Nghiệp vụ</th><th class="p-2 text-left">Phương thức</th><th class="p-2 text-left">File</th><th class="p-2 text-left">Thời gian</th></tr></thead><tbody>@foreach($selectedLogs as $log)<tr class="border-b"><td class="p-2">{{ $log->process_type }}</td><td class="p-2">{{ $log->method ?? '—' }}</td><td class="p-2">{{ $log->file_name ?? '—' }}</td><td class="p-2">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td></tr>@endforeach</tbody></table></div>
@endif
@endif
<script>
const category=document.getElementById('exam-category'), custom=document.getElementById('custom-exam-wrap'), customInput=document.querySelector('[name="custom_exam_name"]');
document.querySelectorAll('.exam-plan-picker .exam-label').forEach(label => { label.textContent = 'Chọn kế hoạch thi'; });
if(category){ const sync=()=>{custom?.classList.toggle('hidden',category.value!=='OTHER'); if(customInput)customInput.required=category.value==='OTHER';}; category.addEventListener('change',sync); sync(); }
</script>
<script>
const logLabels = {
  VERIFY_VIEW:'Kiểm dò điểm — Xem bảng', VERIFY_PRINT:'Kiểm dò điểm — In bảng',
  CANDIDATE_NUMBER:'Đánh số báo danh', ROOM_ASSIGN:'Xếp phòng thi', CIPHER:'Đánh phách',
  GRADING_DIRECT:'Nhập điểm trực tiếp', GRADING_PACKET:'Nhập điểm theo túi', GRADING_ROOM:'Nhập điểm theo phòng',
  ABSENT:'Nhập SBD vắng / PQ', PACKET:'Dồn túi', ROOM_REGISTER:'Đăng ký phòng thi',
  DOC_PACKET:'Hướng dẫn dồn túi', DOC_CIPHER:'Hướng dẫn đánh số phách', DOC_MINUTES:'Biên bản chấm thi',
  RESULT_CLASS:'Kết quả lớp', RESULT_GOOD:'Danh sách học viên giỏi', RESULT_WEAK:'Danh sách học viên kém', RESULT_STATISTICS:'Thống kê điểm thi'
};
const candidateForm = document.querySelector('form input[name="process_type"][value="CANDIDATE_NUMBER"]')?.closest('form');
const selectedProcessLogs = @json($selectedLogs ?? []);
const candidateListForPrint = @json($candidates ?? []);
const selectedPlanClass = @json($selectedPlan?->class?->name ?? '');
const selectedPlanSubject = @json($selectedPlan?->subject?->name ?? '');
const selectedPlanName = @json($selectedPlan?->name ?? '');
let processLogCard = null;
window.printClassResult = () => {
  const printWindow = window.open('', '_blank'); if (!printWindow) return;
  const rows = [...candidateListForPrint]; const split = Math.ceil(rows.length / 2); const columns = [rows.slice(0, split), rows.slice(split)];
   const doc = printWindow.document; doc.open(); doc.close();
   const compactStyle = doc.createElement('style'); compactStyle.textContent = '@page{size:A4 landscape!important;margin:6mm!important}body{font-size:13px!important;zoom:1!important}.head{font-size:15px!important}.head strong{font-size:16px!important}.class-title{font-size:17px!important;margin:18px 0 2px!important}.columns{gap:18px!important}.result-table th,.result-table td{padding:2px 4px!important;height:18px!important;line-height:18px!important}.result-table .stt{width:34px!important}.result-table .code{width:78px!important}.result-table .score{width:42px!important}.summary{font-size:13px!important;margin-top:5px!important;padding-top:3px!important;line-height:1.25!important}.stats{font-size:13px!important;padding:3px 0!important}.stats td{padding:2px 4px!important}.stats th{padding:2px!important}'; doc.head.appendChild(compactStyle);
  const style = doc.createElement('style'); style.textContent = '@page{size:A4 landscape;margin:10mm}body{font-family:"Times New Roman",serif;color:#111;font-size:14px;margin:0}.head{display:grid;grid-template-columns:1fr 1fr;text-align:center;line-height:1.2;font-size:16px}.head strong{font-size:17px}.class-title{text-align:center;font-weight:bold;font-size:18px;margin:66px 0 2px}.columns{display:grid;grid-template-columns:1fr 1fr;gap:38px}.result-table{width:100%;border-collapse:collapse;table-layout:fixed}.result-table th,.result-table td{border-top:1px solid #111;border-bottom:1px solid #111;padding:3px 5px;height:20px}.result-table th{text-align:center}.result-table td:first-child,.result-table td:nth-child(3),.result-table td:nth-child(4){text-align:center}.result-table .stt{width:38px}.result-table .code{width:78px}.result-table .score{width:42px}.result-table .status{background:#eee;font-weight:bold}.summary{width:50%;margin:8px 0 0 auto;border-top:1px solid #111;padding-top:4px;line-height:1.35}.summary strong{font-weight:bold}.stats{width:50%;margin-left:auto;border-bottom:1px solid #111;padding:4px 0}.stats table{width:100%;border-collapse:collapse}.stats td{padding:2px 4px}.stats th{text-align:center;border-bottom:1px solid #111;padding:3px}.print-actions{display:none}'; doc.head.appendChild(style);
  const head = doc.createElement('div'); head.className='head'; head.innerHTML='<div>TRƯỜNG CAO ĐẲNG HẬU CẦN 2<br><strong>BAN KHẢO THÍ ĐBCLGDĐT</strong></div><div><strong>MÔN HỌC: '+(selectedPlanSubject || 'TÊN HỌC PHẦN')+'</strong><br>KỲ THI '+(selectedPlanName || '')+'<br><strong>KHỐI, LỚP: '+(selectedPlanClass || '')+'</strong></div>'; doc.body.appendChild(head);
  const allRows = []; columns.forEach((column, index) => { const wrap=doc.createElement('div'); const title=doc.createElement('div'); title.className='class-title'; title.textContent=selectedPlanClass || ''; wrap.appendChild(title); const table=doc.createElement('table'); table.className='result-table'; table.innerHTML='<thead><tr><th class="stt">Stt</th><th>Họ và tên học viên</th><th class="code">Mshv</th><th class="score">Điểm</th></tr></thead><tbody></tbody>'; const tbody=table.querySelector('tbody'); column.forEach((candidate,rowIndex)=>{ const tr=doc.createElement('tr'); const score=candidate.absent ? 'vắng' : (candidate.score ?? ''); [index*split+rowIndex+1,candidate.student_name || '',candidate.student_code || '',score].forEach((value,valueIndex)=>{const td=doc.createElement('td');td.textContent=value; if(valueIndex===3 && score==='vắng') td.className='status'; tr.appendChild(td);}); tbody.appendChild(tr); }); wrap.appendChild(table); allRows.push(wrap); }); const columnsBox=doc.createElement('div'); columnsBox.className='columns'; allRows.forEach(column=>columnsBox.appendChild(column)); doc.body.appendChild(columnsBox);
  const absent=rows.filter(candidate=>candidate.absent).length; const scored=rows.filter(candidate=>!candidate.absent && candidate.score !== null && candidate.score !== '').length; const summary=doc.createElement('div'); summary.className='summary'; summary.innerHTML='<div>Diện miễn trừ (MT), bảo lưu (BL) <strong>0</strong></div><div>Diện không được thi (KĐT): <strong>0</strong></div><div>Diện vắng thi: <strong>'+absent+'</strong></div><div>Dự thi, kt tra <strong>'+scored+'</strong></div>'; doc.body.appendChild(summary);
  const stats=doc.createElement('div'); stats.className='stats'; stats.innerHTML='<table><thead><tr><th>Hệ QS</th><th>Hệ DS</th><th>Kết quả chung</th></tr></thead><tbody><tr><td>Xuất sắc</td><td></td><td></td></tr><tr><td>Giỏi</td><td></td><td></td></tr><tr><td>Khá</td><td></td><td></td></tr><tr><td>T.bình</td><td></td><td></td></tr><tr><td>Yếu</td><td></td><td></td></tr><tr><td>Kém</td><td></td><td></td></tr></tbody></table>'; doc.body.appendChild(stats); printWindow.focus(); printWindow.print();
};
const selectedPlanSystem = @json($selectedPlan?->class?->specialization?->trainingSystem?->name ?? '');
const resultLevel = score => { const value=Number(score); if (!Number.isFinite(value)) return 'Không đạt'; if (value >= 8) return 'Giỏi'; if (value >= 6.5) return 'Khá'; if (value >= 5) return 'T.bình'; return 'Kém'; };
const resultRows = () => candidateListForPrint.filter(candidate => !candidate.absent && candidate.score !== null && candidate.score !== '').map(candidate => ({...candidate, level: resultLevel(candidate.score)}));
window.printFilteredResult = (kind) => {
  const rows=resultRows().filter(candidate => kind === 'GOOD' ? Number(candidate.score) >= 8 : Number(candidate.score) < 5); const win=window.open('', '_blank'); if(!win) return; const doc=win.document; doc.open(); doc.close();
  const style=doc.createElement('style'); style.textContent='@page{size:A4 portrait;margin:15mm}body{font-family:"Times New Roman",serif;font-size:14px;color:#111}.head{display:grid;grid-template-columns:1fr 1fr;text-align:center;line-height:1.25}.head strong{font-size:16px}.title{text-align:center;font-weight:bold;font-size:17px;margin:35px 0 20px}.result-list{width:90%;margin:auto;border-collapse:collapse}.result-list th,.result-list td{border-top:1px solid #111;border-bottom:1px solid #111;padding:5px}.result-list th{text-align:center}.result-list td:first-child,.result-list td:nth-child(3),.result-list td:nth-child(4){text-align:center}.result-list .score{text-align:center}.sign{margin-top:35px;text-align:right;font-style:italic}'; doc.head.appendChild(style);
  const head=doc.createElement('div'); head.className='head'; head.innerHTML='<div>TRƯỜNG CAO ĐẲNG HẬU CẦN 2<br><strong>BAN KHẢO THÍ ĐBCLGDĐT</strong></div><div><strong>MÔN HỌC: '+(selectedPlanSubject||'TÊN HỌC PHẦN')+'</strong><br>KỲ THI '+(selectedPlanName||'')+'<br><strong>KHỐI, LỚP: '+(selectedPlanClass||'')+'</strong></div>'; doc.body.appendChild(head);
  const title=doc.createElement('div'); title.className='title'; title.textContent='Danh sách học viên '+(kind==='GOOD'?'giỏi':'không đạt'); doc.body.appendChild(title);
  const table=doc.createElement('table'); table.className='result-list'; table.innerHTML='<thead><tr><th>Stt</th><th>Họ và tên học viên</th><th>Mshv</th><th>Điểm</th></tr></thead><tbody></tbody>'; const tbody=table.querySelector('tbody'); rows.forEach((candidate,index)=>{const tr=doc.createElement('tr'); [index+1,candidate.student_name||'',candidate.student_code||'',candidate.score].forEach(value=>{const td=doc.createElement('td');td.textContent=value;tr.appendChild(td);});tbody.appendChild(tr);}); if(!rows.length){const tr=doc.createElement('tr');tr.innerHTML='<td colspan="4">Không có học viên thuộc nhóm này.</td>';tbody.appendChild(tr);} doc.body.appendChild(table); const sign=doc.createElement('div');sign.className='sign';sign.textContent='Ngày ...... tháng ...... năm '+new Date().getFullYear();doc.body.appendChild(sign);win.focus();win.print();
};
window.printScoreStatistics = () => {
  const rows=resultRows(); const counts={Giỏi:0,Khá:0,'T.bình':0,Kém:0}; rows.forEach(candidate=>counts[candidate.level]++); const total=candidateListForPrint.length; const win=window.open('', '_blank'); if(!win)return; const doc=win.document; doc.open();doc.close(); const style=doc.createElement('style');style.textContent='@page{size:A4 portrait;margin:15mm}body{font-family:"Times New Roman",serif;font-size:14px}.head{display:grid;grid-template-columns:1fr 1fr;text-align:center;line-height:1.25}.head strong{font-size:16px}.title{text-align:center;font-weight:bold;font-size:17px;margin:20px 0}.stats{width:75%;margin:auto;border-collapse:collapse}.stats th,.stats td{border:1px solid #111;padding:7px}.stats th{text-align:center}.stats td:not(:first-child){text-align:center}.note{margin:35px 0 0 8%;font-style:italic}.sign{text-align:right;margin:35px 10% 0 0;font-weight:bold}';doc.head.appendChild(style);const head=doc.createElement('div');head.className='head';head.innerHTML='<div>TRƯỜNG CAO ĐẲNG HẬU CẦN 2<br><strong>BAN KHẢO THÍ ĐBCLGDĐT</strong></div><div><strong>THỐNG KÊ ĐIỂM THI</strong><br>MÔN HỌC: '+(selectedPlanSubject||'TÊN HỌC PHẦN')+'<br>Điểm thi '+(selectedPlanName||'')+'<br><strong>CÁC LỚP: '+(selectedPlanClass||'')+'</strong></div>';doc.body.appendChild(head);const title=doc.createElement('div');title.className='title';title.textContent='THỐNG KÊ ĐIỂM THI';doc.body.appendChild(title);const table=doc.createElement('table');table.className='stats';table.innerHTML='<thead><tr><th>Điểm</th><th>Số hv</th><th>Tỉ lệ %</th><th>Hệ QS</th><th>Hệ DS</th></tr></thead><tbody><tr><td>Tổng dự thi</td><td>'+total+'</td><td>100</td><td></td><td></td></tr><tr><td colspan="5"><strong>Cộng dự thi:</strong></td></tr><tr><td>Xuất sắc</td><td>0</td><td>0</td><td></td><td></td></tr><tr><td>Giỏi</td><td>'+counts.Giỏi+'</td><td>'+(total?((counts.Giỏi/total)*100).toFixed(1):0)+'</td><td></td><td></td></tr><tr><td>Khá</td><td>'+counts.Khá+'</td><td>'+(total?((counts.Khá/total)*100).toFixed(1):0)+'</td><td></td><td></td></tr><tr><td>T.bình</td><td>'+counts['T.bình']+'</td><td>'+(total?((counts['T.bình']/total)*100).toFixed(1):0)+'</td><td></td><td></td></tr><tr><td>Không đạt</td><td>'+counts.Kém+'</td><td>'+(total?((counts.Kém/total)*100).toFixed(1):0)+'</td><td></td><td></td></tr></tbody>';doc.body.appendChild(table);const note=doc.createElement('div');note.className='note';note.textContent='Vắng thi, đình chỉ thi: '+candidateListForPrint.filter(candidate=>candidate.absent).length;doc.body.appendChild(note);const sign=doc.createElement('div');sign.className='sign';sign.textContent='Ngày ...... tháng ...... năm '+new Date().getFullYear()+'\nTRƯỞNG BAN';doc.body.appendChild(sign);win.focus();win.print();
};
if (new URLSearchParams(window.location.search).get('section') === 'results') { document.querySelectorAll('form input[name="process_type"]').forEach(input=>{const form=input.closest('form'); if(!form)return; if(input.value==='RESULT_CLASS')form.addEventListener('submit',event=>{event.preventDefault();window.printClassResult();}); if(input.value==='RESULT_GOOD')form.addEventListener('submit',event=>{event.preventDefault();window.printFilteredResult('GOOD');}); if(input.value==='RESULT_WEAK')form.addEventListener('submit',event=>{event.preventDefault();window.printFilteredResult('WEAK');}); if(input.value==='RESULT_STATISTICS')form.addEventListener('submit',event=>{event.preventDefault();window.printScoreStatistics();});}); }
window.printCandidateList = () => {
  const printWindow = window.open('', '_blank');
  if (!printWindow) { alert('Trình duyệt đang chặn tab mới. Hãy cho phép mở cửa sổ bật lên để in danh sách.'); return; }
  const doc = printWindow.document; doc.open(); doc.close(); doc.title = 'Danh sách học viên và SBD';
  const style = doc.createElement('style'); style.textContent = 'body{font-family:Arial,sans-serif;padding:28px;color:#111}h1{text-align:center;font-size:22px}p{text-align:center;color:#555}.actions{text-align:center;margin:18px 0}.actions button{padding:10px 18px;background:#2563eb;color:#fff;border:0;border-radius:6px;font-weight:700;cursor:pointer}table{width:100%;border-collapse:collapse;margin-top:20px}th,td{border:1px solid #333;padding:9px;text-align:left}th{background:#e5e7eb} @media print{.actions{display:none}body{padding:0}}'; doc.head.appendChild(style);
  const heading = doc.createElement('h1'); heading.textContent = 'DANH SÁCH HỌC VIÊN VÀ SỐ BÁO DANH'; doc.body.appendChild(heading);
  const note = doc.createElement('p'); note.textContent = 'Lớp: ' + selectedPlanClass; doc.body.appendChild(note);
  const actions = doc.createElement('div'); actions.className = 'actions'; const printButton = doc.createElement('button'); printButton.textContent = 'In bảng này'; printButton.addEventListener('click', () => printWindow.print()); actions.appendChild(printButton); doc.body.appendChild(actions);
  const table = doc.createElement('table'); const thead = doc.createElement('thead'); const headRow = doc.createElement('tr'); ['STT','SBD','Họ và tên','Lớp'].forEach(text => { const th = doc.createElement('th'); th.textContent = text; headRow.appendChild(th); }); thead.appendChild(headRow); table.appendChild(thead);
  const tbody = doc.createElement('tbody'); candidateListForPrint.forEach((candidate, index) => { const row = doc.createElement('tr'); [index + 1, candidate.candidate_number || '', candidate.student_name || '', candidate.class_name || selectedPlanClass].forEach(value => { const td = doc.createElement('td'); td.textContent = value; row.appendChild(td); }); tbody.appendChild(row); }); if (!candidateListForPrint.length) { const row = doc.createElement('tr'); const td = doc.createElement('td'); td.colSpan = 4; td.textContent = 'Chưa có học viên đã đánh số báo danh.'; row.appendChild(td); tbody.appendChild(row); } table.appendChild(tbody); doc.body.appendChild(table); printWindow.focus();
};
window.printRoomList = () => {
  const roomPrintCandidates = [...candidateListForPrint].sort((a,b) => Number(a.seat_number||0) - Number(b.seat_number||0));
  const printWindow = window.open('', '_blank');
  if (!printWindow) { alert('Trình duyệt đang chặn tab mới. Hãy cho phép mở cửa sổ bật lên để in danh sách.'); return; }
  const doc = printWindow.document; doc.open(); doc.close(); doc.title = 'Danh sách xếp phòng thi';
  const style = doc.createElement('style'); style.textContent = 'body{font-family:Arial,sans-serif;padding:28px;color:#111}h1{text-align:center;font-size:22px}p{text-align:center;color:#555}.actions{text-align:center;margin:18px 0}.actions button{padding:10px 18px;background:#2563eb;color:#fff;border:0;border-radius:6px;font-weight:700;cursor:pointer}table{width:100%;border-collapse:collapse;margin-top:20px}th,td{border:1px solid #333;padding:9px;text-align:left}th{background:#e5e7eb}@media print{.actions{display:none}body{padding:0}}'; doc.head.appendChild(style);
  const heading = doc.createElement('h1'); heading.textContent = 'DANH SÁCH XẾP PHÒNG THI'; doc.body.appendChild(heading); const note = doc.createElement('p'); note.textContent = selectedPlanName+' — Lớp: '+selectedPlanClass; doc.body.appendChild(note);
  const actions = doc.createElement('div'); actions.className = 'actions'; const printButton = doc.createElement('button'); printButton.textContent = 'In bảng này'; printButton.addEventListener('click', () => printWindow.print()); actions.appendChild(printButton); doc.body.appendChild(actions);
  const table = doc.createElement('table'); const thead = doc.createElement('thead'); const hr = doc.createElement('tr'); ['STT','SBD','Họ và tên','Lớp','Phòng thi','Số ghế'].forEach(text => { const th = doc.createElement('th'); th.textContent = text; hr.appendChild(th); }); thead.appendChild(hr); table.appendChild(thead); const tbody = doc.createElement('tbody'); roomPrintCandidates.forEach((candidate,index) => { const row=doc.createElement('tr'); [index+1,candidate.candidate_number||'',candidate.student_name||'',candidate.class_name||selectedPlanClass,candidate.room_name||'',candidate.seat_number||''].forEach(value=>{const td=doc.createElement('td');td.textContent=value;row.appendChild(td);});tbody.appendChild(row);}); table.appendChild(tbody); doc.body.appendChild(table); printWindow.focus();
};
const showProcessLog = (processType, anchorForm) => {
  processLogCard?.remove(); processLogCard = null;
  if (!processType || !anchorForm) return;
  const logs = selectedProcessLogs.filter(log => log.process_type === processType);
  processLogCard = document.createElement('div'); processLogCard.className = 'exam-card mt-4';
  const title = logLabels[processType] || processType;
  const rows = logs.length ? logs.map(log => '<tr class="border-t"><td class="p-3">'+(logLabels[log.process_type] || log.process_type)+'</td><td class="p-3">'+selectedPlanClass+'</td><td class="p-3">'+(log.process_type === 'ROOM_REGISTER' ? (log.method || '—') : (log.method || log.file_name || '—'))+'</td><td class="p-3">'+(log.created_at ? new Date(log.created_at).toLocaleString('vi-VN') : '—')+'</td><td class="p-3">'+(log.process_type === 'CANDIDATE_NUMBER' ? '<button type="button" data-print-candidate title="Mở tab mới chứa danh sách học viên và SBD để xem rồi in" class="px-3 py-1 rounded-lg bg-blue-600 text-white font-bold">In danh sách</button>' : '—')+'</td></tr>').join('') : '<tr><td colspan="5" class="p-4 text-center text-slate-500">Chưa có nhật ký cho chức năng này.</td></tr>';
  const description = processType === 'CANDIDATE_NUMBER' ? '<p class="exam-help mb-3">Lớp: <b>'+selectedPlanClass+'</b> — bấm <b>In danh sách</b> để mở tab mới xem bảng học viên đã được thêm SBD.</p>' : '';
  processLogCard.innerHTML = '<h2 class="font-bold text-lg mb-1">Nhật ký: '+title+'</h2>'+description+'<div class="overflow-auto"><table class="w-full text-sm"><thead><tr><th class="text-left p-3">Chức năng</th><th class="text-left p-3">Lớp</th><th class="text-left p-3">Phòng / Chi tiết</th><th class="text-left p-3">Thời gian</th><th class="text-left p-3">Thao tác</th></tr></thead><tbody>'+rows+'</tbody></table></div>';
  processLogCard.querySelectorAll('[data-print-candidate]').forEach(button => button.addEventListener('click', window.printCandidateList));
  anchorForm.parentElement.insertAdjacentElement('afterend', processLogCard);
};
 const assignForm = document.querySelector('form input[name="process_type"][value="ROOM_ASSIGN"]')?.closest('form');
 if (candidateForm && assignForm) {
    const assignTitle = assignForm.querySelector('h2'); if (assignTitle) assignTitle.textContent = 'Đăng ký và xếp phòng thi';
    document.querySelectorAll('form input[name="process_type"][value="ROOM_REGISTER"]').forEach(input => input.closest('form')?.remove());
   const assignRoomLabel = assignForm.querySelector('label.exam-label'); if (assignRoomLabel) assignRoomLabel.firstChild.textContent = 'Chọn phòng học để đăng ký và xếp';
   const assignProcessType = assignForm.querySelector('input[name="process_type"]'); if (assignProcessType) assignProcessType.value = 'ROOM_REGISTER_ASSIGN';
   const grid = candidateForm.parentElement; grid.classList.add('pre-exam-grid');
  const classSelect = document.createElement('select');
  classSelect.name = 'class_id'; classSelect.className = 'exam-input';
  classSelect.innerHTML = '<option value="">Chọn lớp có sẵn danh sách học viên</option>' + @json($classes->map(fn($class) => ['id'=>$class->id,'label'=>$class->code.' — '.$class->name])->values()).map(item => '<option value="'+item.id+'">'+item.label+'</option>').join('');
  const classLabel = document.createElement('label'); classLabel.className = 'exam-label'; classLabel.textContent = 'Chọn lớp để lấy danh sách học viên';
  const classHelp = document.createElement('p'); classHelp.className = 'exam-help'; classHelp.textContent = 'Hệ thống sẽ lấy toàn bộ học viên hiện có trong lớp để đánh số báo danh.';
  const fileInput = candidateForm.querySelector('input[type="file"]');
  const fileLabel = Array.from(candidateForm.querySelectorAll('label')).find(label => label.textContent.includes('Import'));
  if (fileLabel) { fileLabel.before(classHelp); fileLabel.before(classSelect); fileLabel.before(classLabel); }
  fileInput?.remove(); fileLabel?.remove();
  classLabel.insertAdjacentElement('afterend', classSelect); classSelect.insertAdjacentElement('afterend', classHelp);
  const submitButton = candidateForm.querySelector('button[type="submit"], button:not([type])');
  const syncCandidateSource = () => {
    const useClass = classSelect.value !== '';
    if (submitButton) { submitButton.disabled = !useClass; submitButton.textContent = useClass ? 'Lấy danh sách lớp và tạo SBD' : 'Hãy chọn lớp trước'; }
  };
  classSelect.addEventListener('change', syncCandidateSource); syncCandidateSource();
  const rooms = @json($registeredRooms ?? []);
  const registerForm = document.createElement('form');
  registerForm.method = 'POST'; registerForm.action = @json(route('exam-organization.process')); registerForm.className = 'exam-card space-y-3';
  registerForm.innerHTML = '<input type="hidden" name="_token" value="'+@json(csrf_token())+'"><input type="hidden" name="plan_id" value="'+@json($selectedPlanId)+'"><input type="hidden" name="process_type" value="ROOM_REGISTER"><h2 class="font-bold text-lg">Đăng ký phòng thi</h2><p class="text-sm text-slate-500">Kế hoạch, môn và lớp được lấy từ mục 3.1.</p><label class="exam-label">Tên phòng thi<input name="room_name" required class="exam-input mt-1" placeholder="Ví dụ: Phòng 01"></label><button class="px-4 py-2 rounded-lg bg-blue-600 text-white font-bold">Lưu phòng đăng ký</button>';
  const classroomOptions = @json($classrooms->map(fn($room) => ['id'=>$room->id,'name'=>$room->name])->values());
  const registerRoomInput = registerForm.querySelector('input[name="room_name"]');
  if (registerRoomInput) { const roomSelect = document.createElement('select'); roomSelect.name = 'room_name'; roomSelect.required = true; roomSelect.className = 'exam-input mt-1'; roomSelect.innerHTML = '<option value="">Chọn phòng học</option>' + classroomOptions.map(room => '<option value="'+room.name+'">'+room.name+'</option>').join(''); registerRoomInput.replaceWith(roomSelect); }
   registerForm.remove();
  const roomInput = assignForm.querySelector('input[name="room_name"]');
   if (roomInput) { const select = document.createElement('select'); select.name='room_name'; select.required=true; select.className='exam-input'; select.innerHTML='<option value="">Chọn phòng học</option>'+classroomOptions.map(room => '<option value="'+room.name+'">'+room.name+'</option>').join(''); roomInput.replaceWith(select); }
   const methodSelect = assignForm.querySelector('select[name="method"]');
   if (methodSelect) { const deskBox = document.createElement('div'); deskBox.className = 'grid grid-cols-2 gap-3'; deskBox.innerHTML = '<label class="exam-label">Số bàn ngang<input type="number" name="desks_horizontal" min="1" required class="exam-input mt-1" placeholder="Ví dụ: 3"></label><label class="exam-label">Số bàn dọc<input type="number" name="desks_vertical" min="1" required class="exam-input mt-1" placeholder="Ví dụ: 7"></label>'; methodSelect.insertAdjacentElement('afterend', deskBox); }
   const forms = { CANDIDATE_NUMBER:candidateForm, ROOM_REGISTER_ASSIGN:assignForm };
   Object.values(forms).forEach(form => { form.hidden = false; });
   processLogCard?.remove();
   const latestLog = type => selectedProcessLogs.find(log => log.process_type === type);
   const preExamLog = document.createElement('div'); preExamLog.className = 'exam-card mt-4';
   const sbdLog = latestLog('CANDIDATE_NUMBER'); const registerLog = latestLog('ROOM_REGISTER'); const assignLog = latestLog('ROOM_ASSIGN');
   preExamLog.innerHTML = '<h2 class="font-bold text-lg mb-1">Nhật ký xử lý trước khi thi</h2><p class="exam-help mb-3">Theo dõi kế hoạch, lớp, phòng và các danh sách đã tạo.</p><div class="overflow-auto"><table class="w-full text-sm"><thead><tr><th class="text-left p-3">Tên kỳ thi</th><th class="text-left p-3">Lớp</th><th class="text-left p-3">Phòng thi</th><th class="text-left p-3">Dạng đánh SBD</th><th class="text-left p-3">Dạng xếp phòng thi</th><th class="text-left p-3">In danh sách SBD</th><th class="text-left p-3">In danh sách xếp phòng thi</th></tr></thead><tbody><tr class="border-t"><td class="p-3">'+selectedPlanName+'</td><td class="p-3">'+selectedPlanClass+'</td><td class="p-3">'+(registerLog?.method || 'Chưa đăng ký phòng')+'</td><td class="p-3">'+(sbdLog?.method || 'Chưa đánh SBD')+'</td><td class="p-3">'+(assignLog?.method || 'Chưa xếp phòng')+'</td><td class="p-3">'+(sbdLog ? '<button type="button" data-print-candidate class="px-3 py-1 rounded-lg bg-blue-600 text-white font-bold">In danh sách SBD</button>' : '—')+'</td><td class="p-3">'+(assignLog ? '<button type="button" data-print-room class="px-3 py-1 rounded-lg bg-blue-600 text-white font-bold">In danh sách phòng</button>' : '—')+'</td></tr></tbody></table></div>';
   preExamLog.querySelector('[data-print-candidate]')?.addEventListener('click', window.printCandidateList); preExamLog.querySelector('[data-print-room]')?.addEventListener('click', window.printRoomList); grid.parentElement.insertAdjacentElement('afterend', preExamLog);
}
 if (new URLSearchParams(window.location.search).get('section') === 'pre_exam') {
   document.querySelectorAll('.exam-card').forEach(card => { if (card.querySelector('h2')?.textContent.includes('Nhật ký xử lý trước khi thi')) card.remove(); });
   const sharedLogs = @json($preExamLogs ?? []);
   const grouped = {};
   sharedLogs.forEach(log => { const key = log.plan_id; grouped[key] ??= { plan: log.plan || {}, logs: [] }; grouped[key].logs.push(log); });
   const rows = Object.values(grouped).map(item => {
     const sbd = item.logs.find(log => log.process_type === 'CANDIDATE_NUMBER'); const register = item.logs.find(log => log.process_type === 'ROOM_REGISTER'); const assign = item.logs.find(log => log.process_type === 'ROOM_ASSIGN'); const plan = item.plan || {}; const planClass = plan.class?.name || '—'; const planName = plan.name || '—';
     return '<tr class="border-t"><td class="p-3">'+planName+'</td><td class="p-3">'+planClass+'</td><td class="p-3">'+(register?.method || 'Chưa đăng ký phòng')+'</td><td class="p-3">'+(sbd?.method || 'Chưa đánh SBD')+'</td><td class="p-3">'+(assign?.method || 'Chưa xếp phòng')+'</td><td class="p-3">'+(sbd ? '<button type="button" data-print-candidate data-plan-id="'+plan.id+'" class="px-3 py-1 rounded-lg bg-blue-600 text-white font-bold">In danh sách SBD</button>' : '—')+'</td><td class="p-3">'+(assign ? '<button type="button" data-print-room data-plan-id="'+plan.id+'" class="px-3 py-1 rounded-lg bg-blue-600 text-white font-bold">In danh sách phòng</button>' : '—')+'</td></tr>';
   }).join('');
   const sharedCard = document.createElement('div'); sharedCard.className = 'exam-card mt-4 overflow-auto'; sharedCard.innerHTML = '<h2 class="font-bold text-lg mb-1">Nhật ký xử lý trước khi thi</h2><p class="exam-help mb-3">Một bảng dùng chung cho tất cả kế hoạch thi.</p><table class="w-full text-sm"><thead><tr><th class="text-left p-3">Tên kỳ thi</th><th class="text-left p-3">Lớp</th><th class="text-left p-3">Phòng thi</th><th class="text-left p-3">Dạng đánh SBD</th><th class="text-left p-3">Dạng xếp phòng thi</th><th class="text-left p-3">In danh sách SBD</th><th class="text-left p-3">In danh sách xếp phòng thi</th></tr></thead><tbody>'+ (rows || '<tr><td colspan="7" class="p-4 text-center text-slate-500">Chưa có dữ liệu xử lý trước thi.</td></tr>') +'</tbody></table>';
   candidateForm?.parentElement.parentElement.insertAdjacentElement('afterend', sharedCard);
   const allPreExamCandidates = @json($preExamCandidates ?? []);
   const printSharedList = (planId, roomMode) => { const list = allPreExamCandidates[planId] || []; const win = window.open('', '_blank'); if (!win) return; const doc = win.document; const title = roomMode ? 'DANH SÁCH XẾP PHÒNG THI' : 'DANH SÁCH HỌC VIÊN VÀ SỐ BÁO DANH'; const h = doc.createElement('h1'); h.textContent = title; doc.body.appendChild(h); const style = doc.createElement('style'); style.textContent = 'body{font-family:Arial;padding:24px}h1{text-align:center}table{width:100%;border-collapse:collapse;margin-top:20px}th,td{border:1px solid #333;padding:8px}th{background:#eee}'; doc.head.appendChild(style); const table = doc.createElement('table'); const head = doc.createElement('tr'); (roomMode ? ['STT','SBD','Họ và tên','Lớp','Phòng thi','Số ghế'] : ['STT','SBD','Họ và tên','Lớp']).forEach(text => { const th = doc.createElement('th'); th.textContent = text; head.appendChild(th); }); table.appendChild(head); list.forEach((candidate,index) => { const row = doc.createElement('tr'); const values = roomMode ? [index+1,candidate.candidate_number||'',candidate.student_name||'',candidate.class_name||'',candidate.room_name||'',candidate.seat_number||''] : [index+1,candidate.candidate_number||'',candidate.student_name||'',candidate.class_name||'']; values.forEach(value => { const td = doc.createElement('td'); td.textContent = value; row.appendChild(td); }); table.appendChild(row); }); doc.body.appendChild(table); const print = doc.createElement('button'); print.textContent = 'In bảng này'; print.onclick = () => { print.remove(); win.print(); }; doc.body.appendChild(print); };
   sharedCard.querySelectorAll('[data-print-candidate]').forEach(button => button.addEventListener('click', () => printSharedList(button.dataset.planId, false))); sharedCard.querySelectorAll('[data-print-room]').forEach(button => button.addEventListener('click', () => printSharedList(button.dataset.planId, true)));
 }
 const gradingSelect = document.querySelector('select[name="grading_mode"]');
const roomSelect = document.querySelector('select[name="room_name"]');
const packetSelect = document.querySelector('select[name="packet_number"]');
if (gradingSelect) {
  gradingSelect.value = @json($gradingMode);
  const placeholder = document.createElement('option'); placeholder.value=''; placeholder.textContent='Chọn chức năng nhập điểm...'; gradingSelect.insertBefore(placeholder, gradingSelect.firstChild); gradingSelect.value='';
  gradingSelect.value = @json($gradingMode);
  const gradingForm = gradingSelect.closest('form');
  const classField = document.createElement('div');
  classField.innerHTML = '<label class="exam-label">Lớp nhập điểm</label><select name="grading_class_id" class="exam-input"></select>';
  const classSelect = classField.querySelector('select');
  classSelect.innerHTML = '<option value="">Chọn lớp</option>' + @json($classes->map(fn($class) => ['id' => $class->id, 'label' => ($class->code.' — '.$class->name)])->values()).map(item => '<option value="'+item.id+'">'+item.label+'</option>').join('');
  classSelect.value = @json((string) $gradingClassId);
  classSelect.disabled = gradingSelect.value !== 'direct';
  classSelect.addEventListener('change', () => gradingForm.submit());
  gradingForm.insertBefore(classField, gradingSelect.closest('div').nextElementSibling);
  const roomField = roomSelect?.closest('div');
  const packetField = packetSelect?.closest('div');
  const syncGradingFields = () => {
    const mode = gradingSelect.value;
    if (roomField) roomField.style.display = mode === 'room' ? '' : 'none';
    if (packetField) packetField.style.display = mode === 'packet' ? '' : 'none';
    classSelect.disabled = mode !== 'direct';
    const processType = mode === 'direct' ? 'GRADING_DIRECT' : (mode === 'room' ? 'GRADING_ROOM' : (mode === 'packet' ? 'GRADING_PACKET' : ''));
    // Mục 3.4 chỉ hiển thị bảng nhập điểm, không chèn bảng thông báo nhật ký phụ.
  };
  gradingSelect.addEventListener('change', syncGradingFields);
  syncGradingFields();
}
const currentSection = new URLSearchParams(window.location.search).get('section');
const allPacketCandidates = @json($allPacketCandidates ?? []);
document.querySelectorAll('[data-print-packet]').forEach(button => button.addEventListener('click', () => { const list = allPacketCandidates[button.dataset.packetKey] || []; const win = window.open('', '_blank'); if (!win) return; const doc = win.document; const style = doc.createElement('style'); style.textContent = 'body{font-family:Arial;padding:24px}h1{text-align:center}table{width:100%;border-collapse:collapse;margin-top:18px}th,td{border:1px solid #333;padding:8px}th{background:#eee}'; doc.head.appendChild(style); const heading = doc.createElement('h1'); heading.textContent = 'DANH SÁCH TRONG TÚI'; doc.body.appendChild(heading); const table = doc.createElement('table'); const header = doc.createElement('tr'); ['STT','SBD','Họ và tên','Lớp','Số phách'].forEach(text => { const th = doc.createElement('th'); th.textContent = text; header.appendChild(th); }); table.appendChild(header); list.forEach((candidate,index) => { const row = doc.createElement('tr'); [index+1,candidate.candidate_number||'',candidate.student_name||'',candidate.class_name||'',candidate.cipher_number||''].forEach(value => { const td = doc.createElement('td'); td.textContent = value; row.appendChild(td); }); table.appendChild(row); }); doc.body.appendChild(table); const print = doc.createElement('button'); print.textContent = 'In bảng này'; print.onclick = () => { print.remove(); win.print(); }; doc.body.appendChild(print); }));
if (currentSection === 'post_exam') {
  const packetForm = document.querySelector('form input[name="process_type"][value="PACKET"]')?.closest('form');
  if (packetForm) {
    const packetTitle = packetForm.querySelector('h2'); if (packetTitle) packetTitle.textContent = 'Dồn túi / Đánh phách';
    const packetButton = packetForm.querySelector('button'); if (packetButton) packetButton.textContent = 'Lưu dồn túi / đánh phách';
    const packetConfig = document.createElement('div'); packetConfig.className = 'space-y-4'; packetConfig.innerHTML = '<div><label class="exam-label">Loại đề dồn túi</label><select name="packet_mode" id="packet-mode" class="exam-input" required><option value="">Chọn loại đề</option><option value="SINGLE">1 đề (chẵn hoặc lẻ)</option><option value="DOUBLE">2 đề (chẵn và lẻ)</option></select></div><div id="single-packet-config" class="hidden grid md:grid-cols-2 gap-3"><label class="exam-label">Đề sử dụng<select name="single_variant" class="exam-input mt-1"><option value="EVEN">Đề chẵn</option><option value="ODD">Đề lẻ</option></select></label><label class="exam-label">Số bài thi của nhóm<input name="single_total" type="number" min="1" class="exam-input mt-1"></label><label class="exam-label">Số túi bài thi<input name="single_packet_count" type="number" min="1" class="exam-input mt-1"></label><label class="exam-label">Số bài thi trong mỗi túi<input name="single_per_packet" type="number" min="1" class="exam-input mt-1"></label><label class="exam-label">Số bài thi trong túi cuối cùng<input name="single_last_packet" type="number" min="1" class="exam-input mt-1"></label></div><div id="double-packet-config" class="hidden grid md:grid-cols-2 gap-3"><h3 class="md:col-span-2 font-bold text-slate-800">Đề chẵn</h3><label class="exam-label">Số bài thi chẵn<input name="even_total" type="number" min="1" class="exam-input mt-1"></label><label class="exam-label">Số túi chẵn<input name="even_packet_count" type="number" min="1" class="exam-input mt-1"></label><label class="exam-label">Số bài mỗi túi chẵn<input name="even_per_packet" type="number" min="1" class="exam-input mt-1"></label><label class="exam-label">Số bài túi chẵn cuối<input name="even_last_packet" type="number" min="1" class="exam-input mt-1"></label><h3 class="md:col-span-2 font-bold text-slate-800">Đề lẻ</h3><label class="exam-label">Số bài thi lẻ<input name="odd_total" type="number" min="1" class="exam-input mt-1"></label><label class="exam-label">Số túi lẻ<input name="odd_packet_count" type="number" min="1" class="exam-input mt-1"></label><label class="exam-label">Số bài mỗi túi lẻ<input name="odd_per_packet" type="number" min="1" class="exam-input mt-1"></label><label class="exam-label">Số bài túi lẻ cuối<input name="odd_last_packet" type="number" min="1" class="exam-input mt-1"></label></div>';
    packetForm.querySelector('h2')?.insertAdjacentElement('afterend', packetConfig); const mode = packetConfig.querySelector('#packet-mode'); const single = packetConfig.querySelector('#single-packet-config'); const double = packetConfig.querySelector('#double-packet-config'); mode.addEventListener('change', () => { single.classList.toggle('hidden', mode.value !== 'SINGLE'); double.classList.toggle('hidden', mode.value !== 'DOUBLE'); single.querySelectorAll('input,select').forEach(input => input.disabled = mode.value !== 'SINGLE'); double.querySelectorAll('input,select').forEach(input => input.disabled = mode.value !== 'DOUBLE'); });
  }
}
if (currentSection === 'post_exam') {
  const simplePacketForm = document.querySelector('form input[name="process_type"][value="PACKET"]')?.closest('form'); const simplePacketConfig = simplePacketForm?.querySelector('.space-y-4');
  if (simplePacketForm && simplePacketConfig) {
    simplePacketConfig.innerHTML = '<div><label class="exam-label">Cách dồn túi</label><select name="packet_mode" id="simple-packet-mode" class="exam-input" required><option value="">Chọn cách dồn túi</option><option value="SINGLE">1 đề</option><option value="DOUBLE">2 đề</option></select></div><div id="simple-single" class="hidden grid md:grid-cols-2 gap-3"><label class="exam-label">Sử dụng đề<select name="single_variant" class="exam-input mt-1"><option value="EVEN">Đề chẵn</option><option value="ODD">Đề lẻ</option></select></label></div><div id="simple-double" class="hidden"><label class="exam-label">Túi đề chẵn chọn SBD</label><select name="double_even_source" class="exam-input"><option value="EVEN">SBD chẵn</option><option value="ODD">SBD lẻ</option></select><p class="exam-help mt-2">Túi đề lẻ sẽ tự động nhận nhóm SBD còn lại.</p></div><div class="grid md:grid-cols-2 gap-3"><label class="exam-label">Từ số phách<input name="cipher_from" type="number" min="1" class="exam-input mt-1" placeholder="Ví dụ: 1"></label><label class="exam-label">Đến số phách<input name="cipher_to" type="number" min="1" class="exam-input mt-1" placeholder="Ví dụ: 30"></label></div>';
    const simpleMode = simplePacketConfig.querySelector('#simple-packet-mode'); const singleBox = simplePacketConfig.querySelector('#simple-single'); const doubleBox = simplePacketConfig.querySelector('#simple-double'); simpleMode.addEventListener('change', () => { singleBox.classList.toggle('hidden', simpleMode.value !== 'SINGLE'); doubleBox.classList.toggle('hidden', simpleMode.value !== 'DOUBLE'); });
    simplePacketForm.addEventListener('submit', () => { ['cipher_from', 'cipher_to'].forEach((name) => { const input = simplePacketForm.querySelector(`[name="${name}"]`); if (input && input.value !== '') input.value = String(Math.trunc(Number(input.value))); }); });
  }
}
if (currentSection === 'marking_docs') {
  const minuteForm = document.querySelector('form input[name="process_type"][value="DOC_MINUTES"]')?.closest('form');
  if (minuteForm) {
    const trigger = minuteForm.querySelector('button'); trigger.type = 'button'; trigger.textContent = 'Biên bản chấm thi';
    const panel = document.createElement('div'); panel.className = 'exam-card mt-4'; panel.hidden = true;
    panel.innerHTML = '<h2 class="font-bold text-lg mb-3">Thông tin biên bản chấm thi</h2><div><label class="exam-label">Túi số</label><select id="minutes-packet" class="exam-input"><option value="">Chọn túi số</option></select></div><p class="exam-help mt-3">Tổng số bài thi: <b>'+@json($candidates->count())+'</b> (theo tổng số học viên của lớp).</p><button type="button" id="print-minutes" class="mt-4 px-5 py-2 rounded-lg bg-blue-600 text-white font-bold" disabled>In biên bản chấm thi</button>';
    minuteForm.parentElement.insertAdjacentElement('afterend', panel);
     const packetSelect = panel.querySelector('#minutes-packet'); const printMinutes = panel.querySelector('#print-minutes'); const sbdSelect = { innerHTML:'', disabled:true, value:'', appendChild(){}, addEventListener(){} }; const candidateValues = [];
     const packetValues = @json($packets ?? []);
     const originalPacketAddEventListener = packetSelect.addEventListener.bind(packetSelect); packetSelect.addEventListener = (eventName, handler) => { originalPacketAddEventListener(eventName, (...args) => { handler(...args); if (eventName === 'change') printMinutes.disabled = !packetSelect.value; }); };
    packetValues.forEach(packet => { const option = document.createElement('option'); option.value = packet; option.textContent = packet; packetSelect.appendChild(option); });
    trigger.addEventListener('click', () => { panel.hidden = false; panel.scrollIntoView({ behavior:'smooth', block:'start' }); });
    packetSelect.addEventListener('change', () => { sbdSelect.innerHTML = '<option value="">Chọn túi SBD</option>'; const values = candidateValues.filter(item => !packetSelect.value || item.packet === packetSelect.value).map(item => item.number).filter(Boolean); values.forEach(number => { const option = document.createElement('option'); option.value = number; option.textContent = number; sbdSelect.appendChild(option); }); sbdSelect.disabled = values.length === 0; printMinutes.disabled = true; });
    sbdSelect.addEventListener('change', () => { printMinutes.disabled = !(packetSelect.value && sbdSelect.value); });
    printMinutes.addEventListener('click', () => { const win = window.open('', '_blank'); if (!win) return; const doc = win.document; const style = doc.createElement('style'); style.textContent = 'body{font-family:Arial,sans-serif;padding:28px;color:#111}h1{text-align:center;font-size:20px;text-transform:uppercase}table{width:100%;border-collapse:collapse;margin-top:18px}td{border:1px solid #333;padding:10px}.label{width:35%;font-weight:bold}.sign{height:100px;vertical-align:top;text-align:center}'; doc.head.appendChild(style); const heading = doc.createElement('h1'); heading.textContent = 'BIÊN BẢN CHẤM THI'; doc.body.appendChild(heading); const table = doc.createElement('table'); [['Kỳ thi', selectedPlanName],['Môn thi', selectedPlanSubject],['Lớp', selectedPlanClass],['Túi số', packetSelect.value],['Túi SBD', sbdSelect.value],['Tổng số bài thi', @json($candidates->count())]].forEach(item => { const row = doc.createElement('tr'); const label = doc.createElement('td'); label.className='label'; label.textContent=item[0]; const value = doc.createElement('td'); value.textContent=item[1] || ''; row.append(label,value); table.appendChild(row); }); const sign = doc.createElement('tr'); sign.innerHTML = '<td class="sign">Người lập biên bản</td><td class="sign">Giám khảo</td>'; table.appendChild(sign); doc.body.appendChild(table); const button = doc.createElement('button'); button.textContent='In biên bản chấm thi'; button.style.marginTop='20px'; button.onclick=()=>{button.remove();win.print();}; doc.body.appendChild(button); });
  }
}
 if (currentSection === 'marking_docs') {
   const oldMinutesButton = document.querySelector('#print-minutes');
   const minutesPacket = document.querySelector('#minutes-packet');
   if (oldMinutesButton && minutesPacket) {
     const minutesButton = oldMinutesButton.cloneNode(true); oldMinutesButton.replaceWith(minutesButton); minutesButton.disabled = !minutesPacket.value;
     minutesPacket.addEventListener('change', () => { minutesButton.disabled = !minutesPacket.value; });
     const minuteCandidates = @json($candidates->map(fn($candidate) => ['cipher'=>$candidate->cipher_number, 'name'=>$candidate->student_name])->values());
     minutesButton.addEventListener('click', () => {
       const win = window.open('', '_blank'); if (!win) return; const doc = win.document; doc.open(); doc.close();
       const style = doc.createElement('style'); style.textContent = '@page{size:A4 landscape;margin:12mm}body{font-family:"Times New Roman",serif;color:#111;font-size:15px;margin:0}h1,h2,p{margin:0} .head{display:grid;grid-template-columns:1fr 1fr;text-align:center;line-height:1.25}.head strong{font-size:16px}.title{text-align:center;font-weight:bold;font-size:18px;margin:24px 0 12px}.meta{display:grid;grid-template-columns:1fr 1fr;text-align:center;font-weight:bold;font-size:16px;margin-bottom:10px}.meta .right{text-align:right;padding-right:20px}.meta .left{text-align:left;padding-left:20px}.exam-table{width:88%;margin:0 auto;border-collapse:collapse;table-layout:fixed}.exam-table th,.exam-table td{border:1px solid #111;text-align:center;padding:4px;height:22px}.exam-table th{font-weight:normal}.exam-table .group{height:22px}.exam-table .small{width:38px}.exam-table .cipher{width:80px}.exam-table .score{width:95px}.totals{width:88%;margin:10px auto 0;line-height:1.65}.signatures{width:92%;margin:18px auto 0;display:grid;grid-template-columns:1fr 1fr 1fr;text-align:center;font-weight:bold}.signatures div{padding-top:8px;text-decoration:underline}.print-action{display:none}'; doc.head.appendChild(style);
       const head = doc.createElement('div'); head.className='head'; head.innerHTML = '<div>TRƯỜNG CAO ĐẲNG HẬU CẦN 2<br><strong>BAN KHẢO THÍ VÀ ĐBCLGDĐT</strong></div><div><strong>MÔN HỌC: '+(selectedPlanSubject || 'TÊN HỌC PHẦN')+'</strong><br>KỲ THI '+(selectedPlanName || '')+'<br><strong>KHỐI, LỚP: '+(selectedPlanClass || '')+'</strong></div>'; doc.body.appendChild(head);
       const title = doc.createElement('div'); title.className='title'; title.textContent='BIÊN BẢN CHẤM THI'; doc.body.appendChild(title);
       const meta = doc.createElement('div'); meta.className='meta'; meta.innerHTML='<div class="left">TÚI SỐ</div><div class="right">TÚI SỐ '+(minutesPacket.value || '')+'</div>'; doc.body.appendChild(meta);
       const table = doc.createElement('table'); table.className='exam-table'; table.innerHTML='<thead><tr><th class="small" rowspan="2">Stt</th><th class="cipher" rowspan="2">Số phách</th><th colspan="2">Điểm</th><th colspan="2">Điểm sửa chữa</th><th rowspan="2">Người sửa chữa<br>ký tên, ghi họ tên</th></tr><tr><th class="score">Bằng số</th><th class="score">Bằng chữ</th><th class="score">Bằng số</th><th class="score">Bằng chữ</th></tr></thead><tbody></tbody>'; const tbody=table.querySelector('tbody'); const rows=minuteCandidates.length ? minuteCandidates : [{cipher:''}]; rows.forEach((candidate,index)=>{ const tr=doc.createElement('tr'); [String(index+1).padStart(2,'0'),candidate.cipher || '', '', '', '', '', ''].forEach(value=>{const td=doc.createElement('td');td.textContent=value;tr.appendChild(td);});tbody.appendChild(tr); }); doc.body.appendChild(table);
       const totals=doc.createElement('div'); totals.className='totals'; totals.innerHTML='<div>Tổng số bài thi theo danh sách: <strong>'+minuteCandidates.length+'</strong> bài &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Số phách từ: .......... đến: ..........</div><div>Tổng số bài thi thực có: .......... bài &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Tổng số tờ giấy thi thực có: .......... tờ</div><div>Tổng số điểm thi: .......... &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Ngày ........ tháng ........ năm '+new Date().getFullYear()+'</div>'; doc.body.appendChild(totals);
       const signatures=doc.createElement('div'); signatures.className='signatures'; signatures.innerHTML='<div>Cán bộ chấm thi thứ nhất</div><div>Cán bộ chấm thi thứ hai</div><div>Chủ nhiệm khoa</div>'; doc.body.appendChild(signatures); win.focus(); win.print();
     });
   }
 }
 if (false && currentSection === 'marking_docs') {
   const oldSbd = document.querySelector('#minutes-sbd');
   const oldPrint = document.querySelector('#print-minutes');
   const packet = document.querySelector('#minutes-packet');
   if (oldSbd) oldSbd.parentElement?.remove();
   if (oldPrint && packet) {
     const printButton = oldPrint.cloneNode(true);
     oldPrint.replaceWith(printButton);
     printButton.disabled = !packet.value;
     packet.addEventListener('change', () => { printButton.disabled = !packet.value; });
     printButton.addEventListener('click', () => {
       const win = window.open('', '_blank'); if (!win) return;
        const doc = win.document; doc.open(); doc.close();
        const style = doc.createElement('style'); style.textContent = 'body{font-family:Arial,sans-serif;padding:28px;color:#111}h1{text-align:center;font-size:20px;text-transform:uppercase}table{width:100%;border-collapse:collapse;margin-top:18px}td{border:1px solid #333;padding:10px}.label{width:35%;font-weight:bold}.sign{height:100px;vertical-align:top;text-align:center}'; doc.head.appendChild(style);
        const heading = doc.createElement('h1'); heading.textContent = 'BIÊN BẢN CHẤM THI'; doc.body.appendChild(heading);
        const table = doc.createElement('table'); [['Kỳ thi', selectedPlanName], ['Môn thi', selectedPlanSubject], ['Lớp', selectedPlanClass], ['Túi số', packet.value], ['Tổng số bài thi', @json($candidates->count())]].forEach(item => { const row = doc.createElement('tr'); const label = doc.createElement('td'); label.className = 'label'; label.textContent = item[0]; const value = doc.createElement('td'); value.textContent = item[1] || ''; row.append(label, value); table.appendChild(row); });
        const sign = doc.createElement('tr'); sign.innerHTML = '<td class="sign">Người lập biên bản</td><td class="sign">Giám khảo</td>'; table.appendChild(sign); doc.body.appendChild(table); win.focus(); win.print(); return;
       // Đã loại bỏ document.write cũ vì có thể ghi đè trang chính và làm lộ mã JavaScript.
       doc.close(); win.focus(); win.print();
     });
   }
 }
 if (false && currentSection === 'grading') {
  const scoreForm = document.querySelector('input[name^="scores["]')?.closest('form');
  if (scoreForm) {
    const actions = document.createElement('div');
    actions.className = 'mt-4 flex flex-wrap gap-3';
    actions.innerHTML = '<button type="button" class="px-5 py-2 rounded-lg border border-blue-300 text-blue-700 font-bold">Xem bảng điểm</button><button type="button" class="px-5 py-2 rounded-lg bg-blue-600 text-white font-bold">In bảng điểm</button>';
    scoreForm.appendChild(actions);
    actions.children[0].addEventListener('click', () => scoreForm.scrollIntoView({ behavior: 'smooth', block: 'start' }));
    actions.children[1].addEventListener('click', () => {
      const printWindow = window.open('', '_blank');
      if (!printWindow) return;
      const doc = printWindow.document;
      doc.title = 'Bảng điểm';
      const style = doc.createElement('style'); style.textContent = 'body{font-family:Arial,sans-serif;padding:24px;color:#111}h1{text-align:center;font-size:20px}p{text-align:center}table{width:100%;border-collapse:collapse;margin-top:18px}th,td{border:1px solid #333;padding:8px;text-align:left}th{background:#eee}'; doc.head.appendChild(style);
      const heading = doc.createElement('h1'); heading.textContent = 'BẢNG ĐIỂM'; doc.body.appendChild(heading);
      const note = doc.createElement('p'); note.textContent = selectedPlanName + ' — Lớp: ' + selectedPlanClass; doc.body.appendChild(note);
      const table = scoreForm.querySelector('table').cloneNode(true); table.querySelectorAll('input').forEach(input => { const td = input.closest('td'); if (td) td.textContent = input.value || ''; }); doc.body.appendChild(table);
      const printButton = doc.createElement('button'); printButton.textContent = 'In bảng này'; printButton.style.marginTop = '18px'; printButton.onclick = () => { printButton.remove(); printWindow.print(); }; doc.body.appendChild(printButton);
    });
  }
}
const savedScoreTable = document.querySelector('input[name^="scores["]')?.closest('form')?.querySelector('table');
const allGradingCandidates = @json($allGradingCandidates ?? []);
const scoreTableForOutput = (planId) => {
  if (String(planId) === String(@json($selectedPlanId)) && savedScoreTable) { const table = savedScoreTable.cloneNode(true); table.querySelectorAll('input').forEach(input => { const cell = input.closest('td'); if (cell) cell.textContent = input.value || ''; }); return table; }
  const table = document.createElement('table'); table.className = 'w-full text-sm'; const header = document.createElement('tr'); ['STT','SBD','Họ và tên','Lớp','Phòng / Túi','Điểm'].forEach(text => { const th = document.createElement('th'); th.textContent = text; header.appendChild(th); }); table.appendChild(header);
  (allGradingCandidates[planId] || []).forEach((candidate, index) => { const row = document.createElement('tr'); [index+1,candidate.candidate_number||'',candidate.student_name||'',candidate.class_name||'',candidate.room_name||candidate.packet_number||'—',candidate.score ?? ''].forEach(value => { const td = document.createElement('td'); td.textContent = value; row.appendChild(td); }); table.appendChild(row); }); return table;
};
document.querySelectorAll('[data-view-score]').forEach(button => button.addEventListener('click', () => {
  document.querySelector('.saved-score-preview')?.remove();
  const card = document.createElement('div'); card.className = 'exam-card saved-score-preview mt-4 overflow-auto';
  const heading = document.createElement('h2'); heading.className = 'font-bold text-lg mb-3'; heading.textContent = 'Bảng điểm đã lưu'; card.appendChild(heading);
  const table = scoreTableForOutput(button.dataset.planId); if (table && table.rows.length > 1) card.appendChild(table); else { const note = document.createElement('p'); note.textContent = 'Chưa có bảng điểm đã lưu.'; card.appendChild(note); }
  button.closest('.exam-card')?.insertAdjacentElement('afterend', card);
}));
document.querySelectorAll('[data-print-score]').forEach(button => button.addEventListener('click', () => {
  const table = scoreTableForOutput(button.dataset.planId); const printWindow = window.open('', '_blank'); if (!printWindow || !table) return;
  const doc = printWindow.document; doc.title = 'Bảng điểm'; const style = doc.createElement('style'); style.textContent = 'body{font-family:Arial,sans-serif;padding:24px;color:#111}h1{text-align:center;font-size:20px}p{text-align:center}table{width:100%;border-collapse:collapse;margin-top:18px}th,td{border:1px solid #333;padding:8px;text-align:left}th{background:#eee}'; doc.head.appendChild(style);
  const heading = doc.createElement('h1'); heading.textContent = 'BẢNG ĐIỂM'; doc.body.appendChild(heading); const note = doc.createElement('p'); note.textContent = 'Kế hoạch ID: ' + button.dataset.planId; doc.body.appendChild(note); doc.body.appendChild(table);
  const printButton = doc.createElement('button'); printButton.textContent = 'In bảng này'; printButton.style.marginTop = '18px'; printButton.onclick = () => { printButton.remove(); printWindow.print(); }; doc.body.appendChild(printButton);
}));
const sectionChoices = {};
if (sectionChoices[currentSection]) {
  const forms = sectionChoices[currentSection].map(([key]) => document.querySelector('form input[name="process_type"][value="'+key+'"]')?.closest('form')).filter(Boolean);
  if (forms.length) {
    const box = document.createElement('div'); box.className='exam-card mb-4';
    box.innerHTML='<label class="exam-label">Chọn chức năng</label><select id="section-function" class="exam-input mt-1"><option value="">Chọn chức năng...</option>'+sectionChoices[currentSection].map(([key,label])=>'<option value="'+key+'">'+label+'</option>').join('')+'</select>';
    forms[0].parentElement.parentElement.insertBefore(box, forms[0].parentElement);
    const select = box.querySelector('select');
    select.value = new URLSearchParams(window.location.search).get('post_exam_mode') || '';
    const sync = () => { forms.forEach(form => { const type=form.querySelector('input[name="process_type"]').value; form.hidden=select.value!==type; }); showProcessLog(select.value, forms.find(form => form.querySelector('input[name="process_type"]').value === select.value)); };
    select.addEventListener('change', sync); sync();
  }
}
document.querySelectorAll('.exam-card').forEach(function(card){
  const heading = card.querySelector('h2');
  if (heading && heading.textContent.includes('Nhật ký xử lý kế hoạch thi')) card.remove();
});
if (new URLSearchParams(window.location.search).get('section') !== 'planning') {
  document.querySelectorAll('.exam-card').forEach(function(card){
    const heading = card.querySelector('h2');
    if (heading && heading.textContent.includes('Danh sách kế hoạch thi')) card.remove();
  });
}
document.querySelectorAll('td').forEach(function(cell){
  const key = cell.textContent.trim();
  if (logLabels[key]) cell.textContent = logLabels[key];
});
document.querySelectorAll('form.exam-card').forEach(function(form){
  const type = form.querySelector('input[name="process_type"]')?.value;
  if (type === 'VERIFY_VIEW' || type === 'VERIFY_PRINT') form.classList.add('verify-card');
  if (type === 'GRADING_DIRECT' || type === 'GRADING_PACKET' || type === 'GRADING_ROOM') form.classList.add('score-card');
  if (type && (type.startsWith('DOC_') || type.startsWith('RESULT_'))) form.classList.add('print-action');
});
</script>
<script>
const scoreForm = false && document.querySelector('form[action*="exam-organization/process"] table');
if (scoreForm) {
  const printButton = document.createElement('button');
  printButton.type = 'button'; printButton.textContent = 'In bảng danh sách và điểm';
  printButton.className = 'ml-2 mt-4 px-5 py-2 rounded-lg border border-blue-300 text-blue-700 font-bold';
  printButton.onclick = () => window.print();
  scoreForm.closest('form').appendChild(printButton);
}
</script>
 </div>
@endsection
