@extends('layouts.admin')

@section('title', $network->name)
@section('page-title', 'Chi tiết Wi‑Fi trường')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Wi‑Fi trường', 'url' => route('campus-network.index')],
    ['title' => $network->name],
]" />

<div class="bg-white rounded-lg shadow max-w-2xl p-6 space-y-3 text-sm">
    <div><span class="text-slate-500">Tên:</span> <strong>{{ $network->name }}</strong></div>
    <div><span class="text-slate-500">MAC AP:</span> <code class="font-mono">{{ $network->wifi_mac ?: '—' }}</code></div>
    <div><span class="text-slate-500">CIDR:</span> <code class="font-mono break-all">{{ $network->ip_cidrs ?: '—' }}</code></div>
    <div><span class="text-slate-500">Probe:</span> {{ $network->probe_url ?: '—' }}</div>
    <div><span class="text-slate-500">Bắt buộc:</span> {{ $network->require_campus_network ? 'Có' : 'Không' }}</div>
    <div><span class="text-slate-500">Active:</span> {{ $network->is_active ? 'Có' : 'Không' }}</div>
    <div><span class="text-slate-500">Ghi chú:</span> {{ $network->note ?: '—' }}</div>
    <div class="pt-3 flex gap-2">
        @can('campus-network.edit')
            <a href="{{ route('campus-network.edit', $network) }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm">Sửa</a>
        @endcan
        <a href="{{ route('campus-network.index') }}" class="px-4 py-2 border rounded-lg text-sm">Danh sách</a>
    </div>
</div>
@endsection
