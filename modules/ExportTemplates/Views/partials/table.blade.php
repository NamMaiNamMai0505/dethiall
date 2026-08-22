@if(session('success'))
    <div class="mb-3 rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-900">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-3 rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-900">{{ session('error') }}</div>
@endif
<form method="GET" class="mb-4 grid grid-cols-1 md:grid-cols-4 gap-3 rounded-xl border bg-white p-4">
    <div>
        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Tìm kiếm</label>
        <input type="search" name="search" data-live-search="1" value="{{ request('search') }}"
               placeholder="Tên, mã, feature..."
               class="w-full rounded-lg border px-3 py-2 text-sm">
    </div>
    <div>
        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Feature</label>
        <select name="feature_key" class="w-full rounded-lg border px-3 py-2 text-sm">
            <option value="">Tất cả</option>
            @foreach($featureKeys ?? [] as $featureKey)
                <option value="{{ $featureKey }}" @selected(request('feature_key') === $featureKey)>{{ $featureKey }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Định dạng</label>
        <select name="format" class="w-full rounded-lg border px-3 py-2 text-sm">
            <option value="">Word và Excel</option>
            <option value="word" @selected(request('format') === 'word')>Word</option>
            <option value="excel" @selected(request('format') === 'excel')>Excel</option>
        </select>
    </div>
    <div>
        <label class="block text-xs font-semibold uppercase text-slate-500 mb-1">Trạng thái</label>
        <div class="flex gap-2">
            <select name="status" class="min-w-0 flex-1 rounded-lg border px-3 py-2 text-sm">
                <option value="">Tất cả</option>
                <option value="draft" @selected(request('status') === 'draft')>Bản nháp</option>
                <option value="published" @selected(request('status') === 'published')>Đã phát hành</option>
                <option value="invalid" @selected(request('status') === 'invalid')>Không hợp lệ</option>
            </select>
            <button class="rounded-lg bg-slate-800 px-3 py-2 text-sm font-semibold text-white">Lọc</button>
        </div>
    </div>
</form>
<div class="bg-white rounded-xl shadow border overflow-hidden">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-100 text-xs uppercase text-slate-600">
        <tr>
            <th class="px-3 py-2 text-left">Tên</th>
            <th class="px-3 py-2 text-left">Feature</th>
            <th class="px-3 py-2 text-left">Loại</th>
            <th class="px-3 py-2 text-left">Phiên bản</th>
            <th class="px-3 py-2 text-left">Trạng thái</th>
            <th class="px-3 py-2"></th>
        </tr>
        </thead>
        <tbody class="divide-y">
        @forelse($templates as $t)
            <tr class="hover:bg-slate-50">
                <td class="px-3 py-2">
                    <div class="font-medium">{{ $t->name }}</div>
                    <div class="text-xs text-slate-400 font-mono">{{ $t->code }}</div>
                </td>
                <td class="px-3 py-2 font-mono text-xs">{{ $t->feature_key }}</td>
                <td class="px-3 py-2">{{ $t->output_format?->label() ?? '—' }}</td>
                <td class="px-3 py-2">
                    <span class="font-semibold">v{{ $t->latestVersion?->version_number ?? '—' }}</span>
                    <span class="text-xs text-slate-400">({{ $t->versions_count }})</span>
                    @if($t->latestVersion?->manifest)
                        <div class="text-xs text-slate-400">{{ $t->latestVersion->manifest['summary']['target_count'] ?? 0 }} target</div>
                    @endif
                </td>
                <td class="px-3 py-2">
                    @if($t->is_active)
                        <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Active</span>
                    @else
                        <span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-600">{{ $t->status?->label() ?? '—' }}</span>
                    @endif
                </td>
                <td class="px-3 py-2 text-right space-x-2">
                    <a href="{{ route('export-templates.portal.show', ['portal' => $portal, 'exportTemplate' => $t]) }}" class="text-blue-600 font-semibold">Chi tiết</a>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="px-4 py-10 text-center text-slate-500">
                    Chưa có template phù hợp với bộ lọc.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
    <div class="p-3">{{ $templates->links() }}</div>
</div>
@include('partials.live-search')
