@extends('layouts.admin')
@section('title', $template->name)
@section('page-title', 'Chi tiết mẫu Dashboard')
@section('content')
@include('exporttemplates::partials.show-body', ['portal' => 'dashboard', 'template' => $template, 'portalLabel' => $portalLabel])
@endsection
