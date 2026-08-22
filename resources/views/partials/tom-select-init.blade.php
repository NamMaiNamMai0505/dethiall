<style>
    .ui-select-field,
    .instructor-select-field {
        position: relative;
        width: 100%;
        /* Cho dropdown absolute bám control — không bị parent clip */
        overflow: visible !important;
    }

    #admin-content .ts-wrapper {
        font-family: inherit;
        width: 100%;
        position: relative;
        overflow: visible !important;
    }

    #admin-content .ts-wrapper.dropdown-active {
        z-index: 10050;
        position: relative;
    }

    #admin-content .ts-wrapper .ts-control {
        border-color: #d5dae3;
    }

    #admin-content .ts-wrapper .ts-control {
        display: flex !important;
        align-items: center !important;
        flex-wrap: wrap;
        gap: 0.25rem;
        background: #ffffff !important;
        border: 1px solid #d5dae3 !important;
        border-radius: 0.625rem !important;
        min-height: 44px;
        /* Căn giữa chữ theo chiều dọc — tránh padding trên/dưới lệch baseline */
        padding: 0 2.25rem 0 0.75rem !important;
        box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.03);
        transition: border-color 0.2s ease, box-shadow 0.28s ease, background-color 0.2s ease;
        font-size: 0.875rem;
        line-height: 1.25rem;
        color: #1f2937;
    }

    #admin-content .ts-wrapper.single .ts-control {
        background-image: none !important;
        flex-wrap: nowrap;
    }

    #admin-content .ts-wrapper.multi .ts-control {
        padding-top: 0.35rem !important;
        padding-bottom: 0.35rem !important;
        padding-right: 2.25rem !important;
    }

    #admin-content .ts-wrapper.focus .ts-control,
    #admin-content .ts-wrapper.dropdown-active .ts-control {
        border-color: #4ea1ff !important;
        box-shadow: 0 0 0 3px rgba(78, 161, 255, 0.22), inset 0 1px 2px rgba(15, 23, 42, 0.03) !important;
    }

    #admin-content .ts-wrapper .ts-control input {
        font-size: 0.875rem !important;
        line-height: 1.25rem !important;
        color: #1f2937 !important;
        margin: 0 !important;
        padding: 0 !important;
        min-height: 0 !important;
        height: auto !important;
        align-self: center;
    }

    #admin-content .ts-wrapper .ts-control input::placeholder {
        color: #9ca3af;
        line-height: 1.25rem;
    }

    #admin-content .ts-wrapper .ts-control > .item {
        display: inline-flex !important;
        align-items: center !important;
        background: transparent !important;
        color: #1f2937 !important;
        border: none !important;
        font-weight: 500;
        line-height: 1.25rem !important;
        margin: 0 !important;
        padding: 0 !important;
        max-width: 100%;
    }

    /* Giá trị đã chọn luôn nằm gọn trong control; panel mở mới được nới rộng. */
    #admin-content .ts-wrapper.single .ts-control > .item,
    .ui-select-field .ts-wrapper.single .ts-control > .item,
    .instructor-select-field .ts-wrapper.single .ts-control > .item {
        flex: 1 1 auto;
        min-width: 0;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    #admin-content .ts-wrapper.single .ts-control > .item > *,
    .ui-select-field .ts-wrapper.single .ts-control > .item > *,
    .instructor-select-field .ts-wrapper.single .ts-control > .item > * {
        min-width: 0;
        max-width: 100%;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    #admin-content .ts-wrapper.multi .ts-control > .item {
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.3rem;
        background: #eef6ff !important;
        border: 1px solid #bfdbfe !important;
        border-radius: 0.375rem !important;
        color: #3580d6 !important;
        /* chừa chỗ cho nút × bên phải chip */
        padding: 0.2rem 0.35rem 0.2rem 0.55rem !important;
        margin: 0.05rem 0.25rem 0.05rem 0 !important;
        line-height: 1.15rem !important;
        position: relative !important;
    }

    /* Nút × xóa từng giá trị (multi remove_button) — trong chip, không absolute */
    #admin-content .ts-wrapper.multi .ts-control > .item .remove,
    #admin-content .ts-wrapper.plugin-remove_button .item .remove {
        position: static !important;
        top: auto !important;
        right: auto !important;
        bottom: auto !important;
        left: auto !important;
        transform: none !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        flex-shrink: 0 !important;
        width: 1.1rem !important;
        height: 1.1rem !important;
        min-width: 1.1rem !important;
        margin: 0 0 0 0.1rem !important;
        padding: 0 !important;
        border: none !important;
        border-radius: 999px !important;
        background: transparent !important;
        color: #6b9fd4 !important;
        font-size: 0.95rem !important;
        line-height: 1 !important;
        box-sizing: border-box !important;
        order: 2;
        transition: color 0.15s ease, background-color 0.15s ease;
    }

    #admin-content .ts-wrapper.multi .ts-control > .item .remove:hover,
    #admin-content .ts-wrapper.plugin-remove_button .item .remove:hover {
        color: #1d4ed8 !important;
        background: rgba(59, 130, 246, 0.15) !important;
    }

    /* Nút clear all (single select) — góc phải control */
    #admin-content .ts-wrapper .clear-button {
        color: #9ca3af;
        transition: color 0.15s ease;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        position: absolute !important;
        top: 50% !important;
        right: 0.6rem !important;
        transform: translateY(-50%) !important;
        margin: 0 !important;
        z-index: 2;
    }

    #admin-content .ts-wrapper .clear-button:hover {
        color: #4ea1ff;
    }

    /*
     * Dropdown gắn trong .ts-wrapper (absolute) — cuộn #admin-content thì panel đi cùng ô.
     * Không dùng fixed+body (dễ trôi khỏi textbox).
     */
    #admin-content .ts-wrapper .ts-dropdown,
    .ui-select-field .ts-dropdown,
    .instructor-select-field .ts-dropdown {
        position: absolute !important;
        left: 0 !important;
        right: auto !important;
        top: 100% !important;
        bottom: auto !important;
        width: 100% !important;
        max-width: calc(100vw - 1.5rem) !important;
        margin: 0.25rem 0 0 0 !important;
        z-index: 10060 !important;
        border: 1px solid #d5dae3 !important;
        border-radius: 0.75rem !important;
        background: rgba(250, 248, 244, 0.98) !important;
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        box-shadow: 0 12px 32px -8px rgba(78, 161, 255, 0.25), 0 0 0 1px rgba(78, 161, 255, 0.08) !important;
        transform: none !important;
        max-height: min(16rem, 50vh);
        overflow-x: hidden;
        overflow-y: auto;
        animation: none !important;
        font-family: inherit;
    }

    /* Dọn portal body cũ nếu còn sót instance */
    body > .ts-dropdown {
        display: none !important;
        visibility: hidden !important;
        pointer-events: none !important;
        opacity: 0 !important;
    }

    #admin-content .ts-wrapper .ts-dropdown .dropdown-input-wrap,
    .ui-select-field .ts-dropdown .dropdown-input-wrap,
    .instructor-select-field .ts-dropdown .dropdown-input-wrap {
        border-bottom: 1px solid #e5e7eb;
        padding: 0.5rem;
        background: rgba(238, 246, 255, 0.5);
        position: sticky;
        top: 0;
        z-index: 1;
    }

    #admin-content .ts-wrapper .ts-dropdown .dropdown-input,
    .ui-select-field .ts-dropdown .dropdown-input,
    .instructor-select-field .ts-dropdown .dropdown-input {
        border: 1px solid #d5dae3 !important;
        border-radius: 0.5rem !important;
        padding: 0.5rem 0.75rem 0.5rem 2rem !important;
        font-size: 0.875rem;
        background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='%239ca3af' viewBox='0 0 16 16'%3E%3Cpath d='M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85zm-5.242 1.106a5 5 0 1 1 0-10 5 5 0 0 1 0 10z'/%3E%3C/svg%3E") 0.625rem center / 0.875rem no-repeat !important;
    }

    #admin-content .ts-wrapper .ts-dropdown .dropdown-input:focus,
    .ui-select-field .ts-dropdown .dropdown-input:focus,
    .instructor-select-field .ts-dropdown .dropdown-input:focus {
        border-color: #4ea1ff !important;
        box-shadow: 0 0 0 3px rgba(78, 161, 255, 0.18) !important;
        outline: none;
    }

    #admin-content .ts-wrapper .ts-dropdown .option,
    .ui-select-field .ts-dropdown .option,
    .instructor-select-field .ts-dropdown .option {
        display: flex;
        align-items: center;
        min-width: 0;
        min-height: 2.5rem;
        padding: 0.5rem 0.875rem;
        line-height: 1.25rem;
        white-space: normal !important;
        overflow-wrap: anywhere;
        word-break: break-word;
        transition: background-color 0.12s ease;
    }

    #admin-content .ts-wrapper .ts-dropdown .option.active,
    .ui-select-field .ts-dropdown .option.active,
    .instructor-select-field .ts-dropdown .option.active {
        background: #eef6ff !important;
        color: #3580d6 !important;
    }

    #admin-content .ts-wrapper .ts-dropdown .option:hover,
    .ui-select-field .ts-dropdown .option:hover,
    .instructor-select-field .ts-dropdown .option:hover {
        background: #f0f7ff !important;
    }

    #admin-content .ts-wrapper .ts-dropdown .no-results,
    .ui-select-field .ts-dropdown .no-results,
    .instructor-select-field .ts-dropdown .no-results {
        padding: 0.75rem 0.875rem;
        color: #6b7280;
        font-size: 0.875rem;
    }

    #admin-content .ts-wrapper.disabled .ts-control {
        background: #f3f4f6 !important;
        opacity: 0.85;
        cursor: not-allowed;
        pointer-events: none;
    }

    #admin-content .ts-wrapper:not(.disabled) .ts-control {
        pointer-events: auto !important;
        cursor: pointer;
    }

    /* Dropdown ít giá trị — không có ô tìm kiếm */
    #admin-content .ts-wrapper.ts-no-search:not(.disabled) .ts-control {
        cursor: pointer;
    }

    #admin-content .ts-wrapper.ts-no-search .ts-control input {
        display: none !important;
        width: 0 !important;
        min-width: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        opacity: 0 !important;
        position: absolute !important;
        pointer-events: none !important;
    }

    #admin-content .ts-wrapper .ts-dropdown.ts-no-search-panel .dropdown-input-wrap,
    .ui-select-field .ts-dropdown.ts-no-search-panel .dropdown-input-wrap,
    .instructor-select-field .ts-dropdown.ts-no-search-panel .dropdown-input-wrap,
    body > .ts-dropdown.ts-no-search-panel .dropdown-input-wrap {
        display: none !important;
    }

    .instructor-option {
        display: flex;
        align-items: center;
        gap: 0.625rem;
        line-height: 1.3;
    }

    .instructor-option__avatar {
        width: 2rem;
        height: 2rem;
        border-radius: 0.5rem;
        background: linear-gradient(135deg, #6eb5ff, #4ea1ff);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-size: 0.6875rem;
        font-weight: 700;
        flex-shrink: 0;
        box-shadow: 0 2px 6px -2px rgba(78, 161, 255, 0.45);
    }

    .instructor-option__body { min-width: 0; flex: 1; }
    .instructor-option__name { font-weight: 600; color: #1f2937; font-size: 0.875rem; }
    .instructor-option__meta { font-size: 0.75rem; color: #6b7280; margin-top: 0.125rem; }
    .instructor-option__item-name { font-weight: 500; }
    .instructor-option__item-code { color: #6b7280; font-weight: 400; }
</style>
<script>
(function () {
    const SEARCH_MIN_OPTIONS = 15;
    const DROPDOWN_VIEWPORT_GUTTER = 12;
    const DROPDOWN_MOBILE_BREAKPOINT = 640;
    const DROPDOWN_DEFAULT_MAX_WIDTH = 720;
    const DROPDOWN_DEFAULT_MAX_HEIGHT = 320;

    function shouldSkipSelect(el) {
        if (!el || el.tagName !== 'SELECT') return true;
        if (el.classList.contains('tomselected')) return true;
        if (el.hasAttribute('data-native-select')) return true;
        if (el.dataset.tomSelect === 'off') return true;
        if (el.dataset.instructorSelectSkip === '1') return true;
        return false;
    }

    function isFieldVisible(el) {
        const field = el.closest('.ui-select-field, .instructor-select-field') || el;
        if (!field || field.closest('.hidden, [hidden]')) return false;

        // Walk ancestors — offsetParent fails with some overflow/fixed layouts
        let node = field;
        while (node && node !== document.body) {
            const style = getComputedStyle(node);
            if (style.display === 'none' || style.visibility === 'hidden') return false;
            if (node.classList && node.classList.contains('hidden')) return false;
            node = node.parentElement;
        }

        const rect = field.getBoundingClientRect();
        return rect.width > 0 || rect.height > 0;
    }

    function hideTomDropdown(dropdown) {
        if (!dropdown) return;
        dropdown.classList.remove('ts-dropdown-open');
        const search = dropdown.querySelector('.dropdown-input');
        if (search) search.value = '';
    }

    function resetDropdownGeometry(dropdown) {
        if (!dropdown) return;
        [
            'left', 'right', 'top', 'bottom', 'width', 'max-width',
            'max-height', 'margin-top', 'margin-bottom'
        ].forEach(function (property) {
            dropdown.style.removeProperty(property);
        });
    }

    function numericDatasetValue(el, key, fallback) {
        const value = Number.parseInt(el && el.dataset ? el.dataset[key] : '', 10);
        return Number.isFinite(value) && value > 0 ? value : fallback;
    }

    function measureDropdownContent(tom) {
        const input = tom && (tom.input || tom.$input?.[0]);
        const control = tom && tom.control;
        if (!input || !control) return 0;

        const style = window.getComputedStyle(control);
        let context = null;
        try {
            const canvas = document.createElement('canvas');
            context = canvas.getContext('2d');
            if (context) {
                context.font = [style.fontStyle, style.fontWeight, style.fontSize, style.fontFamily]
                    .filter(Boolean)
                    .join(' ');
            }
        } catch (e) { /* fallback bên dưới */ }

        let longest = 0;
        Array.from(input.options || []).forEach(function (option) {
            const text = String(option.textContent || option.label || '').trim();
            if (!text) return;
            const width = context ? context.measureText(text).width : text.length * 8;
            longest = Math.max(longest, width);
        });

        // Chừa chỗ cho padding, scrollbar, icon/avatar và metadata của option.
        const chromeWidth = input.hasAttribute('data-instructor-select') ? 112 : 64;
        return Math.ceil(longest + chromeWidth);
    }

    /**
     * Control giữ nguyên kích thước trong grid. Chỉ dropdown đang mở được nới
     * theo nội dung và tự dịch trái/phải để luôn nằm trong viewport.
     */
    function applyAdaptiveDropdownGeometry(tom) {
        if (!tom || !tom.dropdown || !tom.wrapper || !tom.control) return;

        const input = tom.input || tom.$input?.[0];
        const dropdown = tom.dropdown;
        const wrapperRect = tom.wrapper.getBoundingClientRect();
        const controlRect = tom.control.getBoundingClientRect();
        const viewportWidth = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
        const viewportHeight = Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0);
        if (!viewportWidth || !controlRect.width) return;

        const availableWidth = Math.max(180, viewportWidth - (DROPDOWN_VIEWPORT_GUTTER * 2));
        const maxConfiguredWidth = numericDatasetValue(input, 'dropdownMaxWidth', DROPDOWN_DEFAULT_MAX_WIDTH);
        const keepControlWidth = input && input.dataset.dropdownWidth === 'control';
        const mobile = viewportWidth <= DROPDOWN_MOBILE_BREAKPOINT;
        const contentWidth = keepControlWidth ? controlRect.width : measureDropdownContent(tom);
        const effectiveMaxWidth = Math.max(controlRect.width, maxConfiguredWidth);
        const desiredWidth = mobile
            ? availableWidth
            : Math.min(
                availableWidth,
                effectiveMaxWidth,
                Math.max(controlRect.width, contentWidth)
            );

        let relativeLeft = 0;
        if (mobile) {
            relativeLeft = DROPDOWN_VIEWPORT_GUTTER - wrapperRect.left;
        } else if (wrapperRect.left + desiredWidth > viewportWidth - DROPDOWN_VIEWPORT_GUTTER) {
            relativeLeft = viewportWidth - DROPDOWN_VIEWPORT_GUTTER - desiredWidth - wrapperRect.left;
        }
        relativeLeft = Math.max(DROPDOWN_VIEWPORT_GUTTER - wrapperRect.left, relativeLeft);

        dropdown.style.setProperty('left', Math.round(relativeLeft) + 'px', 'important');
        dropdown.style.setProperty('right', 'auto', 'important');
        dropdown.style.setProperty('width', Math.round(desiredWidth) + 'px', 'important');
        dropdown.style.setProperty('max-width', Math.round(availableWidth) + 'px', 'important');

        const spaceBelow = viewportHeight - controlRect.bottom - DROPDOWN_VIEWPORT_GUTTER;
        const spaceAbove = controlRect.top - DROPDOWN_VIEWPORT_GUTTER;
        const openUpward = spaceBelow < 180 && spaceAbove > spaceBelow;
        const availableHeight = Math.max(140, openUpward ? spaceAbove : spaceBelow);
        const maxConfiguredHeight = numericDatasetValue(input, 'dropdownMaxHeight', DROPDOWN_DEFAULT_MAX_HEIGHT);

        dropdown.style.setProperty('max-height', Math.min(maxConfiguredHeight, availableHeight) + 'px', 'important');
        if (openUpward) {
            dropdown.style.setProperty('top', 'auto', 'important');
            dropdown.style.setProperty('bottom', 'calc(100% + 0.25rem)', 'important');
            dropdown.style.setProperty('margin-top', '0', 'important');
            dropdown.style.setProperty('margin-bottom', '0', 'important');
        } else {
            dropdown.style.setProperty('top', '100%', 'important');
            dropdown.style.setProperty('bottom', 'auto', 'important');
            dropdown.style.setProperty('margin-top', '0.25rem', 'important');
            dropdown.style.setProperty('margin-bottom', '0', 'important');
        }
    }

    function removeOrphanBodyDropdowns() {
        document.querySelectorAll('body > .ts-dropdown').forEach(function (dd) {
            if (dd.parentNode) dd.parentNode.removeChild(dd);
        });
    }

    /**
     * Ép dropdown nằm trong .ts-wrapper + absolute top:100%.
     * KHÔNG dùng dropdownParent:"body" (Tom Select tính top bằng window.scrollY
     * trong khi app cuộn #admin-content → panel trôi khỏi ô).
     */
    function lockDropdownToWrapper(tom) {
        if (!tom || !tom.dropdown || !tom.wrapper) return;

        // Khóa setting — chặn plugin/code khác set lại "body"
        try {
            tom.settings.dropdownParent = null;
        } catch (e) { /* ignore */ }

        if (tom.dropdown.parentNode !== tom.wrapper) {
            tom.wrapper.appendChild(tom.dropdown);
        }

        const dd = tom.dropdown;
        dd.classList.add('ts-dropdown-open');
        // setProperty + important: thắng CDN CSS / style inline cũ (top:xxxpx)
        dd.style.setProperty('position', 'absolute', 'important');
        dd.style.setProperty('top', '100%', 'important');
        dd.style.setProperty('left', '0', 'important');
        dd.style.setProperty('right', 'auto', 'important');
        dd.style.setProperty('bottom', 'auto', 'important');
        dd.style.setProperty('width', '100%', 'important');
        dd.style.setProperty('margin-top', '0.25rem', 'important');
        dd.style.setProperty('margin-left', '0', 'important');
        dd.style.setProperty('margin-right', '0', 'important');
        dd.style.setProperty('transform', 'none', 'important');
        dd.style.setProperty('z-index', '10060', 'important');
        dd.style.setProperty('max-height', 'min(16rem, 50vh)', 'important');
        dd.style.setProperty('overflow-x', 'hidden', 'important');
        dd.style.setProperty('overflow-y', 'auto', 'important');
        // Xóa toạ độ fixed/body cũ nếu còn
        dd.style.removeProperty('inset');
        applyAdaptiveDropdownGeometry(tom);
    }

    function positionTomDropdown(tom) {
        if (!tom || !tom.isOpen) return;
        lockDropdownToWrapper(tom);
    }

    function bindGlobalReposition() {
        removeOrphanBodyDropdowns();

        if (window.__tomRepositionBoundV4) return;
        window.__tomRepositionBoundV4 = true;

        document.addEventListener('mousedown', function (e) {
            const inControl = e.target.closest && e.target.closest('.ts-wrapper, .ts-dropdown');
            if (inControl) return;
            window.closeAllTomSelects();
        }, true);
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') window.closeAllTomSelects();
        });

        let repositionFrame = null;
        const repositionOpenDropdowns = function () {
            if (repositionFrame) return;
            repositionFrame = requestAnimationFrame(function () {
                repositionFrame = null;
                document.querySelectorAll('select.tomselected').forEach(function (el) {
                    if (el.tomselect && el.tomselect.isOpen) {
                        applyAdaptiveDropdownGeometry(el.tomselect);
                    }
                });
            });
        };
        window.addEventListener('resize', repositionOpenDropdowns, { passive: true });
        document.addEventListener('scroll', repositionOpenDropdowns, { passive: true, capture: true });

        document.addEventListener('turbo:load', function () {
            removeOrphanBodyDropdowns();
            repositionOpenDropdowns();
        });
    }

    function dropdownHooks(el) {
        const isMulti = !!(el && el.multiple);
        let closeAfterSelect = !isMulti;
        if (el && el.dataset.closeAfterSelect === '0') closeAfterSelect = false;
        if (el && el.dataset.closeAfterSelect === '1') closeAfterSelect = true;

        return {
            // BẮT BUỘC null — không được "body"
            dropdownParent: null,
            closeAfterSelect: closeAfterSelect,
            onInitialize: function () {
                // Một số plugin chạy sau constructor — khóa lại sau init
                try { this.settings.dropdownParent = null; } catch (e) { /* ignore */ }
                if (this.dropdown && this.wrapper && this.dropdown.parentNode !== this.wrapper) {
                    this.wrapper.appendChild(this.dropdown);
                }
            },
            onDropdownOpen: function () {
                const self = this;
                try {
                    try { self.settings.dropdownParent = null; } catch (e) { /* ignore */ }

                    document.querySelectorAll('select.tomselected').forEach(function (other) {
                        if (other.tomselect && other.tomselect !== self && other.tomselect.isOpen) {
                            try { other.tomselect.close(); } catch (e) { /* ignore */ }
                        }
                    });

                    const input = self.input || self.$input?.[0];
                    if (input && !needsSearchInput(input)) {
                        self.dropdown?.classList.add('ts-no-search-panel');
                    } else {
                        self.dropdown?.classList.remove('ts-no-search-panel');
                    }

                    lockDropdownToWrapper(self);
                    // Tom Select có thể ghi style sau hook — khóa lại 2 frame
                    requestAnimationFrame(function () {
                        if (self.isOpen) lockDropdownToWrapper(self);
                        requestAnimationFrame(function () {
                            if (self.isOpen) lockDropdownToWrapper(self);
                        });
                    });
                    // Dọn portal body (instance cũ / race)
                    removeOrphanBodyDropdowns();
                } catch (err) {
                    console.error('Tom Select dropdown open failed:', err);
                }
            },
            onItemAdd: function () {
                if (closeAfterSelect && this.isOpen) {
                    try { this.close(); } catch (e) { /* ignore */ }
                } else if (this.isOpen) {
                    lockDropdownToWrapper(this);
                }
            },
            onDropdownClose: function () {
                try {
                    hideTomDropdown(this.dropdown);
                    resetDropdownGeometry(this.dropdown);
                    removeOrphanBodyDropdowns();
                } catch (e) { /* ignore */ }
            },
            onBlur: function () {
                try {
                    if (this.isOpen) this.close();
                    hideTomDropdown(this.dropdown);
                    resetDropdownGeometry(this.dropdown);
                } catch (e) { /* ignore */ }
            },
        };
    }

    function baseConfig(el) {
        return Object.assign({
            create: false,
            allowEmptyOption: true,
            maxOptions: null,
            placeholder: el.getAttribute('placeholder') || el.dataset.placeholder || (el.multiple ? 'Chọn...' : 'Chọn...'),
        }, dropdownHooks(el));
    }

    function bindControlOpen(tom) {
        if (!tom.control || tom.control.dataset.openBound) return;
        tom.control.dataset.openBound = '1';
        tom.control.addEventListener('click', function (e) {
            if (tom.isDisabled) return;
            if (e.target.closest('.clear-button, .remove')) return;
            if (!tom.isOpen) tom.open();
        });
    }

    function applySelectMode(tom, el) {
        try { tom.settings.dropdownParent = null; } catch (e) { /* ignore */ }
        patchTomPosition(tom);
        // Ghi đè chắc chắn (một số bản Tom Select bind method sớm)
        tom.positionDropdown = function () {
            if (this.isOpen) lockDropdownToWrapper(this);
        };
        if (tom.dropdown && tom.wrapper && tom.dropdown.parentNode !== tom.wrapper) {
            tom.wrapper.appendChild(tom.dropdown);
        }
        bindControlOpen(tom);

        // LMS giữ bảng màu/kích thước riêng, chỉ dùng chung lifecycle + geometry.
        if (document.body && document.body.classList.contains('lms-shell')) {
            tom.wrapper.classList.add('lms-ts-field');
            if (el.hasAttribute('data-tom-status')) {
                tom.wrapper.classList.add('lms-ts-status');
            }
        }

        if (!needsSearchInput(el)) {
            tom.wrapper.classList.add('ts-no-search');
            if (tom.dropdown) {
                tom.dropdown.classList.add('ts-no-search-panel');
            }
            if (tom.control_input) {
                tom.control_input.readOnly = true;
                tom.control_input.setAttribute('readonly', 'readonly');
                tom.control_input.setAttribute('tabindex', '-1');
            }
        }
    }

    function instructorInitials(name) {
        if (!name) return 'GV';
        const parts = String(name).trim().split(/\s+/);
        if (parts.length === 1) return parts[0].slice(0, 2).toUpperCase();
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }

    function instructorOptionHtml(data, escape, compact) {
        const code = data.code ? escape(data.code) : '';
        const unit = data.unit ? escape(data.unit) : '';
        const name = escape(data.text || '');
        const meta = [code, unit].filter(Boolean).join(' · ');

        if (compact) {
            return '<div class="instructor-option instructor-option--compact"><span class="instructor-option__item-name">' + name + '</span>' +
                (code ? '<span class="instructor-option__item-code"> (' + code + ')</span>' : '') + '</div>';
        }

        return '<div class="instructor-option"><div class="instructor-option__avatar">' + escape(instructorInitials(data.text)) + '</div>' +
            '<div class="instructor-option__body"><div class="instructor-option__name">' + name + '</div>' +
            (meta ? '<div class="instructor-option__meta">' + meta + '</div>' : '') + '</div></div>';
    }

    function syncInstructorMeta(select, tom) {
        Object.keys(tom.options).forEach(function (key) {
            const option = tom.options[key];
            const elOption = select.querySelector('option[value="' + CSS.escape(key) + '"]');
            if (!elOption) return;
            option.code = elOption.dataset.code || '';
            option.unit = elOption.dataset.unit || '';
        });
    }

    function countSelectOptions(el) {
        return Array.from(el.options).filter(function (o) { return o.value !== ''; }).length;
    }

    function needsSearchInput(el) {
        if (!el || !el.options) return false;
        if (el.dataset.searchable === '0') return false;
        if (el.dataset.searchable === '1') return true;
        return countSelectOptions(el) > SEARCH_MIN_OPTIONS;
    }

    function buildPlugins(el) {
        const plugins = ['clear_button'];
        if (el.multiple) plugins.unshift('remove_button');
        if (needsSearchInput(el)) {
            plugins.unshift('dropdown_input');
        }
        return plugins;
    }

    function patchTomPosition(tom) {
        tom.positionDropdown = function () {
            positionTomDropdown(tom);
        };
    }

    function buildInstructorTomSelect(el) {
        const withSearch = needsSearchInput(el);
        const hooks = dropdownHooks(el);
        const instructorConfig = {
            placeholder: withSearch
                ? (el.dataset.placeholder || 'Tìm và chọn giảng viên...')
                : (el.dataset.placeholder || 'Chọn giảng viên...'),
            plugins: buildPlugins(el),
            dropdownParent: null,
            closeAfterSelect: hooks.closeAfterSelect,
            render: {
                option: function (data, escape) { return instructorOptionHtml(data, escape, false); },
                item: function (data, escape) { return instructorOptionHtml(data, escape, true); },
                no_results: function () { return '<div class="no-results">Không tìm thấy giảng viên phù hợp</div>'; },
            },
            onInitialize: function () {
                if (typeof hooks.onInitialize === 'function') hooks.onInitialize.call(this);
                syncInstructorMeta(el, this);
                applySelectMode(this, el);
            },
            onDropdownOpen: hooks.onDropdownOpen,
            onDropdownClose: hooks.onDropdownClose,
            onItemAdd: hooks.onItemAdd,
            onBlur: hooks.onBlur,
        };

        if (withSearch) {
            instructorConfig.searchField = ['text', 'code', 'unit'];
        }

        return new window.TomSelect(el, Object.assign({}, baseConfig(el), instructorConfig));
    }

    function buildGenericTomSelect(el) {
        const hooks = dropdownHooks(el);
        const config = Object.assign({}, baseConfig(el), {
            plugins: buildPlugins(el),
            dropdownParent: null,
            render: {
                no_results: function () { return '<div class="no-results">Không tìm thấy kết quả</div>'; },
            },
            onInitialize: function () {
                if (typeof hooks.onInitialize === 'function') hooks.onInitialize.call(this);
                applySelectMode(this, el);
            },
            onDropdownOpen: hooks.onDropdownOpen,
            onDropdownClose: hooks.onDropdownClose,
            onItemAdd: hooks.onItemAdd,
            onBlur: hooks.onBlur,
            closeAfterSelect: hooks.closeAfterSelect,
        });

        if (el.multiple && el.dataset.maxItems) {
            config.maxItems = parseInt(el.dataset.maxItems, 10) || null;
        }

        return new window.TomSelect(el, config);
    }

    function ensureSelectWrapper(el) {
        if (el.closest('.ui-select-field, .instructor-select-field')) return;

        const wrap = document.createElement('div');
        wrap.className = el.hasAttribute('data-instructor-select')
            ? 'instructor-select-field'
            : 'ui-select-field';
        el.parentNode.insertBefore(wrap, el);
        wrap.appendChild(el);
    }

    function initOne(el) {
        if (shouldSkipSelect(el)) return;
        ensureSelectWrapper(el);
        if (!isFieldVisible(el)) return;
        if (el.tomselect) return;

        try {
            if (el.hasAttribute('data-instructor-select')) {
                buildInstructorTomSelect(el);
            } else {
                buildGenericTomSelect(el);
            }
            bindGlobalReposition();
        } catch (err) {
            console.error('Tom Select init failed:', el.name || el.id, err);
        }
    }

    window.destroyTomSelect = function (el) {
        if (!el) return;
        if (el.tomselect) {
            try {
                const tom = el.tomselect;
                const dropdown = tom.dropdown;
                try { tom.close(); } catch (e) { /* ignore */ }
                hideTomDropdown(dropdown);
                resetDropdownGeometry(dropdown);
                tom.destroy();
                // Dọn portal body còn sót (phiên bản cũ)
                if (dropdown && dropdown.parentNode === document.body) {
                    dropdown.parentNode.removeChild(dropdown);
                }
                removeOrphanBodyDropdowns();
            } catch (err) {
                console.warn('Tom Select destroy failed:', err);
            }
        }
        // Dọn rác nếu destroy không khôi phục hết native select
        delete el.tomselect;
        el.classList.remove('tomselected', 'ts-hidden-accessible');
        el.removeAttribute('tabindex');
        el.style.display = '';
        el.style.position = '';
        el.style.left = '';
        el.style.top = '';
        el.style.width = '';
        el.style.height = '';
        el.style.opacity = '';
        el.style.visibility = '';
        el.style.pointerEvents = '';
    };

    /** Đóng mọi dropdown đang mở + ẩn panel body (KHÔNG removeChild khi instance còn sống) */
    window.closeAllTomSelects = function () {
        document.querySelectorAll('select.tomselected').forEach(function (el) {
            if (el.tomselect) {
                try {
                    if (el.tomselect.isOpen) el.tomselect.close();
                    hideTomDropdown(el.tomselect.dropdown);
                } catch (e) { /* ignore */ }
            }
        });
        removeOrphanBodyDropdowns();
    };

    window.initTomSelects = function (root) {
        if (typeof window.TomSelect === 'undefined') return false;
        const scope = root || document.getElementById('admin-content') || document;
        scope.querySelectorAll('select').forEach(initOne);
        return true;
    };

    window.initInstructorSelects = window.initTomSelects;

    window.refreshTomSelect = function (el) {
        if (!el) return;
        window.destroyTomSelect(el);
        initOne(el);
        if (el.tomselect) el.tomselect.sync();
    };

    window.refreshInstructorSelect = window.refreshTomSelect;

    window.getSelectValue = function (id) {
        const el = typeof id === 'string' ? document.getElementById(id) : id;
        if (!el) return '';
        if (el.tomselect) {
            const val = el.tomselect.getValue();
            return Array.isArray(val) ? (val[0] || '') : (val || '');
        }
        return el.value || '';
    };

    window.getSelectOption = function (id) {
        const el = typeof id === 'string' ? document.getElementById(id) : id;
        if (!el) return null;
        const value = window.getSelectValue(el);
        if (!value) return null;
        return el.querySelector('option[value="' + CSS.escape(String(value)) + '"]');
    };

    window.getTomValues = function (id) {
        const el = typeof id === 'string' ? document.getElementById(id) : id;
        if (!el) return [];
        if (el.tomselect) {
            const val = el.tomselect.getValue();
            return Array.isArray(val) ? val : (val ? [val] : []);
        }
        if (el.multiple) return Array.from(el.selectedOptions).map(o => o.value);
        return el.value ? [el.value] : [];
    };

    window.setTomValues = function (id, values, silent) {
        const el = typeof id === 'string' ? document.getElementById(id) : id;
        if (!el) return;
        const list = Array.isArray(values) ? values : (values ? [values] : []);
        if (el.tomselect) {
            el.tomselect.setValue(el.multiple ? list : (list[0] || ''), silent !== false);
            return;
        }
        if (el.multiple) {
            Array.from(el.options).forEach(o => { o.selected = list.includes(o.value); });
        } else {
            el.value = list[0] || '';
        }
        el.dispatchEvent(new Event('change', { bubbles: true }));
    };

    window.rebuildTomOptions = function (id, items, selected) {
        window.setTomSelectOptions(id, items, { selected: selected, enabled: true });
    };

    /**
     * Cập nhật options Tom Select an toàn (destroy + rebuild, không kẹt disabled).
     * items: [{ value, text, code?, unit? }]
     * opts: { selected?: string|string[], enabled?: boolean }
     */
    window.setTomSelectOptions = function (id, items, opts) {
        const el = typeof id === 'string' ? document.getElementById(id) : id;
        if (!el || typeof TomSelect === 'undefined') return;

        const options = opts || {};
        const enabled = options.enabled !== false;
        const list = Array.isArray(items) ? items : [];

        // selected: string | string[] | null — hỗ trợ multi-select
        let selectedList = [];
        if (options.selected !== undefined && options.selected !== null && options.selected !== '') {
            selectedList = Array.isArray(options.selected)
                ? options.selected.map(String)
                : [String(options.selected)];
        }
        const selectedSet = new Set(selectedList);

        window.destroyTomSelect(el);

        el.innerHTML = '';
        list.forEach(function (item) {
            const opt = document.createElement('option');
            const value = item.value === null || item.value === undefined ? '' : String(item.value);
            opt.value = value;
            opt.textContent = item.text != null ? String(item.text) : value;
            if (item.code) opt.dataset.code = item.code;
            if (item.unit) opt.dataset.unit = item.unit;
            if (item.name) opt.dataset.name = item.name;
            if (item.email) opt.dataset.email = item.email;
            if (item.unitId != null && item.unitId !== '') opt.dataset.unitId = String(item.unitId);
            if (item.unit_id != null && item.unit_id !== '') opt.dataset.unitId = String(item.unit_id);
            if (item.start !== undefined) opt.dataset.start = String(item.start ?? '');
            if (item.end !== undefined) opt.dataset.end = String(item.end ?? '');
            if (item.specialization_id !== undefined) {
                opt.dataset.specializationId = String(item.specialization_id ?? '');
            }
            if (item.data && typeof item.data === 'object') {
                Object.keys(item.data).forEach(function (k) {
                    const key = String(k);
                    opt.dataset[key] = item.data[k] == null ? '' : String(item.data[k]);
                });
            }
            if (selectedSet.has(value)) opt.selected = true;
            el.appendChild(opt);
        });

        // Quan trọng: set enabled TRƯỚC khi new TomSelect — init disabled sẽ khóa click
        el.disabled = !enabled;
        el.removeAttribute('aria-disabled');

        ensureSelectWrapper(el);

        try {
            if (el.hasAttribute('data-instructor-select')) {
                buildInstructorTomSelect(el);
            } else {
                buildGenericTomSelect(el);
            }
            bindGlobalReposition();
        } catch (err) {
            console.error('setTomSelectOptions init failed:', el.id || el.name, err);
        }

        if (el.tomselect) {
            if (enabled) {
                el.tomselect.enable();
                el.disabled = false;
            } else {
                el.tomselect.disable();
            }
            if (el.multiple) {
                el.tomselect.setValue(selectedList, true);
            } else if (selectedList.length) {
                el.tomselect.setValue(selectedList[0], true);
            } else {
                el.tomselect.setValue('', true);
            }
        } else if (el.multiple) {
            Array.from(el.options).forEach(function (o) {
                o.selected = selectedSet.has(o.value);
            });
        } else {
            el.value = selectedList[0] || '';
        }
    };

    window.setTomSelectEnabled = function (id, enabled) {
        const el = typeof id === 'string' ? document.getElementById(id) : id;
        if (!el) return;
        el.disabled = !enabled;
        if (el.tomselect) {
            if (enabled) el.tomselect.enable();
            else el.tomselect.disable();
        }
    };

    window.onTomChange = function (id, handler) {
        const el = typeof id === 'string' ? document.getElementById(id) : id;
        if (!el) return;

        const bind = function () {
            if (el.tomselect) {
                el.tomselect.on('change', handler);
            } else {
                el.addEventListener('change', handler);
            }
        };

        bind();

        if (!el.tomselect) {
            const observer = new MutationObserver(function () {
                if (el.tomselect) {
                    observer.disconnect();
                    el.addEventListener('change', handler);
                    el.tomselect.on('change', handler);
                }
            });
            observer.observe(el, { attributes: true, attributeFilter: ['class'] });
        }
    };

    let bootRetryTimer = null;
    let bootRetryCount = 0;

    function bootTomSelects() {
        const ready = window.initTomSelects(document.getElementById('admin-content') || document);
        if (ready) {
            bootRetryCount = 0;
            if (bootRetryTimer) {
                clearTimeout(bootRetryTimer);
                bootRetryTimer = null;
            }
            return;
        }

        // Module Vite được thực thi sau khi HTML đã parse. Chờ thư viện thực sự
        // sẵn sàng thay vì dùng một timeout cố định dễ gây dropdown native.
        if (bootRetryCount >= 100) return;
        bootRetryCount++;
        clearTimeout(bootRetryTimer);
        bootRetryTimer = setTimeout(bootTomSelects, 50);
    }

    if (!window.__tomSelectBound) {
        window.__tomSelectBound = true;
        window.addEventListener('app:tom-select-ready', bootTomSelects);
        document.addEventListener('turbo:load', bootTomSelects);
        document.addEventListener('DOMContentLoaded', bootTomSelects);
        document.addEventListener('turbo:before-cache', function () {
            document.querySelectorAll('select.tomselected').forEach(function (el) {
                window.destroyTomSelect(el);
            });
        });
    }

    bootTomSelects();
})();
</script>
