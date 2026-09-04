@php
    $canEditInventoryLog = auth()->user()?->isSuperAdmin() || \App\Support\PermissionCheck::can(auth()->user(), 'inventory.logs.edit');
    $canDeleteInventoryLog = auth()->user()?->isSuperAdmin() || \App\Support\PermissionCheck::can(auth()->user(), 'inventory.logs.delete');
@endphp

<div class="overflow-x-auto rounded border bg-white p-4">
    <h2 class="mb-2 font-semibold">Nhật ký hỏng / sửa chữa</h2>
    <table class="w-full min-w-[950px] text-left text-sm">
        <thead class="bg-slate-100">
            <tr>
                <th class="p-3">Thời gian</th>
                <th class="p-3">Mã</th>
                <th class="p-3">Tên vật tư</th>
                <th class="p-3">Số lượng</th>
                <th class="p-3">Trạng thái</th>
                <th class="p-3">Lý do</th>
                <th class="p-3">Người sửa</th>
                <th class="p-3">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($brokenLogs as $item)
                <tr class="border-t">
                    <td class="p-3">{{ $item->event_at?->format('d/m/Y H:i') }}</td>
                    <td class="p-3">{{ $item->asset_code ?: '-' }}</td>
                    <td class="p-3">{{ $item->asset_name }}</td>
                    <td class="p-3">{{ (float) $item->quantity }}</td>
                    <td class="p-3">{{ ['REPAIRING'=>'Đang sửa chữa','NORMAL'=>'Bình thường','BROKEN'=>'Hỏng'][$item->status_after] ?? $item->status_after }}</td>
                    <td class="p-3">{{ $item->reason ?: $item->result_note ?: '-' }}</td>
                    <td class="p-3">{{ $item->performer ?: $item->actor?->name ?: '-' }}</td>
                    <td class="p-3">
                        <div class="flex flex-wrap gap-2">
                            @if($canEditInventoryLog)
                                <button type="button" class="rounded bg-blue-600 px-3 py-1.5 text-xs font-bold text-white" onclick="document.getElementById('broken-log-edit-{{ $item->id }}').classList.toggle('hidden')">Sửa</button>
                            @endif
                            @if($canDeleteInventoryLog)
                                <form method="POST" action="{{ route('inventory.broken-logs.delete', $item) }}" onsubmit="return confirm('Xóa nhật ký hỏng / sửa chữa này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded bg-rose-600 px-3 py-1.5 text-xs font-bold text-white">Xóa</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @if($canEditInventoryLog)
                    <tr id="broken-log-edit-{{ $item->id }}" class="hidden bg-blue-50/60">
                        <td colspan="8" class="p-4">
                            <form method="POST" action="{{ route('inventory.broken-logs.update', $item) }}" class="grid gap-3 rounded border bg-white p-4 md:grid-cols-4">
                                @csrf
                                @method('PATCH')
                                <label class="text-sm font-semibold">Thời gian<input name="event_at" type="datetime-local" value="{{ $item->event_at?->format('Y-m-d\TH:i') }}" class="mt-1 w-full rounded border p-2"></label>
                                <label class="text-sm font-semibold">Loại sự kiện<input name="event_type" value="{{ $item->event_type }}" required class="mt-1 w-full rounded border p-2"></label>
                                <label class="text-sm font-semibold">Mã vật tư<input name="asset_code" value="{{ $item->asset_code }}" class="mt-1 w-full rounded border p-2"></label>
                                <label class="text-sm font-semibold">Số lượng<input name="quantity" type="number" min="0" step=".01" value="{{ $item->quantity }}" required class="mt-1 w-full rounded border p-2"></label>
                                <label class="text-sm font-semibold md:col-span-2">Tên vật tư<input name="asset_name" value="{{ $item->asset_name }}" required class="mt-1 w-full rounded border p-2"></label>
                                <label class="text-sm font-semibold">Trạng thái sau<input name="status_after" value="{{ $item->status_after }}" class="mt-1 w-full rounded border p-2"></label>
                                <label class="text-sm font-semibold">Người sửa<input name="performer" value="{{ $item->performer }}" class="mt-1 w-full rounded border p-2"></label>
                                <label class="text-sm font-semibold md:col-span-4">Lý do<textarea name="reason" rows="2" class="mt-1 w-full rounded border p-2">{{ $item->reason ?: $item->result_note }}</textarea></label>
                                <button class="w-fit rounded bg-blue-600 px-4 py-2 text-white">Lưu</button>
                            </form>
                        </td>
                    </tr>
                @endif
            @empty
                <tr><td colspan="8" class="p-5 text-center text-slate-500">Chưa có nhật ký hỏng.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
