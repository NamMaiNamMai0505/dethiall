@extends($portal === 'lms' ? 'layouts.lms-learner' : ($portal === 'grades' ? 'layouts.grades' : 'layouts.admin'))
@section('title', 'Builder Template')

@section('content')
<style>
    #template-builder {
        --builder-brand: #0f766e;
        --builder-brand-dark: #115e59;
        --builder-line: #cbd5e1;
        --builder-paper: #fff;
        color: #0f172a;
    }
    #template-builder button,
    #template-builder select,
    #template-builder input {
        transition: border-color .16s ease, background-color .16s ease, box-shadow .16s ease, transform .16s ease;
    }
    #template-builder button:hover:not(:disabled) {
        transform: translateY(-1px);
        box-shadow: 0 8px 20px -14px rgba(15, 118, 110, .9);
    }
    #template-builder button:active:not(:disabled) { transform: scale(.97); }
    #template-builder button:focus-visible,
    #template-builder [contenteditable]:focus-visible,
    #template-builder select:focus-visible {
        outline: 2px solid #14b8a6;
        outline-offset: 2px;
    }
    #builder-toolbar {
        position: sticky;
        top: .5rem;
        z-index: 30;
        background: linear-gradient(135deg, #0f172a, #134e4a);
        box-shadow: 0 12px 26px -18px rgba(15, 23, 42, .9);
    }
    #builder-toolbar select[data-native-select] {
        width: 58px !important;
        min-width: 58px !important;
        max-width: 58px !important;
        height: 30px !important;
        padding: 2px 4px !important;
        border-radius: 7px;
        border: 1px solid rgba(255,255,255,.24);
        background: #fff;
        color: #0f172a;
        font-size: 12px;
    }
    #builder-toolbar select[data-format="align"] {
        width: 52px !important;
        min-width: 52px !important;
        max-width: 52px !important;
    }
    #builder-toolbar .ts-wrapper { display: none !important; }
    #builder-toolbar [data-format-toggle].is-active {
        background: #2dd4bf;
        color: #042f2e;
        box-shadow: inset 0 0 0 1px rgba(255,255,255,.45);
    }
    .builder-palette-button {
        min-height: 54px;
        border: 1px solid #dbe4ee;
        background: linear-gradient(180deg, #fff, #f8fafc);
        color: #334155;
    }
    .builder-palette-button:hover {
        border-color: #5eead4;
        background: #f0fdfa;
        color: #0f766e;
    }
    .builder-block {
        position: relative;
        border: 1px solid transparent;
        border-radius: 8px;
        transition: border-color .16s ease, box-shadow .16s ease;
    }
    .builder-block:hover { border-color: #99f6e4; }
    .builder-block.is-selected {
        border-color: #14b8a6;
        box-shadow: 0 0 0 3px rgba(20, 184, 166, .14);
    }
    .builder-editable:empty::before {
        content: attr(data-placeholder);
        color: #94a3b8;
    }
    .builder-header-pair {
        display: grid;
        grid-template-columns: minmax(0, 1fr) minmax(0, 1.25fr);
        gap: 28px;
        align-items: start;
    }
    .builder-header-cell {
        min-height: 58px;
        border-radius: 8px;
        padding: 8px 10px;
        white-space: pre-wrap;
        text-align: center;
        font-weight: 700;
        line-height: 1.45;
    }
    .builder-header-cell:hover { background: #f8fafc; }
    .builder-table-shell {
        position: relative;
        padding: 30px 34px 34px;
        overflow: auto;
    }
    .builder-grid-cell {
        min-width: 90px;
        height: 34px;
        border: 1px solid #94a3b8;
        padding: 5px 7px;
        vertical-align: top;
        background: #fff;
        white-space: pre-wrap;
    }
    .builder-grid-cell:focus {
        background: #ecfeff;
        box-shadow: inset 0 0 0 2px #14b8a6;
        outline: 0;
    }
    .builder-grid-add {
        position: absolute;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 25px;
        height: 25px;
        border: 1px solid #5eead4;
        border-radius: 999px;
        background: #f0fdfa;
        color: #0f766e;
        font-weight: 800;
    }
    .builder-grid-add.top { top: 1px; left: 50%; }
    .builder-grid-add.bottom { bottom: 1px; left: 50%; }
    .builder-grid-add.left { left: 1px; top: 50%; }
    .builder-grid-add.right { right: 1px; top: 50%; }
    #builder-slash-menu {
        width: min(360px, calc(100vw - 24px));
        border: 1px solid #5eead4;
        background: #fff;
        z-index: 2147483647 !important;
        box-shadow: 0 22px 48px -18px rgba(15, 23, 42, .5);
    }
    .builder-variable-option.is-active,
    .builder-variable-option:hover {
        background: #f0fdfa;
        box-shadow: inset 3px 0 0 #14b8a6;
    }
    #template-builder .builder-save-button {
        border: 1px solid #0f766e !important;
        background: linear-gradient(135deg, #0f766e, #0891b2) !important;
        color: #fff !important;
        box-shadow: 0 12px 24px -14px rgba(8, 145, 178, .85) !important;
    }
    #template-builder .builder-save-button:hover {
        border-color: #115e59 !important;
        background: linear-gradient(135deg, #115e59, #0e7490) !important;
        color: #fff !important;
        box-shadow: 0 14px 28px -12px rgba(13, 148, 136, .9) !important;
    }
    #template-builder .builder-save-button:active {
        background: #134e4a !important;
        color: #fff !important;
    }
    @media (max-width: 900px) {
        .builder-header-pair { grid-template-columns: 1fr; gap: 10px; }
    }
</style>

<div id="template-builder" class="mx-auto max-w-[1500px] px-4 py-5 sm:px-6">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="grid h-11 w-11 place-items-center rounded-xl bg-gradient-to-br from-teal-600 to-cyan-600 text-xl font-black text-white shadow-lg">T</div>
            <div>
                <h1 class="text-xl font-extrabold tracking-tight text-slate-900">{{ $version->template->name }}</h1>
                <p class="mt-1 text-sm text-slate-500">
                {{ $portalLabel }} · <span class="font-mono">{{ $version->template->feature_key }}</span>
                · version {{ $version->version_number }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <span class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1 text-xs font-bold text-amber-700">DRAFT</span>
            @if($editable)
                <form method="POST" data-turbo="false"
                      action="{{ route('export-templates.portal.builder.version', ['portal'=>$portal,'version'=>$version]) }}">
                    @csrf
                    <button class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-bold text-blue-700">
                        Tạo version mới
                    </button>
                </form>
            @endif
        </div>
    </div>

    <form id="builder-form" data-turbo="false" method="POST"
          action="{{ route('export-templates.portal.builder.update', ['portal'=>$portal,'version'=>$version]) }}">
        @csrf
        @method('PUT')
        <input type="hidden" id="schema-input" name="schema">

        <div class="grid gap-4 lg:grid-cols-[220px_minmax(0,1fr)]">
            <aside class="h-fit rounded-2xl border border-slate-200 bg-white p-3 shadow-sm lg:sticky lg:top-3">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="text-sm font-extrabold text-slate-800">Thành phần</h2>
                    <span class="text-[10px] font-bold uppercase tracking-wider text-teal-600">Kéo thả</span>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    @foreach([
                        ['text', 'T', 'Văn bản'],
                        ['heading', 'H', 'Tiêu đề'],
                        ['table', '▦', 'Bảng'],
                        ['signature', '✍', 'Ký tên'],
                        ['divider', '—', 'Phân cách'],
                        ['page_break', '↵', 'Ngắt trang'],
                        ['spacer', '↕', 'Khoảng trống'],
                    ] as [$type, $icon, $label])
                        <button type="button" data-add="{{ $type }}"
                                class="builder-palette-button rounded-xl p-2 text-center">
                            <span class="block text-lg font-black">{{ $icon }}</span>
                            <span class="block text-[11px] font-bold">{{ $label }}</span>
                        </button>
                    @endforeach
                </div>

                <div class="my-4 h-px bg-slate-200"></div>
                <h2 class="mb-2 text-sm font-extrabold text-slate-800">Biến dữ liệu</h2>
                <select id="variable-picker" data-searchable="1" data-placeholder="Tìm biến...">
                    <option value="">Tìm biến...</option>
                    @foreach($variables as $variable)
                        <option value="{{ $variable['key'] }}">
                            {{ $variable['label'] }} — {{ $variable['key'] }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-2 text-[11px] leading-relaxed text-slate-500">
                    Trong block hoặc ô bảng, gõ <strong>/</strong> để chèn biến ngay tại con trỏ.
                </p>
            </aside>

            <section class="min-w-0">
                <div id="builder-toolbar" class="mb-3 flex min-h-12 flex-wrap items-center gap-1 rounded-xl px-2 py-2 text-white">
                    <button type="button" data-format-toggle="bold" title="Đậm"
                            class="h-8 min-w-8 rounded-lg px-2 text-sm font-black hover:bg-white/15">B</button>
                    <button type="button" data-format-toggle="italic" title="Nghiêng"
                            class="h-8 min-w-8 rounded-lg px-2 text-sm italic hover:bg-white/15">I</button>
                    <button type="button" data-format-toggle="underline" title="Gạch chân"
                            class="h-8 min-w-8 rounded-lg px-2 text-sm underline hover:bg-white/15">U</button>
                    <span class="mx-1 h-6 w-px bg-white/20"></span>

                    <select data-format="align" data-native-select data-tom-select="off" title="Căn lề">
                        <option value="left">⇤</option>
                        <option value="center">↔</option>
                        <option value="right">⇥</option>
                        <option value="justify">☰</option>
                    </select>
                    <select data-format="font_size" data-native-select data-tom-select="off" title="Cỡ chữ">
                        @foreach([9,10,11,12,13,14,16,18,20,24,28,32,36] as $size)
                            <option value="{{ $size }}" @selected($size === 14)>{{ $size }}</option>
                        @endforeach
                    </select>

                    <span class="mx-1 h-6 w-px bg-white/20"></span>
                    <label class="flex h-8 items-center gap-1 rounded-lg bg-white/10 px-2 text-[11px]" title="Màu chữ">
                        A
                        <input id="toolbar-color" type="color" value="#1f2937" class="h-5 w-5 cursor-pointer rounded border-0 p-0">
                        <button id="toolbar-color-ok" type="button" class="rounded bg-teal-400 px-1.5 py-0.5 font-black text-slate-950">OK</button>
                    </label>
                    <label class="flex h-8 items-center gap-1 rounded-lg bg-white/10 px-2 text-[11px]" title="Màu nền">
                        Nền
                        <input id="toolbar-background" type="color" value="#ffffff" class="h-5 w-5 cursor-pointer rounded border-0 p-0">
                        <button id="toolbar-background-ok" type="button" class="rounded bg-teal-400 px-1.5 py-0.5 font-black text-slate-950">OK</button>
                    </label>

                    <details class="relative">
                        <summary class="flex h-8 cursor-pointer list-none items-center rounded-lg px-2 text-xs font-bold hover:bg-white/15">
                            Khoảng cách
                        </summary>
                        <div class="absolute right-0 z-40 mt-2 grid w-56 grid-cols-2 gap-2 rounded-xl border bg-white p-3 text-slate-800 shadow-2xl">
                            <label class="text-[11px]">Padding
                                <input data-format-number="padding" type="number" min="0" value="12"
                                       class="mt-1 w-full rounded border px-2 py-1">
                            </label>
                            <label class="text-[11px]">Margin
                                <input data-format-number="margin" type="number" min="0" value="0"
                                       class="mt-1 w-full rounded border px-2 py-1">
                            </label>
                            <label class="col-span-2 text-[11px]">Giãn dòng
                                <input data-format-number="line_height" type="number" min=".8" step=".1" value="1.4"
                                       class="mt-1 w-full rounded border px-2 py-1">
                            </label>
                        </div>
                    </details>

                    <span id="toolbar-status" class="ml-auto hidden rounded-lg bg-white/10 px-2 py-1 text-[11px] sm:block">
                        Chọn block hoặc ô để chỉnh
                    </span>
                </div>

                <div class="relative rounded-2xl border border-slate-300 bg-slate-200/70 shadow-inner">
                    <div class="flex items-center justify-between rounded-t-2xl border-b border-slate-300 bg-white/80 px-4 py-2 text-[11px] font-bold text-slate-500 backdrop-blur">
                        <span>TRANG A4 · SOẠN THẢO TRỰC TIẾP</span>
                        <span>Gõ / để chèn biến dữ liệu</span>
                    </div>
                    <div class="p-4">
                    <div id="builder-canvas"
                         class="mx-auto min-h-[720px] max-w-[1050px] space-y-3 bg-white px-10 py-9 shadow-xl">
                    </div>
                    </div>
                    <div id="builder-slash-menu"
                         class="fixed z-[100] hidden max-h-96 overflow-auto rounded-xl p-2">
                    </div>
                </div>

                @if($editable)
                    <div class="mt-4 flex justify-end">
                        <button type="submit"
                                class="builder-save-button rounded-xl px-6 py-2.5 text-sm font-extrabold">
                            Lưu cấu trúc template
                        </button>
                    </div>
                @endif
            </section>
        </div>
    </form>
</div>

<script>
(function () {
    function bootBuilder() {
        const root = document.getElementById('template-builder');
        if (!root || root.dataset.ready === '1') return;
        root.dataset.ready = '1';

        const canvas = document.getElementById('builder-canvas');
        const form = document.getElementById('builder-form');
        const schemaInput = document.getElementById('schema-input');
        const slashMenu = document.getElementById('builder-slash-menu');
        const toolbarStatus = document.getElementById('toolbar-status');
        const toolbar = document.getElementById('builder-toolbar');
        const variableCatalog = @json($variables);
        let schema = @json($schema ?: \Modules\ExportTemplates\Services\BuilderTemplateSchema::empty($version->template->output_format?->value ?? 'excel'));
        let selectedBlockId = null;
        let selectedEditable = null;
        let selectedContext = null;
        let savedRange = null;
        let activeSlashAnchor = null;
        let slashPositionFrame = null;
        const lifecycle = new AbortController();

        // Menu phải nằm trực tiếp dưới body để không bị stacking context của
        // canvas/layout giữ ở phía sau toolbar sticky.
        document.body.appendChild(slashMenu);
        slashMenu.style.zIndex = '2147483647';

        schema.blocks = Array.isArray(schema.blocks) ? schema.blocks : [];
        const defaultHeader = @json(
            \Modules\ExportTemplates\Services\BuilderTemplateSchema::empty(
                $version->template->output_format?->value ?? 'excel'
            )['blocks'][0]['props']
        );
        let headerBlock = schema.blocks.find(block => block.type === 'header_pair');
        if (!headerBlock) {
            schema.blocks.unshift({
                id: 'header_pair_' + Math.random().toString(36).slice(2, 9),
                type: 'header_pair',
                props: {...defaultHeader},
                children: []
            });
        } else {
            headerBlock.props = headerBlock.props || {};
            if (!(headerBlock.props.left_text || '').trim()) headerBlock.props.left_text = defaultHeader.left_text;
            if (!(headerBlock.props.right_text || '').trim()) headerBlock.props.right_text = defaultHeader.right_text;
            headerBlock.props.font_size = Number(headerBlock.props.font_size) || defaultHeader.font_size;
            headerBlock.props.bold = true;
            const headerIndex = schema.blocks.indexOf(headerBlock);
            if (headerIndex > 0) {
                schema.blocks.splice(headerIndex, 1);
                schema.blocks.unshift(headerBlock);
            }
        }

        const blockId = () => 'block_' + Math.random().toString(36).slice(2, 10);
        const selectedBlock = () => schema.blocks.find(block => block.id === selectedBlockId);

        function sync() {
            schemaInput.value = JSON.stringify(schema);
        }

        function setCaretEnd(element) {
            element.focus();
            const range = document.createRange();
            range.selectNodeContents(element);
            range.collapse(false);
            const selection = window.getSelection();
            selection.removeAllRanges();
            selection.addRange(range);
            savedRange = range.cloneRange();
        }

        function rememberCaret(element) {
            const selection = window.getSelection();
            if (!selection || selection.rangeCount === 0) return;
            const range = selection.getRangeAt(0);
            if (element.contains(range.commonAncestorContainer)) {
                savedRange = range.cloneRange();
            }
        }

        function insertTokenAtCaret(token) {
            if (!selectedEditable || !selectedEditable.isConnected) return false;
            selectedEditable.focus();
            const selection = window.getSelection();
            const range = savedRange && selectedEditable.contains(savedRange.commonAncestorContainer)
                ? savedRange.cloneRange()
                : document.createRange();
            if (!savedRange || !selectedEditable.contains(range.commonAncestorContainer)) {
                range.selectNodeContents(selectedEditable);
                range.collapse(false);
            }
            range.deleteContents();
            const node = document.createTextNode(token);
            range.insertNode(node);
            range.setStartAfter(node);
            range.collapse(true);
            selection.removeAllRanges();
            selection.addRange(range);
            savedRange = range.cloneRange();
            selectedEditable.dispatchEvent(new Event('input', {bubbles: true}));
            return true;
        }

        function selectedStyle(block = selectedBlock(), context = selectedContext, create = false) {
            if (!block) return null;
            block.props = block.props || {};
            if (context?.kind === 'cell') {
                block.props.cell_styles = Array.isArray(block.props.cell_styles) ? block.props.cell_styles : [];
                block.props.cell_styles[context.row] = Array.isArray(block.props.cell_styles[context.row])
                    ? block.props.cell_styles[context.row]
                    : [];
                if (create && !block.props.cell_styles[context.row][context.col]) {
                    block.props.cell_styles[context.row][context.col] = {};
                }
                return block.props.cell_styles[context.row][context.col] || {};
            }
            if (context?.kind === 'header') {
                const key = context.side + '_style';
                if (create && !block.props[key]) block.props[key] = {};
                return block.props[key] || {};
            }
            return block.props;
        }

        function refreshToolbar() {
            const values = {...(selectedBlock()?.props || {}), ...(selectedStyle() || {})};
            root.querySelectorAll('[data-format-toggle]').forEach(button => {
                button.classList.toggle('is-active', Boolean(values[button.dataset.formatToggle]));
            });
            root.querySelectorAll('select[data-format]').forEach(select => {
                const value = values[select.dataset.format];
                if (value !== undefined && value !== null && value !== '') select.value = value;
            });
            root.querySelectorAll('[data-format-number]').forEach(input => {
                const value = values[input.dataset.formatNumber];
                if (value !== undefined && value !== null && value !== '') input.value = value;
            });
            if (values.color) root.querySelector('#toolbar-color').value = values.color;
            if (values.background) root.querySelector('#toolbar-background').value = values.background;
        }

        function selectBlock(block, element, context = null) {
            selectedBlockId = block.id;
            selectedEditable = element;
            selectedContext = context;
            canvas.querySelectorAll('.builder-block').forEach(item => item.classList.toggle('is-selected', item.dataset.id === block.id));
            toolbarStatus.textContent = context?.kind === 'cell'
                ? `Ô ${context.row + 1}:${context.col + 1}`
                : (context?.kind === 'header' ? `Header ${context.side === 'left' ? 'trái' : 'phải'}` : block.type);
            refreshToolbar();
        }

        function applyBlockStyle(element, props) {
            element.style.fontSize = (Number(props.font_size) || 14) + 'px';
            element.style.lineHeight = Number(props.line_height) || 1.4;
            element.style.textAlign = props.align || 'left';
            element.style.fontWeight = props.bold ? '700' : '400';
            element.style.fontStyle = props.italic ? 'italic' : 'normal';
            element.style.textDecoration = props.underline ? 'underline' : 'none';
            element.style.color = props.color || '';
            element.style.backgroundColor = props.background || '';
            element.style.padding = (Number(props.padding) || 0) + 'px';
            element.style.margin = (Number(props.margin) || 0) + 'px';
        }

        function bindEditable(element, block, getter, setter, cell = null) {
            element.contentEditable = 'true';
            element.spellcheck = false;
            element.classList.add('builder-editable');
            element.textContent = getter() || '';
            element.addEventListener('focus', () => {
                selectBlock(block, element, cell);
                rememberCaret(element);
            });
            element.addEventListener('click', event => {
                event.stopPropagation();
                selectBlock(block, element, cell);
                rememberCaret(element);
            });
            element.addEventListener('input', () => {
                setter(element.innerText);
                sync();
                rememberCaret(element);
                updateSlashMenu(element, block, setter);
            });
            element.addEventListener('keyup', () => rememberCaret(element));
            element.addEventListener('mouseup', () => rememberCaret(element));
            element.addEventListener('keydown', event => {
                if (event.key === '/') setTimeout(() => updateSlashMenu(element, block, setter), 0);
                if (event.key === 'Escape') hideSlashMenu();
            });
        }

        function hideSlashMenu() {
            slashMenu.classList.add('hidden');
            slashMenu.innerHTML = '';
            activeSlashAnchor = null;
            if (slashPositionFrame !== null) {
                cancelAnimationFrame(slashPositionFrame);
                slashPositionFrame = null;
            }
        }

        function positionSlashMenu() {
            if (!activeSlashAnchor?.isConnected || slashMenu.classList.contains('hidden')) {
                if (!activeSlashAnchor?.isConnected) hideSlashMenu();
                return;
            }

            const elementRect = activeSlashAnchor.getBoundingClientRect();
            const caretRect = savedRange && activeSlashAnchor.contains(savedRange.commonAncestorContainer)
                ? savedRange.getBoundingClientRect()
                : null;
            const hasCaretRect = Boolean(caretRect?.width || caretRect?.height);
            const anchorLeft = hasCaretRect ? caretRect.left : elementRect.left;
            const anchorBottom = hasCaretRect ? caretRect.bottom : elementRect.bottom;
            const menuRect = slashMenu.getBoundingClientRect();
            const toolbarRect = toolbar?.getBoundingClientRect();
            const minimumTop = toolbarRect
                ? Math.max(12, Math.min(window.innerHeight - 12, toolbarRect.bottom + 8))
                : 12;
            const left = Math.min(
                Math.max(12, anchorLeft),
                Math.max(12, window.innerWidth - menuRect.width - 12)
            );
            let top = Math.max(minimumTop, anchorBottom + 8);
            if (top + menuRect.height > window.innerHeight - 12) {
                const above = elementRect.top - menuRect.height - 8;
                top = above >= minimumTop
                    ? above
                    : Math.max(minimumTop, window.innerHeight - menuRect.height - 12);
            }
            slashMenu.style.left = left + 'px';
            slashMenu.style.top = top + 'px';
        }

        function scheduleSlashMenuPosition() {
            if (!activeSlashAnchor || slashMenu.classList.contains('hidden') || slashPositionFrame !== null) return;
            slashPositionFrame = requestAnimationFrame(() => {
                slashPositionFrame = null;
                positionSlashMenu();
            });
        }

        function escapeVariableText(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function variableExample(item) {
            if (item.example === null || item.example === undefined || item.example === '') return '';
            const value = typeof item.example === 'object'
                ? JSON.stringify(item.example)
                : String(item.example);

            return `<span class="mt-1 block rounded bg-slate-50 px-2 py-1 text-[10px] text-slate-600">
                Ví dụ xuất ra: <strong>${escapeVariableText(value)}</strong>
            </span>`;
        }

        function updateSlashMenu(element, block, setter) {
            const text = element.innerText || '';
            const match = text.match(/\/([^\s/]*)$/);
            if (!match) {
                hideSlashMenu();
                return;
            }
            const typedCommand = match[1].toLowerCase();
            // `/thembien` là lệnh mở toàn bộ danh sách, không phải từ khóa tìm kiếm.
            const query = ['thembien', 'them-bien', 'bien'].includes(typedCommand) ? '' : typedCommand;
            const catalog = Array.isArray(variableCatalog) ? variableCatalog : [];
            const items = catalog
                .filter(item => `${item.label} ${item.key} ${item.description || ''}`.toLowerCase().includes(query))
                .slice(0, 30);
            slashMenu.innerHTML = items.length
                ? items.map(item =>
                    `<button type="button" class="builder-variable-option block w-full rounded-lg px-3 py-2.5 text-left">
                        <span class="mb-1 flex items-center justify-between gap-2">
                            <strong class="text-xs text-slate-800">${escapeVariableText(item.label)}</strong>
                            <small class="rounded bg-teal-50 px-1.5 py-0.5 text-[9px] font-bold text-teal-700">${escapeVariableText(item.group || 'Dữ liệu')}</small>
                        </span>
                        <span class="block font-mono text-[10px] font-semibold text-teal-700">${escapeVariableText(item.key)}</span>
                        <span class="mt-1 block text-[10px] leading-relaxed text-slate-500">${escapeVariableText(item.description || '')}</span>
                        ${variableExample(item)}
                    </button>`
                ).join('')
                : `<div class="px-3 py-3 text-xs text-slate-500">
                    ${catalog.length ? 'Không tìm thấy biến phù hợp.' : 'Feature này chưa cung cấp biến dữ liệu.'}
                   </div>`;

            activeSlashAnchor = element;
            slashMenu.classList.remove('hidden');
            positionSlashMenu();

            slashMenu.querySelectorAll('button').forEach((button, index) => {
                button.addEventListener('mousedown', event => event.preventDefault());
                button.addEventListener('click', () => {
                    const token = '{' + '{' + items[index].key + '}' + '}';
                    const replaced = (element.innerText || '').replace(/\/[^\s/]*$/, token + ' ');
                    element.innerText = replaced;
                    setter(replaced);
                    sync();
                    hideSlashMenu();
                    setCaretEnd(element);
                });
            });
        }

        function renderHeaderPair(block) {
            const props = block.props || (block.props = {});
            const wrapper = document.createElement('div');
            wrapper.className = 'builder-block builder-header-pair px-2 py-3';
            wrapper.dataset.id = block.id;

            const left = document.createElement('div');
            left.className = 'builder-header-cell';
            left.dataset.headerSide = 'left';
            left.dataset.placeholder = 'Khối header trái';
            bindEditable(left, block, () => props.left_text, value => props.left_text = value, {kind: 'header', side: 'left'});

            const right = document.createElement('div');
            right.className = 'builder-header-cell';
            right.dataset.headerSide = 'right';
            right.dataset.placeholder = 'Khối header phải';
            bindEditable(right, block, () => props.right_text, value => props.right_text = value, {kind: 'header', side: 'right'});

            applyBlockStyle(left, {...props, ...(props.left_style || {}), align: 'center', bold: true});
            applyBlockStyle(right, {...props, ...(props.right_style || {}), align: 'center', bold: true});
            wrapper.append(left, right);
            return wrapper;
        }

        function renderTable(block) {
            const props = block.props || (block.props = {});
            const rows = Math.max(1, Math.min(50, Number(props.rows) || 2));
            const columns = Math.max(1, Math.min(26, Number(props.columns) || 4));
            props.cell_text = Array.isArray(props.cell_text) ? props.cell_text : [];
            props.cell_styles = Array.isArray(props.cell_styles) ? props.cell_styles : [];

            const wrapper = document.createElement('div');
            wrapper.className = 'builder-block builder-table-shell';
            wrapper.dataset.id = block.id;

            const table = document.createElement('table');
            table.className = 'w-full border-collapse';
            for (let row = 0; row < rows; row++) {
                const tr = document.createElement('tr');
                for (let column = 0; column < columns; column++) {
                    props.cell_text[row] = Array.isArray(props.cell_text[row]) ? props.cell_text[row] : [];
                    const td = document.createElement('td');
                    td.className = 'builder-grid-cell';
                    td.dataset.row = row;
                    td.dataset.col = column;
                    td.style.height = (Number(props.row_height) || 34) + 'px';
                    td.style.width = (Number(props.column_width) || 120) + 'px';
                    td.dataset.placeholder = row === 0 ? 'Tiêu đề cột' : 'Nhập nội dung hoặc / để chèn biến';
                    props.cell_styles[row] = Array.isArray(props.cell_styles[row]) ? props.cell_styles[row] : [];
                    bindEditable(
                        td,
                        block,
                        () => props.cell_text[row][column] || '',
                        value => props.cell_text[row][column] = value,
                        {kind: 'cell', row, col: column}
                    );
                    applyBlockStyle(td, {...props, ...(props.cell_styles[row][column] || {})});
                    tr.appendChild(td);
                }
                table.appendChild(tr);
            }
            wrapper.appendChild(table);

            [
                ['top', '＋', () => { props.rows = rows + 1; draw(); }],
                ['bottom', '＋', () => { props.rows = rows + 1; draw(); }],
                ['left', '＋', () => { props.columns = columns + 1; draw(); }],
                ['right', '＋', () => { props.columns = columns + 1; draw(); }],
            ].forEach(([position, label, handler]) => {
                const button = document.createElement('button');
                button.type = 'button';
                button.className = `builder-grid-add ${position}`;
                button.title = position === 'top' || position === 'bottom' ? 'Thêm dòng' : 'Thêm cột';
                button.textContent = label;
                button.addEventListener('click', event => {
                    event.stopPropagation();
                    handler();
                });
                wrapper.appendChild(button);
            });
            return wrapper;
        }

        function renderBlock(block) {
            if (block.type === 'header_pair') return renderHeaderPair(block);
            if (block.type === 'table') return renderTable(block);

            const props = block.props || (block.props = {});
            const element = document.createElement('div');
            element.className = 'builder-block px-3 py-2';
            element.dataset.id = block.id;

            if (block.type === 'divider') {
                element.innerHTML = '<div class="my-2 border-t-2 border-slate-400"></div>';
                return element;
            }
            if (block.type === 'page_break') {
                element.className += ' border border-dashed border-amber-300 bg-amber-50 text-center text-xs font-bold text-amber-700';
                element.textContent = '— NGẮT TRANG —';
                return element;
            }
            if (block.type === 'spacer') {
                element.className += ' bg-slate-50';
                element.style.height = (Number(props.height) || 36) + 'px';
                return element;
            }

            element.dataset.placeholder = block.type === 'heading' ? 'Nhập tiêu đề...' : 'Nhập nội dung...';
            bindEditable(element, block, () => props.text, value => props.text = value);
            applyBlockStyle(element, props);
            return element;
        }

        function draw() {
            canvas.innerHTML = '';
            schema.blocks.forEach(block => canvas.appendChild(renderBlock(block)));
            sync();
        }

        function applyFormat(key, value) {
            const block = selectedBlock();
            if (!block) return;
            const style = selectedStyle(block, selectedContext, true);
            style[key] = value;
            draw();
            let selector = `[data-id="${block.id}"]`;
            if (selectedContext?.kind === 'cell') {
                selector += ` [data-row="${selectedContext.row}"][data-col="${selectedContext.col}"]`;
            } else if (selectedContext?.kind === 'header') {
                selector += ` [data-header-side="${selectedContext.side}"]`;
            }
            const target = canvas.querySelector(selector);
            const wrapper = canvas.querySelector(`[data-id="${block.id}"]`);
            wrapper?.classList.add('is-selected');
            selectedEditable = target || wrapper;
            refreshToolbar();
        }

        root.querySelectorAll('[data-add]').forEach(button => {
            button.addEventListener('click', () => {
                const type = button.dataset.add;
                const block = {
                    id: blockId(),
                    type,
                    props: {text: type === 'heading' ? 'Tiêu đề mới' : (type === 'signature' ? 'Người ký' : '')},
                    children: []
                };
                if (type === 'table') {
                    block.props = {rows: 2, columns: 4, row_height: 34, column_width: 120, cell_text: []};
                }
                schema.blocks.push(block);
                selectedBlockId = block.id;
                draw();
                const target = canvas.querySelector(`[data-id="${block.id}"] [contenteditable], [data-id="${block.id}"][contenteditable]`);
                if (target) setCaretEnd(target);
            });
        });

        root.querySelectorAll('[data-format-toggle]').forEach(button => {
            button.addEventListener('click', () => {
                const key = button.dataset.formatToggle;
                const block = selectedBlock();
                applyFormat(key, !(block?.props?.[key]));
            });
        });
        root.querySelectorAll('select[data-format]').forEach(select => {
            select.addEventListener('change', () => {
                applyFormat(select.dataset.format, select.dataset.format === 'font_size' ? Number(select.value) : select.value);
            });
        });
        root.querySelectorAll('[data-format-number]').forEach(input => {
            input.addEventListener('change', () => applyFormat(input.dataset.formatNumber, Number(input.value)));
        });
        root.querySelector('#toolbar-color-ok').addEventListener('click', () => {
            applyFormat('color', root.querySelector('#toolbar-color').value);
        });
        root.querySelector('#toolbar-background-ok').addEventListener('click', () => {
            applyFormat('background', root.querySelector('#toolbar-background').value);
        });

        const variablePicker = root.querySelector('#variable-picker');
        variablePicker.addEventListener('change', () => {
            if (!variablePicker.value) return;
            const token = '{' + '{' + variablePicker.value + '}' + '} ';
            insertTokenAtCaret(token);
            if (variablePicker.tomselect) {
                variablePicker.tomselect.clear(true);
            } else {
                variablePicker.value = '';
            }
        });

        document.addEventListener('click', event => {
            if (!slashMenu.contains(event.target) && !selectedEditable?.contains(event.target)) hideSlashMenu();
        }, {signal: lifecycle.signal});
        window.addEventListener('resize', scheduleSlashMenuPosition, {signal: lifecycle.signal});
        window.addEventListener('scroll', scheduleSlashMenuPosition, {capture: true, passive: true, signal: lifecycle.signal});
        document.addEventListener('turbo:before-cache', () => {
            lifecycle.abort();
            slashMenu.remove();
            root.removeAttribute('data-ready');
        }, {once: true, signal: lifecycle.signal});
        form.addEventListener('submit', sync);
        draw();
    }

    // Script nằm sau toàn bộ markup của Builder và Turbo cũng evaluate script
    // sau khi thay body, vì vậy boot ngay giúp tránh tích lũy listener cũ.
    bootBuilder();
})();
</script>
@endsection
