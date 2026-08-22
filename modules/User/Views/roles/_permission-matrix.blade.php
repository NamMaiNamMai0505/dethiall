{{--
  Ma trận phân quyền: Phân hệ → Ứng dụng (hàng) → Xem/Thêm/Sửa/Xóa/Duyệt/Xuất (cột).

  Required: $subsystems, $actions, $actionLabels
  Optional: $extraPermissions, $readonly (bool), $matrixId
--}}
@php
    $readonly = ! empty($readonly);
    $matrixId = $matrixId ?? 'role-permission-matrix';
    $extraPermissions = $extraPermissions ?? [];
    $grantedExtras = collect($extraPermissions)->where('granted', true)->count();

    $actionHelp = [
        'view' => 'Nhìn thấy ứng dụng trên menu và mở được danh sách. Không tick Xem thì các cột còn lại vô nghĩa.',
        'create' => 'Thêm bản ghi mới.',
        'edit' => 'Sửa bản ghi đã có.',
        'delete' => 'Xóa bản ghi.',
        'approve' => 'Phê duyệt / chốt / xử lý — thao tác mang tính quyết định.',
        'export' => 'Kết xuất ra Excel, Word hoặc PDF.',
    ];
    $actionTone = [
        'view' => 'text-slate-700',
        'create' => 'text-emerald-700',
        'edit' => 'text-blue-700',
        'delete' => 'text-red-700',
        'approve' => 'text-amber-700',
        'export' => 'text-violet-700',
    ];
@endphp

<div class="space-y-4" id="{{ $matrixId }}">
    {{-- Chú giải cột: đọc một lần là hiểu toàn bảng --}}
    <div class="rounded-xl border border-slate-200 bg-slate-50/70 p-4">
        <div class="mb-2 text-xs font-bold uppercase tracking-wide text-slate-500">Ý nghĩa từng cột</div>
        <div class="grid grid-cols-1 gap-x-6 gap-y-1.5 text-xs text-slate-600 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($actions as $action)
                <div class="flex gap-1.5">
                    <span class="shrink-0 font-semibold {{ $actionTone[$action] ?? 'text-slate-700' }}">
                        {{ $actionLabels[$action] ?? ucfirst($action) }}:
                    </span>
                    <span>{{ $actionHelp[$action] ?? '' }}</span>
                </div>
            @endforeach
            <div class="flex gap-1.5">
                <span class="shrink-0 font-semibold text-slate-400">—:</span>
                <span>Ứng dụng không có thao tác này, không tick được.</span>
            </div>
        </div>
    </div>

    @unless($readonly)
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="relative">
                <span class="pointer-events-none absolute inset-y-0 left-0 flex w-9 items-center justify-center text-gray-400">
                    <i class="bi bi-search text-xs"></i>
                </span>
                <input type="search" data-matrix-search
                       placeholder="Tìm nhanh ứng dụng…"
                       class="h-9 w-64 rounded-lg border border-gray-300 bg-white pl-9 pr-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs text-slate-500">
                    Đang tick <strong data-matrix-count class="text-slate-800">0</strong> ô
                </span>
                <button type="button" data-matrix-all="1"
                        class="rounded-lg border border-blue-100 bg-blue-50 px-3 py-1.5 text-xs font-medium text-blue-700 hover:bg-blue-100">
                    Chọn tất cả
                </button>
                <button type="button" data-matrix-all="0"
                        class="rounded-lg border border-gray-200 bg-gray-50 px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-100">
                    Bỏ chọn tất cả
                </button>
            </div>
        </div>
    @endunless

    <div class="w-full max-h-[40rem] overflow-auto rounded-xl border border-gray-200">
        <table class="min-w-full border-collapse text-sm">
            <thead class="sticky top-0 z-20 bg-gray-50 shadow-sm">
                <tr>
                    <th class="sticky left-0 z-30 min-w-[340px] bg-gray-50 p-3 text-left font-semibold text-gray-700">
                        Phân hệ / Ứng dụng
                    </th>
                    @foreach($actions as $action)
                        <th class="min-w-[78px] border-b border-gray-200 p-2 text-center font-semibold {{ $actionTone[$action] ?? 'text-gray-700' }}">
                            <div title="{{ $actionHelp[$action] ?? '' }}">{{ $actionLabels[$action] ?? ucfirst($action) }}</div>
                            @unless($readonly)
                                <div class="mt-1 flex justify-center gap-1">
                                    <button type="button" class="text-[10px] font-normal text-blue-600 hover:underline"
                                            data-matrix-col="{{ $action }}" data-matrix-checked="1">tất cả</button>
                                    <button type="button" class="text-[10px] font-normal text-gray-400 hover:underline"
                                            data-matrix-col="{{ $action }}" data-matrix-checked="0">∅</button>
                                </div>
                            @endunless
                        </th>
                    @endforeach
                    @unless($readonly)
                        <th class="min-w-[84px] border-b border-gray-200 p-2 text-center font-semibold text-gray-700">Hàng</th>
                    @endunless
                </tr>
            </thead>
            <tbody class="bg-white">
                @foreach($subsystems as $index => $subsystem)
                    <tr class="bg-slate-100" data-subsystem-header="{{ $subsystem['key'] }}">
                        <td colspan="{{ count($actions) + 1 + ($readonly ? 0 : 1) }}"
                            class="sticky left-0 border-y border-slate-200 bg-slate-100 px-3 py-2">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <span class="text-xs font-bold uppercase tracking-wide text-slate-700">
                                    <span class="mr-1.5 inline-flex h-5 w-5 items-center justify-center rounded bg-slate-700 text-[10px] font-bold text-white">
                                        {{ $index + 1 }}
                                    </span>
                                    {{ $subsystem['label'] }}
                                    <span class="ml-1 font-normal normal-case text-slate-500">
                                        ({{ count($subsystem['applications']) }} ứng dụng)
                                    </span>
                                </span>
                                @unless($readonly)
                                    <span class="flex gap-2">
                                        <button type="button" class="text-[11px] text-blue-600 hover:underline"
                                                data-matrix-group="{{ $subsystem['key'] }}" data-matrix-checked="1">chọn cả phân hệ</button>
                                        <button type="button" class="text-[11px] text-gray-400 hover:underline"
                                                data-matrix-group="{{ $subsystem['key'] }}" data-matrix-checked="0">bỏ chọn</button>
                                    </span>
                                @endunless
                            </div>
                        </td>
                    </tr>

                    @foreach($subsystem['applications'] as $application)
                        <tr class="hover:bg-blue-50/40"
                            data-matrix-row-for="{{ $application['key'] }}"
                            data-search="{{ mb_strtolower($application['label'].' '.$application['permission'].' '.$subsystem['label']) }}">
                            <td class="sticky left-0 z-10 border-b border-gray-100 bg-white p-3">
                                <div class="font-medium text-gray-900">{{ $application['label'] }}</div>
                                @if(!empty($application['note']))
                                    <div class="mt-0.5 flex gap-1 text-xs leading-4 text-amber-700">
                                        <i class="bi bi-info-circle mt-px shrink-0"></i>
                                        <span>{{ $application['note'] }}</span>
                                    </div>
                                @endif
                                <div class="mt-0.5 font-mono text-[11px] text-gray-400">{{ $application['permission'] }}</div>
                            </td>

                            @foreach($actions as $action)
                                @php($cell = $application['cells'][$action] ?? null)
                                <td class="border-b border-gray-100 p-2 text-center">
                                    @if($cell === null)
                                        <span class="text-gray-300"
                                              title="{{ $application['label'] }} không có thao tác {{ $actionLabels[$action] ?? $action }}">—</span>
                                    @elseif($readonly)
                                        @if($cell['granted'])
                                            <i class="bi bi-check-circle-fill text-green-600" title="{{ implode(', ', $cell['permissions']) }}"></i>
                                        @elseif($cell['partial'])
                                            <i class="bi bi-slash-circle text-amber-500" title="Cấp một phần: {{ implode(', ', $cell['permissions']) }}"></i>
                                        @else
                                            <i class="bi bi-dash-circle text-gray-300" title="{{ implode(', ', $cell['permissions']) }}"></i>
                                        @endif
                                    @else
                                        <input type="checkbox"
                                               name="abilities[{{ $application['key'] }}][]"
                                               value="{{ $action }}"
                                               data-subsystem="{{ $subsystem['key'] }}"
                                               data-application="{{ $application['key'] }}"
                                               data-action="{{ $action }}"
                                               title="{{ implode(', ', $cell['permissions']) }}"
                                               class="h-4 w-4 cursor-pointer rounded border-gray-300 text-blue-600 focus:ring-blue-500 {{ $cell['partial'] ? 'ring-2 ring-amber-400' : '' }}"
                                               {{ $cell['granted'] || $cell['partial'] ? 'checked' : '' }}>
                                    @endif
                                </td>
                            @endforeach

                            @unless($readonly)
                                <td class="border-b border-gray-100 p-2 text-center">
                                    <div class="flex justify-center gap-1">
                                        <button type="button" class="text-[10px] text-blue-600 hover:underline"
                                                data-matrix-row="{{ $application['key'] }}" data-matrix-checked="1">tất cả</button>
                                        <button type="button" class="text-[10px] text-gray-400 hover:underline"
                                                data-matrix-row="{{ $application['key'] }}" data-matrix-checked="0">∅</button>
                                    </div>
                                </td>
                            @endunless
                        </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="text-xs text-slate-500">
        <i class="bi bi-lightbulb text-amber-500"></i>
        Tick một thao tác ghi (Thêm / Sửa / Xóa / Duyệt / Xuất) thì <strong>Xem</strong> của cùng ứng dụng
        được bật kèm tự động — thiếu Xem thì vai trò không mở được màn hình nào để thao tác.
    </p>

    @if(!empty($extraPermissions))
        <details class="rounded-xl border border-gray-200 bg-gray-50/60 p-4" @if($grantedExtras > 0) open @endif>
            <summary class="cursor-pointer text-sm font-semibold text-gray-700">
                Quyền ngoài danh mục ứng dụng
                <span class="ml-1 rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium text-gray-700">
                    {{ $grantedExtras }}/{{ count($extraPermissions) }}
                </span>
            </summary>
            <p class="mt-2 text-xs text-gray-600">
                Quyền gộp cũ và quyền kỹ thuật chưa thuộc ứng dụng nào. Giữ nguyên cho vai trò đang
                chuyển đổi; vai trò mới <strong>không cần</strong> tick ở đây.
            </p>
            <div class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($extraPermissions as $permission)
                    <label class="flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-xs">
                        @if($readonly)
                            <i class="bi {{ $permission['granted'] ? 'bi-check-circle-fill text-green-600' : 'bi-dash-circle text-gray-300' }}"></i>
                        @else
                            <input type="checkbox" name="extra_permissions[]" value="{{ $permission['id'] }}"
                                   class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                   {{ $permission['granted'] ? 'checked' : '' }}>
                        @endif
                        <span class="font-mono text-gray-700">{{ $permission['name'] }}</span>
                    </label>
                @endforeach
            </div>
        </details>
    @endif
</div>

@unless($readonly)
@once
@push('scripts')
<script>
(function () {
    function bindMatrix(root) {
        if (!root || root.dataset.bound === '1') return;
        root.dataset.bound = '1';

        const all = sel => Array.from(root.querySelectorAll(sel)).filter(el => !el.disabled);
        const counter = root.querySelector('[data-matrix-count]');

        function refreshCount() {
            if (counter) counter.textContent = root.querySelectorAll('input[data-application]:checked').length;
        }

        function apply(selector, checked) {
            all(selector).forEach(el => {
                el.checked = checked;
                el.classList.remove('ring-2', 'ring-amber-400');
            });
            refreshCount();
        }

        root.addEventListener('click', function (e) {
            const allBtn = e.target.closest('[data-matrix-all]');
            if (allBtn) return apply('input[data-application]', allBtn.getAttribute('data-matrix-all') === '1');

            const groupBtn = e.target.closest('[data-matrix-group]');
            if (groupBtn) return apply(
                'input[data-subsystem="' + groupBtn.getAttribute('data-matrix-group') + '"]',
                groupBtn.getAttribute('data-matrix-checked') === '1'
            );

            const colBtn = e.target.closest('[data-matrix-col]');
            if (colBtn) return apply(
                'input[data-action="' + colBtn.getAttribute('data-matrix-col') + '"]',
                colBtn.getAttribute('data-matrix-checked') === '1'
            );

            const rowBtn = e.target.closest('[data-matrix-row]');
            if (rowBtn) return apply(
                'input[data-application="' + rowBtn.getAttribute('data-matrix-row') + '"]',
                rowBtn.getAttribute('data-matrix-checked') === '1'
            );
        });

        // Thao tác ghi luôn kéo theo quyền Xem của cùng ứng dụng.
        root.addEventListener('change', function (e) {
            const box = e.target.closest('input[data-application]');
            if (!box) return;
            if (box.checked && box.dataset.action !== 'view') {
                const viewBox = root.querySelector(
                    'input[data-application="' + box.dataset.application + '"][data-action="view"]'
                );
                if (viewBox) viewBox.checked = true;
            }
            refreshCount();
        });

        // Tìm nhanh ứng dụng; ẩn luôn tiêu đề phân hệ không còn dòng nào khớp.
        const search = root.querySelector('[data-matrix-search]');
        if (search) {
            search.addEventListener('input', function () {
                const q = search.value.trim().toLowerCase();
                const visibleBySubsystem = {};

                root.querySelectorAll('[data-matrix-row-for]').forEach(function (tr) {
                    const hit = !q || (tr.dataset.search || '').includes(q);
                    tr.hidden = !hit;
                    const key = tr.querySelector('input[data-subsystem]')?.dataset.subsystem;
                    if (key && hit) visibleBySubsystem[key] = true;
                });

                root.querySelectorAll('[data-subsystem-header]').forEach(function (tr) {
                    tr.hidden = q !== '' && !visibleBySubsystem[tr.dataset.subsystemHeader];
                });
            });
        }

        refreshCount();
    }

    function boot() {
        document.querySelectorAll('#role-permission-matrix, [id$="permission-matrix"]').forEach(bindMatrix);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
    document.addEventListener('turbo:load', boot);
})();
</script>
@endpush
@endonce
@endunless
