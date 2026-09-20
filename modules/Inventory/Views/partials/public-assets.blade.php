@php
    $rows = $assets->getCollection();
    $totalInitial = $rows->sum(fn ($asset) => (float) $asset->initial_value);
    $totalRemaining = $rows->sum(fn ($asset) => (float) $asset->remaining_value);
    $canEditPublicAssets = auth()->user()?->isSuperAdmin() || \App\Support\PermissionCheck::can(auth()->user(), 'inventory.assets.edit');
    $canDeletePublicAssets = auth()->user()?->isSuperAdmin() || \App\Support\PermissionCheck::can(auth()->user(), 'inventory.assets.delete');
    $showPublicAssetActions = true;
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
        @if($hasFilters)
            <div class="flex items-end gap-2 md:col-span-5">
                <a href="{{ route('inventory.public-assets') }}" class="rounded border px-4 py-2 font-semibold text-slate-700">Xóa lọc</a>
            </div>
        @endif
    </form>
    <script>
        (() => {
            const init = () => {
                const form = document.getElementById('public-assets-filter');
                if (!form || form.dataset.autoFilterReady === '1') return;
                form.dataset.autoFilterReady = '1';
                let timer = null;
                const submit = (delay = 350) => {
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
    <script>
        (() => {
            const init = () => {
                document.querySelectorAll('[data-public-asset-edit-form]').forEach(form => {
                    if (form.dataset.totalBound === '1') return;
                    form.dataset.totalBound = '1';
                    const quantity = form.querySelector('input[name="quantity"]');
                    const unitPrice = form.querySelector('input[name="unit_price"]');
                    const total = form.querySelector('input[name="total_amount_preview"]');
                    const sync = () => {
                        const value = Number(quantity?.value || 0) * Number(unitPrice?.value || 0);
                        if (total) total.value = value > 0 ? String(Math.round(value)) : '';
                    };
                    quantity?.addEventListener('input', sync);
                    unitPrice?.addEventListener('input', sync);
                    sync();
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
        <div class="rounded-lg border border-amber-100 bg-white p-4 shadow-sm"><p class="text-sm text-slate-500">Giá trị còn lại năm {{ $reportYear }}</p><p class="mt-1 text-2xl font-bold text-slate-900" data-public-total-remaining>{{ number_format($totalRemaining, 0, ',', '.') }}</p></div>
    </div>

    <section class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
        <button type="button" id="public-assets-list-header" class="flex w-full flex-col gap-2 border-b border-slate-100 bg-slate-50 px-5 py-4 text-left transition hover:bg-slate-100 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-bold text-slate-900">Danh sách tài sản công</h2>
                <p class="text-sm text-slate-500">Danh sách tài sản theo từng hồ sơ.</p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span class="w-fit rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-semibold text-slate-600">{{ number_format($assets->total(), 0, ',', '.') }} tài sản</span>
                <span id="public-assets-list-indicator" class="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-xs font-bold text-slate-600">Ẩn</span>
            </div>
        </button>
        <div id="public-assets-list-body">
        <div class="divide-y divide-slate-100">
            @forelse($assets as $asset)
                @php
                    $statusLabel = ['NORMAL'=>'Bình thường','BROKEN'=>'Hỏng','REPAIRING'=>'Đang sửa','LIQUIDATED'=>'Đã thanh lý'][$asset->status] ?? $asset->status;
                    $statusClass = [
                        'NORMAL' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                        'BROKEN' => 'border-rose-200 bg-rose-50 text-rose-700',
                        'REPAIRING' => 'border-amber-200 bg-amber-50 text-amber-700',
                        'LIQUIDATED' => 'border-slate-200 bg-slate-100 text-slate-600',
                    ][$asset->status] ?? 'border-slate-200 bg-slate-100 text-slate-600';
                    $currentRateValue = rtrim(rtrim(number_format((float) $asset->current_year_depreciation_rate, 2, ',', '.'), '0'), ',');
                    $currentRate = $asset->current_year_depreciation_recorded
                        ? $currentRateValue.'% năm '.$reportYear
                        : (($asset->current_year_depreciation_inherited_from_year ?? null)
                            ? $currentRateValue.'% kế thừa từ '.$asset->current_year_depreciation_inherited_from_year
                            : 'Chưa nhập');
                    $depreciatedPercent = rtrim(rtrim(number_format((float) $asset->depreciated_percent, 2, ',', '.'), '0'), ',');
                    $remainingPercent = rtrim(rtrim(number_format((float) $asset->remaining_percent, 2, ',', '.'), '0'), ',');
                @endphp
                <article class="bg-white p-5 transition hover:bg-slate-50/60" data-public-asset-card data-asset-id="{{ $asset->id }}" data-initial-value="{{ (float) $asset->initial_value }}" data-previous-remaining="{{ (float) $asset->remaining_value }}">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="rounded border border-blue-100 bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">Mã: <span class="font-mono">{{ $asset->asset_code }}</span></span>
                                <span class="rounded border px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">Tình trạng: {{ $statusLabel }}</span>
                            </div>
                            <h3 class="mt-2 break-words text-lg font-bold text-slate-950">{{ $asset->name }}</h3>
                            <div class="mt-3 grid gap-2 text-sm text-slate-700 sm:grid-cols-3">
                                <div class="rounded border border-slate-200 bg-slate-50 px-3 py-2">
                                    <p class="text-xs font-semibold text-slate-500">Số lượng</p>
                                    <p class="mt-0.5 font-bold text-slate-900">{{ $asset->quantity }} {{ $asset->unit ?: 'cái' }}</p>
                                </div>
                                <div class="rounded border border-slate-200 bg-slate-50 px-3 py-2">
                                    <p class="text-xs font-semibold text-slate-500">Phòng</p>
                                    <p class="mt-0.5 font-bold text-slate-900">{{ $asset->classroom?->name ?: 'Chưa gán phòng' }}</p>
                                </div>
                                <div class="rounded border border-slate-200 bg-slate-50 px-3 py-2">
                                    <p class="text-xs font-semibold text-slate-500">Đơn vị cung cấp</p>
                                    <p class="mt-0.5 break-words font-bold text-slate-900">{{ $asset->supplier ?: 'Chưa có' }}</p>
                                </div>
                            </div>
                        </div>
                        @if($showPublicAssetActions)
                            <div class="flex shrink-0 flex-wrap gap-2 lg:justify-end">
                                <button type="button" class="inline-flex items-center gap-1.5 rounded border border-slate-200 bg-white px-3 py-2 text-xs font-bold text-slate-700 shadow-sm hover:border-blue-200 hover:text-blue-700" onclick="document.getElementById('public-asset-history-{{ $asset->id }}').classList.toggle('hidden')"><i class="bi bi-clock-history"></i> Chi tiết</button>
                                @if($canEditPublicAssets)
                                    <button type="button" class="inline-flex items-center gap-1.5 rounded bg-blue-600 px-3 py-2 text-xs font-bold text-white shadow-sm hover:bg-blue-700" onclick="document.getElementById('public-asset-edit-{{ $asset->id }}').classList.toggle('hidden')"><i class="bi bi-pencil-square"></i> Sửa</button>
                                @endif
                                @if($canDeletePublicAssets)
                                    <form method="POST" action="{{ route('inventory.assets.delete', $asset) }}" onsubmit="return confirm('Xóa tài sản công này?');">
                                        @csrf @method('DELETE')
                                        <button class="inline-flex items-center gap-1.5 rounded bg-rose-600 px-3 py-2 text-xs font-bold text-white shadow-sm hover:bg-rose-700"><i class="bi bi-trash"></i> Xóa</button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </div>

                    <div class="mt-5 grid gap-4 lg:grid-cols-[1.1fr_1.4fr]">
                        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                            <p class="text-xs font-bold uppercase text-slate-500">Thông tin mua sắm</p>
                            <dl class="mt-3 grid gap-3 text-sm">
                                <div class="flex justify-between gap-4"><dt class="text-slate-500">Hợp đồng/Hóa đơn</dt><dd class="max-w-[60%] break-words text-right font-semibold text-slate-900">{{ $asset->contract_invoice_number ?: '—' }}</dd></div>
                                <div class="flex justify-between gap-4"><dt class="text-slate-500">Đơn giá</dt><dd class="font-semibold text-slate-900">{{ number_format((float) $asset->unit_price, 0, ',', '.') }}</dd></div>
                                <div class="flex justify-between gap-4 border-t border-slate-100 pt-3"><dt class="font-semibold text-slate-700">Thành tiền</dt><dd class="text-lg font-extrabold text-slate-950">{{ number_format((float) $asset->initial_value, 0, ',', '.') }}</dd></div>
                            </dl>
                        </div>

                        <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-xs font-bold uppercase text-slate-500">Khấu hao năm {{ $reportYear }}</p>
                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600" data-current-rate-label>{{ $currentRate }}</span>
                            </div>
                            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                                <div><p class="text-xs text-slate-500">Đã khấu hao</p><p class="mt-1 font-bold text-slate-900" data-current-depreciated-label>{{ $asset->depreciated_years }} năm · {{ $depreciatedPercent }}%</p></div>
                                <div><p class="text-xs text-slate-500">Còn lại</p><p class="mt-1 font-bold text-slate-900" data-current-remaining-percent>{{ $remainingPercent }}%</p></div>
                                <div><p class="text-xs text-slate-500">Giá trị còn lại</p><p class="mt-1 font-bold text-amber-700" data-current-remaining-value>{{ number_format((float) $asset->remaining_value, 0, ',', '.') }}</p></div>
                            </div>
                            <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-blue-600" style="width: {{ min(100, max(0, (float) $asset->depreciated_percent)) }}%" data-current-progress></div>
                            </div>
                        </div>
                    </div>

                    @if($canEditPublicAssets)
                        <div id="public-asset-edit-{{ $asset->id }}" class="mt-4 hidden rounded-lg border border-blue-100 bg-blue-50 p-4">
                            <form method="POST" action="{{ route('inventory.public-assets.update', $asset) }}" class="grid gap-3 md:grid-cols-4" data-public-asset-edit-form>
                                @csrf @method('PATCH')
                                <label class="text-sm font-semibold">Mã tài sản<input name="asset_code" value="{{ $asset->asset_code }}" required class="mt-1 w-full rounded border px-3 py-2"></label>
                                <label class="text-sm font-semibold md:col-span-2">Tên tài sản<input name="name" value="{{ $asset->name }}" required class="mt-1 w-full rounded border px-3 py-2"></label>
                                <label class="text-sm font-semibold">Phòng<select name="classroom_id" class="mt-1 w-full rounded border px-3 py-2"><option value="">Chưa gán phòng</option>@foreach($classrooms as $room)<option value="{{ $room->id }}" @selected((int) $asset->classroom_id === (int) $room->id)>{{ $room->name }}</option>@endforeach</select></label>
                                <label class="text-sm font-semibold">Số lượng<input name="quantity" type="number" min="1" step="1" value="{{ (int) $asset->quantity }}" required class="mt-1 w-full rounded border px-3 py-2"></label>
                                <label class="text-sm font-semibold">Đơn vị tính<input name="unit" value="{{ $asset->unit ?: 'cái' }}" class="mt-1 w-full rounded border px-3 py-2"></label>
                                <label class="text-sm font-semibold">Đơn giá<input name="unit_price" type="number" min="10000000" step="1000" value="{{ max((float) $asset->unit_price, 10000000) }}" class="mt-1 w-full rounded border px-3 py-2"></label>
                                <label class="text-sm font-semibold">Thành tiền<input name="total_amount_preview" type="number" readonly value="{{ max((float) $asset->unit_price, 10000000) * (int) $asset->quantity }}" class="mt-1 w-full rounded border bg-slate-100 px-3 py-2 text-slate-700"></label>
                                <label class="text-sm font-semibold">Hợp đồng/Hóa đơn<input name="contract_invoice_number" value="{{ $asset->contract_invoice_number }}" class="mt-1 w-full rounded border px-3 py-2"></label>
                                <label class="text-sm font-semibold">Đơn vị cung cấp<input name="supplier" value="{{ $asset->supplier }}" class="mt-1 w-full rounded border px-3 py-2"></label>
                                <label class="text-sm font-semibold">Khấu hao (%)<input name="depreciation_rate" type="number" min="0" max="100" step="0.01" value="{{ (float) $asset->depreciation_rate }}" class="mt-1 w-full rounded border px-3 py-2"></label>
                                <label class="text-sm font-semibold">Tình trạng<select name="status" required class="mt-1 w-full rounded border px-3 py-2"><option value="NORMAL" @selected($asset->status === 'NORMAL')>Bình thường</option><option value="BROKEN" @selected($asset->status === 'BROKEN')>Hỏng</option><option value="REPAIRING" @selected($asset->status === 'REPAIRING')>Đang sửa</option><option value="LIQUIDATED" @selected($asset->status === 'LIQUIDATED')>Đã thanh lý</option></select></label>
                                <div class="flex flex-wrap items-end gap-2 md:col-span-4">
                                    <button class="rounded bg-blue-600 px-4 py-2 font-semibold text-white">Lưu sửa</button>
                                    <button type="button" class="rounded border px-4 py-2 font-semibold text-slate-700" onclick="document.getElementById('public-asset-edit-{{ $asset->id }}').classList.add('hidden')">Hủy</button>
                                </div>
                            </form>
                        </div>
                    @endif

                    <div id="public-asset-history-{{ $asset->id }}" class="mt-4 hidden rounded-lg border border-slate-200 bg-slate-50 p-4">
                        <div class="grid gap-4 lg:grid-cols-[1fr_320px]">
                            <div class="space-y-2">
                                @foreach($asset->depreciation_history as $yearRow)
                                    <div class="rounded-lg border border-slate-200 bg-white p-3 text-sm shadow-sm" data-depreciation-year-row data-year="{{ $yearRow['year'] }}" data-save-url="{{ route('inventory.public-assets.depreciation', $asset) }}" data-delete-url="{{ route('inventory.public-assets.depreciation.delete', [$asset, $yearRow['year']]) }}">
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <p class="font-bold text-slate-900">Năm {{ $yearRow['year'] }}</p>
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span data-depreciation-status class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $yearRow['recorded'] ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">{{ $yearRow['recorded'] ? 'Đã nhập' : 'Chưa nhập' }}</span>
                                                @if($canEditPublicAssets && $yearRow['recorded'])
                                                    <form method="POST" action="{{ route('inventory.public-assets.depreciation.delete', [$asset, $yearRow['year']]) }}" data-depreciation-delete-form>
                                                        @csrf @method('DELETE')
                                                        <button class="rounded bg-rose-600 px-2.5 py-1 text-xs font-bold text-white" onclick="return confirm('Xóa khấu hao năm {{ $yearRow['year'] }}?')">Xóa</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="mt-3 grid gap-3 sm:grid-cols-3">
                                            <div data-depreciation-rate-cell>
                                                <p class="text-xs text-slate-500">Tỷ lệ khấu hao</p>
                                                @if($canEditPublicAssets && $yearRow['recorded'])
                                                    <form method="POST" action="{{ route('inventory.public-assets.depreciation', $asset) }}" class="mt-1 flex gap-2" data-depreciation-form>
                                                        @csrf @method('PATCH')
                                                        <input type="hidden" name="year" value="{{ $yearRow['year'] }}">
                                                        <input name="depreciation_rate" type="number" min="0" max="100" step="0.01" value="{{ (float) $yearRow['rate'] }}" class="w-24 rounded border px-2 py-1.5">
                                                        <button class="rounded bg-blue-600 px-3 py-1.5 text-xs font-bold text-white">Lưu</button>
                                                    </form>
                                                @else
                                                    <p data-depreciation-rate class="font-semibold">{{ $yearRow['recorded'] ? rtrim(rtrim(number_format((float) $yearRow['rate'], 2, ',', '.'), '0'), ',').'%' : 'Chưa nhập' }}</p>
                                                @endif
                                            </div>
                                            <div><p class="text-xs text-slate-500">Tiền khấu hao lũy kế</p><p data-depreciation-amount class="font-semibold">{{ $yearRow['recorded'] ? number_format((float) $yearRow['amount'], 0, ',', '.') : 'Chưa nhập' }}</p></div>
                                            <div><p class="text-xs text-slate-500">Còn lại năm đó</p><p data-depreciation-remaining class="font-semibold">{{ $yearRow['recorded'] ? number_format((float) $yearRow['remaining_value'], 0, ',', '.') : 'Chưa nhập' }}</p></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            @if($canEditPublicAssets)
                                <form method="POST" action="{{ route('inventory.public-assets.depreciation', $asset) }}" class="h-fit rounded-lg border border-slate-200 bg-white p-4 shadow-sm" data-depreciation-form>
                                    @csrf @method('PATCH')
                                    <input type="hidden" name="year" value="{{ $reportYear }}">
                                    <h3 class="font-bold text-slate-900">Khấu hao năm {{ $reportYear }}</h3>
                                    <label class="mt-3 block text-sm font-semibold">Tỷ lệ khấu hao (%)<input name="depreciation_rate" type="number" min="0" max="100" step="0.01" value="{{ $asset->current_year_depreciation_recorded ? (float) $asset->current_year_depreciation_rate : '' }}" class="mt-1 w-full rounded border px-3 py-2"></label>
                                    <button class="mt-4 w-full rounded bg-slate-900 px-4 py-2 font-semibold text-white hover:bg-slate-800">Lưu khấu hao năm</button>
                                </form>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="px-4 py-10 text-center text-slate-500">Chưa có tài sản công phù hợp.</div>
            @endforelse
        </div>
        <div class="border-t px-4 py-3">{{ $assets->links() }}</div>
        </div>
    </section>
    <script>
        (() => {
            const init = () => {
                const button = document.getElementById('public-assets-list-header');
                const body = document.getElementById('public-assets-list-body');
                const indicator = document.getElementById('public-assets-list-indicator');
                if (!button || !body || button.dataset.bound === '1') return;
                button.dataset.bound = '1';
                button.addEventListener('click', () => {
                    const hidden = body.classList.toggle('hidden');
                    if (indicator) indicator.textContent = hidden ? 'Mở' : 'Ẩn';
                });
            };
            document.addEventListener('DOMContentLoaded', init);
            document.addEventListener('turbo:load', init);
            if (document.readyState !== 'loading') init();
        })();
    </script>
    <script>
        (() => {
            const formatMoney = value => new Intl.NumberFormat('vi-VN', { maximumFractionDigits: 0 }).format(Number(value || 0));
            const formatRate = value => {
                const number = Number(value || 0);
                return Number.isInteger(number) ? String(number) : number.toLocaleString('vi-VN', { maximumFractionDigits: 2 });
            };
            const reportYear = '{{ $reportYear }}';
            const token = () => document.querySelector('meta[name="csrf-token"]')?.content || document.querySelector('input[name="_token"]')?.value || '';
            const updatePageRemainingTotal = (card, nextRemaining) => {
                const total = document.querySelector('[data-public-total-remaining]');
                if (!total || !card) return;
                const previous = Number(card.dataset.previousRemaining || 0);
                const currentTotal = Number(total.textContent.replace(/[^\d.-]/g, '') || 0);
                const next = currentTotal - previous + Number(nextRemaining || 0);
                total.textContent = formatMoney(next);
                card.dataset.previousRemaining = String(nextRemaining || 0);
            };
            const updateCardSummary = (card, data) => {
                if (!card) return;
                const rate = Number(data.rate || 0);
                const remaining = Number(data.remaining_value || 0);
                const initial = Number(card.dataset.initialValue || 0);
                const depreciatedPercent = Number(data.depreciated_percent ?? (initial > 0 ? Math.min(100, rate) : 0));
                const remainingPercent = Number(data.remaining_percent ?? (initial > 0 ? Math.max(0, remaining / initial * 100) : 0));
                const rateLabel = card.querySelector('[data-current-rate-label]');
                const depreciatedLabel = card.querySelector('[data-current-depreciated-label]');
                const remainingPercentLabel = card.querySelector('[data-current-remaining-percent]');
                const remainingValueLabel = card.querySelector('[data-current-remaining-value]');
                const progress = card.querySelector('[data-current-progress]');
                if (rateLabel) rateLabel.textContent = `${formatRate(rate)}% năm ${data.year}`;
                if (depreciatedLabel) depreciatedLabel.textContent = `${data.depreciated_years || 0} năm · ${formatRate(depreciatedPercent)}%`;
                if (remainingPercentLabel) remainingPercentLabel.textContent = `${formatRate(remainingPercent)}%`;
                if (remainingValueLabel) remainingValueLabel.textContent = formatMoney(remaining);
                if (progress) progress.style.width = `${Math.min(100, Math.max(0, depreciatedPercent))}%`;
                updatePageRemainingTotal(card, remaining);
            };
            const setStatus = (row, text, className) => {
                const badge = row?.querySelector('[data-depreciation-status]');
                if (!badge) return;
                badge.className = 'rounded-full px-2.5 py-1 text-xs font-semibold ' + className;
                badge.textContent = text;
            };
            const setRecorded = (row, data) => {
                if (!row) return;
                const rateCell = row.querySelector('[data-depreciation-rate-cell]');
                const amount = row.querySelector('[data-depreciation-amount]');
                const remaining = row.querySelector('[data-depreciation-remaining]');
                const year = row.dataset.year;
                const csrf = token();
                if (rateCell) {
                    rateCell.innerHTML = `<p class="text-xs text-slate-500">Tỷ lệ khấu hao</p><form method="POST" action="${row.dataset.saveUrl}" class="mt-1 flex gap-2" data-depreciation-form><input type="hidden" name="_token" value="${csrf}"><input type="hidden" name="_method" value="PATCH"><input type="hidden" name="year" value="${year}"><input name="depreciation_rate" type="number" min="0" max="100" step="0.01" value="${Number(data.rate || 0)}" class="w-24 rounded border px-2 py-1.5"><button class="rounded bg-blue-600 px-3 py-1.5 text-xs font-bold text-white">Lưu</button></form>`;
                }
                if (amount) amount.textContent = formatMoney(data.amount);
                if (remaining) remaining.textContent = formatMoney(data.remaining_value);
                setStatus(row, 'Đã nhập', 'bg-emerald-50 text-emerald-700');
                const actions = row.querySelector('[data-depreciation-status]')?.parentElement;
                if (actions && !actions.querySelector('[data-depreciation-delete-form]')) {
                    actions.insertAdjacentHTML('beforeend', `<form method="POST" action="${row.dataset.deleteUrl}" data-depreciation-delete-form><input type="hidden" name="_token" value="${csrf}"><input type="hidden" name="_method" value="DELETE"><button class="rounded bg-rose-600 px-2.5 py-1 text-xs font-bold text-white" onclick="return confirm('Xóa khấu hao năm ${year}?')">Xóa</button></form>`);
                }
            };
            const setUnrecorded = row => {
                if (!row) return;
                const rateCell = row.querySelector('[data-depreciation-rate-cell]');
                const amount = row.querySelector('[data-depreciation-amount]');
                const remaining = row.querySelector('[data-depreciation-remaining]');
                if (rateCell) rateCell.innerHTML = '<p class="text-xs text-slate-500">Tỷ lệ khấu hao</p><p data-depreciation-rate class="font-semibold">Chưa nhập</p>';
                if (amount) amount.textContent = 'Chưa nhập';
                if (remaining) remaining.textContent = 'Chưa nhập';
                row.querySelector('[data-depreciation-delete-form]')?.remove();
                setStatus(row, 'Chưa nhập', 'bg-slate-100 text-slate-500');
            };
            const submitForm = async form => {
                const button = form.querySelector('button');
                const oldText = button?.textContent;
                if (button) {
                    button.disabled = true;
                    button.textContent = 'Đang lưu...';
                }
                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: new FormData(form),
                    });
                    const data = await response.json();
                    if (!response.ok || !data.success) throw new Error(data.message || 'Không lưu được khấu hao.');
                    const card = form.closest('[data-public-asset-card]');
                    const row = form.closest('[data-depreciation-year-row]') || card?.querySelector(`[data-depreciation-year-row][data-year="${data.year}"]`) || document.querySelector(`[data-depreciation-year-row][data-year="${data.year}"]`);
                    setRecorded(row, data);
                    if (String(data.year) === String(reportYear)) updateCardSummary(card, data);
                    if (button) button.textContent = 'Đã lưu';
                    setTimeout(() => { if (button) button.textContent = oldText || 'Lưu'; }, 1200);
                } catch (error) {
                    alert(error.message || 'Không lưu được khấu hao.');
                    if (button) button.textContent = oldText || 'Lưu';
                } finally {
                    if (button) button.disabled = false;
                }
            };
            const deleteForm = async form => {
                const button = form.querySelector('button');
                const row = form.closest('[data-depreciation-year-row]');
                if (button) button.disabled = true;
                try {
                    const response = await fetch(form.action, {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                        body: new FormData(form),
                    });
                    const data = await response.json();
                    if (!response.ok || !data.success) throw new Error(data.message || 'Không xóa được khấu hao.');
                    setUnrecorded(row);
                } catch (error) {
                    alert(error.message || 'Không xóa được khấu hao.');
                } finally {
                    if (button) button.disabled = false;
                }
            };
            document.addEventListener('submit', event => {
                const save = event.target.closest('[data-depreciation-form]');
                const remove = event.target.closest('[data-depreciation-delete-form]');
                if (!save && !remove) return;
                event.preventDefault();
                if (save) submitForm(save);
                if (remove) deleteForm(remove);
            });
        })();
    </script>
</div>
