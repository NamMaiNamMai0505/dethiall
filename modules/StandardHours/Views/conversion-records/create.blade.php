@extends('layouts.admin')

@section('title', 'Thêm kê khai HĐ CM')
@section('page-title', 'Thêm kê khai HĐ CM')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Kê khai HĐ CM', 'url' => route('standard-hours.conversion-records.index')],
    ['title' => 'Thêm mới']
]" />

<x-page-header title="THÊM KÊ KHAI HĐ CHUYÊN MÔN" :actions="[[
    'url' => route('standard-hours.conversion-records.index'), 'label' => 'Quay lại', 'icon' => 'arrow-left', 'color' => 'gray'
]]" />

<div class="bg-white rounded-lg shadow-sm p-6">
    <form action="{{ route('standard-hours.conversion-records.store') }}" method="POST"
          enctype="multipart/form-data" data-conversion-record-form>
        @csrf
        <div class="max-w-3xl">@include('standardhours::conversion-records._form')</div>
        <div class="border-t mt-6 pt-6 flex justify-end gap-3">
            <button type="submit" name="status" value="draft" class="{{ \Modules\StandardHours\Support\ActionButton::classes('secondary') }}">Lưu nháp</button>
            <button type="submit" name="status" value="submitted" class="{{ \Modules\StandardHours\Support\ActionButton::classes('primary') }}">Lưu & Gửi duyệt</button>
        </div>
    </form>
</div>
@endsection
