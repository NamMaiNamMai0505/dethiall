@php
    $canEditTransfer = auth()->user()?->isSuperAdmin() || \App\Support\PermissionCheck::can(auth()->user(), 'inventory.transfers.edit');
    $canDeleteTransfer = auth()->user()?->isSuperAdmin() || \App\Support\PermissionCheck::can(auth()->user(), 'inventory.transfers.delete');
@endphp

<div id="inventory-log-transfers" class="mt-4 overflow-x-auto rounded border bg-white p-4">
    <h2 class="mb-3 font-semibold">Điều động / thu hồi</h2>
    <table class="w-full min-w-[1250px] text-left text-sm">
        <thead class="bg-slate-100">
            <tr>
                <th class="p-3">Thời gian</th>
                <th class="p-3">Vật tư</th>
                <th class="p-3">Số lượng</th>
                <th class="p-3">Phòng nguồn</th>
                <th class="p-3">Phòng đích</th>
                <th class="p-3">Đơn vị thực hiện</th>
                <th class="p-3">Số quyết định</th>
                <th class="p-3">Trạng thái</th>
                <th class="p-3">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transferLogs as $item)
                <tr class="border-t">
                    <td class="p-3">{{ ($item->performed_at ?: $item->created_at)?->format('d/m/Y H:i') }}</td>
                    <td class="p-3">{{ $item->asset?->asset_code ?: $item->material?->code ?: '-' }} - {{ $item->asset?->name ?: $item->material?->name ?: '-' }}</td>
                    <td class="p-3">{{ (int)($item->quantity ?: $item->asset?->quantity ?: $item->material?->quantity ?: 0) }}</td>
                    <td class="p-3">{{ $item->fromClassroom?->name ?: '-' }}</td>
                    <td class="p-3">{{ $item->type === 'RECALL' ? ($item->warehouse?->name ?: 'Kho vật tư') : ($item->toClassroom?->name ?: '-') }}</td>
                    <td class="p-3">{{ $item->performing_unit ?: '-' }}</td>
                    <td class="p-3">{{ $item->decision_number ?: '-' }}</td>
                    <td class="p-3">{{ ['PENDING'=>'Chờ duyệt','APPROVED'=>'Đã duyệt','REJECTED'=>'Từ chối','COMPLETED'=>'Đã hoàn thành'][$item->status] ?? $item->status }}</td>
                    <td class="p-3">
                        <div class="flex flex-wrap gap-2">
                            @if($canEditTransfer)
                                <button type="button" class="rounded bg-blue-600 px-3 py-1.5 text-xs font-bold text-white" onclick="document.getElementById('transfer-log-edit-{{ $item->id }}').classList.toggle('hidden')">Sửa</button>
                            @endif
                            @if($canDeleteTransfer)
                                <form method="POST" action="{{ route('inventory.transfers.delete', $item) }}" onsubmit="return confirm('Xóa phiếu điều động / thu hồi này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded bg-rose-600 px-3 py-1.5 text-xs font-bold text-white">Xóa</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @if($canEditTransfer)
                    <tr id="transfer-log-edit-{{ $item->id }}" class="hidden bg-blue-50/60">
                        <td colspan="9" class="p-4">
                            <form method="POST" action="{{ route('inventory.transfers.update', $item) }}" class="grid gap-3 rounded border bg-white p-4 md:grid-cols-3">
                                @csrf
                                @method('PATCH')
                                <label class="text-sm font-semibold">Đơn vị thực hiện<input name="performing_unit" value="{{ $item->performing_unit }}" required class="mt-1 w-full rounded border p-2"></label>
                                <label class="text-sm font-semibold">Đơn vị đề nghị<input name="requesting_unit" value="{{ $item->requesting_unit ?: '' }}" required class="mt-1 w-full rounded border p-2"></label>
                                <label class="text-sm font-semibold">Số quyết định<input name="decision_number" value="{{ $item->decision_number }}" class="mt-1 w-full rounded border p-2"></label>
                                <label class="text-sm font-semibold">Ngày quyết định<input name="decision_date" type="date" value="{{ $item->decision_date?->format('Y-m-d') }}" class="mt-1 w-full rounded border p-2"></label>
                                <label class="text-sm font-semibold">Người ký<input name="signer" value="{{ $item->signer }}" class="mt-1 w-full rounded border p-2"></label>
                                <label class="text-sm font-semibold">Đơn vị quản lý / giữ<input name="using_unit" value="{{ $item->using_unit }}" class="mt-1 w-full rounded border p-2"></label>
                                <label class="text-sm font-semibold md:col-span-3">Ghi chú<textarea name="general_note" rows="2" class="mt-1 w-full rounded border p-2">{{ $item->general_note }}</textarea></label>
                                <button class="w-fit rounded bg-blue-600 px-4 py-2 text-white">Lưu</button>
                            </form>
                        </td>
                    </tr>
                @endif
            @empty
                <tr><td colspan="9" class="p-5 text-center text-slate-500">Chưa có phiếu điều động / thu hồi.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
