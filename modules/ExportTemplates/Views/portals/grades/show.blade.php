@extends('layouts.grades')
@section('title', $template->name)
@section('content')
@include('exporttemplates::partials.show-body', ['portal' => 'grades', 'template' => $template, 'portalLabel' => $portalLabel])
@endsection
