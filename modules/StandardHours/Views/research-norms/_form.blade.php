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
                    {{ old('object_type_id', $researchNorm->object_type_id ?? '') == $objectType->id ? 'selected' : '' }}>
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
                    {{ old('year', $researchNorm->year ?? ($currentYear ?? '')) == $year ? 'selected' : '' }}>
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
    <label class="block font-medium mb-2" for="research_hours">
        Số giờ NCKH <span class="text-red-500">*</span>
    </label>
    <input type="number"
           id="research_hours"
           name="research_hours"
           value="{{ old('research_hours', $researchNorm->research_hours ?? '') }}"
           min="0"
           step="0.01"
           class="form-input w-full @error('research_hours') border-red-500 @enderror"
           placeholder="Ví dụ: 600, 300, 150"
           required>
    <p class="text-sm text-gray-500 mt-1">Định mức giờ nghiên cứu khoa học theo đối tượng và năm.</p>
    @error('research_hours')
        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
    @enderror
</div>

<div class="mb-6">
    <label class="block font-medium mb-2">Trạng thái</label>
    <div class="flex gap-4">
        <label class="inline-flex items-center">
            <input type="radio" name="is_active" value="1" class="form-radio"
                {{ old('is_active', ($researchNorm->is_active ?? true) ? '1' : '0') == '1' ? 'checked' : '' }}>
            <span class="ml-2">Đang sử dụng</span>
        </label>
        <label class="inline-flex items-center">
            <input type="radio" name="is_active" value="0" class="form-radio"
                {{ old('is_active', ($researchNorm->is_active ?? true) ? '1' : '0') == '0' ? 'checked' : '' }}>
            <span class="ml-2">Ngừng sử dụng</span>
        </label>
    </div>
    @error('is_active')
        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
    @enderror
</div>