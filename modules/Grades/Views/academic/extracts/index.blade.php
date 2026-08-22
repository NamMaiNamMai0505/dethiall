@extends('layouts.grades')
@section('title', 'Trích ngang')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-3 mb-6">
    <div>
        <a href="{{ route('grades.academic.hub') }}" class="text-sm text-teal-700 font-semibold hover:underline">← Tổng kết · TN</a>
        <h1 class="text-2xl font-bold text-slate-900 mt-1">Danh sách trích ngang</h1>
    </div>
    <a href="{{ route('grades.academic.extracts.print', request()->only('class_id')) }}"
       class="grades-btn grades-btn-ghost" target="_blank" data-turbo="false">
        <i class="bi bi-printer"></i> In DS
    </a>
</div>

<form method="GET" class="mb-4">
    <label class="text-xs font-semibold text-slate-500">Lớp</label>
    <select name="class_id" class="border rounded-lg px-3 py-2 text-sm ml-2" onchange="this.form.submit()">
        <option value="">— Tất cả (theo quyền) —</option>
        @foreach($classes as $id => $name)
            <option value="{{ $id }}" @selected((int)$classId === (int)$id)>{{ $name }}</option>
        @endforeach
    </select>
</form>

<div class="grades-card overflow-hidden">
    <table class="min-w-full text-sm grades-table">
        <thead class="bg-slate-50 border-b">
        <tr>
            <th class="px-3 py-2 text-left">Họ tên</th>
            <th class="px-3 py-2 text-left">Mã</th>
            <th class="px-3 py-2 text-left">Lớp</th>
            <th class="px-3 py-2 text-left">SĐT / CCCD</th>
            <th class="px-3 py-2"></th>
        </tr>
        </thead>
        <tbody class="divide-y">
        @forelse($students as $s)
            @php $p = $profiles[$s->id] ?? null; @endphp
            <tr>
                <td class="px-3 py-2 font-medium">{{ $s->name }}</td>
                <td class="px-3 py-2 font-mono text-xs">{{ $p->student_code ?? $s->code }}</td>
                <td class="px-3 py-2">{{ $s->class?->name }}</td>
                <td class="px-3 py-2 text-xs text-slate-600">{{ $p->phone ?? '—' }} · {{ $p->id_number ?? '—' }}</td>
                <td class="px-3 py-2 text-right">
                    <a href="{{ route('grades.academic.extracts.edit', $s) }}" class="text-teal-700 font-semibold">
                        {{ $canEdit ? 'Sửa' : 'Xem' }}
                    </a>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">Không có học viên.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="p-3">{{ $students->links() }}</div>
</div>
@endsection
