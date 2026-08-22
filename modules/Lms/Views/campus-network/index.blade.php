@extends('layouts.admin')

@section('title', 'Wi‑Fi trường · Điểm danh LMS')
@section('page-title', 'Wi‑Fi trường (điểm danh QR)')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Wi‑Fi trường'],
]" />

@php
    $headerActions = [
        [
            'url' => route('campus-network.test-ip'),
            'label' => 'Test IP / mạng trường',
            'icon' => 'wifi',
            'color' => 'secondary',
        ],
        [
            'url' => route('campus-network.stats'),
            'label' => 'Thống kê P2',
            'icon' => 'bar-chart',
            'color' => 'secondary',
        ],
    ];
    if (auth()->user()?->can('campus-network.create')) {
        $headerActions[] = [
            'url' => route('campus-network.create'),
            'label' => 'Thêm AP / dải IP',
            'icon' => 'plus',
            'color' => 'blue',
        ];
    }
    $diagOk = (bool) (($diagnose['evaluate']['ok'] ?? false));
    $diagIp = $diagnose['client_ip'] ?? '—';
@endphp

<x-page-header
    title="CẤU HÌNH WI‑FI TRƯỜNG (ĐIỂM DANH LMS)"
    :actions="$headerActions" />

<div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
    <strong>Lưu ý kỹ thuật:</strong> trình duyệt không đọc được MAC Wi‑Fi của điện thoại/laptop.
    Hệ thống lưu <strong>MAC access point</strong> (để IT cập nhật khi đổi router) và
    <strong>xác minh check-in bằng dải IP (CIDR)</strong> khi học viên quét QR điểm danh.
    Chỉ <strong>admin</strong> được chỉnh mục này — giảng viên không set trên portal LMS.
</div>

{{-- P0: tóm tắt IP client + TrustProxies --}}
<div class="mb-4 rounded-xl border px-4 py-3 {{ $diagOk ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }}">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide {{ $diagOk ? 'text-emerald-700' : 'text-rose-700' }}">
                IP hiện tại · {{ $diagOk ? 'OK' : 'Không khớp' }}
            </p>
            <p class="mt-1 font-mono text-lg font-bold text-slate-900">{{ $diagIp }}</p>
            <p class="mt-1 text-sm text-slate-700">{{ $diagnose['evaluate']['note'] ?? '' }}</p>
            <p class="mt-2 text-xs text-slate-500">
                TrustProxies:
                @if(!empty($diagnose['trusted_proxies']['configured']))
                    <span class="font-mono text-emerald-800">{{ $diagnose['trusted_proxies']['raw'] }}</span>
                @else
                    <span class="text-amber-800 font-medium">chưa set TRUSTED_PROXIES</span>
                @endif
            </p>
        </div>
        <a href="{{ route('campus-network.test-ip') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">
            <i class="bi bi-radar"></i> Chi tiết Test IP
        </a>
    </div>
    @if(!empty($diagnose['global_warnings']))
        <ul class="mt-3 space-y-1 text-xs">
            @foreach(array_slice($diagnose['global_warnings'], 0, 3) as $w)
                <li class="{{ ($w['level'] ?? '') === 'error' ? 'text-rose-700' : (($w['level'] ?? '') === 'warning' ? 'text-amber-800' : 'text-slate-600') }}">
                    · {{ $w['message'] }}
                </li>
            @endforeach
        </ul>
    @endif
</div>

<x-filter-form
    :action="route('campus-network.index')"
    :clear-url="route('campus-network.index')"
    :filters="[
        [
            'type' => 'search',
            'name' => 'search',
            'placeholder' => 'Tìm tên, MAC, CIDR…',
        ],
        [
            'type' => 'select',
            'name' => 'is_active',
            'placeholder' => 'Tất cả trạng thái',
            'options' => [
                '1' => 'Đang dùng',
                '0' => 'Tắt',
            ],
        ],
    ]" />

<div class="bg-white rounded-lg shadow">
    <div class="p-6">
        @if($networks->count() > 0)
            <div class="overflow-x-auto">
                <table class="min-w-full table-auto">
                    <thead class="bg-slate-100 text-slate-800 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase">#</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase">Tên</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase">MAC AP</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase">Dải IP (CIDR)</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase">Bắt buộc</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase">TT</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($networks as $net)
                            <tr class="hover:bg-slate-50/80">
                                <td class="px-4 py-3 text-sm text-slate-500">{{ $net->id }}</td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-slate-900">{{ $net->name }}</div>
                                    @if($net->note)
                                        <div class="text-xs text-slate-400 mt-0.5">{{ \Illuminate\Support\Str::limit($net->note, 60) }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 font-mono text-sm">{{ $net->wifi_mac ?: '—' }}</td>
                                <td class="px-4 py-3 font-mono text-xs max-w-xs break-all">{{ $net->ip_cidrs ?: '—' }}</td>
                                <td class="px-4 py-3 text-sm">
                                    @if($net->require_campus_network)
                                        <span class="text-emerald-700 font-medium">Có</span>
                                    @else
                                        <span class="text-slate-400">Không</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm">
                                    @if($net->is_active)
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-800 text-xs font-semibold">Active</span>
                                    @else
                                        <span class="inline-flex px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-xs">Off</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right text-sm space-x-2 whitespace-nowrap">
                                    @can('campus-network.show')
                                        <a href="{{ route('campus-network.show', $net) }}" class="text-slate-600 hover:text-blue-600">Xem</a>
                                    @endcan
                                    @can('campus-network.edit')
                                        <a href="{{ route('campus-network.edit', $net) }}" class="text-blue-600 hover:underline">Sửa</a>
                                    @endcan
                                    @can('campus-network.delete')
                                        <form action="{{ route('campus-network.destroy', $net) }}" method="POST" class="inline"
                                              data-confirm="Xoá cấu hình «{{ $net->name }}»?" data-confirm-danger="1" data-confirm-title="Xoá Wi‑Fi">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-rose-600 hover:underline">Xoá</button>
                                        </form>
                                    @endcan
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-4">{{ $networks->links() }}</div>
        @else
            <p class="text-center text-slate-500 py-10 text-sm">Chưa có cấu hình. Thêm AP / dải IP để bật kiểm tra Wi‑Fi khi điểm danh QR.</p>
        @endif
    </div>
</div>
@endsection
