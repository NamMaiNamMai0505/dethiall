@extends('layouts.admin')

@section('title', 'Thêm danh mục NCKH')
@section('page-title', 'Thêm danh mục NCKH')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Danh mục NCKH', 'url' => route('standard-hours.research-categories.index')],
    ['title' => 'Thêm mới']
]" />

<x-page-header title="THÊM DANH MỤC NCKH" :actions="[[
    'url' => route('standard-hours.research-categories.index'), 'label' => 'Quay lại', 'icon' => 'arrow-left', 'color' => 'gray'
]]" />

<div class="bg-white rounded-lg shadow-sm p-6">
    <form action="{{ route('standard-hours.research-categories.store') }}" method="POST">
        @csrf
        <div class="max-w-3xl">@include('standardhours::research-categories._form')</div>
        <div class="border-t mt-6 pt-6 text-right"><button type="submit" class="btn btn-primary">Lưu</button></div>
    </form>
</div>
@endsection