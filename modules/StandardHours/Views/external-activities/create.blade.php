@extends('layouts.admin')

@section('title', 'Kê khai hoạt động ngoài HĐCM')
@section('page-title', 'Kê khai hoạt động ngoài HĐCM')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Giờ chuẩn GV', 'url' => route('standard-hours.hub')],
    ['title' => 'Hoạt động ngoài HĐCM', 'url' => route('standard-hours.external-activities.index')],
    ['title' => 'Thêm kê khai'],
]" />

<x-page-header
    title="THÊM HOẠT ĐỘNG NGOÀI HĐCM"
    subtitle="Ghi nhận hoạt động ngoài danh mục quy đổi giờ chuẩn để đơn vị quản lý theo dõi và duyệt"
    :actions="[\Modules\StandardHours\Support\HubNavigation::backAction()]" />

<form method="POST" action="{{ route('standard-hours.external-activities.store') }}"
      enctype="multipart/form-data" data-turbo="false"
      class="rounded-xl border bg-white p-5 shadow-sm">
    @csrf
    @include('standardhours::external-activities._form')

    <div class="mt-6 flex flex-wrap justify-end gap-2 border-t pt-5">
        <a href="{{ route('standard-hours.external-activities.index') }}"
           class="{{ \Modules\StandardHours\Support\ActionButton::classes('secondary') }}">Hủy</a>
        <button type="submit" class="{{ \Modules\StandardHours\Support\ActionButton::classes('primary') }}">
            <i class="bi bi-save"></i> Lưu bản nháp
        </button>
    </div>
</form>
@endsection
