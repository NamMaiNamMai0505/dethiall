@props([
    'name' => 'evidence',
    'accept' => '',
    'help' => '',
    'currentUrl' => null,
    'currentLabel' => 'Xem minh chứng hiện tại',
])

@php($fieldId = 'file-drop-'.$name)

<div {{ $attributes }}>
    @if($currentUrl)
        <p class="mb-2 text-sm text-slate-600">
            File hiện tại: <a href="{{ $currentUrl }}" target="_blank" class="text-teal-600 hover:underline">{{ $currentLabel }}</a>
        </p>
    @endif

    <label for="{{ $fieldId }}"
           class="lms-file-drop group flex cursor-pointer flex-col items-center justify-center gap-1.5 rounded-xl border-2 border-dashed border-slate-200 bg-slate-50 px-4 py-6 text-center transition-colors hover:border-teal-300 hover:bg-teal-50 @error($name) !border-rose-300 @enderror">
        <i class="bi bi-cloud-arrow-up text-2xl text-slate-400 group-hover:text-teal-600"></i>
        <span class="text-sm font-medium text-slate-700">
            <span class="text-teal-700 underline">Chọn file</span> hoặc kéo thả vào đây
        </span>
        <span class="lms-file-drop-name text-xs text-slate-500">Chưa chọn file</span>
        <input type="file" id="{{ $fieldId }}" name="{{ $name }}" accept="{{ $accept }}" class="sr-only">
    </label>

    @if($help)
        <p class="mt-1 text-xs text-slate-500">{{ $help }}</p>
    @endif
    @error($name)
        <p class="mt-1 text-sm text-rose-600">{{ $message }}</p>
    @enderror
</div>

@once
    @push('scripts')
    <script>
    (function () {
        if (window.__lmsFileDropBound) return;
        window.__lmsFileDropBound = true;

        function bind(label) {
            if (label.dataset.bound) return;
            label.dataset.bound = '1';
            const input = label.querySelector('input[type="file"]');
            const nameEl = label.querySelector('.lms-file-drop-name');
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
            document.querySelectorAll('.lms-file-drop').forEach(bind);
        }

        document.addEventListener('DOMContentLoaded', bindAll);
        if (document.readyState !== 'loading') bindAll();
        document.addEventListener('turbo:load', bindAll);
    })();
    </script>
    @endpush
@endonce
