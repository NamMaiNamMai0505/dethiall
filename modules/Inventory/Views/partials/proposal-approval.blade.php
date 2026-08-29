<div class="space-y-4">
    <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-5">
        <h2 class="text-xl font-bold text-slate-900">Duyệt đề xuất</h2>
        <p class="mt-1 text-sm text-slate-600">Danh sách các đề xuất đang chờ duyệt và đã được duyệt.</p>
    </div>
    <div class="overflow-x-auto rounded-xl border bg-white shadow-sm">
        <table class="w-full min-w-[1100px] text-left text-sm">
            <thead class="bg-slate-100"><tr><th class="p-3">STT</th><th class="p-3">Loại đề xuất</th><th class="p-3">Tiêu đề</th><th class="p-3">Ngành</th><th class="p-3">Vật tư</th><th class="p-3">Đơn vị đề xuất</th><th class="p-3">Trạng thái</th><th class="p-3">Thao tác</th></tr></thead>
            <tbody>
            @forelse($proposals as $i => $proposal)
                @php($item = $proposal->items->first())
                <tr class="border-t align-top">
                    <td class="p-3">{{ $i + 1 }}</td>
                    <td class="p-3">{{ ['REPAIR'=>'Sửa chữa','RECALL'=>'Thu hồi / trả về kho','LIQUIDATION'=>'Thanh lý'][$proposal->type] ?? $proposal->type }}</td>
                    <td class="p-3 font-semibold">{{ $proposal->title }}<div class="mt-1 text-xs text-slate-500">{{ $proposal->description ?: '—' }}</div></td>
                    <td class="p-3">{{ $proposal->nganh_code ?: '—' }}</td>
                    <td class="p-3">{{ $item?->material_code ?: $item?->original_code ?: '—' }} — {{ $item?->material_name ?: $item?->name ?: '—' }}<div class="text-xs text-slate-500">Số lượng: {{ (int)($item?->quantity ?? 0) }}</div></td>
                    <td class="p-3">{{ $proposal->unit?->name ?: $proposal->proposed_by_display_name ?: '—' }}</td>
                    <td class="p-3">{{ ['PENDING'=>'Chờ duyệt','APPROVED'=>'Đã duyệt','REJECTED'=>'Từ chối','COMPLETED'=>'Đã hoàn thành'][$proposal->status] ?? $proposal->status }}</td>
                    <td class="p-3">
                        <a href="{{ route('inventory.proposals.detail',$proposal) }}" class="mb-2 inline-block rounded border border-slate-300 px-3 py-2 text-sm text-blue-700">Xem chi tiết</a>
                        @if($proposal->status === 'PENDING')
                            <div class="flex min-w-max flex-nowrap items-center gap-2 whitespace-nowrap"><form method="POST" action="{{ route('inventory.proposals.decide',$proposal) }}" class="inline-flex shrink-0">@csrf @method('PATCH')<button name="status" value="APPROVED" class="whitespace-nowrap rounded bg-emerald-600 px-3 py-2 font-semibold text-white">Duyệt</button></form><form method="POST" action="{{ route('inventory.proposals.decide',$proposal) }}" class="inline-flex shrink-0 items-center gap-2">@csrf @method('PATCH')<textarea name="decision_note" required minlength="3" rows="1" class="w-32 resize-none rounded border p-2 text-sm" placeholder="Lý do từ chối..."></textarea><button name="status" value="REJECTED" class="whitespace-nowrap rounded bg-rose-600 px-3 py-2 font-semibold text-white">Từ chối</button></form></div>
                        @elseif($proposal->status === 'APPROVED')
                            <form method="POST" action="{{ route('inventory.proposals.decide',$proposal) }}">@csrf @method('PATCH')<button name="status" value="COMPLETED" class="rounded bg-blue-600 px-3 py-2 font-semibold text-white">Hoàn thành</button></form>
                        @else <span class="text-slate-500">Đã xử lý</span> @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="p-8 text-center text-slate-500">Không có đề xuất cần duyệt.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="space-y-4">
    <div class="rounded-xl border border-amber-100 bg-amber-50 p-5">
        <h2 class="text-xl font-bold text-slate-900">Đề xuất điều động / thu hồi</h2>
        <p class="mt-1 text-sm text-slate-600">Các phiếu điều động và thu hồi cũng được duyệt trong quy trình đề xuất.</p>
    </div>
    <div class="overflow-x-auto rounded-xl border bg-white shadow-sm">
        <table class="w-full min-w-[1100px] text-left text-sm">
            <thead class="bg-slate-100"><tr><th class="p-3">STT</th><th class="p-3">Loại</th><th class="p-3">Vật tư</th><th class="p-3">Số lượng</th><th class="p-3">Phòng nguồn</th><th class="p-3">Phòng đích / kho</th><th class="p-3">Đơn vị quản lý</th><th class="p-3">Trạng thái</th><th class="p-3">Thao tác</th></tr></thead>
            <tbody>
            @forelse($transferProposals ?? [] as $i => $transfer)
                <tr class="border-t align-top">
                    <td class="p-3">{{ $i + 1 }}</td>
                    <td class="p-3">{{ $transfer->type === 'RECALL' ? 'Thu hồi' : 'Điều động' }}</td>
                    <td class="p-3">{{ $transfer->asset?->asset_code }} — {{ $transfer->asset?->name }}</td>
                    <td class="p-3 font-semibold">{{ (int)($transfer->quantity ?: 1) }}</td>
                    <td class="p-3">{{ $transfer->fromClassroom?->name ?: '—' }}</td>
                    <td class="p-3">{{ $transfer->type === 'RECALL' ? ($transfer->warehouse?->name ?: 'Kho vật tư') : ($transfer->toClassroom?->name ?: '—') }}</td>
                    <td class="p-3">{{ $transfer->using_unit ?: '—' }}</td>
                    <td class="p-3">{{ ['PENDING'=>'Chờ duyệt','APPROVED'=>'Đã duyệt'][$transfer->status] ?? $transfer->status }}</td>
                    <td class="p-3">
                        <a href="{{ route('inventory.transfers.detail',$transfer) }}" class="mb-2 inline-block rounded border border-slate-300 px-3 py-2 text-sm font-semibold text-blue-700">Xem chi tiết</a>
                        @if($transfer->status === 'PENDING' && $transfer->is_printed)
                            <div class="flex min-w-max flex-nowrap items-center gap-2 whitespace-nowrap"><form method="POST" action="{{ route('inventory.transfers.decide',$transfer) }}" class="inline-flex shrink-0">@csrf @method('PATCH')<button name="status" value="APPROVED" class="whitespace-nowrap rounded bg-emerald-600 px-3 py-2 font-semibold text-white">Duyệt</button></form><form method="POST" action="{{ route('inventory.transfers.decide',$transfer) }}" class="inline-flex shrink-0 items-center gap-2">@csrf @method('PATCH')<textarea name="decision_note" required minlength="3" rows="1" class="w-32 resize-none rounded border p-2 text-sm" placeholder="Lý do từ chối..."></textarea><button name="status" value="REJECTED" class="whitespace-nowrap rounded bg-rose-600 px-3 py-2 font-semibold text-white">Từ chối</button></form></div>
                        @elseif($transfer->status === 'APPROVED' && $transfer->is_printed)
                            <form method="POST" action="{{ route('inventory.transfers.decide',$transfer) }}">@csrf @method('PATCH')<button name="status" value="COMPLETED" class="rounded bg-blue-600 px-3 py-2 font-semibold text-white">Hoàn thành</button></form>
                        @else
                            <span class="text-sm text-amber-700">Chưa in quyết định</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="p-8 text-center text-slate-500">Không có đề xuất điều động / thu hồi cần duyệt.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
