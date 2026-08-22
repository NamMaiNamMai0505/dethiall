@extends('layouts.admin')

@section('title', 'Chỉnh sửa định mức giờ chuẩn')
@section('page-title', 'Chỉnh sửa định mức giờ chuẩn')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Định mức giờ chuẩn', 'url' => route('standard-hours.hour-norms.index')],
    ['title' => 'Chỉnh sửa']
]" />

<x-page-header
    title="CHỈNH SỬA ĐỊNH MỨC GIỜ CHUẨN"
    :actions="[
        ['url' => route('standard-hours.hour-norms.show', $hourNorm), 'label' => 'Xem chi tiết', 'icon' => 'eye', 'color' => 'gray'],
        ['url' => route('standard-hours.hour-norms.index'), 'label' => 'Quay lại', 'icon' => 'arrow-left', 'color' => 'gray']
    ]" />

<div class="bg-white rounded-lg shadow-sm p-6">
    <form action="{{ route('standard-hours.hour-norms.update', $hourNorm) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="max-w-3xl">
            @include('standardhours::hour-norms._form', ['hourNorm' => $hourNorm])
        </div>
        <div class="border-t border-gray-200 mt-6 pt-6 text-right">
            <button type="submit" class="btn btn-primary min-w-[100px]">Cập nhật</button>
        </div>
    </form>
</div>
@endsection