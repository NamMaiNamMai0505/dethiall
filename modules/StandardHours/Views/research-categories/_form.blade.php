<div class="mb-6">
    <label class="block font-medium mb-2" for="code">Mã danh mục <span class="text-red-500">*</span></label>
    <input type="text" id="code" name="code"
           value="{{ old('code', $researchCategory->code ?? '') }}"
           class="form-input w-full @error('code') border-red-500 @enderror"
           placeholder="Ví dụ: NCKH-DT-CS" required>
    @error('code')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
</div>

<div class="mb-6">
    <label class="block font-medium mb-2" for="name">Tên danh mục <span class="text-red-500">*</span></label>
    <input type="text" id="name" name="name"
           value="{{ old('name', $researchCategory->name ?? '') }}"
           class="form-input w-full @error('name') border-red-500 @enderror"
           placeholder="Ví dụ: Đề tài cấp cơ sở" required>
    @error('name')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
</div>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
    <div>
        <label class="block font-medium mb-2" for="unit">Đơn vị tính</label>
        <input type="text" id="unit" name="unit"
               value="{{ old('unit', $researchCategory->unit ?? '') }}"
               class="form-input w-full @error('unit') border-red-500 @enderror"
               placeholder="VD: Đề tài, Sáng kiến, Bài báo...">
        @error('unit')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
    </div>
    <div>
        <label class="block font-medium mb-2" for="research_hours">Số giờ quy đổi <span class="text-red-500">*</span></label>
        <input type="number" id="research_hours" name="research_hours"
               value="{{ old('research_hours', $researchCategory->research_hours ?? '') }}"
               min="0" step="0.01"
               class="form-input w-full @error('research_hours') border-red-500 @enderror"
               placeholder="Ví dụ: 1200, 300" required>
        <p class="text-sm text-gray-500 mt-1">Tổng giờ quy đổi NCKH theo danh mục (chia cho thành viên khi tính toán).</p>
        @error('research_hours')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
    </div>
</div>

<div class="mb-6">
    <label class="block font-medium mb-2" for="description">Mô tả</label>
    <textarea id="description" name="description" rows="3"
              class="form-textarea w-full @error('description') border-red-500 @enderror">{{ old('description', $researchCategory->description ?? '') }}</textarea>
    @error('description')<span class="text-red-500 text-sm mt-1">{{ $message }}</span>@enderror
</div>

<div class="mb-6">
    <label class="block font-medium mb-2">Trạng thái</label>
    <div class="flex gap-4">
        <label class="inline-flex items-center">
            <input type="radio" name="is_active" value="1" class="form-radio"
                {{ old('is_active', ($researchCategory->is_active ?? true) ? '1' : '0') == '1' ? 'checked' : '' }}>
            <span class="ml-2">Đang sử dụng</span>
        </label>
        <label class="inline-flex items-center">
            <input type="radio" name="is_active" value="0" class="form-radio"
                {{ old('is_active', ($researchCategory->is_active ?? true) ? '1' : '0') == '0' ? 'checked' : '' }}>
            <span class="ml-2">Ngừng sử dụng</span>
        </label>
    </div>
</div>