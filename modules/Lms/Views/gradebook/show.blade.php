@extends('layouts.admin')
@section('title', 'Bảng điểm — '.$course->title)
@section('page-title', 'Bảng điểm LMS')
@section('content')
<div class="flex flex-wrap justify-between gap-3 mb-4">
    <div>
        <a href="{{ route('lms.courses.show', $course) }}" class="text-sm text-blue-600">← {{ $course->title }}</a>
        <h1 class="text-xl font-bold mt-1">Bảng điểm</h1>
        @php($gradeWeights = \Modules\Lms\Support\LmsSettings::gradeWeights())
        <p class="text-xs text-slate-500">Tổng hợp BT {{ $gradeWeights['assignments'] * 100 }}% · Thi {{ $gradeWeights['exams'] * 100 }}% · Chuyên cần {{ $gradeWeights['attendance'] * 100 }}% · Tiến độ {{ $gradeWeights['progress'] * 100 }}% (thang 10)</p>
    </div>
    <div class="flex flex-wrap gap-2">
        <form method="POST" action="{{ route('lms.courses.gradebook.refresh', $course) }}">@csrf
            <button class="px-3 py-2 rounded-lg bg-slate-800 text-white text-sm">Làm mới / lưu snapshot</button>
        </form>
        <form method="POST" action="{{ route('lms.courses.gradebook.transfer', $course) }}">@csrf
            <button class="px-3 py-2 rounded-lg bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700"><i class="bi bi-arrow-left-right mr-1"></i>Chuyển sang Quản lý điểm</button>
        </form>
    </div>
</div>
<div class="bg-white border rounded-xl overflow-x-auto">
<table class="min-w-full text-xs">
<thead class="bg-slate-50">
<tr>
    <th class="text-left px-3 py-2 sticky left-0 bg-slate-50">Học viên</th>
    @foreach($assignments as $a)<th class="px-2 py-2 whitespace-nowrap" title="{{ $a->title }}">BT{{ $loop->iteration }}</th>@endforeach
    @foreach($exams as $e)<th class="px-2 py-2 whitespace-nowrap" title="{{ $e->title }}">Thi{{ $loop->iteration }}</th>@endforeach
    <th class="px-2 py-2">TB BT</th>
    <th class="px-2 py-2">TB Thi</th>
    <th class="px-2 py-2">CC%</th>
    <th class="px-2 py-2">Tiến độ%</th>
    <th class="px-2 py-2">Tự động</th>
    <th class="px-2 py-2">Tổng kết</th>
    <th class="px-2 py-2">Ghi chú</th>
</tr>
</thead>
<tbody class="divide-y">
@forelse($rows as $uid => $r)
<tr>
    <td class="px-3 py-2 font-medium sticky left-0 bg-white whitespace-nowrap">{{ $r['user']->name ?? $uid }}</td>
    @foreach($assignments as $a)
        @php($c = $r['assignment_cells'][$a->id] ?? null)
        <td class="px-2 py-2 text-center">{{ $c && $c['score'] !== null ? $c['score'] : ($c ? '…' : '—') }}</td>
    @endforeach
    @foreach($exams as $e)
        @php($c = $r['exam_cells'][$e->id] ?? null)
        <td class="px-2 py-2 text-center">{{ $c ? $c['score'] : '—' }}</td>
    @endforeach
    <td class="px-2 py-2 text-center">{{ $r['assignment_avg'] ?? '—' }}</td>
    <td class="px-2 py-2 text-center">{{ $r['exam_avg'] ?? '—' }}</td>
    <td class="px-2 py-2 text-center">{{ $r['attendance_pct'] !== null ? $r['attendance_pct'].'%' : '—' }}</td>
    <td class="px-2 py-2 text-center">{{ $r['progress_pct'] !== null ? $r['progress_pct'].'%' : '—' }}</td>
    <td class="px-2 py-2 text-center font-semibold">{{ $r['computed_score'] ?? '—' }}</td>
    <td class="px-2 py-2" colspan="2">
        <form method="POST" action="{{ route('lms.courses.gradebook.override', [$course, $r['user']]) }}" class="flex gap-1 items-center">
            @csrf
            <input type="number" step="0.1" min="0" max="10" name="final_score" value="{{ $r['final_score'] }}" class="w-16 border rounded px-1 py-0.5" placeholder="{{ $r['computed_score'] }}">
            <input name="note" value="{{ $r['note'] }}" class="w-28 border rounded px-1 py-0.5" placeholder="Ghi chú">
            <button class="text-blue-600 font-medium">Lưu</button>
        </form>
    </td>
</tr>
@empty
<tr><td colspan="20" class="px-4 py-8 text-center text-slate-500">Chưa có học viên.</td></tr>
@endforelse
</tbody>
</table>
</div>
@endsection
