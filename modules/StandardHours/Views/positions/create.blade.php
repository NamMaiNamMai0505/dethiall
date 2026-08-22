@extends('layouts.admin')

@section('title', 'Thêm chức danh')
@section('page-title', 'Thêm chức danh')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Chức danh', 'url' => route('standard-hours.positions.index')],
    ['title' => 'Thêm mới']
]" />

<x-page-header
    title="THÊM CHỨC DANH"
    :actions="[
        [
            'url' => route('standard-hours.positions.index'),
            'label' => 'Quay lại danh sách',
            'icon' => 'arrow-left',
            'color' => 'gray'
        ]
    ]" />

<div class="bg-white rounded-lg shadow-sm p-6">
    <form action="{{ route('standard-hours.positions.store') }}" method="POST">
        @csrf

        <div class="max-w-3xl">
            @include('standardhours::positions._form')
        </div>

        <div class="border-t border-gray-200 mt-6 pt-6 text-right">
            <button type="submit" class="btn btn-primary min-w-[100px]">
                Lưu
            </button>
        </div>
    </form>
</div>
@endsection