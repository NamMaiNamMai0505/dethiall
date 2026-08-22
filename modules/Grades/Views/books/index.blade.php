@extends('layouts.grades')
@section('title', 'Bảng điểm')
@section('content')
<div class="flex flex-wrap justify-between gap-3 mb-4 items-end">
    <div>
        <a href="{{ route('grades.hub') }}" class="text-sm text-teal-700 font-semibold hover:underline">← Hub điểm</a>
        <h1 class="text-xl font-bold mt-1">
            @if($currentSubject && $currentClass)
                {{ $currentSubject->name }} · {{ $currentClass->name }}
            @elseif($currentSubject)
                Môn {{ $currentSubject->name }}
            @else
                Tất cả bảng điểm
            @endif
        </h1>
    </div>
    <a href="{{ route('grades.books.create', array_filter(['subject_id' => $subjectId, 'class_id' => $classId])) }}"
       class="grades-btn grades-btn-solid">+ Tạo bảng</a>
</div>

<form method="GET" action="{{ route('grades.books.index') }}" class="mb-4 grid sm:grid-cols-2 gap-3">
    <div>
        <label class="block text-xs font-semibold text-slate-500 mb-1">Môn</label>
        <select name="subject_id" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()">
            <option value="">— Tất cả môn —</option>
            @foreach($subjects as $s)
                <option value="{{ $s->id }}" @selected((int)$subjectId === (int)$s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-semibold text-slate-500 mb-1">Lớp</label>
        <select name="class_id" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm" onchange="this.form.submit()" @disabled(!$subjectId)>
            <option value="">— {{ $subjectId ? 'Tất cả lớp' : 'Chọn môn trước' }} —</option>
            @foreach($classes as $c)
                <option value="{{ $c->id }}" @selected((int)$classId === (int)$c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
</form>

<div class="grades-card overflow-hidden">
    <table class="min-w-full text-sm grades-table">
        <thead class="bg-slate-50 border-b">
        <tr>
            <th class="px-3 py-2 text-left">Tiêu đề</th>
            <th class="px-3 py-2 text-left">Lớp / Môn</th>
            <th class="px-3 py-2 text-left">GV</th>
            <th class="px-3 py-2 text-left">TT</th>
            <th class="px-3 py-2"></th>
        </tr>
        </thead>
        <tbody class="divide-y">
        @forelse($books as $b)
            <tr class="hover:bg-orange-50/40">
                <td class="px-3 py-2 font-medium">{{ $b->title }}</td>
                <td class="px-3 py-2 text-slate-600">{{ $b->classModel?->name }} · {{ $b->subject?->name }}</td>
                <td class="px-3 py-2">{{ $b->instructor?->name ?? '—' }}</td>
                <td class="px-3 py-2"><span class="grades-chip grades-chip-wait">{{ $b->statusLabel() }}</span></td>
                <td class="px-3 py-2 text-right"><a class="text-teal-700 font-semibold" href="{{ route('grades.books.show', $b) }}">Mở</a></td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500 text-sm">Chưa có bảng điểm.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="p-3">{{ $books->links() }}</div>
</div>
@endsection
