@php
    $canEditWarehouse = auth()->user()?->isSuperAdmin() || \App\Support\PermissionCheck::can(auth()->user(), 'inventory.warehouses.edit');
    $canDeleteWarehouse = auth()->user()?->isSuperAdmin() || \App\Support\PermissionCheck::can(auth()->user(), 'inventory.warehouses.delete');
@endphp

<div class="mt-5 space-y-5">
    @forelse($warehouses as $warehouse)
        <section class="rounded border bg-white p-4">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                <div>
                    <h2 class="font-semibold">{{ $warehouse->code }} - {{ $warehouse->name }}</h2>
                    <p class="text-sm text-slate-500">{{ $warehouse->items->count() }} mặt hàng đang được quản lý trong kho</p>
                </div>
                <div class="flex items-center gap-3">
                    @if($canEditWarehouse)
                        <details>
                            <summary class="inline-flex cursor-pointer items-center gap-1 rounded-lg border border-amber-200 px-3 py-1.5 text-sm font-semibold text-amber-700"><i class="bi bi-pencil-square"></i> Sửa kho</summary>
                            <form method="POST" action="{{ route('inventory.warehouse.update', $warehouse) }}" class="mt-2 grid gap-2 rounded bg-slate-50 p-3 md:grid-cols-4">
                                @csrf
                                @method('PATCH')
                                <input name="code" required value="{{ $warehouse->code }}" class="rounded border p-2">
                                <input name="name" required value="{{ $warehouse->name }}" class="rounded border p-2">
                                <input name="location" value="{{ $warehouse->location }}" placeholder="Vị trí" class="rounded border p-2">
                                <select name="manager_id" class="rounded border p-2">
                                    <option value="">Thủ kho</option>
                                    @foreach(($users ?? collect()) as $user)
                                        <option value="{{ $user->id }}" @selected($warehouse->manager_id == $user->id)>{{ $user->name }}</option>
                                    @endforeach
                                </select>
                                <button class="rounded bg-blue-600 px-3 py-2 text-white md:col-span-4">Lưu kho</button>
                            </form>
                        </details>
                    @endif
                    @if($canDeleteWarehouse)
                        <form method="POST" action="{{ route('inventory.warehouse.destroy', $warehouse) }}" onsubmit="return confirm('Xóa kho này và toàn bộ dữ liệu trong kho?')">
                            @csrf
                            @method('DELETE')
                            <button class="inline-flex items-center gap-1 rounded-lg border border-red-200 px-3 py-1.5 text-sm font-semibold text-red-600 hover:bg-red-50"><i class="bi bi-trash3"></i> Xóa kho</button>
                        </form>
                    @endif
                </div>
            </div>

            @php($industries = $warehouse->items->groupBy(fn($item) => $item->material?->category?->parent?->id ?: 'none'))
            @if($warehouse->items->isEmpty())
                <div class="overflow-x-auto rounded border border-blue-100">
                    <table class="w-full min-w-[1150px] text-left text-sm">
                        <thead class="bg-slate-100">
                            <tr>
                                <th class="p-3">Ngành vật tư</th>
                                <th class="p-3">Loại vật tư</th>
                                <th class="p-3">Vật tư</th>
                                <th class="p-3">Năm sản xuất</th>
                                <th class="p-3">Năm sử dụng</th>
                                <th class="p-3">Thời gian bảo hành</th>
                                <th class="p-3">Phân cấp</th>
                                <th class="p-3">Số lượng</th>
                                <th class="p-3">Thao tác</th>
                            </tr>
                        </thead>
                        <tbody><tr><td colspan="9" class="p-5 text-center text-slate-500">Kho chưa có vật tư. Hãy thêm vật tư ở biểu mẫu bên dưới.</td></tr></tbody>
                    </table>
                </div>
            @endif

            <div class="space-y-4">
                @foreach($industries as $industryItems)
                    @php($industry = $industryItems->first()->material?->category?->parent)
                    <div class="overflow-x-auto rounded border border-blue-100">
                        <div class="border-b bg-blue-50 px-3 py-2 font-semibold text-blue-900">
                            Ngành vật tư: {{ $industry?->name ?: 'Chưa phân ngành' }}
                        </div>
                        @php($types = $industryItems->groupBy(fn($item) => $item->material?->category?->id ?: 'none'))
                        @foreach($types as $typeItems)
                            @php($type = $typeItems->first()->material?->category)
                            <div class="border-b last:border-b-0">
                                <div class="bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-800">
                                    Loại vật tư: {{ $type?->name ?: 'Chưa phân loại' }}
                                </div>
                                <table class="w-full min-w-[1250px] text-left text-sm">
                                    <thead class="bg-white text-slate-600">
                                        <tr>
                                            <th class="p-3">Vật tư</th>
                                            <th class="p-3">Năm sản xuất</th>
                                            <th class="p-3">Năm sử dụng</th>
                                            <th class="p-3">Thời gian bảo hành</th>
                                            <th class="p-3">Phân cấp</th>
                                            <th class="p-3">Số lượng</th>
                                            <th class="p-3">Thao tác</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($typeItems as $item)
                                            @php($material = $item->material)
                                            <tr class="border-t align-top">
                                                <td class="p-3 font-semibold">{{ $item->code }} - {{ $item->name }}</td>
                                                <td class="p-3">{{ $material?->manufacture_year ?: '-' }}</td>
                                                <td class="p-3">{{ $material?->usage_year ?: '-' }}</td>
                                                <td class="p-3">
                                                    @if($material?->purchase_date && $material?->expiry_date)
                                                        {{ $material->purchase_date->diffInMonths($material->expiry_date) }} tháng (đến {{ $material->expiry_date->format('d/m/Y') }})
                                                    @elseif($material?->expiry_date)
                                                        Đến {{ $material->expiry_date->format('d/m/Y') }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td class="p-3">{{ $material?->classification ?: '-' }}</td>
                                                <td class="p-3">{{ (int) $item->quantity }} {{ $item->unit ?: $material?->unit }}</td>
                                                <td class="p-3">
                                                    <div class="flex flex-wrap gap-2">
                                                        @if($canEditWarehouse)
                                                            <button type="button" class="rounded bg-blue-600 px-3 py-1.5 text-xs font-bold text-white" onclick="document.getElementById('warehouse-item-edit-{{ $item->id }}').classList.toggle('hidden')">Sửa</button>
                                                        @endif
                                                        @if($canDeleteWarehouse)
                                                            <form method="POST" action="{{ route('inventory.warehouse.item.destroy', [$warehouse, $item]) }}" onsubmit="return confirm('Xóa vật tư này khỏi kho?');">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button class="rounded bg-rose-600 px-3 py-1.5 text-xs font-bold text-white">Xóa</button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </td>
                                            </tr>
                                            @if($canEditWarehouse)
                                                <tr id="warehouse-item-edit-{{ $item->id }}" class="hidden bg-blue-50/60">
                                                    <td colspan="7" class="p-4">
                                                        <form method="POST" action="{{ route('inventory.warehouse.item.update', [$warehouse, $item]) }}" class="grid gap-3 rounded border bg-white p-4 md:grid-cols-4">
                                                            @csrf
                                                            @method('PATCH')
                                                            <input type="hidden" name="material_id" value="{{ $item->material_id }}">
                                                            <label class="text-sm font-semibold">Mã vật tư<input name="code" value="{{ $item->code }}" required class="mt-1 w-full rounded border p-2"></label>
                                                            <label class="text-sm font-semibold">Tên vật tư<input name="name" value="{{ $item->name }}" required class="mt-1 w-full rounded border p-2"></label>
                                                            <label class="text-sm font-semibold">Đơn vị<input name="unit" value="{{ $item->unit ?: $material?->unit }}" class="mt-1 w-full rounded border p-2"></label>
                                                            <label class="text-sm font-semibold">Số lượng<input name="quantity" type="number" min="0" step="1" value="{{ (int) $item->quantity }}" required class="mt-1 w-full rounded border p-2"></label>
                                                            <label class="text-sm font-semibold">Tồn tối thiểu<input name="minimum_quantity" type="number" min="0" step="1" value="{{ (int) $item->minimum_quantity }}" class="mt-1 w-full rounded border p-2"></label>
                                                            <label class="text-sm font-semibold md:col-span-3">Ghi chú<input name="note" value="{{ $item->note }}" class="mt-1 w-full rounded border p-2"></label>
                                                            <div class="flex gap-2 md:col-span-4">
                                                                <button class="rounded bg-blue-600 px-4 py-2 text-white">Lưu</button>
                                                                <button type="button" class="rounded border px-4 py-2" onclick="document.getElementById('warehouse-item-edit-{{ $item->id }}').classList.add('hidden')">Hủy</button>
                                                            </div>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endif
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </section>
    @empty
        <div class="rounded border bg-white p-6 text-center text-slate-500">Chưa có kho vật tư.</div>
    @endforelse
</div>
