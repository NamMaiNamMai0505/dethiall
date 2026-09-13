@php
    $rows = $assets->getCollection();
    $totalInitial = $rows->sum(fn ($asset) => (float) $asset->initial_value);
    $totalRemaining = $rows->sum(fn ($asset) => (float) $asset->remaining_value);
    $canEditPublicAssets = auth()->user()?->isSuperAdmin() || \App\Support\PermissionCheck::can(auth()->user(), 'inventory.assets.edit');
    $hasFilters = request()->filled('search') || request()->filled('classroom_id') || request()->filled('status') || request('year', now()->year) != now()->year;
@endphp
<div class="space-y-5">
    <form method="GET" id="public-assets-filter" class="grid gap-3 rounded-lg border bg-white p-4 md:grid-cols-5">
        <label class="text-sm font-semibold text-slate-700 md:col-span-2">Tìm kiếm
            <input name="search" value="{{ request('search') }}" list="public-asset-suggestions" autocomplete="off" placeholder="Mã, tên tài sản hoặc đơn vị cung cấp" class="mt-1 w-full rounded border px-3 py-2">
            <datalist id="public-asset-suggestions">
                @foreach(($filterSuggestions ?? collect()) as $suggestion)
                    <option value="{{ $suggestion }}"></option>
                @endforeach
            </datalist>
        </label>
        <label class="text-sm font-semibold text-slate-700">Năm thống kê
            <input name="year" type="number" min="1900" max="2200" value="{{ $reportYear }}" class="mt-1 w-full rounded border px-3 py-2">
        </label>
        <label class="text-sm font-semibold text-slate-700">Phòng
            <select name="classroom_id" class="mt-1 w-full rounded border px-3 py-2">
                <option value="">Tất cả phòng</option>
                @foreach($classrooms as $room)
                    <option value="{{ $room->id }}" @selected(request('classroom_id') == $room->id)>{{ $room->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm font-semibold text-slate-700">Tình trạng
            <select name="status" class="mt-1 w-full rounded border px-3 py-2">
                <option value="">Tất cả</option>
                <option value="NORMAL" @selected(request('status') === 'NORMAL')>Bình thường</option>
                <option value="BROKEN" @selected(request('status') === 'BROKEN')>Hỏng</option>
                <option value="REPAIRING" @selected(request('status') === 'REPAIRING')>Đang sửa</option>
                <option value="LIQUIDATED" @selected(request('status') === 'LIQUIDATED')>Đã thanh lý</option>
            </select>
        </label>
        <div class="flex items-end gap-2 md:col-span-5">
            <span id="public-assets-filter-state" class="rounded bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700">{{ $hasFilters ? 'Đã lọc' : 'Lọc tự động khi nhập hoặc chọn' }}</span>
            <a href="{{ route('inventory.public-assets') }}" class="rounded border px-4 py-2 font-semibold text-slate-700">Xóa lọc</a>
        </div>
    </form>
    <script>
        (() => {
            const init = () => {
                const form = document.getElementById('public-assets-filter');
                if (!form || form.dataset.autoFilterReady === '1') return;
                form.dataset.autoFilterReady = '1';
                const state = document.getElementById('public-assets-filter-state');
                let timer = null;
                const submit = (delay = 350) => {
                    if (state) state.textContent = 'Đang lọc...';
                    clearTimeout(timer);
                    timer = setTimeout(() => form.requestSubmit(), delay);
                };
                form.querySelectorAll('select').forEach(select => select.addEventListener('change', () => submit(80)));
                form.querySelectorAll('input').forEach(input => {
                    const delay = input.type === 'number' ? 300 : 450;
                    input.addEventListener('input', () => submit(delay));
                    input.addEventListener('change', () => submit(80));
                });
            };
            document.addEventListener('DOMContentLoaded', init);
            document.addEventListener('turbo:load', init);
            if (document.readyState !== 'loading') init();
        })();
    </script>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-lg border border-blue-100 bg-white p-4 shadow-sm"><p class="text-sm text-slate-500">Tài sản công</p><p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($assets->total(), 0, ',', '.') }}</p></div>
        <div class="rounded-lg border border-emerald-100 bg-white p-4 shadow-sm"><p class="text-sm text-slate-500">Nguyên giá theo trang hiện tại</p><p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($totalInitial, 0, ',', '.') }}</p></div>
        <div class="rounded-lg border border-amber-100 bg-white p-4 shadow-sm"><p class="text-sm text-slate-500">Giá trị còn lại năm {{ $reportYear }}</p><p class="mt-1 text-2xl font-bold text-slate-900">{{ number_format($totalRemaining, 0, ',', '.') }}</p></div>
    </div>

    <section class="overflow-hidden rounded-lg border bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1450px] text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Mã</th>
                        <th class="px-4 py-3">Tên tài sản</th>
                        <th class="px-4 py-3">Phòng</th>
                        <th class="px-4 py-3">Đơn vị cung cấp</th>
                        <th class="px-4 py-3">Hợp đồng/Hóa đơn</th>
                        <th class="px-4 py-3 text-right">Đơn giá</th>
                        <th class="px-4 py-3 text-right">Thành tiền</th>
                        <th class="px-4 py-3 text-center">Khấu hao</th>
                        <th class="px-4 py-3 text-center">Đã khấu hao</th>
                        <th class="px-4 py-3 text-right">Còn lại</th>
                        <th class="px-4 py-3">Tình trạng</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($assets as $asset)
                        <tr>
                            <td class="px-4 py-3 font-mono text-xs text-blue-700">{{ $asset->asset_code }}</td>
                            <td class="px-4 py-3"><p class="font-semibold text-slate-900">{{ $asset->name }}</p><p class="text-xs text-slate-500">{{ $asset->quantity }} {{ $asset->unit ?: 'cái' }}</p></td>
                            <td class="px-4 py-3">{{ $asset->classroom?->name ?: 'Chưa gán phòng' }}</td>
                            <td class="px-4 py-3">{{ $asset->supplier ?: '—' }}</td>
                            <td class="px-4 py-3">{{ $asset->contract_invoice_number ?: '—' }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $asset->unit_price, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((float) $asset->initial_value, 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-center">
                                @if($canEditPublicAssets)
                                    <form method="POST" action="{{ route('inventory.public-assets.depreciation', $asset) }}" class="flex min-w-[150px] items-center justify-center gap-1">
                                        @csrf @method('PATCH')
                                        <input name="depreciation_rate" type="number" min="0" max="100" step="0.01" value="{{ (float) $asset->depreciation_rate }}" class="w-20 rounded border px-2 py-1 text-right">
                                        <button class="rounded bg-slate-800 px-2 py-1 text-xs font-semibold text-white">Lưu</button>
                                    </form>
                                @else
                                    {{ rtrim(rtrim(number_format((float) $asset->depreciation_rate, 2, ',', '.'), '0'), ',') }}%/năm
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">{{ $asset->depreciated_years }} năm · {{ rtrim(rtrim(number_format((float) $asset->depreciated_percent, 2, ',', '.'), '0'), ',') }}%</td>
                            <td class="px-4 py-3 text-right"><b>{{ rtrim(rtrim(number_format((float) $asset->remaining_percent, 2, ',', '.'), '0'), ',') }}%</b><div class="text-xs text-slate-500">{{ number_format((float) $asset->remaining_value, 0, ',', '.') }}</div></td>
                            <td class="px-4 py-3">{{ ['NORMAL'=>'Bình thường','BROKEN'=>'Hỏng','REPAIRING'=>'Đang sửa','LIQUIDATED'=>'Đã thanh lý'][$asset->status] ?? $asset->status }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="px-4 py-10 text-center text-slate-500">Chưa có tài sản công phù hợp.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t px-4 py-3">{{ $assets->links() }}</div>
    </section>
</div>
