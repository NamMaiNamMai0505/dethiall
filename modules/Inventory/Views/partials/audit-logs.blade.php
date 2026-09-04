@php
    $inventoryActionLabels = ['CREATE' => 'Thêm mới', 'UPDATE' => 'Cập nhật', 'DELETE' => 'Xóa', 'IMPORT' => 'Import', 'MOVEMENT' => 'Nhập / xuất', 'INCREASE' => 'Tăng', 'DECREASE' => 'Giảm', 'ADJUST' => 'Điều chỉnh', 'APPROVED' => 'Duyệt đề xuất', 'REJECTED' => 'Từ chối đề xuất', 'COMPLETED' => 'Hoàn thành đề xuất'];
    $canEditInventoryLog = auth()->user()?->isSuperAdmin() || \App\Support\PermissionCheck::can(auth()->user(), 'inventory.logs.edit');
    $canDeleteInventoryLog = auth()->user()?->isSuperAdmin() || \App\Support\PermissionCheck::can(auth()->user(), 'inventory.logs.delete');
@endphp
<div class="overflow-x-auto rounded border bg-white p-4">
    <h2 class="mb-3 font-semibold">Nhật ký cập nhật vật tư</h2>
    <table class="w-full min-w-[1150px] text-left text-sm">
        <thead class="bg-slate-100">
            <tr>
                <th class="p-3">Thời gian</th>
                <th class="p-3">Vật tư</th>
                <th class="p-3">Số lượng</th>
                <th class="p-3">Loại cập nhật</th>
                <th class="p-3">Lý do</th>
                <th class="p-3">Người thực hiện</th>
                <th class="p-3">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($auditLogs as $item)
                @php($details = is_array($item->details) ? $item->details : (json_decode($item->details ?: '{}', true) ?: []))
                @php($assetCode = data_get($details, 'asset_code', data_get($details, 'code', '—')))
                @php($assetName = data_get($details, 'name', data_get($details, 'material_name', '—')))
                @php($quantity = (int) data_get($details, 'quantity', abs((int) data_get($details, 'change', data_get($details, 'quantity_processed', 0)))))
                @php($reason = data_get($details, 'reason', data_get($details, 'note', data_get($details, 'decision_note', '—'))))
                <tr class="border-t">
                    <td class="p-3">{{ $item->created_at?->format('d/m/Y H:i') }}</td>
                    <td class="p-3">{{ $assetCode }} — {{ $assetName }}</td>
                    <td class="p-3">{{ $quantity }}</td>
                    <td class="p-3">{{ $inventoryActionLabels[$item->action] ?? $item->action }}</td>
                    <td class="p-3">{{ $reason }}</td>
                    <td class="p-3">{{ $item->user?->name ?: '—' }}</td>
                    <td class="p-3">
                        <div class="flex flex-wrap gap-2">
                            @if($canEditInventoryLog)
                                <button type="button" class="rounded bg-blue-600 px-3 py-1.5 text-xs font-bold text-white" onclick="document.getElementById('audit-log-edit-{{ $item->id }}').classList.toggle('hidden')">Sửa</button>
                            @endif
                            @if($canDeleteInventoryLog)
                                <form method="POST" action="{{ route('inventory.logs.delete', $item) }}" onsubmit="return confirm('Xóa dòng nhật ký vật tư này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded bg-rose-600 px-3 py-1.5 text-xs font-bold text-white">Xóa</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @if($canEditInventoryLog)
                    <tr id="audit-log-edit-{{ $item->id }}" class="hidden bg-blue-50/60">
                        <td colspan="7" class="p-4">
                            <form method="POST" action="{{ route('inventory.logs.update', $item) }}" class="grid gap-3 rounded border bg-white p-4 md:grid-cols-3">
                                @csrf
                                @method('PATCH')
                                <label class="text-sm font-semibold">Thời gian<input name="created_at" type="datetime-local" value="{{ $item->created_at?->format('Y-m-d\TH:i') }}" class="mt-1 w-full rounded border p-2"></label>
                                <label class="text-sm font-semibold">Loại cập nhật<input name="action" value="{{ $item->action }}" required class="mt-1 w-full rounded border p-2"></label>
                                <label class="text-sm font-semibold">Số lượng<input name="quantity" type="number" min="0" step=".01" value="{{ $quantity }}" class="mt-1 w-full rounded border p-2"></label>
                                <label class="text-sm font-semibold">Mã vật tư<input name="asset_code" value="{{ $assetCode === '—' ? '' : $assetCode }}" class="mt-1 w-full rounded border p-2"></label>
                                <label class="text-sm font-semibold md:col-span-2">Tên vật tư<input name="name" value="{{ $assetName === '—' ? '' : $assetName }}" class="mt-1 w-full rounded border p-2"></label>
                                <label class="text-sm font-semibold md:col-span-3">Lý do<textarea name="reason" rows="2" class="mt-1 w-full rounded border p-2">{{ $reason === '—' ? '' : $reason }}</textarea></label>
                                <button class="w-fit rounded bg-blue-600 px-4 py-2 text-white">Lưu nhật ký</button>
                            </form>
                        </td>
                    </tr>
                @endif
            @empty
                <tr><td colspan="7" class="p-5 text-center text-slate-500">Chưa có nhật ký cập nhật vật tư.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
