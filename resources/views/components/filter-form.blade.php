@props([
    'action' => '',
    'method' => 'GET',
    'filters' => [],
    'clearUrl' => '',
    'columns' => 4,
    'autoSubmit' => true,
])
@php
    $filterFormUid = 'filter-'.\Illuminate\Support\Str::lower(\Illuminate\Support\Str::random(8));
    $colCount = max(1, min(6, (int) $columns));

    /*
     * Lọc liên động: `depends_on` khai báo điều kiện cha của một select.
     * Khi đổi điều kiện cha, form tự nạp lại nên danh sách của điều kiện con
     * luôn được lọc sẵn từ server — không cần bấm "Lọc" giữa chừng.
     */
    $filterDependencies = collect($filters)
        ->filter(fn ($filter) => ! empty($filter['depends_on']))
        ->mapWithKeys(fn ($filter) => [
            $filter['name'] => implode(',', (array) $filter['depends_on']),
        ])
        ->all();

    $filterParentIsEmpty = function (array $filter): bool {
        foreach ((array) ($filter['depends_on'] ?? []) as $parent) {
            if (! request()->filled($parent)) {
                return true;
            }
        }

        return false;
    };
    // Full class names so Tailwind keeps every supported grid variant.
    $mdCols = match ($colCount) {
        1 => 'md:grid-cols-1',
        2 => 'md:grid-cols-2',
        3 => 'md:grid-cols-3',
        4 => 'md:grid-cols-4',
        5 => 'md:grid-cols-5',
        default => 'md:grid-cols-6',
    };
@endphp

<div class="bg-white rounded-lg shadow-sm border p-4 sm:p-5 mb-6">
    <form method="{{ $method }}" action="{{ $action }}"
          data-filter-form
          @if($autoSubmit) data-filter-auto-submit="1" @endif>
        @if(strtoupper($method) !== 'GET')
            @csrf
        @else
            @foreach(['sort_by', 'sort_order'] as $sortParameter)
                @if(request()->filled($sortParameter))
                    <input type="hidden" name="{{ $sortParameter }}" value="{{ request($sortParameter) }}">
                @endif
            @endforeach
        @endif

        {{-- filters + nút cùng hàng --}}
        <div class="grid grid-cols-1 {{ $mdCols ?? 'md:grid-cols-4' }} gap-4 items-end">
            @foreach($filters as $filter)
                <div class="min-w-0">
                    @if($filter['type'] === 'search')
                        <div class="relative w-full">
                            <span class="pointer-events-none absolute inset-y-0 left-0 z-[1] flex w-10 items-center justify-center text-gray-400">
                                <i class="bi bi-search text-[0.95rem] leading-none"></i>
                            </span>
                            <input type="text"
                                   name="{{ $filter['name'] }}"
                                   data-filter-search="1"
                                   value="{{ request($filter['name']) }}"
                                   placeholder="{{ $filter['placeholder'] ?? '' }}"
                                   class="block w-full h-11 rounded-lg border border-gray-300 bg-white pl-10 pr-3 text-sm leading-none
                                          focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
                        </div>
                    @elseif($filter['type'] === 'select')
                        @php
                            $waitingForParent = ! empty($filter['depends_on']) && $filterParentIsEmpty($filter);
                            $selectPlaceholder = $waitingForParent && ! empty($filter['waiting_placeholder'])
                                ? $filter['waiting_placeholder']
                                : ($filter['placeholder'] ?? 'Tất cả');
                        @endphp
                        @if(!empty($filter['label']))
                            <label class="mb-1 block text-xs font-semibold text-slate-600">{{ $filter['label'] }}</label>
                        @endif
                        <select name="{{ $filter['name'] }}"
                                data-searchable="1"
                                data-filter-name="{{ $filter['name'] }}"
                                @if(!empty($filter['depends_on'])) data-filter-depends="{{ implode(',', (array) $filter['depends_on']) }}" @endif
                                @if(($filter['auto'] ?? true) === false) data-filter-no-auto="1" @endif
                                data-placeholder="{{ $selectPlaceholder }}"
                                class="w-full">
                            <option value="">{{ $selectPlaceholder }}</option>
                            @foreach($filter['options'] as $key => $label)
                                <option value="{{ $key }}" {{ (string) request($filter['name']) === (string) $key ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    @elseif($filter['type'] === 'relation-select')
                        <select name="{{ $filter['name'] }}"
                                data-searchable="1"
                                data-placeholder="{{ $filter['placeholder'] ?? 'Tất cả' }}"
                                class="w-full">
                            <option value="">{{ $filter['placeholder'] ?? 'Tất cả' }}</option>
                            @foreach($filter['options'] as $item)
                                <option value="{{ $item->id }}" {{ (string) request($filter['name']) === (string) $item->id ? 'selected' : '' }}>
                                    {{ $item->name }}
                                </option>
                            @endforeach
                        </select>
                    @elseif($filter['type'] === 'instructor-select')
                        <div class="instructor-select-field">
                            <select name="{{ $filter['name'] }}"
                                    id="filter_{{ $filter['name'] }}"
                                    data-instructor-select
                                    data-placeholder="{{ $filter['placeholder'] ?? 'Tất cả giảng viên' }}"
                                    data-searchable="1"
                                    class="w-full">
                                <option value="">{{ $filter['placeholder'] ?? 'Tất cả giảng viên' }}</option>
                                @foreach($filter['options'] ?? [] as $item)
                                    @php
                                        $id = is_array($item) ? ($item['id'] ?? $item['value'] ?? '') : ($item->id ?? '');
                                        $name = is_array($item) ? ($item['name'] ?? $item['text'] ?? '') : ($item->name ?? '');
                                        $code = is_array($item) ? ($item['code'] ?? '') : ($item->code ?? '');
                                        $unit = is_array($item) ? ($item['unit'] ?? '') : ($item->unit->name ?? '');
                                    @endphp
                                    <option value="{{ $id }}"
                                            data-name="{{ $name }}"
                                            data-code="{{ $code }}"
                                            data-unit="{{ $unit }}"
                                            {{ (string) request($filter['name']) === (string) $id ? 'selected' : '' }}>
                                        {{ $name }}@if($code) ({{ $code }})@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @elseif($filter['type'] === 'date')
                        @php($dateFilterId = $filterFormUid.'-'.$filter['name'].'-'.$loop->index)
                        @if(!empty($filter['label']))
                            <label for="{{ $dateFilterId }}" class="mb-2 block text-sm font-medium text-gray-700">
                                {{ $filter['label'] }}
                            </label>
                        @endif
                        <div class="date-input-control">
                            <input type="date"
                                   id="{{ $dateFilterId }}"
                                   name="{{ $filter['name'] }}"
                                   value="{{ request($filter['name']) }}"
                                   @if(!empty($filter['min'])) min="{{ $filter['min'] }}" @endif
                                   @if(!empty($filter['max'])) max="{{ $filter['max'] }}" @endif
                                   class="date-input date-input--ready w-full">
                            <i class="bi bi-calendar3 date-input-icon" aria-hidden="true"></i>
                        </div>
                    @endif
                </div>
            @endforeach

            @if($slot->isEmpty())
                <div class="flex flex-col sm:flex-row sm:items-stretch gap-2 md:col-span-1 min-w-0">
                    <button type="submit"
                            class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-700">
                        <i class="bi bi-funnel leading-none"></i>
                        <span>Lọc</span>
                    </button>
                    @if($clearUrl)
                        <a href="{{ $clearUrl }}"
                           class="inline-flex h-11 flex-1 items-center justify-center gap-2 rounded-lg bg-red-600 px-4 text-sm font-medium text-white shadow-sm transition-colors hover:bg-red-700">
                            <i class="bi bi-x-circle leading-none"></i>
                            <span>Xóa lọc</span>
                        </a>
                    @endif
                </div>
            @endif
        </div>

        @if($slot->isNotEmpty())
            <div class="mt-4">
                {{ $slot }}
            </div>
        @endif
    </form>
</div>

@if($filterDependencies !== [])
    <script type="application/json" data-filter-dependencies="{{ $filterFormUid }}">
        @json($filterDependencies)
    </script>
@endif

@once
@push('scripts')
<script>
(function () {
    /**
     * Lọc liên động dùng chung cho mọi màn hình danh mục.
     *
     * - Đổi một điều kiện lọc → xóa giá trị của các điều kiện phụ thuộc phía sau
     *   (kể cả phụ thuộc bắc cầu: Hệ → Ngành → Môn → Bài) rồi nạp lại danh sách.
     * - Server dựng lại option của điều kiện con theo điều kiện cha, nên danh sách
     *   luôn khớp và không cần bấm "Lọc" ở giữa chuỗi điều kiện.
     * - Ô tìm kiếm dạng chữ tự nạp lại khi gõ (debounce ~400ms) — không cần bấm
     *   "Lọc" hay Enter nữa.
     */
    function dependencyMap(form) {
        const map = {};
        form.querySelectorAll('[data-filter-depends]').forEach(function (el) {
            const name = el.getAttribute('data-filter-name');
            if (!name) return;
            map[name] = el.getAttribute('data-filter-depends').split(',').map(function (s) {
                return s.trim();
            }).filter(Boolean);
        });
        return map;
    }

    function descendantsOf(map, name) {
        const found = new Set();
        const walk = function (parent) {
            Object.keys(map).forEach(function (child) {
                if (map[child].indexOf(parent) === -1 || found.has(child)) return;
                found.add(child);
                walk(child);
            });
        };
        walk(name);
        return Array.from(found);
    }

    function clearControl(form, name) {
        const el = form.querySelector('[data-filter-name="' + name + '"]');
        if (!el) return;
        if (el.tomselect) {
            el.tomselect.clear(true);
        }
        el.value = '';
    }

    function submitFiltered(form) {
        // Bỏ trang hiện tại khi đổi bộ lọc, tránh rơi vào trang trống.
        const page = form.querySelector('[name="page"]');
        if (page) page.remove();

        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }

    function bindForm(form) {
        if (!form || form.dataset.filterCascadeBound === '1') return;
        form.dataset.filterCascadeBound = '1';

        const map = dependencyMap(form);

        form.addEventListener('change', function (e) {
            const control = e.target.closest('[data-filter-name]');
            if (!control || !form.contains(control)) return;

            const name = control.getAttribute('data-filter-name');

            // Điều kiện cha đổi → giá trị cũ của điều kiện con không còn hợp lệ.
            descendantsOf(map, name).forEach(function (child) {
                clearControl(form, child);
            });

            if (control.hasAttribute('data-filter-no-auto')) return;
            if (form.getAttribute('data-filter-auto-submit') !== '1') return;

            submitFiltered(form);
        });

        // Ô tìm kiếm dạng chữ: tự nạp lại khi gõ, debounce để không spam server.
        // Turbo visit (GET, không morph) sẽ thay cả <body> nên mất focus - lưu tên
        // ô đang gõ vào sessionStorage để refocus lại sau khi trang nạp xong.
        if (form.getAttribute('data-filter-auto-submit') === '1') {
            let searchDebounce = null;
            form.querySelectorAll('[data-filter-search]').forEach(function (input) {
                input.addEventListener('input', function () {
                    clearTimeout(searchDebounce);
                    searchDebounce = setTimeout(function () {
                        try {
                            sessionStorage.setItem('__filterSearchFocus', input.name);
                        } catch (e) { /* ignore (private mode...) */ }
                        submitFiltered(form);
                    }, 400);
                });
            });
        }
    }

    function restoreSearchFocus() {
        let name;
        try {
            name = sessionStorage.getItem('__filterSearchFocus');
        } catch (e) {
            return;
        }
        if (!name) return;
        try {
            sessionStorage.removeItem('__filterSearchFocus');
        } catch (e) { /* ignore */ }
        const input = document.querySelector('[data-filter-search][name="' + name + '"]');
        if (!input) return;
        input.focus();
        const len = input.value.length;
        try {
            input.setSelectionRange(len, len);
        } catch (e) { /* ignore (unsupported input type) */ }
    }

    function boot() {
        document.querySelectorAll('form[data-filter-form]').forEach(bindForm);
        restoreSearchFocus();
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
