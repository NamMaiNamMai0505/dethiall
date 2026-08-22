{{-- Turbo Drive cho portal Quản lý điểm — chuyển trang mượt, không flash trắng --}}
<style>
    .grades-turbo-progress {
        position: fixed;
        top: 0;
        left: 0;
        height: 2px;
        width: 100%;
        background: linear-gradient(90deg, #ea580c, #0d9488);
        z-index: 10050;
        transform-origin: left;
        transform: scaleX(0);
        opacity: 0;
        pointer-events: none;
    }
    .grades-turbo-progress.is-active {
        opacity: 1;
        animation: grades-turbo-bar 0.65s cubic-bezier(0.4, 0, 0.2, 1) forwards;
    }
    @keyframes grades-turbo-bar {
        0%   { transform: scaleX(0); }
        45%  { transform: scaleX(0.55); }
        100% { transform: scaleX(0.92); }
    }
</style>
<div id="grades-turbo-progress" class="grades-turbo-progress" hidden aria-hidden="true"></div>

<script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@8.0.12/dist/turbo.es2017-esm.js" type="module"></script>

<script>
(function () {
    if (window.__turboGradesBound) return;
    window.__turboGradesBound = true;

    function progressEl() {
        return document.getElementById('grades-turbo-progress');
    }

    function startProgress() {
        var el = progressEl();
        if (!el) return;
        el.hidden = false;
        el.classList.remove('is-active');
        void el.offsetWidth;
        el.classList.add('is-active');
    }

    function stopProgress() {
        var el = progressEl();
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

    document.addEventListener('turbo:click', function (e) {
        var a = e.target.closest('a');
        if (!a) return;
        // Giữ full reload khi rời portal grades
        try {
            var url = new URL(a.href, window.location.origin);
            if (!url.pathname.startsWith('/grades') && !url.pathname.startsWith('/export-templates')) {
                // allow turbo false already set; default leave portal = full nav via data-turbo=false on external
            }
        } catch (err) {}
    });

    /** Highlight tab header theo URL hiện tại (sau Turbo navigate) */
    function syncGradesNav() {
        var nav = document.querySelector('[data-grades-nav]');
        if (!nav) return;
        var path = window.location.pathname || '';

        nav.querySelectorAll('a[data-nav-match]').forEach(function (a) {
            var key = a.getAttribute('data-nav-match');
            var active = false;
            if (key === 'grades-entry') {
                active = path === '/grades' || path === '/grades/'
                    || path.indexOf('/grades/subjects') === 0
                    || path.indexOf('/grades/faculties') === 0;
            } else if (key === 'grades-academic') {
                active = path.indexOf('/grades/academic') === 0;
            } else if (key === 'grades-books') {
                active = path.indexOf('/grades/books') === 0;
            } else if (key === 'grades-export') {
                active = path.indexOf('/export-templates/grades') === 0;
            }
            a.classList.toggle('is-active', !!active);
        });
    }

    document.addEventListener('turbo:before-visit', startProgress);
    document.addEventListener('turbo:before-fetch-request', startProgress);
    document.addEventListener('turbo:load', function () {
        stopProgress();
        syncGradesNav();
    });
    document.addEventListener('turbo:render', function () {
        stopProgress();
        syncGradesNav();
    });
    document.addEventListener('turbo:fetch-request-error', stopProgress);

    if (document.readyState !== 'loading') {
        syncGradesNav();
    } else {
        document.addEventListener('DOMContentLoaded', syncGradesNav);
    }
})();
</script>
