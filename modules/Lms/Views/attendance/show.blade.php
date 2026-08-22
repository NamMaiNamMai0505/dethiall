@extends('layouts.admin')
@section('title', $session->title)
@section('page-title', 'Điểm danh buổi')
@section('content')
<a href="{{ route('lms.courses.attendance.index', $course) }}" class="text-sm text-blue-600">← Danh sách buổi</a>
<div class="flex flex-wrap justify-between gap-2 mt-2 mb-4">
    <div>
        <h1 class="text-xl font-bold">{{ $session->title }}</h1>
        <p class="text-sm text-slate-500">{{ $session->mode }} · {{ $session->status }} · {{ $session->session_date?->format('d/m/Y') }}</p>
        @if($session->checkin_token)
            <p class="text-xs font-mono mt-1">Token QR: {{ $session->checkin_token }}</p>
            <p class="text-xs text-slate-500">Link HV: {{ route('lms.learn.attendance.checkin', [$course, $session]) }}?token={{ $session->checkin_token }}</p>
        @endif
    </div>
    @if($session->status === 'open')
    <form method="POST" action="{{ route('lms.courses.attendance.close', [$course, $session]) }}">@csrf
        <button class="px-3 py-2 border rounded-lg text-sm">Đóng buổi</button>
    </form>
    @endif
</div>
<form method="POST" action="{{ route('lms.courses.attendance.mark', [$course, $session]) }}">
@csrf
<div class="bg-white border rounded-xl overflow-hidden">
<table class="min-w-full text-sm">
<thead class="bg-slate-50"><tr>
<th class="text-left px-4 py-2">Học viên</th>
<th class="text-left px-4 py-2">Trạng thái</th>
<th class="text-left px-4 py-2">Check-in</th>
</tr></thead>
<tbody class="divide-y">
@foreach($students as $m)
    @php $u=$m->user; $rec=$records[$u->id ?? 0] ?? null; @endphp
    @continue(!$u)
    <tr>
        <td class="px-4 py-2">{{ $u->name }}</td>
        <td class="px-4 py-2">
            <input type="hidden" name="records[{{ $loop->index }}][user_id]" value="{{ $u->id }}">
            <select name="records[{{ $loop->index }}][status]" class="border rounded-lg text-sm px-2 py-1">
                @foreach(['present'=>'Có mặt','absent'=>'Vắng','late'=>'Muộn','excused'=>'Có phép'] as $k=>$lab)
                    <option value="{{ $k }}" @selected(($rec->status ?? 'present')===$k)>{{ $lab }}</option>
                @endforeach
            </select>
        </td>
        <td class="px-4 py-2 text-xs text-slate-500">{{ $rec?->checked_in_at?->format('H:i d/m') ?? '—' }} · {{ $rec?->method }}</td>
    </tr>
@endforeach
</tbody>
</table>
</div>
<button class="mt-3 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm">Lưu điểm danh</button>
</form>
@endsection
