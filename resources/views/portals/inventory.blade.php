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
        ['route'=>'inventory.index','permission'=>'inventory.access.index','label'=>'Tổng quan vật tư','desc'=>'Chỉ số, lối tắt và trạng thái chung','icon'=>'bi-grid-1x2'],
        ['route'=>'inventory.buildings','permission'=>'inventory.locations.index','label'=>'Tòa nhà','desc'=>'Danh sách khu nhà và không gian quản lý','icon'=>'bi-building'],
        ['route'=>'inventory.classrooms','permission'=>'inventory.locations.index','label'=>'Phòng','desc'=>'Theo dõi phòng, ảnh và vật tư lắp đặt','icon'=>'bi-door-open'],
        ['route'=>'inventory.category','permission'=>'inventory.categories.index','label'=>'Danh mục vật tư','desc'=>'Cây ngành, loại và vật tư; bấm từng mục để xem chi tiết','icon'=>'bi-diagram-3'],
        ['route'=>'inventory.warehouse','permission'=>'inventory.warehouses.index','label'=>'Kho vật tư','desc'=>'Theo dõi kho và số lượng tồn','icon'=>'bi-boxes'],
        ['route'=>'inventory.assets','permission'=>'inventory.assets.index','label'=>'Cập nhật vật tư','desc'=>'Ghi nhận tăng, giảm và điều chỉnh tài sản','icon'=>'bi-pencil-square'],
        ['route'=>'inventory.proposals','permission'=>'inventory.proposals.index','label'=>'Đề xuất / Thanh lý','desc'=>'Mua sắm, sửa chữa, thay thế, thanh lý','icon'=>'bi-send'],
        ['route'=>'inventory.proposals.approval','permission'=>'inventory.proposals.approve','label'=>'Duyệt đề xuất','desc'=>'Xem xét, phê duyệt và in phiếu đề xuất','icon'=>'bi-check2-square'],
        ['route'=>'inventory.repairs','permission'=>'inventory.repairs.index','label'=>'Phân công sửa chữa','desc'=>'Tiếp nhận hỏng hóc và xử lý sửa chữa','icon'=>'bi-tools'],
        ['route'=>'inventory.transfers','permission'=>'inventory.transfers.index','label'=>'Điều động & thu hồi','desc'=>'Quản lý tài sản theo phòng và biên bản','icon'=>'bi-arrow-left-right'],
        ['route'=>'inventory.search','permission'=>'inventory.search.index','label'=>'Tìm kiếm','desc'=>'Tra cứu nhanh vật tư, tài sản, vị trí','icon'=>'bi-search'],
        ['route'=>'inventory.logs','permission'=>'inventory.logs.index','label'=>'Nhật ký vật tư','desc'=>'Lịch sử thao tác, nhập xuất và hỏng hóc','icon'=>'bi-clock-history'],
        ['route'=>'inventory.reports','permission'=>'inventory.reports.index','label'=>'Báo cáo vật tư','desc'=>'Tổng hợp và xuất báo cáo','icon'=>'bi-bar-chart'],
        ['route'=>'inventory.movement-report','permission'=>'inventory.reports.index','label'=>'Xuất báo cáo','desc'=>'Báo cáo di chuyển và đồng bộ vị trí','icon'=>'bi-file-earmark-arrow-down'],
        ['route'=>'inventory.templates','permission'=>'inventory.templates.index','label'=>'Mẫu báo cáo Word','desc'=>'Quản lý mẫu và biến báo cáo','icon'=>'bi-file-earmark-word'],
    ] as $item)
        @continue(! \App\Support\PermissionCheck::can(auth()->user(), $item['permission']))
        <a href="{{ route($item['route']) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-300 hover:shadow-md">
            <i class="{{ $item['icon'] }} text-2xl text-blue-700"></i><h2 class="mt-3 font-extrabold">{{ $item['label'] }}</h2><p class="mt-1 text-sm text-slate-500">{{ $item['desc'] }}</p>
        </a>
    @endforeach
</div>
@endsection
