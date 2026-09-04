@php
    $repairStatusLabels = ['OPEN' => 'Đang xử lý', 'ASSIGNED' => 'Đã phân công', 'COMPLETED' => 'Đã hoàn thành', 'CANCELLED' => 'Đã hủy'];
    $canEditRepair = auth()->user()?->isSuperAdmin() || \App\Support\PermissionCheck::can(auth()->user(), 'inventory.repairs.edit');
    $canDeleteRepair = auth()->user()?->isSuperAdmin() || \App\Support\PermissionCheck::can(auth()->user(), 'inventory.repairs.delete');
@endphp

<div id="inventory-log-repairs" class="mt-4 overflow-x-auto rounded border bg-white p-4">
    <h2 class="mb-3 font-semibold">Lịch sử sửa chữa</h2>
    <table class="w-full min-w-[1000px] text-left text-sm">
        <thead class="bg-slate-100">
            <tr>
                <th class="p-3">Vật tư</th>
                <th class="p-3">Nội dung</th>
                <th class="p-3">Trạng thái</th>
                <th class="p-3">Người sửa</th>
                <th class="p-3">Ngày bắt đầu</th>
                <th class="p-3">Chi phí</th>
                <th class="p-3">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($repairLogs as $item)
                <tr class="border-t">
                    <td class="p-3">{{ $item->asset?->asset_code ?: '-' }} - {{ $item->asset?->name ?: '-' }}</td>
                    <td class="p-3">{{ $item->content ?: $item->result_note ?: '-' }}</td>
                    <td class="p-3">{{ $repairStatusLabels[$item->status] ?? $item->status }}</td>
                    <td class="p-3">{{ $item->performer ?: $item->assignee?->name ?: '-' }}</td>
                    <td class="p-3">{{ $item->started_at?->format('d/m/Y') ?: '-' }}</td>
                    <td class="p-3">{{ number_format((float) $item->cost, 0, ',', '.') }}</td>
                    <td class="p-3">
                        <div class="flex flex-wrap gap-2">
                            @if($canEditRepair)
                                <button type="button" class="rounded bg-blue-600 px-3 py-1.5 text-xs font-bold text-white" onclick="document.getElementById('repair-log-edit-{{ $item->id }}').classList.toggle('hidden')">Sửa</button>
                            @endif
                            @if($canDeleteRepair)
                                <form method="POST" action="{{ route('inventory.repairs.delete', $item) }}" onsubmit="return confirm('Xóa lịch sử sửa chữa này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded bg-rose-600 px-3 py-1.5 text-xs font-bold text-white">Xóa</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @if($canEditRepair)
                    <tr id="repair-log-edit-{{ $item->id }}" class="hidden bg-blue-50/60">
                        <td colspan="7" class="p-4">
                            <form method="POST" action="{{ route('inventory.repairs.update', $item) }}" class="grid gap-3 rounded border bg-white p-4 md:grid-cols-3">
                                @csrf
                                @method('PATCH')
                                <label class="text-sm font-semibold">Trạng thái<select name="status" class="mt-1 w-full rounded border p-2">@foreach($repairStatusLabels as $key => $label)<option value="{{ $key }}" @selected($item->status === $key)>{{ $label }}</option>@endforeach</select></label>
                                <label class="text-sm font-semibold">Người sửa<input name="performer" value="{{ $item->performer }}" class="mt-1 w-full rounded border p-2"></label>
                                <label class="text-sm font-semibold">Ngày bắt đầu<input name="started_at" type="date" value="{{ $item->started_at?->format('Y-m-d') }}" class="mt-1 w-full rounded border p-2"></label>
                                <label class="text-sm font-semibold">Chi phí<input name="cost" type="number" min="0" step="1" value="{{ $item->cost }}" class="mt-1 w-full rounded border p-2"></label>
                                <label class="text-sm font-semibold md:col-span-2">Nội dung<textarea name="content" rows="2" class="mt-1 w-full rounded border p-2">{{ $item->content }}</textarea></label>
                                <label class="text-sm font-semibold md:col-span-3">Ghi chú kết quả<textarea name="result_note" rows="2" class="mt-1 w-full rounded border p-2">{{ $item->result_note }}</textarea></label>
                                <button class="w-fit rounded bg-blue-600 px-4 py-2 text-white">Lưu</button>
                            </form>
                        </td>
                    </tr>
                @endif
            @empty
                <tr><td colspan="7" class="p-5 text-center text-slate-500">Chưa có lịch sử sửa chữa.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
