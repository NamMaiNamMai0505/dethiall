<style>
    .system-settings {
        --ss-accent: #2563eb;
        --ss-accent-dark: #1d4ed8;
        --ss-accent-soft: #eff6ff;
        --ss-accent-border: #bfdbfe;
        --ss-title: #0f172a;
        --ss-muted: #64748b;
        --ss-border: #e2e8f0;
        --ss-surface: #ffffff;
        --ss-panel: #f8fafc;
        width: 100%;
        max-width: 100%;
        min-width: 0;
        color: var(--ss-title);
        box-sizing: border-box;
    }

    .system-settings--lms {
        --ss-accent: #0d9488;
        --ss-accent-dark: #0f766e;
        --ss-accent-soft: #f0fdfa;
        --ss-accent-border: #99f6e4;
    }

    .system-settings--grades {
        --ss-accent: #ea580c;
        --ss-accent-dark: #c2410c;
        --ss-accent-soft: #fff7ed;
        --ss-accent-border: #fed7aa;
    }

    .ss-context {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.15rem;
        margin-bottom: 1.25rem;
        border: 1px solid var(--ss-accent-border);
        border-radius: .9rem;
        background: linear-gradient(135deg, var(--ss-accent-soft), #fff 72%);
    }

    .system-settings--grades .ss-context {
        background: linear-gradient(135deg, #fff7ed 0%, #fff 58%, #f0fdfa 100%);
        border-color: #fed7aa;
    }

    .ss-context-main,
    .ss-title-row,
    .ss-section-title {
        display: flex;
        align-items: center;
        gap: .75rem;
        min-width: 0;
    }

    .ss-context-icon {
        display: inline-flex;
        width: 2.75rem;
        height: 2.75rem;
        flex: 0 0 2.75rem;
        align-items: center;
        justify-content: center;
        border-radius: .8rem;
        background: var(--ss-accent);
        color: #fff;
        font-size: 1.2rem;
        box-shadow: 0 8px 22px -13px var(--ss-accent);
    }

    .system-settings--grades .ss-context-icon,
    .system-settings--grades .ss-btn-primary {
        background: linear-gradient(135deg, #ea580c, #c2410c 62%, #0d9488);
    }

    .ss-context h2,
    .ss-card h2 {
        margin: 0;
        color: var(--ss-title);
        font-size: 1rem;
        font-weight: 750;
        line-height: 1.35;
    }

    .ss-context p,
    .ss-card-head p,
    .ss-help {
        margin: .2rem 0 0;
        color: var(--ss-muted);
        font-size: .8125rem;
        line-height: 1.5;
    }

    .ss-db-chip,
    .ss-status,
    .ss-current-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .3rem;
        border-radius: 999px;
        white-space: nowrap;
        font-size: .72rem;
        font-weight: 700;
        line-height: 1;
    }

    .ss-db-chip {
        padding: .48rem .7rem;
        color: var(--ss-accent-dark);
        background: #fff;
        border: 1px solid var(--ss-accent-border);
    }

    .ss-readonly {
        display: flex;
        align-items: flex-start;
        gap: .6rem;
        margin-bottom: 1rem;
        padding: .8rem 1rem;
        border: 1px solid #fde68a;
        border-radius: .75rem;
        background: #fffbeb;
        color: #92400e;
        font-size: .825rem;
        line-height: 1.45;
    }

    .ss-layout {
        display: block;
        margin-top: 1.4rem;
        width: 100%;
        max-width: 100%;
        min-width: 0;
        isolation: isolate;
    }

    .ss-layout[data-settings-hub-panel] {
        display: none;
        animation: ss-panel-in .22s ease both;
    }

    .ss-layout[data-settings-hub-panel].is-active {
        display: block;
    }

    .ss-card {
        position: relative;
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
        border: 1px solid var(--ss-border);
        border-radius: 1rem;
        background: var(--ss-surface);
        box-shadow: 0 10px 28px -22px rgba(15, 23, 42, .36);
    }

    .ss-card--academic {
        z-index: 1;
        container-name: ss-academic;
        container-type: inline-size;
    }

    .ss-card-head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 1rem 1.15rem;
        border-bottom: 1px solid var(--ss-border);
        background: #fff;
    }

    .ss-section-icon {
        display: inline-flex;
        width: 2.15rem;
        height: 2.15rem;
        flex: 0 0 2.15rem;
        align-items: center;
        justify-content: center;
        border-radius: .65rem;
        background: var(--ss-accent-soft);
        color: var(--ss-accent-dark);
        border: 1px solid var(--ss-accent-border);
    }

    .ss-add-form {
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        align-items: end;
        gap: .75rem;
        width: 100%;
        max-width: 100%;
        padding: 1rem 1.15rem;
        border-bottom: 1px solid var(--ss-border);
        background: var(--ss-panel);
        box-sizing: border-box;
    }

    .ss-add-form > .ss-field {
        grid-column: span 2;
    }

    .ss-add-form > .ss-checkbox,
    .ss-add-form > .ss-btn-primary {
        grid-column: span 3;
    }

    .ss-add-form > .ss-btn-primary {
        width: 100%;
    }

    .ss-field {
        min-width: 0;
    }

    .ss-field label {
        display: block;
        margin-bottom: .38rem;
        color: #475569;
        font-size: .75rem;
        font-weight: 700;
    }

    .ss-field input,
    .ss-field select {
        display: block;
        width: 100%;
        min-height: 2.5rem;
        padding: .55rem .7rem;
        border: 1px solid #cbd5e1;
        border-radius: .65rem;
        outline: none;
        background: #fff;
        color: #0f172a;
        font: inherit;
        font-size: .875rem;
        line-height: 1.2;
        box-sizing: border-box;
    }

    .ss-field input:focus,
    .ss-field select:focus {
        border-color: var(--ss-accent);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--ss-accent) 17%, transparent);
    }

    .ss-field input:disabled,
    .ss-field select:disabled {
        cursor: not-allowed;
        background: #f1f5f9;
        color: #64748b;
    }

    .ss-checkbox {
        display: inline-flex;
        min-height: 2.5rem;
        align-items: center;
        gap: .5rem;
        color: #334155;
        font-size: .8rem;
        font-weight: 650;
        white-space: nowrap;
    }

    .ss-checkbox input {
        width: 1rem;
        height: 1rem;
        accent-color: var(--ss-accent);
    }

    .ss-btn {
        display: inline-flex;
        min-height: 2.35rem;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        padding: .5rem .78rem;
        border: 1px solid transparent;
        border-radius: .65rem;
        cursor: pointer;
        font-size: .8rem;
        font-weight: 700;
        line-height: 1.2;
        text-decoration: none;
        white-space: nowrap;
        transition: border-color .18s ease, background .18s ease, box-shadow .2s ease, color .18s ease;
    }

    .ss-btn-primary {
        color: #fff;
        background: var(--ss-accent);
        border-color: var(--ss-accent-dark);
    }

    .ss-btn-primary:hover {
        color: #fff;
        background: var(--ss-accent-dark);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--ss-accent) 15%, transparent);
    }

    .ss-btn-soft {
        color: var(--ss-accent-dark);
        background: var(--ss-accent-soft);
        border-color: var(--ss-accent-border);
    }

    .ss-btn-soft:hover {
        color: var(--ss-accent-dark);
        background: #fff;
        border-color: var(--ss-accent);
    }

    .ss-btn-neutral {
        color: #475569;
        background: #fff;
        border-color: #cbd5e1;
    }

    .ss-btn-neutral:hover {
        color: #0f172a;
        background: #f8fafc;
        border-color: #94a3b8;
    }

    .ss-table-wrap {
        width: 100%;
        overflow-x: auto;
        scrollbar-width: thin;
    }

    .ss-table {
        width: 100%;
        min-width: 38rem;
        border-collapse: collapse;
        color: #334155;
        font-size: .825rem;
    }

    .ss-table thead {
        color: #64748b;
        background: #f8fafc;
    }

    .ss-table th {
        padding: .72rem 1.15rem;
        text-align: left;
        font-size: .7rem;
        font-weight: 750;
        letter-spacing: .04em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    .ss-table td {
        padding: .78rem 1.15rem;
        border-top: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .ss-table tbody tr:hover {
        background: color-mix(in srgb, var(--ss-accent-soft) 50%, #fff);
    }

    .ss-year-code {
        color: #0f172a;
        font-size: .875rem;
        font-weight: 750;
        white-space: nowrap;
    }

    .ss-current-chip {
        margin-left: .4rem;
        padding: .28rem .48rem;
        color: var(--ss-accent-dark);
        background: var(--ss-accent-soft);
        border: 1px solid var(--ss-accent-border);
    }

    .ss-status {
        padding: .36rem .56rem;
    }

    .ss-status--active {
        color: #047857;
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
    }

    .ss-status--inactive {
        color: #64748b;
        background: #f1f5f9;
        border: 1px solid #e2e8f0;
    }

    .ss-actions {
        display: flex;
        flex-wrap: wrap;
        justify-content: flex-end;
        gap: .45rem;
    }

    .ss-side-body {
        padding: 1.15rem;
    }

    .ss-form-stack {
        display: grid;
        gap: 1rem;
    }

    .ss-two-cols {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .75rem;
    }

    .ss-three-cols {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: .75rem;
    }

    .ss-portal-form {
        display: block;
        margin-top: 1.4rem;
    }

    .ss-portal-form.is-hub-hidden {
        display: none;
    }

    .ss-portal-heading {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
        margin: 1.4rem 0 .9rem;
    }

    .ss-portal-heading h2 {
        margin: .25rem 0 0;
        color: #0f172a;
        font-size: 1.15rem;
        font-weight: 800;
    }

    .ss-portal-heading p {
        margin: .25rem 0 0;
        color: #64748b;
        font-size: .825rem;
    }

    .ss-eyebrow {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        color: var(--ss-accent-dark);
        font-size: .7rem;
        font-weight: 800;
        letter-spacing: .06em;
        text-transform: uppercase;
    }

    .ss-setting-count {
        display: inline-flex;
        flex: 0 0 auto;
        padding: .4rem .65rem;
        border: 1px solid var(--ss-accent-border);
        border-radius: 999px;
        color: var(--ss-accent-dark);
        background: var(--ss-accent-soft);
        font-size: .72rem;
        font-weight: 750;
    }

    .ss-hub-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(min(100%, 13.5rem), 1fr));
        align-items: stretch;
        gap: .85rem;
    }

    .system-settings--lms .ss-hub-grid {
        grid-template-columns: repeat(6, minmax(0, 1fr));
    }

    .system-settings--lms .ss-hub-tile {
        grid-column: span 2;
    }

    .system-settings--lms .ss-hub-tile:nth-last-child(-n + 2) {
        grid-column: span 3;
    }

    .ss-hub-tile {
        position: relative;
        display: grid;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: start;
        gap: .75rem;
        min-width: 0;
        min-height: 9.25rem;
        padding: .95rem;
        overflow: hidden;
        border: 1px solid var(--ss-border);
        border-radius: 1rem;
        outline: none;
        color: var(--ss-title);
        background:
            radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--ss-accent-soft) 80%, transparent), transparent 42%),
            #fff;
        text-align: left;
        cursor: pointer;
        box-shadow: 0 10px 26px -24px rgba(15, 23, 42, .65);
        transition: transform .18s ease, border-color .18s ease, box-shadow .2s ease, background .2s ease;
    }

    .ss-hub-tile::after {
        position: absolute;
        right: 0;
        bottom: 0;
        left: 0;
        height: 3px;
        background: var(--ss-accent);
        content: "";
        opacity: 0;
        transform: scaleX(.45);
        transition: opacity .18s ease, transform .2s ease;
    }

    .ss-hub-tile:hover {
        z-index: 1;
        border-color: var(--ss-accent-border);
        transform: translateY(-2px);
        box-shadow:
            0 14px 30px -22px color-mix(in srgb, var(--ss-accent) 55%, rgba(15, 23, 42, .25)),
            0 0 0 3px color-mix(in srgb, var(--ss-accent) 7%, transparent);
    }

    .ss-hub-tile:focus-visible {
        border-color: var(--ss-accent);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--ss-accent) 18%, transparent);
    }

    .ss-hub-tile.is-active {
        border-color: var(--ss-accent);
        background:
            radial-gradient(circle at 100% 0%, color-mix(in srgb, var(--ss-accent) 15%, transparent), transparent 48%),
            linear-gradient(145deg, #fff, var(--ss-accent-soft));
        box-shadow:
            0 16px 34px -24px color-mix(in srgb, var(--ss-accent) 70%, rgba(15, 23, 42, .28)),
            inset 0 0 0 1px color-mix(in srgb, var(--ss-accent) 9%, transparent);
    }

    .ss-hub-tile.is-active::after {
        opacity: 1;
        transform: scaleX(1);
    }

    .ss-hub-tile-icon {
        display: inline-flex;
        width: 2.55rem;
        height: 2.55rem;
        flex: 0 0 2.55rem;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--ss-accent-border);
        border-radius: .78rem;
        color: var(--ss-accent-dark);
        background: var(--ss-accent-soft);
        font-size: 1.05rem;
        transition: color .18s ease, background .18s ease, transform .18s ease;
    }

    .ss-hub-tile.is-active .ss-hub-tile-icon {
        border-color: var(--ss-accent);
        color: #fff;
        background: var(--ss-accent);
        transform: scale(1.04);
    }

    .system-settings--grades .ss-hub-tile.is-active .ss-hub-tile-icon {
        background: linear-gradient(135deg, #ea580c, #c2410c 66%, #0d9488);
    }

    .ss-hub-tile-content {
        display: flex;
        min-width: 0;
        height: 100%;
        flex-direction: column;
    }

    .ss-hub-tile-content strong {
        display: block;
        padding-top: .12rem;
        color: #0f172a;
        font-size: .86rem;
        font-weight: 800;
        line-height: 1.3;
    }

    .ss-hub-tile-content small {
        display: -webkit-box;
        margin-top: .36rem;
        overflow: hidden;
        color: #64748b;
        font-size: .73rem;
        line-height: 1.45;
        -webkit-box-orient: vertical;
        -webkit-line-clamp: 3;
    }

    .ss-hub-tile-content > span {
        display: inline-flex;
        align-self: flex-start;
        margin-top: auto;
        padding-top: .65rem;
        color: var(--ss-accent-dark);
        font-size: .68rem;
        font-weight: 750;
    }

    .ss-hub-arrow {
        align-self: center;
        color: #94a3b8;
        font-size: 1.2rem;
        transition: color .18s ease, transform .18s ease;
    }

    .ss-hub-tile:hover .ss-hub-arrow,
    .ss-hub-tile.is-active .ss-hub-arrow {
        color: var(--ss-accent);
        transform: translateX(2px);
    }

    .ss-settings-panels {
        width: 100%;
        max-width: 58rem;
    }

    .ss-settings-panels > [data-settings-hub-panel] {
        display: none;
        animation: ss-panel-in .22s ease both;
    }

    .ss-settings-panels > [data-settings-hub-panel].is-active {
        display: flex;
    }

    @keyframes ss-panel-in {
        from {
            opacity: 0;
            transform: translateY(5px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .ss-setting-card {
        display: flex;
        min-height: 100%;
        flex-direction: column;
    }

    .ss-setting-card .ss-side-body {
        flex: 1 1 auto;
    }

    .ss-switch {
        display: grid;
        grid-template-columns: auto minmax(0, 1fr);
        align-items: center;
        gap: .7rem;
        padding: .72rem .8rem;
        border: 1px solid #e2e8f0;
        border-radius: .75rem;
        background: #f8fafc;
        color: #334155;
        cursor: pointer;
    }

    .ss-switch > input {
        position: absolute;
        width: 1px;
        height: 1px;
        overflow: hidden;
        opacity: 0;
        pointer-events: none;
    }

    .ss-switch-track {
        position: relative;
        display: inline-flex;
        width: 2.3rem;
        height: 1.3rem;
        align-items: center;
        padding: .12rem;
        border-radius: 999px;
        background: #cbd5e1;
        transition: background .18s ease, box-shadow .18s ease;
    }

    .ss-switch-track > span {
        display: block;
        width: 1.05rem;
        height: 1.05rem;
        border-radius: 50%;
        background: #fff;
        box-shadow: 0 1px 3px rgba(15, 23, 42, .28);
        transition: transform .18s ease;
    }

    .ss-switch > input:checked + .ss-switch-track {
        background: var(--ss-accent);
        box-shadow: 0 0 0 3px color-mix(in srgb, var(--ss-accent) 13%, transparent);
    }

    .ss-switch > input:checked + .ss-switch-track > span {
        transform: translateX(1rem);
    }

    .ss-switch > input:focus-visible + .ss-switch-track {
        outline: 2px solid var(--ss-accent);
        outline-offset: 2px;
    }

    .ss-switch > input:disabled + .ss-switch-track {
        opacity: .55;
    }

    .ss-switch strong,
    .ss-switch small {
        display: block;
    }

    .ss-switch strong {
        color: #334155;
        font-size: .8rem;
    }

    .ss-switch small {
        margin-top: .14rem;
        color: #64748b;
        font-size: .7rem;
        line-height: 1.4;
    }

    .ss-weight-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .75rem;
    }

    .ss-weight-total {
        display: flex;
        align-items: center;
        gap: .45rem;
        margin-top: .9rem;
        padding: .65rem .75rem;
        border: 1px solid #a7f3d0;
        border-radius: .7rem;
        color: #047857;
        background: #ecfdf5;
        font-size: .78rem;
    }

    .ss-weight-total.is-invalid {
        border-color: #fcd34d;
        color: #b45309;
        background: #fffbeb;
    }

    .ss-save-bar {
        position: sticky;
        bottom: .75rem;
        z-index: 8;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-top: 1rem;
        padding: .8rem;
        border: 1px solid var(--ss-accent-border);
        border-radius: .9rem;
        background: color-mix(in srgb, #fff 94%, var(--ss-accent-soft));
        box-shadow: 0 12px 30px -18px rgba(15, 23, 42, .4);
        backdrop-filter: blur(12px);
    }

    .ss-save-bar .ss-connection {
        max-width: 42rem;
    }

    .ss-save {
        width: 100%;
        margin-top: .2rem;
    }

    .ss-connections {
        display: grid;
        gap: .65rem;
        margin: 1.1rem 0 0;
        padding: 1rem 0 0;
        border-top: 1px solid var(--ss-border);
    }

    .ss-connection {
        display: flex;
        align-items: flex-start;
        gap: .55rem;
        color: #64748b;
        font-size: .78rem;
        line-height: 1.45;
    }

    .ss-connection i {
        margin-top: .08rem;
        color: var(--ss-accent);
    }

    .ss-empty {
        padding: 2.5rem 1rem;
        color: #64748b;
        text-align: center;
        font-size: .85rem;
    }

    @container ss-academic (min-width: 52rem) {
        .ss-add-form {
            grid-template-columns: minmax(7rem, .8fr) minmax(9rem, 1fr) minmax(9rem, 1fr) auto auto;
        }

        .ss-add-form > .ss-field,
        .ss-add-form > .ss-checkbox,
        .ss-add-form > .ss-btn-primary {
            grid-column: auto;
        }

        .ss-add-form > .ss-btn-primary {
            width: auto;
        }
    }

    @media (max-width: 1100px) {
        .ss-hub-grid,
        .system-settings--lms .ss-hub-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }

        .system-settings--lms .ss-hub-tile,
        .system-settings--lms .ss-hub-tile:nth-last-child(-n + 2) {
            grid-column: span 1;
        }
    }

    @media (max-width: 640px) {
        .ss-context,
        .ss-card-head {
            align-items: flex-start;
        }

        .ss-context {
            flex-direction: column;
        }

        .ss-db-chip {
            margin-left: 3.5rem;
        }

        .ss-card-head {
            flex-direction: column;
        }

        .ss-add-form,
        .ss-two-cols,
        .ss-three-cols,
        .ss-weight-grid,
        .ss-hub-grid,
        .system-settings--lms .ss-hub-grid {
            grid-template-columns: minmax(0, 1fr);
        }

        .system-settings--lms .ss-hub-tile,
        .system-settings--lms .ss-hub-tile:nth-last-child(-n + 2) {
            grid-column: 1 / -1;
        }

        .ss-add-form > .ss-field,
        .ss-add-form > .ss-checkbox,
        .ss-add-form > .ss-btn-primary {
            grid-column: 1 / -1;
        }

        .ss-add-form {
            padding: .9rem;
        }

        .ss-checkbox {
            min-height: 2rem;
        }

        .ss-portal-heading,
        .ss-save-bar {
            align-items: stretch;
            flex-direction: column;
        }

        .ss-setting-count {
            align-self: flex-start;
        }

        .ss-hub-tile {
            min-height: 7.75rem;
        }

        .ss-save-bar {
            position: static;
        }

        .ss-save-bar .ss-btn {
            width: 100%;
        }

        .ss-table th,
        .ss-table td {
            padding-left: .85rem;
            padding-right: .85rem;
        }
    }
</style>
