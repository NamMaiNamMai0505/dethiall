@extends('layouts.admin')
@section('title','Xem trước import bộ đề')
@section('page-title','Xem trước import bộ đề')
@section('content')
<x-breadcrumb :items="[['title'=>'Đề thi tự luận','url'=>route(auth()->user()->can('essay-exams.index') ? 'essay-exams.index' : 'essay-exams.mine')],['title'=>'Xem trước import']]" />
<div class="bg-white border rounded-xl p-5 mb-5"><h1 class="text-2xl font-bold">Xem trước bộ đề</h1><p class="text-sm text-slate-500">Kiểm tra từng đề số, số câu và tổng điểm trước khi lưu. Mã bộ đề TLxxxx sẽ được cấp tự động khi xác nhận.</p></div>
<div class="bg-emerald-50 border border-emerald-300 rounded-xl p-4"><h2 class="font-bold">Sẽ import {{ $papers->count() }} đề số</h2><ul class="mt-2 list-disc pl-5 text-sm">@foreach($papers as $paper)<li><span class="text-emerald-700 font-medium">[Import]</span> Đề số {{ $paper['paper'] }} — {{ $paper['questions'] }} câu — {{ number_format($paper['points'],2,',','.') }} điểm</li>@endforeach</ul></div>
<form method="POST" action="{{ route('essay-exams.import.confirm') }}" class="mt-5 flex gap-3">@csrf<input type="hidden" name="rows_json" value='@json($rows)'>@foreach($data as $key=>$value) @if($key !== 'import_file')<input type="hidden" name="{{ $key }}" value="{{ $value }}">@endif @endforeach<button class="px-5 py-2 rounded-lg bg-emerald-600 text-white">Xác nhận import</button><a href="{{ route('essay-exams.create') }}" class="px-5 py-2 rounded-lg border">Huỷ</a></form>
@endsection
