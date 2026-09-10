@php
    $canPrintProposals = \App\Support\PermissionCheck::userCan('inventory.proposals.export');
    $canEditProposals = auth()->user()?->isSuperAdmin() || \App\Support\PermissionCheck::userCan('inventory.proposals.edit');
    $canDeleteProposals = auth()->user()?->isSuperAdmin() || \App\Support\PermissionCheck::userCan('inventory.proposals.delete');
    $proposalTypeLabels = $proposalTypeLabels ?? ['LIQUIDATION' => 'Thanh lý', 'REPAIR' => 'Sửa chữa'];
    $digitalSignature = auth()->id()
        ? \App\Models\DigitalSignature::query()
            ->active()
            ->forUser((int) auth()->id())
            ->orderByDesc('is_default')
            ->orderBy('sort_order')
            ->orderByDesc('id')
            ->first()
        : null;
    $hasDigitalSignature = (bool) ($digitalSignature?->imageUrl());
@endphp

<div class="space-y-4">
    <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-5">
        <h2 class="text-xl font-bold text-slate-900">Duyệt đề xuất thanh lý / sửa chữa</h2>
        <p class="mt-1 text-sm text-slate-600">Danh sách các đề xuất thanh lý và sửa chữa đang chờ duyệt.</p>
    </div>
    <div class="overflow-x-auto rounded-xl border bg-white shadow-sm">
        <table class="w-full min-w-[1100px] text-left text-sm">
            <thead class="bg-slate-100"><tr><th class="p-3">STT</th><th class="p-3">Loại đề xuất</th><th class="p-3">Tiêu đề</th><th class="p-3">Ngành</th><th class="p-3">Vật tư</th><th class="p-3">Đơn vị đề xuất</th><th class="p-3">Trạng thái</th><th class="p-3">Thao tác</th></tr></thead>
            <tbody>
            @forelse($proposals as $i => $proposal)
                @php($item = $proposal->items->first())
                <tr class="border-t align-top">
                    <td class="p-3">{{ $i + 1 }}</td>
                    <td class="p-3">{{ $proposalTypeLabels[$proposal->type] ?? $proposal->type }}</td>
                    <td class="p-3 font-semibold">{{ $proposal->title }}<div class="mt-1 text-xs text-slate-500">{{ $proposal->description ?: '—' }}</div></td>
                    <td class="p-3">{{ $proposal->nganh_code ?: '—' }}</td>
                    <td class="p-3">{{ $item?->material_code ?: $item?->original_code ?: '—' }} — {{ $item?->material_name ?: $item?->name ?: '—' }}<div class="text-xs text-slate-500">Số lượng: {{ (int)($item?->quantity ?? 0) }}</div></td>
                    <td class="p-3">{{ $proposal->unit?->name ?: $proposal->proposed_by_display_name ?: '—' }}</td>
                    <td class="p-3">{{ ['PENDING'=>'Chờ duyệt','APPROVED'=>'Đã duyệt','REJECTED'=>'Từ chối','COMPLETED'=>'Đã hoàn thành'][$proposal->status] ?? $proposal->status }}</td>
                    <td class="p-3">
                        <a href="{{ route('inventory.proposals.detail',$proposal) }}" class="mb-2 inline-block rounded border border-slate-300 px-3 py-2 text-sm text-blue-700">Xem chi tiết</a>
                        @if($proposal->status === 'PENDING')
                            <div class="flex min-w-[460px] flex-nowrap items-center gap-2 whitespace-nowrap">
                                @if($canPrintProposals)
                                    <form method="POST" action="{{ route('inventory.proposals.print',$proposal) }}" target="_blank" class="inline-flex shrink-0" data-proposal-preview-print-form data-proposal-id="{{ $proposal->id }}">
                                        @csrf
                                        <input type="hidden" name="print_mode" value="preview">
                                        <button class="rounded bg-slate-900 px-3 py-2 font-semibold text-white">In xem trước</button>
                                    </form>
                                @endif
                                <form method="POST" action="{{ route('inventory.proposals.decide',$proposal) }}" class="{{ $proposal->printed_at ? 'inline-flex' : 'hidden' }} shrink-0" data-proposal-approve-form="{{ $proposal->id }}">
                                    @csrf @method('PATCH')
                                    <button name="status" value="APPROVED" class="whitespace-nowrap rounded bg-emerald-600 px-3 py-2 font-semibold text-white">Duyệt</button>
                                </form>
                                <span class="{{ $proposal->printed_at ? 'hidden' : '' }} text-sm text-amber-700" data-proposal-print-wait="{{ $proposal->id }}">Chờ in phiếu</span>
                                <form method="POST" action="{{ route('inventory.proposals.decide',$proposal) }}" class="inline-flex min-w-0 flex-1 items-center gap-2">
                                    @csrf @method('PATCH')
                                    <input name="decision_note" required minlength="3" class="min-w-[150px] flex-1 rounded border p-2 text-sm" placeholder="Lý do từ chối">
                                    <button name="status" value="REJECTED" class="whitespace-nowrap rounded bg-rose-600 px-3 py-2 font-semibold text-white">Từ chối</button>
                                </form>
                            </div>
                        @elseif($proposal->status === 'APPROVED')
                            <div class="flex min-w-max flex-nowrap items-center gap-2 whitespace-nowrap">
                                @if($canPrintProposals && $hasDigitalSignature)
                                    <form method="POST" action="{{ route('inventory.proposals.print',$proposal) }}" target="_blank" class="inline-flex shrink-0">
                                        @csrf
                                        <input type="hidden" name="print_mode" value="digital">
                                        <button class="rounded bg-blue-600 px-3 py-2 font-semibold text-white">In chữ ký số</button>
                                    </form>
                                @elseif($canPrintProposals)
                                    <a href="{{ route('inventory.proposals.detail',$proposal) }}" class="rounded border border-blue-200 bg-white px-3 py-2 text-sm font-semibold text-blue-700">Ký / in</a>
                                @endif
                                @if($proposal->printed_at)
                                    <form method="POST" action="{{ route('inventory.proposals.decide', $proposal) }}">@csrf @method('PATCH')<button name="status" value="COMPLETED" class="rounded bg-indigo-600 px-3 py-2 font-semibold text-white">Hoàn thành</button></form>
                                @else
                                    <span class="text-sm text-amber-700">Chờ in phiếu</span>
                                @endif
                                @if($proposal->type !== 'REPAIR' && $proposal->printed_at)
                                    <form method="POST" action="{{ route('inventory.proposals.decide', $proposal) }}">@csrf @method('PATCH')<button name="status" value="COMPLETED" class="rounded bg-indigo-600 px-3 py-2 font-semibold text-white">Hoàn thành</button></form>
                                @endif
                            </div>
                        @else <span class="text-slate-500">Đã xử lý</span> @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="p-8 text-center text-slate-500">Không có đề xuất cần duyệt.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-5">
        <h2 class="text-xl font-bold text-slate-900">Đề xuất đã xử lý</h2>
        <p class="mt-1 text-sm text-slate-600">Các phiếu đã duyệt, từ chối hoặc hoàn thành được lưu lại tại đây.</p>
    </div>
    <div class="rounded-xl border bg-white p-4 shadow-sm">
        <div class="grid gap-3 md:grid-cols-3">
            <label class="text-sm font-semibold text-slate-700">Loại đề xuất
                <select id="decided-proposal-type-filter" class="mt-1 w-full rounded-lg border-slate-300 px-3 py-2 text-sm">
                    <option value="">Tất cả</option>
                    @foreach($proposalTypeLabels as $typeKey => $typeLabel)
                        <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-slate-700">Trạng thái
                <select id="decided-proposal-status-filter" class="mt-1 w-full rounded-lg border-slate-300 px-3 py-2 text-sm">
                    <option value="">Tất cả</option>
                    <option value="APPROVED">Đã duyệt</option>
                    <option value="REJECTED">Từ chối</option>
                    <option value="COMPLETED">Đã hoàn thành</option>
                </select>
            </label>
            <label class="text-sm font-semibold text-slate-700">Phân công
                <select id="decided-proposal-assignment-filter" class="mt-1 w-full rounded-lg border-slate-300 px-3 py-2 text-sm">
                    <option value="">Tất cả</option>
                    <option value="Đã phân công">Đã phân công</option>
                    <option value="Chưa được phân công">Chưa được phân công</option>
                    <option value="Đã hoàn thành">Đã hoàn thành</option>
                    <option value="—">Không áp dụng</option>
                </select>
            </label>
        </div>
    </div>
    <div class="overflow-x-auto rounded-xl border bg-white shadow-sm">
        <table class="w-full min-w-[1240px] text-left text-sm">
            <thead class="bg-slate-100"><tr><th class="p-3">STT</th><th class="p-3">Loại đề xuất</th><th class="p-3">Tiêu đề</th><th class="p-3">Vật tư</th><th class="p-3">Đơn vị đề xuất</th><th class="p-3">Người duyệt</th><th class="p-3">Trạng thái</th><th class="p-3">Phân công</th><th class="p-3">Thao tác</th></tr></thead>
            <tbody>
            @forelse($decidedProposals ?? [] as $i => $proposal)
                <?php
                    $repair = ($repairByProposalId ?? collect())->get($proposal->id);
                    $assignmentLabel = $proposal->type === 'REPAIR'
                        ? (['COMPLETED' => 'Đã hoàn thành', 'ASSIGNED' => 'Đã phân công', 'CANCELLED' => 'Đã hủy'][$repair?->status] ?? 'Chưa được phân công')
                        : '—';
                ?>
                <tr class="border-t align-top" data-decided-proposal-row data-type="{{ $proposal->type }}" data-status="{{ $proposal->status }}" data-assignment="{{ $assignmentLabel }}">
                    <td class="p-3">{{ $i + 1 }}</td>
                    <td class="p-3">{{ $proposalTypeLabels[$proposal->type] ?? $proposal->type }}</td>
                    <td class="p-3 font-semibold">{{ $proposal->title }}<div class="mt-1 text-xs text-slate-500">{{ $proposal->description ?: '—' }}</div></td>
                    <td class="p-3">{{ $proposal->items->first()?->material_code ?: $proposal->items->first()?->original_code ?: '—' }} — {{ $proposal->items->first()?->material_name ?: $proposal->items->first()?->name ?: '—' }}<div class="text-xs text-slate-500">Số lượng: {{ (int)($proposal->items->first()?->quantity ?? 0) }}</div></td>
                    <td class="p-3">{{ $proposal->unit?->name ?: $proposal->proposed_by_display_name ?: '—' }}</td>
                    <td class="p-3">{{ $proposal->decidedBy?->name ?: '—' }}</td>
                    <td class="p-3">{{ ['PENDING'=>'Chờ duyệt','APPROVED'=>'Đã duyệt','REJECTED'=>'Từ chối','COMPLETED'=>'Đã hoàn thành'][$proposal->status] ?? $proposal->status }}</td>
                    <td class="p-3">
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $assignmentLabel === 'Đã hoàn thành' ? 'bg-emerald-100 text-emerald-700' : ($assignmentLabel === 'Đã phân công' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700') }}">{{ $assignmentLabel }}</span>
                        @if($repair?->assignee || $repair?->performer)
                            <div class="mt-1 text-xs text-slate-500">{{ $repair->performer ?: $repair->assignee?->name }}</div>
                        @endif
                    </td>
                    <td class="p-3">
                        <div class="flex flex-nowrap items-center gap-2 whitespace-nowrap">
                            @if($canPrintProposals && in_array($proposal->status, ['APPROVED','COMPLETED'], true))
                                @if($proposal->signature_path)
                                    <a href="{{ route('inventory.proposals.print.pdf', ['proposal' => $proposal, 'print_mode' => 'saved']) }}" target="_blank" class="rounded bg-blue-600 px-3 py-2 font-semibold text-white">In</a>
                                @else
                                    <a href="{{ route('inventory.proposals.detail', $proposal) }}" class="rounded bg-blue-600 px-3 py-2 font-semibold text-white">In</a>
                                @endif
                            @elseif($proposal->status === 'REJECTED')
                                <span class="rounded border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-400">Không in</span>
                            @endif
                            @if($canEditProposals)
                                <button type="button" class="rounded bg-slate-700 px-3 py-2 font-semibold text-white" onclick="document.getElementById('decided-proposal-edit-{{ $proposal->id }}').classList.toggle('hidden')">Sửa</button>
                            @endif
                            @if($canDeleteProposals)
                                <form method="POST" action="{{ route('inventory.proposals.delete', $proposal) }}" onsubmit="return confirm('Xóa đề xuất này?');" class="inline-flex">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded bg-rose-600 px-3 py-2 font-semibold text-white">Xóa</button>
                                </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @if($canEditProposals)
                    <tr id="decided-proposal-edit-{{ $proposal->id }}" class="hidden bg-blue-50/60">
                        <td colspan="9" class="p-4">
                            <form method="POST" action="{{ route('inventory.proposals.update', $proposal) }}" class="grid gap-3 rounded border bg-white p-4 md:grid-cols-3">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="type" value="{{ $proposal->type }}">
                                <input type="hidden" name="unit_id" value="{{ $proposal->unit_id }}">
                                <input type="hidden" name="status" value="{{ $proposal->status }}">
                                <label class="text-sm font-semibold md:col-span-2">Tiêu đề
                                    <input name="title" value="{{ $proposal->title }}" required class="mt-1 w-full rounded border p-2">
                                </label>
                                <label class="text-sm font-semibold">Số quyết định
                                    <input name="decision_number" value="{{ $proposal->decision_number }}" class="mt-1 w-full rounded border p-2">
                                </label>
                                <label class="text-sm font-semibold md:col-span-3">Lý do / tình trạng
                                    <textarea name="description" rows="2" class="mt-1 w-full rounded border p-2">{{ $proposal->description }}</textarea>
                                </label>
                                <label class="text-sm font-semibold md:col-span-3">Ghi chú quyết định
                                    <textarea name="decision_note" rows="2" class="mt-1 w-full rounded border p-2">{{ $proposal->decision_note }}</textarea>
                                </label>
                                <button class="w-fit rounded bg-blue-600 px-4 py-2 font-semibold text-white">Lưu sửa</button>
                            </form>
                        </td>
                    </tr>
                @endif
            @empty
                <tr><td colspan="9" class="p-8 text-center text-slate-500">Chưa có đề xuất đã xử lý.</td></tr>
            @endforelse
                <tr id="decided-proposal-empty-filter-row" class="hidden"><td colspan="9" class="p-8 text-center text-slate-500">Không có đề xuất phù hợp bộ lọc.</td></tr>
            </tbody>
        </table>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-proposal-preview-print-form]').forEach(form => {
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            const button = form.querySelector('button');
            const proposalId = form.dataset.proposalId;
            const originalText = button ? button.textContent : '';
            if (button) {
                button.disabled = true;
                button.textContent = 'Đang in...';
            }
            try {
                const response = await fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                if (!response.ok) {
                    const text = await response.text();
                    throw new Error(text.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim() || 'Không thể in phiếu.');
                }
                const payload = await response.json();
                if (payload.url) window.open(payload.url, '_blank');
                document.querySelector(`[data-proposal-approve-form="${proposalId}"]`)?.classList.remove('hidden');
                document.querySelector(`[data-proposal-approve-form="${proposalId}"]`)?.classList.add('inline-flex');
                document.querySelector(`[data-proposal-print-wait="${proposalId}"]`)?.classList.add('hidden');
            } catch (error) {
                alert(error.message || 'Không thể in phiếu.');
            } finally {
                if (button) {
                    button.disabled = false;
                    button.textContent = originalText;
                }
            }
        });
    });

    const type = document.getElementById('decided-proposal-type-filter');
    const status = document.getElementById('decided-proposal-status-filter');
    const assignment = document.getElementById('decided-proposal-assignment-filter');
    const rows = Array.from(document.querySelectorAll('[data-decided-proposal-row]'));
    const emptyRow = document.getElementById('decided-proposal-empty-filter-row');
    if (!type || !status || !assignment || !emptyRow) return;

    const applyFilter = () => {
        let shown = 0;
        rows.forEach(row => {
            const visible = (!type.value || row.dataset.type === type.value)
                && (!status.value || row.dataset.status === status.value)
                && (!assignment.value || row.dataset.assignment === assignment.value);
            row.classList.toggle('hidden', !visible);
            if (visible) shown += 1;
        });
        emptyRow.classList.toggle('hidden', shown > 0);
    };

    [type, status, assignment].forEach(control => control.addEventListener('change', applyFilter));
});
</script>
<div class="space-y-4">
    <div class="rounded-xl border border-amber-100 bg-amber-50 p-5">
        <h2 class="text-xl font-bold text-slate-900">Điều động / thu hồi</h2>
        <p class="mt-1 text-sm text-slate-600">Các phiếu điều động và thu hồi được duyệt theo quy trình riêng.</p>
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
