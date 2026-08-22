@extends('layouts.admin')

@section('title', 'Thêm HĐ chuyên môn')
@section('page-title', 'Thêm HĐ chuyên môn')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'HĐ chuyên môn', 'url' => route('standard-hours.conversion-categories.index')],
    ['title' => 'Thêm mới']
]" />

<x-page-header title="THÊM DANH MỤC HĐ CHUYÊN MÔN" :actions="[[
    'url' => route('standard-hours.conversion-categories.index'),
    'label' => 'Quay lại', 'icon' => 'arrow-left', 'color' => 'gray'
]]" />

<div class="bg-white rounded-lg shadow-sm p-6">
    <form action="{{ route('standard-hours.conversion-categories.store') }}" method="POST">
        @csrf
        <div class="max-w-3xl">@include('standardhours::conversion-categories._form')</div>
        <div class="border-t mt-6 pt-6 text-right">
            <button type="submit" class="btn btn-primary">Lưu</button>
        </div>
    </form>
</div>
@endsection