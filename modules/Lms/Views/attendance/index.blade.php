@extends('layouts.admin')
@section('title', 'Điểm danh — '.$course->title)
@section('page-title', 'Điểm danh')
@section('content')
<a href="{{ route('lms.courses.show', $course) }}" class="text-sm text-blue-600">← {{ $course->title }}</a>
<h1 class="text-xl font-bold mt-2 mb-4">Điểm danh (manual / QR / tự check-in)</h1>
<div class="bg-white border rounded-xl p-4 mb-4">
<form method="POST" action="{{ route('lms.courses.attendance.store', $course) }}" class="grid sm:grid-cols-2 gap-3 text-sm">
    @csrf
    <input name="title" required placeholder="Buổi học / tiêu đề" class="border rounded-lg px-3 py-2">
    <input type="date" name="session_date" value="{{ now()->toDateString() }}" class="border rounded-lg px-3 py-2">
    <select name="mode" class="border rounded-lg px-3 py-2">
        <option value="manual">Thủ công (GV điểm)</option>
        <option value="self">Tự check-in (cửa sổ thời gian)</option>
        <option value="qr">QR / token</option>
    </select>
    <input type="datetime-local" name="open_until" class="border rounded-lg px-3 py-2" title="Hết hạn check-in">
    <button class="sm:col-span-2 bg-blue-600 text-white rounded-lg px-3 py-2">Tạo buổi điểm danh</button>
</form>
</div>
<ul class="bg-white border rounded-xl divide-y">
@forelse($sessions as $s)
    <li class="px-4 py-3 flex flex-wrap justify-between gap-2 text-sm">
        <div>
            <strong>{{ $s->title }}</strong>
            <span class="text-slate-500">· {{ $s->session_date?->format('d/m/Y') }} · {{ $s->mode }} · {{ $s->status }} · {{ $s->records_count }} bản ghi</span>
            @if($s->checkin_token)
                <div class="text-xs font-mono mt-1 text-slate-600">Token: {{ $s->checkin_token }}</div>
            @endif
        </div>
        <a href="{{ route('lms.courses.attendance.show', [$course, $s]) }}" class="text-blue-600">Chi tiết</a>
    </li>
@empty
    <li class="px-4 py-8 text-center text-slate-500">Chưa có buổi điểm danh.</li>
@endforelse
</ul>
@endsection
