@extends('layouts.admin')

@section('title', 'Thêm định mức giờ chuẩn')
@section('page-title', 'Thêm định mức giờ chuẩn')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Định mức giờ chuẩn', 'url' => route('standard-hours.hour-norms.index')],
    ['title' => 'Thêm mới']
]" />

<x-page-header
    title="THÊM ĐỊNH MỨC GIỜ CHUẨN"
    :actions="[[
        'url' => route('standard-hours.hour-norms.index'),
        'label' => 'Quay lại danh sách',
        'icon' => 'arrow-left',
        'color' => 'gray'
    ]]" />

<div class="bg-white rounded-lg shadow-sm p-6">
    <form action="{{ route('standard-hours.hour-norms.store') }}" method="POST">
        @csrf
        <div class="max-w-3xl">
            @include('standardhours::hour-norms._form')
        </div>
        <div class="border-t border-gray-200 mt-6 pt-6 text-right">
            <button type="submit" class="btn btn-primary min-w-[100px]">Lưu</button>
        </div>
    </form>
</div>
@endsection