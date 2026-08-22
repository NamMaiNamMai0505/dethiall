<div class="bg-white rounded-xl shadow border p-6">
    <p class="text-sm text-slate-600 mb-4">
        Phạm vi cố định: <strong>{{ $portalLabel }}</strong>.
        Template mới được lưu dưới dạng bản nháp và chỉ được sử dụng sau khi kích hoạt.
    </p>
    <form method="POST" action="{{ route('export-templates.portal.store', ['portal' => $portal]) }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">Tên mẫu *</label>
            <input type="text" name="name" required class="w-full border rounded-lg px-3 py-2 text-sm" value="{{ old('name') }}">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Feature key *</label>
            <select id="export-template-feature-key" name="feature_key" required
                    class="w-full border rounded-lg px-3 py-2 text-sm font-mono"
                    data-searchable="1" data-placeholder="Chọn feature key">
                <option value="">-- Chọn feature --</option>
                @foreach($featureHints as $h)
                    @php($featureLabel = match ($h) {
                        'lhl.training_plan' => 'Lịch huấn luyện — Slot cố định 1–3 / 4–5 / 6–9',
                        'lhl.training_plan.grouped_periods' => 'Lịch huấn luyện — Chia nhóm tiết',
                        'grades.score_sheet' => 'Quản lý điểm — Bảng điểm',
                        'grades.summary' => 'Quản lý điểm — Bảng tổng hợp',
                        'grades.transcript' => 'Quản lý điểm — Phiếu điểm',
                        default => $h,
                    })
                    <option value="{{ $h }}" @selected(old('feature_key', $featureHints[0] ?? '') === $h)>{{ $featureLabel }} ({{ $h }})</option>
                @endforeach
            </select>
            <p class="text-xs text-slate-500 mt-1">Chỉ chọn feature đã có Data Provider để Preview/Binding hoạt động.</p>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">File *</label>
            <input type="file" name="file" accept=".xlsx,.xls,.xlsm,.xlsb,.docx" required class="w-full text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Ghi chú</label>
            <textarea name="notes" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm">{{ old('notes') }}</textarea>
        </div>
        <button class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700">Tải lên bản nháp</button>
    </form>
</div>
