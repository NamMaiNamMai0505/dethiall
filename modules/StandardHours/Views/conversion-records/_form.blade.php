@php
    $scopedInstructorId = \Modules\StandardHours\Support\InstructorScope::instructorId();
    $scopedInstructor = $scopedInstructorId ? $instructors->firstWhere('id', $scopedInstructorId) : null;
    $years = $years ?? [];
    $defaultYear = old(
        'year',
        $conversionRecord->year
            ?? (array_key_first($years) ?: null)
    );
    $periodService = app(\Modules\StandardHours\Services\PeriodService::class);
    $periodModeLabel = $periodService->modeLabel();
@endphp

<x-form.instructor-select
    name="instructor_id"
    label="Giảng viên"
    :instructors="$instructors"
    :value="old('instructor_id', $conversionRecord->instructor_id ?? null)"
    :required="true"
    :readonly="(bool) $scopedInstructorId"
    :readonly-label="$scopedInstructor ? $scopedInstructor->name.' ('.$scopedInstructor->code.')' : null"
    class="mb-6"
/>

<div class="mb-6">
    <label class="block font-medium mb-2" for="conversion_category_id">Tên hoạt động chuyên môn <span class="text-red-500">*</span></label>
    <div class="ui-select-field">
        <select name="conversion_category_id" id="conversion_category_id"
                data-placeholder="Chọn danh mục"
                data-searchable="1"
                class="w-full @error('conversion_category_id') border-red-500 @enderror" required>
            <option value="">Chọn danh mục</option>
            @foreach($categories as $category)
                <option value="{{ $category->id }}"
                    data-method="{{ $category->conversion_method }}"
                    data-coefficient="{{ $category->coefficient !== null ? (float) $category->coefficient : '' }}"
                    data-fixed-hours="{{ $category->fixed_hours !== null ? (float) $category->fixed_hours : '' }}"
                    data-unit="{{ $category->unit }}"
                    {{ old('conversion_category_id', $conversionRecord->conversion_category_id ?? '') == $category->id ? 'selected' : '' }}>
                    {{ $category->code }} — {{ $category->name }}
                    @if($category->conversion_method === 'coefficient')
                        (HS {{ number_format((float) $category->coefficient, 2) }} / {{ $category->unit }})
                    @else
                        ({{ number_format((float) $category->fixed_hours, 2) }} giờ / {{ $category->unit }})
                    @endif
                </option>
            @endforeach
        </select>
    </div>
    @error('conversion_category_id')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
</div>

<div class="mb-6">
    <label class="block font-medium mb-2" for="activity_name">Chi tiết hoạt động <span class="text-red-500">*</span></label>
    <input type="text" id="activity_name" name="activity_name"
           value="{{ old('activity_name', $conversionRecord->activity_name ?? '') }}"
           class="form-input w-full @error('activity_name') border-red-500 @enderror" required>
    @error('activity_name')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
    <div>
        <x-form.date-input
            name="activity_date"
            label="Ngày thực hiện"
            :value="old('activity_date', isset($conversionRecord) ? $conversionRecord->activity_date?->format('Y-m-d') : '')"
            required />
    </div>
    <div>
        <label class="block font-medium mb-2" for="year">{{ $periodModeLabel }} <span class="text-red-500">*</span></label>
        <div class="ui-select-field">
            <select name="year" id="year"
                    data-placeholder="Chọn {{ mb_strtolower($periodModeLabel) }}"
                    data-searchable="0"
                    class="w-full @error('year') border-red-500 @enderror" required>
                <option value="">Chọn {{ mb_strtolower($periodModeLabel) }}</option>
                @foreach($years as $yearKey => $yearLabel)
                    @php([$periodStart, $periodEnd] = $periodService->dateRange($yearKey))
                    <option value="{{ $yearKey }}"
                            data-period-start="{{ $periodStart }}"
                            data-period-end="{{ $periodEnd }}"
                            {{ (string) $defaultYear === (string) $yearKey ? 'selected' : '' }}>
                        {{ $yearLabel }}
                    </option>
                @endforeach
            </select>
        </div>
        <p class="text-xs text-gray-500 mt-1">Tự đồng bộ theo {{ mb_strtolower($periodModeLabel) }} chứa ngày thực hiện.</p>
        @error('year')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
    </div>
    <div>
        <label class="block font-medium mb-2" for="quantity">
            Số lượng
            <span id="quantity-unit-label" class="text-gray-500 text-sm font-normal"></span>
            <span class="text-red-500">*</span>
        </label>
        <input type="number" id="quantity" name="quantity" min="0.01" step="0.01"
               value="{{ old('quantity', $conversionRecord->quantity ?? '') }}"
               class="form-input w-full @error('quantity') border-red-500 @enderror" required>
        @error('quantity')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
    </div>
</div>

<div id="hours-preview" class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-100 hidden">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm font-semibold text-blue-900 mb-1">Giờ chuẩn quy đổi</p>
            <p class="text-sm text-gray-700" id="preview-formula">—</p>
        </div>
        <div class="text-right">
            <p class="text-xs text-gray-500 uppercase tracking-wide">Kết quả</p>
            <p class="text-2xl font-bold text-blue-700">
                <span id="preview-hours">—</span>
                <span class="text-base font-semibold">giờ</span>
            </p>
        </div>
    </div>
    <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-2 text-sm">
        <div class="rounded-md bg-white/70 border border-blue-100 px-3 py-2">
            <span class="text-gray-500">Đơn vị</span>
            <div class="font-medium text-gray-900" id="preview-unit">—</div>
        </div>
        <div class="rounded-md bg-white/70 border border-blue-100 px-3 py-2">
            <span class="text-gray-500">Hệ số / giờ cố định</span>
            <div class="font-medium text-gray-900" id="preview-rate">—</div>
        </div>
        <div class="rounded-md bg-white/70 border border-blue-100 px-3 py-2">
            <span class="text-gray-500">Số lượng</span>
            <div class="font-medium text-gray-900" id="preview-qty">—</div>
        </div>
    </div>
</div>

<div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:border-blue-300 hover:bg-blue-50">
        <input type="hidden" name="has_other_remuneration" value="0">
        <input type="checkbox" name="has_other_remuneration" value="1"
               class="mt-1 rounded border-slate-300 text-blue-700"
               {{ old('has_other_remuneration', $conversionRecord->has_other_remuneration ?? false) ? 'checked' : '' }}>
        <span>
            <strong class="block text-sm text-slate-900">Đã có chế độ thù lao riêng</strong>
            <span class="mt-1 block text-xs leading-5 text-slate-600">Vẫn ghi nhận vào kết quả giờ chuẩn, nhưng không đưa vào quỹ giờ vượt định mức.</span>
        </span>
    </label>
    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:border-blue-300 hover:bg-blue-50">
        <input type="hidden" name="is_external_invitation" value="0">
        <input type="checkbox" name="is_external_invitation" value="1"
               class="mt-1 rounded border-slate-300 text-blue-700"
               {{ old('is_external_invitation', $conversionRecord->is_external_invitation ?? false) ? 'checked' : '' }}>
        <span>
            <strong class="block text-sm text-slate-900">Mời giảng ngoài nhà trường</strong>
            <span class="mt-1 block text-xs leading-5 text-slate-600">Theo Thông tư nội bộ, hoạt động này không cộng vào giờ chuẩn của giảng viên nhà trường.</span>
        </span>
    </label>
</div>

<div class="mb-6">
    <label class="block font-medium mb-2" for="notes">Ghi chú</label>
    <textarea id="notes" name="notes" rows="3" class="form-textarea w-full">{{ old('notes', $conversionRecord->notes ?? '') }}</textarea>
</div>

<div class="mb-6">
    <label class="block font-medium mb-2" for="evidence">Minh chứng</label>
    @if(!empty($conversionRecord?->evidence_path))
        <p class="text-sm text-gray-600 mb-2">
            File hiện tại: <a href="{{ $conversionRecord->evidence_url }}" target="_blank" class="text-blue-600 hover:underline">Xem minh chứng</a>
        </p>
    @endif
    <input type="file" id="evidence" name="evidence" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
           data-placeholder="Chưa chọn minh chứng"
           class="w-full @error('evidence') border-red-500 @enderror">
    <p class="text-sm text-gray-500 mt-1">PDF, ảnh hoặc Word. Tối đa 5MB.</p>
    @error('evidence')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
</div>

@push('scripts')
<script>
(function () {
    const boundForms = window.__standardHoursConversionPreviewForms
        || (window.__standardHoursConversionPreviewForms = new WeakSet());

    function selectedCategoryOption(form) {
        const el = form?.querySelector('#conversion_category_id');
        if (!el) return null;
        if (typeof window.getSelectOption === 'function') {
            return window.getSelectOption(el);
        }
        return el.options[el.selectedIndex] || null;
    }

    function attr(option, name) {
        if (!option) return '';
        return option.getAttribute('data-' + name)
            || option.dataset[name.replace(/-([a-z])/g, function (_, c) { return c.toUpperCase(); })]
            || '';
    }

    function yearFromDate(form, dateStr) {
        if (!dateStr) return null;
        const yearEl = form?.querySelector('#year');
        const matched = Array.from(yearEl?.options || []).find(function (option) {
            const start = option.getAttribute('data-period-start');
            const end = option.getAttribute('data-period-end');
            return start && end && dateStr >= start && dateStr <= end;
        });
        if (matched) return matched.value;
        const d = new Date(dateStr + 'T00:00:00');
        if (Number.isNaN(d.getTime())) return null;
        return String(d.getFullYear());
    }

    function setYearIfExists(form, year) {
        if (!year) return;
        const el = form?.querySelector('#year');
        if (!el) return;
        const hasOption = Array.from(el.options).some(function (o) { return o.value === year; });
        if (!hasOption) {
            const opt = document.createElement('option');
            opt.value = year;
            opt.textContent = year;
            el.appendChild(opt);
            if (el.tomselect) {
                el.tomselect.addOption({ value: year, text: year });
            }
        }
        if (typeof window.setTomValues === 'function') {
            window.setTomValues(el, year, true);
        } else {
            el.value = year;
        }
    }

    function updateConversionPreview(form) {
        if (!form?.isConnected) return;

        const quantityEl = form.querySelector('#quantity');
        const preview = form.querySelector('#hours-preview');
        const hoursEl = form.querySelector('#preview-hours');
        const formulaEl = form.querySelector('#preview-formula');
        const unitLabel = form.querySelector('#quantity-unit-label');
        const unitBox = form.querySelector('#preview-unit');
        const rateBox = form.querySelector('#preview-rate');
        const qtyBox = form.querySelector('#preview-qty');
        const selected = selectedCategoryOption(form);
        const quantity = parseFloat(quantityEl?.value);

        if (!preview || !hoursEl || !formulaEl || !unitLabel || !unitBox || !rateBox || !qtyBox) {
            return;
        }

        if (!selected || !selected.value) {
            preview.classList.add('hidden');
            unitLabel.textContent = '';
            return;
        }

        const unit = attr(selected, 'unit') || '';
        const method = attr(selected, 'method') || 'coefficient';
        const coefficient = parseFloat(attr(selected, 'coefficient') || '0');
        const fixedHours = parseFloat(attr(selected, 'fixed-hours') || '0');
        const rate = method === 'coefficient' ? coefficient : fixedHours;
        const rateLabel = method === 'coefficient'
            ? ('Hệ số ' + (Number.isFinite(rate) ? rate : 0))
            : ((Number.isFinite(rate) ? rate : 0) + ' giờ cố định');

        unitLabel.textContent = unit ? '(' + unit + ')' : '';
        unitBox.textContent = unit || '—';
        rateBox.textContent = rateLabel;
        qtyBox.textContent = Number.isFinite(quantity) ? quantity : '—';

        if (!Number.isFinite(quantity) || quantity <= 0 || !Number.isFinite(rate)) {
            hoursEl.textContent = '—';
            formulaEl.textContent = method === 'coefficient'
                ? 'Công thức: Số lượng × Hệ số quy đổi'
                : 'Công thức: Số lượng × Giờ cố định';
            preview.classList.remove('hidden');
            return;
        }

        const hours = Math.round(quantity * rate * 100) / 100;
        hoursEl.textContent = hours;
        formulaEl.textContent = method === 'coefficient'
            ? (quantity + ' ' + (unit || '') + ' × hệ số ' + rate + ' = ' + hours + ' giờ chuẩn')
            : (quantity + ' ' + (unit || '') + ' × ' + rate + ' giờ = ' + hours + ' giờ chuẩn');
        preview.classList.remove('hidden');
    }

    function bindPreview() {
        const form = document.querySelector('[data-conversion-record-form]');
        if (!form || boundForms.has(form)) return;
        boundForms.add(form);

        const update = function () { updateConversionPreview(form); };
        const category = form.querySelector('#conversion_category_id');
        category?.addEventListener('change', update);

        form.querySelector('#quantity')?.addEventListener('input', update);
        form.querySelector('#quantity')?.addEventListener('change', update);

        const dateInput = form.querySelector('#activity_date');
        if (dateInput) {
            dateInput.addEventListener('change', function () {
                // Only auto-fill when year empty or still matching previous suggestion.
                const year = yearFromDate(form, dateInput.value);
                const yearEl = form.querySelector('#year');
                const current = typeof window.getSelectValue === 'function'
                    ? window.getSelectValue(yearEl)
                    : (yearEl?.value || '');
                if (!current || current === dateInput.dataset.lastSuggestedYear) {
                    setYearIfExists(form, year);
                    dateInput.dataset.lastSuggestedYear = year || '';
                }
            });
        }

        update();
    }

    document.addEventListener('DOMContentLoaded', bindPreview);
    document.addEventListener('turbo:load', bindPreview);
    // In case scripts run after DOM ready
    if (document.readyState !== 'loading') {
        bindPreview();
    }
})();
</script>
@endpush
