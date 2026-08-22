@php
    /** @var \Modules\StandardHours\Models\AcademicDepartment|null $department */
    $department = $department ?? null;
@endphp

<div class="space-y-5">
    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
            Khoa <span class="text-red-500">*</span>
        </label>
        <div class="ui-select-field">
            <select name="unit_id" required data-searchable="1" data-placeholder="Chọn khoa...">
                <option value="">— Chọn khoa —</option>
                @foreach($units as $u)
                    <option value="{{ $u->id }}"
                        @selected(old('unit_id', $department?->unit_id) == $u->id)>
                        {{ $u->name }}@if($u->code) ({{ $u->code }})@endif
                    </option>
                @endforeach
            </select>
        </div>
        @error('unit_id')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                Mã bộ môn <span class="text-red-500">*</span>
            </label>
            <input type="text" name="code" required maxlength="40"
                   value="{{ old('code', $department?->code) }}"
                   class="w-full border border-slate-200 rounded-lg text-sm px-3 py-2.5 font-mono
                          focus:ring-2 focus:ring-blue-400 focus:border-blue-400"
                   placeholder="VD: BM-GP">
            @error('code')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-1.5">Thứ tự hiển thị</label>
            <input type="number" name="sort_order" min="0"
                   value="{{ old('sort_order', $department?->sort_order ?? 0) }}"
                   class="w-full border border-slate-200 rounded-lg text-sm px-3 py-2.5
                          focus:ring-2 focus:ring-blue-400 focus:border-blue-400">
        </div>
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1.5">
            Tên bộ môn <span class="text-red-500">*</span>
        </label>
        <input type="text" name="name" required maxlength="255"
               value="{{ old('name', $department?->name) }}"
               class="w-full border border-slate-200 rounded-lg text-sm px-3 py-2.5
                      focus:ring-2 focus:ring-blue-400 focus:border-blue-400"
               placeholder="VD: Bộ môn Giải phẫu">
        @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
    </div>

    <div>
        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Mô tả</label>
        <textarea name="description" rows="3"
                  class="w-full border border-slate-200 rounded-lg text-sm px-3 py-2.5
                         focus:ring-2 focus:ring-blue-400 focus:border-blue-400"
                  placeholder="Ghi chú (tuỳ chọn)">{{ old('description', $department?->description) }}</textarea>
    </div>

    <label class="inline-flex items-center gap-2.5 text-sm text-slate-700 cursor-pointer select-none">
        <input type="checkbox" name="is_active" value="1"
               class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
               @checked(old('is_active', $department?->is_active ?? true))>
        <span>Đang sử dụng</span>
    </label>
</div>
