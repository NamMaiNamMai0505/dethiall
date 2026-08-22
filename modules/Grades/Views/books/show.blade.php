@extends('layouts.grades')
@section('title', $book->title)

@section('content')
<div class="flex flex-wrap items-start justify-between gap-3 mb-4">
    <div>
        @if($book->class_id)
            <a href="{{ route('grades.classes.show', $book->class_id) }}" class="text-sm text-teal-700 font-semibold hover:underline">← Lớp {{ $book->classModel?->name }}</a>
        @else
            <a href="{{ route('grades.hub') }}" class="text-sm text-teal-700 font-semibold hover:underline">← Chọn lớp</a>
        @endif
        <h1 class="text-xl font-bold text-slate-900 mt-1">{{ $book->title }}</h1>
        <p class="text-sm text-slate-500 mt-1">
            {{ $book->classModel?->name }} · {{ $book->subject?->name }}
            · <span class="grades-chip grades-chip-lock">{{ $book->statusLabel() }}</span>
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        @if($canEdit || $isPdot)
            <button type="submit" form="scores-form" class="grades-btn grades-btn-solid"><i class="bi bi-save"></i> Lưu điểm</button>
        @endif
        @if(auth()->user()->isInstructor() && (int)auth()->user()->instructor_id === (int)$book->instructor_id && $book->isEditableByInstructor())
            <form method="POST"
                  action="{{ route('grades.books.lock', $book) }}"
                  data-confirm="Khóa bảng và gửi PDOT duyệt?"
                  data-confirm-title="Khóa bảng điểm"
                  data-confirm-ok="Khóa & gửi"
                  data-turbo="false">
                @csrf
                <button class="grades-btn grades-btn-teal">Khóa & gửi duyệt</button>
            </form>
        @endif
        @if($isPdot && $book->status === 'pending_pdot')
            <form method="POST" action="{{ route('grades.books.approve', $book) }}" data-turbo="false">
                @csrf
                <button class="grades-btn grades-btn-solid">PDOT duyệt</button>
            </form>
        @endif
        @if($book->isLocked() || $book->status === 'approved')
            <button type="button" class="grades-btn grades-btn-ghost" onclick="document.getElementById('unlock-box').classList.toggle('hidden')">Ý kiến mở khóa</button>
        @endif
        @if(Route::has('grades.books.export'))
            <a href="{{ route('grades.books.export', $book) }}" class="grades-btn grades-btn-ghost" data-turbo="false"><i class="bi bi-download"></i> Xuất</a>
        @endif
        @if(Route::has('export-templates.portal.index'))
            <a href="{{ route('export-templates.portal.index', ['portal' => 'grades']) }}" class="grades-btn grades-btn-ghost">Mẫu xuất</a>
        @endif
    </div>
</div>

<div id="unlock-box" class="hidden grades-card p-4 mb-4">
    <form method="POST" action="{{ route('grades.books.request-unlock', $book) }}" class="space-y-2">
        @csrf
        <label class="text-sm font-medium">Lý do xin mở khóa (qua PDOT → Chủ nhiệm PDOT)</label>
        <textarea name="reason" rows="3" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" placeholder="Mô tả sai sót / đề nghị sửa..."></textarea>
        <button class="grades-btn grades-btn-teal">Gửi ý kiến</button>
    </form>
</div>

@if($requests->count())
    <div class="grades-card p-4 mb-4 text-sm space-y-3">
        <div class="font-semibold">Yêu cầu mở khóa</div>
        @foreach($requests as $r)
            <div class="border border-slate-100 rounded-lg p-3">
                <div class="text-xs text-slate-500">{{ $r->requester?->name }} · {{ $r->statusLabel() }} · {{ $r->created_at?->format('d/m H:i') }}</div>
                <p class="mt-1">{{ $r->reason }}</p>
                @if(in_array($r->status, ['pending', 'pdot_ok'], true) && ($isPdot || $isDirector))
                    <form method="POST" action="{{ route('grades.requests.review', $r) }}" class="mt-2 flex flex-wrap gap-2 items-end">
                        @csrf
                        <input type="text" name="note" placeholder="Ghi chú" class="border rounded-lg px-2 py-1 text-sm flex-1 min-w-[10rem]">
                        @if($r->status === 'pending' && $isPdot)
                            <button name="action" value="pdot_forward" class="grades-btn grades-btn-teal text-xs">PDOT → CN</button>
                        @endif
                        @if(($r->status === 'pdot_ok' || $r->status === 'pending') && $isDirector)
                            <button name="action" value="approve" class="grades-btn grades-btn-solid text-xs">CN duyệt mở</button>
                        @endif
                        <button name="action" value="reject" class="grades-btn grades-btn-ghost text-xs">Từ chối</button>
                    </form>
                @endif
            </div>
        @endforeach
    </div>
@endif

<form id="scores-form" method="POST" action="{{ route('grades.books.scores', $book) }}">
    @csrf
    <div class="grades-card overflow-x-auto">
        <table class="min-w-full text-sm grades-table">
            <thead class="bg-slate-50 border-b">
            <tr>
                <th class="px-3 py-2 text-left sticky left-0 bg-slate-50">Học viên</th>
                @foreach($book->columns as $col)
                    <th class="px-2 py-2 text-center">
                        {{ $col->name }}
                        @if($col->pdot_only)<div class="text-[10px] text-orange-700 font-bold">PDOT</div>@endif
                    </th>
                @endforeach
            </tr>
            </thead>
            <tbody class="divide-y">
            @forelse($students as $st)
                <tr>
                    <td class="px-3 py-2 sticky left-0 bg-white font-medium">
                        {{ $st->name }}
                        <div class="text-[11px] text-slate-400 font-normal">{{ $st->code }}</div>
                    </td>
                    @foreach($book->columns as $col)
                        @php
                            $cell = $cells[$st->id][$col->id] ?? null;
                            $editable = \Modules\Grades\Services\GradeAccess::canEditCell(auth()->user(), $book, $col);
                        @endphp
                        <td class="px-2 py-2 text-center">
                            @if($editable)
                                <input type="number" step="0.1" min="0" max="{{ $col->max_score }}"
                                       name="scores[{{ $col->id }}][{{ $st->id }}]"
                                       value="{{ $cell?->score }}">
                            @else
                                <span class="tabular-nums">{{ $cell?->score ?? '—' }}</span>
                            @endif
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ 1 + $book->columns->count() }}" class="px-4 py-10 text-center text-slate-500">
                    Lớp chưa có học viên (user_type=student + class_id).
                </td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</form>
@endsection
