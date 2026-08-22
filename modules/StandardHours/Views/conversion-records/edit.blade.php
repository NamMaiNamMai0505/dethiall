@extends('layouts.admin')

@section('title', 'Sửa kê khai HĐ CM')
@section('page-title', 'Sửa kê khai HĐ CM')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Kê khai HĐ CM', 'url' => route('standard-hours.conversion-records.index')],
    ['title' => 'Chỉnh sửa']
]" />

<x-page-header title="CHỈNH SỬA KÊ KHAI" :actions="[
    ['url' => route('standard-hours.conversion-records.index'), 'label' => 'Quay lại', 'icon' => 'arrow-left', 'color' => 'gray'],
    ['url' => route('standard-hours.conversion-records.show', $conversionRecord), 'label' => 'Chi tiết', 'icon' => 'eye', 'color' => 'blue'],
]" />

<div class="bg-white rounded-lg shadow-sm p-6">
    <form action="{{ route('standard-hours.conversion-records.update', $conversionRecord) }}" method="POST"
          enctype="multipart/form-data" data-conversion-record-form>
        @csrf @method('PUT')
        <div class="max-w-3xl">@include('standardhours::conversion-records._form', ['conversionRecord' => $conversionRecord])</div>
        <div class="border-t mt-6 pt-6 text-right">
            <button type="submit" class="{{ \Modules\StandardHours\Support\ActionButton::classes('primary') }}">Cập nhật</button>
        </div>
    </form>
</div>
@endsection
