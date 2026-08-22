<style>
    /*
     * Theme admin = palette trang Home (/)
     * Brand xanh #4ea1ff · ivory #faf8f4 · sidebar #174766
     */
    :root {
        --brand: #4ea1ff;
        --brand-hover: #358fee;
        --brand-dark: #3580d6;
        --brand-soft: #6eb5ff;
        --brand-softest: #8ec5ff;
        --brand-sidebar: #174766;
        --brand-sidebar-deep: #123a55;
        --brand-link: #4ea1ff;
        --brand-link-hover: #2f86e8;
        --brand-ring: rgba(78, 161, 255, 0.38);
        --ivory: #faf8f4;
        --ivory-soft: #f5f3ef;
        --gray-light: #eceef2;
        --gray-muted: #e2e6ec;
        --gray-border: #d5dae3;
        --text-primary: #1f2937;
        --glass-blur: 12px;
        --glass-blur-strong: 16px;
        --ui-border: var(--gray-border);
        --ui-border-soft: rgba(255, 255, 255, 0.55);
        --glass-shadow: 0 10px 30px -14px rgba(78, 161, 255, 0.16);
        --glass-inset: inset 0 1px 0 rgba(255, 255, 255, 0.75);
        --glow-soft: 0 0 0 1px rgba(78, 161, 255, 0.22), 0 0 14px rgba(78, 161, 255, 0.2);
        --glow-medium: 0 0 0 1px rgba(78, 161, 255, 0.32), 0 0 18px rgba(78, 161, 255, 0.32), 0 0 32px rgba(78, 161, 255, 0.14);
        --glow-strong: 0 0 0 1px rgba(78, 161, 255, 0.42), 0 0 22px rgba(78, 161, 255, 0.42), 0 0 40px rgba(78, 161, 255, 0.18);
        --glow-sidebar: 0 0 0 1px rgba(142, 197, 255, 0.28), 0 0 14px rgba(78, 161, 255, 0.28);
    }

    /* Glow hover utility */
    .hover-glow {
        transition: box-shadow 0.28s ease, border-color 0.28s ease, background-color 0.2s ease, color 0.2s ease !important;
    }

    .hover-glow:hover {
        transform: none !important;
        box-shadow: var(--glow-soft) !important;
    }

    .hover-glow-strong:hover {
        transform: none !important;
        box-shadow: var(--glow-strong) !important;
    }

    /* Neutralize lift/scale hover utilities */
    [class*="hover:-translate-y"]:hover,
    [class*="hover:translate-y"]:hover,
    [class*="hover:scale"]:hover,
    .group:hover [class*="group-hover:scale"] {
        transform: none !important;
    }

    input:focus,
    select:focus,
    textarea:focus,
    button:focus-visible,
    a:focus-visible {
        transform: none !important;
    }

    body {
        /* Mesh nhẹ như trang Home */
        background:
            radial-gradient(1100px 520px at 8% -8%, rgba(78, 161, 255, 0.16), transparent 55%),
            radial-gradient(900px 480px at 96% 0%, rgba(53, 143, 238, 0.1), transparent 50%),
            linear-gradient(180deg, var(--ivory) 0%, #eef6ff 45%, var(--gray-light) 100%) !important;
        color: var(--text-primary);
    }

    #admin-content {
        background: transparent !important;
    }

    /* Sidebar — cùng footer/home deep navy */
    #sidebar {
        background: linear-gradient(180deg, var(--brand-sidebar) 0%, var(--brand-sidebar-deep) 100%) !important;
        backdrop-filter: blur(var(--glass-blur-strong));
        -webkit-backdrop-filter: blur(var(--glass-blur-strong));
        border-right: 1px solid rgba(142, 197, 255, 0.14);
        box-shadow: 4px 0 28px -10px rgba(18, 58, 85, 0.5);
    }

    #sidebar,
    #sidebar .sidebar-text,
    #sidebar #sidebar-title {
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.18);
    }

    #sidebar .border-gray-700,
    #sidebar .border-b {
        border-color: rgba(255, 255, 255, 0.1) !important;
    }

    #sidebar a {
        border: 1px solid transparent;
        border-radius: 0.5rem;
        margin-inline: 0.5rem;
    }

    #sidebar a.hover\:bg-gray-700:hover,
    #sidebar .hover\:bg-gray-700:hover,
    #sidebar button.hover\:bg-gray-700:hover,
    #sidebar a:hover:not(.bg-blue-600) {
        background: rgba(255, 255, 255, 0.1) !important;
        border-color: rgba(142, 197, 255, 0.35) !important;
        box-shadow: var(--glow-sidebar);
        transform: none !important;
    }

    #sidebar a.bg-blue-600,
    #sidebar .bg-blue-600 {
        background: var(--brand) !important;
        border-color: rgba(255, 255, 255, 0.28) !important;
        box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.22), 0 4px 14px -6px rgba(78, 161, 255, 0.55);
        text-shadow: 0 1px 1px rgba(0, 0, 0, 0.12);
    }

    /* Header — gradient brand giống nút Home */
    .admin-top-header {
        position: relative;
        z-index: 200;
        background: linear-gradient(135deg, var(--brand-soft) 0%, var(--brand) 42%, var(--brand-hover) 100%) !important;
        backdrop-filter: blur(var(--glass-blur-strong));
        -webkit-backdrop-filter: blur(var(--glass-blur-strong));
        border-bottom: 1px solid rgba(255, 255, 255, 0.32);
        box-shadow:
            0 8px 24px -10px rgba(78, 161, 255, 0.45),
            inset 0 1px 0 rgba(255, 255, 255, 0.28);
        text-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
    }

    #admin-content {
        position: relative;
        z-index: 1;
    }

    #userDropdown,
    #notification-panel {
        z-index: 300 !important;
    }

    /* Content panels — ivory/white card kiểu Home */
    .glass-panel,
    .bg-white.rounded-lg,
    .bg-white.rounded-xl,
    .bg-white.rounded-lg.shadow-sm,
    .bg-white.rounded-lg.shadow,
    .bg-white.rounded-xl.shadow-sm,
    .bg-white.rounded-lg.border,
    .bg-white.rounded-xl.border {
        background: linear-gradient(165deg, rgba(250, 248, 244, 0.98) 0%, #ffffff 55%) !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
        border: 1px solid var(--ui-border) !important;
        box-shadow: 0 1px 3px rgba(23, 71, 102, 0.05), 0 10px 28px -18px rgba(78, 161, 255, 0.18) !important;
        transition: box-shadow 0.22s ease, border-color 0.22s ease;
    }

    .glass-panel:hover,
    .bg-white.rounded-lg:hover,
    .bg-white.rounded-xl:hover,
    a.bg-white.rounded-lg:hover,
    a.bg-white.rounded-xl:hover {
        transform: none !important;
        border-color: rgba(78, 161, 255, 0.42) !important;
        box-shadow: 0 2px 10px rgba(78, 161, 255, 0.14), 0 12px 28px -16px rgba(78, 161, 255, 0.22) !important;
    }

    #admin-content .container > .bg-white,
    #admin-content table,
    #admin-content .overflow-hidden.shadow.ring-1 {
        border: 1px solid var(--ui-border) !important;
    }

    #admin-content .overflow-hidden.shadow.ring-1 {
        background: #ffffff !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06) !important;
        --tw-ring-color: rgba(148, 163, 184, 0.2) !important;
    }

    /* Bảng / form inputs luôn nền solid */
    #admin-content table thead,
    #admin-content table tbody,
    #admin-content .bg-gray-50,
    #admin-content .bg-slate-50,
    #admin-content .bg-slate-100 {
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }

    #admin-content input:not([type="checkbox"]):not([type="radio"]),
    #admin-content select,
    #admin-content textarea {
        background-color: #ffffff !important;
    }

    /* Dropdowns & modals */
    #userDropdown,
    #notification-panel,
    #confirm-modal .relative.bg-white,
    #toast-container > div {
        backdrop-filter: blur(var(--glass-blur-strong));
        -webkit-backdrop-filter: blur(var(--glass-blur-strong));
        border: 1px solid var(--ui-border) !important;
        box-shadow: var(--glass-shadow), var(--glass-inset);
    }

    #userDropdown,
    #notification-panel {
        background: rgba(250, 248, 244, 0.92) !important;
    }

    #confirm-modal .relative.bg-white {
        background: rgba(250, 248, 244, 0.95) !important;
    }

    #notification-panel .notification-item {
        border-bottom: 1px solid var(--gray-muted) !important;
    }

    #notification-panel .notification-item.is-unread {
        background: rgba(239, 246, 255, 0.85) !important;
        border-left: 3px solid var(--brand);
    }

    /* Buttons — gradient brand Home */
    .brand-btn,
    .bg-blue-600,
    a.bg-blue-600,
    button.bg-blue-600,
    input.bg-blue-600[type="submit"] {
        background: linear-gradient(135deg, var(--brand) 0%, var(--brand-hover) 100%) !important;
        color: #fff !important;
        border: 1px solid rgba(255, 255, 255, 0.28) !important;
        box-shadow: 0 8px 20px -10px rgba(78, 161, 255, 0.48), inset 0 1px 0 rgba(255, 255, 255, 0.32);
        transition: background 0.2s ease, box-shadow 0.28s ease, border-color 0.2s ease, opacity 0.15s ease, filter 0.15s ease;
    }

    .brand-btn:hover,
    a.bg-blue-600:hover,
    button.bg-blue-600:hover {
        filter: brightness(1.04);
    }

    .brand-btn:hover,
    .hover\:bg-blue-700:hover:not([data-notification-trigger]):not([data-user-menu-trigger]),
    a.hover\:bg-blue-700:hover:not([data-notification-trigger]):not([data-user-menu-trigger]),
    button.hover\:bg-blue-700:hover:not([data-notification-trigger]):not([data-user-menu-trigger]),
    .bg-green-600:hover,
    .bg-red-600:hover,
    .bg-gray-600:hover,
    .bg-purple-600:hover,
    .bg-indigo-600:hover {
        transform: none !important;
        border-color: rgba(255, 255, 255, 0.38) !important;
        box-shadow: var(--glow-strong), inset 0 1px 0 rgba(255, 255, 255, 0.28) !important;
    }

    .brand-btn:active,
    button.bg-blue-600:active,
    a.bg-blue-600:active {
        transform: none !important;
        opacity: 0.9;
        box-shadow: var(--glow-medium) !important;
    }

    /* Form controls */
    select,
    input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]):not([type="file"]),
    textarea,
    .form-input,
    .form-select,
    .form-textarea,
    .ts-control {
        background: rgba(250, 248, 244, 0.9) !important;
        border: 1px solid var(--ui-border) !important;
        box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.03);
    }

    .ts-wrapper .ts-control {
        border-radius: 0.5rem !important;
    }

    .text-blue-600,
    a.text-blue-600 {
        color: var(--brand-link) !important;
    }

    .text-blue-600:hover,
    a.text-blue-600:hover,
    .hover\:text-blue-800:hover,
    .hover\:text-blue-900:hover {
        color: var(--brand-link-hover) !important;
        text-shadow: 0 0 10px rgba(78, 161, 255, 0.35);
    }

    .text-blue-700 { color: var(--brand) !important; }
    .text-blue-800, .text-blue-900 { color: var(--brand-dark) !important; }

    .focus\:ring-blue-500:focus,
    .focus\:ring-blue-600:focus {
        --tw-ring-color: var(--brand-ring) !important;
    }

    .focus\:border-blue-500:focus,
    .focus\:border-blue-600:focus {
        border-color: var(--brand) !important;
    }

    .border-blue-500,
    .border-blue-600 {
        border-color: #8ec5ff !important;
    }

    .border-gray-200,
    .border-gray-300 {
        border-color: var(--ui-border) !important;
    }

    .bg-gray-100 {
        background-color: var(--gray-light) !important;
    }

    .bg-blue-50,
    .from-blue-50 {
        background-color: #eef6ff !important;
    }

    .to-blue-100,
    .bg-blue-100 {
        background-color: #dceeff !important;
    }

    .bg-blue-500,
    .bg-blue-600 { background-color: var(--brand) !important; }
    .bg-blue-700 { background-color: var(--brand-hover) !important; }

    [class*="bg-gradient"][class*="from-blue"][class*="to-blue"] {
        background-image: linear-gradient(to bottom right, #6eb5ff, #4ea1ff) !important;
        border: 1px solid rgba(255, 255, 255, 0.25);
    }

    [class*="bg-gradient-to-r"][class*="from-blue-500"][class*="to-blue-600"],
    [class*="bg-gradient-to-r"][class*="from-blue-600"][class*="to-blue-700"],
    [class*="bg-gradient-to-br"][class*="from-blue-500"][class*="to-blue-600"],
    [class*="bg-gradient-to-br"][class*="from-blue-600"][class*="to-blue-700"] {
        background-image: linear-gradient(to right, #6eb5ff, #4ea1ff) !important;
        border: 1px solid rgba(255, 255, 255, 0.25);
    }

    .turbo-progress {
        background: var(--brand) !important;
    }

    #notification-mark-all {
        color: var(--brand-link) !important;
    }

    #notification-mark-all:hover {
        color: var(--brand-link-hover) !important;
    }

    .page-title-gradient {
        color: var(--brand-dark) !important;
        background: none;
        -webkit-text-fill-color: currentColor;
    }

    .ts-wrapper.dropdown-active .ts-control,
    select:focus,
    input:not([type="checkbox"]):not([type="radio"]):focus,
    textarea:focus {
        border-color: var(--brand) !important;
        box-shadow: 0 0 0 3px var(--brand-ring), inset 0 1px 2px rgba(15, 23, 42, 0.03) !important;
    }

    input[type="checkbox"],
    input[type="radio"],
    .form-checkbox,
    .form-radio {
        accent-color: var(--brand);
    }

    .pagination .bg-blue-600,
    nav[role="navigation"] .bg-blue-600 {
        background: var(--brand) !important;
        border: 1px solid rgba(255, 255, 255, 0.22) !important;
    }

    /* ===== Unified pagination (< 1 2 3 >) ===== */
    .ui-pagination {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.75rem;
    }

    .ui-pagination__mobile {
        display: flex;
        width: 100%;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
    }

    @media (min-width: 640px) {
        .ui-pagination__mobile { display: none; }
    }

    .ui-pagination__desktop {
        display: none;
        width: 100%;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        flex-wrap: wrap;
    }

    @media (min-width: 640px) {
        .ui-pagination__desktop {
            display: flex;
        }
    }

    .ui-pagination__summary {
        margin: 0;
        font-size: 0.8125rem;
        color: #6b7280;
        line-height: 1.25rem;
    }

    .ui-pagination__summary strong {
        color: #1f2937;
        font-weight: 600;
    }

    .ui-pagination__list,
    .ui-pagination__simple {
        list-style: none;
        margin: 0;
        padding: 0.25rem;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        flex-wrap: wrap;
        justify-content: center;
        background: rgba(255, 255, 255, 0.92);
        border: 1px solid var(--gray-border);
        border-radius: 0.875rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), inset 0 1px 0 rgba(255, 255, 255, 0.8);
    }

    .ui-pagination__simple {
        display: inline-flex;
        gap: 0.35rem;
        padding: 0.3rem;
    }

    .ui-pagination__page,
    .ui-pagination__btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2.25rem;
        height: 2.25rem;
        padding: 0 0.65rem;
        border-radius: 0.625rem;
        border: 1px solid transparent;
        font-size: 0.875rem;
        font-weight: 600;
        line-height: 1;
        color: #374151;
        background: transparent;
        text-decoration: none !important;
        transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease, box-shadow 0.2s ease;
        user-select: none;
        box-sizing: border-box;
    }

    .ui-pagination__page.is-nav {
        color: #4b5563;
        min-width: 2.25rem;
        padding: 0;
    }

    .ui-pagination__page:hover:not(.is-disabled):not(.is-active):not(.is-ellipsis),
    .ui-pagination__btn:hover:not(.is-disabled) {
        background: rgba(78, 161, 255, 0.1);
        border-color: rgba(78, 161, 255, 0.22);
        color: var(--brand-dark);
        box-shadow: 0 0 0 1px rgba(78, 161, 255, 0.08);
    }

    .ui-pagination__page.is-active {
        background: linear-gradient(180deg, #5aacff 0%, var(--brand) 100%) !important;
        color: #ffffff !important;
        border-color: rgba(255, 255, 255, 0.2) !important;
        box-shadow: 0 2px 8px -2px rgba(78, 161, 255, 0.55), inset 0 1px 0 rgba(255, 255, 255, 0.25);
        cursor: default;
    }

    .ui-pagination__page.is-disabled,
    .ui-pagination__btn.is-disabled {
        color: #c4c9d2 !important;
        cursor: not-allowed;
        background: transparent !important;
        border-color: transparent !important;
        box-shadow: none !important;
        opacity: 0.85;
    }

    .ui-pagination__page.is-ellipsis {
        min-width: 1.75rem;
        color: #9ca3af;
        font-weight: 500;
        cursor: default;
        pointer-events: none;
    }

    .ui-pagination__btn {
        min-width: 4.5rem;
        height: 2.35rem;
        border: 1px solid var(--gray-border);
        background: #fff;
        border-radius: 0.625rem;
        color: #374151;
    }

    .ui-pagination__meta-mobile {
        font-size: 0.8125rem;
        font-weight: 600;
        color: #4b5563;
        white-space: nowrap;
    }

    /* Legacy bootstrap-style ul.pagination (nếu còn dùng default view) */
    ul.pagination {
        list-style: none;
        margin: 0;
        padding: 0.25rem;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        flex-wrap: wrap;
        background: rgba(255, 255, 255, 0.92);
        border: 1px solid var(--gray-border);
        border-radius: 0.875rem;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    }

    ul.pagination > li > a,
    ul.pagination > li > span {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 2.25rem;
        height: 2.25rem;
        padding: 0 0.65rem;
        border-radius: 0.625rem;
        border: 1px solid transparent;
        font-size: 0.875rem;
        font-weight: 600;
        color: #374151;
        text-decoration: none !important;
        background: transparent;
        transition: background-color 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }

    ul.pagination > li > a:hover {
        background: rgba(78, 161, 255, 0.1);
        border-color: rgba(78, 161, 255, 0.22);
        color: var(--brand-dark);
    }

    ul.pagination > li.active > span,
    ul.pagination > li.active > a {
        background: linear-gradient(180deg, #5aacff 0%, var(--brand) 100%) !important;
        color: #fff !important;
        border-color: transparent !important;
        box-shadow: 0 2px 8px -2px rgba(78, 161, 255, 0.55);
    }

    ul.pagination > li.disabled > span,
    ul.pagination > li.disabled > a {
        color: #c4c9d2 !important;
        cursor: not-allowed;
        opacity: 0.85;
    }

    tbody tr {
        border-bottom: 1px solid var(--gray-muted);
        transition: box-shadow 0.22s ease, background-color 0.15s ease;
    }

    tbody tr:hover {
        box-shadow: inset 0 0 0 1px rgba(78, 161, 255, 0.14), 0 0 12px rgba(78, 161, 255, 0.1);
    }

    #notification-panel .notification-item:hover,
    #userDropdown a:hover,
    #userDropdown button:hover {
        box-shadow: inset 0 0 0 1px rgba(78, 161, 255, 0.12), 0 0 10px rgba(78, 161, 255, 0.1);
        transform: none !important;
    }

    .view-mode-btn > div:hover,
    .tab-btn:hover {
        box-shadow: var(--glow-soft) !important;
        transform: none !important;
    }

    /* Gradient & shadow-lg buttons → glow */
    [class*="bg-gradient-to-"]:hover,
    [class*="hover:shadow-lg"]:hover {
        transform: none !important;
        box-shadow: var(--glow-strong) !important;
    }

    [class*="from-green"]:hover,
    [class*="to-emerald"]:hover,
    .bg-green-600:hover,
    .hover\:bg-green-700:hover {
        box-shadow: 0 0 0 1px rgba(34, 197, 94, 0.32), 0 0 18px rgba(34, 197, 94, 0.28), 0 0 32px rgba(34, 197, 94, 0.12) !important;
    }

    [class*="from-gray-5"]:hover,
    [class*="from-gray-6"]:hover,
    [class*="to-gray-6"]:hover,
    [class*="to-gray-7"]:hover {
        box-shadow: 0 0 0 1px rgba(107, 114, 128, 0.28), 0 0 16px rgba(107, 114, 128, 0.22) !important;
    }

    .bg-red-600:hover,
    .hover\:bg-red-700:hover,
    [class*="hover:bg-red"]:hover {
        box-shadow: 0 0 0 1px rgba(239, 68, 68, 0.32), 0 0 18px rgba(239, 68, 68, 0.28) !important;
    }

    [class*="hover:border-blue"]:hover,
    [class*="hover:border-red"]:hover,
    [class*="hover:border-gray"]:hover,
    [class*="hover:bg-blue-50"]:hover,
    [class*="hover:bg-red-50"]:hover,
    [class*="hover:bg-gray-50"]:hover {
        box-shadow: var(--glow-soft) !important;
        transform: none !important;
    }

    .group:hover [class*="group-hover:scale"] {
        filter: drop-shadow(0 0 6px rgba(78, 161, 255, 0.45));
    }

    /*
     * Header bảng: nền ivory (ghi đè bg-blue-500 cũ).
     * Link sort trước đây dùng text-white → chữ trắng trên nền sáng → không đọc được.
     */
    thead th {
        background: var(--ivory-soft) !important;
        border-bottom: 1px solid var(--gray-border) !important;
        color: #1f2937 !important;
    }

    #admin-content thead th,
    #admin-content thead th a,
    #admin-content thead th span,
    #admin-content thead th label {
        color: #1f2937 !important;
    }

    #admin-content thead th a {
        font-weight: 600;
        text-decoration: none !important;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        transition: color 0.15s ease;
    }

    #admin-content thead th a:hover {
        color: var(--brand-dark) !important;
    }

    #admin-content thead th a .bi-arrow-up,
    #admin-content thead th a .bi-arrow-down,
    #admin-content thead th a [class*="bi-arrow"] {
        color: var(--brand) !important;
        opacity: 1;
    }

    #admin-content thead th a .bi-arrow-down-up {
        color: #94a3b8 !important;
    }

    /* Checkbox trong header không bị kéo màu chữ */
    #admin-content thead th input[type="checkbox"] {
        accent-color: var(--brand);
    }

    /* Header: chuông thông báo & profile — glow, không nổi lên */
    .admin-top-header [data-notification-trigger],
    .admin-top-header [data-user-menu-trigger],
    .admin-top-header .header-action-btn {
        border: 1px solid transparent !important;
        background: transparent !important;
        box-shadow: none !important;
        transform: none !important;
        transition: background-color 0.2s ease, box-shadow 0.28s ease, border-color 0.28s ease, opacity 0.15s ease !important;
    }

    .admin-top-header [data-notification-trigger]:hover,
    .admin-top-header [data-user-menu-trigger]:hover,
    .admin-top-header .header-action-btn:hover {
        background: rgba(255, 255, 255, 0.18) !important;
        border-color: rgba(255, 255, 255, 0.42) !important;
        box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.28), 0 0 16px rgba(255, 255, 255, 0.45), 0 0 28px rgba(255, 255, 255, 0.2) !important;
        transform: none !important;
    }

    .admin-top-header [data-notification-trigger]:active,
    .admin-top-header [data-user-menu-trigger]:active,
    .admin-top-header .header-action-btn:active {
        transform: none !important;
        opacity: 0.88;
        box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.22), 0 0 12px rgba(255, 255, 255, 0.35) !important;
    }

    .admin-top-header [data-notification-trigger]:hover .bi-bell,
    .admin-top-header [data-user-menu-trigger]:hover .bi-person-circle {
        filter: drop-shadow(0 0 6px rgba(255, 255, 255, 0.65));
    }

    /* =========================================================
     * Icon thao tác (Xem / Sửa / Xóa / Copy / …)
     * Khung rõ + glow hover — áp dụng toàn admin
     * ========================================================= */
    #admin-content table td .flex.space-x-2,
    #admin-content table td .flex.gap-2,
    #admin-content table td .flex.gap-3,
    #admin-content table td .inline-flex.space-x-2,
    #admin-content table td .inline-flex.gap-2,
    #admin-content table td .table-row-actions,
    #admin-content .action-icons {
        display: inline-flex !important;
        align-items: center !important;
        flex-wrap: wrap;
        gap: 0.4rem !important;
        column-gap: 0.4rem !important;
        row-gap: 0.4rem !important;
    }

    /* Form.inline bọc nút xóa/toggle — không làm lệch hàng */
    #admin-content table td form.inline {
        display: inline-flex !important;
        align-items: center !important;
        margin: 0 !important;
        vertical-align: middle;
    }

    /*
     * Icon-only controls trong bảng + class tường minh
     * CHỈ áp dụng khi là nút icon-only (không có chữ/span).
     * Nút có label (Xem / Cập nhật / Xóa…) dùng .action-btn — KHÔNG bị ép 2.35rem.
     *
     * BẪY đã gặp thật (Sprint 44, 07-08-2026): `:only-child` chỉ đếm phần tử
     * con, KHÔNG đếm text node. Nút <a><i class="bi bi-x"></i> Chữ</a> — chữ
     * để trần không bọc <span> — vẫn bị `:has(> i.bi:only-child)` coi là
     * icon-only vì <i> là phần tử con duy nhất, dù rõ ràng có label. Hậu quả:
     * nút bị ép co về ô 2.35rem, nền/màu-brand bị đè, chữ biến mất hoàn
     * toàn — không phải lỗi font. MỌI nút có label trong bảng PHẢI gắn class
     * `action-btn` tường minh (đã có sẵn escape hatch `:not(.action-btn)`
     * bên dưới) — đừng trông cậy vào việc quên bọc <span> sẽ tự bị bắt lỗi.
     */
    #admin-content table td a.action-icon,
    #admin-content table td button.action-icon,
    #admin-content a.action-icon,
    #admin-content button.action-icon,
    #admin-content table td a[title]:has(> i.bi):not(:has(span)):not(.action-btn):not(.btn),
    #admin-content table td a[title]:has(> .bi):not(:has(span)):not(.action-btn):not(.btn),
    #admin-content table td button[title]:has(> i.bi):not(:has(span)):not(.action-btn):not(.btn),
    #admin-content table td button[title]:has(> .bi):not(:has(span)):not(.action-btn):not(.btn),
    #admin-content table td a:has(> i.bi:only-child):not(.action-btn):not(.btn),
    #admin-content table td button:has(> i.bi:only-child):not(.action-btn):not(.btn),
    #admin-content table td form.inline button[type="submit"]:has(> i.bi):not(:has(span)):not(.action-btn):not(.btn),
    #admin-content table td form.inline button[type="submit"]:has(> .bi):not(:has(span)):not(.action-btn):not(.btn),
    #admin-content .copy-row,
    #admin-content .clear-row {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 2.35rem !important;
        height: 2.35rem !important;
        min-width: 2.35rem !important;
        min-height: 2.35rem !important;
        padding: 0 !important;
        margin: 0 !important;
        border-radius: 0.65rem !important;
        border: 1px solid var(--gray-border) !important;
        background: linear-gradient(165deg, rgba(250, 248, 244, 0.98) 0%, #ffffff 70%) !important;
        box-shadow: 0 1px 2px rgba(23, 71, 102, 0.06), inset 0 1px 0 rgba(255, 255, 255, 0.85) !important;
        line-height: 1 !important;
        font-size: 1.05rem !important;
        font-weight: 500 !important;
        text-decoration: none !important;
        vertical-align: middle;
        cursor: pointer;
        transition:
            color 0.18s ease,
            background 0.18s ease,
            border-color 0.18s ease,
            box-shadow 0.22s ease,
            filter 0.18s ease !important;
        transform: none !important;
    }

    /* Nút thao tác có chữ trong bảng (hub /accounts, roles…) */
    #admin-content table td a.action-btn,
    #admin-content table td button.action-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        gap: 0.4rem !important;
        width: auto !important;
        min-width: 0 !important;
        height: auto !important;
        min-height: 2.15rem !important;
        padding: 0.4rem 0.75rem !important;
        border-radius: 0.55rem !important;
        font-size: 0.8125rem !important;
        font-weight: 600 !important;
        line-height: 1.2 !important;
        text-decoration: none !important;
        white-space: nowrap !important;
        vertical-align: middle;
        cursor: pointer;
        transition: background-color 0.18s ease, border-color 0.18s ease, box-shadow 0.22s ease, color 0.18s ease !important;
        transform: none !important;
    }

    #admin-content table td a.action-btn > i.bi,
    #admin-content table td button.action-btn > i.bi {
        font-size: 0.95rem !important;
        line-height: 1 !important;
        width: auto !important;
        height: auto !important;
        display: inline-flex !important;
        pointer-events: none;
    }

    #admin-content table td a.action-btn--ghost {
        color: #334155 !important;
        background: #fff !important;
        border: 1px solid #cbd5e1 !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04) !important;
    }
    #admin-content table td a.action-btn--ghost:hover {
        background: #f8fafc !important;
        border-color: #94a3b8 !important;
        box-shadow: 0 0 0 1px rgba(78, 161, 255, 0.2), 0 0 12px rgba(78, 161, 255, 0.18) !important;
        color: #0f172a !important;
    }

    #admin-content table td a.action-btn--primary,
    #admin-content table td button.action-btn--primary {
        color: #fff !important;
        background: linear-gradient(180deg, #5babff 0%, #4ea1ff 45%, #358fee 100%) !important;
        border: 1px solid #3580d6 !important;
        box-shadow: 0 2px 6px rgba(78, 161, 255, 0.28) !important;
    }
    #admin-content table td a.action-btn--primary:hover,
    #admin-content table td button.action-btn--primary:hover {
        background: linear-gradient(180deg, #6eb5ff 0%, #4ea1ff 40%, #2f86e8 100%) !important;
        box-shadow: 0 0 0 1px rgba(78, 161, 255, 0.35), 0 0 16px rgba(78, 161, 255, 0.35) !important;
        color: #fff !important;
    }

    #admin-content table td a.action-btn--danger,
    #admin-content table td button.action-btn--danger {
        color: #fff !important;
        background: linear-gradient(180deg, #f87171 0%, #ef4444 50%, #dc2626 100%) !important;
        border: 1px solid #b91c1c !important;
        box-shadow: 0 2px 6px rgba(239, 68, 68, 0.25) !important;
    }
    #admin-content table td a.action-btn--danger:hover,
    #admin-content table td button.action-btn--danger:hover {
        background: linear-gradient(180deg, #fca5a5 0%, #ef4444 45%, #b91c1c 100%) !important;
        box-shadow: 0 0 0 1px rgba(239, 68, 68, 0.35), 0 0 14px rgba(239, 68, 68, 0.3) !important;
        color: #fff !important;
    }

    /*
     * Icon-font (bi bi-*) hiển thị qua ::before — KHÔNG được ép display:flex
     * kèm width/height cố định trên chính thẻ <i>. Tổ hợp đó khiến một số
     * bản Chrome/Blink tính sai nội dung ::before bên trong flex container
     * thành rỗng (content: "" dù rule .bi-xxx::before{content:"\fXXX"} vẫn
     * đúng) — icon-only button (Xem/Xóa ở /roles, /trash…) thành ô trống.
     * Nút cha (.action-icon) đã tự flex + center rồi, icon con chỉ cần
     * width/height auto như .action-btn > i.bi đang làm đúng.
     */
    #admin-content table td a.action-icon > i.bi,
    #admin-content table td button.action-icon > i.bi,
    #admin-content a.action-icon > i.bi,
    #admin-content button.action-icon > i.bi,
    #admin-content table td a[title]:has(> i.bi):not(:has(span)):not(.action-btn) > i.bi,
    #admin-content table td button[title]:has(> i.bi):not(:has(span)):not(.action-btn) > i.bi,
    #admin-content table td a:has(> i.bi:only-child):not(.action-btn) > i.bi,
    #admin-content table td button:has(> i.bi:only-child):not(.action-btn) > i.bi,
    #admin-content table td form.inline button[type="submit"]:has(> i.bi):not(:has(span)):not(.action-btn) > i.bi,
    #admin-content .copy-row > i.bi,
    #admin-content .clear-row > i.bi {
        font-size: 1.1rem !important;
        line-height: 1 !important;
        width: auto !important;
        height: auto !important;
        display: inline-block !important;
        pointer-events: none;
    }

    /* SVG thật (không phải icon-font) vẫn ép kích thước + flex bình thường —
       không bị lỗi ::before rỗng vì SVG không dùng content sinh từ font. */
    #admin-content .copy-row > svg,
    #admin-content .clear-row > svg {
        font-size: 1.1rem !important;
        line-height: 1 !important;
        width: 1.15rem !important;
        height: 1.15rem !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        pointer-events: none;
    }

    /* Màu mặc định theo class text-* */
    #admin-content table td a.text-blue-600,
    #admin-content table td button.text-blue-600,
    #admin-content table td a.text-blue-600[title],
    #admin-content table td button.text-blue-600[title] {
        color: var(--brand-dark) !important;
        border-color: rgba(78, 161, 255, 0.35) !important;
        background: linear-gradient(165deg, #eef6ff 0%, #ffffff 75%) !important;
    }

    #admin-content table td a.text-green-600,
    #admin-content table td button.text-green-600 {
        color: #15803d !important;
        border-color: rgba(34, 197, 94, 0.35) !important;
        background: linear-gradient(165deg, #f0fdf4 0%, #ffffff 75%) !important;
    }

    #admin-content table td a.text-yellow-600,
    #admin-content table td button.text-yellow-600,
    #admin-content table td a.text-amber-600,
    #admin-content table td button.text-amber-600 {
        color: #d97706 !important;
        border-color: rgba(245, 158, 11, 0.4) !important;
        background: linear-gradient(165deg, #fffbeb 0%, #ffffff 75%) !important;
    }

    #admin-content table td a.text-red-600,
    #admin-content table td button.text-red-600 {
        color: #dc2626 !important;
        border-color: rgba(239, 68, 68, 0.35) !important;
        background: linear-gradient(165deg, #fef2f2 0%, #ffffff 75%) !important;
    }

    #admin-content table td a.text-orange-600,
    #admin-content table td button.text-orange-600,
    #admin-content table td button[class*="text-orange"] {
        color: #ea580c !important;
        border-color: rgba(249, 115, 22, 0.35) !important;
        background: linear-gradient(165deg, #fff7ed 0%, #ffffff 75%) !important;
    }

    #admin-content table td a.text-purple-600,
    #admin-content table td button.text-purple-600 {
        color: #7c3aed !important;
        border-color: rgba(139, 92, 246, 0.35) !important;
        background: linear-gradient(165deg, #f5f3ff 0%, #ffffff 75%) !important;
    }

    #admin-content table td a.text-gray-600,
    #admin-content table td button.text-gray-600,
    #admin-content .copy-row,
    #admin-content .clear-row {
        color: #4b5563 !important;
    }

    /* Hover — glow theo màu (icon-only; .action-btn có CSS riêng) */
    #admin-content table td a[title]:has(> i.bi):not(:has(span)):not(.action-btn):hover,
    #admin-content table td button[title]:has(> i.bi):not(:has(span)):not(.action-btn):hover,
    #admin-content table td a:has(> i.bi:only-child):not(.action-btn):hover,
    #admin-content table td button:has(> i.bi:only-child):not(.action-btn):hover,
    #admin-content table td form.inline button[type="submit"]:has(> i.bi):not(:has(span)):not(.action-btn):hover,
    #admin-content a.action-icon:hover,
    #admin-content button.action-icon:hover,
    #admin-content .copy-row:hover,
    #admin-content .clear-row:hover {
        transform: none !important;
        filter: none !important;
    }

    #admin-content table td a.text-blue-600:not(.action-btn):hover,
    #admin-content table td button.text-blue-600:not(.action-btn):hover,
    #admin-content table td a.text-blue-600[title]:not(.action-btn):hover,
    #admin-content a.action-icon.text-blue-600:hover {
        color: var(--brand) !important;
        border-color: rgba(78, 161, 255, 0.65) !important;
        background: linear-gradient(165deg, #e0f0ff 0%, #f8fbff 100%) !important;
        box-shadow: 0 0 0 1px rgba(78, 161, 255, 0.35), 0 0 16px rgba(78, 161, 255, 0.4), 0 6px 14px -8px rgba(78, 161, 255, 0.45) !important;
        text-shadow: none !important;
    }

    #admin-content table td a.text-green-600:not(.action-btn):hover,
    #admin-content table td button.text-green-600:not(.action-btn):hover {
        color: #16a34a !important;
        border-color: rgba(34, 197, 94, 0.6) !important;
        background: linear-gradient(165deg, #dcfce7 0%, #f7fef9 100%) !important;
        box-shadow: 0 0 0 1px rgba(34, 197, 94, 0.3), 0 0 16px rgba(34, 197, 94, 0.35), 0 6px 14px -8px rgba(34, 197, 94, 0.4) !important;
        text-shadow: none !important;
    }

    #admin-content table td a.text-yellow-600:hover,
    #admin-content table td button.text-yellow-600:hover,
    #admin-content table td a.text-amber-600:hover,
    #admin-content table td button.text-amber-600:hover {
        color: #f59e0b !important;
        border-color: rgba(245, 158, 11, 0.65) !important;
        background: linear-gradient(165deg, #fef3c7 0%, #fffbeb 100%) !important;
        box-shadow: 0 0 0 1px rgba(245, 158, 11, 0.35), 0 0 16px rgba(245, 158, 11, 0.35), 0 6px 14px -8px rgba(245, 158, 11, 0.4) !important;
        text-shadow: none !important;
    }

    #admin-content table td a.text-red-600:not(.action-btn):hover,
    #admin-content table td button.text-red-600:not(.action-btn):hover {
        color: #ef4444 !important;
        border-color: rgba(239, 68, 68, 0.6) !important;
        background: linear-gradient(165deg, #fee2e2 0%, #fff8f8 100%) !important;
        box-shadow: 0 0 0 1px rgba(239, 68, 68, 0.3), 0 0 16px rgba(239, 68, 68, 0.32), 0 6px 14px -8px rgba(239, 68, 68, 0.4) !important;
        text-shadow: none !important;
    }

    #admin-content table td a.text-orange-600:hover,
    #admin-content table td button.text-orange-600:hover,
    #admin-content table td button[class*="text-orange"]:hover {
        color: #f97316 !important;
        border-color: rgba(249, 115, 22, 0.6) !important;
        background: linear-gradient(165deg, #ffedd5 0%, #fffaf5 100%) !important;
        box-shadow: 0 0 0 1px rgba(249, 115, 22, 0.3), 0 0 16px rgba(249, 115, 22, 0.32) !important;
        text-shadow: none !important;
    }

    #admin-content table td a.text-purple-600:hover,
    #admin-content table td button.text-purple-600:hover {
        color: #8b5cf6 !important;
        border-color: rgba(139, 92, 246, 0.6) !important;
        background: linear-gradient(165deg, #ede9fe 0%, #faf8ff 100%) !important;
        box-shadow: 0 0 0 1px rgba(139, 92, 246, 0.3), 0 0 16px rgba(139, 92, 246, 0.32) !important;
        text-shadow: none !important;
    }

    #admin-content .copy-row:hover {
        color: #16a34a !important;
        border-color: rgba(34, 197, 94, 0.55) !important;
        background: linear-gradient(165deg, #dcfce7 0%, #f7fef9 100%) !important;
        box-shadow: 0 0 0 1px rgba(34, 197, 94, 0.28), 0 0 14px rgba(34, 197, 94, 0.3) !important;
    }

    #admin-content .clear-row:hover {
        color: #ef4444 !important;
        border-color: rgba(239, 68, 68, 0.55) !important;
        background: linear-gradient(165deg, #fee2e2 0%, #fff8f8 100%) !important;
        box-shadow: 0 0 0 1px rgba(239, 68, 68, 0.28), 0 0 14px rgba(239, 68, 68, 0.3) !important;
    }

    /* Active / focus */
    #admin-content table td a[title]:has(> i.bi):focus-visible,
    #admin-content table td button[title]:has(> i.bi):focus-visible,
    #admin-content .copy-row:focus-visible,
    #admin-content .clear-row:focus-visible,
    #admin-content a.action-icon:focus-visible,
    #admin-content button.action-icon:focus-visible {
        outline: none !important;
        box-shadow: 0 0 0 3px rgba(78, 161, 255, 0.35), 0 0 14px rgba(78, 161, 255, 0.25) !important;
    }
</style>