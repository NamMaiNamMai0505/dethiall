<style>
    #notification-panel.ui-dropdown {
        width: 22rem;
        max-height: 24rem;
        transform-origin: top right;
    }

    #notification-panel .notification-list {
        max-height: 18rem;
        overflow-y: auto;
    }

    #notification-panel .notification-item {
        transition: background-color 0.15s ease;
    }

    #notification-panel .notification-item.is-unread {
        background-color: #eff6ff;
    }

    #notification-badge:empty,
    #notification-badge[data-count="0"] {
        display: none;
    }
</style>

<script>
(function () {
    if (window.__notificationCenterBound) return;
    window.__notificationCenterBound = true;

    const routes = {
        index: @json(route('notifications.index')),
        unread: @json(route('notifications.unread-count')),
        readAll: @json(route('notifications.read-all')),
        read: @json(url('/notifications')),
    };

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
    const WAF_BLOCK_STATUS = 468;
    const POLL_INTERVAL_MS = 60000;
    const WAF_BACKOFF_MS = 5 * 60 * 1000;
    let pollTimer = null;
    let lastSeenId = Number(localStorage.getItem('notification_last_seen_id') || 0);
    let isOpen = false;
    let blockedUntil = 0;
    let lastPollAt = 0;

    function headers(json = true) {
        const value = {
            'X-CSRF-TOKEN': csrf,
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };
        if (json) value['Content-Type'] = 'application/json';
        return value;
    }

    function panel() {
        return document.getElementById('notification-panel');
    }

    function badge() {
        return document.getElementById('notification-badge');
    }

    function list() {
        return document.getElementById('notification-list');
    }

    function setBadge(count) {
        const el = badge();
        if (!el) return;
        const value = Math.max(0, Number(count) || 0);
        el.dataset.count = String(value);
        el.textContent = value > 99 ? '99+' : String(value);
    }

    function renderEmpty() {
        const container = list();
        if (!container) return;
        container.innerHTML = `
            <div class="px-4 py-8 text-center text-sm text-gray-500">
                <i class="bi bi-bell-slash text-2xl mb-2 block text-gray-300"></i>
                Chưa có thông báo nào
            </div>
        `;
    }

    function renderBlocked() {
        const container = list();
        if (!container) return;
        container.innerHTML = `
            <div class="px-4 py-8 text-center text-sm text-gray-500">
                <i class="bi bi-exclamation-triangle text-2xl mb-2 block text-amber-400"></i>
                Không tải được thông báo lúc này, thử lại sau ít phút.
            </div>
        `;
    }

    function renderItems(items) {
        const container = list();
        if (!container) return;

        if (!items.length) {
            renderEmpty();
            return;
        }

        container.innerHTML = items.map((item) => {
            const unread = !item.read_at;
            const url = resolveNavUrl(item.url) || '';
            return `
                <button type="button"
                        class="notification-item w-full text-left px-4 py-3 border-b border-gray-100 ${unread ? 'is-unread' : ''}"
                        data-notification-id="${item.id}"
                        data-notification-url="${escapeHtml(url)}">
                    <p class="text-sm font-medium text-gray-900 leading-snug">${escapeHtml(item.title)}</p>
                    <p class="text-xs text-gray-600 mt-1 leading-relaxed whitespace-pre-line">${escapeHtml(item.message)}</p>
                    <p class="text-[11px] text-gray-400 mt-1">${escapeHtml(item.time_ago || '')}</p>
                </button>
            `;
        }).join('');
    }

    function escapeHtml(value) {
        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;');
    }

    /**
     * Convert absolute URLs (e.g. http://localhost/...) to same-origin relative paths
     * so clicks do not jump to wrong host when APP_URL differs from the browser URL.
     */
    function resolveNavUrl(url) {
        if (!url || url === '#' || url === 'null' || url === 'undefined') {
            return null;
        }
        try {
            const parsed = new URL(url, window.location.origin);
            return parsed.pathname + parsed.search + parsed.hash;
        } catch {
            return String(url).startsWith('/') ? String(url) : null;
        }
    }

    function handleBlockedResponse(response) {
        if (response.status !== WAF_BLOCK_STATUS) return false;

        // SafeLine trả 468 trước khi request tới Laravel. Fetch không thể chạy
        // JavaScript challenge trong HTML phản hồi, nên tạm dừng để tránh spam WAF.
        blockedUntil = Date.now() + WAF_BACKOFF_MS;
        return true;
    }

    function applyCount(payload) {
        if (payload && payload.count !== undefined && payload.count !== null) {
            setBadge(payload.count);
        }
    }

    async function fetchNotifications({ since = false } = {}) {
        if (Date.now() < blockedUntil) {
            return { data: [], blocked: true };
        }

        try {
            const url = since ? `${routes.index}?since_id=${lastSeenId}` : routes.index;
            const response = await fetch(url, { headers: headers(false) });
            if (handleBlockedResponse(response)) return { data: [], blocked: true };
            if (!response.ok) return { data: [] };
            return await response.json();
        } catch (e) {
            return { data: [] };
        }
    }

    async function refreshPanel() {
        const payload = await fetchNotifications();
        if (payload?.blocked) {
            renderBlocked();
            return;
        }
        applyCount(payload);
        renderItems((payload && payload.data) || []);
    }

    async function markRead(id) {
        try {
            const response = await fetch(`${routes.read}/${id}/read`, {
                method: 'POST',
                headers: headers(),
                body: '{}',
            });
            if (handleBlockedResponse(response) || !response.ok) return null;

            return await response.json();
        } catch {
            return null;
        }
    }

    async function markAllRead() {
        try {
            const response = await fetch(routes.readAll, {
                method: 'POST',
                headers: headers(),
                body: '{}',
            });
            if (handleBlockedResponse(response) || !response.ok) return;

            const payload = await response.json();
            applyCount(payload);
            setBadge(0);
            await refreshPanel();
        } catch {
            // Giữ nguyên trạng thái khi mạng/WAF từ chối request.
        }
    }

    const toastedIds = new Set();
    let pollInFlight = false;

    function notifyIncoming(items) {
        items.forEach((item) => {
            const id = Number(item?.id || 0);
            if (id && toastedIds.has(id)) return;
            if (id) toastedIds.add(id);
            if (window.Notify?.info) {
                window.Notify.info(item.message, 6000);
            }
        });
    }

    async function poll() {
        if (!document.getElementById('notification-bell')) return;
        if (pollInFlight) return;
        if (document.visibilityState === 'hidden') return;
        if (Date.now() < blockedUntil) return;
        pollInFlight = true;
        lastPollAt = Date.now();

        try {
            const payload = await fetchNotifications({ since: lastSeenId > 0 });
            if (payload?.blocked) return;
            applyCount(payload);

            if (lastSeenId > 0) {
                const fresh = payload?.data || [];
                if (fresh.length) {
                    const sorted = [...fresh].sort((a, b) => a.id - b.id);
                    lastSeenId = sorted[sorted.length - 1].id;
                    localStorage.setItem('notification_last_seen_id', String(lastSeenId));
                    notifyIncoming(sorted);
                    if (isOpen) await refreshPanel();
                }
            } else {
                // Lần đầu: chỉ neo lastSeenId, KHÔNG toast (tránh spam / trùng)
                const items = payload?.data || [];
                if (items.length) {
                    lastSeenId = items[0].id;
                    localStorage.setItem('notification_last_seen_id', String(lastSeenId));
                    items.forEach((item) => {
                        if (item?.id) toastedIds.add(Number(item.id));
                    });
                }
            }
        } catch {
            // silent network failures during polling
        } finally {
            pollInFlight = false;
        }
    }

    function showPanelEl(el) {
        if (!el) return;
        if (window.UiMotion?.open) {
            window.UiMotion.open(el);
            return;
        }
        // Fallback khi thiếu UiMotion (vd. layout LMS trước đây)
        el.classList.remove('hidden');
        el.classList.add('is-open');
        el.setAttribute('aria-hidden', 'false');
        el.style.display = 'block';
        el.style.opacity = '1';
        el.style.visibility = 'visible';
        el.style.pointerEvents = 'auto';
    }

    function hidePanelEl(el) {
        if (!el) return;
        if (window.UiMotion?.close) {
            window.UiMotion.close(el);
            return;
        }
        el.classList.add('hidden');
        el.classList.remove('is-open');
        el.setAttribute('aria-hidden', 'true');
        el.style.display = 'none';
        el.style.opacity = '0';
        el.style.visibility = 'hidden';
        el.style.pointerEvents = 'none';
    }

    function openPanel() {
        const el = panel();
        if (!el) return;
        showPanelEl(el);
        isOpen = true;
        refreshPanel();
        document.querySelector('[data-notification-chevron]')?.classList.add('rotate-180');
    }

    function closePanel() {
        const el = panel();
        if (!el) return;
        hidePanelEl(el);
        isOpen = false;
        document.querySelector('[data-notification-chevron]')?.classList.remove('rotate-180');
    }

    window.toggleNotificationPanel = function () {
        const el = panel();
        if (!el) return;
        if (el.classList.contains('is-open') || (!el.classList.contains('hidden') && el.style.display === 'block')) {
            closePanel();
        } else {
            openPanel();
        }
    };

    function bindEvents() {
        document.addEventListener('click', (event) => {
            const trigger = event.target.closest('[data-notification-trigger]');
            const inside = event.target.closest('#notification-root');

            if (trigger) {
                event.preventDefault();
                toggleNotificationPanel();
                return;
            }

            if (!inside && panel()?.classList.contains('is-open')) {
                closePanel();
            }
        });

        document.addEventListener('click', async (event) => {
            const item = event.target.closest('[data-notification-id]');
            if (!item) return;

            const id = Number(item.dataset.notificationId);
            const url = resolveNavUrl(item.dataset.notificationUrl);
            if (!id) return;

            const payload = await markRead(id);
            if (payload) {
                applyCount(payload);
                item.classList.remove('is-unread');
            }

            if (url) {
                if (window.Turbo) {
                    window.Turbo.visit(url);
                } else {
                    window.location.assign(url);
                }
            }
            closePanel();
        });

        // Delegation: header grades re-render sau Turbo vẫn bấm được «Đánh dấu đã đọc»
        document.addEventListener('click', async (event) => {
            const markAll = event.target.closest('#notification-mark-all');
            if (!markAll) return;
            event.preventDefault();
            await markAllRead();
        });
    }

    function boot() {
        const bell = document.getElementById('notification-bell');
        if (!bell) return;

        const el = panel();
        if (el && !el.dataset.ncReady) {
            el.dataset.ncReady = '1';
            el.classList.add('ui-dropdown', 'hidden');
            el.setAttribute('aria-hidden', 'true');
        }

        // Poll / interval chỉ khởi động 1 lần / session
        if (window.__ncPollStarted) {
            if (Date.now() - lastPollAt >= 15000) poll();
            return;
        }
        window.__ncPollStarted = true;

        poll();
        if (!pollTimer) {
            pollTimer = window.setInterval(poll, POLL_INTERVAL_MS);
        }
    }

    bindEvents();
    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState === 'visible') poll();
    });

    // Turbo 8: turbo:load chạy cả lần load đầu — KHÔNG gắn thêm DOMContentLoaded (sẽ boot 2 lần → toast đôi)
    document.addEventListener('turbo:load', boot);
    // Fallback khi không có Turbo
    if (!window.Turbo) {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', boot);
        } else {
            boot();
        }
    }
})();
</script>
