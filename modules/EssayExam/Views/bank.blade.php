@extends('layouts.admin')
@section('title','Ngân hàng đề tự luận') @section('page-title','Ngân hàng đề tự luận') @section('content')
@include('partials.module-menu', ['module' => 'exam'])
@php
    $semesterLabel = static function ($value): string {
        $raw = trim((string) $value);
        $key = mb_strtolower($raw);
        return match ($key) {
            'semester_1', '1', 'hk1', 'hoc ky 1', 'học kỳ 1' => 'Học kỳ 1',
            'semester_2', '2', 'hk2', 'hoc ky 2', 'học kỳ 2' => 'Học kỳ 2',
            'semester_3', '3', 'hk3', 'hoc ky 3', 'học kỳ 3' => 'Học kỳ 3',
            'semester_4', '4', 'hk4', 'hoc ky 4', 'học kỳ 4' => 'Học kỳ 4',
            'semester_5', '5', 'hk5', 'hoc ky 5', 'học kỳ 5' => 'Học kỳ 5',
            'semester_6', '6', 'hk6', 'hoc ky 6', 'học kỳ 6' => 'Học kỳ 6',
            'semester_7', '7', 'hk7', 'hoc ky 7', 'học kỳ 7' => 'Học kỳ 7',
            'summer', 'he', 'hè', 'hoc ky he', 'học kỳ hè' => 'Học kỳ hè',
            '' => '—',
            default => $raw,
        };
    };
    $difficultyLabel = static function ($value): string {
        $raw = trim((string) $value);
        return match (mb_strtolower($raw)) {
            'easy', 'de', 'dễ' => 'Dễ',
            'medium', 'normal', 'vua', 'vừa', 'trung bình' => 'Vừa',
            'hard', 'kho', 'khó' => 'Khó',
            '' => '—',
            default => $raw,
        };
    };
    $examTypeLabel = static function ($value): string {
        $raw = trim((string) $value);
        return match (mb_strtolower($raw)) {
            'essay', 'tu luan', 'tự luận' => 'Tự luận',
            'multiple_choice', 'multiple choice', 'trac nghiem', 'trắc nghiệm' => 'Trắc nghiệm',
            'integrated', 'tich hop', 'tích hợp' => 'Tích hợp',
            '' => 'Tự luận',
            default => $raw,
        };
    };
@endphp
<h1 class="text-2xl font-bold mb-5">Ngân hàng đề tự luận</h1>
<form id="essay-bank-filter" class="bg-white border rounded-xl p-4 mb-4 grid md:grid-cols-5 gap-3">
    <label class="block text-sm font-semibold text-slate-700">Tìm kiếm
        <input name="search" value="{{ request('search') }}" placeholder="Mã đề, tiêu đề..." class="mt-1 w-full border rounded-lg px-3 py-2 font-normal">
    </label>
    <label class="block text-sm font-semibold text-slate-700">Năm học
        <input name="academic_year" value="{{ request('academic_year') }}" list="essay-bank-years" placeholder="Chọn hoặc nhập năm học" class="mt-1 w-full border rounded-lg px-3 py-2 font-normal">
        <datalist id="essay-bank-years">@foreach(($academicYears ?? collect()) as $year)<option value="{{ $year }}"></option>@endforeach</datalist>
    </label>
    <label class="block text-sm font-semibold text-slate-700">Học kỳ
        <select name="semester" class="mt-1 w-full border rounded-lg px-3 py-2 font-normal bg-white">
            <option value="">Tất cả học kỳ</option>
            @foreach(['semester_1' => 'Học kỳ 1', 'semester_2' => 'Học kỳ 2', 'semester_3' => 'Học kỳ 3', 'semester_4' => 'Học kỳ 4', 'semester_5' => 'Học kỳ 5', 'semester_6' => 'Học kỳ 6', 'semester_7' => 'Học kỳ 7', 'summer' => 'Học kỳ hè'] as $value => $label)
                <option value="{{ $value }}" @selected(($selectedSemester ?? request('semester')) === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>
    <label class="block text-sm font-semibold text-slate-700">Mức độ
        <select name="difficulty" class="mt-1 w-full border rounded-lg px-3 py-2 font-normal bg-white">
            <option value="">Tất cả mức độ</option>
            <option value="Dễ" @selected(in_array(request('difficulty'), ['Dễ','easy','de'], true))>Dễ</option>
            <option value="Vừa" @selected(in_array(request('difficulty'), ['Vừa','medium','normal','trung bình'], true))>Vừa</option>
            <option value="Khó" @selected(in_array(request('difficulty'), ['Khó','hard','kho'], true))>Khó</option>
        </select>
    </label>
    <label class="block text-sm font-semibold text-slate-700">Dạng đề
        <select name="exam_type" class="mt-1 w-full border rounded-lg px-3 py-2 font-normal bg-white">
            <option value="">Tất cả dạng đề</option>
            <option value="Tự luận" @selected(in_array(request('exam_type'), ['Tự luận','essay'], true))>Tự luận</option>
            <option value="Trắc nghiệm" @selected(in_array(request('exam_type'), ['Trắc nghiệm','multiple_choice','multiple choice'], true))>Trắc nghiệm</option>
            <option value="Tích hợp" @selected(in_array(request('exam_type'), ['Tích hợp','integrated'], true))>Tích hợp</option>
        </select>
    </label>
</form>
<script>
(() => {
    const form = document.getElementById('essay-bank-filter');
    if (!form) return;
    let timer = null;
    const submit = () => form.requestSubmit ? form.requestSubmit() : form.submit();
    form.querySelectorAll('select').forEach((field) => field.addEventListener('change', submit));
    form.querySelectorAll('input').forEach((field) => field.addEventListener('input', () => {
        clearTimeout(timer);
        timer = setTimeout(submit, 450);
    }));
})();
</script>
@if($exams->count())
@foreach($exams->groupBy(fn($e) => $e->subject->specialization_id ?? 0) as $specializationExams)
<section class="bg-white border rounded-xl overflow-hidden mb-5"><div class="px-4 py-3 bg-blue-50 font-bold">Ngành đào tạo: {{ optional($specializationExams->first()->subject->specialization)->code ?? 'Chưa phân ngành' }} — {{ optional($specializationExams->first()->subject->specialization)->name ?? '' }}</div><div class="overflow-x-auto"><table class="w-full min-w-[1100px] text-sm"><thead class="bg-slate-100"><tr><th class="p-3 text-left">Mã đề</th><th class="p-3 text-left">Môn</th><th class="p-3">Năm học</th><th class="p-3">Học kỳ</th><th class="p-3">Mức độ</th><th class="p-3">Dạng đề</th><th class="p-3">Đề số</th><th class="p-3">QR khóa</th><th class="p-3">Trạng thái</th></tr></thead><tbody class="divide-y">
@foreach($specializationExams as $exam) @foreach($exam->questions->where('paper_status','APPROVED')->groupBy('paper_number') as $paper=>$questions) @php($qrValue=($exam->approval_qr ?: 'QR-EXAM-'.$exam->id).'-D'.str_pad($paper,2,'0',STR_PAD_LEFT))<tr><td class="p-3 font-semibold">{{ $exam->code }}-D{{ str_pad($paper,2,'0',STR_PAD_LEFT) }}</td><td class="p-3">{{ $exam->subject->name ?? '—' }}</td><td class="p-3 text-center">{{ $exam->academic_year ?? '—' }}</td><td class="p-3 text-center">{{ $semesterLabel($exam->semester) }}</td><td class="p-3 text-center">{{ $difficultyLabel($exam->difficulty) }}</td><td class="p-3 text-center">{{ $examTypeLabel($exam->exam_type) }}</td><td class="p-3 text-center font-bold">{{ $paper }}</td><td class="p-3 text-center"><img class="w-20 h-20 mx-auto border rounded" alt="QR khóa đề" src="https://api.qrserver.com/v1/create-qr-code/?size=120x120&data={{ urlencode($qrValue) }}"></td><td class="p-3 text-center"><span class="px-2 py-1 rounded bg-emerald-100 text-emerald-800 text-xs">Đã khóa</span></td></tr>@endforeach @endforeach
</tbody></table></div></section>
@endforeach
@else<div class="bg-white border rounded-xl p-10 text-center text-slate-500">Ngân hàng chưa có đề đã duyệt.</div>@endif
<div class="mt-4">{{ $exams->links() }}</div>
@endsection
