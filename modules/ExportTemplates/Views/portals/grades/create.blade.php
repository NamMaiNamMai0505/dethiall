@extends('layouts.grades')
@section('title', 'Tải mẫu điểm')
@section('content')
<div class="max-w-lg mx-auto">
    <a href="{{ route('export-templates.portal.index', ['portal' => 'grades']) }}" class="text-sm text-teal-700 font-semibold">← Mẫu điểm</a>
    <h1 class="text-xl font-bold mt-2 mb-4">Tải mẫu xuất điểm</h1>
    @include('exporttemplates::partials.form', ['portal' => 'grades', 'portalLabel' => $portalLabel, 'featureHints' => $featureHints])
</div>
@endsection
