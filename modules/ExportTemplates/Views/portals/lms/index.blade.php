@extends('layouts.lms-learner')
@section('title', 'Mẫu xuất LMS')
@section('content')
<div class="max-w-4xl mx-auto px-4 py-6">
    <div class="flex flex-wrap items-center justify-between gap-3 mb-4">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Mẫu xuất · LMS</h1>
            <p class="text-sm text-slate-500 mt-1">Upload và quản lý mẫu chỉ cho cổng học / LMS.</p>
        </div>
        <div class="flex gap-2">
            @can('export-templates.create')
                <a href="{{ route('export-templates.portal.builder.create', ['portal' => 'lms']) }}"
                   class="inline-flex items-center gap-1 px-3 py-2 rounded-lg border border-teal-300 text-teal-700 text-sm font-semibold">Tạo bằng Builder</a>
                <a href="{{ route('export-templates.portal.create', ['portal' => 'lms']) }}"
                   class="inline-flex items-center gap-1 px-3 py-2 rounded-lg bg-teal-600 text-white text-sm font-semibold">Tải mẫu LMS</a>
            @endcan
            <a href="{{ route('lms.learn.home') }}" class="inline-flex items-center gap-1 px-3 py-2 rounded-lg border text-sm">← LMS</a>
        </div>
    </div>
    @include('exporttemplates::partials.table', ['portal' => 'lms'])
</div>
@endsection
