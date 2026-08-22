@extends($layout)

@section('title', 'Binding dữ liệu · '.$template->name)
@section('page-title', 'Data Explorer & Placeholder Binding')

@section('content')
@php
    $editable = $canEdit
        && $version->status === \Modules\ExportTemplates\Enums\TemplateStatus::DRAFT
        && $version->activations->isEmpty();
    $selectedRef = $selectedTarget['ref'] ?? null;
    $selectedStyle = old('style', $selectedBinding?->style_overrides ?? []);
    $selectedOptions = old('options', $selectedBinding?->options ?? []);
@endphp
<div class="mx-auto max-w-[1600px] px-4 py-6">
    <div class="mb-4 flex flex-wrap items-start justify-between gap-3">
        <div>
            <a class="text-sm font-semibold text-teal-700 hover:underline"
               href="{{ route('export-templates.portal.show', ['portal' => $portal, 'exportTemplate' => $template]) }}">
                ← {{ $template->name }}
            </a>
            <h1 class="mt-2 text-xl font-bold text-slate-900">
                Binding dữ liệu · v{{ $version->version_number }}
            </h1>
            <p class="mt-1 text-sm text-slate-500">
                {{ $portalLabel }} · <code class="rounded bg-slate-100 px-1 text-xs">{{ $template->feature_key }}</code>
                · {{ $version->bindings()->count() }} binding
            </p>
        </div>
        @if($editable)
            <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                Bản nháp có thể chỉnh sửa
            </span>
        @else
            <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-800">
                Chỉ đọc — hãy tạo version mới để chỉnh binding
            </span>
        @endif
    </div>

    @if(session('success'))
        <div class="mb-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-900">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-900">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="mb-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-900">{{ $errors->first() }}</div>
    @endif

    <section class="mb-4 overflow-hidden rounded-xl border bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b px-4 py-3">
            <div>
                <h2 class="font-bold text-slate-900">Preview trực quan</h2>
                <p class="mt-0.5 text-xs text-slate-500">
                    Dữ liệu mock · Click target để chỉnh · Kéo trường từ Data Explorer thả vào target để binding.
                </p>
            </div>
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-bold uppercase text-slate-600">
                {{ $preview['format'] }}
            </span>
        </div>
        @if($preview['format'] === 'excel')
            @include('exporttemplates::partials.preview-excel')
        @else
            @include('exporttemplates::partials.preview-word')
        @endif
    </section>

    <div class="grid gap-4 xl:grid-cols-[minmax(300px,0.9fr)_minmax(330px,1fr)_minmax(360px,1.1fr)]">
        <section class="overflow-hidden rounded-xl border bg-white shadow-sm">
            <div class="border-b p-4">
                <h2 class="font-bold text-slate-900">Data Explorer</h2>
                <p class="mt-1 text-xs text-slate-500">Chỉ hiển thị dữ liệu trong schema allowlist.</p>
                <form class="mt-3" method="GET">
                    @if($selectedRef)
                        <input type="hidden" name="target_ref" value="{{ $selectedRef }}">
                    @endif
                    <input type="hidden" name="target_q" value="{{ request('target_q') }}">
                    <input name="q" data-live-search="1" value="{{ request('q') }}" placeholder="Tìm tên lớp, môn học, người ký..."
                           class="w-full rounded-lg border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                </form>
            </div>
            <div class="max-h-[68vh] overflow-y-auto p-3">
                @forelse($groups as $group)
                    <details class="mb-2 rounded-lg border" open>
                        <summary class="cursor-pointer px-3 py-2 text-sm font-bold text-slate-700">
                            {{ $group['label'] }}
                            <span class="ml-1 text-xs font-normal text-slate-400">({{ count($group['fields']) }})</span>
                        </summary>
                        <div class="divide-y border-t">
                            @foreach($group['fields'] as $field)
                                <button type="button"
                                        data-data-key="{{ $field['key'] }}"
                                        draggable="{{ $editable ? 'true' : 'false' }}"
                                        class="data-field block w-full px-3 py-2 text-left hover:bg-teal-50 {{ $editable ? 'cursor-grab active:cursor-grabbing' : '' }}">
                                    <span class="block text-sm font-semibold text-slate-700">{{ $field['label'] }}</span>
                                    <code class="block break-all text-[11px] text-teal-700">{{ $field['key'] }}</code>
                                    <span class="mt-0.5 block truncate text-xs text-slate-400">
                                        Mẫu:
                                        @if(is_bool($field['preview_value'] ?? null))
                                            {{ $field['preview_value'] ? 'Có' : 'Không' }}
                                        @elseif(is_scalar($field['preview_value'] ?? null))
                                            {{ $field['preview_value'] }}
                                        @else
                                            —
                                        @endif
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    </details>
                @empty
                    <p class="p-4 text-center text-sm text-slate-500">Không tìm thấy trường dữ liệu.</p>
                @endforelse
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border bg-white shadow-sm">
            <div class="border-b p-4">
                <h2 class="font-bold text-slate-900">Target trong template</h2>
                <p class="mt-1 text-xs text-slate-500">
                    {{ $targetTotal }} vị trí được parser phát hiện.
                    @if($targetTotal > 300) Hiển thị 300 kết quả đầu. @endif
                </p>
                <form class="mt-3" method="GET">
                    <input type="hidden" name="q" value="{{ request('q') }}">
                    <input name="target_q" data-live-search="1" value="{{ request('target_q') }}" placeholder="Tìm sheet, ô, bookmark, target ref..."
                           class="w-full rounded-lg border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                </form>
            </div>
            <div class="max-h-[68vh] divide-y overflow-y-auto">
                @forelse($targets as $target)
                    @php
                        $targetRef = $target['ref'] ?? '';
                        $binding = $bindings->get($targetRef);
                        $selected = $selectedRef === $targetRef;
                    @endphp
                    <a href="{{ request()->fullUrlWithQuery(['target_ref' => $targetRef]) }}"
                       class="block px-4 py-3 {{ $selected ? 'bg-blue-50 ring-1 ring-inset ring-blue-200' : 'hover:bg-slate-50' }}">
                        <div class="flex items-start justify-between gap-2">
                            <span class="text-sm font-bold text-slate-700">{{ $target['kind'] ?? 'target' }}</span>
                            @if($binding)
                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[10px] font-bold text-emerald-700">Đã binding</span>
                            @endif
                        </div>
                        <div class="mt-1 text-xs text-slate-500">
                            {{ $target['sheet'] ?? $target['part_type'] ?? $target['part'] ?? '—' }}
                            {{ $target['address'] ?? $target['range'] ?? '' }}
                        </div>
                        <code class="mt-1 block break-all text-[11px] text-blue-700">{{ $targetRef }}</code>
                        @if($binding)
                            <code class="mt-1 block break-all text-[11px] text-emerald-700">→ {{ $binding->data_key }}</code>
                        @endif
                    </a>
                @empty
                    <p class="p-6 text-center text-sm text-slate-500">Không có target phù hợp.</p>
                @endforelse
            </div>
        </section>

        <section class="self-start overflow-hidden rounded-xl border bg-white shadow-sm">
            <div class="border-b p-4">
                <h2 class="font-bold text-slate-900">Placeholder Binding</h2>
                <p class="mt-1 text-xs text-slate-500">Chọn dữ liệu; hệ thống tự lưu mapping, không nhập placeholder thủ công.</p>
            </div>
            @if($selectedTarget)
                <div class="space-y-4 p-4">
                    <div class="rounded-lg bg-slate-50 p-3">
                        <div class="text-xs font-bold uppercase text-slate-400">Target đang chọn</div>
                        <div class="mt-1 text-sm font-bold">{{ $selectedTarget['kind'] ?? 'target' }}</div>
                        <code class="mt-1 block break-all text-xs text-slate-600">{{ $selectedRef }}</code>
                    </div>

                    <form id="binding-form" method="POST"
                          action="{{ route('export-templates.portal.versions.bindings.store', [
                              'portal' => $portal,
                              'exportTemplate' => $template,
                              'version' => $version,
                          ]) }}"
                          class="space-y-3">
                        @csrf
                        <input id="binding-target-ref" type="hidden" name="target_ref" value="{{ $selectedRef }}">
                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">Binding</span>
                            <select id="binding-data-key" name="data_key" required {{ $editable ? '' : 'disabled' }}
                                    class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                                <option value="">— Chọn trường dữ liệu —</option>
                                @foreach($groups as $group)
                                    <optgroup label="{{ $group['label'] }}">
                                        @foreach($group['fields'] as $field)
                                            @php
                                                $preview = is_scalar($field['preview_value'] ?? null)
                                                    ? (string) $field['preview_value']
                                                    : '';
                                            @endphp
                                            <option value="{{ $field['key'] }}"
                                                    data-preview="{{ $preview }}"
                                                    data-type="{{ $field['type'] ?? 'string' }}"
                                                    @selected(old('data_key', $selectedBinding?->data_key ?? request('pending_data_key')) === $field['key'])>
                                                {{ $field['label'] }} · {{ $field['key'] }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                        </label>
                        <label class="block">
                            <span class="text-sm font-bold text-slate-700">Formatter <span class="font-normal text-slate-400">(tùy chọn)</span></span>
                            <input name="formatter" value="{{ old('formatter', $selectedBinding?->formatter) }}"
                                   {{ $editable ? '' : 'disabled' }}
                                   placeholder="Ví dụ: d/m/Y"
                                   class="mt-1 w-full rounded-lg border-slate-300 text-sm focus:border-teal-500 focus:ring-teal-500">
                        </label>

                        <details class="rounded-lg border" open>
                            <summary class="cursor-pointer px-3 py-2 text-sm font-bold text-slate-700">Font & căn chỉnh</summary>
                            <div class="grid grid-cols-2 gap-3 border-t p-3">
                                <label class="col-span-2 block">
                                    <span class="text-xs font-bold text-slate-600">Font</span>
                                    <input name="style[font_name]" value="{{ $selectedStyle['font_name'] ?? '' }}"
                                           data-style-prop="fontFamily" {{ $editable ? '' : 'disabled' }}
                                           placeholder="Times New Roman"
                                           class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                </label>
                                <label class="block">
                                    <span class="text-xs font-bold text-slate-600">Cỡ chữ</span>
                                    <input type="number" min="6" max="72" step="0.5"
                                           name="style[font_size]" value="{{ $selectedStyle['font_size'] ?? '' }}"
                                           data-style-prop="fontSize" {{ $editable ? '' : 'disabled' }}
                                           class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                </label>
                                <label class="block">
                                    <span class="text-xs font-bold text-slate-600">Căn ngang</span>
                                    <select name="style[align]" data-style-prop="textAlign" {{ $editable ? '' : 'disabled' }}
                                            class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                        <option value="">Theo template</option>
                                        @foreach(['left' => 'Trái', 'center' => 'Giữa', 'right' => 'Phải', 'justify' => 'Đều'] as $value => $label)
                                            <option value="{{ $value }}" @selected(($selectedStyle['align'] ?? '') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="text-xs font-bold text-slate-600">Căn dọc</span>
                                    <select name="style[vertical_align]" data-style-prop="verticalAlign" {{ $editable ? '' : 'disabled' }}
                                            class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                        <option value="">Theo template</option>
                                        @foreach(['top' => 'Trên', 'middle' => 'Giữa', 'bottom' => 'Dưới'] as $value => $label)
                                            <option value="{{ $value }}" @selected(($selectedStyle['vertical_align'] ?? '') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <div class="flex items-end gap-4 pb-2">
                                    <label class="inline-flex items-center gap-2 text-sm font-semibold">
                                        <input type="hidden" name="style[bold]" value="0">
                                        <input type="checkbox" name="style[bold]" value="1"
                                               data-style-prop="fontWeight" {{ $editable ? '' : 'disabled' }}
                                               @checked((bool) ($selectedStyle['bold'] ?? false))>
                                        Bold
                                    </label>
                                    <label class="inline-flex items-center gap-2 text-sm font-semibold">
                                        <input type="hidden" name="style[italic]" value="0">
                                        <input type="checkbox" name="style[italic]" value="1"
                                               data-style-prop="fontStyle" {{ $editable ? '' : 'disabled' }}
                                               @checked((bool) ($selectedStyle['italic'] ?? false))>
                                        Italic
                                    </label>
                                </div>
                            </div>
                        </details>

                        <details class="rounded-lg border">
                            <summary class="cursor-pointer px-3 py-2 text-sm font-bold text-slate-700">Kích thước, border & khoảng cách</summary>
                            <div class="grid grid-cols-2 gap-3 border-t p-3">
                                @foreach([
                                    'width' => 'Width (px)',
                                    'height' => 'Height (px)',
                                    'padding' => 'Padding (px)',
                                    'margin' => 'Margin (px)',
                                ] as $key => $label)
                                    <label class="block">
                                        <span class="text-xs font-bold text-slate-600">{{ $label }}</span>
                                        <input type="number" min="{{ in_array($key, ['padding', 'margin']) ? 0 : 1 }}"
                                               max="{{ in_array($key, ['padding', 'margin']) ? 200 : 2000 }}"
                                               name="style[{{ $key }}]" value="{{ $selectedStyle[$key] ?? '' }}"
                                               data-style-prop="{{ $key }}" {{ $editable ? '' : 'disabled' }}
                                               class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                    </label>
                                @endforeach
                                <label class="block">
                                    <span class="text-xs font-bold text-slate-600">Border</span>
                                    <select name="style[border_style]" data-style-prop="borderStyle" {{ $editable ? '' : 'disabled' }}
                                            class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                        @foreach(['' => 'Theo template', 'none' => 'Không', 'thin' => 'Mảnh', 'medium' => 'Vừa', 'thick' => 'Dày', 'dashed' => 'Nét đứt', 'dotted' => 'Chấm', 'double' => 'Đôi'] as $value => $label)
                                            <option value="{{ $value }}" @selected(($selectedStyle['border_style'] ?? '') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="text-xs font-bold text-slate-600">Màu border</span>
                                    <input type="color" name="style[border_color]"
                                           value="{{ $selectedStyle['border_color'] ?? '#0F172A' }}"
                                           data-style-prop="borderColor" {{ $editable ? '' : 'disabled' }}
                                           class="mt-1 h-10 w-full rounded-lg border-slate-300">
                                </label>
                            </div>
                        </details>

                        <details class="rounded-lg border">
                            <summary class="cursor-pointer px-3 py-2 text-sm font-bold text-slate-700">Table / Cell</summary>
                            <div class="grid grid-cols-2 gap-3 border-t p-3">
                                <label class="block">
                                    <span class="text-xs font-bold text-slate-600">Row height</span>
                                    <input type="number" min="1" max="1000" name="options[row_height]"
                                           value="{{ $selectedOptions['row_height'] ?? '' }}" {{ $editable ? '' : 'disabled' }}
                                           class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                </label>
                                <label class="block">
                                    <span class="text-xs font-bold text-slate-600">Column width</span>
                                    <input type="number" min="1" max="1000" name="options[column_width]"
                                           value="{{ $selectedOptions['column_width'] ?? '' }}" {{ $editable ? '' : 'disabled' }}
                                           class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                </label>
                                <label class="block">
                                    <span class="text-xs font-bold text-slate-600">Cell action</span>
                                    <select id="cell-action" name="options[cell_action]" {{ $editable ? '' : 'disabled' }}
                                            class="mt-1 w-full rounded-lg border-slate-300 text-sm">
                                        @foreach(['none' => 'Không thay đổi', 'merge' => 'Merge Cell', 'split' => 'Split Cell'] as $value => $label)
                                            <option value="{{ $value }}" @selected(($selectedOptions['cell_action'] ?? 'none') === $value)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="block">
                                    <span class="text-xs font-bold text-slate-600">Merge range</span>
                                    <input id="merge-range" name="options[merge_range]"
                                           value="{{ $selectedOptions['merge_range'] ?? '' }}"
                                           placeholder="A1:B2" {{ $editable ? '' : 'disabled' }}
                                           class="mt-1 w-full rounded-lg border-slate-300 text-sm uppercase">
                                </label>
                            </div>
                        </details>

                        <div class="rounded-lg border border-dashed border-teal-300 bg-teal-50 p-3">
                            <div class="text-xs font-bold uppercase text-teal-700">Preview mock realtime</div>
                            <div id="binding-preview-key" class="mt-1 break-all font-mono text-xs text-teal-900">
                                {{ $selectedBinding?->data_key ?? 'Chưa chọn dữ liệu' }}
                            </div>
                            <div id="binding-preview-value" class="mt-2 min-h-6 text-base font-bold text-slate-900">—</div>
                        </div>

                        @if($editable)
                            <button class="w-full rounded-lg bg-teal-600 px-4 py-2 text-sm font-bold text-white hover:bg-teal-700">
                                Lưu binding
                            </button>
                        @endif
                    </form>

                    @if($selectedBinding && $editable)
                        <form method="POST"
                              action="{{ route('export-templates.portal.versions.bindings.destroy', [
                                  'portal' => $portal,
                                  'exportTemplate' => $template,
                                  'version' => $version,
                                  'binding' => $selectedBinding,
                              ]) }}"
                              data-confirm="Gỡ binding khỏi target này?"
                              data-confirm-danger="1"
                              data-confirm-title="Gỡ binding"
                              data-confirm-ok="Gỡ binding">
                            @csrf
                            @method('DELETE')
                            <button class="w-full rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-bold text-rose-700">
                                Gỡ binding
                            </button>
                        </form>
                    @endif
                </div>
            @else
                <p class="p-6 text-center text-sm text-slate-500">Chọn một target để cấu hình binding.</p>
            @endif
        </section>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const editable = @json($editable);
    const form = document.getElementById('binding-form');
    const select = document.getElementById('binding-data-key');
    const targetInput = document.getElementById('binding-target-ref');
    const keyBox = document.getElementById('binding-preview-key');
    const valueBox = document.getElementById('binding-preview-value');
    if (!select || !keyBox || !valueBox) return;

    const currentTargets = function () {
        if (!targetInput || !targetInput.value) return [];
        return Array.from(document.querySelectorAll('.preview-target')).filter(function (element) {
            return element.dataset.targetRef === targetInput.value;
        });
    };

    const refresh = function () {
        const option = select.options[select.selectedIndex];
        keyBox.textContent = select.value || 'Chưa chọn dữ liệu';
        valueBox.textContent = option && option.dataset.preview ? option.dataset.preview : '—';
        currentTargets().forEach(function (target) {
            const previewValue = target.querySelector('.preview-value');
            if (previewValue && option && option.dataset.preview) {
                previewValue.textContent = option.dataset.preview;
            }
        });
    };

    select.addEventListener('change', refresh);
    document.querySelectorAll('.data-field').forEach(function (button) {
        button.addEventListener('dragstart', function (event) {
            if (!editable) {
                event.preventDefault();
                return;
            }
            event.dataTransfer.effectAllowed = 'copy';
            event.dataTransfer.setData('text/plain', button.dataset.dataKey || '');
            button.classList.add('opacity-50');
        });
        button.addEventListener('dragend', function () {
            button.classList.remove('opacity-50');
        });
        button.addEventListener('click', function () {
            if (select.disabled) return;
            select.value = button.dataset.dataKey || '';
            refresh();
            select.focus();
        });
    });

    document.querySelectorAll('.preview-target').forEach(function (target) {
        if (!target.dataset.targetRef) return;

        target.addEventListener('click', function (event) {
            event.stopPropagation();
            const url = new URL(window.location.href);
            url.searchParams.set('target_ref', target.dataset.targetRef);
            window.location.href = url.toString();
        });
        target.addEventListener('dragover', function (event) {
            if (!editable) return;
            event.preventDefault();
            event.dataTransfer.dropEffect = 'copy';
            target.classList.add('ring-4', 'ring-emerald-400');
        });
        target.addEventListener('dragleave', function () {
            target.classList.remove('ring-4', 'ring-emerald-400');
        });
        target.addEventListener('drop', function (event) {
            if (!editable || !form || !targetInput) return;
            event.preventDefault();
            event.stopPropagation();
            target.classList.remove('ring-4', 'ring-emerald-400');
            const dataKey = event.dataTransfer.getData('text/plain');
            if (!dataKey) return;
            targetInput.value = target.dataset.targetRef;
            select.value = dataKey;
            refresh();
            form.requestSubmit();
        });
    });

    const applyRealtimeStyle = function () {
        const styleInputs = document.querySelectorAll('[data-style-prop]');
        currentTargets().forEach(function (target) {
            styleInputs.forEach(function (input) {
                const property = input.dataset.styleProp;
                let value = input.type === 'checkbox' ? input.checked : input.value;
                if (property === 'fontSize' && value) value += 'pt';
                if (['width', 'height', 'padding', 'margin'].includes(property) && value) value += 'px';
                if (property === 'fontWeight') value = value ? '700' : '400';
                if (property === 'fontStyle') value = value ? 'italic' : 'normal';
                if (property === 'borderStyle') {
                    const map = {thin: 'solid', medium: 'solid', thick: 'solid'};
                    value = map[value] || value;
                    if (value && value !== 'none') {
                        target.style.borderWidth = input.value === 'thick' ? '3px' : (input.value === 'medium' ? '2px' : '1px');
                    }
                }
                if (value !== '') target.style[property] = value;
            });
        });
    };
    document.querySelectorAll('[data-style-prop]').forEach(function (input) {
        input.addEventListener('input', applyRealtimeStyle);
        input.addEventListener('change', applyRealtimeStyle);
    });

    const cellAction = document.getElementById('cell-action');
    const mergeRange = document.getElementById('merge-range');
    const toggleMergeRange = function () {
        if (!cellAction || !mergeRange) return;
        mergeRange.disabled = !editable || cellAction.value !== 'merge';
        mergeRange.closest('label').classList.toggle('opacity-50', cellAction.value !== 'merge');
    };
    if (cellAction) cellAction.addEventListener('change', toggleMergeRange);
    toggleMergeRange();
    refresh();
});
</script>
@include('partials.live-search')
@endsection
