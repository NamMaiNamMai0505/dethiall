@extends('layouts.admin')
@section('title', 'Mẫu xuất Dashboard')
@section('page-title', 'Mẫu xuất · Dashboard')
@section('content')
<x-breadcrumb :items="[['title'=>'Dashboard','url'=>route('dashboard')],['title'=>'Mẫu xuất Dashboard']]" />
<x-page-header title="MẪU XUẤT · DASHBOARD" :actions="[
    ['url'=>route('export-templates.portal.create',['portal'=>'dashboard']),'label'=>'Tải mẫu Dashboard','icon'=>'upload','color'=>'blue'],
    ['url'=>route('export-templates.portal.builder.create',['portal'=>'dashboard']),'label'=>'Tạo bằng Builder','icon'=>'plus','color'=>'teal'],
    ['url'=>route('dashboard'),'label'=>'Về Dashboard','icon'=>'arrow-left','color'=>'gray'],
]" />

<p class="text-sm text-slate-600 mb-4">
    Chỉ quản lý mẫu phạm vi <strong>Dashboard</strong>. Upload tại đây; không lẫn với LMS / Điểm.
</p>

@include('exporttemplates::partials.table', ['portal' => 'dashboard'])
@endsection
