{{--
  Editor header/footer + preview realtime trước khi xuất Word.
  Usage: include with $editorId (default wordExportEditor)
--}}
@php
    $editorId = $editorId ?? 'wordExportEditor';
    $defaultTitle = $defaultTitle ?? 'BÁO CÁO';
    $headerLeft = old('header_left', \App\Support\WordExportTemplate::defaultHeaderLeft());
    $headerRight = old('header_right', \App\Support\WordExportTemplate::defaultHeaderRight());
    $footerLeft = old('footer_left', \App\Support\WordExportTemplate::defaultFooterLeft());
    $footerRight = old('footer_right', \App\Support\WordExportTemplate::defaultFooterRight());
@endphp

<div id="{{ $editorId }}" class="word-export-editor space-y-4" data-word-export-editor>
    <div class="rounded-lg border border-blue-100 bg-blue-50/60 px-3 py-2 text-xs text-blue-900">
        <i class="bi bi-info-circle mr-1"></i>
        Chỉnh header / tiêu đề / footer bên trái — preview bên phải cập nhật realtime. Bảng dữ liệu sẽ được hệ thống điền khi xuất.
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-4">
        {{-- Editors --}}
        <div class="space-y-3">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Header trái</label>
                    <textarea data-field="header_left" rows="4"
                              class="w-full text-sm border rounded-lg p-2 font-serif leading-snug">{{ $headerLeft }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Header phải</label>
                    <textarea data-field="header_right" rows="4"
                              class="w-full text-sm border rounded-lg p-2 font-serif leading-snug">{{ $headerRight }}</textarea>
                </div>
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">Tiêu đề báo cáo (giữa)</label>
                <input type="text" data-field="title" value="{{ $defaultTitle }}"
                       class="w-full text-sm border rounded-lg p-2 font-semibold text-center">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Footer trái <span class="font-normal text-slate-400">(tuỳ chọn)</span></label>
                    <textarea data-field="footer_left" rows="3" placeholder="VD: NGƯỜI LẬP"
                              class="w-full text-sm border rounded-lg p-2 font-serif">{{ $footerLeft }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Footer phải <span class="font-normal text-slate-400">(tuỳ chọn)</span></label>
                    <textarea data-field="footer_right" rows="3" placeholder="VD: TRƯỞNG PHÒNG"
                              class="w-full text-sm border rounded-lg p-2 font-serif">{{ $footerRight }}</textarea>
                </div>
            </div>
        </div>

        {{-- Preview --}}
        <div class="rounded-xl border-2 border-dashed border-slate-300 bg-white p-4 shadow-inner min-h-[22rem]">
            <div class="text-[10px] uppercase tracking-wider text-slate-400 mb-2">Preview Word</div>
            <div class="grid grid-cols-2 gap-3 border-b border-slate-200 pb-3">
                <div data-preview="header_left" class="text-center text-[11px] font-bold font-serif whitespace-pre-line leading-tight text-slate-800"></div>
                <div data-preview="header_right" class="text-center text-[11px] font-bold font-serif whitespace-pre-line leading-tight text-slate-800"></div>
            </div>
            <div data-preview="title" class="text-center font-bold text-sm mt-4 mb-4 text-slate-900 uppercase"></div>
            <div class="border border-slate-200 rounded overflow-hidden">
                <div class="bg-slate-100 text-[10px] font-semibold text-slate-600 px-2 py-1">Bảng dữ liệu (tự điền khi xuất)</div>
                <div class="p-3 text-xs text-slate-400 italic text-center">… nội dung lịch / kế hoạch …</div>
            </div>
            <div class="grid grid-cols-2 gap-3 mt-6">
                <div data-preview="footer_left" class="text-center text-[11px] font-bold font-serif whitespace-pre-line text-slate-800"></div>
                <div data-preview="footer_right" class="text-center text-[11px] font-bold font-serif whitespace-pre-line text-slate-800"></div>
            </div>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
(function () {
    function bindWordExportEditor(root) {
        if (!root || root.dataset.bound === '1') return;
        root.dataset.bound = '1';
        const fields = ['header_left', 'header_right', 'title', 'footer_left', 'footer_right'];
        function sync() {
            fields.forEach(function (f) {
                const input = root.querySelector('[data-field="' + f + '"]');
                const prev = root.querySelector('[data-preview="' + f + '"]');
                if (input && prev) prev.textContent = input.value || '';
            });
        }
        fields.forEach(function (f) {
            const input = root.querySelector('[data-field="' + f + '"]');
            if (input) {
                input.addEventListener('input', sync);
                input.addEventListener('change', sync);
            }
        });
        sync();
        root.getMeta = function () {
            const meta = {};
            fields.forEach(function (f) {
                const input = root.querySelector('[data-field="' + f + '"]');
                meta[f] = input ? input.value : '';
            });
            return meta;
        };
    }
    window.bindWordExportEditor = bindWordExportEditor;
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelectorAll('[data-word-export-editor]').forEach(bindWordExportEditor);
    });
    document.addEventListener('turbo:load', function () {
        document.querySelectorAll('[data-word-export-editor]').forEach(function (el) {
            el.dataset.bound = '';
            bindWordExportEditor(el);
        });
    });
})();
</script>
@endpush
@endonce
