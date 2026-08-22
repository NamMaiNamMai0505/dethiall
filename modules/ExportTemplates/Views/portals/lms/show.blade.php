@extends('layouts.lms-learner')
@section('title', $template->name)
@section('content')
<div class="max-w-6xl mx-auto px-4 py-6">
    @include('exporttemplates::partials.show-body', ['portal' => 'lms', 'template' => $template, 'portalLabel' => $portalLabel])
</div>
@endsection
