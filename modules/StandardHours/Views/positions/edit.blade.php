@extends('layouts.admin')

@section('title', 'Chỉnh sửa chức danh')
@section('page-title', 'Chỉnh sửa chức danh')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Chức danh', 'url' => route('standard-hours.positions.index')],
    ['title' => 'Chỉnh sửa']
]" />

<x-page-header
    title="CHỈNH SỬA CHỨC DANH"
    :actions="[
        [
            'url' => route('standard-hours.positions.show', $position),
            'label' => 'Xem chi tiết',
            'icon' => 'eye',
            'color' => 'gray'
        ],
        [
            'url' => route('standard-hours.positions.index'),
            'label' => 'Quay lại danh sách',
            'icon' => 'arrow-left',
            'color' => 'gray'
        ]
    ]" />

<div class="bg-white rounded-lg shadow-sm p-6">
    <form action="{{ route('standard-hours.positions.update', $position) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="max-w-3xl">
            @include('standardhours::positions._form', ['position' => $position])
        </div>

        <div class="border-t border-gray-200 mt-6 pt-6 text-right">
            <button type="submit" class="btn btn-primary min-w-[100px]">
                Cập nhật
            </button>
        </div>
    </form>
</div>
@endsection