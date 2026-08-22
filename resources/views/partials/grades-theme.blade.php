<style>
    :root {
        --gr-orange: #ea580c;
        --gr-orange-soft: #fb923c;
        --gr-teal: #0d9488;
        --gr-teal-deep: #0f766e;
        --gr-ink: #1f2937;
        --gr-muted: #64748b;
        --gr-bg: #fff7ed;
        --gr-card: #ffffff;
        --gr-border: #fed7aa;
        --gr-ring: rgba(234, 88, 12, 0.28);
    }
    html.grades-html, body.grades-shell {
        margin: 0;
        min-height: 100%;
        background:
            radial-gradient(900px 420px at 0% -10%, rgba(234, 88, 12, 0.14), transparent 55%),
            radial-gradient(800px 400px at 100% 0%, rgba(13, 148, 136, 0.12), transparent 50%),
            linear-gradient(180deg, #fff7ed 0%, #f0fdfa 50%, #f8fafc 100%);
        color: var(--gr-ink);
        font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
    }
    .grades-top {
        position: sticky; top: 0; z-index: 40;
        backdrop-filter: blur(12px);
        background: rgba(255, 247, 237, 0.92);
        border-bottom: 1px solid var(--gr-border);
        box-shadow: 0 8px 24px -18px rgba(15, 23, 42, 0.2);
        width: 100%;
    }
    /* Header cùng bề rộng 96rem với LMS; vùng nội dung vẫn giữ 72rem để dễ đọc. */
    .grades-top-inner {
        max-width: 96rem;
        margin: 0 auto;
        width: 100%;
        box-sizing: border-box;
        padding-left: 1rem;
        padding-right: 1rem;
    }
    @media (min-width: 640px) {
        .grades-top-inner {
            padding-left: 1.5rem;
            padding-right: 1.5rem;
        }
    }
    .grades-main {
        max-width: 72rem;
        margin: 0 auto;
        padding: 1.25rem 1rem 2.5rem;
        width: 100%;
        box-sizing: border-box;
    }
    @media (min-width: 640px) {
        .grades-main { padding: 1.5rem 1.5rem 2.75rem; }
    }
    @media (min-width: 1024px) {
        .grades-top-inner,
        .grades-main {
            padding-left: 1.75rem;
            padding-right: 1.75rem;
        }
    }
    /* Nav header: không bóp logo/chữ khi nhiều link */
    .grades-nav {
        flex: 1 1 auto;
        justify-content: flex-end;
        min-width: 0;
    }
    .grades-brand {
        flex: 0 1 auto;
        min-width: 0;
    }
    .grades-brand-text small {
        max-width: min(20rem, 40vw);
    }
    .grades-brand {
        gap: 0.85rem; /* tách logo ↔ chữ */
    }
    .grades-brand-logo {
        width: 2.5rem;
        height: 2.5rem;
        object-fit: contain;
        border-radius: 0.7rem;
        background: #fff;
        border: 1px solid rgba(234, 88, 12, 0.28);
        box-shadow: 0 4px 12px -6px rgba(234, 88, 12, 0.45);
        padding: 0.12rem;
    }
    .grades-brand-text {
        display: flex;
        flex-direction: column;
        gap: 0.12rem;
        line-height: 1.2;
    }
    .grades-nav a {
        display: inline-flex; align-items: center;
        padding: 0.4rem 0.75rem; border-radius: 0.65rem;
        font-size: 0.875rem; font-weight: 600; color: #334155;
        text-decoration: none; transition: background .15s, color .15s;
    }
    .grades-nav a:hover { background: rgba(234, 88, 12, 0.1); color: var(--gr-orange); }
    .grades-nav a.is-active {
        background: linear-gradient(135deg, rgba(234,88,12,.16), rgba(13,148,136,.14));
        color: var(--gr-teal-deep);
        box-shadow: 0 0 0 1px rgba(13,148,136,.2);
    }
    /* Chuông thông báo — cùng pattern LMS */
    .grades-nav-icon-btn {
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
    .grades-nav-icon-btn:hover {
        background: rgba(255, 237, 213, 0.75);
        border-color: rgba(234, 88, 12, 0.28);
        box-shadow: 0 4px 12px -6px rgba(234, 88, 12, 0.4);
        color: #c2410c;
    }
    .grades-notif-badge {
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
    .grades-notif-badge:not(:empty):not([data-count="0"]) {
        display: inline-flex;
    }
    .grades-card {
        background: var(--gr-card);
        border: 1px solid #e2e8f0;
        border-radius: 1rem;
        box-shadow: 0 10px 30px -18px rgba(15,23,42,.18);
    }
    .grades-btn {
        display: inline-flex; align-items: center; gap: .35rem;
        padding: .45rem .85rem; border-radius: .65rem;
        font-size: .875rem; font-weight: 600; border: 1px solid transparent;
        cursor: pointer; text-decoration: none;
    }
    .grades-btn-solid {
        background: linear-gradient(135deg, var(--gr-orange), #c2410c);
        color: #fff;
    }
    .grades-btn-solid:hover { filter: brightness(1.05); color: #fff; }
    .grades-btn-teal {
        background: linear-gradient(135deg, var(--gr-teal), var(--gr-teal-deep));
        color: #fff;
    }
    .grades-btn-ghost {
        background: #fff; border-color: #e2e8f0; color: #334155;
    }
    .grades-btn-ghost:hover { border-color: var(--gr-orange-soft); color: var(--gr-orange); }
    .grades-chip {
        display: inline-flex; padding: .15rem .5rem; border-radius: 999px;
        font-size: .7rem; font-weight: 700; text-transform: uppercase; letter-spacing: .03em;
    }
    .grades-chip-open { background: #ccfbf1; color: #115e59; }
    .grades-chip-lock { background: #ffedd5; color: #9a3412; }
    .grades-chip-ok { background: #d1fae5; color: #065f46; }
    .grades-chip-wait { background: #fef3c7; color: #92400e; }
    .grades-table th { font-size: .7rem; text-transform: uppercase; letter-spacing: .04em; color: #64748b; }
    .grades-table input[type=number] {
        width: 4.25rem; border: 1px solid #e2e8f0; border-radius: .4rem;
        padding: .25rem .4rem; font-size: .875rem; text-align: center;
    }
    .grades-table input[type=number]:focus {
        outline: none; border-color: var(--gr-orange); box-shadow: 0 0 0 3px var(--gr-ring);
    }
</style>
