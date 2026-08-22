@extends('layouts.admin')

@section('title', 'Vai trò & phân quyền')
@section('page-title', 'Vai trò & phân quyền')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Quản lý tài khoản', 'url' => route('accounts.hub')],
    ['title' => 'Vai trò']
]" />

<x-page-header
    title="VAI TRÒ & PHÂN QUYỀN"
    subtitle="Mỗi vai trò là một chức trách. Quyền cụ thể do ma trận ứng dụng quyết định, không suy ra từ tên vai trò."
    :actions="[
        [
            'url' => route('accounts.hub'),
            'label' => 'Hub tài khoản',
            'icon' => 'grid-3x3-gap',
            'color' => 'gray'
        ],
        [
            'url' => route('roles.integrity'),
            'label' => 'Sức khỏe phân quyền',
            'icon' => 'shield-check',
            'color' => 'green'
        ],
        [
            'url' => route('roles.create'),
            'label' => 'Tạo vai trò',
            'icon' => 'plus',
            'color' => 'blue'
        ],
    ]" />

@if(session('success'))
    <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{{ session('error') }}</div>
@endif

{{-- Chú giải: đọc được bảng bên dưới mà không cần mở từng vai trò --}}
<div class="mb-4 flex flex-wrap items-center gap-x-5 gap-y-2 rounded-xl border border-slate-200 bg-white px-4 py-3 text-xs text-slate-600">
    <span class="font-semibold text-slate-800">Cách đọc:</span>
    <span class="inline-flex items-center gap-1.5">
        <span class="inline-flex h-5 min-w-[2.75rem] items-center justify-center rounded-md bg-blue-100 px-1.5 font-semibold text-blue-800">5/17</span>
        số ứng dụng <strong>xem được</strong> / tổng số trong phân hệ
    </span>
    <span class="inline-flex items-center gap-1.5">
        <span class="inline-flex h-5 items-center rounded-md bg-amber-100 px-1.5 font-semibold text-amber-800">
            <i class="bi bi-pencil-fill text-[9px]"></i>
        </span>
        có quyền <strong>ghi</strong> (Thêm/Sửa/Xóa) trong phân hệ
    </span>
    <span class="inline-flex items-center gap-1.5">
        <span class="inline-flex h-5 min-w-[2.75rem] items-center justify-center rounded-md bg-slate-100 px-1.5 font-semibold text-slate-400">0/7</span>
        không chạm tới phân hệ
    </span>
</div>

<div class="overflow-hidden rounded-xl border bg-white shadow-sm">
    @if($roles->count())
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="sticky left-0 z-10 bg-gray-50 px-5 py-3 text-left text-xs font-semibold uppercase text-gray-500">
                            Vai trò
                        </th>
                        @foreach($subsystems['labels'] as $subsystem)
                            <th class="px-3 py-3 text-center text-xs font-semibold uppercase text-gray-500">
                                <div class="whitespace-nowrap">{{ $subsystem['label'] }}</div>
                                <div class="mt-0.5 font-normal normal-case text-gray-400">{{ $subsystem['total'] }} ứng dụng</div>
                            </th>
                        @endforeach
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-500">Tài khoản</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase text-gray-500">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($roles as $role)
                        @php
                            $isSuperAdmin = $role->name === \App\Support\ManagementRole::SUPER_ADMIN;
                            $isLegacy = $role->name === \App\Support\ManagementRole::LEGACY_MANAGER;
                            $isCatalog = in_array($role->name, $catalogNames, true);
                            $coverage = $subsystems['byRole'][$role->id] ?? [];
                        @endphp
                        <tr class="hover:bg-blue-50/40">
                            <td class="sticky left-0 z-10 bg-white px-5 py-3 hover:bg-blue-50/40">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-gray-900">{{ \App\Support\RoleDisplay::label($role->name) }}</span>
                                    @if($isCatalog)
                                        <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-emerald-700 ring-1 ring-inset ring-emerald-200"
                                              title="Nhóm vai trò chuẩn của nhà trường">Chuẩn</span>
                                    @endif
                                    @if($isSuperAdmin)
                                        <span class="rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-amber-700 ring-1 ring-inset ring-amber-200">Toàn quyền</span>
                                    @endif
                                    @if($isLegacy)
                                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-slate-600 ring-1 ring-inset ring-slate-300"
                                              title="Vai trò cũ, nên chuyển tài khoản sang nhóm vai trò chuẩn">Cũ</span>
                                    @endif
                                </div>
                                <div class="mt-0.5 font-mono text-[11px] text-gray-400">{{ $role->name }}</div>
                                <div class="mt-1 text-[11px] text-gray-500">{{ $role->permissions_count }} quyền</div>
                            </td>

                            @foreach($subsystems['labels'] as $subsystem)
                                @php
                                    $cell = $coverage[$subsystem['key']] ?? ['used' => 0, 'total' => $subsystem['total'], 'write' => false];
                                    $full = $cell['used'] > 0 && $cell['used'] === $cell['total'];
                                    $tone = $cell['used'] === 0
                                        ? 'bg-slate-100 text-slate-400'
                                        : ($full ? 'bg-blue-600 text-white' : 'bg-blue-100 text-blue-800');
                                @endphp
                                <td class="px-3 py-3 text-center">
                                    <div class="inline-flex items-center gap-1">
                                        <span class="inline-flex h-6 min-w-[2.75rem] items-center justify-center rounded-md px-1.5 text-xs font-semibold {{ $tone }}"
                                              title="Xem được {{ $cell['used'] }}/{{ $cell['total'] }} ứng dụng của {{ $subsystem['label'] }}">
                                            {{ $cell['used'] }}/{{ $cell['total'] }}
                                        </span>
                                        @if($cell['write'])
                                            <span class="inline-flex h-6 items-center rounded-md bg-amber-100 px-1.5 text-amber-800"
                                                  title="Có quyền Thêm/Sửa/Xóa trong phân hệ này">
                                                <i class="bi bi-pencil-fill text-[9px]"></i>
                                            </span>
                                        @endif
                                    </div>
                                </td>
                            @endforeach

                            <td class="px-4 py-3 text-center">
                                @if($role->users_count > 0)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-700">
                                        <i class="bi bi-person-fill text-[10px]"></i>{{ $role->users_count }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>

                            <td class="px-5 py-3 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('roles.edit', $role) }}"
                                       class="action-btn inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-medium text-white transition-colors hover:bg-blue-700">
                                        <i class="bi bi-pencil-square"></i> Phân quyền
                                    </a>
                                    <a href="{{ route('roles.show', $role) }}"
                                       class="inline-flex items-center rounded-lg border border-gray-200 px-2.5 py-1.5 text-xs text-gray-600 transition-colors hover:bg-gray-50"
                                       title="Xem ma trận (chỉ đọc)">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @unless($isSuperAdmin)
                                        <form action="{{ route('roles.destroy', $role) }}" method="POST" class="inline"
                                              data-confirm="Xóa vai trò «{{ \App\Support\RoleDisplay::label($role->name) }}»?@if($role->users_count) {{ $role->users_count }} tài khoản đang dùng sẽ mất vai trò này.@endif"
                                              data-confirm-danger="1"
                                              data-confirm-ok="Xóa">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="inline-flex items-center rounded-lg border border-red-200 px-2.5 py-1.5 text-xs text-red-600 transition-colors hover:bg-red-50"
                                                    title="Xóa vai trò">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="border-t border-gray-100 bg-gray-50 px-5 py-3">
            {{ $roles->links() }}
        </div>
    @else
        <div class="p-8 text-center text-gray-500">Không có vai trò nào.</div>
    @endif
</div>
@endsection
