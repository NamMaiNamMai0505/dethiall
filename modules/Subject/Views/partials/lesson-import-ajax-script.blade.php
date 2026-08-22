@once
    @push('scripts')
        <script>
            (function () {
                'use strict';

                if (!window.SubjectLessonImportAjax) {
                    const boundForms = new WeakSet();

                    function notify(type, message) {
                        if (window.Notify && typeof window.Notify[type] === 'function') {
                            window.Notify[type](message);
                            return;
                        }
                        if (window.PortalPopup && typeof window.PortalPopup[type] === 'function') {
                            window.PortalPopup[type](message);
                        }
                    }

                    function setFeedback(form, state, title, message, percent) {
                        const box = form.querySelector('[data-lesson-import-feedback]');
                        if (!box) return;

                        const palette = {
                            loading: {
                                box: 'border-blue-200 bg-blue-50/80',
                                icon: 'bg-blue-100 text-blue-700',
                                text: 'text-blue-900',
                                subtext: 'text-blue-700',
                                bar: 'from-blue-600 to-cyan-500',
                                iconClass: 'bi-arrow-repeat animate-spin',
                            },
                            success: {
                                box: 'border-emerald-200 bg-emerald-50/80',
                                icon: 'bg-emerald-100 text-emerald-700',
                                text: 'text-emerald-900',
                                subtext: 'text-emerald-700',
                                bar: 'from-emerald-600 to-teal-500',
                                iconClass: 'bi-check-lg',
                            },
                            error: {
                                box: 'border-red-200 bg-red-50/80',
                                icon: 'bg-red-100 text-red-700',
                                text: 'text-red-900',
                                subtext: 'text-red-700',
                                bar: 'from-red-600 to-orange-500',
                                iconClass: 'bi-exclamation-triangle',
                            },
                        };
                        const selected = palette[state] || palette.loading;
                        const iconWrap = box.querySelector('[data-import-status-icon]');
                        const icon = iconWrap?.querySelector('i');
                        const titleEl = box.querySelector('[data-import-status-title]');
                        const messageEl = box.querySelector('[data-import-status-message]');
                        const percentEl = box.querySelector('[data-import-status-percent]');
                        const bar = box.querySelector('[data-import-progress]');
                        const safePercent = Math.max(0, Math.min(100, Number(percent) || 0));

                        box.className = 'rounded-xl border p-3.5 ' + selected.box;
                        if (iconWrap) {
                            iconWrap.className = 'mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ' + selected.icon;
                        }
                        if (icon) icon.className = 'bi ' + selected.iconClass;
                        if (titleEl) {
                            titleEl.className = 'text-sm font-semibold ' + selected.text;
                            titleEl.textContent = title;
                        }
                        if (messageEl) {
                            messageEl.className = 'mt-0.5 break-words text-xs leading-5 ' + selected.subtext;
                            messageEl.textContent = message;
                        }
                        if (percentEl) {
                            percentEl.className = 'text-xs font-bold tabular-nums ' + selected.subtext;
                            percentEl.textContent = safePercent + '%';
                        }
                        if (bar) {
                            bar.className = 'h-full rounded-full bg-gradient-to-r transition-[width] duration-300 ease-out ' + selected.bar;
                            bar.style.width = safePercent + '%';
                        }
                    }

                    function setBusy(form, busy) {
                        form.dataset.importRunning = busy ? '1' : '0';
                        const button = form.querySelector('[data-import-submit]');
                        const fileInput = form.querySelector('input[type="file"]');
                        const icon = button?.querySelector('[data-import-submit-icon]');
                        const label = button?.querySelector('[data-import-submit-label]');

                        if (button) {
                            button.disabled = busy;
                            button.setAttribute('aria-busy', busy ? 'true' : 'false');
                            button.classList.toggle('cursor-wait', busy);
                            button.classList.toggle('opacity-75', busy);
                        }
                        if (fileInput) fileInput.disabled = busy;
                        if (icon) {
                            icon.className = busy
                                ? 'bi bi-arrow-repeat animate-spin'
                                : 'bi bi-upload';
                        }
                        if (label) {
                            label.textContent = busy
                                ? 'Đang import...'
                                : (button.dataset.idleLabel || 'Import');
                        }
                    }

                    function renderLessons(lessons) {
                        const container = document.querySelector('[data-subject-lessons-container]');
                        if (!container || !Array.isArray(lessons)) return;

                        container.replaceChildren();
                        if (lessons.length === 0) {
                            const empty = document.createElement('div');
                            empty.className = 'py-6 text-center text-sm text-gray-500';
                            empty.textContent = 'Chưa có bài học.';
                            container.appendChild(empty);
                            return;
                        }

                        const list = document.createElement('div');
                        list.className = 'flex flex-wrap gap-2';
                        lessons.forEach(function (lesson) {
                            const badge = document.createElement('span');
                            badge.className = 'inline-flex items-center rounded-lg border border-slate-200 bg-slate-100 px-3 py-1.5 font-mono text-sm text-slate-800';
                            badge.textContent = lesson.code || '—';
                            badge.title = lesson.name || lesson.code || '';
                            list.appendChild(badge);
                        });

                        const count = document.createElement('p');
                        count.className = 'mt-3 text-xs text-gray-400';
                        count.textContent = lessons.length + ' mã bài học';
                        container.append(list, count);
                    }

                    function validationMessage(data, fallback) {
                        const errors = data && data.errors ? Object.values(data.errors).flat() : [];
                        return errors.length ? errors.join(' ') : (data?.message || fallback);
                    }

                    function createImportId() {
                        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
                            return window.crypto.randomUUID();
                        }

                        return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (char) {
                            const random = Math.floor(Math.random() * 16);
                            const value = char === 'x' ? random : ((random & 0x3) | 0x8);
                            return value.toString(16);
                        });
                    }

                    async function submitImport(form) {
                        if (form.dataset.importRunning === '1') return;

                        const fileInput = form.querySelector('input[type="file"]');
                        if (!fileInput?.files?.length) {
                            const message = 'Vui lòng chọn file Excel trước khi import.';
                            setFeedback(form, 'error', 'Chưa có file', message, 0);
                            notify('error', message);
                            return;
                        }

                        const body = new FormData(form);
                        const importId = createImportId();
                        let progress = 8;
                        let progressTimer = null;
                        let willRedirect = false;

                        setBusy(form, true);
                        setFeedback(form, 'loading', 'Đang tải file', 'Đang gửi và kiểm tra dữ liệu Excel...', progress);
                        progressTimer = window.setInterval(function () {
                            progress = Math.min(88, progress + (progress < 55 ? 9 : 3));
                            setFeedback(
                                form,
                                'loading',
                                progress < 45 ? 'Đang tải file' : 'Đang xử lý dữ liệu',
                                progress < 45
                                    ? 'Đang gửi file Excel lên hệ thống...'
                                    : 'Đang tạo môn học và bài học, vui lòng không đóng trang...',
                                progress
                            );
                        }, 450);

                        try {
                            const response = await fetch(form.action, {
                                method: (form.method || 'POST').toUpperCase(),
                                body,
                                credentials: 'same-origin',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-Import-ID': importId,
                                },
                            });
                            const responseText = await response.text();
                            let data = {};
                            try {
                                data = responseText ? JSON.parse(responseText) : {};
                            } catch (_) {
                                data = {};
                            }

                            if (!response.ok || data.success === false) {
                                const responseImportId = data.import_id
                                    || response.headers.get('X-Import-ID')
                                    || importId;
                                const fallback = response.status === 419
                                    ? 'Phiên đăng nhập đã hết hạn. Hãy tải lại trang và thử lại.'
                                    : `Máy chủ trả về lỗi ${response.status || 500}. Mã import: ${responseImportId}.`;
                                throw new Error(validationMessage(data, fallback));
                            }

                            window.clearInterval(progressTimer);
                            progressTimer = null;
                            setFeedback(form, 'success', 'Import thành công', data.message || 'Dữ liệu đã được cập nhật.', 100);
                            renderLessons(data.lessons);
                            fileInput.value = '';
                            notify('success', data.message || 'Import thành công.');

                            if (data.redirect_url && form.dataset.importStay !== 'true') {
                                willRedirect = true;
                                window.setTimeout(function () {
                                    if (window.Turbo && typeof window.Turbo.visit === 'function') {
                                        window.Turbo.visit(data.redirect_url, { action: 'advance' });
                                    } else {
                                        window.location.assign(data.redirect_url);
                                    }
                                }, 650);
                            }
                        } catch (error) {
                            const message = error?.message || 'Có lỗi xảy ra khi import.';
                            setFeedback(form, 'error', 'Import không thành công', message, 0);
                            notify('error', message);
                        } finally {
                            if (progressTimer) window.clearInterval(progressTimer);
                            if (!willRedirect) setBusy(form, false);
                        }
                    }

                    function bind(scope) {
                        (scope || document).querySelectorAll('[data-lesson-import-form]').forEach(function (form) {
                            if (boundForms.has(form)) return;
                            boundForms.add(form);
                            form.addEventListener('submit', function (event) {
                                event.preventDefault();
                                event.stopPropagation();
                                submitImport(form);
                            });
                        });
                    }

                    window.SubjectLessonImportAjax = { bind };
                    document.addEventListener('turbo:load', function () {
                        window.SubjectLessonImportAjax.bind(document);
                    });
                    document.addEventListener('DOMContentLoaded', function () {
                        window.SubjectLessonImportAjax.bind(document);
                    });
                }

                window.SubjectLessonImportAjax.bind(document);
            })();
        </script>
    @endpush
@endonce
