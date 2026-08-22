@extends('layouts.admin')
@section('title','Soạn đề tự luận')
@section('page-title','Soạn đề tự luận')
@section('content')
<style>
    .essay-import-panel, form[enctype="multipart/form-data"] { font-size: .9rem; }
    form[enctype="multipart/form-data"] { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 14px 18px; align-items: end; margin-top: 16px; }
    form[enctype="multipart/form-data"] > label { display: flex; min-width: 0; flex-direction: column; gap: 6px; color: #1e293b; font-size: .82rem; font-weight: 800; }
    form[enctype="multipart/form-data"] > label > input, form[enctype="multipart/form-data"] > label > select { width: 100%; min-height: 42px; border: 1px solid #cbd5e1; border-radius: 10px; background: #fff; padding: 9px 11px; color: #0f172a; font-weight: 600; }
    form[enctype="multipart/form-data"] > label > input:focus, form[enctype="multipart/form-data"] > label > select:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.14); outline: 0; }
    form[enctype="multipart/form-data"] > [class~="md:col-span-2"], form[enctype="multipart/form-data"] > .md\:col-span-2 { grid-column: 1 / -1; }
    form[enctype="multipart/form-data"] > div[class*="border-2"] { margin: 0; border-radius: 14px; background: linear-gradient(110deg,#dbeafe,#eff6ff); padding: 14px 16px; }
    form[enctype="multipart/form-data"] > div[class*="border-2"] select { min-height: 44px; border: 2px solid #60a5fa; font-size: .95rem; }
    form[enctype="multipart/form-data"] > label:has(input[type="file"]) { grid-column: 1 / -1; }
    form[enctype="multipart/form-data"] input[type="file"] { border-style: dashed; background: #fff; }
    form[enctype="multipart/form-data"] button { grid-column: 1 / -1; justify-self: start; border-radius: 10px; background: linear-gradient(135deg,#2563eb,#1d4ed8); padding: 10px 18px; font-weight: 800; box-shadow: 0 7px 14px rgba(37,99,235,.2); }
    .bg-blue-50.border-blue-200.rounded-xl { border: 1px solid #bfdbfe; border-radius: 22px; background: linear-gradient(145deg,#eaf3ff 0%,#f5f9ff 55%,#ffffff 100%); box-shadow: 0 18px 42px rgba(30,64,175,.1); }
    .bg-blue-50.border-blue-200.rounded-xl > h2 { display: flex; align-items: center; gap: 10px; color: #123b87; letter-spacing: -.02em; }
    .bg-blue-50.border-blue-200.rounded-xl > h2::before { content: '✦'; display: inline-flex; width: 32px; height: 32px; align-items: center; justify-content: center; border-radius: 10px; background: #2563eb; color: #fff; box-shadow: 0 7px 14px rgba(37,99,235,.25); }
    .bg-blue-50.border-blue-200.rounded-xl > p { max-width: 820px; color: #26364d; line-height: 1.6; font-weight: 500; }
    .bg-blue-50.border-blue-200.rounded-xl > div.flex { padding: 10px 12px; border: 1px solid #d5e5fb; border-radius: 12px; background: rgba(255,255,255,.65); }
    .bg-blue-50.border-blue-200.rounded-xl > div.flex span { color: #172033; font-weight: 800; }
    .bg-blue-50.border-blue-200.rounded-xl > div.flex a { display: inline-flex; align-items: center; border-radius: 7px; padding: 5px 8px; color: #172033; font-weight: 700; text-decoration: underline; text-decoration-color: #93c5fd; text-underline-offset: 3px; }
    .bg-blue-50.border-blue-200.rounded-xl > div.flex a:hover { background: #dbeafe; }
    form[enctype="multipart/form-data"] { margin: 0; padding: 4px; }
    form[enctype="multipart/form-data"] > div[class*="border-2"] { position: relative; overflow: hidden; border-color: #60a5fa; box-shadow: 0 8px 22px rgba(37,99,235,.08); }
    form[enctype="multipart/form-data"] > div[class*="border-2"] label { color: #172033; }
    form[enctype="multipart/form-data"] > div[class*="text-blue"] { color: #26364d; font-weight: 500; }
    .exam-type-picker { display: grid; grid-template-columns: repeat(3,minmax(0,1fr)); gap: 10px; margin-top: 10px; }
    .exam-type-option { display: flex; min-height: 76px; cursor: pointer; flex-direction: column; justify-content: center; gap: 3px; border: 1px solid #cbd5e1; border-radius: 12px; background: rgba(255,255,255,.85); padding: 11px 13px; text-align: left; color: #172033; transition: .18s ease; }
    .exam-type-option:hover { border-color: #60a5fa; background: #fff; transform: translateY(-1px); box-shadow: 0 7px 14px rgba(37,99,235,.08); }
    .exam-type-option.is-active { border-color: #2563eb; background: linear-gradient(135deg,#eff6ff,#fff); box-shadow: 0 0 0 3px rgba(37,99,235,.12),0 8px 16px rgba(37,99,235,.1); }
    .exam-type-option .type-icon { color: #2563eb; font-size: 18px; line-height: 1; }
    .exam-type-option .type-title { font-size: .86rem; font-weight: 900; }
    .exam-type-option .type-help { color: #64748b; font-size: .7rem; font-weight: 600; }
    .exam-type-option:first-child .type-title { font-size: .86rem !important; }
    .exam-type-hidden { position: absolute !important; width: 1px !important; height: 1px !important; opacity: 0 !important; pointer-events: none !important; }
    @media (max-width: 700px) { .exam-type-picker { grid-template-columns: 1fr; } .exam-type-option { min-height: 58px; } }
    .bg-blue-50.border-blue-200.rounded-xl .exam-type-picker { display: flex !important; flex-direction: row !important; width: 100% !important; gap: 12px !important; margin-top: 10px !important; }
    .bg-blue-50.border-blue-200.rounded-xl .exam-type-picker .exam-type-option { flex: 1 1 0 !important; width: 0 !important; min-width: 0 !important; border: 1px solid #cbd5e1 !important; background: #ffffff !important; color: #172033 !important; box-shadow: 0 3px 8px rgba(15,23,42,.04) !important; transform: none !important; }
    .bg-blue-50.border-blue-200.rounded-xl .exam-type-picker .exam-type-option:hover { border-color: #60a5fa !important; background: #f8fbff !important; }
    .bg-blue-50.border-blue-200.rounded-xl .exam-type-picker .exam-type-option.is-active { border: 2px solid #2563eb !important; background: #eff6ff !important; color: #172033 !important; box-shadow: 0 5px 12px rgba(37,99,235,.1) !important; }
    .bg-blue-50.border-blue-200.rounded-xl .exam-type-picker .type-icon { color: #2563eb !important; }
    .bg-blue-50.border-blue-200.rounded-xl .exam-type-picker .type-help { color: #64748b !important; }
    @media (max-width: 700px) { .bg-blue-50.border-blue-200.rounded-xl .exam-type-picker { flex-direction: column !important; } .bg-blue-50.border-blue-200.rounded-xl .exam-type-picker .exam-type-option { width: 100% !important; } }
    /* Bộ chọn dạng đề trong phần soạn đề dùng cùng giao diện với phần import. */
    .bg-blue-50.rounded-xl .exam-type-picker { display: flex !important; flex-direction: row !important; width: 100% !important; gap: 12px !important; margin-top: 10px !important; }
    .bg-blue-50.rounded-xl .exam-type-picker .exam-type-option { flex: 1 1 0 !important; width: 0 !important; min-width: 0 !important; min-height: 76px; border: 1px solid #cbd5e1 !important; border-radius: 12px; background: #fff !important; color: #172033 !important; box-shadow: 0 3px 8px rgba(15,23,42,.04) !important; transform: none !important; }
    .bg-blue-50.rounded-xl .exam-type-picker .exam-type-option:hover { border-color: #60a5fa !important; background: #f8fbff !important; }
    .bg-blue-50.rounded-xl .exam-type-picker .exam-type-option.is-active { border: 2px solid #2563eb !important; background: #eff6ff !important; color: #172033 !important; box-shadow: 0 5px 12px rgba(37,99,235,.1) !important; }
    .bg-blue-50.rounded-xl .exam-type-picker .type-icon { color: #2563eb !important; }
    .bg-blue-50.rounded-xl .exam-type-picker .type-help { color: #64748b !important; }
    @media (max-width: 700px) { .bg-blue-50.rounded-xl .exam-type-picker { flex-direction: column !important; } .bg-blue-50.rounded-xl .exam-type-picker .exam-type-option { width: 100% !important; } }
    #exam-form { display: none !important; }
    #exam-form .exam-type-panel { border-color: #93c5fd !important; background: linear-gradient(135deg,#eff6ff,#f8fbff) !important; box-shadow: 0 8px 22px rgba(37,99,235,.08); }
    #exam-form .exam-info-panel { border-color: #d7e3f3 !important; background: #fff !important; box-shadow: 0 8px 22px rgba(15,23,42,.05); }
    #exam-form .exam-info-panel > h2, #exam-form .exam-metadata-panel > h2 { color: #123b70; letter-spacing: -.01em; }
    #exam-form .exam-info-panel > .grid { grid-template-columns: repeat(2,minmax(0,1fr)); gap: 18px 16px; }
    #exam-form .exam-info-panel label, #exam-form .exam-metadata-panel label { color: #243b5a; font-weight: 800; }
    #exam-form .exam-info-panel input, #exam-form .exam-info-panel select, #exam-form .exam-info-panel textarea,
    #exam-form .exam-metadata-panel input, #exam-form .exam-metadata-panel select { border-color: #cbd9ea; background: #fbfdff; color: #172033; font-weight: 600; transition: border-color .18s ease, box-shadow .18s ease, background .18s ease; }
    #exam-form .exam-info-panel input:focus, #exam-form .exam-info-panel select:focus, #exam-form .exam-info-panel textarea:focus,
    #exam-form .exam-metadata-panel input:focus, #exam-form .exam-metadata-panel select:focus { border-color: #2563eb; background: #fff; box-shadow: 0 0 0 3px rgba(37,99,235,.12); outline: none; }
    #exam-form .exam-metadata-panel { border-color: #d7e3f3 !important; background: linear-gradient(180deg,#f8fbff,#fff) !important; box-shadow: 0 8px 22px rgba(15,23,42,.05); }
    #exam-form .exam-metadata-panel { grid-template-columns: repeat(3,minmax(0,1fr)); gap: 16px; }
    #exam-form .exam-metadata-panel > label { min-width: 0; }
    @media (max-width: 700px) { #exam-form .exam-info-panel > .grid, #exam-form .exam-metadata-panel { grid-template-columns: 1fr; } }
    form[enctype="multipart/form-data"] > label:has(input[name="import_file"]), form[enctype="multipart/form-data"] > label:has(input[name="answer_file"]) { grid-column: auto; min-height: 112px; justify-content: space-between; }
    form[enctype="multipart/form-data"] > label:has(input[name="import_file"]) input[type="file"], form[enctype="multipart/form-data"] > label:has(input[name="answer_file"]) input[type="file"] { min-height: 48px; padding: 10px; }
    form[enctype="multipart/form-data"] > label:has(input[name="import_file"]), form[enctype="multipart/form-data"] > label:has(input[name="answer_file"]) { border-color: #d6e2f0; background: linear-gradient(145deg,#ffffff,#f8fbff); }
    form[enctype="multipart/form-data"] > label:has(input[name="import_file"]):hover, form[enctype="multipart/form-data"] > label:has(input[name="answer_file"]):hover { border-color: #60a5fa; background: #f5f9ff; }
    form[enctype="multipart/form-data"] > div[class*="grid"]:not([class*="border-2"]) { grid-template-columns: repeat(3,minmax(0,1fr)); gap: 12px; }
    @media (max-width: 700px) { form[enctype="multipart/form-data"] > label:has(input[name="import_file"]), form[enctype="multipart/form-data"] > label:has(input[name="answer_file"]) { grid-column: auto; } form[enctype="multipart/form-data"] > div[class*="grid"]:not([class*="border-2"]) { grid-template-columns: 1fr; } }
    form[enctype="multipart/form-data"] > div[class*="border-2"]::after { content: 'Bước 1'; position: absolute; top: 12px; right: 14px; color: #2563eb; font-size: .68rem; font-weight: 900; letter-spacing: .08em; text-transform: uppercase; }
    form[enctype="multipart/form-data"] > div[class*="grid"]:not([class*="border-2"]) { padding: 14px; border: 1px solid #e2e8f0; border-radius: 14px; background: rgba(255,255,255,.72); }
    form[enctype="multipart/form-data"] > label:has(input[type="file"]) { padding: 14px; border: 1px dashed #93c5fd; border-radius: 14px; background: #f8fbff; }
    @media (max-width: 700px) { form[enctype="multipart/form-data"] { grid-template-columns: 1fr; } form[enctype="multipart/form-data"] > [class~="md:col-span-2"], form[enctype="multipart/form-data"] > .md\:col-span-2, form[enctype="multipart/form-data"] > label:has(input[type="file"]) { grid-column: auto; } }
    form[enctype="multipart/form-data"] > label[data-import-field="duration_minutes"] { grid-column: 1 / -1; max-width: 50%; }
    form[enctype="multipart/form-data"] > label[data-import-field="import_file"], form[enctype="multipart/form-data"] > label[data-import-field="answer_file"] { grid-column: span 1; }
    form[enctype="multipart/form-data"] > button { grid-column: 1 / -1; }
    @media (max-width: 700px) { form[enctype="multipart/form-data"] > label[data-import-field="duration_minutes"], form[enctype="multipart/form-data"] > label[data-import-field="import_file"], form[enctype="multipart/form-data"] > label[data-import-field="answer_file"] { grid-column: auto; max-width: none; } }
</style>
@include('partials.module-menu', ['module' => 'exam'])
@if($errors->any())<div class="mb-4 rounded-lg bg-red-50 border border-red-300 text-red-800 px-4 py-3"><b>Không thể xem trước import:</b><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<x-breadcrumb :items="[['title'=>'Đề thi tự luận','url'=>route('essay-exams.index')],['title'=>'Soạn đề mới']]" />
<div class="bg-blue-50 border border-blue-200 rounded-xl p-5 mb-5"><h2 class="font-semibold text-lg text-blue-900">Import bộ câu hỏi + đáp án</h2><p class="text-sm text-blue-800 mt-1 mb-3">Hỗ trợ TXT/CSV/TSV và Word .DOC/.DOCX. Dạng tích hợp dùng hai file mẫu riêng: file đề và file đáp án / hướng dẫn chấm.</p><div class="flex flex-wrap gap-2 mb-4 text-xs"><span class="font-medium text-blue-900">Tải file mẫu:</span>@foreach(['mau-de-gop.txt'=>'Mẫu gộp TXT','mau-de-gop.docx'=>'Mẫu gộp Word','mau-de-tich-hop.doc'=>'Mẫu đề tích hợp .DOC','mau-dap-an-tich-hop.doc'=>'Mẫu đáp án tích hợp .DOC','bo-de-mau.txt'=>'Bộ đề mẫu','cau-hoi-mau.txt'=>'Câu hỏi mẫu','dap-an-mau.txt'=>'Đáp án mẫu'] as $file=>$label)<a class="text-blue-700 hover:underline" href="{{ asset('samples/essay-exam/'.$file) }}" download>{{ $label }}</a>@endforeach</div><form method="POST" action="{{ route('essay-exams.import') }}" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-3">@csrf<input name="import_code" required placeholder="Mã đề gốc (vd: DT-2026-01)" class="border rounded-lg px-3 py-2 bg-white"><input name="import_title" placeholder="Tên đề (tùy chọn)" class="border rounded-lg px-3 py-2 bg-white"><select name="import_subject_id" required class="border rounded-lg px-3 py-2 bg-white"><option value="">Chọn môn học hiện có</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}">{{ $subject->code }} — {{ $subject->name }}</option>@endforeach</select><input type="number" name="duration_minutes" value="60" min="1" max="600" class="border rounded-lg px-3 py-2 bg-white" placeholder="Thời gian (phút)"><input type="file" name="import_file" required accept=".txt,.csv,.tsv,.doc,.docx" class="border rounded-lg px-3 py-2 bg-white md:col-span-2"><button class="md:col-span-2 justify-self-start px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700">Import và tạo bản nháp</button></form></div>
 <form method="POST" action="{{ route('essay-exams.store') }}" class="space-y-5" id="exam-form">@csrf
<div class="bg-white border rounded-xl p-5"><h2 class="font-semibold text-lg mb-4">Thông tin đề</h2><div class="grid md:grid-cols-2 gap-4"><div><label class="block text-sm mb-1">Mã đề *</label><input name="code" required class="w-full border rounded-lg px-3 py-2" value="{{ old('code') }}">@error('code')<p class="text-red-600 text-xs mt-1">{{ $message }}</p>@enderror</div><div><label class="block text-sm mb-1">Môn học *</label><select name="subject_id" required class="w-full border rounded-lg px-3 py-2"><option value="">Chọn môn học đã có</option>@foreach($subjects as $subject)<option value="{{ $subject->id }}" @selected(old('subject_id')==$subject->id)>{{ $subject->code }} — {{ $subject->name }}</option>@endforeach</select></div><div><label class="block text-sm mb-1">Tên đề *</label><input name="title" required class="w-full border rounded-lg px-3 py-2" value="{{ old('title') }}"></div><div><label class="block text-sm mb-1">Thời gian (phút)</label><input type="number" name="duration_minutes" min="1" max="600" value="{{ old('duration_minutes',60) }}" class="w-full border rounded-lg px-3 py-2"></div></div><label class="block text-sm mt-4 mb-1">Ghi chú</label><textarea name="note" rows="2" class="w-full border rounded-lg px-3 py-2">{{ old('note') }}</textarea></div>
<div class="bg-white border rounded-xl p-5"><div class="flex items-center justify-between mb-4"><h2 class="font-semibold text-lg">Câu hỏi và đáp án</h2><button type="button" id="add-question" class="px-3 py-2 rounded-lg bg-slate-100 hover:bg-slate-200">+ Thêm câu</button></div><div id="questions">@for($i=0;$i<3;$i++)<div class="question border rounded-lg p-4 mb-3"><div class="flex justify-between mb-2"><strong>Câu <span class="number">{{ $i+1 }}</span></strong><button type="button" class="remove text-red-600 {{ $i<1?'hidden':'' }}">Xóa</button></div><textarea name="questions[{{ $i }}][content]" required rows="3" class="w-full border rounded px-3 py-2 mb-2" placeholder="Nội dung câu hỏi..."></textarea><textarea name="questions[{{ $i }}][answer]" rows="2" class="w-full border rounded px-3 py-2 mb-2" placeholder="Đáp án / hướng dẫn chấm..."></textarea><input name="questions[{{ $i }}][points]" type="number" step="0.25" min="0" value="1" class="border rounded px-3 py-2 w-32" placeholder="Điểm"></div>@endfor</div></div><div class="flex justify-end gap-3"><a href="{{ route('essay-exams.index') }}" class="px-4 py-2 border rounded-lg">Hủy</a><button class="px-5 py-2 rounded-lg bg-blue-600 text-white">Lưu bản nháp</button></div></form>
<script>const box=document.getElementById('questions');function renumber(){[...box.querySelectorAll('.question')].forEach((el,i)=>{el.querySelector('.number').textContent=i+1;el.querySelectorAll('[name]').forEach(x=>x.name=x.name.replace(/questions\[\d+\]/,'questions['+i+']'))})}[...box.querySelectorAll('.remove')].forEach(b=>b.onclick=()=>{b.closest('.question').remove();renumber()});document.getElementById('add-question').onclick=()=>{const i=box.children.length;const el=document.createElement('div');el.className='question border rounded-lg p-4 mb-3';el.innerHTML='<div class="flex justify-between mb-2"><strong>Câu <span class="number">'+(i+1)+'</span></strong><button type="button" class="remove text-red-600">Xóa</button></div><textarea name="questions['+i+'][content]" required rows="3" class="w-full border rounded px-3 py-2 mb-2" placeholder="Nội dung câu hỏi..."></textarea><textarea name="questions['+i+'][answer]" rows="2" class="w-full border rounded px-3 py-2 mb-2" placeholder="Đáp án / hướng dẫn chấm..."></textarea><input name="questions['+i+'][points]" type="number" step="0.25" min="0" value="1" class="border rounded px-3 py-2 w-32">';el.querySelector('.remove').onclick=()=>{el.remove();renumber()};box.append(el)};</script>
<script>
const importForm = document.querySelector('form[action="{{ route('essay-exams.import') }}"]');
const actualImportForm = importForm || [...document.querySelectorAll('form')].find(f => f.querySelector('input[name="import_file"]'));
document.querySelectorAll('#exam-form select[name="subject_id"], [name="import_subject_id"]').forEach(select => select.dataset.nativeSelect = '1');
if (actualImportForm) {
  const importLabels = {import_code:'Mã đề gốc *', import_title:'Tên đề', import_subject_id:'Môn học', duration_minutes:'Thời gian làm bài (phút)', import_file:'File câu hỏi / bộ đề'};
  Object.entries(importLabels).forEach(([name, text]) => {
    const field = actualImportForm.querySelector('[name="'+name+'"]');
    if (!field || field.closest('label')) return;
     const label = document.createElement('label'); label.className = 'block text-sm font-bold text-slate-800'; label.dataset.importField = name; label.textContent = text;
    field.parentNode.insertBefore(label, field); label.appendChild(field);
  });
  const metadata = document.createElement('div');
  metadata.className = 'md:col-span-2 grid md:grid-cols-4 gap-3';
  metadata.innerHTML = '<label class="block text-sm font-bold text-black">Năm học<input name="academic_year" required maxlength="20" placeholder="Ví dụ: 2025-2026" class="mt-1 w-full border rounded-lg px-3 py-2 text-black font-semibold"></label>'
    + '<label class="block text-sm font-bold text-black">Học kỳ<input name="semester" type="text" required maxlength="30" placeholder="Ví dụ: 1, 2, Hè, HK phụ" class="mt-1 w-full border rounded-lg px-3 py-2 text-black font-semibold"></label>'
    + '<label class="block text-sm font-bold text-black">Mức độ<select name="difficulty" required class="mt-1 w-full border rounded-lg px-3 py-2 text-black font-semibold"><option value="">Chọn mức độ</option><option value="Dễ">Dễ</option><option value="Vừa">Vừa</option><option value="Khó">Khó</option></select></label>'
    + '<label class="block text-sm font-bold text-black">Dạng đề<select name="exam_type" required class="mt-1 w-full border rounded-lg px-3 py-2 text-black font-semibold"><option value="Tự luận">Tự luận</option><option value="Tích hợp">Tích hợp</option></select></label>';
  const submitButton = actualImportForm.querySelector('button');
  const importExamType = metadata.querySelector('[name="exam_type"]')?.closest('label');
  const importTypeBox = document.createElement('div');
  importTypeBox.className = 'md:col-span-2 rounded-xl border-2 border-blue-300 bg-blue-50 p-4';
  if (importExamType) { importTypeBox.appendChild(importExamType); }
  const firstImportField = actualImportForm.querySelector('[name="import_code"]')?.closest('label');
  if (firstImportField) { actualImportForm.insertBefore(importTypeBox, firstImportField); actualImportForm.insertBefore(metadata, firstImportField); }
  else if (submitButton) { actualImportForm.insertBefore(importTypeBox, submitButton); actualImportForm.insertBefore(metadata, submitButton); }
}
if (actualImportForm && !importForm) {
  actualImportForm.action='{{ route('essay-exams.import') }}';
  const submit = actualImportForm.querySelector('button');
  if (submit) submit.textContent='Import và tạo bản nháp';
}
if (importForm) {
  importForm.action='{{ route('essay-exams.import') }}';
  const submit = importForm.querySelector('button[type="submit"]') || importForm.querySelector('button');
  if (submit) submit.textContent='Import và tạo bản nháp';
  const hint = document.createElement('div');
  hint.className='md:col-span-2 rounded-lg bg-white border border-blue-200 px-3 py-2 text-sm text-blue-800';
  hint.textContent='File sẽ được đọc thành một bộ đề gồm các Đề số, câu hỏi, đáp án và barem điểm; sau khi import đề được lưu ở trạng thái Bản nháp để kiểm tra và gửi phê duyệt.';
  importForm.prepend(hint);
  const data = @json($classes);
  const selectWrap = document.createElement('label'); selectWrap.className='block text-sm font-bold text-slate-800'; selectWrap.textContent='Lớp được phân công *';
    const select = document.createElement('select'); select.name='import_class_id'; select.required=true; select.className='mt-1 w-full border rounded-lg px-3 py-2 bg-white'; selectWrap.appendChild(select);
    selectWrap.dataset.importField = 'import_class_id';
   select.dataset.nativeSelect = '1';
  select.innerHTML='<option value="">Chọn lớp được phân công trước</option>'+data.map(x=>`<option value="${x.class_id}" data-subjects="${(x.subject_ids || (x.subject_id ? [x.subject_id] : [])).join(',')}">${x.label}</option>`).join('');
   const subject=importForm.querySelector('[name="import_subject_id"]');
   const lessonWrap = document.createElement('label'); lessonWrap.className='block text-sm font-bold text-slate-800'; lessonWrap.dataset.importField='import_lesson_id'; lessonWrap.textContent='Bài học';
   const lesson = document.createElement('select'); lesson.name='import_lesson_id'; lesson.className='mt-1 w-full border rounded-lg px-3 py-2 bg-white'; const lessonData=@json($lessonOptions); lesson.innerHTML='<option value="">Chọn bài học</option>'+lessonData.map(x=>`<option value="${x.id}" data-subject="${x.subject_id}" data-class="${x.class_id}">${x.title}</option>`).join(''); lessonWrap.appendChild(lesson);
   subject?.closest('label')?.insertAdjacentElement('afterend',lessonWrap);
  subject?.closest('label')?.insertAdjacentElement('beforebegin',selectWrap);
  if (subject) subject.disabled = true;
    const filterLessons=()=>{ [...lesson.options].forEach(o=>{o.hidden=!!(o.value && (o.dataset.class!==select.value || o.dataset.subject!==subject.value));}); if(lesson.selectedOptions[0]?.hidden) lesson.value=''; }; select.addEventListener('change',()=>{ const classOption=select.options[select.selectedIndex]; const subjectIds=(classOption?.dataset.subjects || '').split(',').map(String).map(x=>x.trim()).filter(Boolean); [...subject.options].forEach(o=>{o.hidden=!!(o.value && subjectIds.length && !subjectIds.includes(String(o.value)))}); subject.value=subjectIds.length===1 ? subjectIds[0] : ''; subject.disabled=!select.value; if(select.value && !subjectIds.length){ [...subject.options].forEach(o=>o.hidden=false); subject.value=''; } filterLessons(); }); subject.addEventListener('change',filterLessons);
    const syncLessonRequired=()=>{ const type=importForm.querySelector('[name="exam_type"]')?.value || ''; lesson.required=type.includes('Trắc') || type.includes('Tích'); }; document.querySelectorAll('[name="exam_type"]').forEach(x=>x.addEventListener('change',syncLessonRequired)); syncLessonRequired();
}
</script>
<script>
(() => {
  const form = document.querySelector('form[action="{{ route('essay-exams.import') }}"]');
  const classSelect = form?.querySelector('[name="import_class_id"]');
  const subjectSelect = form?.querySelector('[name="import_subject_id"]');
  if (!form || !classSelect || !subjectSelect) return;
  const classData = @json($classes);
  const specializations = @json($specializations);
  const specializationWrap = document.createElement('label');
  specializationWrap.className = 'block text-sm font-bold text-slate-800';
  specializationWrap.dataset.importField = 'import_specialization_id';
  specializationWrap.textContent = 'Ngành đào tạo *';
  const specializationSelect = document.createElement('select');
  specializationSelect.name = 'import_specialization_id';
  specializationSelect.required = true;
  specializationSelect.className = 'mt-1 w-full border rounded-lg px-3 py-2 bg-white';
  specializationSelect.innerHTML = '<option value="">Chọn ngành đào tạo</option>' + specializations.map((item) => `<option value="${item.id}">${item.code} — ${item.name}</option>`).join('');
  specializationWrap.appendChild(specializationSelect);
  classSelect.closest('label')?.insertAdjacentElement('beforebegin', specializationWrap);
  specializationSelect.dataset.nativeSelect = '1';

  [...classSelect.options].forEach((option) => {
    const item = classData.find((row) => String(row.class_id) === String(option.value));
    if (item) option.dataset.specialization = item.specialization_id || '';
  });
  subjectSelect.dataset.nativeSelect = '1';

  const resetSubject = () => {
    subjectSelect.value = '';
    subjectSelect.disabled = !classSelect.value;
    [...subjectSelect.options].forEach((option) => { option.hidden = !!(option.value && classSelect.value && !(classSelect.options[classSelect.selectedIndex]?.dataset.subjects || '').split(',').includes(String(option.value))); });
  };
  const filterClasses = () => {
    const specializationId = specializationSelect.value;
    const current = classSelect.value;
    [...classSelect.options].forEach((option) => { option.hidden = !!(option.value && specializationId && option.dataset.specialization !== specializationId); });
    if (current && classSelect.options[classSelect.selectedIndex]?.hidden) classSelect.value = '';
    resetSubject();
    const lesson = form.querySelector('[name="import_lesson_id"]');
    if (lesson) { lesson.value = ''; [...lesson.options].forEach((option) => { option.hidden = !!(option.value && (!classSelect.value || option.dataset.class !== classSelect.value)); }); }
  };
  specializationSelect.addEventListener('change', filterClasses);
  classSelect.addEventListener('change', resetSubject);
  filterClasses();
})();
</script>
<script>
const multiImportInput = document.querySelector('form[action="{{ route('essay-exams.import') }}"] [name="import_file"]');
if (multiImportInput) {
  multiImportInput.name = 'import_files[]';
  multiImportInput.multiple = true;
  multiImportInput.setAttribute('multiple', 'multiple');
  const multiImportLabel = multiImportInput.closest('label');
  const multiFileHint = document.createElement('span');
  multiFileHint.className = 'block text-xs font-semibold text-blue-700 mt-1';
  multiFileHint.textContent = 'Có thể chọn nhiều file đề cùng lúc; file đáp án chọn ở ô riêng bên dưới';
  multiImportLabel?.appendChild(multiFileHint);
  multiImportInput.addEventListener('change', () => {
    multiFileHint.textContent = multiImportInput.files.length
      ? `Đã chọn ${multiImportInput.files.length} file đề`
      : 'Có thể chọn nhiều file đề cùng lúc; file đáp án chọn ở ô riêng bên dưới';
  });
}
const importTypeSelect = document.querySelector('form[action="{{ route('essay-exams.import') }}"] [name="exam_type"]');
const importQuestionFile = document.querySelector('form[action="{{ route('essay-exams.import') }}"] [name="import_files[]"]');
document.querySelectorAll('[name="exam_type"]').forEach((select) => select.setAttribute('data-native-select', '1'));
const examTypeChoices = [
  { value: 'Tự luận', icon: '✎', title: 'Tự luận', help: 'Câu hỏi và hướng dẫn chấm' },
  { value: 'Tích hợp', icon: '◈', title: 'Tích hợp', help: 'Trắc nghiệm kết hợp tự luận' },
];
document.querySelectorAll('[name="exam_type"]').forEach((select) => {
  if (select.dataset.typePickerReady) return;
  select.dataset.typePickerReady = '1';
  select.classList.add('exam-type-hidden');
  const picker = document.createElement('div');
  picker.className = 'exam-type-picker';
  picker.setAttribute('role', 'radiogroup');
   picker.innerHTML = examTypeChoices.map((item) => `<button type="button" class="exam-type-option" data-value="${item.value}" role="radio"><span class="type-icon">${item.icon}</span><span class="type-title">${item.title}</span><span class="type-help">${item.help}</span></button>`).join('');
   const essayChoice = picker.querySelector('.exam-type-option:first-child');
   if (essayChoice) {
     essayChoice.dataset.value = 'Tự luận';
     essayChoice.querySelector('.type-title').textContent = 'Tự luận';
     essayChoice.querySelector('.type-help').textContent = 'Câu hỏi và hướng dẫn chấm';
   }
  select.closest('label')?.insertAdjacentElement('afterend', picker);
  const syncPicker = () => {
    const choices = picker.querySelectorAll('.exam-type-option');
    choices.forEach((button, index) => {
      const active = button.dataset.value === select.value;
      button.classList.toggle('is-active', active);
      button.setAttribute('aria-checked', active ? 'true' : 'false');
      const title = button.querySelector('.type-title');
      const help = button.querySelector('.type-help');
      if (index === 0) { if (title) title.textContent = 'Tự luận'; if (help) help.textContent = 'Câu hỏi và hướng dẫn chấm'; }
      if (index === 1) { if (title) title.textContent = 'Tích hợp'; if (help) help.textContent = 'Trắc nghiệm kết hợp tự luận'; }
    });
  };
  picker.querySelectorAll('.exam-type-option').forEach((button) => button.addEventListener('click', () => { select.value = button.dataset.value; select.dispatchEvent(new Event('change', { bubbles: true })); syncPicker(); }));
  select.addEventListener('change', syncPicker);
  syncPicker();
});
document.querySelectorAll('.exam-type-option').forEach((option, index) => {
  const title = option.querySelector('.type-title');
  const help = option.querySelector('.type-help');
  if (title && /import|nháp/i.test(title.textContent || '')) {
    title.textContent = index === 0 ? 'Tự luận' : title.textContent;
    if (index === 0 && help) help.textContent = 'Câu hỏi và hướng dẫn chấm';
  }
});
const integratedSampleLinks = [...document.querySelectorAll('a[href*="mau-de-tich-hop.doc"], a[href*="mau-dap-an-tich-hop.doc"]')];
const refreshIntegratedSamples = () => integratedSampleLinks.forEach((link) => { link.hidden = !(importTypeSelect?.value || '').toLowerCase().includes('tích'); });
refreshIntegratedSamples();
importTypeSelect?.addEventListener('change', refreshIntegratedSamples);
if (importTypeSelect && importQuestionFile) {
  const importForm = importQuestionFile.form;
  const questionLabel = importQuestionFile.closest('label');
  const importModeLabel = document.createElement('label');
  importModeLabel.className = 'block text-sm font-bold text-slate-800'; importModeLabel.dataset.importField = 'import_mode';
  importModeLabel.innerHTML = 'Nội dung cần import <select name="import_mode" class="mt-1 w-full border rounded-lg px-3 py-2 bg-white"><option value="question">Import đề / câu hỏi</option><option value="answer">Import đáp án</option></select>';
  importTypeSelect.closest('label')?.insertAdjacentElement('afterend', importModeLabel);
  const importModeSelect = importModeLabel.querySelector('[name="import_mode"]');
  const answerLabel = document.createElement('label');
  answerLabel.className = 'block text-sm font-bold text-slate-800'; answerLabel.dataset.importField = 'answer_file';
  answerLabel.innerHTML = 'File đáp án / hướng dẫn chấm <input type="file" name="answer_file" accept=".txt,.csv,.tsv,.doc,.docx" class="mt-1 w-full border rounded-lg px-3 py-2 bg-white">';
  questionLabel?.insertAdjacentElement('afterend', answerLabel);
  const answerFile = answerLabel.querySelector('[name="answer_file"]');
  const refreshImportFiles = () => {
    const selectedType = importTypeSelect.value.toLowerCase();
    const isIntegrated = selectedType.includes('tích');
    const answerMode = isIntegrated && importModeSelect.value === 'answer';
    const fileLabel = selectedType.includes('tích') ? 'File đề tích hợp ' : (selectedType.includes('trắc') ? 'File đề trắc nghiệm ' : 'File câu hỏi / bộ đề ');
    if (questionLabel?.firstChild) questionLabel.firstChild.textContent = answerMode ? 'Không cần file đề khi import đáp án ' : fileLabel;
    importModeLabel.hidden = !isIntegrated;
    questionLabel.hidden = answerMode;
    importQuestionFile.required = !answerMode;
    answerLabel.hidden = !answerMode;
    answerFile.required = answerMode;
    if (!answerMode) answerFile.value = '';
    const submit = importForm.querySelector('button[type="submit"]') || importForm.querySelector('button');
    if (submit) submit.textContent = answerMode ? 'Import đáp án' : 'Import và tạo bản nháp';
  };
  importTypeSelect.addEventListener('change', refreshImportFiles);
  importModeSelect.addEventListener('change', refreshImportFiles);
  refreshImportFiles();
}
</script>
<script>
const examForm = document.getElementById('exam-form');
if (examForm) {
  const metadata = document.createElement('div');
   metadata.className = 'exam-metadata-panel bg-white border rounded-xl p-5 grid md:grid-cols-4 gap-4';
  metadata.innerHTML = '<label class="block text-sm font-bold text-black">Năm học<input name="academic_year" required maxlength="20" placeholder="Ví dụ: 2025-2026" class="mt-1 w-full border rounded-lg px-3 py-2 text-black font-semibold"></label>'
    + '<label class="block text-sm font-bold text-black">Học kỳ<input name="semester" type="text" required maxlength="30" placeholder="Ví dụ: 1, 2, Hè, HK phụ" class="mt-1 w-full border rounded-lg px-3 py-2 text-black font-semibold"></label>'
    + '<label class="block text-sm font-bold text-black">Mức độ<select name="difficulty" required class="mt-1 w-full border rounded-lg px-3 py-2 text-black font-semibold"><option value="">Chọn mức độ</option><option value="Dễ">Dễ</option><option value="Vừa">Vừa</option><option value="Khó">Khó</option></select></label>'
    + '<label class="block text-sm font-bold text-black">Dạng đề<select name="exam_type" required class="mt-1 w-full border rounded-lg px-3 py-2 text-black font-semibold"><option value="Tự luận">Tự luận</option><option value="Tích hợp">Tích hợp</option></select></label>';
  const questionBox = document.getElementById('questions').closest('.bg-white');
  const composeExamType = metadata.querySelector('[name="exam_type"]')?.closest('label');
  const composeTypeBox = document.createElement('div');
   composeTypeBox.className = 'exam-type-panel bg-blue-50 border-2 border-blue-300 rounded-xl p-4 mb-4';
  if (composeExamType) { composeTypeBox.appendChild(composeExamType); }
   const firstInfoSection = examForm.querySelector('.bg-white');
   firstInfoSection?.classList.add('exam-info-panel');
  examForm.insertBefore(composeTypeBox, firstInfoSection || questionBox);
  examForm.insertBefore(metadata, questionBox);
  const subject = examForm.querySelector('[name="subject_id"]');
  const classLabel = document.createElement('label');
  classLabel.className = 'block text-sm mb-1';
  classLabel.innerHTML = 'Lớp được phân công *<select name="class_id" required class="mt-1 w-full border rounded-lg px-3 py-2"><option value="">Chọn lớp trước</option></select>';
   const classSelect = classLabel.querySelector('select');
   classSelect.dataset.nativeSelect = '1';
  const classData = @json($classes);
  classSelect.insertAdjacentHTML('beforeend', classData.map(x => '<option value="'+x.class_id+'" data-subjects="'+(x.subject_ids || (x.subject_id ? [x.subject_id] : [])).join(',')+'">'+x.label+'</option>').join(''));
  subject.closest('div').parentElement.insertBefore(classLabel, subject.closest('div'));
  subject.disabled = true;
  classSelect.addEventListener('change', function () {
     const subjectIds = (this.options[this.selectedIndex]?.dataset.subjects || '').split(',').map(String).map(x=>x.trim()).filter(Boolean);
     [...subject.options].forEach(option => { option.hidden = !!(option.value && subjectIds.length && !subjectIds.includes(String(option.value))); });
     subject.value = subjectIds.length === 1 ? subjectIds[0] : '';
     subject.disabled = !this.value;
     if (this.value && !subjectIds.length) { [...subject.options].forEach(option => option.hidden = false); subject.value = ''; }
  });
}
 </script>
 <script>
 document.querySelectorAll('.exam-type-picker').forEach((picker) => {
     const first = picker.querySelector('.exam-type-option:first-child');
     if (!first) return;
     first.dataset.value = 'Tự luận';
     first.innerHTML = '<span class="type-icon">✎</span><span class="type-title">Tự luận</span><span class="type-help">Câu hỏi và hướng dẫn chấm</span>';
 });
 const importFormForLesson = document.querySelector('form[action*="essay-exams/import"]');
 const importTypeForLesson = importFormForLesson?.querySelector('[name="exam_type"]');
 const lessonFieldForImport = importFormForLesson?.querySelector('[name="import_lesson_id"]')?.closest('label');
 const syncLessonVisibility = () => {
     if (!importTypeForLesson || !lessonFieldForImport) return;
     const needsLesson = importTypeForLesson.value === 'Tích hợp';
     lessonFieldForImport.hidden = !needsLesson;
     importFormForLesson.querySelector('[name="import_lesson_id"]').required = needsLesson;
     if (!needsLesson) importFormForLesson.querySelector('[name="import_lesson_id"]').value = '';
 };
 importTypeForLesson?.addEventListener('change', syncLessonVisibility);
 syncLessonVisibility();
 </script>
 <script>
 (() => {
     const form = document.querySelector('form[action="{{ route('essay-exams.import') }}"]');
     const subject = form?.querySelector('[name="import_subject_id"]');
     const semesterInput = form?.querySelector('[name="semester"]');
     if (!form || !subject || !semesterInput) return;
     const curriculumSubjects = @json($subjects->map(fn ($item) => ['id' => $item->id, 'semester' => $item->semester])->values());
     const semesterSelect = document.createElement('select');
     semesterSelect.name = 'semester';
     semesterSelect.required = true;
     semesterSelect.className = semesterInput.className;
     semesterSelect.innerHTML = '<option value="">Chọn học kỳ theo chương trình đào tạo</option>' + Array.from({ length: 7 }, (_, index) => {
         const value = `semester_${index + 1}`;
         return `<option value="${value}">Học kỳ ${index + 1}</option>`;
     }).join('');
     semesterInput.replaceWith(semesterSelect);
     const syncSemester = () => {
         const curriculum = curriculumSubjects.find((item) => String(item.id) === String(subject.value));
         const rawSemester = String(curriculum?.semester || '');
         semesterSelect.value = rawSemester.startsWith('semester_') ? rawSemester : (rawSemester ? `semester_${rawSemester}` : '');
         semesterSelect.disabled = false;
     };
     subject.addEventListener('change', syncSemester);
     syncSemester();
 })();
 </script>
 <script>
 document.querySelectorAll('.exam-type-picker').forEach((picker) => {
     const choices = picker.querySelectorAll('.exam-type-option');
     const labels = [
         ['Tự luận', 'Câu hỏi và hướng dẫn chấm'],
         ['Tích hợp', 'Trắc nghiệm kết hợp tự luận'],
     ];
     choices.forEach((choice, index) => {
         if (!labels[index]) return;
         choice.dataset.value = index === 0 ? 'Tự luận' : 'Tích hợp';
         const title = choice.querySelector('.type-title');
         const help = choice.querySelector('.type-help');
         if (title) title.textContent = labels[index][0];
         if (help) help.textContent = labels[index][1];
     });
 });
 </script>
<script>
(() => {
  const form = document.querySelector('form[action="{{ route('essay-exams.import') }}"]');
  if (!form) return;
  const classSelect = form.querySelector('[name="import_class_id"]');
  const subjectSelect = form.querySelector('[name="import_subject_id"]');
  const yearInput = form.querySelector('[name="academic_year"]');
  const semesterInput = form.querySelector('[name="semester"]');
  const options = @json($curriculumOptions ?? []);
  if (!classSelect || !subjectSelect || !yearInput || !semesterInput) return;
  const academicYears = @json(($academicYears ?? collect())->map(fn ($year) => ['code' => $year->code, 'name' => $year->name])->values());
  const yearSelect = document.createElement('select');
  yearSelect.name = 'academic_year';
  yearSelect.required = true;
  yearSelect.className = yearInput.className;
  yearSelect.innerHTML = '<option value="">Chọn năm học từ cơ sở dữ liệu</option>' + academicYears.map(year => `<option value="${year.code}">${year.code}${year.name ? ` · ${year.name}` : ''}</option>`).join('');
  yearInput.replaceWith(yearSelect);
  const semesterSelect = semesterInput.tagName === 'SELECT' ? semesterInput : (() => { const select = document.createElement('select'); select.name = 'semester'; select.required = true; select.className = semesterInput.className; select.innerHTML = '<option value="">Chọn học kỳ từ chương trình đào tạo</option>' + Array.from({ length: 7 }, (_, index) => `<option value="semester_${index + 1}">Học kỳ ${index + 1}</option>`).join(''); semesterInput.replaceWith(select); return select; })();
  const yearField = yearSelect;
  const semesterField = semesterSelect;
  const sync = () => {
    const item = options.find(x => String(x.class_id) === String(classSelect.value) && String(x.subject_id) === String(subjectSelect.value));
    yearField.value = item?.academic_year || '';
    semesterField.value = item?.semester || '';
    yearField.classList.toggle('bg-blue-50', !!item);
    semesterField.classList.toggle('bg-blue-50', !!item);
  };
  classSelect.addEventListener('change', sync);
  subjectSelect.addEventListener('change', sync);
  sync();
})();
</script>
 @endsection
