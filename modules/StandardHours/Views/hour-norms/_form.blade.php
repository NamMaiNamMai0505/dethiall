<div class="mb-6">
    <label class="block font-medium mb-2" for="object_type_id">
        Đối tượng <span class="text-red-500">*</span>
    </label>
    <div class="ui-select-field">
        <select name="object_type_id" id="object_type_id"
                data-placeholder="Chọn đối tượng"
                class="w-full @error('object_type_id') border-red-500 @enderror" required>
            <option value="">Chọn đối tượng</option>
            @foreach($objectTypes as $objectType)
                <option value="{{ $objectType->id }}"
                    {{ old('object_type_id', $hourNorm->object_type_id ?? '') == $objectType->id ? 'selected' : '' }}>
                    {{ $objectType->name }}
                </option>
            @endforeach
        </select>
    </div>
    @error('object_type_id')
        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
    @enderror
</div>

<div class="mb-6">
    <label class="block font-medium mb-2" for="position_id">
        Chức danh <span class="text-red-500">*</span>
    </label>
    <div class="ui-select-field">
        <select name="position_id" id="position_id"
                data-placeholder="Chọn chức danh"
                class="w-full @error('position_id') border-red-500 @enderror" required>
            <option value="">Chọn chức danh</option>
            @foreach($positions as $position)
                <option value="{{ $position->id }}"
                    data-ratio="{{ $position->ratio_percent }}"
                    data-min-classroom="{{ $position->min_classroom_percent ?? 50 }}"
                    {{ old('position_id', $hourNorm->position_id ?? '') == $position->id ? 'selected' : '' }}>
                    {{ $position->name }} ({{ number_format($position->ratio_percent, 0) }}%)
                </option>
            @endforeach
        </select>
    </div>
    @error('position_id')
        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
    @enderror
</div>

<div class="mb-6">
    <label class="block font-medium mb-2" for="year">
        Năm <span class="text-red-500">*</span>
    </label>
    <div class="ui-select-field">
        <select name="year" id="year"
                data-placeholder="Chọn năm"
                class="w-full @error('year') border-red-500 @enderror" required>
            <option value="">Chọn năm</option>
            @foreach($years as $year => $label)
                <option value="{{ $year }}"
                    {{ old('year', $hourNorm->year ?? ($currentYear ?? '')) == $year ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
    </div>
    @error('year')
        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
    @enderror
</div>

<div class="mb-6">
    <label class="block font-medium mb-2" for="standard_hours">
        Số giờ chuẩn (cơ sở) <span class="text-red-500">*</span>
    </label>
    <input type="number"
           id="standard_hours"
           name="standard_hours"
           value="{{ old('standard_hours', $hourNorm->standard_hours ?? '') }}"
           min="0"
           step="0.01"
           class="form-input w-full @error('standard_hours') border-red-500 @enderror"
           placeholder="Ví dụ: 280, 380, 430"
           required>
    <p class="text-sm text-gray-500 mt-1">Định mức cơ sở theo đối tượng. Giờ hiệu lực = Số giờ chuẩn × Tỷ lệ chức danh.</p>
    @error('standard_hours')
        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
    @enderror
</div>

<div id="computed-preview" class="mb-6 p-4 bg-blue-50 rounded-lg border border-blue-100 hidden">
    <p class="text-sm text-gray-700">
        <strong>Giờ hiệu lực:</strong> <span id="preview-effective">—</span> giờ
    </p>
    <p class="text-sm text-gray-700 mt-1">
        <strong>Giờ tối thiểu đứng lớp:</strong> <span id="preview-min-classroom">—</span> giờ
    </p>
</div>

<div class="mb-6">
    <label class="block font-medium mb-2">Trạng thái</label>
    <div class="flex gap-4">
        <label class="inline-flex items-center">
            <input type="radio" name="is_active" value="1" class="form-radio"
                {{ old('is_active', ($hourNorm->is_active ?? true) ? '1' : '0') == '1' ? 'checked' : '' }}>
            <span class="ml-2">Đang sử dụng</span>
        </label>
        <label class="inline-flex items-center">
            <input type="radio" name="is_active" value="0" class="form-radio"
                {{ old('is_active', ($hourNorm->is_active ?? true) ? '1' : '0') == '0' ? 'checked' : '' }}>
            <span class="ml-2">Ngừng sử dụng</span>
        </label>
    </div>
    @error('is_active')
        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
    @enderror
</div>

@push('scripts')
<script>
    function updateHourNormPreview() {
        const positionSelect = document.getElementById('position_id');
        const hoursInput = document.getElementById('standard_hours');
        const preview = document.getElementById('computed-preview');
        const effectiveEl = document.getElementById('preview-effective');
        const minEl = document.getElementById('preview-min-classroom');

        const selected = typeof window.getSelectOption === 'function'
            ? window.getSelectOption(positionSelect)
            : positionSelect.options[positionSelect.selectedIndex];
        const hours = parseFloat(hoursInput.value);

        if (!selected?.value || isNaN(hours)) {
            preview.classList.add('hidden');
            return;
        }

        const ratio = parseFloat(selected.dataset.ratio || 100);
        const minClassroom = parseFloat(selected.dataset.minClassroom || 50);
        const effective = Math.round(hours * ratio / 100 * 100) / 100;
        const minHours = Math.round(effective * minClassroom / 100 * 100) / 100;

        effectiveEl.textContent = effective.toLocaleString('vi-VN');
        minEl.textContent = minHours.toLocaleString('vi-VN');
        preview.classList.remove('hidden');
    }

    if (typeof window.onTomChange === 'function') {
        window.onTomChange('position_id', updateHourNormPreview);
    } else {
        document.getElementById('position_id')?.addEventListener('change', updateHourNormPreview);
    }
    document.getElementById('standard_hours')?.addEventListener('input', updateHourNormPreview);
    updateHourNormPreview();
</script>
@endpush