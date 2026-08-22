<style>
    @media (prefers-reduced-motion: no-preference) {
        #sidebar {
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .sidebar-text,
        #sidebar-title {
            transition: opacity 0.22s ease, width 0.22s ease;
        }

        #sidebar-toggle i {
            transition: transform 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        @keyframes ui-slide-down {
            from { opacity: 0; transform: translateY(-8px) scale(0.98); }
            to { opacity: 1; transform: translateY(0) scale(1); }
        }

        /* Dropdown panels */
        .ui-dropdown {
            opacity: 0;
            transform: translateY(-8px) scale(0.98);
            transition: opacity 0.22s ease, transform 0.22s cubic-bezier(0.4, 0, 0.2, 1);
            transform-origin: top right;
            pointer-events: none;
        }

        .ui-dropdown.is-open {
            opacity: 1;
            transform: translateY(0) scale(1);
            pointer-events: auto;
        }

        .ui-dropdown.hidden {
            display: none !important;
        }

        /* Sidebar submenus */
        .ui-collapse {
            overflow: hidden;
            max-height: 0;
            opacity: 0;
            margin-top: 0;
            transition:
                max-height 0.32s cubic-bezier(0.4, 0, 0.2, 1),
                opacity 0.24s ease,
                margin-top 0.24s ease;
        }

        .ui-collapse.is-open {
            max-height: 320px;
            opacity: 1;
            margin-top: 0.25rem;
        }

        .ui-collapse.ui-collapse--instant,
        .ui-collapse.ui-collapse--instant > li,
        .ui-collapse.ui-collapse--settled > li {
            transition: none !important;
            animation: none !important;
        }

        .sidebar-nav a {
            transition: background-color 0.2s ease, color 0.2s ease, padding-left 0.2s ease;
        }

        .sidebar-nav .ui-collapse.is-open.ui-collapse--animate-children > li {
            animation: ui-submenu-in 0.22s cubic-bezier(0.4, 0, 0.2, 1) both;
        }

        .sidebar-nav .ui-collapse.is-open.ui-collapse--animate-children > li:nth-child(1) { animation-delay: 0.02s; }
        .sidebar-nav .ui-collapse.is-open.ui-collapse--animate-children > li:nth-child(2) { animation-delay: 0.05s; }
        .sidebar-nav .ui-collapse.is-open.ui-collapse--animate-children > li:nth-child(3) { animation-delay: 0.08s; }
        .sidebar-nav .ui-collapse.is-open.ui-collapse--animate-children > li:nth-child(4) { animation-delay: 0.11s; }

        @keyframes ui-submenu-in {
            from { opacity: 0; transform: translateX(-6px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* Tom Select dropdown — không animate (dễ treo khung tìm trên body fixed) */
        .ts-dropdown,
        .ts-dropdown.single,
        body > .ts-dropdown {
            animation: none !important;
        }

        .ts-wrapper .ts-control {
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }

        .ts-wrapper.dropdown-active .ts-control {
            box-shadow: 0 0 0 3px rgba(78, 161, 255, 0.18);
        }

        /* Form controls */
        select,
        input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]),
        textarea,
        .form-input,
        .form-select,
        .form-textarea {
            transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
        }

        select:focus,
        input:not([type="checkbox"]):not([type="radio"]):focus,
        textarea:focus,
        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            box-shadow: 0 0 0 3px rgba(78, 161, 255, 0.14);
        }

        /* Buttons & links */
        button,
        a.bg-blue-600, a.bg-green-600, a.bg-red-600, a.bg-gray-600,
        a.bg-blue-600, [class*="hover:bg-"] {
            transition: background-color 0.2s ease, color 0.2s ease, box-shadow 0.28s ease, border-color 0.2s ease, opacity 0.15s ease;
        }

        button:active:not(:disabled),
        a:active[class*="bg-"] {
            transform: none;
            opacity: 0.9;
        }

        /* Modal */
        #confirm-modal {
            transition: opacity 0.25s ease, visibility 0.25s ease;
        }

        #confirm-modal > .absolute {
            transition: opacity 0.25s ease;
        }

        #confirm-modal .relative.bg-white {
            transform: scale(0.95) translateY(8px);
            opacity: 0;
            transition: transform 0.28s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.28s ease;
        }

        #confirm-modal.is-open {
            opacity: 1;
            visibility: visible;
        }

        #confirm-modal.is-open .relative.bg-white {
            transform: scale(1) translateY(0);
            opacity: 1;
        }

        /* Tables */
        tbody tr {
            transition: background-color 0.15s ease;
        }

        /* Cards on hub / stats — glow hover */
        .bg-white.rounded-lg,
        .bg-white.rounded-xl {
            transition: box-shadow 0.28s ease, border-color 0.28s ease, background-color 0.2s ease;
        }

        a.bg-white.rounded-xl:hover,
        a.bg-white.rounded-lg:hover {
            transform: none;
            border-color: rgba(78, 161, 255, 0.38);
            box-shadow: 0 0 0 1px rgba(78, 161, 255, 0.28), 0 0 18px rgba(78, 161, 255, 0.28);
        }
    }
</style>

<script>
(function () {
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    window.UiMotion = {
        open(el) {
            if (!el) return;
            el.classList.remove('hidden');
            el.setAttribute('aria-hidden', 'false');
            if (!reducedMotion) {
                requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('is-open')));
            } else {
                el.classList.add('is-open');
            }
        },

        close(el) {
            if (!el) return;
            el.classList.remove('is-open');
            el.setAttribute('aria-hidden', 'true');
            const hide = () => el.classList.add('hidden');
            if (reducedMotion) {
                hide();
                return;
            }
            const done = (e) => {
                if (e.target !== el) return;
                hide();
                el.removeEventListener('transitionend', done);
            };
            el.addEventListener('transitionend', done);
            setTimeout(hide, 320);
        },

        toggle(el) {
            if (!el) return;
            if (el.classList.contains('is-open')) {
                this.close(el);
            } else {
                this.open(el);
            }
        },

        toggleCollapse(el) {
            if (!el) return;
            if (el.classList.contains('is-open')) {
                this.closeCollapse(el);
            } else {
                this.openCollapse(el);
            }
        },

        openCollapse(el) {
            if (!el) return;
            el.classList.remove('hidden', 'ui-collapse--settled', 'ui-collapse--instant');
            el.setAttribute('aria-hidden', 'false');
            if (reducedMotion) {
                el.classList.add('is-open');
                return;
            }
            requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('is-open')));
        },

        closeCollapse(el) {
            if (!el) return;
            el.classList.remove('is-open', 'ui-collapse--animate-children');
            el.setAttribute('aria-hidden', 'true');
            if (reducedMotion) {
                el.classList.add('hidden');
                return;
            }
            const done = (e) => {
                if (e.target !== el || e.propertyName !== 'max-height') return;
                el.classList.add('hidden');
                el.removeEventListener('transitionend', done);
            };
            el.addEventListener('transitionend', done);
            setTimeout(() => el.classList.add('hidden'), 360);
        },

        markCollapseChildrenAnimated(el) {
            if (!el || reducedMotion) return;
            el.classList.add('ui-collapse--animate-children');
            const cleanup = () => el.classList.remove('ui-collapse--animate-children');
            el.addEventListener('transitionend', function onEnd(e) {
                if (e.target !== el || e.propertyName !== 'max-height') return;
                cleanup();
                el.removeEventListener('transitionend', onEnd);
            });
            setTimeout(cleanup, 400);
        },

        setSidebarSubmenu(el, shouldOpen, { animate = true } = {}) {
            if (!el) return;

            const isOpen = el.classList.contains('is-open') && !el.classList.contains('hidden');

            if (shouldOpen) {
                if (isOpen) return;

                if (animate && !reducedMotion) {
                    this.markCollapseChildrenAnimated(el);
                    this.openCollapse(el);
                } else {
                    el.classList.remove('hidden', 'ui-collapse--settled');
                    el.classList.add('is-open');
                    el.setAttribute('aria-hidden', 'false');
                }
                return;
            }

            if (!isOpen && el.classList.contains('hidden')) return;

            if (animate && !reducedMotion) {
                this.closeCollapse(el);
            } else {
                el.classList.remove('is-open', 'ui-collapse--animate-children');
                el.classList.add('hidden');
                el.setAttribute('aria-hidden', 'true');
            }
        },
    };

    window.toggleDropdown = function () {
        const dropdown = document.getElementById('userDropdown');
        UiMotion.toggle(dropdown);
        const chevron = document.querySelector('[data-user-menu-chevron]');
        if (chevron) chevron.classList.toggle('rotate-180', dropdown?.classList.contains('is-open'));
    };

    window.initSidebarSubmenus = function initSidebarSubmenus() {
        document.querySelectorAll('.sidebar-submenu').forEach((submenu) => {
            if (submenu.dataset.uiReady) return;
            submenu.dataset.uiReady = '1';

            submenu.classList.remove('block');
            submenu.classList.add('ui-collapse');

            const startsOpen = submenu.classList.contains('is-open') || !submenu.classList.contains('hidden');

            if (startsOpen) {
                submenu.classList.add('ui-collapse--instant', 'ui-collapse--settled', 'is-open');
                submenu.classList.remove('hidden');
                submenu.setAttribute('aria-hidden', 'false');
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => submenu.classList.remove('ui-collapse--instant'));
                });
            } else {
                submenu.classList.remove('is-open');
                submenu.classList.add('hidden');
                submenu.setAttribute('aria-hidden', 'true');
            }
        });
    };

    function initUiMotionPage() {
        const userDropdown = document.getElementById('userDropdown');
        if (userDropdown && !userDropdown.dataset.uiReady) {
            userDropdown.dataset.uiReady = '1';
            userDropdown.classList.add('ui-dropdown', 'hidden');
            userDropdown.setAttribute('aria-hidden', 'true');
        }

        initSidebarSubmenus();

        document.querySelectorAll('#hours-preview, .ui-reveal').forEach((el) => {
            if (el.dataset.uiRevealReady) return;
            el.dataset.uiRevealReady = '1';
            el.classList.add('ui-reveal');
        });

        document.querySelectorAll('select').forEach((sel) => {
            if (sel.dataset.uiSelectReady) return;
            sel.dataset.uiSelectReady = '1';
            sel.addEventListener('mousedown', () => sel.classList.add('ui-select-active'));
            sel.addEventListener('blur', () => sel.classList.remove('ui-select-active'));
            sel.addEventListener('change', () => sel.classList.remove('ui-select-active'));
        });

    }

    if (!window.__uiMotionGlobalBound) {
        window.__uiMotionGlobalBound = true;

        document.addEventListener('click', function (event) {
            const dropdown = document.getElementById('userDropdown');
            if (!dropdown || dropdown.classList.contains('hidden')) return;
            const trigger = event.target.closest('[data-user-menu-trigger]');
            if (!trigger) {
                UiMotion.close(dropdown);
                document.querySelector('[data-user-menu-chevron]')?.classList.remove('rotate-180');
            }
        });

        const previewObserver = new MutationObserver((mutations) => {
            mutations.forEach((m) => {
                if (m.attributeName !== 'class') return;
                const el = m.target;
                if (!el.classList.contains('ui-reveal')) return;
                if (!el.classList.contains('hidden')) {
                    el.style.animation = 'none';
                    void el.offsetHeight;
                    el.style.animation = 'ui-slide-down 0.28s cubic-bezier(0.4, 0, 0.2, 1) both';
                }
            });
        });
        previewObserver.observe(document.documentElement, { attributes: true, subtree: true, attributeFilter: ['class'] });
    }

    if (!window.__uiMotionPageBound) {
        window.__uiMotionPageBound = true;
        document.addEventListener('turbo:load', initUiMotionPage);
        document.addEventListener('DOMContentLoaded', initUiMotionPage);
    } else {
        initUiMotionPage();
    }

    // Expose for sidebar user management
    window.toggleUserManagementAnimated = function (event) {
        event.preventDefault();
        const sidebar = document.getElementById('sidebar');
        if (sidebar?.classList.contains('w-16')) return;

        const submenu = document.getElementById('user-management-submenu');
        const chevron = document.getElementById('user-management-chevron');
        const toggle = document.getElementById('user-management-toggle');

        if (!submenu) return;

        const wasOpen = submenu.classList.contains('is-open') && !submenu.classList.contains('hidden');
        UiMotion.setSidebarSubmenu(submenu, !wasOpen, { animate: true });
        const open = !wasOpen;
        chevron?.classList.toggle('rotate-180', open);
        toggle?.classList.toggle('bg-gray-700', open && !toggle.classList.contains('bg-blue-600'));
    };
})();
</script>