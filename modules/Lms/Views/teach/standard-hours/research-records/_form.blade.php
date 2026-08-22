@php
    $scopedInstructorId = \Modules\StandardHours\Support\InstructorScope::instructorId();
    $scopedInstructor = $scopedInstructorId ? $instructors->firstWhere('id', $scopedInstructorId) : null;
    $participation = old('participation_type', $researchRecord->participation_type ?? \Modules\StandardHours\Models\ResearchRecord::PARTICIPATION_LEAD);
    $memberCount = old('member_count', $researchRecord->member_count ?? 1);
    $durationYears = old('duration_years', $researchRecord->duration_years ?? 1);
    $declaredHours = old('converted_hours', $researchRecord->converted_hours ?? '');
    $calculatedHours = $researchRecord->calculated_hours ?? null;
    $hasManualHours = old('converted_hours') !== null
        || (!empty($researchRecord) && $researchRecord->has_hours_adjustment);
@endphp

<div class="mb-6 rounded-xl border border-indigo-100 bg-gradient-to-r from-indigo-50 to-violet-50 p-4">
    <div class="flex gap-3">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white">
            <i class="bi bi-person-check"></i>
        </span>
        <div>
            <h3 class="font-semibold text-slate-900">Kê khai phần việc của chính bạn</h3>
            <p class="mt-1 text-sm leading-6 text-slate-600">
                Chỉ nhập tổng số người tham gia và vai trò của bạn; không cần kê khai danh sách người khác.
                Hệ thống lấy Luật quy đổi đang áp dụng để gợi ý giờ, sau đó bạn có thể điều chỉnh theo tỷ lệ đóng góp thực tế.
            </p>
        </div>
    </div>
</div>

<input type="hidden" name="instructor_id" value="{{ old('instructor_id', $researchRecord->instructor_id ?? $scopedInstructorId) }}">

<div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Giảng viên kê khai</span>
    <div class="mt-1 font-semibold text-slate-900">
        {{ $scopedInstructor ? $scopedInstructor->name.' ('.$scopedInstructor->code.')' : '—' }}
    </div>
</div>

@include('lms::teach.standard-hours.research-records._norm-card', [
    'norm' => $currentResearchNorm,
])

<div class="mb-6">
    <label class="mb-2 block font-medium text-slate-800" for="research_category_id">
        Loại sản phẩm/hoạt động NCKH <span class="text-rose-500">*</span>
    </label>
    <select name="research_category_id" id="research_category_id"
            data-placeholder="Chọn loại sản phẩm" data-searchable="1"
            class="ui-select-field w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none @error('research_category_id') !border-rose-400 @enderror" required>
        <option value="">Chọn loại sản phẩm</option>
        @foreach($categories as $category)
            <option value="{{ $category->id }}"
                data-hours="{{ (float) $category->research_hours }}"
                {{ old('research_category_id', $researchRecord->research_category_id ?? '') == $category->id ? 'selected' : '' }}>
                {{ $category->code }} — {{ $category->name }}
                ({{ number_format((float) $category->research_hours, 2) }} giờ/sản phẩm)
            </option>
        @endforeach
    </select>
    @error('research_category_id')<span class="mt-1 block text-sm text-rose-600">{{ $message }}</span>@enderror
</div>

<div class="mb-6">
    <label class="mb-2 block font-medium text-slate-800" for="product_name">
        Tên/chi tiết sản phẩm <span class="text-rose-500">*</span>
    </label>
    <input type="text" id="product_name" name="product_name"
           value="{{ old('product_name', $researchRecord->product_name ?? '') }}"
           placeholder="Ví dụ: Giáo trình môn..., bài báo đăng tại..."
           class="w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none @error('product_name') !border-rose-400 @enderror" required>
    @error('product_name')<span class="mt-1 block text-sm text-rose-600">{{ $message }}</span>@enderror
</div>

<div class="mb-6 grid grid-cols-1 gap-5 md:grid-cols-3">
    <div>
        <x-lms.date-input
            name="acceptance_date"
            label="Ngày nghiệm thu/công nhận"
            :value="old('acceptance_date', isset($researchRecord) ? $researchRecord->acceptance_date?->format('Y-m-d') : '')"
            required />
        <p class="mt-1 text-xs text-slate-500">{{ app(\Modules\StandardHours\Services\PeriodService::class)->modeLabel() }} giờ chuẩn được tự lấy từ ngày này.</p>
    </div>
    <div>
        <x-lms.date-input
            name="publication_date"
            label="Ngày công bố"
            :value="old('publication_date', isset($researchRecord) ? $researchRecord->publication_date?->format('Y-m-d') : '')" />
    </div>
    <div>
        <label class="mb-2 block font-medium text-slate-800" for="publication_place">Nơi công bố/xuất bản</label>
        <input type="text" id="publication_place" name="publication_place"
               value="{{ old('publication_place', $researchRecord->publication_place ?? '') }}"
               class="w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none @error('publication_place') !border-rose-400 @enderror">
        @error('publication_place')<span class="mt-1 block text-sm text-rose-600">{{ $message }}</span>@enderror
    </div>
</div>

<div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
    <div class="mb-4 flex items-center gap-2">
        <i class="bi bi-diagram-3 text-indigo-600"></i>
        <h3 class="font-semibold text-slate-900">Thông tin phân chia sản phẩm</h3>
    </div>

    <div class="grid grid-cols-1 gap-5 md:grid-cols-3">
        <div>
            <label class="mb-2 block font-medium text-slate-800" for="member_count">
                Tổng số thành viên tham gia <span class="text-rose-500">*</span>
            </label>
            <input type="number" id="member_count" name="member_count" min="1" max="99" step="1"
                   value="{{ $memberCount }}"
                   class="w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none @error('member_count') !border-rose-400 @enderror" required>
            <p class="mt-1 text-xs text-slate-500">Mặc định là 1, có thể sửa theo sản phẩm thực tế.</p>
            @error('member_count')<span class="mt-1 block text-sm text-rose-600">{{ $message }}</span>@enderror
        </div>

        <div>
            <label class="mb-2 block font-medium text-slate-800" for="participation_type">
                Vai trò/chức danh của bạn <span class="text-rose-500">*</span>
            </label>
            <select name="participation_type" id="participation_type"
                    data-searchable="0"
                    class="ui-select-field w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none" required>
                @foreach($participationTypes as $value => $label)
                    <option value="{{ $value }}" {{ $participation === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <p class="mt-1 text-xs text-slate-500">Chủ nhiệm tính đúng tỷ lệ quy định; thành viên có thể khai tỷ lệ thực tế.</p>
            @error('participation_type')<span class="mt-1 block text-sm text-rose-600">{{ $message }}</span>@enderror
        </div>

        <div>
            <label class="mb-2 block font-medium text-slate-800" for="duration_years">
                Số năm thực hiện <span class="text-rose-500">*</span>
            </label>
            <input type="number" id="duration_years" name="duration_years" min="1" max="20" step="1"
                   value="{{ $durationYears }}"
                   class="w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none @error('duration_years') !border-rose-400 @enderror" required>
            <p class="mt-1 text-xs text-slate-500">Giờ sản phẩm được chia đều cho số năm thực hiện.</p>
            @error('duration_years')<span class="mt-1 block text-sm text-rose-600">{{ $message }}</span>@enderror
        </div>
    </div>

    <div id="contribution-field" class="mt-5 {{ $participation === \Modules\StandardHours\Models\ResearchRecord::PARTICIPATION_MEMBER ? '' : 'hidden' }}">
        <label class="mb-2 block font-medium text-slate-800" for="contribution_percent">
            Tỷ lệ đóng góp thực tế của bạn (%)
        </label>
        <div class="relative max-w-xs">
            <input type="number" id="contribution_percent" name="contribution_percent"
                   min="0" max="100" step="0.01"
                   value="{{ old('contribution_percent', $researchRecord->contribution_percent ?? '') }}"
                   placeholder="Để trống nếu chia theo luật"
                   class="w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 pr-10 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none @error('contribution_percent') !border-rose-400 @enderror">
            <span class="pointer-events-none absolute right-3 top-2.5 text-slate-500">%</span>
        </div>
        <p class="mt-1 text-xs text-slate-500">
            Ví dụ nhập 40 nếu phần đóng góp thực tế của bạn là 40%. Để trống, hệ thống dùng tỷ lệ mặc định trong Luật quy đổi.
        </p>
        @error('contribution_percent')<span class="mt-1 block text-sm text-rose-600">{{ $message }}</span>@enderror
    </div>
</div>

<div id="research-hours-preview" class="mb-6 rounded-xl border border-teal-100 bg-teal-50 p-5">
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Hệ thống tự tính theo Luật quy đổi</p>
            <p id="research-rule-description" class="mt-1 text-sm leading-6 text-slate-700">Chọn loại sản phẩm để xem cách tính.</p>
        </div>
        <div class="shrink-0 text-left sm:text-right">
            <p class="text-xs uppercase tracking-wide text-slate-500">Giờ hệ thống gợi ý</p>
            <p class="text-3xl font-bold text-teal-700">
                <span id="calculated-hours">{{ $calculatedHours !== null ? number_format((float) $calculatedHours, 2, '.', '') : '—' }}</span>
                <span class="text-base font-semibold">giờ</span>
            </p>
        </div>
    </div>

    <div class="mt-4 grid grid-cols-1 gap-2 text-sm sm:grid-cols-3">
        <div class="rounded-lg border border-teal-100 bg-white/80 px-3 py-2">
            <span class="text-slate-500">Giờ của sản phẩm</span>
            <div id="product-hours" class="font-semibold text-slate-900">—</div>
        </div>
        <div class="rounded-lg border border-teal-100 bg-white/80 px-3 py-2">
            <span class="text-slate-500">Bình quân mỗi năm</span>
            <div id="annual-product-hours" class="font-semibold text-slate-900">—</div>
        </div>
        <div class="rounded-lg border border-teal-100 bg-white/80 px-3 py-2">
            <span class="text-slate-500">Tỷ lệ áp dụng cho bạn</span>
            <div id="personal-ratio" class="font-semibold text-slate-900">—</div>
        </div>
    </div>
</div>

<div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4">
    <label class="mb-2 block font-semibold text-slate-900" for="converted_hours">
        Số giờ bạn kê khai <span class="text-rose-500">*</span>
    </label>
    <div class="flex max-w-sm items-center gap-2">
        <input type="number" id="converted_hours" name="converted_hours" min="0" max="1000000" step="0.01"
               value="{{ $declaredHours }}"
               data-manual="{{ $hasManualHours ? '1' : '0' }}"
               class="w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none @error('converted_hours') !border-rose-400 @enderror"
               placeholder="Hệ thống sẽ tự điền">
        <span class="font-medium text-slate-700">giờ</span>
    </div>
    <p class="mt-2 text-sm text-slate-600">
        Hệ thống tự điền theo kết quả phía trên. Thành viên được sửa trực tiếp nếu giờ thực tế phân chia theo tỷ lệ đóng góp khác;
        Chủ nhiệm luôn áp dụng đúng tỷ lệ trong Luật quy đổi.
    </p>
    <div id="manual-hours-notice" class="mt-3 hidden rounded-lg border border-amber-300 bg-white px-3 py-2 text-sm text-amber-800">
        <i class="bi bi-exclamation-triangle mr-1"></i>
        Số giờ kê khai khác kết quả hệ thống gợi ý. Vui lòng ghi ngắn gọn lý do để cấp quản lý dễ đối chiếu.
    </div>
    @error('converted_hours')<span class="mt-1 block text-sm text-rose-600">{{ $message }}</span>@enderror
</div>

<div class="mb-6">
    <label class="mb-2 block font-medium text-slate-800" for="hours_adjustment_note">Giải trình điều chỉnh giờ</label>
    <textarea id="hours_adjustment_note" name="hours_adjustment_note" rows="2"
              placeholder="Ví dụ: Phân chia theo biên bản đóng góp: bản thân tham gia 40%..."
              class="w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none @error('hours_adjustment_note') !border-rose-400 @enderror">{{ old('hours_adjustment_note', $researchRecord->hours_adjustment_note ?? '') }}</textarea>
    @error('hours_adjustment_note')<span class="mt-1 block text-sm text-rose-600">{{ $message }}</span>@enderror
</div>

<div class="mb-6">
    <label class="mb-2 block font-medium text-slate-800" for="notes">Ghi chú chung</label>
    <textarea id="notes" name="notes" rows="3" class="w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none">{{ old('notes', $researchRecord->notes ?? '') }}</textarea>
</div>

<div class="mb-2">
    <label class="mb-2 block font-medium text-slate-800">Minh chứng</label>
    <x-lms.file-drop name="evidence" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"
        :current-url="!empty($researchRecord?->evidence_path) ? $researchRecord->evidence_url : null"
        help="PDF, ảnh hoặc Word. Tối đa 5MB." />
</div>

@push('scripts')
<script>
(function () {
    'use strict';

    const rules = @json($distributionRules);
    const researchNorms = @json($researchNormsByInstructor);
    const scopedInstructorId = @json($scopedInstructorId ? (string) $scopedInstructorId : '');
    const normObject = document.getElementById('research-norm-object');
    const normPeriod = document.getElementById('research-norm-period');
    const normHours = document.getElementById('research-norm-hours');
    const normWarning = document.getElementById('research-norm-warning');
    const category = document.getElementById('research_category_id');
    const memberCount = document.getElementById('member_count');
    const participation = document.getElementById('participation_type');
    const contribution = document.getElementById('contribution_percent');
    const duration = document.getElementById('duration_years');
    const declared = document.getElementById('converted_hours');
    const contributionField = document.getElementById('contribution-field');
    const calculatedOutput = document.getElementById('calculated-hours');
    const productOutput = document.getElementById('product-hours');
    const annualOutput = document.getElementById('annual-product-hours');
    const ratioOutput = document.getElementById('personal-ratio');
    const ruleOutput = document.getElementById('research-rule-description');
    const manualNotice = document.getElementById('manual-hours-notice');
    const adjustmentNote = document.getElementById('hours_adjustment_note');
    let lastSuggested = Number.NaN;

    function formatNorm(value) {
        const parsed = Number(value);
        return Number.isFinite(parsed)
            ? parsed.toLocaleString('vi-VN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
            : '—';
    }

    function updateResearchNorm() {
        const norm = researchNorms?.[scopedInstructorId] || null;
        const hasNorm = norm && norm.research_norm_hours !== null;
        if (normObject) {
            normObject.textContent = hasNorm
                ? [norm.object_type_code, norm.object_type_name].filter(Boolean).join(' — ')
                : 'Chưa gắn đối tượng giờ chuẩn';
        }
        if (normPeriod) {
            normPeriod.textContent = hasNorm
                ? `Áp dụng theo ${String(norm.period_mode_label || 'Năm').toLowerCase()} ${norm.period_label || ''}`
                : 'Định mức lấy từ Đối tượng đang gắn với hồ sơ giảng viên.';
        }
        if (normHours) normHours.textContent = hasNorm ? formatNorm(norm.research_norm_hours) : '—';
        normWarning?.classList.toggle('hidden', !!hasNorm);
    }

    function selectedOption() {
        if (!category) return null;
        if (typeof window.getSelectOption === 'function') return window.getSelectOption(category);
        return category.options[category.selectedIndex] || null;
    }

    function number(value, fallback) {
        const parsed = parseFloat(value);
        return Number.isFinite(parsed) ? parsed : fallback;
    }

    function ratioFor(count, role, contributionValue) {
        if (count <= 1) {
            return number(rules?.single?.lead, 1);
        }

        if (role === 'lead') {
            if (count === 2) return number(rules?.two?.lead, 2 / 3);
            if (count === 3) return number(rules?.three?.lead, 0.5);
            return number(rules?.four_plus?.lead_fixed, 1 / 3);
        }

        if (contributionValue !== null) {
            return Math.min(100, Math.max(0, contributionValue)) / 100;
        }

        if (count === 2) return number(rules?.two?.member, 1 / 3);
        if (count === 3) return number(rules?.three?.member, 0.25);
        return (1 - number(rules?.four_plus?.lead_fixed, 1 / 3)) / Math.max(1, count - 1);
    }

    function format(value) {
        return Number.isFinite(value)
            ? value.toLocaleString('vi-VN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
            : '—';
    }

    function updateManualNotice() {
        const current = number(declared?.value, Number.NaN);
        const canAdjust = participation?.value === 'member';
        const differs = canAdjust
            && Number.isFinite(current)
            && Number.isFinite(lastSuggested)
            && Math.abs(current - lastSuggested) >= 0.01;
        manualNotice?.classList.toggle('hidden', !differs);
    }

    function updatePreview() {
        const option = selectedOption();
        const productHours = number(option?.dataset?.hours, Number.NaN);
        const years = Math.max(1, Math.round(number(duration?.value, 1)));
        const count = Math.max(1, Math.round(number(memberCount?.value, 1)));
        const role = participation?.value || 'lead';
        const contributionValue = role === 'member' && contribution?.value !== ''
            ? number(contribution.value, null)
            : null;
        const ratio = ratioFor(count, role, contributionValue);
        const annualHours = Number.isFinite(productHours) ? productHours / years : Number.NaN;
        const suggested = Number.isFinite(annualHours)
            ? Math.round(annualHours * ratio * 100) / 100
            : Number.NaN;
        const canAdjust = role === 'member';

        contributionField?.classList.toggle('hidden', role !== 'member');
        if (declared) {
            declared.readOnly = !canAdjust;
            declared.classList.toggle('bg-slate-100', !canAdjust);
            declared.classList.toggle('cursor-not-allowed', !canAdjust);
        }
        if (adjustmentNote) {
            adjustmentNote.disabled = !canAdjust;
            adjustmentNote.classList.toggle('bg-slate-100', !canAdjust);
        }
        productOutput.textContent = Number.isFinite(productHours) ? format(productHours) + ' giờ' : '—';
        annualOutput.textContent = Number.isFinite(annualHours) ? format(annualHours) + ' giờ/năm' : '—';
        ratioOutput.textContent = Number.isFinite(ratio) ? format(ratio * 100) + '%' : '—';
        calculatedOutput.textContent = format(suggested);

        if (!Number.isFinite(productHours)) {
            ruleOutput.textContent = 'Chọn loại sản phẩm để xem cách tính.';
        } else if (count === 1) {
            ruleOutput.textContent = 'Sản phẩm có 1 người tham gia: bạn được tính 100% giờ bình quân năm.';
        } else if (role === 'lead') {
            ruleOutput.textContent = 'Vai trò Chủ nhiệm: tỷ lệ được lấy cố định từ Luật quy đổi theo tổng số thành viên.';
        } else if (contributionValue !== null) {
            ruleOutput.textContent = 'Vai trò Thành viên: tính theo tỷ lệ đóng góp thực tế bạn đã nhập.';
        } else {
            ruleOutput.textContent = 'Vai trò Thành viên: chưa nhập tỷ lệ thực tế nên hệ thống dùng tỷ lệ mặc định trong Luật quy đổi.';
        }

        const current = number(declared?.value, Number.NaN);
        const followsPreviousSuggestion = !canAdjust
            || !declared?.dataset.manual
            || declared.dataset.manual === '0'
            || !Number.isFinite(current)
            || (Number.isFinite(lastSuggested) && Math.abs(current - lastSuggested) < 0.01);

        if (declared && Number.isFinite(suggested) && followsPreviousSuggestion) {
            declared.value = suggested.toFixed(2);
            declared.dataset.manual = '0';
        }

        lastSuggested = suggested;
        updateManualNotice();
    }

    [category, memberCount, participation, contribution, duration].forEach(function (input) {
        input?.addEventListener('change', updatePreview);
        if (input !== category && input !== participation) {
            input?.addEventListener('input', updatePreview);
        }
    });

    declared?.addEventListener('input', function () {
        declared.dataset.manual = '1';
        updateManualNotice();
    });

    window.addEventListener('app:tom-select-ready', function () {
        updateResearchNorm();
        updatePreview();
    }, { once: true });
    requestAnimationFrame(function () {
        updateResearchNorm();
        updatePreview();
    });
})();
</script>
@endpush
