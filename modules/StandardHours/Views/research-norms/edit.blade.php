@extends('layouts.admin')

@section('title', 'Chỉnh sửa định mức NCKH')
@section('page-title', 'Chỉnh sửa định mức NCKH')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Định mức NCKH', 'url' => route('standard-hours.research-norms.index')],
    ['title' => 'Chỉnh sửa']
]" />

<x-page-header
    title="CHỈNH SỬA ĐỊNH MỨC NCKH"
    :actions="[
        ['url' => route('standard-hours.research-norms.show', $researchNorm), 'label' => 'Xem chi tiết', 'icon' => 'eye', 'color' => 'gray'],
        ['url' => route('standard-hours.research-norms.index'), 'label' => 'Quay lại', 'icon' => 'arrow-left', 'color' => 'gray']
    ]" />

<div class="bg-white rounded-lg shadow-sm p-6">
    <form action="{{ route('standard-hours.research-norms.update', $researchNorm) }}" method="POST">
        @csrf
        @method('PUT')
        <div class="max-w-3xl">
            @include('standardhours::research-norms._form', ['researchNorm' => $researchNorm])
        </div>
        <div class="border-t border-gray-200 mt-6 pt-6 text-right">
            <button type="submit" class="btn btn-primary min-w-[100px]">Cập nhật</button>
        </div>
    </form>
</div>
@endsection