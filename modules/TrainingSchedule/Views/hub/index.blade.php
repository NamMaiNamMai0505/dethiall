@extends('layouts.admin')

@section('title', 'Lịch đào tạo')
@section('page-title', 'Lịch đào tạo')

@section('content')
    <x-breadcrumb :items="[
        ['title' => 'Trang chủ'],
        ['title' => 'Lịch đào tạo']
    ]"/>

    <x-page-header
        title="LỊCH ĐÀO TẠO"
        subtitle="Một khung phân chia tiết — PDOT xếp môn/loại/phòng · Khoa gán bài/GV (theo tài khoản)"
    />

    <div class="mb-6 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 flex flex-wrap items-center gap-2">
        <span class="font-semibold text-slate-900">Phạm vi tài khoản:</span>
        <span class="inline-flex items-center rounded-full bg-slate-100 px-3 py-1 text-slate-800">{{ $scopeLabel ?? '—' }}</span>
        @if(!empty($user?->unit?->name))
            <span class="text-slate-500">Đơn vị: <strong>{{ $user->unit->name }}</strong>@if($user->unit->code) ({{ $user->unit->code }})@endif</span>
        @endif
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
        <div class="bg-white rounded-xl shadow-sm border p-5 border-l-4 border-blue-500">
            <p class="text-sm text-gray-600">Tổng lịch</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['total'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-5 border-l-4 border-green-500">
            <p class="text-sm text-gray-600">Đang hoạt động</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['active'] }}</p>
        </div>
        <div class="bg-white rounded-xl shadow-sm border p-5 border-l-4 border-gray-400">
            <p class="text-sm text-gray-600">Tạm dừng</p>
            <p class="text-3xl font-bold text-gray-900 mt-1">{{ $stats['inactive'] }}</p>
        </div>
    </div>

    <div class="mb-4">
        <h2 class="text-lg font-semibold text-gray-900">Chức năng</h2>
        <p class="text-sm text-gray-500">
            PDOT và Khoa vào <strong>cùng một chỗ</strong> để phân chia tiết; form chỉ mở field đúng quyền tài khoản.
        </p>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-10">
        @foreach($menuItems as $item)
            @if(auth()->user()->can($item['perm']))
                <a href="{{ route($item['route']) }}"
                   class="group bg-white rounded-xl shadow-sm border hover:shadow-md transition p-5 flex flex-col
                          {{ !empty($item['primary']) ? 'border-blue-300 ring-1 ring-blue-100 hover:border-blue-400' : 'hover:border-blue-200' }}">
                    <div class="w-11 h-11 rounded-lg {{ $item['iconBg'] }} flex items-center justify-center mb-3">
                        <i class="{{ $item['icon'] }} text-xl"></i>
                    </div>
                    <span class="font-semibold text-gray-900 group-hover:text-blue-700">
                        {{ $item['label'] }}
                        @if(!empty($item['primary']))
                            <span class="ml-1 text-[10px] uppercase tracking-wide text-blue-600 font-bold">Chính</span>
                        @endif
                    </span>
                    <span class="text-sm text-gray-500 mt-1">{{ $item['desc'] }}</span>
                    <span class="text-xs text-gray-400 mt-3">Nhấn để mở →</span>
                </a>
            @endif
        @endforeach
    </div>

    <div class="bg-blue-50 border border-blue-100 rounded-xl p-5 text-sm text-blue-900">
        <p class="font-semibold mb-1"><i class="bi bi-info-circle mr-1"></i> Cùng khung phân chia — khác quyền field</p>
        <ul class="list-disc pl-5 space-y-1 text-blue-800/90">
            <li>Vào <strong>Phân chia lịch học</strong> → chọn lịch lớp → chọn ngày (cùng form cho mọi tài khoản).</li>
            <li><strong>Phòng đào tạo</strong>: chỉnh <em>Môn · Loại tiết · Địa điểm</em>. Bài học &amp; GV khoá (do Khoa).</li>
            <li><strong>Khoa</strong> (K1–K8): chỉnh <em>Bài học · Giảng viên</em> cho môn thuộc khoa. Ba ô khung PDOT chỉ xem.</li>
            <li>Super-admin: full. Không cần menu riêng “việc Khoa” / “việc PDOT” cho bước phân tiết.</li>
        </ul>
    </div>
@endsection
