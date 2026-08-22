@extends('layouts.admin')
@section('title', 'Tải mẫu xuất')
@section('content')
<x-breadcrumb :items="[['title'=>'Trang chủ'],['title'=>'Mẫu xuất','url'=>route('export-templates.index')],['title'=>'Tải lên']]" />
<x-page-header title="TẢI MẪU XUẤT" :actions="[['url'=>route('export-templates.index'),'label'=>'Quay lại','icon'=>'arrow-left','color'=>'gray']]" />

<div class="bg-white rounded-lg shadow border p-6 max-w-2xl">
    <p class="text-sm text-slate-600 mb-4">
        Tải file <strong>xlsx/docx</strong>. Hệ thống quét <code class="bg-slate-100 px-1 rounded">{{'{{bien}}'}}</code>
        và nhãn tiếng Việt (Họ tên, 15 phút, Điểm thi…) để gợi ý map ô.
        Chọn <strong>phạm vi</strong> (Dashboard / LMS / Điểm / Chung) và <strong>feature_key</strong> để biết mẫu dùng chỗ nào.
    </p>
    <form method="POST" action="{{ route('export-templates.store') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">Tên mẫu *</label>
            <input type="text" name="name" required class="w-full border rounded-lg px-3 py-2 text-sm" value="{{ old('name') }}">
        </div>
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium mb-1">Phạm vi *</label>
                <select name="scope" required class="w-full border rounded-lg px-3 py-2 text-sm">
                    <option value="shared">Dùng chung</option>
                    <option value="dashboard">Dashboard</option>
                    <option value="lms">LMS</option>
                    <option value="grades" @selected(request('scope')==='grades')>Quản lý điểm</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium mb-1">Feature key *</label>
                @php($availableFeatures = $featureHints ?? ['lhl.training_plan', 'grades.score_sheet', 'grades.summary', 'grades.transcript'])
                <select id="export-template-feature-key" name="feature_key" required
                        class="w-full border rounded-lg px-3 py-2 text-sm font-mono"
                        data-searchable="1" data-placeholder="Chọn feature key">
                    <option value="">-- Chọn feature --</option>
                    @foreach($availableFeatures as $feature)
                        @php($featureLabel = match ($feature) {
                            'lhl.training_plan' => 'Lịch huấn luyện — Slot cố định 1–3 / 4–5 / 6–9',
                            'lhl.training_plan.grouped_periods' => 'Lịch huấn luyện — Chia nhóm tiết',
                            'grades.score_sheet' => 'Quản lý điểm — Bảng điểm',
                            'grades.summary' => 'Quản lý điểm — Bảng tổng hợp',
                            'grades.transcript' => 'Quản lý điểm — Phiếu điểm',
                            default => $feature,
                        })
                        <option value="{{ $feature }}" @selected(old('feature_key') === $feature)>{{ $featureLabel }} ({{ $feature }})</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">File *</label>
            <input type="file" name="file" accept=".xlsx,.xls,.xlsm,.xlsb,.docx" required class="w-full text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Ghi chú</label>
            <textarea name="notes" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm">{{ old('notes') }}</textarea>
        </div>
        <button class="px-4 py-2 rounded-lg bg-blue-600 text-white text-sm font-semibold">Tải lên & quét</button>
    </form>
</div>
@endsection
