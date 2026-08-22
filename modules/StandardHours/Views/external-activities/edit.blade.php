@extends('layouts.admin')

@section('title', 'Chỉnh sửa hoạt động ngoài HĐCM')
@section('page-title', 'Chỉnh sửa hoạt động ngoài HĐCM')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Giờ chuẩn GV', 'url' => route('standard-hours.hub')],
    ['title' => 'Hoạt động ngoài HĐCM', 'url' => route('standard-hours.external-activities.index')],
    ['title' => 'Chỉnh sửa'],
]" />

<x-page-header title="CHỈNH SỬA HOẠT ĐỘNG NGOÀI HĐCM" :actions="[
    ['url' => route('standard-hours.external-activities.show', $record), 'label' => 'Xem chi tiết', 'icon' => 'eye', 'color' => 'gray'],
]" />

<form method="POST" action="{{ route('standard-hours.external-activities.update', $record) }}"
      enctype="multipart/form-data" data-turbo="false"
      class="rounded-xl border bg-white p-5 shadow-sm">
    @csrf
    @method('PUT')
    @include('standardhours::external-activities._form')

    <div class="mt-6 flex flex-wrap justify-end gap-2 border-t pt-5">
        <a href="{{ route('standard-hours.external-activities.show', $record) }}"
           class="{{ \Modules\StandardHours\Support\ActionButton::classes('secondary') }}">Hủy</a>
        <button type="submit" class="{{ \Modules\StandardHours\Support\ActionButton::classes('primary') }}">
            <i class="bi bi-save"></i> Lưu thay đổi
        </button>
    </div>
</form>
@endsection
