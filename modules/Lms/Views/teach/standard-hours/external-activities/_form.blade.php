@php
    $record = $record ?? null;
    $selectedInstructor = old('instructor_id', $record?->instructor_id ?? auth()->user()?->instructor_id);
@endphp

<input type="hidden" name="instructor_id" value="{{ $selectedInstructor }}">
<div class="mb-6 rounded-xl border border-slate-200 bg-slate-50 p-4">
    <span class="text-xs font-semibold uppercase tracking-wide text-slate-500">Người kê khai</span>
    <div class="mt-1 font-semibold text-slate-900">{{ auth()->user()?->instructor?->name ?? auth()->user()?->name }}</div>
    <div class="text-sm text-slate-500">{{ auth()->user()?->instructor?->code ?? auth()->user()?->code }}</div>
</div>

<div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
    <div>
        <label for="activity_type" class="mb-2 block font-medium text-slate-800">
            Nhóm hoạt động <span class="text-rose-500">*</span>
        </label>
        <select id="activity_type" name="activity_type" data-searchable="0" data-placeholder="Chọn nhóm hoạt động"
                class="ui-select-field w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none" required>
            @foreach($activityTypes as $value => $label)
                <option value="{{ $value }}" @selected(old('activity_type', $record?->activity_type ?? 'other') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('activity_type')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="activity_name" class="mb-2 block font-medium text-slate-800">
            Tên hoạt động <span class="text-rose-500">*</span>
        </label>
        <input id="activity_name" name="activity_name" type="text" required maxlength="255"
               value="{{ old('activity_name', $record?->activity_name) }}"
               placeholder="Ví dụ: Tham gia tổ chức hội thao cấp trường"
               class="w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none @error('activity_name') !border-rose-400 @enderror">
        @error('activity_name')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div class="lg:col-span-2">
        <label for="activity_details" class="mb-2 block font-medium text-slate-800">Chi tiết hoạt động</label>
        <textarea id="activity_details" name="activity_details" rows="4"
                  placeholder="Nêu nội dung, nhiệm vụ thực hiện và phạm vi tham gia..."
                  class="w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none">{{ old('activity_details', $record?->activity_details) }}</textarea>
        @error('activity_details')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <x-lms.date-input
            name="from_date"
            label="Từ ngày"
            :value="old('from_date', $record?->from_date?->format('Y-m-d'))"
            required />
    </div>
    <div>
        <x-lms.date-input
            name="to_date"
            label="Đến ngày"
            :value="old('to_date', $record?->to_date?->format('Y-m-d'))" />
        <p class="mt-1 text-xs text-slate-500">Có thể bỏ trống nếu hoạt động chỉ diễn ra trong một ngày.</p>
    </div>

    <div>
        <label for="role_or_position" class="mb-2 block font-medium text-slate-800">Vai trò / chức trách</label>
        <input id="role_or_position" name="role_or_position" type="text" maxlength="255"
               value="{{ old('role_or_position', $record?->role_or_position) }}"
               placeholder="Chủ trì, thành viên, hỗ trợ..."
               class="w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none">
    </div>
    <div>
        <label for="organizer" class="mb-2 block font-medium text-slate-800">Đơn vị tổ chức</label>
        <input id="organizer" name="organizer" type="text" maxlength="255"
               value="{{ old('organizer', $record?->organizer) }}"
               class="w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none">
    </div>

    <div>
        <label for="location" class="mb-2 block font-medium text-slate-800">Địa điểm</label>
        <input id="location" name="location" type="text" maxlength="255"
               value="{{ old('location', $record?->location) }}"
               class="w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none">
    </div>
    <div>
        <label for="result" class="mb-2 block font-medium text-slate-800">Kết quả / sản phẩm</label>
        <textarea id="result" name="result" rows="2"
                  class="w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none">{{ old('result', $record?->result) }}</textarea>
    </div>

    <div class="lg:col-span-2">
        <label for="notes" class="mb-2 block font-medium text-slate-800">Ghi chú</label>
        <textarea id="notes" name="notes" rows="3"
                  class="w-full border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none">{{ old('notes', $record?->notes) }}</textarea>
    </div>

    <div class="lg:col-span-2">
        <label class="mb-2 block font-medium text-slate-800">Minh chứng</label>
        <x-lms.file-drop name="evidence" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
            :current-url="$record?->evidence_url" help="PDF, ảnh, Word hoặc Excel; tối đa 10MB." />
    </div>
</div>

<div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
    <i class="bi bi-info-circle mr-1"></i>
    Hoạt động này được lưu riêng để theo dõi và duyệt, không tự động cộng vào giờ chuẩn HĐ chuyên môn hoặc NCKH.
</div>
