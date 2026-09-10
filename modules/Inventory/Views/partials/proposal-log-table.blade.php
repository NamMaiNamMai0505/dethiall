@php
    $proposalStatusLabels = ['PENDING'=>'Chờ duyệt','APPROVED'=>'Đã duyệt','REJECTED'=>'Từ chối','COMPLETED'=>'Đã hoàn thành'];
    $proposalTypeLabels = ['LIQUIDATION'=>'Thanh lý', 'REPAIR'=>'Sửa chữa'];
    $canEditProposal = auth()->user()?->isSuperAdmin() || \App\Support\PermissionCheck::can(auth()->user(), 'inventory.proposals.edit');
    $canDeleteProposal = auth()->user()?->isSuperAdmin() || \App\Support\PermissionCheck::can(auth()->user(), 'inventory.proposals.delete');
@endphp

<div id="inventory-log-proposals" class="mt-4 overflow-x-auto rounded border bg-white p-4">
    <h2 class="mb-3 font-semibold">Đề xuất thanh lý / sửa chữa</h2>
    <table class="w-full min-w-[1300px] text-left text-sm">
        <thead class="bg-slate-100">
            <tr>
                <th class="p-3">Thời gian</th>
                <th class="p-3">Loại</th>
                <th class="p-3">Vật tư</th>
                <th class="p-3">Số lượng</th>
                <th class="p-3">Lý do</th>
                <th class="p-3">Đơn vị đề xuất</th>
                <th class="p-3">Người duyệt</th>
                <th class="p-3">Số quyết định</th>
                <th class="p-3">Trạng thái</th>
                <th class="p-3">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($proposalLogs as $proposal)
                @php($item = $proposal->items->first())
                @php($proposalTypeLabel = $proposalTypeLabels[$proposal->type] ?? $proposal->type)
                @php($proposalFilterText = \Illuminate\Support\Str::lower($proposal->type.' '.$proposalTypeLabel.' '.($proposal->created_at?->format('Y-m-d d/m/Y') ?? '')))
                <tr class="border-t" data-filter-text="{{ $proposalFilterText }}" data-proposal-type="{{ \Illuminate\Support\Str::lower($proposal->type) }}" data-status="{{ \Illuminate\Support\Str::lower($proposal->status) }}" data-unit-id="{{ $proposal->unit_id }}">
                    <td class="p-3">{{ $proposal->created_at?->format('d/m/Y H:i') }}</td>
                    <td class="p-3">{{ $proposalTypeLabel }}</td>
                    <td class="p-3">{{ $item?->material_code ?: $item?->original_code ?: '-' }} - {{ $item?->material_name ?: $item?->name ?: $proposal->title }}</td>
                    <td class="p-3">{{ (int)($item?->quantity ?: 0) }}</td>
                    <td class="p-3">{{ $proposal->description ?: $item?->note ?: '-' }}</td>
                    <td class="p-3">{{ $proposal->unit?->name ?: '-' }}</td>
                    <td class="p-3">{{ $proposal->decidedBy?->name ?: '-' }}</td>
                    <td class="p-3">{{ $proposal->decision_number ?: '-' }}</td>
                    <td class="p-3">{{ $proposalStatusLabels[$proposal->status] ?? $proposal->status }}</td>
                    <td class="p-3">
                        <div class="flex flex-wrap gap-2">
                            @if($canEditProposal)
                                <button type="button" class="rounded bg-blue-600 px-3 py-1.5 text-xs font-bold text-white" onclick="document.getElementById('proposal-log-edit-{{ $proposal->id }}').classList.toggle('hidden')">Sửa</button>
                            @endif
                            @if($canDeleteProposal)
                                <form method="POST" action="{{ route('inventory.proposals.delete', $proposal) }}" onsubmit="return confirm('Xóa đề xuất / thanh lý này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded bg-rose-600 px-3 py-1.5 text-xs font-bold text-white">Xóa</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @if($canEditProposal)
                    <tr id="proposal-log-edit-{{ $proposal->id }}" class="hidden bg-blue-50/60">
                        <td colspan="10" class="p-4">
                            <form method="POST" action="{{ route('inventory.proposals.update', $proposal) }}" class="grid gap-3 rounded border bg-white p-4 md:grid-cols-3">
                                @csrf
                                @method('PATCH')
                                <label class="text-sm font-semibold">Loại đề xuất<select name="type" class="mt-1 w-full rounded border p-2">@foreach($proposalTypeLabels as $key=>$label)<option value="{{ $key }}" @selected($proposal->type === $key)>{{ $label }}</option>@endforeach</select></label>
                                <label class="text-sm font-semibold md:col-span-2">Tiêu đề<input name="title" value="{{ $proposal->title }}" required class="mt-1 w-full rounded border p-2"></label>
                                <label class="text-sm font-semibold">Trạng thái<select name="status" class="mt-1 w-full rounded border p-2">@foreach($proposalStatusLabels as $key=>$label)<option value="{{ $key }}" @selected($proposal->status === $key)>{{ $label }}</option>@endforeach</select></label>
                                <input type="hidden" name="unit_id" value="{{ $proposal->unit_id }}">
                                <label class="text-sm font-semibold">Số quyết định<input name="decision_number" value="{{ $proposal->decision_number }}" class="mt-1 w-full rounded border p-2"></label>
                                <label class="text-sm font-semibold md:col-span-2">Lý do<textarea name="description" rows="2" class="mt-1 w-full rounded border p-2">{{ $proposal->description }}</textarea></label>
                                <label class="text-sm font-semibold md:col-span-3">Ghi chú quyết định<textarea name="decision_note" rows="2" class="mt-1 w-full rounded border p-2">{{ $proposal->decision_note }}</textarea></label>
                                <button class="w-fit rounded bg-blue-600 px-4 py-2 text-white">Lưu</button>
                            </form>
                        </td>
                    </tr>
                @endif
            @empty
                <tr><td colspan="10" class="p-5 text-center text-slate-500">Chưa có đề xuất.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
