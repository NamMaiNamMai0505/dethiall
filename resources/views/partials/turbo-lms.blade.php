{{--
  Turbo Drive cho /lms/hoc — chuyển trang không full reload (hết flash trắng).
  Không fade opacity nội dung.
--}}
<style>
    .lms-turbo-progress {
        position: fixed;
        top: 0;
        left: 0;
        height: 2px;
        width: 100%;
        background: linear-gradient(90deg, #14b8a6, #0d9488);
        z-index: 10050;
        transform-origin: left;
        transform: scaleX(0);
        opacity: 0;
        pointer-events: none;
    }
    .lms-turbo-progress.is-active {
        opacity: 1;
        animation: lms-turbo-bar 0.65s cubic-bezier(0.4, 0, 0.2, 1) forwards;
    }
    @keyframes lms-turbo-bar {
        0%   { transform: scaleX(0); }
        45%  { transform: scaleX(0.55); }
        100% { transform: scaleX(0.92); }
    }
</style>
<div id="lms-turbo-progress" class="lms-turbo-progress" hidden aria-hidden="true"></div>

<script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@8.0.12/dist/turbo.es2017-esm.js" type="module"></script>

<script>
(function () {
    if (window.__turboLmsBound) return;
    window.__turboLmsBound = true;

    function progressEl() {
        return document.getElementById('lms-turbo-progress');
    }

    function startProgress() {
        const el = progressEl();
        if (!el) return;
        el.hidden = false;
        el.classList.remove('is-active');
        void el.offsetWidth;
        el.classList.add('is-active');
    }

    function stopProgress() {
        const el = progressEl();
        if (!el) return;
        el.classList.remove('is-active');
        el.style.transform = 'scaleX(1)';
        el.style.opacity = '1';
        window.setTimeout(function () {
            el.style.transform = '';
            el.style.opacity = '';
            el.hidden = true;
        }, 100);
    }

    function syncHeaderH() {
        const h = document.getElementById('lms-header');
        if (h) {
            document.documentElement.style.setProperty('--lms-header-h', h.offsetHeight + 'px');
        }
    }

    /**
     * Gỡ khóa scroll dính từ modal (overflow:hidden inline) / class h-screen admin
     * khi Turbo giữ lại body attributes hoặc modal đóng không sạch.
     */
    function unlockLmsScroll() {
        var html = document.documentElement;
        var body = document.body;
        if (!html || !body) return;
        if (!html.classList.contains('lms-html') && !body.classList.contains('lms-shell')) {
            return;
        }

        // Class khóa (modal đang mở thì giữ — chỉ clear khi không còn modal)
        var modalOpen = !!(
            document.querySelector('#confirm-modal.is-open') ||
            document.querySelector('#lms-qr-modal.is-open') ||
            document.querySelector('.lms-modal-backdrop.is-open')
        );
        if (!modalOpen) {
            html.classList.remove('lms-scroll-lock', 'overflow-hidden', 'h-full');
            body.classList.remove('lms-scroll-lock', 'overflow-hidden', 'h-screen', 'h-full');
            try {
                html.style.removeProperty('overflow');
                html.style.removeProperty('overflow-x');
                html.style.removeProperty('overflow-y');
                html.style.removeProperty('height');
                html.style.removeProperty('max-height');
                body.style.removeProperty('overflow');
                body.style.removeProperty('overflow-x');
                body.style.removeProperty('overflow-y');
                body.style.removeProperty('height');
                body.style.removeProperty('max-height');
                body.style.removeProperty('position');
            } catch (e) { /* ignore */ }
        }

        // Đảm bảo html là scroller chính
        html.classList.add('lms-html');
        body.classList.add('lms-shell');
    }

    window.unlockLmsScroll = unlockLmsScroll;
    window.lockLmsScroll = function lockLmsScroll() {
        document.documentElement.classList.add('lms-scroll-lock');
        document.body.classList.add('lms-scroll-lock');
    };

    function syncNavActive() {
        const path = (window.location.pathname || '').replace(/\/+$/, '') || '/';
        document.querySelectorAll('#lms-header .lms-nav a[href]').forEach(function (a) {
            try {
                const p = new URL(a.getAttribute('href'), window.location.origin).pathname.replace(/\/+$/, '') || '/';
                // Khóa = /lms/hoc exact; các mục khác prefix OK
                let active = false;
                if (p === '/lms/hoc' || p === '/lms/gv') {
                    active = path === p;
                } else {
                    active = path === p || (p.length > 1 && path.indexOf(p + '/') === 0);
                }
                a.classList.toggle('is-active', active);
            } catch (e) {}
        });
    }

    document.addEventListener('turbo:before-visit', function (e) {
        try {
            const url = new URL(e.detail.url, window.location.origin);
            // Soft-navigate trong cổng học (/lms/hoc) và cổng dạy (/lms/gv)
            const path = url.pathname || '';
            const ok = url.origin === window.location.origin
                && (path.indexOf('/lms/hoc') === 0 || path.indexOf('/lms/gv') === 0);
            if (!ok) {
                e.preventDefault();
                window.location.assign(e.detail.url);
            }
        } catch (err) {}
    });

    document.addEventListener('turbo:visit', function () {
        startProgress();
        // Đóng modal turbo-permanent còn sót (tránh khóa scroll trang mới)
        document.querySelectorAll('#confirm-modal.is-open, #lms-qr-modal.is-open, .lms-modal-backdrop.is-open').forEach(function (el) {
            el.classList.remove('is-open', 'flex');
            el.classList.add('hidden');
            el.setAttribute('aria-hidden', 'true');
        });
        unlockLmsScroll();
    });

    document.addEventListener('turbo:load', function () {
        stopProgress();
        unlockLmsScroll();
        syncHeaderH();
        syncNavActive();
    });

    document.addEventListener('turbo:render', function () {
        stopProgress();
        unlockLmsScroll();
        syncHeaderH();
        syncNavActive();
    });

    document.addEventListener('turbo:before-cache', function () {
        unlockLmsScroll();
    });

    // Lần đầu (không chờ turbo)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () {
            unlockLmsScroll();
            syncHeaderH();
            syncNavActive();
        });
    } else {
        unlockLmsScroll();
        syncHeaderH();
        syncNavActive();
    }
    window.addEventListener('resize', syncHeaderH);
})();
</script>
