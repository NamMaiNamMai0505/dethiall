<style>
    @media (prefers-reduced-motion: no-preference) {
        #admin-content {
            transition: opacity 0.18s ease;
        }

        #admin-content.is-swapping {
            opacity: 0.35;
        }

        #admin-content.is-entering {
            animation: admin-content-in 0.22s cubic-bezier(0.4, 0, 0.2, 1) both;
        }

        @keyframes admin-content-in {
            from { opacity: 0; transform: translateY(4px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .turbo-progress {
            position: fixed;
            top: 0;
            left: 0;
            height: 2px;
            background: #60a5fa;
            z-index: 10001;
            transform-origin: left;
            transform: scaleX(0);
            opacity: 0;
            transition: opacity 0.15s ease;
            pointer-events: none;
        }

        .turbo-progress.is-active {
            opacity: 1;
            animation: turbo-progress 0.9s cubic-bezier(0.4, 0, 0.2, 1) forwards;
        }

        @keyframes turbo-progress {
            0% { transform: scaleX(0); }
            40% { transform: scaleX(0.55); }
            100% { transform: scaleX(0.92); }
        }
    }
</style>

<div id="turbo-progress" class="turbo-progress" hidden></div>

<script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@8.0.12/dist/turbo.es2017-esm.js" type="module"></script>

<script>
(function () {
    if (window.__turboAdminBound) return;
    window.__turboAdminBound = true;

    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function normalizePath(path) {
        const value = (path || '/').replace(/\/+$/, '') || '/';
        return value;
    }

    function hrefPath(href) {
        if (!href || href === '#') return null;
        try {
            return normalizePath(new URL(href, window.location.origin).pathname);
        } catch {
            return null;
        }
    }

    function isLinkActive(href) {
        const linkPath = hrefPath(href);
        if (!linkPath) return false;

        const current = normalizePath(window.location.pathname);
        if (current === linkPath) return true;

        // Prefix match: /subjects active cho /subjects/xxx — NHƯNG không nuốt
        // /subjects/lessons-manage (menu Bài học riêng).
        if (linkPath.length > 1 && current.startsWith(linkPath + '/')) {
            const subjectsRoot = linkPath === '/subjects' || linkPath.endsWith('/subjects');
            if (subjectsRoot && (
                current === linkPath + '/lessons-manage'
                || current.startsWith(linkPath + '/lessons-manage/')
            )) {
                return false;
            }
            return true;
        }

        return false;
    }

    function syncSidebarNav({ animate = false } = {}) {
        const sidebar = document.getElementById('sidebar');
        if (!sidebar) return;

        window.initSidebarSubmenus?.();

        const userRoutes = ['users', 'students', 'roles', 'permissions'];
        const current = normalizePath(window.location.pathname);
        const userSectionActive = userRoutes.some((segment) =>
            current === '/' + segment || current.startsWith('/' + segment + '/')
        );

        sidebar.querySelectorAll('.sidebar-nav > ul > li').forEach((item) => {
            const mainLink = item.querySelector(':scope > a[href]');
            const submenu = item.querySelector(':scope > .sidebar-submenu');
            const links = item.querySelectorAll('a[href]');

            let sectionActive = false;
            links.forEach((link) => {
                if (isLinkActive(link.getAttribute('href'))) {
                    sectionActive = true;
                }
            });

            if (mainLink) {
                mainLink.classList.toggle('bg-blue-600', sectionActive);
            }

            if (submenu) {
                const shouldOpen = submenu.id === 'user-management-submenu' ? userSectionActive : sectionActive;

                if (window.UiMotion?.setSidebarSubmenu) {
                    window.UiMotion.setSidebarSubmenu(submenu, shouldOpen, { animate });
                } else {
                    submenu.classList.toggle('is-open', shouldOpen);
                    submenu.classList.toggle('hidden', !shouldOpen);
                    submenu.setAttribute('aria-hidden', shouldOpen ? 'false' : 'true');
                }

                if (submenu.id === 'user-management-submenu') {
                    const chevron = document.getElementById('user-management-chevron');
                    chevron?.classList.toggle('rotate-180', shouldOpen);

                    const toggle = document.getElementById('user-management-toggle');
                    if (toggle) {
                        toggle.classList.toggle('bg-blue-600', shouldOpen);
                        toggle.classList.toggle('bg-gray-700', false);
                    }
                }
            }

            links.forEach((link) => {
                if (link === mainLink || link.getAttribute('href') === '#') return;

                const active = isLinkActive(link.getAttribute('href'));
                link.classList.toggle('text-white', active);
                link.classList.toggle('text-gray-400', !active);
                link.classList.toggle('hover:text-white', !active);
            });
        });
    }

    function markContentEntering() {
        if (reducedMotion) return;

        const main = document.getElementById('admin-content');
        if (!main) return;

        main.classList.remove('is-entering');
        void main.offsetWidth;
        main.classList.add('is-entering');
        main.addEventListener('animationend', () => main.classList.remove('is-entering'), { once: true });
    }

    function startProgress() {
        const bar = document.getElementById('turbo-progress');
        if (!bar || reducedMotion) return;

        bar.hidden = false;
        bar.classList.remove('is-active');
        void bar.offsetWidth;
        bar.classList.add('is-active');
    }

    function stopProgress() {
        const bar = document.getElementById('turbo-progress');
        if (!bar) return;

        bar.classList.remove('is-active');
        setTimeout(() => { bar.hidden = true; }, 180);
    }

    document.addEventListener('turbo:click', (event) => {
        const original = event.detail?.originalEvent;
        const link = original?.target?.closest?.('a[href]');

        if (link && (link.target === '_blank' || link.hasAttribute('download') || link.dataset.turbo === 'false')) {
            event.preventDefault();
            return;
        }

        try {
            const nextPath = normalizePath(new URL(event.detail.url).pathname);
            if (nextPath === normalizePath(window.location.pathname)) {
                event.preventDefault();
            }
        } catch {
            // ignore malformed urls
        }
    });

    document.addEventListener('turbo:before-visit', () => {
        document.getElementById('admin-content')?.classList.add('is-swapping');
        startProgress();
    });

    document.addEventListener('turbo:render', () => {
        document.getElementById('admin-content')?.classList.remove('is-swapping');
        stopProgress();
        syncSidebarNav({ animate: true });
        markContentEntering();
    });

    document.addEventListener('turbo:load', () => {
        syncSidebarNav({ animate: false });
        // KHÔNG re-dispatch DOMContentLoaded — listener cũ từ trang trước
        // vẫn còn trên document → getElementById null.addEventListener → crash.
        // Page scripts nên dùng turbo:load (hoặc chạy ngay trong body).
    });

    document.addEventListener('turbo:before-cache', () => {
        if (typeof Chart !== 'undefined') {
            document.querySelectorAll('canvas').forEach((canvas) => {
                const instance = Chart.getChart(canvas);
                instance?.destroy();
            });
        }
    });

    // Lỗi Turbo (500/404) không được “nuốt” trang — dừng progress + bỏ opacity swap
    document.addEventListener('turbo:fetch-request-error', () => {
        document.getElementById('admin-content')?.classList.remove('is-swapping');
        stopProgress();
    });

    window.syncSidebarNav = syncSidebarNav;
})();
</script>