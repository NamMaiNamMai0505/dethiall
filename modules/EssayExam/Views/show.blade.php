@extends('layouts.admin')
@section('title','Chi tiết bộ đề')
@section('page-title','Chi tiết bộ đề')
@section('content')
@include('partials.module-menu', ['module' => 'exam'])
@php($formatAnswer = static fn ($answer) => preg_replace('/\R\s*(\[[^\x5D\r\n]*(?:\x{0111}i\x{1EC3}m|diem)[^\x5D\r\n]*\])/iu', ' $1', trim((string) $answer)) ?: trim((string) $answer))
<style>
    .essay-question-content { font-family: "Times New Roman", Times, serif; font-size: 15px; line-height: 1.35; white-space: pre-wrap; }
    .essay-question-options { font-family: "Times New Roman", Times, serif; font-size: 15px; line-height: 1.35; }
</style>
<h1 class="text-2xl font-bold mb-5">{{ $exam->title }}</h1>
@php($labels=['DRAFT'=>'Bản nháp','PENDING_DEPT'=>'Chờ khoa duyệt','PENDING_EXAM_OFFICE'=>'Chờ khảo thí duyệt','PENDING_BGH'=>'Chờ BGH duyệt','APPROVED'=>'Đã duyệt','RETURNED'=>'Trả lại'])
@foreach($exam->questions->groupBy('paper_number') as $paper=>$questions)
@php($paperStatus=$questions->first()->paper_status ?? $exam->status)
<section class="bg-white border rounded-xl overflow-hidden mb-5">
    <div class="px-5 py-3 bg-blue-50 border-b flex justify-between">
        <div><h2 class="font-bold text-lg">Đề số {{ $paper }}</h2><p class="text-sm">{{ $questions->count() }} câu · {{ number_format($questions->sum('points'),2,',','.') }} điểm</p></div>
        <span class="px-2 py-1 rounded-full bg-white text-sm">{{ $labels[$paperStatus] ?? $paperStatus }}</span>
    </div>
    <div class="p-5 space-y-4">
        @foreach($questions as $q)
        <article class="border rounded-lg p-4">
            <b>{{ $q->question_type === 'essay' ? 'Tự luận' : 'Câu '.$q->question_number }} ({{ $q->points }} điểm)</b>
            <div class="mt-2 essay-question-content">{{ $q->content }}</div>
            @if($q->question_type === 'multiple_choice' && is_array($q->options))
            <div class="mt-2 grid md:grid-cols-2 gap-1 essay-question-options">@foreach($q->options as $key=>$option)<div><b>{{ strtoupper($key) }}.</b> {{ $option }}</div>@endforeach</div>
            @endif
            @if($q->answer)<details class="mt-3"><summary class="text-blue-600 cursor-pointer">Xem đáp án / hướng dẫn chấm</summary><div class="mt-2 p-3 bg-slate-50 rounded whitespace-pre-line">{{ $formatAnswer($q->answer) }}</div></details>@endif
        </article>
        @endforeach
    </div>
</section>
@endforeach
@if(in_array($exam->status, ['DRAFT', 'RETURNED'], true) && auth()->user()->can('essay-exams.submission.create') && ($exam->created_by_user_id === auth()->id() || auth()->user()->hasRole('super-admin')))
<div class="bg-amber-50 border border-amber-300 rounded-xl p-4 mt-5 flex items-center justify-between gap-4"><div><p class="font-bold text-black">Đề đang ở trạng thái bản nháp</p><p class="text-sm text-black">Kiểm tra nội dung, sau đó gửi đề đến khoa để bắt đầu quy trình duyệt.</p></div><form method="POST" action="{{ route('essay-exams.submit', $exam) }}">@csrf<button class="px-5 py-2 rounded-lg bg-emerald-600 text-white font-bold whitespace-nowrap">Gửi đi duyệt</button></form></div>
@endif
@endsection
