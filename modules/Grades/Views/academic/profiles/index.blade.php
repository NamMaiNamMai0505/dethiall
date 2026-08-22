@extends('layouts.grades')
@section('title', 'Hồ sơ tốt nghiệp')

@section('content')
<div class="mb-6">
    <a href="{{ route('grades.academic.hub') }}" class="text-sm text-teal-700 font-semibold hover:underline">← Tổng kết · TN</a>
    <h1 class="text-2xl font-bold text-slate-900 mt-1">Đăng ký hồ sơ tốt nghiệp</h1>
</div>

@if($canEdit)
<div class="grades-card p-5 mb-6">
    <h2 class="font-semibold mb-3">Tạo hồ sơ mới</h2>
    <form method="POST" action="{{ route('grades.academic.profiles.store') }}" class="grid sm:grid-cols-2 gap-3">
        @csrf
        <div>
            <label class="text-xs font-semibold text-slate-500">Đợt xét</label>
            <select name="session_id" class="w-full border rounded-lg px-3 py-2 text-sm mt-1">
                <option value="">—</option>
                @foreach($sessions as $s)
                    <option value="{{ $s->id }}">{{ $s->academic_year }} · {{ $s->title }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Lớp</label>
            <select name="class_id" class="w-full border rounded-lg px-3 py-2 text-sm mt-1">
                <option value="">—</option>
                @foreach($classes as $id => $name)
                    <option value="{{ $id }}">{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Ngành</label>
            <input name="major_name" class="w-full border rounded-lg px-3 py-2 text-sm mt-1">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Khóa</label>
            <input name="cohort" class="w-full border rounded-lg px-3 py-2 text-sm mt-1">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Hệ</label>
            <input name="system_type" class="w-full border rounded-lg px-3 py-2 text-sm mt-1">
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Thời gian đào tạo</label>
            <input name="duration_text" class="w-full border rounded-lg px-3 py-2 text-sm mt-1">
        </div>
        <div class="sm:col-span-2">
            <button class="grades-btn grades-btn-solid">Tạo hồ sơ</button>
        </div>
    </form>
</div>
@endif

<div class="grades-card overflow-hidden">
    <table class="min-w-full text-sm grades-table">
        <thead class="bg-slate-50 border-b">
        <tr>
            <th class="px-3 py-2 text-left">Ngành / Khóa</th>
            <th class="px-3 py-2 text-left">Lớp</th>
            <th class="px-3 py-2 text-left">QĐ</th>
            <th class="px-3 py-2 text-left">TT</th>
            <th class="px-3 py-2"></th>
        </tr>
        </thead>
        <tbody class="divide-y">
        @forelse($profiles as $p)
            <tr>
                <td class="px-3 py-2">
                    <div class="font-medium">{{ $p->major_name ?: '—' }}</div>
                    <div class="text-xs text-slate-500">{{ $p->cohort }} · {{ $p->system_type }}</div>
                </td>
                <td class="px-3 py-2">{{ $p->classModel?->name ?? '—' }}</td>
                <td class="px-3 py-2 text-xs">{{ $p->decision_number ?: '—' }}</td>
                <td class="px-3 py-2"><span class="grades-chip grades-chip-wait">{{ $p->statusLabel() }}</span></td>
                <td class="px-3 py-2 text-right space-x-2">
                    <a href="{{ route('grades.academic.profiles.edit', $p) }}" class="text-teal-700 font-semibold">Chi tiết</a>
                    <a href="{{ route('grades.academic.profiles.print-score', $p) }}" class="text-slate-600" target="_blank" data-turbo="false">In phiếu</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">Chưa có hồ sơ.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="p-3">{{ $profiles->links() }}</div>
</div>
@endsection
