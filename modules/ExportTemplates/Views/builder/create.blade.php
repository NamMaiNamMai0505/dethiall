@extends($portal === 'lms' ? 'layouts.lms-learner' : ($portal === 'grades' ? 'layouts.grades' : 'layouts.admin'))
@section('title', 'Tạo mẫu bằng Builder')
@section('content')
<style>
    #template-builder-create button { transition: transform .2s ease, box-shadow .2s ease, filter .2s ease; }
    #template-builder-create button:hover:not(:disabled) { transform: translateY(-2px); box-shadow: var(--glow-soft, 0 0 0 1px rgba(78,161,255,.22), 0 0 14px rgba(78,161,255,.2)), 0 8px 18px -10px rgba(15,23,42,.45); filter: brightness(1.03); }
    #template-builder-create button:active:not(:disabled) { transform: translateY(0) scale(.98); }
    #template-builder-create button:focus-visible { outline: 2px solid #14b8a6; outline-offset: 2px; }
</style>
<div id="template-builder-create" class="mx-auto max-w-3xl p-4 sm:p-6">
    <div class="mb-5"><h1 class="text-xl font-bold text-slate-900">Tạo mẫu bằng Builder</h1><p class="text-sm text-slate-500">{{ $portalLabel }} · Không cần upload Word/Excel</p></div>
    <form method="POST" data-turbo="false" action="{{ route('export-templates.portal.builder.store', ['portal' => $portal]) }}" class="space-y-4 rounded-xl border bg-white p-5 shadow-sm">
        @csrf
        <label class="block text-sm font-medium">Tên mẫu *<input name="name" required value="{{ old('name') }}" class="mt-1 w-full rounded-lg border px-3 py-2"></label>
        <label class="block text-sm font-medium">Feature *
            <select name="feature_key" required data-searchable="1" data-placeholder="Chọn feature" class="mt-1 w-full rounded-lg border px-3 py-2 font-mono text-sm"><option value="">Chọn feature</option>
                @foreach($featureHints as $feature)
                    @php($featureLabel = match ($feature) {
                        'lhl.training_plan' => 'Lịch huấn luyện — Slot cố định 1–3 / 4–5 / 6–9',
                        'lhl.training_plan.grouped_periods' => 'Lịch huấn luyện — Mẫu chia nhóm tiết',
                        'grades.score_sheet' => 'Quản lý điểm — Bảng điểm',
                        'grades.summary' => 'Quản lý điểm — Bảng tổng hợp',
                        'grades.transcript' => 'Quản lý điểm — Phiếu điểm',
                        default => $feature,
                    })
                    <option value="{{ $feature }}" @selected(old('feature_key') === $feature)>{{ $featureLabel }} ({{ $feature }})</option>
                @endforeach
            </select>
        </label>
        <label class="block text-sm font-medium">Định dạng *
            <select name="output_format" required class="mt-1 w-full rounded-lg border px-3 py-2">
                <option value="excel" @selected(old('output_format', $defaultOutputFormat ?? 'excel') === 'excel')>Excel</option>
                <option value="word" @selected(old('output_format', $defaultOutputFormat ?? 'excel') === 'word')>Word</option>
            </select>
        </label>
        <label class="block text-sm font-medium">Mô tả<textarea name="description" rows="3" class="mt-1 w-full rounded-lg border px-3 py-2">{{ old('description') }}</textarea></label>
        <button class="rounded-lg bg-teal-600 px-4 py-2 text-sm font-bold text-white">Tạo Builder Template</button>
    </form>
</div>
@endsection
