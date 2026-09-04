@php
    $proposalTypeLabels = ['REPAIR' => 'Sửa chữa', 'RECALL' => 'Thu hồi / trả về kho', 'LIQUIDATION' => 'Thanh lý', 'PURCHASE' => 'Mua sắm'];
    $proposalStatusLabels = ['PENDING' => 'Chờ duyệt', 'APPROVED' => 'Đã duyệt', 'REJECTED' => 'Từ chối', 'COMPLETED' => 'Đã hoàn thành'];
    $canEditProposal = auth()->user()?->isSuperAdmin() || \App\Support\PermissionCheck::can(auth()->user(), 'inventory.proposals.edit');
    $canDeleteProposal = auth()->user()?->isSuperAdmin() || \App\Support\PermissionCheck::can(auth()->user(), 'inventory.proposals.delete');
@endphp
<div class="overflow-x-auto rounded border bg-white p-4">
    <h2 class="mb-3 font-semibold">Danh sách đề xuất nghiệp vụ vật tư</h2>
    <table class="w-full min-w-[1100px] table-fixed text-sm">
        <colgroup>
            <col class="w-[15%]"><col class="w-[22%]"><col class="w-[18%]"><col class="w-[14%]"><col class="w-[16%]"><col class="w-[15%]">
        </colgroup>
        <thead class="bg-slate-100">
            <tr class="align-middle">
                <th class="p-3 text-center">Loại đề xuất</th>
                <th class="p-3 text-left">Tiêu đề</th>
                <th class="p-3 text-center">Đơn vị đề xuất</th>
                <th class="p-3 text-center">Trạng thái</th>
                <th class="p-3 text-center">Quyết định</th>
                <th class="p-3 text-center">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($proposals as $proposal)
                <tr class="border-t align-middle">
                    <td class="p-3 text-center">{{ $proposalTypeLabels[$proposal->type] ?? 'Không xác định' }}</td>
                    <td class="p-3 text-left">{{ $proposal->title }}</td>
                    <td class="p-3 text-center">{{ $proposal->unit?->name ?: '—' }}</td>
                    <td class="p-3 text-center">{{ $proposalStatusLabels[$proposal->status] ?? 'Không xác định' }}</td>
                    <td class="p-3 text-center">
                        @if($proposal->status === 'PENDING')
                            <form method="POST" action="{{ route('inventory.proposals.decide', $proposal) }}" class="flex justify-center gap-1">
                                @csrf
                                @method('PATCH')
                                <button name="status" value="APPROVED" class="rounded bg-green-600 px-2 py-1 text-white">Duyệt</button>
                                <button name="status" value="REJECTED" class="rounded bg-red-600 px-2 py-1 text-white">Từ chối</button>
                            </form>
                        @elseif($proposal->decision_number)
                            {{ $proposal->decision_number }}
                        @else
                            —
                        @endif
                    </td>
                    <td class="p-3 text-center">
                        <div class="flex justify-center gap-2">
                            @if($canEditProposal)
                                <button type="button" class="rounded bg-blue-600 px-3 py-1.5 text-xs font-bold text-white" onclick="document.getElementById('proposal-edit-{{ $proposal->id }}').classList.toggle('hidden')">Sửa</button>
                            @endif
                            @if($canDeleteProposal)
                                <form method="POST" action="{{ route('inventory.proposals.delete', $proposal) }}" onsubmit="return confirm('Xóa đề xuất vật tư này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded bg-rose-600 px-3 py-1.5 text-xs font-bold text-white">Xóa</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @if($canEditProposal)
                    <tr id="proposal-edit-{{ $proposal->id }}" class="hidden bg-blue-50/60">
                        <td colspan="6" class="p-4">
                            <form method="POST" action="{{ route('inventory.proposals.update', $proposal) }}" class="grid gap-3 rounded border bg-white p-4 md:grid-cols-3">
                                @csrf
                                @method('PATCH')
                                <label class="text-sm font-semibold">Loại đề xuất<select name="type" class="mt-1 w-full rounded border p-2">@foreach($proposalTypeLabels as $key => $label)<option value="{{ $key }}" @selected($proposal->type === $key)>{{ $label }}</option>@endforeach</select></label>
                                <label class="text-sm font-semibold md:col-span-2">Tiêu đề<input name="title" value="{{ $proposal->title }}" required class="mt-1 w-full rounded border p-2"></label>
                                <label class="text-sm font-semibold">Đơn vị đề xuất<select name="unit_id" class="mt-1 w-full rounded border p-2"><option value="">Chưa chọn</option>@foreach(($units ?? collect()) as $unit)<option value="{{ $unit->id }}" @selected($proposal->unit_id == $unit->id)>{{ $unit->name }}</option>@endforeach</select></label>
                                <label class="text-sm font-semibold">Trạng thái<select name="status" class="mt-1 w-full rounded border p-2">@foreach($proposalStatusLabels as $key => $label)<option value="{{ $key }}" @selected($proposal->status === $key)>{{ $label }}</option>@endforeach</select></label>
                                <label class="text-sm font-semibold">Số quyết định<input name="decision_number" value="{{ $proposal->decision_number }}" class="mt-1 w-full rounded border p-2"></label>
                                <label class="text-sm font-semibold md:col-span-3">Mô tả / lý do<textarea name="description" rows="2" class="mt-1 w-full rounded border p-2">{{ $proposal->description }}</textarea></label>
                                <label class="text-sm font-semibold md:col-span-3">Ghi chú quyết định<textarea name="decision_note" rows="2" class="mt-1 w-full rounded border p-2">{{ $proposal->decision_note }}</textarea></label>
                                <button class="w-fit rounded bg-blue-600 px-4 py-2 text-white">Lưu thay đổi</button>
                            </form>
                        </td>
                    </tr>
                @endif
            @empty
                <tr><td colspan="6" class="p-5 text-center text-slate-500">Chưa có đề xuất.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
