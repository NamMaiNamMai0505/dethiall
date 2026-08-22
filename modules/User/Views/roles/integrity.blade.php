@extends('layouts.admin')

@section('title', 'Sức khỏe phân quyền')
@section('page-title', 'Sức khỏe phân quyền')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Quản lý tài khoản', 'url' => route('accounts.hub')],
    ['title' => 'Vai trò', 'url' => route('roles.index')],
    ['title' => 'Sức khỏe phân quyền']
]" />

<x-page-header
    title="SỨC KHỎE PHÂN QUYỀN"
    subtitle="Kiểm tra role, permission, phạm vi đơn vị và liên kết role_id mà không tự thay đổi nghiệp vụ"
    :actions="[[
        'url' => route('roles.index'),
        'label' => 'Danh sách vai trò',
        'icon' => 'arrow-left',
        'color' => 'gray'
    ]]" />

@if(session('success'))
    <div class="mb-5 flex items-start gap-3 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        <i class="bi bi-check-circle-fill mt-0.5"></i>
        <span>{{ session('success') }}</span>
    </div>
@endif
@if(session('info'))
    <div class="mb-5 flex items-start gap-3 rounded-xl border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
        <i class="bi bi-info-circle-fill mt-0.5"></i>
        <span>{{ session('info') }}</span>
    </div>
@endif
@if($errors->any())
    <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
        <div class="flex items-start gap-3">
            <i class="bi bi-exclamation-octagon-fill mt-0.5"></i>
            <span>{{ $errors->first() }}</span>
        </div>
    </div>
@endif

@php
    $cards = [
        ['label' => 'Role chuẩn', 'value' => $summary['roles_checked'], 'icon' => 'people', 'tone' => 'blue'],
        ['label' => 'Đơn vị phạm vi', 'value' => $summary['units_checked'], 'icon' => 'building', 'tone' => 'indigo'],
        ['label' => 'Tài khoản đã quét', 'value' => $summary['users_checked'], 'icon' => 'person-check', 'tone' => 'slate'],
        ['label' => 'Lỗi cần xử lý', 'value' => $summary['errors'], 'icon' => 'exclamation-octagon', 'tone' => 'red'],
        ['label' => 'Cảnh báo', 'value' => $summary['warnings'], 'icon' => 'exclamation-triangle', 'tone' => 'amber'],
        ['label' => 'Có thể sửa an toàn', 'value' => $summary['repairable'], 'icon' => 'wrench-adjustable-circle', 'tone' => 'emerald'],
    ];
    $tones = [
        'blue' => 'border-blue-200 bg-blue-50 text-blue-700',
        'indigo' => 'border-indigo-200 bg-indigo-50 text-indigo-700',
        'slate' => 'border-slate-200 bg-slate-50 text-slate-700',
        'red' => 'border-red-200 bg-red-50 text-red-700',
        'amber' => 'border-amber-200 bg-amber-50 text-amber-700',
        'emerald' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
    ];
@endphp

<div class="mb-6 grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-6">
    @foreach($cards as $card)
        <div class="rounded-xl border p-4 {{ $tones[$card['tone']] }}">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide opacity-75">{{ $card['label'] }}</p>
                    <p class="mt-1 text-2xl font-bold">{{ number_format($card['value']) }}</p>
                </div>
                <i class="bi bi-{{ $card['icon'] }} text-2xl opacity-70"></i>
            </div>
        </div>
    @endforeach
</div>

<div class="mb-6 grid gap-4 lg:grid-cols-2">
    <div class="rounded-xl border border-blue-200 bg-gradient-to-br from-blue-50 to-white p-5">
        <div class="flex gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white">
                <i class="bi bi-shield-lock"></i>
            </div>
            <div>
                <h2 class="font-semibold text-slate-900">Nguyên tắc sửa an toàn</h2>
                <p class="mt-1 text-sm leading-6 text-slate-600">
                    Chỉ đồng bộ cột <code class="rounded bg-white px-1.5 py-0.5 text-blue-700">users.role_id</code>
                    khi tài khoản có đúng một role thực tế. Không thêm, xóa hoặc đổi role đang được gán.
                </p>
            </div>
        </div>
    </div>
    <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">
        <div class="flex gap-3">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-700 text-white">
                <i class="bi bi-terminal"></i>
            </div>
            <div class="min-w-0">
                <h2 class="font-semibold text-slate-900">Kiểm tra bằng dòng lệnh</h2>
                <code class="mt-2 block overflow-x-auto rounded-lg bg-slate-900 px-3 py-2 text-xs text-slate-100">php artisan management-roles:audit --strict</code>
                <code class="mt-2 block overflow-x-auto rounded-lg bg-slate-900 px-3 py-2 text-xs text-slate-100">php artisan roles:repair-links</code>
            </div>
        </div>
    </div>
</div>

@if($issues->isEmpty())
    <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-6 py-12 text-center">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-emerald-600 text-2xl text-white shadow-lg shadow-emerald-200">
            <i class="bi bi-shield-check"></i>
        </div>
        <h2 class="mt-4 text-lg font-bold text-emerald-900">Phân quyền đang nhất quán</h2>
        <p class="mt-1 text-sm text-emerald-700">Không phát hiện lỗi role, permission, phạm vi đơn vị hoặc liên kết tài khoản.</p>
    </div>
@else
    <form method="POST"
          action="{{ route('roles.integrity.repair-links') }}"
          data-confirm="Đồng bộ role_id cho các tài khoản đã chọn? Role thực tế đang được gán sẽ được giữ nguyên."
          data-confirm-ok="Đồng bộ"
          class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        @csrf
        <div class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-semibold text-slate-900">Kết quả kiểm tra</h2>
                <p class="mt-0.5 text-sm text-slate-500">Các mục không có ô chọn cần được xử lý thủ công theo thông tin chi tiết.</p>
            </div>
            @if($summary['repairable'] > 0)
                <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-500/30">
                    <i class="bi bi-wrench-adjustable-circle"></i>
                    Đồng bộ mục đã chọn
                </button>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-white">
                    <tr>
                        <th class="w-14 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">Chọn</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Mức độ</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Mã kiểm tra</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Đối tượng</th>
                        <th class="min-w-[24rem] px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">Chi tiết</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($issues as $issue)
                        <tr class="align-top transition hover:bg-slate-50">
                            <td class="px-4 py-4 text-center">
                                @if($issue['repairable'] && !empty($issue['user_id']))
                                    <input type="checkbox"
                                           name="user_ids[]"
                                           value="{{ $issue['user_id'] }}"
                                           checked
                                           aria-label="Chọn {{ $issue['subject'] }}"
                                           class="h-4 w-4 rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                @else
                                    <i class="bi bi-dash text-slate-300"></i>
                                @endif
                            </td>
                            <td class="px-4 py-4">
                                @if($issue['severity'] === 'error')
                                    <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700"><i class="bi bi-x-circle-fill"></i>Lỗi</span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700"><i class="bi bi-exclamation-triangle-fill"></i>Cảnh báo</span>
                                @endif
                            </td>
                            <td class="px-4 py-4"><code class="rounded bg-slate-100 px-2 py-1 text-xs text-slate-700">{{ $issue['code'] }}</code></td>
                            <td class="px-4 py-4 text-sm font-medium text-slate-800">{{ $issue['subject'] }}</td>
                            <td class="px-4 py-4 text-sm leading-6 text-slate-600">
                                {{ $issue['message'] }}
                                @if($issue['repairable'])
                                    <span class="ml-1 inline-flex items-center gap-1 font-semibold text-emerald-700"><i class="bi bi-check-circle"></i>Có thể đồng bộ an toàn</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </form>
@endif
@endsection
