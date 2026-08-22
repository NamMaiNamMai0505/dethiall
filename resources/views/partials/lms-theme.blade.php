<style>
    /*
     * LMS learner theme — giữ palette teal riêng,
     * hiệu ứng/glass/glow đồng bộ với admin-theme (dashboard).
     */
    :root {
        --lms-brand: #0d9488;
        --lms-brand-hover: #0f766e;
        --lms-brand-deep: #115e59;
        --lms-brand-soft: #14b8a6;
        --lms-brand-softest: #5eead4;
        --lms-ink: #1f2937;
        --lms-muted: #64748b;
        --lms-bg: #f0fdfa;
        --lms-card: #ffffff;
        --lms-border: #d5dae3;
        --lms-border-soft: #ccfbf1;
        --lms-ivory: #faf8f4;

        --lms-glass-blur: 12px;
        --lms-glass-shadow: 0 10px 30px -14px rgba(13, 148, 136, 0.16);
        --lms-glass-inset: inset 0 1px 0 rgba(255, 255, 255, 0.75);
        --lms-glow-soft: 0 0 0 1px rgba(13, 148, 136, 0.22), 0 0 14px rgba(13, 148, 136, 0.2);
        --lms-glow-medium: 0 0 0 1px rgba(13, 148, 136, 0.32), 0 0 18px rgba(13, 148, 136, 0.28), 0 0 32px rgba(13, 148, 136, 0.12);
        --lms-glow-strong: 0 0 0 1px rgba(13, 148, 136, 0.4), 0 0 22px rgba(13, 148, 136, 0.35), 0 0 40px rgba(13, 148, 136, 0.14);
        --lms-ring: rgba(13, 148, 136, 0.35);
    }

    /* Không “nhảy” scale/lift — giống dashboard */
    .lms-shell [class*="hover:-translate-y"]:hover,
    .lms-shell [class*="hover:scale"]:hover,
    .lms-shell .group:hover [class*="group-hover:scale"] {
        transform: none !important;
    }

    .lms-shell input:focus,
    .lms-shell select:focus,
    .lms-shell textarea:focus,
    .lms-shell button:focus-visible,
    .lms-shell a:focus-visible {
        transform: none !important;
        outline: none;
    }

    .lms-shell input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):focus,
    .lms-shell select:focus,
    .lms-shell textarea:focus {
        border-color: var(--lms-brand) !important;
        box-shadow: 0 0 0 3px var(--lms-ring) !important;
    }

    /* Utilities đồng bộ dashboard */
    .lms-shell .hover-glow {
        transition: box-shadow 0.28s ease, border-color 0.28s ease, background-color 0.2s ease, color 0.2s ease !important;
    }
    .lms-shell .hover-glow:hover {
        transform: none !important;
        box-shadow: var(--lms-glow-soft) !important;
        border-color: rgba(13, 148, 136, 0.35) !important;
    }
    .lms-shell .hover-glow-strong:hover {
        transform: none !important;
        box-shadow: var(--lms-glow-strong) !important;
    }

    /* Header sticky — dính top khi scroll */
    .lms-top {
        position: sticky !important;
        top: 0 !important;
        z-index: 100 !important;
        backdrop-filter: blur(var(--lms-glass-blur));
        -webkit-backdrop-filter: blur(var(--lms-glass-blur));
        background: rgba(250, 248, 244, 0.94);
        border-bottom: 1px solid rgba(213, 218, 227, 0.9);
        box-shadow: 0 8px 24px -18px rgba(15, 23, 42, 0.18);
        overflow: visible;
    }
    .lms-top-inner {
        width: 100%;
        max-width: 96rem;
        margin: 0 auto;
        padding: 0.85rem 1.25rem;
        box-sizing: border-box;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        overflow: visible;
        position: relative;
        z-index: 60;
    }
    .lms-nav {
        position: relative;
        z-index: 70;
        overflow: visible;
    }
    #notification-root {
        position: relative;
        z-index: 80;
    }
    #notification-panel {
        z-index: 200 !important;
        background: #fff;
        color: #1f2937;
    }
    #notification-panel.is-open {
        display: block !important;
    }
    .lms-brand {
        display: flex;
        flex: 0 0 auto;
        align-items: center;
        gap: 0.65rem;
        text-decoration: none;
        color: inherit;
        border-radius: 0.75rem;
        padding: 0.15rem 0.35rem 0.15rem 0.15rem;
        transition: box-shadow 0.25s ease, background-color 0.2s ease;
    }
    .lms-brand:hover {
        background: rgba(204, 251, 241, 0.45);
        box-shadow: var(--lms-glow-soft);
    }
    .lms-brand-badge {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.85rem;
        background: linear-gradient(165deg, #fff 0%, #f0fdfa 100%);
        border: 1px solid rgba(13, 148, 136, 0.22);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        flex-shrink: 0;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06), inset 0 1px 0 rgba(255,255,255,0.85);
    }
    .lms-brand-badge img {
        width: 100%;
        height: 100%;
        object-fit: contain;
        display: block;
    }
    .lms-brand-text { display: flex; flex-direction: column; min-width: 0; }
    .lms-brand-text strong { display: block; font-size: 0.95rem; line-height: 1.2; color: var(--lms-ink); }
    .lms-brand-text small { display: block; font-size: 0.72rem; color: var(--lms-muted); margin-top: 0.1rem; }

    /* Nav pills — glow active giống sidebar active */
    .lms-nav {
        display: flex;
        flex: 1 1 auto;
        min-width: 0;
        align-items: center;
        justify-content: flex-end;
        gap: 0.35rem;
        flex-wrap: wrap;
    }
    .lms-nav a,
    .lms-nav button {
        border: 1px solid transparent;
        background: transparent;
        cursor: pointer;
        font-size: 0.875rem;
        color: #334155;
        text-decoration: none;
        padding: 0.45rem 0.8rem;
        border-radius: 0.65rem;
        transition: background-color 0.2s ease, color 0.2s ease, border-color 0.2s ease, box-shadow 0.25s ease;
    }
    .lms-nav a:hover,
    .lms-nav button:hover {
        background: rgba(204, 251, 241, 0.55);
        border-color: rgba(13, 148, 136, 0.28);
        color: var(--lms-brand-deep);
        box-shadow: var(--lms-glow-soft);
        transform: none !important;
    }
    .lms-nav a.is-active {
        background: linear-gradient(180deg, #99f6e4 0%, #5eead4 40%, #2dd4bf 100%);
        border-color: rgba(13, 148, 136, 0.35);
        color: var(--lms-brand-deep);
        font-weight: 600;
        box-shadow: inset 0 1px 0 rgba(255,255,255,0.45), 0 4px 12px -6px rgba(13, 148, 136, 0.45);
    }

    @media (min-width: 1280px) {
        .lms-shell-teach .lms-nav {
            flex-wrap: nowrap;
            gap: 0.25rem;
        }

        .lms-shell-teach .lms-nav a,
        .lms-shell-teach .lms-nav button {
            padding-right: 0.65rem;
            padding-left: 0.65rem;
            white-space: nowrap;
        }
    }

    @media (max-width: 1179px) {
        .lms-shell-teach .lms-top-inner {
            align-items: flex-start;
            flex-direction: column;
            gap: 0.65rem;
        }

        .lms-shell-teach .lms-nav {
            width: 100%;
            justify-content: flex-start;
        }
    }

    @media (max-width: 640px) {
        .lms-top-inner {
            padding: 0.7rem 0.8rem;
        }

        .lms-nav {
            width: 100%;
            justify-content: flex-start;
            gap: 0.25rem;
        }

        .lms-nav a,
        .lms-nav button {
            padding: 0.4rem 0.6rem;
            font-size: 0.8rem;
        }
    }

    .lms-main {
        max-width: 72rem;
        margin: 0 auto;
        padding: 1.5rem 1.25rem 2.5rem;
        /* không khóa height — để document (html) scroll */
        height: auto !important;
        max-height: none !important;
        min-height: 0 !important;
        overflow: visible !important;
    }

    /* Cards — glass + glow hover (không lift) */
    .lms-card {
        background: linear-gradient(165deg, rgba(255,255,255,0.96) 0%, #ffffff 70%);
        border: 1px solid var(--lms-border);
        border-radius: 1rem;
        box-shadow: var(--lms-glass-shadow), var(--lms-glass-inset);
        transition: box-shadow 0.28s ease, border-color 0.28s ease, background 0.2s ease;
    }
    a.lms-card,
    .lms-card-link {
        text-decoration: none;
        color: inherit;
        display: block;
    }
    a.lms-card:hover,
    .lms-card.hover-glow:hover,
    .lms-card-interactive:hover {
        border-color: rgba(13, 148, 136, 0.4) !important;
        box-shadow: var(--lms-glow-medium), var(--lms-glass-inset) !important;
        transform: none !important;
    }

    /* List rows */
    .lms-card ul.divide-y > li > a {
        transition: background-color 0.18s ease, box-shadow 0.22s ease;
    }
    .lms-card ul.divide-y > li > a:hover {
        background: rgba(240, 253, 250, 0.85) !important;
        box-shadow: inset 3px 0 0 var(--lms-brand);
    }

    /* Chip / pill buttons trong phòng học */
    .lms-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        border-radius: 0.65rem;
        padding: 0.4rem 0.75rem;
        font-size: 0.8125rem;
        font-weight: 600;
        text-decoration: none !important;
        border: 1px solid transparent;
        transition: box-shadow 0.25s ease, border-color 0.2s ease, background-color 0.2s ease, color 0.2s ease;
        transform: none !important;
    }
    .lms-chip:hover {
        box-shadow: var(--lms-glow-soft);
        border-color: rgba(13, 148, 136, 0.3);
        transform: none !important;
    }
    .lms-chip-teal { background: #f0fdfa; color: #115e59; border-color: #99f6e4; }
    .lms-chip-amber { background: #fffbeb; color: #92400e; border-color: #fde68a; }
    .lms-chip-rose { background: #fff1f2; color: #9f1239; border-color: #fecdd3; }
    .lms-chip-violet { background: #f5f3ff; color: #5b21b6; border-color: #ddd6fe; }
    .lms-chip-slate { background: #f1f5f9; color: #1e293b; border-color: #e2e8f0; }
    .lms-chip-emerald { background: #ecfdf5; color: #065f46; border-color: #a7f3d0; }
    .lms-chip-orange { background: #fff7ed; color: #9a3412; border-color: #fed7aa; }
    .lms-chip-sky { background: #f0f9ff; color: #075985; border-color: #bae6fd; }

    /* Primary / secondary buttons */
    .lms-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        border-radius: 0.65rem;
        padding: 0.55rem 1rem;
        font-size: 0.875rem;
        font-weight: 600;
        border: 1px solid transparent;
        cursor: pointer;
        text-decoration: none !important;
        transition: box-shadow 0.25s ease, background 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        transform: none !important;
    }
    .lms-btn:hover { transform: none !important; }
    /* Nút phẳng — không 3D/gradient “nổi” */
    .lms-btn-primary {
        color: #fff !important;
        background: #0d9488;
        border-color: #0f766e;
        box-shadow: none;
    }
    .lms-btn-primary:hover {
        background: #0f766e;
        box-shadow: var(--lms-glow-soft);
    }
    .lms-btn-danger {
        color: #fff !important;
        background: #e11d48;
        border-color: #be123c;
        box-shadow: none;
    }
    .lms-btn-danger:hover {
        background: #be123c;
        box-shadow: 0 0 0 1px rgba(244, 63, 94, 0.25), 0 0 12px rgba(244, 63, 94, 0.2);
    }
    .lms-btn-ghost {
        color: #334155 !important;
        background: #fff;
        border-color: #cbd5e1;
        box-shadow: none;
    }
    .lms-btn-ghost:hover {
        border-color: rgba(13, 148, 136, 0.4);
        background: #f0fdfa;
        color: var(--lms-brand-deep) !important;
        box-shadow: var(--lms-glow-soft);
    }

    /* Header height for fixed tabs */
    :root { --lms-header-h: 4.5rem; }

    /*
     * Tab bar: tĩnh ngay dưới card khóa — không sticky/fixed.
     * Scroll trang thì tabs đi theo nội dung, không dính theo viewport.
     */
    .lms-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 0.35rem;
        padding: 0.5rem;
        background: rgba(255,255,255,0.97);
        border: 1px solid rgba(13, 148, 136, 0.28);
        border-radius: 0.85rem;
        box-shadow: var(--lms-glass-shadow);
        position: relative;
        z-index: 1;
        box-sizing: border-box;
        width: 100%;
    }
    .lms-tab {
        border: 1px solid transparent;
        background: transparent;
        color: #475569;
        font-size: 0.8125rem;
        font-weight: 600;
        padding: 0.45rem 0.8rem;
        border-radius: 0.55rem;
        cursor: pointer;
        transition: background 0.18s ease, color 0.18s ease, box-shadow 0.22s ease, border-color 0.18s ease;
    }
    .lms-tab:hover {
        background: #f0fdfa;
        color: var(--lms-brand-deep);
        border-color: rgba(13,148,136,0.25);
    }
    .lms-tab.is-active {
        background: #0d9488;
        color: #fff;
        border-color: #0f766e;
        box-shadow: var(--lms-glow-soft);
    }
    .lms-panel {
        display: none;
        padding-bottom: 0.5rem;
        min-height: 0;
    }
    /* Không animation — tránh nháy khi đổi tab / trang */
    .lms-panel.is-active { display: block; }
    /* Chat: gọn, chỉ chừa ~1 scroll dưới khung */
    .lms-panel[data-panel="chat"].is-active {
        padding-bottom: 1.25rem;
        margin-bottom: 0;
    }

    /* Clickable rows / cards */
    .lms-click-row {
        cursor: pointer;
        transition: background 0.18s ease, box-shadow 0.22s ease, border-color 0.18s ease;
        border: 1px solid transparent;
        border-radius: 0.75rem;
    }
    .lms-click-row:hover {
        background: #f0fdfa;
        border-color: rgba(13,148,136,0.3);
        box-shadow: var(--lms-glow-soft);
    }

    /* Confirm modal — trên sticky header (z=100) */
    .lms-modal-backdrop {
        position: fixed; inset: 0; z-index: 10080 !important;
        background: rgba(15, 23, 42, 0.55);
        display: none; align-items: center; justify-content: center;
        padding: 1rem;
    }
    .lms-modal-backdrop.is-open { display: flex; }
    .lms-modal {
        background: #fff;
        border-radius: 1rem;
        border: 1px solid var(--lms-border);
        box-shadow: 0 24px 48px -20px rgba(15,23,42,0.35);
        max-width: 26rem; width: 100%;
        padding: 1.25rem 1.35rem;
        position: relative;
        z-index: 1;
    }

    /* QR popup — không bị header / tab đè */
    #lms-qr-modal {
        position: fixed !important;
        inset: 0 !important;
        z-index: 10090 !important;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        background: rgba(15, 23, 42, 0.55);
        backdrop-filter: blur(2px);
    }
    #lms-qr-modal.is-open {
        display: flex !important;
    }
    #lms-qr-modal .lms-qr-dialog {
        background: #fff;
        border-radius: 1rem;
        border: 1px solid var(--lms-border);
        box-shadow: 0 28px 56px -18px rgba(15, 23, 42, 0.4);
        max-width: 22rem;
        width: 100%;
        padding: 1.35rem 1.25rem 1.25rem;
        position: relative;
        z-index: 1;
    }
    #lms-qr-box {
        display: flex;
        align-items: center;
        justify-content: center;
        min-height: 220px;
        padding: 0.75rem;
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 0.75rem;
    }
    /* qrcodejs render canvas + img chồng — chỉ hiện 1 */
    #lms-qr-box img {
        display: block !important;
        margin: 0 auto;
        max-width: 200px;
        height: auto;
    }
    #lms-qr-box canvas {
        display: none !important;
    }
    #lms-qr-box table {
        margin: 0 auto;
    }

    /* —— Tom Select đồng bộ toàn LMS shell —— */
    .lms-shell .ts-wrapper,
    .lms-shell .ts-wrapper.lms-ts-field {
        width: 100%;
        font-family: inherit;
    }
    .lms-shell .ts-wrapper .ts-control {
        min-height: 2.5rem;
        border: 1px solid #e2e8f0 !important;
        border-radius: 0.5rem !important;
        background: #fff !important;
        padding: 0.35rem 0.75rem !important;
        font-size: 0.875rem;
        color: #1f2937;
        box-shadow: none;
    }
    .lms-shell .ts-wrapper.focus .ts-control,
    .lms-shell .ts-wrapper.dropdown-active .ts-control {
        border-color: #0d9488 !important;
        box-shadow: 0 0 0 3px rgba(13, 148, 136, 0.15) !important;
    }
    .lms-shell .ts-wrapper .ts-control input {
        font-size: 0.875rem !important;
        color: #1f2937 !important;
    }
    /* Bảng điểm danh: select status gọn */
    .lms-shell .ts-wrapper.lms-ts-status {
        min-width: 9.5rem;
        max-width: 12rem;
        width: auto;
    }
    .lms-shell .ts-wrapper.lms-ts-status .ts-control {
        min-height: 2.15rem;
        padding: 0.25rem 0.5rem !important;
        font-size: 0.8125rem;
    }
    .lms-shell .ts-dropdown,
    body > .ts-dropdown {
        z-index: 10100 !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 0.5rem !important;
        box-shadow: 0 12px 28px -12px rgba(15, 23, 42, 0.25) !important;
        font-size: 0.875rem;
    }
    .lms-shell .ts-dropdown .option.active,
    body > .ts-dropdown .option.active {
        background: #f0fdfa !important;
        color: #115e59 !important;
    }
    /* Ẩn native select đã được Tom Select wrap (phòng flash) */
    .lms-shell select.tomselected {
        visibility: hidden !important;
        height: 0 !important;
        position: absolute !important;
    }

    /* Calendar attendance */
    .lms-cal {
        display: grid;
        grid-template-columns: repeat(7, minmax(0, 1fr));
        gap: 0.35rem;
    }
    .lms-cal-dow {
        text-align: center;
        font-size: 0.7rem;
        font-weight: 700;
        color: #64748b;
        padding: 0.25rem;
    }
    .lms-cal-day {
        aspect-ratio: 1;
        border-radius: 0.65rem;
        border: 1px solid #e2e8f0;
        background: #fff;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: 600;
        color: #334155;
        cursor: pointer;
        transition: box-shadow 0.2s ease, border-color 0.2s ease, background 0.2s ease;
    }
    .lms-cal-day:hover:not(.is-empty) {
        border-color: rgba(13,148,136,0.45);
        box-shadow: var(--lms-glow-soft);
    }
    .lms-cal-day.is-empty { background: transparent; border-color: transparent; cursor: default; }
    .lms-cal-day.is-today { border-color: #0d9488; color: #0f766e; }
    .lms-cal-day.is-selected { background: #0d9488; color: #fff; border-color: #0f766e; }
    .lms-cal-day.has-session::after {
        content: '';
        width: 0.35rem; height: 0.35rem;
        border-radius: 999px;
        background: #14b8a6;
        margin-top: 0.15rem;
    }
    .lms-cal-day.is-selected.has-session::after { background: #fff; }
    .lms-cal-day.is-present { background: #ecfdf5; border-color: #6ee7b7; }
    .lms-cal-day.is-absent { background: #fef2f2; border-color: #fecaca; }
    .lms-cal-day.is-exam-day::after { background: #f59e0b; }
    .lms-cal-count {
        font-size: 0.55rem;
        font-weight: 700;
        color: #0f766e;
        line-height: 1;
        margin-top: 0.1rem;
    }
    .lms-cal-day.is-selected .lms-cal-count { color: #fff; }

    .lms-nav a.is-active {
        background: #0d9488 !important;
        color: #fff !important;
        border-color: #0f766e !important;
        box-shadow: var(--lms-glow-soft) !important;
    }

    /* Nav icon (bell) */
    .lms-nav-icon-btn {
        position: relative;
        border: 1px solid transparent;
        background: transparent;
        color: #334155;
        width: 2.35rem;
        height: 2.35rem;
        border-radius: 0.65rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: background 0.2s, border-color 0.2s, box-shadow 0.2s;
    }
    .lms-nav-icon-btn:hover {
        background: rgba(204, 251, 241, 0.55);
        border-color: rgba(13, 148, 136, 0.28);
        box-shadow: var(--lms-glow-soft);
    }
    .lms-notif-badge {
        position: absolute;
        top: -0.2rem;
        right: -0.2rem;
        min-width: 1.1rem;
        height: 1.1rem;
        padding: 0 0.25rem;
        border-radius: 999px;
        background: #e11d48;
        color: #fff;
        font-size: 0.65rem;
        font-weight: 700;
        display: none;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }
    .lms-notif-badge:not(:empty):not([data-count="0"]) {
        display: inline-flex;
    }

    /* Chat — khung gọn, dưới khung chỉ chừa khoảng 1 scroll nhỏ */
    .lms-chat-shell {
        display: grid;
        grid-template-columns: minmax(11rem, 15rem) 1fr;
        height: min(52vh, 26rem);
        min-height: 20rem;
        border-radius: 1rem;
        overflow: hidden;
        border: 1px solid var(--lms-border);
        background: #fff;
        box-shadow: var(--lms-glass-shadow);
        margin-bottom: 1rem;
    }
    @media (max-width: 720px) {
        .lms-chat-shell {
            grid-template-columns: 1fr;
            height: auto;
            min-height: 22rem;
            max-height: none;
        }
        .lms-chat-sidebar { max-height: 10rem; border-right: 0 !important; border-bottom: 1px solid #e2e8f0; }
    }
    .lms-chat-sidebar {
        background: #f8fafc;
        border-right: 1px solid #e2e8f0;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
    }
    .lms-chat-sidebar-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.65rem 0.75rem;
        border-bottom: 1px solid #e2e8f0;
        font-size: 0.75rem;
        font-weight: 700;
        color: #475569;
        background: #f1f5f9;
    }
    .lms-chat-add-btn {
        width: 2rem;
        height: 2rem;
        border-radius: 0.55rem;
        border: 1px solid #99f6e4;
        background: #0d9488;
        color: #fff;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        font-size: 1.1rem;
        line-height: 1;
        flex-shrink: 0;
    }
    .lms-chat-add-btn:hover { background: #0f766e; }
    .lms-chat-peer-list { flex: 1; overflow-y: auto; }
    .lms-chat-pick-list {
        max-height: 16rem;
        overflow-y: auto;
        margin-top: 0.5rem;
    }
    .lms-chat-pick-item {
        width: 100%;
        text-align: left;
        padding: 0.65rem 0.75rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.65rem;
        background: #fff;
        margin-bottom: 0.4rem;
        cursor: pointer;
        font-size: 0.875rem;
    }
    .lms-chat-pick-item:hover {
        border-color: #99f6e4;
        background: #f0fdfa;
    }
    .lms-chat-person {
        width: 100%;
        text-align: left;
        padding: 0.7rem 0.85rem;
        border: 0;
        border-bottom: 1px solid #f1f5f9;
        background: transparent;
        cursor: pointer;
        font-size: 0.8125rem;
        color: #334155;
        transition: background 0.15s;
    }
    .lms-chat-person:hover { background: #f0fdfa; }
    .lms-chat-person.is-active {
        background: #ccfbf1;
        color: #115e59;
        font-weight: 600;
        box-shadow: inset 3px 0 0 #0d9488;
    }
    .lms-chat-messages {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.55rem;
        background: linear-gradient(180deg, #fafafa 0%, #fff 40%);
        min-height: 0;
    }

    .lms-chat-row { display: flex; flex-direction: column; max-width: 85%; }
    .lms-chat-row.is-mine { align-self: flex-end; align-items: flex-end; }
    .lms-chat-row.is-theirs { align-self: flex-start; align-items: flex-start; }
    .lms-chat-bubble {
        border-radius: 1rem;
        padding: 0.55rem 0.85rem;
        font-size: 0.875rem;
        line-height: 1.4;
        word-break: break-word;
    }
    .lms-chat-row.is-mine .lms-chat-bubble {
        background: #0d9488;
        color: #ffffff;
        border-bottom-right-radius: 0.3rem;
    }
    .lms-chat-row.is-theirs .lms-chat-bubble {
        background: #e2e8f0;
        color: #1e293b;
        border-bottom-left-radius: 0.3rem;
    }
    .lms-chat-meta {
        font-size: 0.65rem;
        color: #94a3b8;
        margin-top: 0.15rem;
        padding: 0 0.25rem;
    }
    .lms-chat-composer {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        padding: 0.75rem;
        border-top: 1px solid #e2e8f0;
        background: #fff;
    }
    .lms-chat-composer input {
        flex: 1;
        min-width: 10rem;
        border: 1px solid #cbd5e1;
        border-radius: 0.75rem;
        padding: 0.55rem 0.85rem;
        font-size: 0.875rem;
    }
    #chat-status {
        flex-basis: 100%;
        min-height: 1rem;
        padding: 0 0.15rem;
    }

    /* Star rating — chọn 1–5 rõ ràng */
    .lms-rate {
        display: flex;
        flex-wrap: wrap;
        gap: 0.45rem;
    }
    .lms-rate-opt {
        position: relative;
        cursor: pointer;
        display: inline-flex;
        flex-direction: column;
        align-items: center;
        gap: 0.15rem;
        min-width: 2.75rem;
        padding: 0.45rem 0.35rem;
        border-radius: 0.75rem;
        border: 1px solid #e2e8f0;
        background: #fff;
        transition: border-color 0.15s, background 0.15s, box-shadow 0.15s;
    }
    .lms-rate-opt input {
        position: absolute;
        opacity: 0;
        pointer-events: none;
    }
    .lms-rate-star {
        font-size: 1.35rem;
        line-height: 1;
        color: #cbd5e1;
        transition: color 0.15s, transform 0.15s;
    }
    .lms-rate-num {
        font-size: 0.7rem;
        font-weight: 700;
        color: #94a3b8;
    }
    .lms-rate-opt:hover {
        border-color: #fcd34d;
        background: #fffbeb;
    }
    .lms-rate-opt:hover .lms-rate-star { color: #f59e0b; transform: scale(1.1); }
    .lms-rate-opt:has(input:checked) {
        border-color: #f59e0b;
        background: #fffbeb;
        box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.25);
    }
    .lms-rate-opt:has(input:checked) .lms-rate-star { color: #f59e0b; }
    .lms-rate-opt:has(input:checked) .lms-rate-num { color: #b45309; }

    /* legacy */
    .lms-stars {
        display: inline-flex;
        flex-direction: row-reverse;
        gap: 0.2rem;
        justify-content: flex-end;
    }
    .lms-stars input { display: none; }
    .lms-stars label {
        cursor: pointer;
        font-size: 1.45rem;
        color: #cbd5e1;
        line-height: 1;
    }
    .lms-stars label:hover,
    .lms-stars label:hover ~ label,
    .lms-stars input:checked ~ label { color: #f59e0b; }

    /* Button sync home (solid) */
    .lms-btn-solid {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        font-weight: 600;
        font-size: 0.9rem;
        color: #fff !important;
        background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%);
        border-radius: 0.75rem;
        padding: 0.65rem 1.25rem;
        border: none;
        box-shadow: 0 10px 22px -12px rgba(13, 148, 136, 0.45), inset 0 1px 0 rgba(255,255,255,0.25);
        text-decoration: none !important;
        cursor: pointer;
        transition: filter 0.2s, box-shadow 0.25s;
    }
    .lms-btn-solid:hover {
        filter: brightness(1.05);
        box-shadow: 0 14px 28px -10px rgba(13, 148, 136, 0.5);
    }

    /* Điểm danh miệng — switch chip Có mặt / Vắng / Muộn / Phép */
    .att-status-switch {
        display: inline-flex;
        flex-wrap: wrap;
        gap: 0.3rem;
        padding: 0.2rem;
        background: #f1f5f9;
        border-radius: 0.65rem;
        border: 1px solid #e2e8f0;
    }
    .att-chip {
        border: 0;
        cursor: pointer;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 0.35rem 0.65rem;
        border-radius: 0.5rem;
        color: #64748b;
        background: transparent;
        transition: background 0.15s ease, color 0.15s ease, box-shadow 0.15s ease;
        line-height: 1.2;
    }
    .att-chip:hover { color: #0f172a; background: rgba(255,255,255,0.7); }
    .att-chip.is-on.is-present {
        background: #d1fae5;
        color: #047857;
        box-shadow: 0 1px 2px rgba(16, 185, 129, 0.2);
    }
    .att-chip.is-on.is-absent {
        background: #fee2e2;
        color: #b91c1c;
        box-shadow: 0 1px 2px rgba(239, 68, 68, 0.15);
    }
    .att-chip.is-on.is-late {
        background: #fef3c7;
        color: #b45309;
        box-shadow: 0 1px 2px rgba(245, 158, 11, 0.15);
    }
    .att-chip.is-on.is-excused {
        background: #e0f2fe;
        color: #0369a1;
        box-shadow: 0 1px 2px rgba(14, 165, 233, 0.15);
    }
    tr.att-row[data-status="present"] { background: rgba(236, 253, 245, 0.35); }
    tr.att-row[data-status="absent"] { background: rgba(254, 242, 242, 0.35); }
    tr.att-row[data-status="late"] { background: rgba(255, 251, 235, 0.4); }
    tr.att-row[data-status="excused"] { background: rgba(240, 249, 255, 0.45); }

    /* Notice tĩnh (không phải flash toast) — vd. banner chế độ dạy */
    .lms-notice {
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        padding: 0.85rem 1rem;
        border-radius: 0.9rem;
        border: 1px solid #99f6e4;
        background: linear-gradient(135deg, rgba(240, 253, 250, 0.95) 0%, rgba(255, 255, 255, 0.92) 100%);
        color: #115e59;
        font-size: 0.875rem;
        box-shadow: 0 8px 22px -16px rgba(13, 148, 136, 0.35);
    }
    .lms-notice-icon {
        width: 2rem;
        height: 2rem;
        border-radius: 0.65rem;
        background: #ccfbf1;
        color: #0d9488;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    /* Legacy flash class — ẩn nếu còn sót (toast Notify thay thế) */
    .lms-flash,
    .lms-flash-error,
    .lms-flash-warn {
        display: none !important;
    }

    /* Section header bar inside cards */
    .lms-card-head {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e2e8f0;
        font-weight: 600;
        color: #1e293b;
        background: linear-gradient(180deg, rgba(240, 253, 250, 0.65) 0%, rgba(255,255,255,0.9) 100%);
    }

    /* Links default teal */
    .lms-shell a.text-teal-700:hover,
    .lms-shell a[class*="text-teal"]:hover {
        color: var(--lms-brand-deep) !important;
    }

    @media (prefers-reduced-motion: reduce) {
        .lms-card, .lms-chip, .lms-btn, .lms-nav a, .lms-nav button {
            transition: none !important;
        }
    }
</style>
