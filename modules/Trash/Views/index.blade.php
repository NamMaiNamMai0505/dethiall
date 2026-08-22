@extends('layouts.admin')

@section('title', 'Thùng rác')
@section('page-title', 'Thùng rác')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Thùng rác'],
]" />

<x-page-header
    title="THÙNG RÁC"
    :actions="[]" />

<div class="bg-amber-50 border border-amber-200 text-amber-900 rounded-lg p-4 mb-6 text-sm">
    <p class="font-semibold mb-1"><i class="bi bi-info-circle mr-1"></i> Lưu ý</p>
    <ul class="list-disc pl-5 space-y-1">
        <li>Dữ liệu xóa mềm được giữ <strong>vĩnh viễn</strong> trong thùng rác (không hết hạn tự động).</li>
        <li>Chỉ <strong>Super Admin</strong> và <strong>Quản lý (manager)</strong> được xem / khôi phục.</li>
        <li>Xóa vĩnh viễn chỉ dành cho Super Admin — không thể hoàn tác.</li>
    </ul>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
    <div class="bg-white rounded-lg border p-4">
        <p class="text-xs text-gray-500">Tổng mục trong thùng rác</p>
        <p class="text-2xl font-bold text-gray-900">{{ $totalCount }}</p>
    </div>
    @foreach(array_slice($counts, 0, 3, true) as $key => $count)
        @if($count > 0)
            <div class="bg-white rounded-lg border p-4">
                <p class="text-xs text-gray-500">{{ $moduleOptions[$key] ?? $key }}</p>
                <p class="text-2xl font-bold text-gray-900">{{ $count }}</p>
            </div>
        @endif
    @endforeach
</div>

<div class="bg-white rounded-lg shadow-sm border p-4 mb-6">
    <form method="GET" action="{{ route('trash.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="md:col-span-2">
            <input type="search" name="search" data-live-search="1" value="{{ $filters['search'] ?? '' }}"
                   placeholder="Tìm theo tên, mã, nội dung..."
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
        </div>
        <div>
            <select name="module" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">Tất cả loại dữ liệu</option>
                @foreach($moduleOptions as $key => $label)
                    <option value="{{ $key }}" @selected(($filters['module'] ?? '') === $key)>
                        {{ $label }}@if(($counts[$key] ?? 0) > 0) ({{ $counts[$key] }})@endif
                    </option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit"
                    class="flex-1 inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition-colors">
                <i class="bi bi-funnel"></i> Lọc
            </button>
            <a href="{{ route('trash.index') }}"
               title="Xóa bộ lọc"
               class="inline-flex items-center justify-center gap-2 shrink-0 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition-colors">
                <i class="bi bi-x-circle"></i>
                <span>Xóa lọc</span>
            </a>
        </div>
    </form>
</div>

<div class="mb-4 flex flex-wrap items-center gap-3">
    <label class="inline-flex items-center gap-2 cursor-pointer select-none">
        <span class="relative inline-block h-6 w-11">
            <input type="checkbox" id="bulk-select-toggle" class="peer sr-only">
            <span class="absolute inset-0 rounded-full bg-gray-300 transition-colors peer-checked:bg-blue-600"></span>
            <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white transition-transform peer-checked:translate-x-5"></span>
        </span>
        <span class="text-sm font-medium text-gray-700">Chọn nhiều</span>
    </label>
    <button type="button" id="bulk-restore-btn"
            class="hidden items-center gap-2 rounded-lg bg-green-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-700"
            onclick="bulkRestoreTrash()">
        <i class="bi bi-arrow-clockwise"></i>
        <span>Khôi phục đã chọn (<span class="bulk-selected-count">0</span>)</span>
    </button>
    @if(auth()->user()->isSuperAdmin())
        <button type="button" id="bulk-delete-btn"
                class="hidden items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700"
                onclick="bulkForceDeleteTrash()">
            <i class="bi bi-trash3-fill"></i>
            <span>Xóa vĩnh viễn đã chọn (<span class="bulk-selected-count">0</span>)</span>
        </button>
    @endif
</div>

<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    @if($items->count() > 0)
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-100 text-slate-700">
                    <tr>
                        <th class="bulk-col hidden px-4 py-3 text-left w-10">
                            <input type="checkbox" id="bulk-select-all" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 focus:ring-2">
                        </th>
                        <th class="px-4 py-3 text-left">Loại</th>
                        <th class="px-4 py-3 text-left">Thông tin giá trị</th>
                        <th class="px-4 py-3 text-left">Chi tiết</th>
                        <th class="px-4 py-3 text-left">Xóa lúc</th>
                        <th class="px-4 py-3 text-left">Người xóa</th>
                        <th class="px-4 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach($items as $item)
                        <tr class="hover:bg-gray-50">
                            <td class="bulk-col hidden px-4 py-3">
                                <input type="checkbox" class="bulk-select-item rounded border-gray-300 text-blue-600 focus:ring-blue-500 focus:ring-2" value="{{ $item['module_key'] }}:{{ $item['model_id'] }}">
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1 px-2 py-1 rounded-full bg-slate-100 text-slate-800 text-xs font-medium">
                                    <i class="bi {{ $item['icon'] }}"></i>
                                    {{ $item['type_label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-900">{{ $item['title'] }}</div>
                                @if($item['identifier'])
                                    <div class="text-xs font-mono text-blue-700 mt-0.5">{{ $item['identifier'] }}</div>
                                @endif
                                <div class="text-xs text-gray-400 mt-0.5">ID: {{ $item['model_id'] }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @if(is_array($item['summary']) && count($item['summary']))
                                    <dl class="text-xs text-gray-600 space-y-0.5 max-w-xs">
                                        @foreach(array_slice($item['summary'], 0, 4, true) as $k => $v)
                                            <div>
                                                <span class="text-gray-400">{{ $k }}:</span>
                                                <span class="text-gray-800">{{ is_scalar($v) ? $v : json_encode($v, JSON_UNESCAPED_UNICODE) }}</span>
                                            </div>
                                        @endforeach
                                    </dl>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-700">
                                {{ $item['deleted_at']?->format('d/m/Y H:i') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-gray-700">
                                {{ $item['deleted_by_name'] ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap justify-end gap-2">
                                    <a href="{{ route('trash.show', [$item['module_key'], $item['model_id']]) }}"
                                       class="action-btn inline-flex items-center gap-1 px-2.5 py-1.5 text-xs rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-50">
                                        <i class="bi bi-eye-fill" aria-hidden="true"></i> Chi tiết
                                    </a>
                                    <form method="POST" action="{{ route('trash.restore', [$item['module_key'], $item['model_id']]) }}" class="inline"
                                          data-confirm='Khôi phục mục này?'>
                                        @csrf
                                        <button type="submit"
                                                class="action-btn inline-flex items-center gap-1 px-2.5 py-1.5 text-xs rounded-lg bg-green-600 hover:bg-green-700 text-white">
                                            <i class="bi bi-arrow-clockwise" aria-hidden="true"></i> Khôi phục
                                        </button>
                                    </form>
                                    @if(auth()->user()->isSuperAdmin())
                                        <form method="POST" action="{{ route('trash.force-delete', [$item['module_key'], $item['model_id']]) }}" class="inline"
                                              data-confirm='XÓA VĨNH VIỄN? Không thể khôi phục lại.'>
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="action-btn inline-flex items-center gap-1 px-2.5 py-1.5 text-xs rounded-lg bg-red-600 hover:bg-red-700 text-white">
                                                <i class="bi bi-trash3-fill" aria-hidden="true"></i> Xóa hẳn
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($items->hasPages())
            <div class="px-4 py-3 border-t flex justify-center">
                {{ $items->links() }}
            </div>
        @endif
    @else
        <div class="text-center py-16 text-gray-500">
            <i class="bi bi-trash3 text-4xl mb-3 block text-gray-300"></i>
            <p class="font-medium">Thùng rác trống</p>
            <p class="text-sm mt-1">Các mục bị xóa (soft delete) sẽ xuất hiện tại đây.</p>
        </div>
    @endif
</div>
@include('partials.live-search')

@push('scripts')
<script>
(function () {
    if (window.__trashBulkSelectBound) return;
    window.__trashBulkSelectBound = true;

    function boot() {
        const toggle = document.getElementById('bulk-select-toggle');
        const selectAll = document.getElementById('bulk-select-all');
        const restoreBtn = document.getElementById('bulk-restore-btn');
        const deleteBtn = document.getElementById('bulk-delete-btn');
        const countEls = document.querySelectorAll('.bulk-selected-count');
        if (!toggle) return;

        function items() {
            return document.querySelectorAll('.bulk-select-item');
        }

        function updateBar() {
            const checked = document.querySelectorAll('.bulk-select-item:checked').length;
            countEls.forEach(function (el) { el.textContent = String(checked); });
            [restoreBtn, deleteBtn].forEach(function (btn) {
                if (!btn) return;
                btn.classList.toggle('hidden', checked === 0);
                btn.classList.toggle('flex', checked > 0);
            });
        }

        toggle.addEventListener('change', function () {
            document.querySelectorAll('.bulk-col').forEach(function (el) {
                el.classList.toggle('hidden', !toggle.checked);
            });
            if (!toggle.checked) {
                items().forEach(function (cb) { cb.checked = false; });
                if (selectAll) selectAll.checked = false;
                updateBar();
            }
        });

        selectAll?.addEventListener('change', function () {
            items().forEach(function (cb) { cb.checked = selectAll.checked; });
            updateBar();
        });

        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('bulk-select-item')) updateBar();
        });

        function selectedValues() {
            return Array.from(document.querySelectorAll('.bulk-select-item:checked')).map(function (cb) { return cb.value; });
        }

        function submitBulk(action, method, values) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = action;
            let html = '@csrf';
            if (method !== 'POST') {
                html += '<input type="hidden" name="_method" value="' + method + '">';
            }
            form.innerHTML = html;
            values.forEach(function (v) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'items[]';
                input.value = v;
                form.appendChild(input);
            });
            document.body.appendChild(form);
            form.submit();
        }

        window.bulkRestoreTrash = function () {
            const values = selectedValues();
            if (!values.length) return;
            if (!confirm('Khôi phục ' + values.length + ' mục đã chọn?')) return;
            submitBulk('{{ route('trash.bulk-restore') }}', 'POST', values);
        };

        window.bulkForceDeleteTrash = function () {
            const values = selectedValues();
            if (!values.length) return;
            if (!confirm('XÓA VĨNH VIỄN ' + values.length + ' mục đã chọn? Không thể khôi phục lại.')) return;
            submitBulk('{{ route('trash.bulk-force-delete') }}', 'DELETE', values);
        };
    }

    document.addEventListener('DOMContentLoaded', boot);
    if (document.readyState !== 'loading') boot();
    document.addEventListener('turbo:load', boot);
})();
</script>
@endpush
@endsection
