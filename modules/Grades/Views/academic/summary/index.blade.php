@extends('layouts.grades')
@section('title', 'Tổng kết · Xét TN')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-3 mb-6">
    <div>
        <a href="{{ route('grades.academic.hub') }}" class="text-sm text-teal-700 font-semibold hover:underline">← Tổng kết · TN</a>
        <h1 class="text-2xl font-bold text-slate-900 mt-1">Tổng kết khóa học · Xét tốt nghiệp</h1>
        <p class="text-sm text-slate-500 mt-1">Chọn / tạo đợt năm học · xét TN</p>
    </div>
</div>

@if($canEdit)
<div class="grades-card p-5 mb-6">
    <h2 class="font-semibold text-slate-800 mb-3">Tạo đợt mới</h2>
    <form method="POST" action="{{ route('grades.academic.summary.sessions.store') }}" class="grid sm:grid-cols-2 gap-3">
        @csrf
        <div>
            <label class="text-xs font-semibold text-slate-500">Năm học *</label>
            <x-academic-year-select
                name="academic_year"
                :selected="old('academic_year', \App\Support\AcademicYearCatalog::currentCode())"
                required
                class="mt-1 text-sm"
            />
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Loại *</label>
            <select name="session_type" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm mt-1">
                <option value="graduation">Xét tốt nghiệp</option>
                <option value="year_end">Tổng kết năm học</option>
            </select>
        </div>
        <div class="sm:col-span-2">
            <label class="text-xs font-semibold text-slate-500">Tiêu đề *</label>
            <input name="title" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm mt-1" placeholder="Xét TN đợt 1 năm 2025">
        </div>
        <div class="sm:col-span-2">
            <button class="grades-btn grades-btn-solid">Tạo đợt</button>
        </div>
    </form>
</div>
@endif

<div class="grades-card overflow-hidden">
    <table class="min-w-full text-sm grades-table">
        <thead class="bg-slate-50 border-b">
        <tr>
            <th class="px-3 py-2 text-left">Năm học</th>
            <th class="px-3 py-2 text-left">Tiêu đề</th>
            <th class="px-3 py-2 text-left">Loại</th>
            <th class="px-3 py-2 text-left">TT</th>
            <th class="px-3 py-2"></th>
        </tr>
        </thead>
        <tbody class="divide-y">
        @forelse($sessions as $s)
            <tr class="hover:bg-orange-50/40">
                <td class="px-3 py-2 font-mono text-xs">{{ $s->academic_year }}</td>
                <td class="px-3 py-2 font-medium">{{ $s->title }}</td>
                <td class="px-3 py-2">{{ $s->session_type === 'graduation' ? 'Xét TN' : 'Năm học' }}</td>
                <td class="px-3 py-2"><span class="grades-chip grades-chip-wait">{{ $s->statusLabel() }}</span></td>
                <td class="px-3 py-2 text-right">
                    <a href="{{ route('grades.academic.summary.show', $s) }}" class="text-teal-700 font-semibold">Mở</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">Chưa có đợt.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="p-3">{{ $sessions->links() }}</div>
</div>
@endsection
