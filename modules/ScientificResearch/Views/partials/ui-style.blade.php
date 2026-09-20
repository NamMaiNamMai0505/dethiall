@include('partials.date-input-theme')

<style>
    .sr-page {
        --sr-ink: #0f172a;
        --sr-muted: #64748b;
        --sr-line: #dbe3ee;
        --sr-soft: #f8fafc;
        --sr-blue: #2563eb;
        --sr-green: #059669;
        --sr-amber: #d97706;
        --sr-rose: #e11d48;
        max-width: 100%;
        overflow-x: hidden;
    }

    .sr-page .sr-panel,
    .sr-page > section,
    .sr-page > div > section {
        border: 1px solid var(--sr-line);
        border-radius: 10px;
        background: rgba(255, 255, 255, .96);
        box-shadow: 0 10px 24px rgba(15, 23, 42, .06);
        overflow: hidden;
    }

    .sr-page .sr-panel,
    .sr-page > section {
        margin-bottom: 1rem;
    }

    .sr-page article,
    .sr-page label,
    .sr-page td,
    .sr-page th {
        min-width: 0;
        overflow-wrap: anywhere;
        word-break: normal;
    }

    .sr-page input,
    .sr-page select,
    .sr-page textarea {
        border-color: #cbd5e1;
        background: #fff;
        color: var(--sr-ink);
        transition: border-color .16s ease, box-shadow .16s ease, background .16s ease;
    }

    .sr-page input:focus,
    .sr-page select:focus,
    .sr-page textarea:focus {
        outline: none;
        border-color: var(--sr-blue);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, .14);
    }

    .sr-page table thead {
        background: #eef4ff;
        color: #334155;
    }

    .sr-page .overflow-x-auto {
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }

    .sr-page .overflow-x-auto table {
        border-collapse: separate;
        border-spacing: 0;
    }

    .sr-page table tbody tr {
        transition: background .14s ease;
    }

    .sr-page table tbody tr:hover {
        background: #f8fafc;
    }

    .sr-page details {
        border-radius: 8px;
    }

    .sr-page summary {
        display: inline-flex;
        min-height: 2.25rem;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        list-style: none;
        white-space: nowrap;
        border: 1px solid #bfdbfe;
        border-radius: 8px;
        background: #eff6ff;
        padding: .45rem .75rem;
        color: #1d4ed8;
        font-size: .875rem;
        font-weight: 800;
        line-height: 1.2;
        transition: transform .14s ease, box-shadow .14s ease, background .14s ease;
    }

    .sr-page summary:hover {
        background: #dbeafe;
        box-shadow: 0 8px 18px rgba(15, 23, 42, .10);
        transform: translateY(-1px);
    }

    .sr-page summary::-webkit-details-marker {
        display: none;
    }

    .sr-page summary::after {
        content: "⌄";
        display: inline-block;
        color: var(--sr-muted);
        font-weight: 800;
    }

    .sr-page details[open] summary::after {
        transform: rotate(180deg);
    }

    .sr-stat {
        position: relative;
        overflow: hidden;
        border-radius: 10px;
        border: 1px solid var(--sr-line);
        background: linear-gradient(135deg, #fff 0%, #f8fafc 100%);
        box-shadow: 0 10px 24px rgba(15, 23, 42, .05);
    }

    .sr-stat::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 4px;
        background: var(--accent, var(--sr-blue));
    }

    .sr-badge {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        border-radius: 999px;
        padding: .25rem .65rem;
        font-size: .75rem;
        font-weight: 800;
        line-height: 1;
    }

    .sr-toolbar {
        border: 1px solid var(--sr-line);
        border-radius: 10px;
        background: #f8fafc;
    }

    .sr-section-head {
        display: grid;
        grid-template-columns: minmax(0, 1fr);
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .sr-section-head h2 {
        margin: 0;
        color: var(--sr-ink);
        font-size: 1.125rem;
        font-weight: 800;
    }

    .sr-section-head p {
        margin-top: .35rem;
        max-width: 52rem;
        color: var(--sr-muted);
        font-size: .875rem;
        line-height: 1.5;
    }

    .sr-section-kpis {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        align-items: center;
    }

    .sr-section-kpis span,
    .sr-workflow span {
        display: inline-flex;
        align-items: center;
        border: 1px solid #dbeafe;
        border-radius: 8px;
        background: #eff6ff;
        padding: .55rem .7rem;
        color: #1e3a8a;
        font-size: .8125rem;
        font-weight: 800;
        line-height: 1.2;
    }

    .sr-section-kpis b {
        margin-right: .25rem;
        color: #0f172a;
        font-size: 1rem;
    }

    .sr-workflow {
        display: flex;
        flex-wrap: wrap;
        gap: .5rem;
        border: 1px solid var(--sr-line);
        border-radius: 10px;
        background: #f8fafc;
        padding: .75rem;
    }

    .sr-workflow span {
        border-color: #e2e8f0;
        background: #fff;
        color: #334155;
    }

    @media (min-width: 1024px) {
        .sr-section-head {
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: start;
        }

        .sr-section-kpis {
            justify-content: flex-end;
        }
    }

    .sr-action {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        border-radius: 8px;
        font-weight: 800;
        transition: transform .14s ease, box-shadow .14s ease, background .14s ease;
    }

    .sr-action-sm {
        min-height: 2.25rem;
        padding: .45rem .75rem;
        font-size: .875rem;
        line-height: 1.2;
        white-space: nowrap;
    }

    .sr-action:hover {
        transform: translateY(-1px);
        box-shadow: 0 8px 18px rgba(15, 23, 42, .12);
    }

    .sr-table-actions {
        display: flex;
        flex-wrap: nowrap;
        align-items: center;
        justify-content: flex-end;
        gap: .5rem;
        min-width: max-content;
    }

    .sr-actions-cell {
        width: 11.5rem;
        min-width: 11.5rem;
        text-align: center;
        vertical-align: middle;
        white-space: nowrap;
    }

    td.sr-actions-cell .sr-table-actions {
        justify-content: center;
    }

    .sr-table-actions form {
        display: inline-flex;
        margin: 0;
    }

    .sr-page form button.text-rose-700 {
        display: inline-flex;
        min-height: 2.25rem;
        align-items: center;
        justify-content: center;
        gap: .4rem;
        white-space: nowrap;
        border: 1px solid #fecdd3;
        border-radius: 8px;
        background: #fff1f2;
        padding: .45rem .75rem;
        font-size: .875rem;
        font-weight: 800;
        line-height: 1.2;
        color: #be123c;
        transition: transform .14s ease, box-shadow .14s ease, background .14s ease;
    }

    .sr-page form button.text-rose-700:hover {
        background: #ffe4e6;
        box-shadow: 0 8px 18px rgba(15, 23, 42, .10);
        transform: translateY(-1px);
    }

    .sr-page pre {
        font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace;
    }
</style>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (typeof window.initDateInputs === 'function') {
            window.initDateInputs(document.querySelector('.sr-page') || document);
        }
    });

    if (typeof window.initDateInputs === 'function') {
        window.initDateInputs(document.querySelector('.sr-page') || document);
    }

    function initScientificResearchMembers(root) {
        root = root || document;
        const participantInput = root.querySelector('[data-sr-participant-count]');
        const membersBlock = root.querySelector('[data-sr-members-block]');
        const list = root.querySelector('[data-sr-members-list]');
        const empty = root.querySelector('[data-sr-members-empty]');
        const label = root.querySelector('[data-sr-member-count-label]');
        const template = document.getElementById('sr-member-row-template');

        if (!participantInput || !membersBlock || !list || !template) {
            return;
        }

        let initialMembers = [];
        const rawInitialMembers = membersBlock.getAttribute('data-sr-initial-members');
        if (rawInitialMembers) {
            try {
                initialMembers = JSON.parse(rawInitialMembers);
            } catch (error) {
                initialMembers = [];
            }
        }
        if (!initialMembers.length && Array.isArray(window.scientificResearchInitialMembers)) {
            initialMembers = window.scientificResearchInitialMembers;
        }

        const currentRows = function () {
            return Array.from(list.querySelectorAll('[data-sr-member-row]')).map(function (row) {
                return {
                    user_id: row.querySelector('[data-sr-member-user]')?.value || '',
                    role: row.querySelector('[data-sr-member-role]')?.value || 'Thành viên',
                    percent: row.querySelector('[data-sr-member-percent]')?.value || 0,
                };
            });
        };

        const renderRows = function () {
            const existing = currentRows();
            const data = existing.length ? existing : initialMembers;
            const participantCount = Math.max(1, parseInt(participantInput.value || '1', 10) || 1);
            const memberCount = Math.max(0, participantCount - 1);

            list.innerHTML = '';
            if (label) {
                label.textContent = memberCount + ' thành viên';
            }
            if (empty) {
                empty.classList.toggle('hidden', memberCount > 0);
            }

            for (let i = 0; i < memberCount; i++) {
                const node = template.content.firstElementChild.cloneNode(true);
                node.setAttribute('data-sr-member-row', '1');
                node.querySelector('[data-sr-member-index]').textContent = i + 1;
                const rowData = data[i] || {};
                const userSelect = node.querySelector('[data-sr-member-user]');
                const roleInput = node.querySelector('[data-sr-member-role]');
                const percentInput = node.querySelector('[data-sr-member-percent]');

                userSelect.value = rowData.user_id || '';
                roleInput.value = rowData.role || 'Thành viên';
                percentInput.value = rowData.percent ?? 0;
                list.appendChild(node);
            }
        };

        participantInput.addEventListener('input', renderRows);
        participantInput.addEventListener('change', renderRows);
        renderRows();
    }

    document.addEventListener('DOMContentLoaded', function () {
        initScientificResearchMembers(document.querySelector('.sr-page') || document);
    });

    initScientificResearchMembers(document.querySelector('.sr-page') || document);
</script>
@endpush
