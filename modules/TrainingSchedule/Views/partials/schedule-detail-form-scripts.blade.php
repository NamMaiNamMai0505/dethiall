@php
    $facultyUnitId = $facultyUnitId ?? null;
    $jsSubjectsById = $subjects->mapWithKeys(function ($s) use ($facultyUnitId) {
        $instructors = collect($s->instructors ?? []);
        if ($facultyUnitId) {
            $instructors = $instructors->filter(function ($i) use ($facultyUnitId) {
                return (int) ($i->unit_id ?? 0) === (int) $facultyUnitId
                    || empty($i->unit_id); // giữ nếu chưa gán unit
            })->values();
        }

        return [
            $s->id => $instructors->map(function ($i) {
                return [
                    'id' => $i->id,
                    'name' => $i->name,
                    'code' => $i->code ?? null,
                    'unit_id' => $i->unit_id ?? null,
                ];
            })->values(),
        ];
    });
    $jsLessonsBySubject = $subjects->mapWithKeys(function ($s) {
        $roots = collect($s->lessons ?? [])->filter(fn ($l) => empty($l->parent_id))->values();

        return [
            $s->id => $roots->map(function ($l) {
                $label = method_exists($l, 'getDisplayLabelAttribute')
                    ? $l->display_label
                    : trim(($l->code ? $l->code.': ' : '').($l->name ?? ''));
                $children = collect($l->children ?? [])->map(function ($c) {
                    return [
                        'id' => $c->id,
                        'label' => method_exists($c, 'getDisplayLabelAttribute')
                            ? $c->display_label
                            : trim(($c->code ? $c->code.': ' : '').($c->name ?? '')),
                        'hour_usage' => $c->hour_usage ?? null,
                    ];
                })->values();

                return [
                    'id' => $l->id,
                    'code' => $l->code ?? '',
                    'name' => $l->name ?? '',
                    'label' => $label !== '' ? $label : ('Bài #'.$l->id),
                    'kind' => $l->lesson_kind ?? 'lesson',
                    'is_unit' => ($l->lesson_kind ?? '') === 'unit' || (bool) preg_match('/^unit\s*\d+/iu', (string) ($l->name ?? '')),
                    'children' => $children,
                    'hour_usage' => $l->hour_usage ?? null,
                ];
            })->values(),
        ];
    });
    $jsSubjectAvailability = $subjects->mapWithKeys(function ($s) {
        return [$s->id => $s->availability ?? []];
    });
    $jsAllInstructors = collect($allInstructors ?? [])->map(function ($i) {
        return [
            'id' => $i->id,
            'name' => $i->name,
            'code' => $i->code ?? null,
            'unit_id' => $i->unit_id ?? null,
        ];
    })->values();
@endphp
@push('scripts')
<script>
(function () {
    'use strict';
    // IIFE + chạy ngay: Turbo không fire DOMContentLoaded lại, và không redeclare global const
    const subjectsById = @json($jsSubjectsById);
    const lessonsBySubject = @json($jsLessonsBySubject);
    const subjectAvailability = @json($jsSubjectAvailability);
    const allInstructors = @json($jsAllInstructors);
    const currentMode = @json($mode);
    const isFacultyManager = @json(!empty($isFacultyManager));
    const canManageSkeleton = @json(!empty($canManageSkeleton));
    const isSuperAdmin = @json(!empty($isFullScheduleAccess));
    const facultyUnitId = @json($facultyUnitId);
    // PDOT-only (không phải super): không copy/clear bài+GV; Khoa: không copy/clear khung skeleton
    const roleFaculty = isFacultyManager && !isSuperAdmin;
    const rolePdot = canManageSkeleton && !isFacultyManager && !isSuperAdmin;
    const form = document.querySelector('[data-schedule-detail-form]');
    if (!form) return;

    let isCopying = false;

    function field(period, name) {
        return form.querySelector('[name="details[' + period + '][' + name + ']"]');
    }

    function periodFromElement(el) {
        if (!el) return null;

        const dataPeriod = parseInt(el.getAttribute('data-period') || '', 10);
        if (Number.isInteger(dataPeriod) && dataPeriod >= 1 && dataPeriod <= 9) {
            return dataPeriod;
        }

        const name = el.getAttribute('name') || '';
        const match = name.match(/^details\[(\d+)\]\[/);
        if (!match) return null;

        const period = parseInt(match[1], 10);
        return Number.isInteger(period) && period >= 1 && period <= 9 ? period : null;
    }

    function instructorSelect(period) {
        return form.querySelector('select.instructor-select[data-period="' + period + '"]');
    }

    function lessonSelect(period) {
        return form.querySelector('select.subject-lesson-select[data-period="' + period + '"]')
            || field(period, 'subject_lesson_id');
    }

    function instructorHint(period) {
        return form.querySelector('.instructor-hint[data-period="' + period + '"]');
    }

    /** Đọc value — hỗ trợ Tom Select */
    function getVal(el) {
        if (!el) return '';
        if (el.tomselect) {
            const v = el.tomselect.getValue();
            return Array.isArray(v) ? String(v[0] || '') : String(v || '');
        }
        if (typeof window.getSelectValue === 'function' && el.id) {
            return String(window.getSelectValue(el) || '');
        }
        return String(el.value || '');
    }

    /**
     * Gán value — hỗ trợ Tom Select (setValue + sync UI).
     * silent=true: không fire change (dùng khi copy hàng loạt).
     */
    function setVal(el, value, silent) {
        if (!el) return;
        const v = value == null ? '' : String(value);

        if (el.tomselect) {
            // Đảm bảo option tồn tại trước khi set
            if (v !== '' && !el.querySelector('option[value="' + CSS.escape(v) + '"]')) {
                // Tom Select vẫn set được nếu option có trong tom.options
                if (!el.tomselect.options[v]) {
                    const label = v;
                    el.tomselect.addOption({ value: v, text: label });
                }
            }
            try {
                el.tomselect.setValue(v, silent !== false);
            } catch (e) {
                el.value = v;
            }
            return;
        }

        if (typeof window.setTomValues === 'function' && el.id) {
            window.setTomValues(el, v, silent !== false);
            return;
        }

        el.value = v;
        if (silent === false) {
            el.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    /** Rebuild options cho Tom Select hoặc native */
    function rebuildSelectOptions(el, items, selected, enabled) {
        if (!el) return;
        const list = Array.isArray(items) ? items : [];
        const sel = selected == null ? '' : String(selected);

        if (typeof window.setTomSelectOptions === 'function') {
            window.setTomSelectOptions(el, list, {
                selected: sel,
                enabled: enabled !== false,
            });
            // setTomSelectOptions silent-set; sync dataset
            if (el.classList.contains('instructor-select')) {
                el.dataset.selected = getVal(el) || '';
            }
            return;
        }

        if (el.tomselect && typeof window.destroyTomSelect === 'function') {
            window.destroyTomSelect(el);
        }

        el.innerHTML = '';
        list.forEach(function (item) {
            const opt = document.createElement('option');
            opt.value = item.value == null ? '' : String(item.value);
            opt.textContent = item.text != null ? String(item.text) : opt.value;
            if (sel !== '' && opt.value === sel) opt.selected = true;
            el.appendChild(opt);
        });
        if (sel) el.value = sel;
        if (typeof window.initTomSelects === 'function') {
            window.initTomSelects(el.closest('.ui-select-field') || el.parentElement || document);
        }
        if (el.classList.contains('instructor-select')) {
            el.dataset.selected = el.value || '';
        }
    }

    function unitSelect(period) {
        return form.querySelector('select.unit-lesson-select[data-period="' + period + '"]')
            || field(period, 'unit_lesson_id');
    }

    /**
     * Fill Unit (nếu môn có Unit — Tiếng Anh) + bài học.
     * - Có Unit: chọn Unit → chỉ hiện bài con của Unit đó (có thể chọn luôn Unit nếu không có con).
     * - Không Unit: danh sách bài/chương gốc (bài = chương).
     */
    function fillLessons(period, preferredId, preferredUnitId) {
        const sel = lessonSelect(period);
        const unitSel = unitSelect(period);
        if (!sel) return;

        const subjectId = getVal(field(period, 'subject_id'));
        const keep = preferredId != null && preferredId !== ''
            ? String(preferredId)
            : String(sel.dataset.selected || getVal(sel) || '');
        const keepUnit = preferredUnitId != null && preferredUnitId !== ''
            ? String(preferredUnitId)
            : String((unitSel && (unitSel.dataset.selected || getVal(unitSel))) || '');

        const list = (subjectId && lessonsBySubject[subjectId]) ? lessonsBySubject[subjectId] : [];
        const units = (list || []).filter(function (l) { return l.is_unit || l.kind === 'unit'; });
        const roots = list || [];

        if (unitSel) {
            const unitItems = [{ value: '', text: units.length ? '-- Chọn Unit --' : '-- (không có Unit) --' }];
            let unitValid = false;
            units.forEach(function (u) {
                unitItems.push({ value: String(u.id), text: u.label || u.name || String(u.id) });
                if (keepUnit && String(u.id) === keepUnit) unitValid = true;
            });
            rebuildSelectOptions(unitSel, unitItems, unitValid ? keepUnit : '', true);
            unitSel.dataset.selected = getVal(unitSel) || '';
            unitSel.disabled = units.length === 0;
        }

        const selectedUnit = unitSel ? getVal(unitSel) : '';
        const lessonType = getVal(field(period, 'lesson_type'));
        let lessonItems = [{ value: '', text: subjectId ? '-- Chọn bài học --' : '-- Chọn môn trước --' }];
        let keepValid = false;

        // Bài đã xếp hết giờ (theo Loại tiết đang chọn: Lý thuyết/Thực hành/Thi)
        // sẽ bị ẩn khỏi danh sách — trừ khi đó chính là bài đang được gán ở
        // dòng này (giữ lại để không mất lựa chọn hiện có khi sửa).
        function isExhausted(lesson) {
            if (!lesson.hour_usage || !lesson.hour_usage[lessonType]) return false;
            if (keep && String(lesson.id) === keep) return false;

            return lesson.hour_usage[lessonType].remaining <= 0;
        }

        function pushLesson(lesson, prefix) {
            if (isExhausted(lesson)) return;
            const id = String(lesson.id);
            const text = (prefix ? prefix + ' › ' : '') + (lesson.label || lesson.name || id);
            lessonItems.push({ value: id, text: text });
            if (keep && id === keep) keepValid = true;
        }

        if (selectedUnit) {
            const unit = units.find(function (u) { return String(u.id) === String(selectedUnit); });
            if (unit) {
                // Cho phép gán cả Unit (nếu không chọn bài con)
                pushLesson(unit, '');
                (unit.children || []).forEach(function (c) { pushLesson(c, unit.label || ''); });
            }
        } else if (units.length) {
            // Có Unit nhưng chưa chọn → vẫn liệt kê tất cả bài con + bài gốc không phải unit
            roots.forEach(function (r) {
                if (r.is_unit || r.kind === 'unit') {
                    pushLesson(r, '');
                    (r.children || []).forEach(function (c) { pushLesson(c, r.label || ''); });
                } else {
                    pushLesson(r, '');
                }
            });
        } else {
            roots.forEach(function (r) { pushLesson(r, ''); });
        }

        rebuildSelectOptions(sel, lessonItems, keepValid ? keep : '', true);
        sel.dataset.selected = getVal(sel) || '';
    }

    /**
     * Fill instructor options:
     * - theory / practice / self_study: theo môn (teaching assignment) + lọc unit khoa
     * - final_exam: GV trong phạm vi (khoa / toàn bộ)
     */
    function fillInstructors(period, preferredId) {
        const sel = instructorSelect(period);
        if (!sel) return;

        const subjectId = getVal(field(period, 'subject_id'));
        const lessonType = getVal(field(period, 'lesson_type'));
        const keep = preferredId != null && preferredId !== ''
            ? String(preferredId)
            : String(sel.dataset.selected || getVal(sel) || '');

        const isExam = lessonType === 'final_exam';
        // PDOT: trường GV bị khoá sẵn (chỉ Khoa mới sửa) nên không cần lọc theo môn — vẫn hiện toàn bộ.
        // Super-admin: cũng lọc theo phân công môn như Khoa để nhất quán, trừ tiết thi/kiểm tra.
        // Lưu ý: không dùng canManageSkeleton trực tiếp — nó cũng đúng với Super Admin (scope SYSTEM),
        // phải dùng rolePdot (đã loại trừ Super Admin) để không vô tình bỏ qua bộ lọc cho Super Admin.
        const showAllInstructors = rolePdot || isExam;
        let list = [];
        if (showAllInstructors) {
            list = Array.isArray(allInstructors) ? allInstructors.slice() : [];
        } else if (subjectId && subjectsById[subjectId]) {
            list = subjectsById[subjectId];
        }

        // Khoa: chỉ GV thuộc unit khoa phụ trách
        if (isFacultyManager && facultyUnitId && !isSuperAdmin) {
            list = (list || []).filter(function (ins) {
                if (ins.unit_id == null || ins.unit_id === '') return true;
                return String(ins.unit_id) === String(facultyUnitId);
            });
        }

        const items = [{
            value: '',
            text: isExam
                ? (isFacultyManager && !isSuperAdmin ? '-- Chọn GV khảo thí (trong khoa) --' : '-- Chọn GV khảo thí --')
                : (showAllInstructors
                    ? '-- Chọn giảng viên --'
                    : (subjectId ? '-- Chọn GV theo môn --' : '-- Chọn môn trước --')),
        }];

        let keepValid = false;
        (list || []).forEach(function (ins) {
            const id = String(ins.id);
            const text = ins.code ? (ins.name + ' (' + ins.code + ')') : ins.name;
            items.push({ value: id, text: text, code: ins.code || '', name: ins.name || '' });
            if (keep && id === keep) keepValid = true;
        });

        const selected = keepValid ? keep : '';
        rebuildSelectOptions(sel, items, selected, true);
        sel.dataset.selected = getVal(sel) || '';

        const hint = instructorHint(period);
        if (hint) {
            if (isExam || showAllInstructors) {
                hint.style.display = 'block';
                hint.className = 'instructor-hint text-xs mt-2 leading-4 text-amber-700';
                hint.textContent = (isFacultyManager && !isSuperAdmin
                    ? 'GV trong khoa. '
                    : 'Admin/PDOT: toàn bộ giảng viên. ') +
                    (list.length ? ('Có ' + list.length + ' GV.') : 'Chưa có GV.');
            } else if (subjectId) {
                hint.style.display = 'block';
                hint.className = 'instructor-hint text-xs mt-2 leading-4 text-gray-500';
                hint.textContent = list.length
                    ? ('GV theo phân công môn' + (isFacultyManager ? ' (lọc khoa)' : '') + ' — ' + list.length + ' GV.')
                    : 'Môn này chưa có GV phù hợp (phân công / unit khoa).';
            } else {
                hint.style.display = 'none';
                hint.textContent = '';
            }
        }
    }

    function updateLessonTypeOptions(period) {
        const subjectId = getVal(field(period, 'subject_id'));
        const lessonTypeSelect = field(period, 'lesson_type');
        if (!lessonTypeSelect || !subjectId || !subjectAvailability[subjectId]) return;

        const availability = subjectAvailability[subjectId];
        const current = getVal(lessonTypeSelect);

        Array.from(lessonTypeSelect.options).forEach(function (opt) {
            if (!opt.value || !availability[opt.value]) return;
            const remaining = availability[opt.value].remaining;
            if (remaining <= 0) {
                opt.disabled = (currentMode === 'create');
                if (!opt.text.includes('(Đã xếp hết)')) opt.text += ' (Đã xếp hết)';
                opt.classList.add('text-amber-600');
            } else {
                opt.disabled = false;
                opt.text = opt.text.replace(' (Đã xếp hết)', '');
                opt.classList.remove('text-amber-600');
            }
        });

        // Đồng bộ Tom Select với option disabled/text
        if (lessonTypeSelect.tomselect) {
            try {
                lessonTypeSelect.tomselect.clearOptions();
                Array.from(lessonTypeSelect.options).forEach(function (opt) {
                    lessonTypeSelect.tomselect.addOption({
                        value: opt.value,
                        text: opt.textContent,
                        disabled: !!opt.disabled,
                    });
                });
                lessonTypeSelect.tomselect.refreshOptions(false);
                if (currentMode === 'create' && current && lessonTypeSelect.querySelector('option[value="' + CSS.escape(current) + '"]')?.disabled) {
                    setVal(lessonTypeSelect, '', true);
                    hideStatsRow(period);
                } else {
                    setVal(lessonTypeSelect, current, true);
                }
            } catch (e) {
                if (currentMode === 'create' && current && lessonTypeSelect.selectedOptions[0]?.disabled) {
                    setVal(lessonTypeSelect, '', true);
                    hideStatsRow(period);
                }
            }
        } else if (currentMode === 'create' && current && lessonTypeSelect.selectedOptions[0]?.disabled) {
            setVal(lessonTypeSelect, '', true);
            hideStatsRow(period);
        }
    }

    function hideStatsRow(period) {
        const statsRow = form.querySelector('.stats-row[data-period="' + period + '"]');
        if (statsRow) statsRow.style.display = 'none';
    }

    function countCurrentAddingHours(subjectId, lessonType) {
        let count = 0;
        for (let period = 1; period <= 9; period++) {
            const s = getVal(field(period, 'subject_id'));
            const t = getVal(field(period, 'lesson_type'));
            if (String(s) === String(subjectId) && t === lessonType) count++;
        }
        return count;
    }

    function showStatsRow(period, data) {
        const statsRow = form.querySelector('.stats-row[data-period="' + period + '"]');
        if (!statsRow) return;
        const subjectStats = statsRow.querySelector('.subject-stats');
        const lessonStats = statsRow.querySelector('.lesson-stats');
        if (!subjectStats || !lessonStats) return;

        const subjectId = getVal(field(period, 'subject_id'));
        const lessonType = getVal(field(period, 'lesson_type'));
        const currentAddingHours = countCurrentAddingHours(subjectId, lessonType);
        const remainingAfterAdd = Math.max(0, data.remaining_hours - currentAddingHours);

        let colorClass = 'text-green-600';
        if (data.percentage > 80) colorClass = 'text-red-600';
        else if (data.percentage > 60) colorClass = 'text-yellow-600';

        if (data.total_hours == 0) {
            subjectStats.innerHTML = '<span class="font-medium text-red-500">Môn học này không có tiết ' + (data.type_label || '') + '</span>';
        } else {
            let remainingColorClass = 'text-green-600';
            if (remainingAfterAdd <= 0) remainingColorClass = 'text-red-600';
            else if (remainingAfterAdd <= 2) remainingColorClass = 'text-orange-600';

            subjectStats.innerHTML =
                '<div class="space-y-1">' +
                '<div><span class="font-medium">Đã xếp:</span> ' +
                '<span class="' + colorClass + ' font-semibold">' + data.used_hours + '/' + data.total_hours + '</span> ' +
                '<span class="text-gray-500">tiết ' + (data.type_label || '') + '</span></div>' +
                '<div class="flex gap-4 text-xs">' +
                '<span><span class="font-medium text-blue-600">Đang thêm:</span> <span class="text-blue-600 font-semibold">' + currentAddingHours + ' tiết</span></span>' +
                '<span><span class="font-medium">Còn lại:</span> <span class="' + remainingColorClass + ' font-semibold">' + remainingAfterAdd + ' tiết</span></span>' +
                '</div></div>';
        }

        lessonStats.innerHTML =
            '<div class="flex items-center gap-2">' +
            '<div class="flex-1 bg-gray-200 rounded-full h-2">' +
            '<div class="bg-blue-500 h-2 rounded-full" style="width: ' + data.percentage + '%"></div></div>' +
            '<span class="text-xs ' + colorClass + ' font-medium">' + data.percentage + '%</span></div>';

        statsRow.style.display = 'block';
    }

    function updateHourUsageDisplay(period, subjectId, lessonType) {
        if (!subjectId || !lessonType || !['theory', 'practice', 'self_study', 'final_exam'].includes(lessonType)) {
            hideStatsRow(period);
            return;
        }
        const availability = subjectAvailability[subjectId]?.[lessonType];
        if (!availability) {
            hideStatsRow(period);
            return;
        }
        showStatsRow(period, {
            total_hours: availability.total,
            used_hours: availability.used,
            remaining_hours: availability.remaining,
            percentage: availability.total > 0 ? Math.round((availability.used / availability.total) * 100) : 0,
            type_label: {
                theory: '(Lý thuyết)',
                practice: '(Thực hành)',
                self_study: '(Tự học)',
                final_exam: '(Thi/kiểm tra)'
            }[lessonType],
        });
    }

    function refreshRelatedStats(targetSubjectId, targetLessonType) {
        for (let period = 1; period <= 9; period++) {
            const s = getVal(field(period, 'subject_id'));
            const t = getVal(field(period, 'lesson_type'));
            if (String(s) === String(targetSubjectId) && t === targetLessonType) {
                updateHourUsageDisplay(period, s, t);
            }
        }
    }

    /**
     * Copy theo vai trò:
     * - PDOT: chỉ môn + loại + phòng
     * - Khoa: chỉ bài + GV (cùng môn)
     * - Full: tất cả
     */
    function copyRowDown(from) {
        from = parseInt(from, 10);
        if (!from || from < 1 || from > 9) return;

        const maxPeriod = from <= 5 ? 5 : 9;
        if (from >= maxPeriod) return;

        const src = {
            subject_id: getVal(field(from, 'subject_id')),
            lesson_type: getVal(field(from, 'lesson_type')),
            subject_lesson_id: getVal(lessonSelect(from)),
            instructor_id: getVal(instructorSelect(from)),
            classroom_id: getVal(field(from, 'classroom_id')),
        };

        if (rolePdot) {
            if (!src.subject_id && !src.lesson_type && !src.classroom_id) {
                if (typeof Notify !== 'undefined' && Notify.warning) {
                    Notify.warning('Chưa có khung môn/loại/phòng ở tiết ' + from + ' để copy.');
                }
                return;
            }
        } else if (roleFaculty) {
            if (!src.subject_lesson_id && !src.instructor_id) {
                if (typeof Notify !== 'undefined' && Notify.warning) {
                    Notify.warning('Chưa có bài học/GV ở tiết ' + from + ' để copy.');
                }
                return;
            }
        } else if (!src.subject_id && !src.lesson_type && !src.instructor_id && !src.classroom_id && !src.subject_lesson_id) {
            if (typeof Notify !== 'undefined' && Notify.warning) {
                Notify.warning('Chưa có dữ liệu ở tiết ' + from + ' để copy xuống.');
            }
            return;
        }

        isCopying = true;
        for (let to = from + 1; to <= maxPeriod; to++) {
            if (!roleFaculty) {
                // PDOT / full: copy khung môn · loại · phòng
                setVal(field(to, 'subject_id'), src.subject_id, true);
                setVal(field(to, 'lesson_type'), src.lesson_type, true);
                setVal(field(to, 'classroom_id'), src.classroom_id, true);
                updateLessonTypeOptions(to);
                if (src.lesson_type) setVal(field(to, 'lesson_type'), src.lesson_type, true);
            }
            if (!rolePdot) {
                // Khoa / full: copy bài + GV
                fillLessons(to, src.subject_lesson_id);
                fillInstructors(to, src.instructor_id);
            }

            const sid = getVal(field(to, 'subject_id'));
            const lt = getVal(field(to, 'lesson_type'));
            if (sid && lt) updateHourUsageDisplay(to, sid, lt);
            else hideStatsRow(to);
        }
        isCopying = false;

        const s0 = getVal(field(from, 'subject_id'));
        const t0 = getVal(field(from, 'lesson_type'));
        if (s0 && t0) refreshRelatedStats(s0, t0);

        if (typeof Notify !== 'undefined' && Notify.success) {
            Notify.success(roleFaculty
                ? 'Đã copy bài/GV tiết ' + from + ' xuống các tiết sau.'
                : (rolePdot
                    ? 'Đã copy khung môn/loại/phòng tiết ' + from + ' xuống các tiết sau.'
                    : 'Đã copy tiết ' + from + ' xuống các tiết sau trong buổi.'));
        }
    }

    function clearRow(period) {
        period = parseInt(period, 10);
        const oldSubjectId = getVal(field(period, 'subject_id'));
        const oldLessonType = getVal(field(period, 'lesson_type'));

        if (roleFaculty) {
            // Chỉ clear bài + GV
            setVal(lessonSelect(period), '', true);
            const insLocked = instructorSelect(period);
            if (insLocked) {
                rebuildSelectOptions(insLocked, [{ value: '', text: '-- Chọn GV theo môn --' }], '', true);
                insLocked.dataset.selected = '';
            }
            fillLessons(period, '');
            hideStatsRow(period);
            return;
        }

        if (rolePdot) {
            // Chỉ clear khung skeleton — không đụng bài/GV (hidden vẫn giữ)
            setVal(field(period, 'subject_id'), '', true);
            setVal(field(period, 'lesson_type'), '', true);
            setVal(field(period, 'classroom_id'), '', true);
            hideStatsRow(period);
            if (oldSubjectId && oldLessonType) {
                setTimeout(function () { refreshRelatedStats(oldSubjectId, oldLessonType); }, 50);
            }
            return;
        }

        // Full
        setVal(field(period, 'subject_id'), '', true);
        setVal(field(period, 'lesson_type'), '', true);
        setVal(field(period, 'classroom_id'), '', true);
        setVal(field(period, 'subject_lesson_id'), '', true);

        const ins = instructorSelect(period);
        if (ins) {
            rebuildSelectOptions(ins, [{ value: '', text: '-- Chọn GV theo môn --' }], '', true);
            ins.dataset.selected = '';
        }
        const les = lessonSelect(period);
        if (les) {
            rebuildSelectOptions(les, [{ value: '', text: '-- Chọn môn trước --' }], '', true);
            les.dataset.selected = '';
        }
        hideStatsRow(period);

        if (oldSubjectId && oldLessonType) {
            setTimeout(function () { refreshRelatedStats(oldSubjectId, oldLessonType); }, 50);
        }
    }

    form.querySelectorAll('.copy-row').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const from = parseInt(this.getAttribute('data-from'), 10);
            copyRowDown(from);
            this.classList.add('text-green-600', 'bg-green-50');
            const el = this;
            setTimeout(function () { el.classList.remove('text-green-600', 'bg-green-50'); }, 500);
        });
    });

    form.querySelectorAll('.clear-row').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            clearRow(parseInt(this.getAttribute('data-period'), 10));
            this.classList.add('text-red-600', 'bg-red-50');
            const el = this;
            setTimeout(function () { el.classList.remove('text-red-600', 'bg-red-50'); }, 500);
        });
    });

    form.querySelectorAll('select.subject-select').forEach(function (subjectSelect) {
        const period = periodFromElement(subjectSelect);
        if (!period) return;

        subjectSelect.addEventListener('change', function () {
            if (isCopying) return;
            const ins = instructorSelect(period);
            if (ins) ins.dataset.selected = '';
            const les = lessonSelect(period);
            if (les) les.dataset.selected = '';
            updateLessonTypeOptions(period);
            fillLessons(period, '');
            fillInstructors(period, '');
            const lessonType = getVal(field(period, 'lesson_type'));
            const subjectId = getVal(this);
            if (subjectId && lessonType) {
                updateHourUsageDisplay(period, subjectId, lessonType);
                setTimeout(function () { refreshRelatedStats(subjectId, lessonType); }, 50);
            } else if (!subjectId) {
                hideStatsRow(period);
            }
        });
    });

    form.querySelectorAll('select.lesson-type-select').forEach(function (lessonTypeSelect) {
        lessonTypeSelect.addEventListener('change', function () {
            if (isCopying) return;
            const period = periodFromElement(this);
            if (!period) return;

            const keep = getVal(instructorSelect(period));
            fillInstructors(period, keep);
            const keepLesson = getVal(lessonSelect(period)) || (lessonSelect(period)?.dataset.selected || '');
            fillLessons(period, keepLesson);

            const subjectId = getVal(field(period, 'subject_id'));
            const lessonType = getVal(this);
            if (subjectId && lessonType) {
                updateHourUsageDisplay(period, subjectId, lessonType);
                setTimeout(function () { refreshRelatedStats(subjectId, lessonType); }, 50);
            } else {
                hideStatsRow(period);
            }
        });
    });

    form.querySelectorAll('select.instructor-select').forEach(function (sel) {
        sel.addEventListener('change', function () {
            this.dataset.selected = getVal(this) || '';
        });
    });

    form.querySelectorAll('select.subject-lesson-select').forEach(function (sel) {
        sel.addEventListener('change', function () {
            this.dataset.selected = getVal(this) || '';
        });
    });

    form.querySelectorAll('select.unit-lesson-select').forEach(function (sel) {
        sel.addEventListener('change', function () {
            if (isCopying) return;
            this.dataset.selected = getVal(this) || '';
            const period = periodFromElement(this);
            if (!period) return;

            fillLessons(period, '', getVal(this));
        });
    });

    let initialHydrationDone = false;
    let initialHydrationRetry = null;
    let initialHydrationAttempts = 0;

    function hydrateInitialForm() {
        if (initialHydrationDone) return;
        if (!form.isConnected) return;

        if (typeof window.TomSelect === 'undefined') {
            if (initialHydrationAttempts++ >= 100) return;
            clearTimeout(initialHydrationRetry);
            initialHydrationRetry = setTimeout(hydrateInitialForm, 50);
            return;
        }

        if (typeof window.initTomSelects === 'function') {
            window.initTomSelects(form);
        }

        initialHydrationDone = true;
        for (let period = 1; period <= 9; period++) {
            const subjectId = getVal(field(period, 'subject_id'));
            const lessonType = getVal(field(period, 'lesson_type'));
            const keepIns = getVal(instructorSelect(period));
            const keepLesson = getVal(lessonSelect(period)) || (lessonSelect(period)?.dataset.selected || '');
            if (subjectId) updateLessonTypeOptions(period);
            fillLessons(period, keepLesson);
            fillInstructors(period, keepIns);
            if (subjectId && lessonType) updateHourUsageDisplay(period, subjectId, lessonType);
        }
    }

    window.addEventListener('app:tom-select-ready', hydrateInitialForm, { once: true });
    requestAnimationFrame(hydrateInitialForm);
})();
</script>
@endpush
