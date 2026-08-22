@extends('layouts.admin')

@section('title', 'Thống kê check-in · Wi‑Fi/GPS')
@section('page-title', 'Thống kê điểm danh mạng / GPS')

@section('content')
@php
    $ev = $stats['events'] ?? [];
    $rec = $stats['records'] ?? [];
    $days = $stats['days'] ?? 14;
@endphp

<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Wi‑Fi trường', 'url' => route('campus-network.index')],
    ['title' => 'Thống kê P2'],
]" />

<x-page-header
    title="THỐNG KÊ CHECK-IN (P2)"
    subtitle="Attempt log + bản ghi thành công: mạng, probe LAN, GPS campus."
    :actions="[
        ['url' => route('campus-network.index'), 'label' => 'Danh sách Wi‑Fi', 'icon' => 'list-ul', 'color' => 'secondary'],
        ['url' => route('campus-network.test-ip'), 'label' => 'Test IP', 'icon' => 'wifi', 'color' => 'secondary'],
    ]" />

<form method="GET" class="mb-4 flex flex-wrap items-end gap-2">
    <div>
        <label class="block text-xs font-semibold text-slate-600 mb-1">Khoảng (ngày)</label>
        <select name="days" class="border border-slate-200 rounded-lg px-3 py-2 text-sm">
            @foreach([7, 14, 30, 90] as $d)
                <option value="{{ $d }}" @selected($days == $d)>{{ $d }} ngày</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold">Lọc</button>
</form>

<div class="mb-4 rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-600">
    Campus GPS: <strong>{{ $campus['address'] ?? '—' }}</strong>
    · tâm <code class="bg-slate-100 px-1 rounded">{{ $campus['lat'] ?? '—' }}, {{ $campus['lng'] ?? '—' }}</code>
    · bán kính <strong>{{ $campus['radius_m'] ?? '—' }}m</strong>
    (env <code class="bg-slate-100 px-1 rounded">CAMPUS_RADIUS_M</code>)
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
    <div class="rounded-xl border bg-white p-4">
        <p class="text-xs uppercase text-slate-500 font-semibold">Attempts</p>
        <p class="text-2xl font-bold text-slate-900">{{ $ev['total'] ?? 0 }}</p>
        <p class="text-xs text-slate-500 mt-1">OK {{ $ev['ok'] ?? 0 }} · Fail {{ $ev['fail'] ?? 0 }}</p>
    </div>
    <div class="rounded-xl border bg-white p-4">
        <p class="text-xs uppercase text-slate-500 font-semibold">Bản ghi QR/self/GPS</p>
        <p class="text-2xl font-bold text-slate-900">{{ $rec['total'] ?? 0 }}</p>
        <p class="text-xs text-slate-500 mt-1">TB khoảng cách {{ $rec['avg_distance_m'] ?? '—' }} m</p>
    </div>
    <div class="rounded-xl border bg-white p-4">
        <p class="text-xs uppercase text-slate-500 font-semibold">Mạng / Probe</p>
        <p class="text-sm mt-1">Net OK <strong class="text-emerald-700">{{ $rec['network_ok'] ?? 0 }}</strong>
            · fail <strong class="text-rose-600">{{ $rec['network_fail'] ?? 0 }}</strong></p>
        <p class="text-sm">Probe OK <strong class="text-emerald-700">{{ $rec['probe_ok'] ?? 0 }}</strong>
            · fail <strong class="text-rose-600">{{ $rec['probe_fail'] ?? 0 }}</strong></p>
    </div>
    <div class="rounded-xl border bg-white p-4">
        <p class="text-xs uppercase text-slate-500 font-semibold">GPS</p>
        <p class="text-sm mt-1">Trong bán kính <strong class="text-emerald-700">{{ $rec['gps_ok'] ?? 0 }}</strong></p>
        <p class="text-sm">Ngoài / kém acc <strong class="text-rose-600">{{ $rec['gps_fail'] ?? 0 }}</strong></p>
    </div>
</div>

<div class="grid lg:grid-cols-2 gap-4 mb-4">
    <div class="bg-white rounded-xl border p-5">
        <h3 class="font-bold text-slate-800 mb-3">Lý do fail (events)</h3>
        @php $reasons = $ev['by_reason'] ?? []; @endphp
        @if(empty($reasons))
            <p class="text-sm text-slate-500">Không có fail trong {{ $days }} ngày.</p>
        @else
            <ul class="space-y-2 text-sm">
                @foreach($reasons as $reason => $count)
                    <li class="flex justify-between border-b border-slate-50 pb-1">
                        <span class="font-mono text-slate-700">{{ $reason ?: '—' }}</span>
                        <strong>{{ $count }}</strong>
                    </li>
                @endforeach
            </ul>
        @endif
        <p class="mt-3 text-[11px] text-slate-400">reason: token, expired, network, probe, gps, closed, manual_only…</p>
    </div>

    <div class="bg-white rounded-xl border p-5">
        <h3 class="font-bold text-slate-800 mb-3">Fail gần đây</h3>
        @if(empty($stats['recent_fails']))
            <p class="text-sm text-slate-500">—</p>
        @else
            <div class="overflow-x-auto max-h-80 overflow-y-auto">
                <table class="min-w-full text-xs">
                    <thead class="bg-slate-50 text-slate-500 uppercase">
                    <tr>
                        <th class="px-2 py-1 text-left">Thời gian</th>
                        <th class="px-2 py-1 text-left">Reason</th>
                        <th class="px-2 py-1 text-left">IP</th>
                        <th class="px-2 py-1 text-left">Ghi chú</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    @foreach($stats['recent_fails'] as $row)
                        <tr>
                            <td class="px-2 py-1 whitespace-nowrap">{{ $row['created_at'] }}</td>
                            <td class="px-2 py-1 font-mono">{{ $row['reason'] }}</td>
                            <td class="px-2 py-1 font-mono">{{ $row['ip'] }}</td>
                            <td class="px-2 py-1 max-w-xs truncate" title="{{ $row['note'] }}">{{ $row['note'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>

<p class="text-xs text-slate-500">
    JSON: <code class="bg-slate-100 px-1 rounded">{{ route('campus-network.stats', ['json' => 1, 'days' => $days]) }}</code>
</p>
@endsection
