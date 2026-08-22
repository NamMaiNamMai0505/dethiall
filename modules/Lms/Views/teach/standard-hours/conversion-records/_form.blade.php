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

<input type="hidden" name="instructor_id" value="{{ old('instructor_id', $conversionRecord->instructor_id ?? $scopedInstructorId) }}">

<div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Giảng viên kê khai</span>
    <div class="mt-1 font-semibold text-slate-900">
        {{ $scopedInstructor ? $scopedInstructor->name.' ('.$scopedInstructor->code.')' : '—' }}
    </div>
</div>

<div class="mb-6">
    <label class="mb-2 block font-medium text-slate-800" for="conversion_category_id">Tên hoạt động chuyên môn <span class="text-rose-500">*</span></label>
    <select name="conversion_category_id" id="conversion_category_id"
            data-placeholder="Chọn danh mục" data-searchable="1"
            class="ui-select-field w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none @error('conversion_category_id') !border-rose-400 @enderror" required>
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
    @error('conversion_category_id')<span class="mt-1 block text-sm text-rose-600">{{ $message }}</span>@enderror
</div>

<div class="mb-6">
    <label class="mb-2 block font-medium text-slate-800" for="activity_name">Chi tiết hoạt động <span class="text-rose-500">*</span></label>
    <input type="text" id="activity_name" name="activity_name"
           value="{{ old('activity_name', $conversionRecord->activity_name ?? '') }}"
           class="w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none @error('activity_name') !border-rose-400 @enderror" required>
    @error('activity_name')<span class="mt-1 block text-sm text-rose-600">{{ $message }}</span>@enderror
</div>

<div class="mb-6 grid grid-cols-1 gap-5 md:grid-cols-3">
    <div>
        <x-lms.date-input
            name="activity_date"
            label="Ngày thực hiện"
            :value="old('activity_date', isset($conversionRecord) ? $conversionRecord->activity_date?->format('Y-m-d') : '')"
            required />
    </div>
    <div>
        <label class="mb-2 block font-medium text-slate-800" for="year">{{ $periodModeLabel }} <span class="text-rose-500">*</span></label>
        <select name="year" id="year"
                data-placeholder="Chọn {{ mb_strtolower($periodModeLabel) }}" data-searchable="0"
                class="ui-select-field w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none @error('year') !border-rose-400 @enderror" required>
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
        <p class="mt-1 text-xs text-slate-500">Tự đồng bộ theo {{ mb_strtolower($periodModeLabel) }} chứa ngày thực hiện.</p>
        @error('year')<span class="mt-1 block text-sm text-rose-600">{{ $message }}</span>@enderror
    </div>
    <div>
        <label class="mb-2 block font-medium text-slate-800" for="quantity">
            Số lượng
            <span id="quantity-unit-label" class="text-slate-500 text-sm font-normal"></span>
            <span class="text-rose-500">*</span>
        </label>
        <input type="number" id="quantity" name="quantity" min="0.01" step="0.01"
               value="{{ old('quantity', $conversionRecord->quantity ?? '') }}"
               class="w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none @error('quantity') !border-rose-400 @enderror" required>
        @error('quantity')<span class="mt-1 block text-sm text-rose-600">{{ $message }}</span>@enderror
    </div>
</div>

<div id="hours-preview" class="mb-6 rounded-xl border border-teal-100 bg-teal-50 p-4 hidden">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="mb-1 text-sm font-semibold text-teal-900">Giờ chuẩn quy đổi</p>
            <p class="text-sm text-slate-700" id="preview-formula">—</p>
        </div>
        <div class="text-right">
            <p class="text-xs uppercase tracking-wide text-slate-500">Kết quả</p>
            <p class="text-2xl font-bold text-teal-700">
                <span id="preview-hours">—</span>
                <span class="text-base font-semibold">giờ</span>
            </p>
        </div>
    </div>
    <div class="mt-3 grid grid-cols-1 gap-2 text-sm sm:grid-cols-3">
        <div class="rounded-lg border border-teal-100 bg-white/70 px-3 py-2">
            <span class="text-slate-500">Đơn vị</span>
            <div class="font-medium text-slate-900" id="preview-unit">—</div>
        </div>
        <div class="rounded-lg border border-teal-100 bg-white/70 px-3 py-2">
            <span class="text-slate-500">Hệ số / giờ cố định</span>
            <div class="font-medium text-slate-900" id="preview-rate">—</div>
        </div>
        <div class="rounded-lg border border-teal-100 bg-white/70 px-3 py-2">
            <span class="text-slate-500">Số lượng</span>
            <div class="font-medium text-slate-900" id="preview-qty">—</div>
        </div>
    </div>
</div>

<div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-2">
    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:border-teal-300 hover:bg-teal-50">
        <input type="hidden" name="has_other_remuneration" value="0">
        <input type="checkbox" name="has_other_remuneration" value="1"
               class="mt-1 rounded border-slate-300 text-teal-700"
               {{ old('has_other_remuneration', $conversionRecord->has_other_remuneration ?? false) ? 'checked' : '' }}>
        <span>
            <strong class="block text-sm text-slate-900">Đã có chế độ thù lao riêng</strong>
            <span class="mt-1 block text-xs leading-5 text-slate-600">Vẫn ghi nhận vào kết quả giờ chuẩn, nhưng không đưa vào quỹ giờ vượt định mức.</span>
        </span>
    </label>
    <label class="flex cursor-pointer items-start gap-3 rounded-xl border border-slate-200 bg-slate-50 p-4 transition hover:border-teal-300 hover:bg-teal-50">
        <input type="hidden" name="is_external_invitation" value="0">
        <input type="checkbox" name="is_external_invitation" value="1"
               class="mt-1 rounded border-slate-300 text-teal-700"
               {{ old('is_external_invitation', $conversionRecord->is_external_invitation ?? false) ? 'checked' : '' }}>
        <span>
            <strong class="block text-sm text-slate-900">Mời giảng ngoài nhà trường</strong>
            <span class="mt-1 block text-xs leading-5 text-slate-600">Theo Thông tư nội bộ, hoạt động này không cộng vào giờ chuẩn của giảng viên nhà trường.</span>
        </span>
    </label>
</div>

<div class="mb-6">
    <label class="mb-2 block font-medium text-slate-800" for="notes">Ghi chú</label>
    <textarea id="notes" name="notes" rows="3" class="w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none">{{ old('notes', $conversionRecord->notes ?? '') }}</textarea>
</div>

<div class="mb-6">
    <label class="mb-2 block font-medium text-slate-800">Minh chứng</label>
    <x-lms.file-drop name="evidence" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
        :current-url="!empty($conversionRecord?->evidence_path) ? $conversionRecord->evidence_url : null"
        help="PDF, ảnh hoặc Word. Tối đa 5MB." />
</div>

@push('scripts')
<script>
(function () {
    const boundForms = window.__lmsConversionPreviewForms
        || (window.__lmsConversionPreviewForms = new WeakSet());

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
    if (document.readyState !== 'loading') {
        bindPreview();
    }
})();
</script>
@endpush
