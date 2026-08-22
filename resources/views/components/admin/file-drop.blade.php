@props([
    'name' => 'file',
    'accept' => '',
    'required' => false,
    'help' => '',
])

@php($fieldId = 'file-drop-'.$name)

<div {{ $attributes }}>
    <label for="{{ $fieldId }}"
           class="admin-file-drop group flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-4 py-8 text-center transition-colors hover:border-teal-400 hover:bg-teal-50 @error($name) !border-rose-400 @enderror">
        <i class="bi bi-cloud-arrow-up text-3xl text-slate-400 group-hover:text-teal-600"></i>
        <span class="text-sm font-medium text-slate-700">
            <span class="text-teal-700 underline">Chọn file</span> hoặc kéo thả vào đây
        </span>
        <span class="admin-file-drop-name text-xs text-slate-500">Chưa chọn file</span>
        {{-- data-native-file: bao initFileInputs (partials/file-input-theme.blade.php)
             bo qua input nay - khong thi no tu boc them 1 lop UI "Chon file"
             mac dinh chong len khung keo-tha tu viet o day. --}}
        <input type="file" id="{{ $fieldId }}" name="{{ $name }}" accept="{{ $accept }}"
               data-native-file
               @if($required) required @endif class="sr-only">
    </label>

    @if($help)
        <p class="mt-1 text-xs text-slate-500">{{ $help }}</p>
    @endif
    @error($name)
        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
    @enderror
</div>

@once
    @push('scripts')
    <script>
    (function () {
        if (window.__adminFileDropBound) return;
        window.__adminFileDropBound = true;

        function bind(label) {
            if (label.dataset.bound) return;
            label.dataset.bound = '1';
            const input = label.querySelector('input[type="file"]');
            const nameEl = label.querySelector('.admin-file-drop-name');
            if (!input || !nameEl) return;

            input.addEventListener('change', function () {
                nameEl.textContent = input.files && input.files.length ? input.files[0].name : 'Chưa chọn file';
            });

            ['dragover', 'dragenter'].forEach(function (evt) {
                label.addEventListener(evt, function (e) {
                    e.preventDefault();
                    label.classList.add('border-teal-400', 'bg-teal-50');
                });
            });
            ['dragleave', 'dragend'].forEach(function (evt) {
                label.addEventListener(evt, function () {
                    label.classList.remove('border-teal-400', 'bg-teal-50');
                });
            });
            label.addEventListener('drop', function (e) {
                e.preventDefault();
                label.classList.remove('border-teal-400', 'bg-teal-50');
                if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
                    input.files = e.dataTransfer.files;
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }

        function bindAll() {
            document.querySelectorAll('.admin-file-drop').forEach(bind);
        }

        document.addEventListener('DOMContentLoaded', bindAll);
        if (document.readyState !== 'loading') bindAll();
        document.addEventListener('turbo:load', bindAll);
    })();
    </script>
    @endpush
@endonce
