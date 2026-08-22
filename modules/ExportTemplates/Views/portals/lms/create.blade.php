@extends('layouts.lms-learner')
@section('title', 'Tải mẫu LMS')
@section('content')
<div class="max-w-xl mx-auto px-4 py-6">
    <a href="{{ route('export-templates.portal.index', ['portal' => 'lms']) }}" class="text-sm text-teal-700 font-semibold">← Mẫu LMS</a>
    <h1 class="text-xl font-bold mt-2 mb-4">Tải mẫu xuất LMS</h1>
    @include('exporttemplates::partials.form', ['portal' => 'lms', 'portalLabel' => $portalLabel, 'featureHints' => $featureHints])
</div>
@endsection
