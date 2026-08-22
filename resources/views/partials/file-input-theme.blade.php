<style>
    .file-input-field {
        position: relative;
        width: 100%;
    }

    .file-input-ui {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-height: 44px;
        padding: 0.5rem 0.875rem;
        border: 1px dashed #c5ccd8;
        border-radius: 0.625rem;
        background: rgba(250, 248, 244, 0.92);
        box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.03);
        transition: border-color 0.2s ease, box-shadow 0.28s ease, background-color 0.2s ease;
        cursor: pointer;
    }

    .file-input-field:hover .file-input-ui,
    .file-input-field:focus-within .file-input-ui {
        border-color: rgba(78, 161, 255, 0.45);
        background: rgba(238, 246, 255, 0.35);
    }

    .file-input-field:focus-within .file-input-ui {
        border-color: #4ea1ff;
        box-shadow: 0 0 0 3px rgba(78, 161, 255, 0.22), inset 0 1px 2px rgba(15, 23, 42, 0.03);
    }

    .file-input-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        flex-shrink: 0;
        padding: 0.375rem 0.75rem;
        border-radius: 0.5rem;
        border: 1px solid #bfdbfe;
        background: #eef6ff;
        color: #3580d6;
        font-size: 0.8125rem;
        font-weight: 600;
        line-height: 1.25rem;
        transition: background-color 0.15s ease, box-shadow 0.2s ease;
    }

    .file-input-field:hover .file-input-btn {
        background: #dbeafe;
        box-shadow: 0 0 0 1px rgba(78, 161, 255, 0.15);
    }

    .file-input-name {
        flex: 1;
        min-width: 0;
        font-size: 0.875rem;
        color: #6b7280;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .file-input-name.has-file {
        color: #1f2937;
        font-weight: 500;
    }

    .file-input-native {
        position: absolute !important;
        inset: 0 !important;
        width: 100% !important;
        height: 100% !important;
        opacity: 0 !important;
        cursor: pointer !important;
        z-index: 2;
    }

    .file-input-field.is-disabled .file-input-ui {
        background: #f3f4f6;
        cursor: not-allowed;
        opacity: 0.85;
    }
</style>
<script>
(function () {
    function shouldSkipFileInput(input) {
        if (!input || input.type !== 'file') return true;
        if (input.dataset.nativeFile !== undefined) return true;
        if (input.classList.contains('file-input-native--ready')) return true;
        if (input.classList.contains('hidden') && input.closest('[id*="drop"], [id*="Drop"], .drop-zone')) return true;
        if (input.closest('#dropZone, [data-dropzone], .file-drop-zone')) return true;
        return false;
    }

    function updateFileLabel(input) {
        const field = input.closest('.file-input-field');
        if (!field) return;
        const nameEl = field.querySelector('.file-input-name');
        if (!nameEl) return;

        const file = input.files && input.files[0];
        if (file) {
            nameEl.textContent = file.name;
            nameEl.classList.add('has-file');
        } else {
            nameEl.textContent = nameEl.dataset.placeholder || 'Chưa chọn file';
            nameEl.classList.remove('has-file');
        }
    }

    function wrapFileInput(input) {
        if (shouldSkipFileInput(input)) return;

        const field = document.createElement('div');
        field.className = 'file-input-field';
        if (input.disabled) field.classList.add('is-disabled');

        const ui = document.createElement('div');
        ui.className = 'file-input-ui';

        const btn = document.createElement('span');
        btn.className = 'file-input-btn';
        btn.innerHTML = '<i class="bi bi-folder2-open"></i> Chọn file';

        const name = document.createElement('span');
        name.className = 'file-input-name';
        name.dataset.placeholder = input.dataset.placeholder || 'Chưa chọn file';
        name.textContent = name.dataset.placeholder;

        ui.appendChild(btn);
        ui.appendChild(name);

        input.parentNode.insertBefore(field, input);
        field.appendChild(ui);
        field.appendChild(input);

        input.classList.add('file-input-native', 'file-input-native--ready');
        input.addEventListener('change', function () { updateFileLabel(input); });
        updateFileLabel(input);
    }

    window.initFileInputs = function (root) {
        const scope = root || document.getElementById('admin-content') || document;
        scope.querySelectorAll('input[type="file"]').forEach(wrapFileInput);
    };

    function bootFileInputs() {
        window.initFileInputs(document.getElementById('admin-content') || document);
    }

    if (!window.__fileInputBound) {
        window.__fileInputBound = true;
        document.addEventListener('turbo:load', bootFileInputs);
        document.addEventListener('DOMContentLoaded', bootFileInputs);
    } else {
        bootFileInputs();
    }
})();
</script>