<div class="mb-6">
    <label class="block font-medium mb-2" for="name">
        Tên chức danh <span class="text-red-500">*</span>
    </label>
    <input type="text"
           id="name"
           name="name"
           value="{{ old('name', $position->name ?? '') }}"
           class="form-input w-full @error('name') border-red-500 @enderror"
           placeholder="Ví dụ: Giảng viên"
           required>
    @error('name')
        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
    @enderror
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div>
        <label class="block font-medium mb-2" for="ratio_percent">
            Tỷ lệ chức danh (%) <span class="text-red-500">*</span>
        </label>
        <input type="number"
               id="ratio_percent"
               name="ratio_percent"
               value="{{ old('ratio_percent', $position->ratio_percent ?? 100) }}"
               min="0"
               max="100"
               step="0.01"
               class="form-input w-full @error('ratio_percent') border-red-500 @enderror"
               required>
        <p class="text-sm text-gray-500 mt-1">Phần trăm định mức giờ chuẩn áp dụng cho chức danh này.</p>
        @error('ratio_percent')
            <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
        @enderror
    </div>

    <div>
        <label class="block font-medium mb-2" for="min_classroom_percent">
            Tỷ lệ tối thiểu đứng lớp (%) <span class="text-red-500">*</span>
        </label>
        <input type="number"
               id="min_classroom_percent"
               name="min_classroom_percent"
               value="{{ old('min_classroom_percent', $position->min_classroom_percent ?? 50) }}"
               min="0"
               max="100"
               step="0.01"
               class="form-input w-full @error('min_classroom_percent') border-red-500 @enderror"
               required>
        <p class="text-sm text-gray-500 mt-1">Tỷ lệ giờ trực tiếp giảng dạy tối thiểu so với định mức.</p>
        @error('min_classroom_percent')
            <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
        @enderror
    </div>
</div>

<div class="mb-6">
    <label class="block font-medium mb-2" for="description">
        Mô tả
    </label>
    <textarea id="description"
              name="description"
              rows="4"
              class="form-textarea w-full @error('description') border-red-500 @enderror"
              placeholder="Mô tả chức danh (nếu có)">{{ old('description', $position->description ?? '') }}</textarea>
    @error('description')
        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
    @enderror
</div>

<div class="mb-6">
    <label class="block font-medium mb-2">Trạng thái</label>
    <div class="flex gap-4">
        <label class="inline-flex items-center">
            <input type="radio"
                   name="is_active"
                   value="1"
                   class="form-radio"
                   {{ old('is_active', ($position->is_active ?? true) ? '1' : '0') == '1' ? 'checked' : '' }}>
            <span class="ml-2">Đang sử dụng</span>
        </label>
        <label class="inline-flex items-center">
            <input type="radio"
                   name="is_active"
                   value="0"
                   class="form-radio"
                   {{ old('is_active', ($position->is_active ?? true) ? '1' : '0') == '0' ? 'checked' : '' }}>
            <span class="ml-2">Ngừng sử dụng</span>
        </label>
    </div>
    @error('is_active')
        <span class="text-red-500 text-sm mt-1">{{ $message }}</span>
    @enderror
</div>