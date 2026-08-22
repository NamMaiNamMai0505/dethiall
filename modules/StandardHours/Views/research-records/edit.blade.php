@extends('layouts.admin')

@section('title', 'Sửa kê khai NCKH')
@section('page-title', 'Sửa kê khai NCKH')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Kê khai NCKH', 'url' => route('standard-hours.research-records.index')],
    ['title' => 'Chỉnh sửa']
]" />

<x-page-header title="CHỈNH SỬA KÊ KHAI NCKH" :actions="[
    ['url' => route('standard-hours.research-records.index'), 'label' => 'Quay lại', 'icon' => 'arrow-left', 'color' => 'gray'],
    ['url' => route('standard-hours.research-records.show', $researchRecord), 'label' => 'Chi tiết', 'icon' => 'eye', 'color' => 'blue'],
]" />

<div class="bg-white rounded-lg shadow-sm p-6">
    <form action="{{ route('standard-hours.research-records.update', $researchRecord) }}" method="POST" enctype="multipart/form-data" data-turbo="false">
        @csrf @method('PUT')
        <div class="mx-auto max-w-5xl">@include('standardhours::research-records._form', ['researchRecord' => $researchRecord])</div>
        <div class="border-t mt-6 pt-6 text-right">
            <button type="submit" class="{{ \Modules\StandardHours\Support\ActionButton::classes('primary') }}">Cập nhật</button>
        </div>
    </form>
</div>
@endsection
