{{--
    Tìm kiếm realtime cho các form GET tự viết (không dùng <x-filter-form>).
    Gắn `data-live-search="1"` vào input text/search bên trong form GET —
    form sẽ tự submit khi gõ (debounce ~400ms), giữ focus lại ô sau khi
    trang nạp lại (qua sessionStorage, vì visit GET thường thay cả <body>).
--}}
@once
@push('scripts')
<script>
(function () {
    if (window.__liveSearchBound) return;
    window.__liveSearchBound = true;

    function submitForm(form) {
        const page = form.querySelector('[name="page"]');
        if (page) page.remove();
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }

    function bind(input) {
        if (input.dataset.liveSearchBound === '1') return;
        input.dataset.liveSearchBound = '1';
        const form = input.closest('form');
        if (!form) return;

        let t = null;
        input.addEventListener('input', function () {
            clearTimeout(t);
            t = setTimeout(function () {
                try {
                    sessionStorage.setItem('__liveSearchFocus', input.name || '');
                } catch (e) { /* ignore */ }
                submitForm(form);
            }, 400);
        });
    }

    function restoreFocus() {
        let name;
        try {
            name = sessionStorage.getItem('__liveSearchFocus');
        } catch (e) {
            return;
        }
        if (!name) return;
        try {
            sessionStorage.removeItem('__liveSearchFocus');
        } catch (e) { /* ignore */ }
        const input = document.querySelector('[data-live-search][name="' + name + '"]');
        if (!input) return;
        input.focus();
        const len = input.value.length;
        try {
            input.setSelectionRange(len, len);
        } catch (e) { /* ignore */ }
    }

    function boot() {
        document.querySelectorAll('[data-live-search]').forEach(bind);
        restoreFocus();
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
