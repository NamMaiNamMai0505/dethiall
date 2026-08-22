@extends('layouts.admin')

@section('title', 'Thêm kê khai NCKH')
@section('page-title', 'Thêm kê khai NCKH')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Kê khai NCKH', 'url' => route('standard-hours.research-records.index')],
    ['title' => 'Thêm mới']
]" />

<x-page-header title="THÊM KÊ KHAI NCKH" :actions="[[
    'url' => route('standard-hours.research-records.index'), 'label' => 'Quay lại', 'icon' => 'arrow-left', 'color' => 'gray'
]]" />

<div class="bg-white rounded-lg shadow-sm p-6">
    <form action="{{ route('standard-hours.research-records.store') }}" method="POST" enctype="multipart/form-data" data-turbo="false">
        @csrf
        <div class="mx-auto max-w-5xl">@include('standardhours::research-records._form')</div>
        <div class="border-t mt-6 pt-6 flex justify-end gap-3">
            <button type="submit" name="status" value="draft" class="{{ \Modules\StandardHours\Support\ActionButton::classes('secondary') }}">Lưu nháp</button>
            <button type="submit" name="status" value="submitted" class="{{ \Modules\StandardHours\Support\ActionButton::classes('primary') }}">Lưu & Gửi duyệt</button>
        </div>
    </form>
</div>
@endsection
