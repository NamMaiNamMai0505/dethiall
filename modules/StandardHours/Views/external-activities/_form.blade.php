@php
    $record = $record ?? null;
    $selectedInstructor = old('instructor_id', $record?->instructor_id ?? auth()->user()?->instructor_id);
@endphp

<div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
    @if($isInstructorView)
        <input type="hidden" name="instructor_id" value="{{ $selectedInstructor }}">
        <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 lg:col-span-2">
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">Người kê khai</p>
            <p class="mt-1 font-semibold text-slate-900">{{ auth()->user()?->instructor?->name ?? auth()->user()?->name }}</p>
            <p class="text-sm text-slate-600">{{ auth()->user()?->instructor?->code ?? auth()->user()?->code }}</p>
        </div>
    @else
        <div>
            <label for="instructor_id" class="mb-2 block text-sm font-medium text-slate-700">
                Giảng viên <span class="text-red-500">*</span>
            </label>
            <select id="instructor_id" name="instructor_id" class="w-full"
                    data-searchable="1" data-placeholder="Chọn giảng viên" required>
                <option value="">Chọn giảng viên</option>
                @foreach($instructors as $instructor)
                    <option value="{{ $instructor->id }}" @selected((string) $selectedInstructor === (string) $instructor->id)>
                        {{ $instructor->name }} ({{ $instructor->code }}){{ $instructor->unit?->name ? ' – '.$instructor->unit->name : '' }}
                    </option>
                @endforeach
            </select>
            @error('instructor_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
        </div>
    @endif

    <div>
        <label for="activity_type" class="mb-2 block text-sm font-medium text-slate-700">
            Nhóm hoạt động <span class="text-red-500">*</span>
        </label>
        <select id="activity_type" name="activity_type" class="w-full"
                data-searchable="0" data-placeholder="Chọn nhóm hoạt động" required>
            @foreach($activityTypes as $value => $label)
                <option value="{{ $value }}" @selected(old('activity_type', $record?->activity_type ?? 'other') === $value)>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @error('activity_type')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="{{ $isInstructorView ? 'lg:col-span-2' : '' }}">
        <label for="activity_name" class="mb-2 block text-sm font-medium text-slate-700">
            Tên hoạt động <span class="text-red-500">*</span>
        </label>
        <input id="activity_name" name="activity_name" type="text" required maxlength="255"
               value="{{ old('activity_name', $record?->activity_name) }}"
               placeholder="Ví dụ: Tham gia tổ chức hội thao cấp trường"
               class="form-input w-full">
        @error('activity_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div class="lg:col-span-2">
        <label for="activity_details" class="mb-2 block text-sm font-medium text-slate-700">Chi tiết hoạt động</label>
        <textarea id="activity_details" name="activity_details" rows="4"
                  placeholder="Nêu nội dung, nhiệm vụ thực hiện và phạm vi tham gia..."
                  class="form-textarea w-full">{{ old('activity_details', $record?->activity_details) }}</textarea>
        @error('activity_details')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>

    <div>
        <x-form.date-input
            name="from_date"
            label="Từ ngày"
            :value="old('from_date', $record?->from_date?->format('Y-m-d'))"
            required />
    </div>
    <div>
        <x-form.date-input
            name="to_date"
            label="Đến ngày"
            :value="old('to_date', $record?->to_date?->format('Y-m-d'))" />
        <p class="mt-1 text-xs text-slate-500">Có thể bỏ trống nếu hoạt động chỉ diễn ra trong một ngày.</p>
    </div>

    <div>
        <label for="role_or_position" class="mb-2 block text-sm font-medium text-slate-700">Vai trò / chức trách</label>
        <input id="role_or_position" name="role_or_position" type="text" maxlength="255"
               value="{{ old('role_or_position', $record?->role_or_position) }}"
               placeholder="Chủ trì, thành viên, hỗ trợ..."
               class="form-input w-full">
    </div>
    <div>
        <label for="organizer" class="mb-2 block text-sm font-medium text-slate-700">Đơn vị tổ chức</label>
        <input id="organizer" name="organizer" type="text" maxlength="255"
               value="{{ old('organizer', $record?->organizer) }}"
               class="form-input w-full">
    </div>

    <div>
        <label for="location" class="mb-2 block text-sm font-medium text-slate-700">Địa điểm</label>
        <input id="location" name="location" type="text" maxlength="255"
               value="{{ old('location', $record?->location) }}"
               class="form-input w-full">
    </div>
    <div>
        <label for="result" class="mb-2 block text-sm font-medium text-slate-700">Kết quả / sản phẩm</label>
        <textarea id="result" name="result" rows="2"
                  class="form-textarea w-full">{{ old('result', $record?->result) }}</textarea>
    </div>

    <div class="lg:col-span-2">
        <label for="notes" class="mb-2 block text-sm font-medium text-slate-700">Ghi chú</label>
        <textarea id="notes" name="notes" rows="3"
                  class="form-textarea w-full">{{ old('notes', $record?->notes) }}</textarea>
    </div>

    <div class="lg:col-span-2">
        <label for="evidence" class="mb-2 block text-sm font-medium text-slate-700">Minh chứng</label>
        @if($record?->evidence_url)
            <a href="{{ $record->evidence_url }}" target="_blank"
               class="mb-2 inline-flex items-center gap-2 text-sm font-medium text-blue-700 hover:underline">
                <i class="bi bi-paperclip"></i> Xem minh chứng hiện tại
            </a>
        @endif
        <input id="evidence" name="evidence" type="file"
               accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx"
               class="block w-full rounded-lg border border-slate-300 bg-white text-sm text-slate-700">
        <p class="mt-1 text-xs text-slate-500">PDF, ảnh, Word hoặc Excel; tối đa 10MB.</p>
        @error('evidence')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
    </div>
</div>

<div class="mt-5 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
    <i class="bi bi-info-circle mr-1"></i>
    Hoạt động này được lưu riêng để theo dõi và duyệt, không tự động cộng vào giờ chuẩn HĐ chuyên môn hoặc NCKH.
</div>
