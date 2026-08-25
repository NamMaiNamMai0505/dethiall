@extends('layouts.module-portal')
@php($portalHome = route('inventory.portal'))
@php($portalTitle = 'Cổng quản lý vật tư')
@php($portalIcon = 'bi-box-seam')
@section('title', 'Cổng quản lý vật tư')
@section('content')
<div class="mb-6 rounded-2xl bg-gradient-to-r from-blue-950 to-blue-700 p-7 text-white">
    <p class="text-sm font-semibold uppercase tracking-widest text-blue-200">Cổng nghiệp vụ</p>
    <h1 class="mt-2 text-3xl font-extrabold">Quản lý vật tư</h1>
    <p class="mt-2 max-w-2xl text-blue-100">Quản lý danh mục, kho, điều động, sửa chữa và báo cáo vật tư trong một không gian riêng.</p>
</div>
<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
    @foreach([
        ['route'=>'inventory.portal','label'=>'Tổng quan vật tư','desc'=>'Danh mục và số lượng tồn','icon'=>'bi-grid-1x2'],
        ['route'=>'inventory.warehouse','label'=>'Kho vật tư','desc'=>'Theo dõi kho và tồn kho','icon'=>'bi-boxes'],
        ['route'=>'inventory.proposals','label'=>'Đề xuất nghiệp vụ','desc'=>'Mua sắm, sửa chữa, thanh lý','icon'=>'bi-send'],
        ['route'=>'inventory.transfers','label'=>'Điều động & thu hồi','desc'=>'Quản lý tài sản theo phòng','icon'=>'bi-arrow-left-right'],
        ['route'=>'inventory.reports','label'=>'Báo cáo vật tư','desc'=>'Tổng hợp và xuất báo cáo','icon'=>'bi-bar-chart'],
        ['route'=>'inventory.search','label'=>'Tìm kiếm','desc'=>'Tra cứu nhanh vật tư, tài sản','icon'=>'bi-search'],
    ] as $item)
        <a href="{{ route($item['route']) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-md">
            <i class="{{ $item['icon'] }} text-2xl text-blue-700"></i><h2 class="mt-3 font-extrabold">{{ $item['label'] }}</h2><p class="mt-1 text-sm text-slate-500">{{ $item['desc'] }}</p>
        </a>
    @endforeach
</div>
@endsection
