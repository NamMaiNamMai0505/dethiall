<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/vn.js"></script>
<style>
    .date-input-field {
        width: 100%;
        display: block;
        position: relative;
        /* Cho calendar absolute bám field — không bị clip / không portal body */
        overflow: visible !important;
        z-index: 1;
    }

    .date-input-field.is-fp-open {
        z-index: 10050;
    }

    /*
     * Flatpickr static bọc input bằng .flatpickr-wrapper (mặc định inline-block)
     * → lệch hàng so với Tom Select / nút. Ép full-width block.
     */
    .date-input-field .flatpickr-wrapper,
    .date-input-control .flatpickr-wrapper,
    .flatpickr-wrapper {
        display: block !important;
        width: 100% !important;
        max-width: 100% !important;
        position: relative !important;
        vertical-align: top;
    }

    .date-input-control {
        position: relative;
        width: 100%;
        min-height: 44px;
        display: block;
    }

    .date-input-control .date-input-icon {
        position: absolute;
        right: 0.875rem;
        top: 50%;
        transform: translateY(-50%);
        width: 1.25rem;
        height: 1.25rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #9ca3af;
        pointer-events: none;
        font-size: 1.0625rem;
        line-height: 1;
        z-index: 3;
        transition: color 0.2s ease, filter 0.2s ease;
    }

    .date-input-control:focus-within .date-input-icon {
        color: #4ea1ff;
        filter: drop-shadow(0 0 4px rgba(78, 161, 255, 0.35));
    }

    input[type="date"].date-input,
    input[type="text"].date-input,
    .date-input-control input[type="date"],
    .date-input-control input.flatpickr-input,
    .date-input-control .flatpickr-input.form-control,
    .date-input-field input.flatpickr-input,
    .date-input-field .flatpickr-input.form-control {
        width: 100% !important;
        min-height: 44px !important;
        height: 44px !important;
        padding: 0 2.75rem 0 0.875rem !important;
        border-radius: 0.625rem !important;
        border: 1px solid #d5dae3 !important;
        background: rgba(250, 248, 244, 0.92) !important;
        color: #1f2937 !important;
        font-size: 0.875rem !important;
        line-height: 1.25rem !important;
        box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.03);
        transition: border-color 0.2s ease, box-shadow 0.28s ease, background-color 0.2s ease;
        cursor: pointer;
    }

    input.date-input:hover:not(:disabled):not([readonly]),
    .date-input-control input:hover:not(:disabled):not([readonly]) {
        border-color: rgba(78, 161, 255, 0.35) !important;
    }

    input.date-input:focus,
    .date-input-control input:focus,
    .date-input-control .flatpickr-input:focus,
    .date-input-field .flatpickr-input:focus {
        outline: none !important;
        border-color: #4ea1ff !important;
        box-shadow: 0 0 0 3px rgba(78, 161, 255, 0.22), inset 0 1px 2px rgba(15, 23, 42, 0.03) !important;
    }

    input.date-input:disabled,
    .date-input-control input:disabled,
    input.date-input[readonly],
    .date-input-control input[readonly] {
        background: #f3f4f6 !important;
        color: #6b7280 !important;
        cursor: not-allowed;
        opacity: 0.9;
    }

    input[type="date"].date-input::-webkit-calendar-picker-indicator {
        opacity: 0;
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        cursor: pointer;
    }

    /*
     * Calendar bám field (static / absolute trong .date-input-field).
     * KHÔNG fixed + body — cuộn #admin-content sẽ trôi khỏi ô.
     */
    .date-input-field .flatpickr-calendar,
    .flatpickr-calendar.static,
    .flatpickr-calendar.arrowTop,
    .flatpickr-calendar.arrowBottom {
        z-index: 10060 !important;
        border: 1px solid #d5dae3 !important;
        border-radius: 0.75rem !important;
        background: rgba(250, 248, 244, 0.98) !important;
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        box-shadow: 0 12px 32px -8px rgba(78, 161, 255, 0.28), 0 0 0 1px rgba(78, 161, 255, 0.1) !important;
        font-family: inherit;
        overflow: hidden;
    }

    .date-input-field .flatpickr-calendar,
    .date-input-field .flatpickr-calendar.static,
    .date-input-field .flatpickr-calendar.open {
        position: absolute !important;
        top: calc(100% + 4px) !important;
        left: 0 !important;
        right: auto !important;
        margin: 0 !important;
        transform: none !important;
        width: max(100%, 16.5rem) !important;
    }

    /* Ẩn calendar portal body (phiên bản cũ / sót) */
    body > .flatpickr-calendar {
        /* vẫn cho phép nếu static=false fallback; ưu tiên field */
    }

    .flatpickr-months {
        background: linear-gradient(180deg, #eef6ff 0%, rgba(238, 246, 255, 0.4) 100%);
        border-bottom: 1px solid #e5e7eb;
        padding: 0.25rem 0;
    }

    .flatpickr-current-month {
        display: flex !important;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        font-weight: 600;
        color: #1f2937;
    }

    .flatpickr-current-month .cur-month,
    .flatpickr-current-month .numInputWrapper,
    .flatpickr-current-month .cur-year {
        display: inline-block !important;
        visibility: visible !important;
        opacity: 1 !important;
    }

    .flatpickr-current-month .cur-month,
    .flatpickr-current-month .cur-year {
        color: #1e3264 !important;
        font-size: 1rem !important;
        font-weight: 700 !important;
    }

    .flatpickr-current-month .numInputWrapper,
    .flatpickr-current-month .cur-year {
        width: 4.5rem !important;
    }

    .flatpickr-weekdays {
        background: #f9fafb;
    }

    span.flatpickr-weekday {
        color: #6b7280;
        font-weight: 600;
        font-size: 0.75rem;
    }

    .flatpickr-day {
        border-radius: 0.5rem;
        color: #374151;
        font-weight: 500;
    }

    .flatpickr-day:hover,
    .flatpickr-day:focus {
        background: #f0f7ff;
        border-color: #f0f7ff;
    }

    .flatpickr-day.selected,
    .flatpickr-day.startRange,
    .flatpickr-day.endRange,
    .flatpickr-day.selected.inRange,
    .flatpickr-day.startRange.inRange,
    .flatpickr-day.endRange.inRange {
        background: #4ea1ff !important;
        border-color: #4ea1ff !important;
        box-shadow: 0 2px 8px -2px rgba(78, 161, 255, 0.55);
    }

    .flatpickr-day.today {
        border-color: #93c5fd;
    }

    .flatpickr-day.today:hover {
        background: #eef6ff;
        color: #3580d6;
    }

    .flatpickr-months .flatpickr-prev-month,
    .flatpickr-months .flatpickr-next-month {
        fill: #4ea1ff;
        padding: 0.5rem;
    }

    .flatpickr-months .flatpickr-prev-month:hover svg,
    .flatpickr-months .flatpickr-next-month:hover svg {
        fill: #3580d6;
    }

    .date-range-field {
        display: grid;
        grid-template-columns: 1fr auto 1fr;
        align-items: end;
        gap: 0.5rem;
    }

    .date-range-field__sep {
        color: #9ca3af;
        font-size: 0.875rem;
        padding-bottom: 0.75rem;
        user-select: none;
    }

    @media (max-width: 640px) {
        .date-range-field { grid-template-columns: 1fr; }
        .date-range-field__sep { display: none; }
    }
</style>
<script>
(function () {
    function ensureDateControlWrap(input) {
        if (!input || input.closest('.date-input-control')) return input.closest('.date-input-control');

        const field = input.closest('.date-input-field');
        const control = document.createElement('div');
        control.className = 'date-input-control';

        if (field) {
            const icon = field.querySelector('.date-input-icon');
            field.insertBefore(control, input);
            control.appendChild(input);
            if (icon) control.appendChild(icon);
        } else {
            const wrap = document.createElement('div');
            wrap.className = 'date-input-field';
            input.parentNode.insertBefore(wrap, input);
            wrap.appendChild(control);
            control.appendChild(input);
            const icon = document.createElement('i');
            icon.className = 'bi bi-calendar3 date-input-icon';
            icon.setAttribute('aria-hidden', 'true');
            control.appendChild(icon);
        }

        return control;
    }

    function wrapDateInput(input) {
        if (!input) return;
        if (!input.closest('.date-input-control')) {
            ensureDateControlWrap(input);
        }
        input.classList.add('date-input', 'date-input--ready');
    }

    function getDateField(instance) {
        const el = instance && (instance.input || instance.altInput);
        return el ? el.closest('.date-input-field') : null;
    }

    /**
     * Gắn calendar vào .date-input-field + absolute dưới ô.
     * Cuộn #admin-content → field + calendar đi cùng (không fixed/body).
     */
    function lockCalendarToField(instance) {
        if (!instance || !instance.calendarContainer) return;

        const field = getDateField(instance);
        const cal = instance.calendarContainer;

        if (field) {
            field.classList.toggle('is-fp-open', !!instance.isOpen);
            if (cal.parentNode !== field) {
                field.appendChild(cal);
            }
        }

        cal.classList.add('static');
        cal.style.setProperty('position', 'absolute', 'important');
        cal.style.setProperty('top', 'calc(100% + 4px)', 'important');
        cal.style.setProperty('left', '0', 'important');
        cal.style.setProperty('right', 'auto', 'important');
        cal.style.setProperty('bottom', 'auto', 'important');
        cal.style.setProperty('margin', '0', 'important');
        cal.style.setProperty('transform', 'none', 'important');
        cal.style.setProperty('z-index', '10060', 'important');
        cal.style.width = '';
        // width theo field
        if (field) {
            const w = Math.max(field.getBoundingClientRect().width, 264);
            cal.style.setProperty('width', Math.round(w) + 'px', 'important');
        }
    }

    function clearFieldOpenState(instance) {
        const field = getDateField(instance);
        if (field) field.classList.remove('is-fp-open');
    }

    function initFlatpickrOn(input) {
        if (!input || input._flatpickr) return;
        if (typeof flatpickr === 'undefined') return;

        const control = ensureDateControlWrap(input) || input.closest('.date-input-control');
        const field = input.closest('.date-input-field');
        const floatingCalendar = input.hasAttribute('data-floating-calendar');

        const fp = flatpickr(input, {
            locale: (flatpickr.l10ns && flatpickr.l10ns.vn) ? flatpickr.l10ns.vn : 'vn',
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd/m/Y',
            yearSelectorType: 'dropdown',
            allowInput: true,
            disableMobile: true,
            // static: calendar nằm cạnh input trong DOM — bám khi scroll
            static: !floatingCalendar,
            appendTo: floatingCalendar ? document.body : undefined,
            positionElement: floatingCalendar ? (control || input) : undefined,
            onReady: function (_d, _s, instance) {
                // Flatpickr static tạo .flatpickr-wrapper (inline-block) — ép full width
                const wrappers = [];
                if (instance.element && instance.element.closest) {
                    const w = instance.element.closest('.flatpickr-wrapper');
                    if (w) wrappers.push(w);
                }
                if (instance.altInput && instance.altInput.closest) {
                    const w = instance.altInput.closest('.flatpickr-wrapper');
                    if (w && wrappers.indexOf(w) === -1) wrappers.push(w);
                }
                wrappers.forEach(function (w) {
                    w.style.display = 'block';
                    w.style.width = '100%';
                    w.style.maxWidth = '100%';
                    // Đưa wrapper vào trong .date-input-control nếu bị đẩy ra ngoài
                    if (control && w.parentNode !== control && control.contains(instance.input)) {
                        // keep
                    } else if (control && w.parentNode === field) {
                        const icon = control.querySelector('.date-input-icon');
                        control.insertBefore(w, icon || null);
                    }
                });

                if (instance.altInput) {
                    instance.altInput.classList.add('date-input', 'date-input--ready');
                    instance.altInput.placeholder = 'dd/mm/yyyy';
                    instance.altInput.setAttribute('aria-label', 'Ngày tháng năm');
                    instance.altInput.style.display = 'block';
                    instance.altInput.style.width = '100%';
                    // Đưa altInput vào control (cạnh icon)
                    if (control && instance.altInput.parentNode !== control) {
                        const icon = control.querySelector('.date-input-icon');
                        // Nếu altInput nằm trong flatpickr-wrapper, move cả wrapper
                        const wrap = instance.altInput.closest('.flatpickr-wrapper');
                        if (wrap && wrap.parentNode !== control) {
                            if (icon) control.insertBefore(wrap, icon);
                            else control.appendChild(wrap);
                        } else if (icon) {
                            control.insertBefore(instance.altInput, icon);
                        } else {
                            control.appendChild(instance.altInput);
                        }
                    }
                }
                if (instance.input) {
                    instance.input.classList.add('date-input');
                }
                if (!floatingCalendar) lockCalendarToField(instance);
            },
            onOpen: function (_d, _s, instance) {
                if (!floatingCalendar) {
                    lockCalendarToField(instance);
                    requestAnimationFrame(function () {
                        lockCalendarToField(instance);
                    });
                    return;
                }
                requestAnimationFrame(function(){
                    if (instance.calendarContainer) {
                        instance.calendarContainer.style.setProperty('z-index', '10090', 'important');
                    }
                    if (typeof instance.positionCalendar === 'function') instance.positionCalendar();
                });
            },
            onMonthChange: function (_d, _s, instance) {
                if (floatingCalendar) requestAnimationFrame(function () {
                    if (typeof instance.positionCalendar === 'function') instance.positionCalendar();
                });
            },
            onYearChange: function (_d, _s, instance) {
                if (floatingCalendar) requestAnimationFrame(function () {
                    if (typeof instance.positionCalendar === 'function') instance.positionCalendar();
                });
            },
            onClose: function (_d, _s, instance) {
                if (!floatingCalendar) clearFieldOpenState(instance);
            },
            onDestroy: function (_d, _s, instance) {
                if (!floatingCalendar) clearFieldOpenState(instance);
            },
        });

        input._flatpickr = fp;

        // Helper: set date từ code ngoài (export auto-fill)
        input._setDateValue = function (ymd, fireChange) {
            if (!ymd) {
                if (fp) fp.clear(fireChange !== false);
                else input.value = '';
                return;
            }
            if (fp) fp.setDate(ymd, fireChange !== false, 'Y-m-d');
            else input.value = ymd;
        };
    }

    window.initDateInputs = function (root) {
        const scope = root || document;
        scope.querySelectorAll('input[type="date"]:not([data-native-date])').forEach(function (input) {
            wrapDateInput(input);
            initFlatpickrOn(input);
        });
    };

    /** Set value an toàn khi input có Flatpickr */
    window.setDateInputValue = function (idOrEl, ymd, fireChange) {
        const el = typeof idOrEl === 'string' ? document.getElementById(idOrEl) : idOrEl;
        if (!el) return;
        if (typeof el._setDateValue === 'function') {
            el._setDateValue(ymd, fireChange !== false);
            return;
        }
        if (el._flatpickr) {
            if (!ymd) el._flatpickr.clear(fireChange !== false);
            else el._flatpickr.setDate(ymd, fireChange !== false, 'Y-m-d');
            return;
        }
        el.value = ymd || '';
        if (fireChange !== false) el.dispatchEvent(new Event('change', { bubbles: true }));
    };

    function bootDateInputs() {
        window.initDateInputs(document.getElementById('admin-content') || document);
    }

    if (!window.__dateInputBound) {
        window.__dateInputBound = true;
        document.addEventListener('turbo:load', bootDateInputs);
        document.addEventListener('DOMContentLoaded', bootDateInputs);
        document.addEventListener('turbo:before-cache', function () {
            document.querySelectorAll('input[type="date"]').forEach(function (input) {
                if (input._flatpickr) {
                    try { input._flatpickr.destroy(); } catch (e) { /* ignore */ }
                    delete input._flatpickr;
                }
            });
            document.querySelectorAll('.date-input-field.is-fp-open').forEach(function (f) {
                f.classList.remove('is-fp-open');
            });
        });
        bootDateInputs();
    } else {
        bootDateInputs();
    }
})();
</script>
