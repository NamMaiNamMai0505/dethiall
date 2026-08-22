@extends('layouts.admin')
@section('title', 'Tải mẫu Dashboard')
@section('page-title', 'Tải mẫu · Dashboard')
@section('content')
<x-page-header title="TẢI MẪU DASHBOARD" :actions="[
    ['url'=>route('export-templates.portal.index',['portal'=>'dashboard']),'label'=>'Quay lại','icon'=>'arrow-left','color'=>'gray'],
]" />
@include('exporttemplates::partials.form', ['portal' => 'dashboard', 'portalLabel' => $portalLabel, 'featureHints' => $featureHints])
@endsection
