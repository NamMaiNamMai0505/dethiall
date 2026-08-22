@extends('layouts.admin')

@section('title', 'Sửa danh mục NCKH')
@section('page-title', 'Sửa danh mục NCKH')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Danh mục NCKH', 'url' => route('standard-hours.research-categories.index')],
    ['title' => 'Chỉnh sửa']
]" />

<x-page-header title="CHỈNH SỬA DANH MỤC NCKH" :actions="[
    ['url' => route('standard-hours.research-categories.show', $researchCategory), 'label' => 'Chi tiết', 'icon' => 'eye', 'color' => 'gray'],
    ['url' => route('standard-hours.research-categories.index'), 'label' => 'Quay lại', 'icon' => 'arrow-left', 'color' => 'gray']
]" />

<div class="bg-white rounded-lg shadow-sm p-6">
    <form action="{{ route('standard-hours.research-categories.update', $researchCategory) }}" method="POST">
        @csrf @method('PUT')
        <div class="max-w-3xl">@include('standardhours::research-categories._form', ['researchCategory' => $researchCategory])</div>
        <div class="border-t mt-6 pt-6 text-right"><button type="submit" class="btn btn-primary">Cập nhật</button></div>
    </form>
</div>
@endsection