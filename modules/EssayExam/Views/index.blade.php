@extends('layouts.admin')
@section('title','Đề thi')
@section('page-title','Đề thi')
@section('content')
@include('partials.module-menu', ['module' => 'exam'])
@php($mine=$mine??false)
@php($isAdmin=$isAdmin??false)
@if(auth()->user()->can('essay-exams.authoring.create') || auth()->user()->can('essay-exams.import.create'))
    <div class="flex justify-end mb-4"><a href="{{ route('essay-exams.create') }}" class="px-4 py-2 rounded-lg bg-blue-600 text-white">Soạn / Import đề</a></div>
@endif
@if($mine && $isAdmin)
    <form method="GET" class="bg-white border rounded-xl p-4 mb-4 grid gap-3 md:grid-cols-[1fr_auto] items-end">
        <div>
            <label for="teacher_id" class="block text-sm font-medium text-slate-700 mb-1">Giáo viên đã đẩy đề</label>
            <select id="teacher_id" name="teacher_id" class="border rounded-lg px-3 py-2 w-full" onchange="this.form.submit()">
                <option value="">Tất cả giáo viên</option>
                @foreach(($teachers??collect()) as $teacher)
                    <option value="{{ $teacher->created_by_user_id }}" @selected((int)$selectedTeacherId === (int)$teacher->created_by_user_id)>{{ $teacher->created_by_display_name ?: $teacher->created_by_username }}</option>
                @endforeach
            </select>
        </div>
        <a href="{{ route('essay-exams.mine') }}" class="px-4 py-2 rounded-lg border text-center">Xóa lọc</a>
    </form>
@endif
<h1 class="text-2xl font-bold mb-5">{{ $mine?'Đề của tôi':'Danh sách đề thi' }}</h1>
<form class="bg-white border rounded-xl p-4 mb-4 flex gap-3"><input name="search" value="{{ request('search') }}" placeholder="Tìm mã hoặc tên đề..." class="border rounded-lg px-3 py-2 flex-1"><button class="px-4 py-2 rounded-lg bg-slate-800 text-white">Lọc</button></form>
<div class="space-y-5">@if($exams->count()) @foreach($exams->groupBy(fn($e)=>(string)($e->subject->specialization_id??0).'|'.(string)($e->class_id??0)) as $group) @php($first=$group->first())<section class="bg-white border rounded-xl overflow-hidden"><div class="px-4 py-3 bg-blue-50 font-bold">Ngành: {{ $first->subject->specialization->name ?? 'Chưa phân ngành' }} · Lớp: {{ $first->class->name ?? 'Chưa gán lớp' }}</div><table class="w-full text-sm"><thead class="bg-slate-100"><tr><th class="p-3 text-left">Mã đề</th><th class="p-3 text-left">Bộ đề</th><th class="p-3 text-left">Môn</th><th class="p-3">Đề số</th><th class="p-3">Câu hỏi</th><th class="p-3">Trạng thái</th><th></th></tr></thead><tbody class="divide-y">@foreach($group as $exam) @foreach($exam->questions->groupBy('paper_number') as $paper=>$questions) @php($status=$questions->first()->paper_status??$exam->status) @php($labels=['DRAFT'=>'Bản nháp','PENDING_DEPT'=>'Chờ khoa duyệt','PENDING_EXAM_OFFICE'=>'Chờ khảo thí duyệt','PENDING_BGH'=>'Chờ BGH duyệt','APPROVED'=>'Đã duyệt','RETURNED'=>'Trả lại'])<tr><td class="p-3 font-semibold">{{ $exam->code }}-D{{ str_pad($paper,2,'0',STR_PAD_LEFT) }}</td><td class="p-3">{{ $exam->title }}</td><td class="p-3">{{ $exam->subject->name }}</td><td class="p-3 text-center font-bold">{{ $paper }}</td><td class="p-3 text-center">{{ $questions->count() }}</td><td class="p-3"><span class="px-2 py-1 rounded-full text-xs bg-slate-100">{{ $labels[$status]??$status }}</span></td><td class="p-3"><a class="text-blue-600" href="{{ route('essay-exams.show',$exam) }}?paper={{ $paper }}">Xem</a></td></tr>@endforeach @endforeach</tbody></table></section>@endforeach @else<div class="bg-white border rounded-xl p-10 text-center text-slate-500">Chưa có đề thi tự luận.</div>@endif</div>
@endsection
