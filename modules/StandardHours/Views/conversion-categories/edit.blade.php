@extends('layouts.admin')

@section('title', 'Sửa HĐ chuyên môn')
@section('page-title', 'Sửa HĐ chuyên môn')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'HĐ chuyên môn', 'url' => route('standard-hours.conversion-categories.index')],
    ['title' => 'Chỉnh sửa']
]" />

<x-page-header title="CHỈNH SỬA DANH MỤC" :actions="[
    ['url' => route('standard-hours.conversion-categories.show', $conversionCategory), 'label' => 'Chi tiết', 'icon' => 'eye', 'color' => 'gray'],
    ['url' => route('standard-hours.conversion-categories.index'), 'label' => 'Quay lại', 'icon' => 'arrow-left', 'color' => 'gray']
]" />

<div class="bg-white rounded-lg shadow-sm p-6">
    <form action="{{ route('standard-hours.conversion-categories.update', $conversionCategory) }}" method="POST">
        @csrf @method('PUT')
        <div class="max-w-3xl">@include('standardhours::conversion-categories._form', ['conversionCategory' => $conversionCategory])</div>
        <div class="border-t mt-6 pt-6 text-right">
            <button type="submit" class="btn btn-primary">Cập nhật</button>
        </div>
    </form>
</div>
@endsection