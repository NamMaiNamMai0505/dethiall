{{-- Cùng một cơ chế popup, tự nhận diện đúng giao diện Dashboard / LMS / Quản lý điểm. --}}
<style>
    #toast-container {
        position: fixed;
        top: 1.1rem;
        right: 1.1rem;
        z-index: 10050;
        display: flex;
        flex-direction: column;
        gap: 0.7rem;
        max-width: min(24rem, calc(100vw - 1.5rem));
        width: 100%;
        pointer-events: none;
    }
    #toast-container .ui-toast {
        pointer-events: auto;
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
        padding: 0.95rem 1rem;
        border-radius: 1rem;
        border: 1px solid rgba(226, 232, 240, 0.95);
        background: rgba(255, 255, 255, 0.92);
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        box-shadow:
            0 18px 40px -18px rgba(15, 23, 42, 0.28),
            0 0 0 1px rgba(255, 255, 255, 0.55) inset;
        transform: translateX(112%) scale(0.96);
        opacity: 0;
        transition: transform 0.38s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.28s ease;
    }
    #toast-container .ui-toast.is-in {
        transform: translateX(0) scale(1);
        opacity: 1;
    }
    #toast-container .ui-toast.is-out {
        transform: translateX(108%) scale(0.96);
        opacity: 0;
    }
    #toast-container .ui-toast-icon {
        flex-shrink: 0;
        width: 2.35rem;
        height: 2.35rem;
        border-radius: 0.75rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
    }
    #toast-container .ui-toast-body { flex: 1; min-width: 0; padding-top: 0.1rem; }
    #toast-container .ui-toast-title {
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        margin-bottom: 0.15rem;
        opacity: 0.85;
    }
    #toast-container .ui-toast-msg {
        font-size: 0.875rem;
        line-height: 1.45;
        color: #1f2937;
        white-space: pre-line;
        word-break: break-word;
    }
    #toast-container .ui-toast-close {
        flex-shrink: 0;
        width: 1.75rem;
        height: 1.75rem;
        border-radius: 0.5rem;
        border: 0;
        background: transparent;
        color: #94a3b8;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background 0.15s, color 0.15s;
    }
    #toast-container .ui-toast-close:hover {
        background: rgba(15, 23, 42, 0.06);
        color: #475569;
    }
    #toast-container .ui-toast--success { border-color: rgba(16, 185, 129, 0.28); }
    #toast-container .ui-toast--success .ui-toast-icon { background: #ecfdf5; color: #059669; }
    #toast-container .ui-toast--success .ui-toast-title { color: #047857; }
    #toast-container .ui-toast--error { border-color: rgba(239, 68, 68, 0.28); }
    #toast-container .ui-toast--error .ui-toast-icon { background: #fef2f2; color: #dc2626; }
    #toast-container .ui-toast--error .ui-toast-title { color: #b91c1c; }
    #toast-container .ui-toast--warning { border-color: rgba(245, 158, 11, 0.32); }
    #toast-container .ui-toast--warning .ui-toast-icon { background: #fffbeb; color: #d97706; }
    #toast-container .ui-toast--warning .ui-toast-title { color: #b45309; }
    #toast-container .ui-toast--info { border-color: rgba(78, 161, 255, 0.3); }
    #toast-container .ui-toast--info .ui-toast-icon { background: #eff6ff; color: #358fee; }
    #toast-container .ui-toast--info .ui-toast-title { color: #2563eb; }

    /* Dashboard: xanh dương theo admin shell. */
    body.dashboard-shell #toast-container .ui-toast--info {
        border-color: rgba(78, 161, 255, 0.34);
    }
    body.dashboard-shell #toast-container .ui-toast--info .ui-toast-icon {
        background: #eff6ff;
        color: #358fee;
    }
    body.dashboard-shell #toast-container .ui-toast--info .ui-toast-title {
        color: #2563eb;
    }

    /* LMS: teal theo cổng học tập / giảng dạy. */
    body.lms-shell #toast-container .ui-toast--info,
    .lms-shell #toast-container .ui-toast--info {
        border-color: rgba(13, 148, 136, 0.28);
    }
    body.lms-shell #toast-container .ui-toast--info .ui-toast-icon,
    .lms-shell #toast-container .ui-toast--info .ui-toast-icon {
        background: #f0fdfa;
        color: #0d9488;
    }
    body.lms-shell #toast-container .ui-toast--info .ui-toast-title,
    .lms-shell #toast-container .ui-toast--info .ui-toast-title {
        color: #0f766e;
    }

    /* Quản lý điểm: cam–teal theo grades shell. */
    body.grades-shell #toast-container .ui-toast--info,
    .grades-shell #toast-container .ui-toast--info {
        border-color: rgba(234, 88, 12, 0.28);
    }
    body.grades-shell #toast-container .ui-toast--info .ui-toast-icon,
    .grades-shell #toast-container .ui-toast--info .ui-toast-icon {
        background: #fff7ed;
        color: #ea580c;
    }
    body.grades-shell #toast-container .ui-toast--info .ui-toast-title,
    .grades-shell #toast-container .ui-toast--info .ui-toast-title {
        color: #c2410c;
    }
    body.grades-shell #toast-container .ui-toast--success .ui-toast-icon {
        background: #f0fdfa;
        color: #0d9488;
    }

    #confirm-modal {
        z-index: 10060 !important;
    }
    #confirm-modal .ui-confirm-card {
        background: rgba(255, 255, 255, 0.97);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
        border: 1px solid rgba(226, 232, 240, 0.95);
        box-shadow: 0 28px 56px -20px rgba(15, 23, 42, 0.35);
        border-radius: 1.15rem;
        max-width: 26rem;
        width: 100%;
        padding: 1.4rem 1.35rem 1.25rem;
        transform: translateY(12px) scale(0.97);
        opacity: 0;
        transition: transform 0.32s cubic-bezier(0.22, 1, 0.36, 1), opacity 0.25s ease;
    }
    #confirm-modal.is-open .ui-confirm-card {
        transform: translateY(0) scale(1);
        opacity: 1;
    }
    #confirm-modal .ui-confirm-icon {
        width: 2.75rem;
        height: 2.75rem;
        border-radius: 0.9rem;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.25rem;
        flex-shrink: 0;
    }
    #confirm-modal .ui-confirm-ok {
        border: 0;
        cursor: pointer;
    }
    body.dashboard-shell #confirm-modal .ui-confirm-ok:not(.is-danger) {
        background: linear-gradient(135deg, #4ea1ff 0%, #358fee 100%) !important;
        color: #fff !important;
    }
    body.dashboard-shell #confirm-modal .ui-confirm-icon.bg-teal-50 {
        background: #eff6ff !important;
        color: #358fee !important;
    }
    body.lms-shell #confirm-modal .ui-confirm-ok:not(.is-danger),
    .lms-shell #confirm-modal .ui-confirm-ok:not(.is-danger) {
        background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%) !important;
        color: #fff !important;
    }
    body.lms-shell #confirm-modal .ui-confirm-ok:not(.is-danger):hover,
    .lms-shell #confirm-modal .ui-confirm-ok:not(.is-danger):hover {
        filter: brightness(1.05);
    }
    body.grades-shell #confirm-modal .ui-confirm-ok:not(.is-danger),
    .grades-shell #confirm-modal .ui-confirm-ok:not(.is-danger) {
        background: linear-gradient(135deg, #ea580c 0%, #c2410c 52%, #0d9488 100%) !important;
        color: #fff !important;
    }
    body.grades-shell #confirm-modal .ui-confirm-icon.bg-teal-50,
    .grades-shell #confirm-modal .ui-confirm-icon.bg-teal-50 {
        background: #fff7ed !important;
        color: #ea580c !important;
    }
    body.grades-shell #confirm-modal .ui-confirm-ok:not(.is-danger):hover,
    .grades-shell #confirm-modal .ui-confirm-ok:not(.is-danger):hover {
        filter: brightness(1.05);
    }
</style>

<div id="toast-container" aria-live="polite" aria-relevant="additions"></div>

<div id="confirm-modal"
     class="fixed inset-0 z-[10060] hidden items-center justify-center p-4 opacity-0 invisible"
     aria-hidden="true"
     role="dialog"
     aria-modal="true"
     aria-labelledby="confirm-modal-title">
    <div class="absolute inset-0 bg-slate-900/50 transition-opacity duration-250" data-confirm-dismiss style="backdrop-filter:blur(3px)"></div>
    <div class="relative ui-confirm-card pointer-events-auto">
        <div class="flex items-start gap-3.5">
            <div id="confirm-modal-icon-wrap" class="ui-confirm-icon bg-amber-50 text-amber-600">
                <i id="confirm-modal-icon" class="bi bi-question-circle"></i>
            </div>
            <div class="flex-1 min-w-0">
                <h3 id="confirm-modal-title" class="text-base font-semibold text-slate-900 mb-1 tracking-tight">Xác nhận</h3>
                <p id="confirm-modal-message" class="text-sm text-slate-600 whitespace-pre-line leading-relaxed"></p>
            </div>
        </div>
        <div class="flex justify-end gap-2.5 mt-6">
            <button type="button" data-confirm-cancel
                    class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-700 hover:bg-slate-50 text-sm font-medium transition-colors">
                Hủy
            </button>
            <button type="button" data-confirm-ok id="confirm-modal-ok"
                    class="ui-confirm-ok px-4 py-2.5 rounded-xl brand-btn text-white text-sm font-medium transition-all shadow-sm">
                Đồng ý
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    if (window.__notifyUiBound) return;
    window.__notifyUiBound = true;

    const titles = {
        success: 'Thành công',
        error: 'Lỗi',
        warning: 'Cảnh báo',
        info: 'Thông báo',
    };
    const icons = {
        success: 'bi-check-circle-fill',
        error: 'bi-x-circle-fill',
        warning: 'bi-exclamation-triangle-fill',
        info: 'bi-info-circle-fill',
    };

    const recentToasts = new Map();
    const DEDUPE_MS = 2500;

    window.Notify = {
        show(type, message, duration = 4200) {
            const container = document.getElementById('toast-container');
            if (!container || message === undefined || message === null || message === '') return;

            type = ['success', 'error', 'warning', 'info'].includes(type) ? type : 'info';
            const key = type + '|' + String(message);
            const now = Date.now();
            if (now - (recentToasts.get(key) || 0) < DEDUPE_MS) return;
            recentToasts.set(key, now);
            if (recentToasts.size > 40) {
                recentToasts.forEach((ts, k) => {
                    if (now - ts > DEDUPE_MS) recentToasts.delete(k);
                });
            }

            const toast = document.createElement('div');
            toast.className = 'ui-toast ui-toast--' + type;
            toast.setAttribute('role', type === 'error' ? 'alert' : 'status');

            const iconWrap = document.createElement('div');
            iconWrap.className = 'ui-toast-icon';
            iconWrap.innerHTML = '<i class="bi ' + icons[type] + '"></i>';

            const body = document.createElement('div');
            body.className = 'ui-toast-body';
            body.innerHTML = '<div class="ui-toast-title"></div><div class="ui-toast-msg"></div>';
            body.querySelector('.ui-toast-title').textContent = titles[type];
            body.querySelector('.ui-toast-msg').textContent = String(message);

            const closeBtn = document.createElement('button');
            closeBtn.type = 'button';
            closeBtn.className = 'ui-toast-close';
            closeBtn.setAttribute('aria-label', 'Đóng');
            closeBtn.innerHTML = '<i class="bi bi-x-lg text-sm"></i>';

            const remove = () => {
                toast.classList.remove('is-in');
                toast.classList.add('is-out');
                setTimeout(() => toast.remove(), 320);
            };
            closeBtn.addEventListener('click', remove);

            toast.append(iconWrap, body, closeBtn);
            container.appendChild(toast);
            requestAnimationFrame(() => requestAnimationFrame(() => toast.classList.add('is-in')));

            const ms = duration === undefined || duration === null ? 4200 : duration;
            if (ms > 0) setTimeout(remove, type === 'error' ? Math.max(ms, 5200) : ms);
        },
        success(msg, duration) { this.show('success', msg, duration); },
        error(msg, duration) { this.show('error', msg, duration); },
        warning(msg, duration) { this.show('warning', msg, duration); },
        info(msg, duration) { this.show('info', msg, duration); },
    };

    const modal = document.getElementById('confirm-modal');
    const modalTitle = document.getElementById('confirm-modal-title');
    const modalMessage = document.getElementById('confirm-modal-message');
    const modalOk = document.getElementById('confirm-modal-ok');
    const modalIconWrap = document.getElementById('confirm-modal-icon-wrap');
    const modalIcon = document.getElementById('confirm-modal-icon');

    let pendingForm = null;
    let pendingSubmitter = null;
    let pendingResolve = null;

    function applyConfirmStyle(options = {}) {
        const danger = !!options.danger;
        const title = options.title || (danger ? 'Xác nhận xóa' : 'Xác nhận');
        const okText = options.confirmText || (danger ? 'Xóa' : 'Đồng ý');
        const cancelText = options.cancelText || 'Hủy';

        modalTitle.textContent = title;
        modalOk.textContent = okText;

        const cancelBtn = modal.querySelector('[data-confirm-cancel]');
        if (cancelBtn) cancelBtn.textContent = cancelText;

        if (danger) {
            modalIconWrap.className = 'ui-confirm-icon bg-red-50 text-red-600';
            modalIcon.className = 'bi bi-exclamation-triangle';
            modalOk.className = 'ui-confirm-ok is-danger px-4 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 text-white text-sm font-medium transition-all shadow-sm';
        } else {
            modalIconWrap.className = 'ui-confirm-icon bg-teal-50 text-teal-700';
            modalIcon.className = 'bi bi-question-circle';
            modalOk.className = 'ui-confirm-ok px-4 py-2.5 rounded-xl brand-btn text-white text-sm font-medium transition-all shadow-sm';
        }
    }

    function openModal(message, options = {}) {
        if (!modal) return;
        applyConfirmStyle(options);
        modalMessage.textContent = String(message ?? '');
        modal.classList.remove('hidden', 'invisible', 'opacity-0');
        modal.classList.add('flex');
        modal.setAttribute('aria-hidden', 'false');
        // LMS: class lock (Turbo-safe). Admin: overflow inline như cũ.
        if (
            document.body.classList.contains('lms-shell')
            || document.documentElement.classList.contains('lms-html')
            || document.body.classList.contains('grades-shell')
            || document.documentElement.classList.contains('grades-html')
        ) {
            document.documentElement.classList.add('lms-scroll-lock');
            document.body.classList.add('lms-scroll-lock');
        } else {
            document.body.style.overflow = 'hidden';
        }
        requestAnimationFrame(() => requestAnimationFrame(() => modal.classList.add('is-open')));
        setTimeout(() => modalOk?.focus(), 50);
    }

    function closeModal(result) {
        if (!modal) return;
        modal.classList.remove('is-open');
        document.documentElement.classList.remove('lms-scroll-lock');
        document.body.classList.remove('lms-scroll-lock');
        document.body.style.overflow = '';
        document.documentElement.style.overflow = '';
        if (typeof window.unlockLmsScroll === 'function') {
            try { window.unlockLmsScroll(); } catch (e) {}
        }
        setTimeout(() => {
            modal.classList.add('hidden', 'invisible', 'opacity-0');
            modal.classList.remove('flex');
            modal.setAttribute('aria-hidden', 'true');

            if (pendingResolve) {
                const resolve = pendingResolve;
                pendingResolve = null;
                resolve(!!result);
            }

            if (result && pendingForm) {
                const form = pendingForm;
                const submitter = pendingSubmitter;
                pendingForm = null;
                pendingSubmitter = null;
                form.dataset.confirmed = '1';
                if (typeof form.requestSubmit === 'function') {
                    if (submitter) {
                        form.requestSubmit(submitter);
                    } else {
                        form.requestSubmit();
                    }
                } else {
                    form.submit();
                }
            } else {
                pendingForm = null;
                pendingSubmitter = null;
            }
        }, 280);
    }

    document.querySelector('[data-confirm-cancel]')?.addEventListener('click', () => closeModal(false));
    document.querySelector('[data-confirm-dismiss]')?.addEventListener('click', () => closeModal(false));
    document.querySelector('[data-confirm-ok]')?.addEventListener('click', () => closeModal(true));

    document.addEventListener('keydown', (e) => {
        if (!modal || modal.classList.contains('hidden')) return;
        if (e.key === 'Escape') {
            e.preventDefault();
            closeModal(false);
        }
    });

    window.uiConfirm = function (message, options = {}) {
        return new Promise((resolve) => {
            if (pendingResolve) pendingResolve(false);
            pendingForm = null;
            pendingSubmitter = null;
            pendingResolve = resolve;
            openModal(message, options);
        });
    };

    window.uiAlert = function (message, type = 'info') {
        if (window.Notify) window.Notify.show(type, String(message ?? ''));
    };

    function createPopupApi(portal) {
        return Object.freeze({
            portal,
            show(type, message, duration) {
                window.Notify?.show(type, String(message ?? ''), duration);
            },
            success(message, duration) {
                window.Notify?.success(String(message ?? ''), duration);
            },
            error(message, duration) {
                window.Notify?.error(String(message ?? ''), duration);
            },
            warning(message, duration) {
                window.Notify?.warning(String(message ?? ''), duration);
            },
            info(message, duration) {
                window.Notify?.info(String(message ?? ''), duration);
            },
            alert(message, type = 'info') {
                window.uiAlert(message, type);
            },
            confirm(message, options = {}) {
                return window.uiConfirm(message, options);
            },
        });
    }

    window.DashboardPopup = window.DashboardPopup || createPopupApi('dashboard');
    window.LmsPopup = window.LmsPopup || createPopupApi('lms');
    window.GradesPopup = window.GradesPopup || createPopupApi('grades');
    window.PortalPopup = document.body.classList.contains('grades-shell')
        ? window.GradesPopup
        : (document.body.classList.contains('lms-shell') ? window.LmsPopup : window.DashboardPopup);

    window.showConfirm = function (message, form, options = {}, submitter = null) {
        if (pendingResolve) {
            pendingResolve(false);
            pendingResolve = null;
        }
        pendingForm = form || null;
        pendingSubmitter = submitter || null;
        pendingResolve = null;
        openModal(message, options);
    };

    function isDangerMessage(message) {
        const m = String(message || '').toLowerCase();
        return m.includes('xóa') || m.includes('xoá') || m.includes('vĩnh viễn')
            || m.includes('không thể hoàn tác') || m.includes('không thể khôi phục');
    }

    document.addEventListener('submit', (e) => {
        const form = e.target;
        if (!(form instanceof HTMLFormElement)) return;
        const message = form.dataset.confirm;
        if (!message) return;
        if (form.dataset.confirmed === '1') {
            delete form.dataset.confirmed;
            return;
        }
        e.preventDefault();
        showConfirm(message, form, {
            danger: form.dataset.confirmDanger === '1' || isDangerMessage(message),
            title: form.dataset.confirmTitle || undefined,
            confirmText: form.dataset.confirmOk || undefined,
            cancelText: form.dataset.confirmCancel || undefined,
        }, e.submitter || null);
    });

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-confirm]');
        if (!btn || btn.tagName === 'FORM') return;
        const form = btn.closest('form');
        if (!form || form.dataset.confirm) return;
        if (btn.type && btn.type !== 'submit') return;
        e.preventDefault();
        showConfirm(btn.dataset.confirm, form, {
            danger: btn.dataset.confirmDanger === '1' || isDangerMessage(btn.dataset.confirm),
            title: btn.dataset.confirmTitle || undefined,
            confirmText: btn.dataset.confirmOk || undefined,
        }, btn);
    });

    // Laravel session flash → toast popup (đọc từ #session-flash-payload trong main)
    function consumeSessionFlash() {
        const el = document.getElementById('session-flash-payload');
        if (!el || el.dataset.consumed === '1') return;
        el.dataset.consumed = '1';
        let data = {};
        try { data = JSON.parse(el.textContent || '{}'); } catch (e) { data = {}; }
        if (data.success) window.Notify.success(data.success);
        if (data.error) window.Notify.error(data.error);
        if (data.warning) window.Notify.warning(data.warning);
        if (data.info) window.Notify.info(data.info);
        // validation errors
        if (Array.isArray(data.errors)) {
            data.errors.forEach((msg) => window.Notify.error(msg, 6000));
        }
        el.remove();
    }
    window.consumeSessionFlash = consumeSessionFlash;
    document.addEventListener('turbo:load', consumeSessionFlash);
    document.addEventListener('DOMContentLoaded', consumeSessionFlash);
    if (document.readyState !== 'loading') setTimeout(consumeSessionFlash, 0);
})();
</script>
