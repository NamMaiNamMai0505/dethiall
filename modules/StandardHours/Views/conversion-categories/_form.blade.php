<div class="mb-6">
    <label class="block font-medium mb-2" for="code">
        Mã danh mục <span class="text-red-500">*</span>
    </label>
    <input type="text" id="code" name="code"
           value="{{ old('code', $conversionCategory->code ?? '') }}"
           class="form-input w-full @error('code') border-red-500 @enderror"
           placeholder="Ví dụ: HD-GD-TIET" required>
    @error('code')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
</div>

<div class="mb-6">
    <label class="block font-medium mb-2" for="name">
        Tên hoạt động <span class="text-red-500">*</span>
    </label>
    <input type="text" id="name" name="name"
           value="{{ old('name', $conversionCategory->name ?? '') }}"
           class="form-input w-full @error('name') border-red-500 @enderror" required>
    @error('name')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
</div>

<div class="mb-6">
    <label class="block font-medium mb-2" for="unit">
        Đơn vị tính <span class="text-red-500">*</span>
    </label>
    <input type="text" id="unit" name="unit"
           value="{{ old('unit', $conversionCategory->unit ?? '') }}"
           class="form-input w-full @error('unit') border-red-500 @enderror"
           placeholder="Ví dụ: tiết, buổi, bài" required>
    @error('unit')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
</div>

<div class="mb-6">
    <label class="block font-medium mb-2">Phương thức quy đổi <span class="text-red-500">*</span></label>
    <div class="flex flex-col sm:flex-row gap-4">
        @foreach($conversionMethods as $value => $label)
            <label class="inline-flex items-center">
                <input type="radio" name="conversion_method" value="{{ $value }}" class="form-radio conversion-method-radio"
                    {{ old('conversion_method', $conversionCategory->conversion_method ?? 'coefficient') == $value ? 'checked' : '' }}>
                <span class="ml-2">{{ $label }}</span>
            </label>
        @endforeach
    </div>
    @error('conversion_method')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
</div>

<div id="coefficient-field" class="mb-6">
    <label class="block font-medium mb-2" for="coefficient">Hệ số quy đổi</label>
    <input type="number" id="coefficient" name="coefficient"
           value="{{ old('coefficient', $conversionCategory->coefficient ?? '') }}"
           min="0" step="0.01"
           class="form-input w-full @error('coefficient') border-red-500 @enderror"
           placeholder="Ví dụ: 1.2">
    <p class="text-sm text-gray-500 mt-1">Giờ quy đổi = Số lượng × Hệ số</p>
    @error('coefficient')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
</div>

<div id="fixed-hours-field" class="mb-6 hidden">
    <label class="block font-medium mb-2" for="fixed_hours">Số giờ cố định</label>
    <input type="number" id="fixed_hours" name="fixed_hours"
           value="{{ old('fixed_hours', $conversionCategory->fixed_hours ?? '') }}"
           min="0" step="0.01"
           class="form-input w-full @error('fixed_hours') border-red-500 @enderror"
           placeholder="Ví dụ: 4, 20">
    <p class="text-sm text-gray-500 mt-1">Giờ quy đổi = Số lượng × Số giờ cố định</p>
    @error('fixed_hours')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
</div>

<div class="mb-6">
    <label class="block font-medium mb-2" for="description">Mô tả</label>
    <textarea id="description" name="description" rows="3"
              class="form-textarea w-full @error('description') border-red-500 @enderror">{{ old('description', $conversionCategory->description ?? '') }}</textarea>
    @error('description')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
</div>

<div class="mb-6">
    <label class="block font-medium mb-2">Trạng thái</label>
    <div class="flex gap-4">
        <label class="inline-flex items-center">
            <input type="radio" name="is_active" value="1" class="form-radio"
                {{ old('is_active', ($conversionCategory->is_active ?? true) ? '1' : '0') == '1' ? 'checked' : '' }}>
            <span class="ml-2">Đang sử dụng</span>
        </label>
        <label class="inline-flex items-center">
            <input type="radio" name="is_active" value="0" class="form-radio"
                {{ old('is_active', ($conversionCategory->is_active ?? true) ? '1' : '0') == '0' ? 'checked' : '' }}>
            <span class="ml-2">Ngừng sử dụng</span>
        </label>
    </div>
</div>

@push('scripts')
<script>
    function toggleConversionFields() {
        const method = document.querySelector('input[name="conversion_method"]:checked')?.value;
        const coeffField = document.getElementById('coefficient-field');
        const fixedField = document.getElementById('fixed-hours-field');
        const coeffInput = document.getElementById('coefficient');
        const fixedInput = document.getElementById('fixed_hours');

        if (method === 'fixed_hours') {
            coeffField.classList.add('hidden');
            fixedField.classList.remove('hidden');
            coeffInput.value = '';
            coeffInput.removeAttribute('required');
            fixedInput.setAttribute('required', 'required');
        } else {
            fixedField.classList.add('hidden');
            coeffField.classList.remove('hidden');
            fixedInput.value = '';
            fixedInput.removeAttribute('required');
            coeffInput.setAttribute('required', 'required');
        }
    }

    document.querySelectorAll('.conversion-method-radio').forEach(radio => {
        radio.addEventListener('change', toggleConversionFields);
    });
    toggleConversionFields();
</script>
@endpush