@extends('layouts.grades')
@section('title', 'Mẫu xuất · Điểm')
@section('content')
<div class="flex flex-wrap items-center justify-between gap-3 mb-4">
    <div>
        <h1 class="text-xl font-bold text-slate-900">Mẫu xuất · Quản lý điểm</h1>
        <p class="text-sm text-slate-500 mt-1">Chỉ mẫu phạm vi điểm (bảng điểm, bảng tổng hợp…).</p>
    </div>
    <div class="flex gap-2">
        @if(auth()->user()?->can('export-templates.create') || auth()->user()?->isSuperAdmin() || auth()->user()?->isManager())
            <a href="{{ route('export-templates.portal.builder.create', ['portal' => 'grades']) }}" class="grades-btn grades-btn-ghost">Tạo bằng Builder</a>
            <a href="{{ route('export-templates.portal.create', ['portal' => 'grades']) }}" class="grades-btn grades-btn-solid">Tải mẫu điểm</a>
        @endif
        <a href="{{ route('grades.hub') }}" class="grades-btn grades-btn-ghost">← Hub điểm</a>
    </div>
</div>
@include('exporttemplates::partials.table', ['portal' => 'grades'])
@endsection
