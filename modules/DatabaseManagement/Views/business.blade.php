@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-[1600px] space-y-5 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div><p class="text-xs font-semibold uppercase tracking-[0.2em] text-emerald-600">Database Management Hub</p><h1 class="mt-1 text-2xl font-bold text-slate-900">Business Relationship Map</h1><p class="mt-1 text-sm text-slate-500">Mapping nghiệp vụ giữa các module. Mọi mapping mới được lưu ở trạng thái đề xuất.</p></div>
        <a href="{{ route('database-management.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Schema Catalog</a>
    </div>
    <div class="grid gap-5 lg:grid-cols-[380px,1fr]">
        <form id="business-map-form" class="space-y-3 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            @csrf
            <h2 class="font-semibold text-slate-900">Đề xuất mapping mới</h2>
            <input name="name" required placeholder="Tên liên kết, ví dụ: Lớp → khóa học LMS" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">
            <select name="module_key" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm">@foreach($modules as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
            <div class="grid grid-cols-2 gap-2"><input name="source_table" required placeholder="Bảng nguồn" class="rounded-xl border border-slate-300 px-3 py-2 text-sm"><input name="source_field" required placeholder="Cột nguồn" class="rounded-xl border border-slate-300 px-3 py-2 text-sm"></div>
            <div class="text-center text-xs font-semibold text-slate-400">LIÊN KẾT NGHIỆP VỤ</div>
            <div class="grid grid-cols-2 gap-2"><input name="target_table" required placeholder="Bảng đích" class="rounded-xl border border-slate-300 px-3 py-2 text-sm"><input name="target_field" required placeholder="Cột đích" class="rounded-xl border border-slate-300 px-3 py-2 text-sm"></div>
            <select name="relationship_type" class="w-full rounded-xl border border-slate-300 px-3 py-2 text-sm"><option value="one_to_many">Một - nhiều</option><option value="one_to_one">Một - một</option><option value="many_to_many">Nhiều - nhiều</option></select>
            <button class="w-full rounded-xl bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Lưu đề xuất</button>
            <p id="business-map-message" class="text-xs text-slate-500"></p>
        </form>
        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 p-5"><h2 class="font-semibold text-slate-900">Mapping đã khai báo</h2><p class="text-xs text-slate-500">Chỉ mapping trạng thái Active mới được module sử dụng.</p></div>
            <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Tên</th><th class="px-4 py-3">Module</th><th class="px-4 py-3">Quan hệ</th><th class="px-4 py-3">Trạng thái</th></tr></thead><tbody class="divide-y divide-slate-100">
            @forelse($maps as $map)<tr><td class="px-4 py-3 font-semibold text-slate-800">{{ $map->name }}<div class="mt-1 text-xs text-slate-500">{{ $map->source_table }}.{{ $map->source_field }} → {{ $map->target_table }}.{{ $map->target_field }}</div></td><td class="px-4 py-3 text-xs">{{ $modules[$map->module_key] ?? $map->module_key }}</td><td class="px-4 py-3 text-xs">{{ str_replace('_', ' ', $map->relationship_type) }}</td><td class="px-4 py-3"><div class="flex flex-wrap items-center gap-2"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $map->status === 'active' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800' }}">{{ $map->status }}</span>@if($map->status === 'proposed')<button type="button" data-activate-url="{{ route('database-management.business.activate', $map) }}" class="activate-business-map rounded-lg border border-emerald-200 px-2.5 py-1 text-xs font-semibold text-emerald-700 hover:bg-emerald-50">Kích hoạt</button>@endif</div></td></tr>@empty<tr><td colspan="4" class="px-4 py-10 text-center text-slate-500">Chưa có mapping nghiệp vụ.</td></tr>@endforelse
            </tbody></table></div>
        </div>
    </div>
</div>
<script>
document.getElementById('business-map-form').addEventListener('submit', async (event) => {
    event.preventDefault(); const form = event.currentTarget; const message = document.getElementById('business-map-message');
    const response = await fetch('{{ route('database-management.business.store') }}', {method:'POST', body:new FormData(form), headers:{'Accept':'application/json','X-Requested-With':'XMLHttpRequest'}});
    const payload = await response.json().catch(() => ({})); message.textContent = payload.message || 'Không thể lưu mapping.'; message.className = response.ok ? 'text-xs text-emerald-700' : 'text-xs text-red-700'; if (response.ok) setTimeout(() => window.location.reload(), 500);
});
document.querySelectorAll('.activate-business-map').forEach((button) => {
    button.addEventListener('click', async () => {
        button.disabled = true;
        const response = await fetch(button.dataset.activateUrl, {method: 'POST', headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''}});
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) { window.Notify?.error(payload.message || 'Không thể kích hoạt mapping.'); button.disabled = false; return; }
        window.Notify?.success(payload.message || 'Đã kích hoạt mapping.'); window.location.reload();
    });
});
</script>
@endsection
