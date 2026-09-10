<div class="rounded-2xl border bg-white p-5">
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h2 class="text-lg font-bold">Phân công sửa chữa</h2>
            <p class="text-sm text-slate-500">Chọn phiếu báo hỏng hoặc đề xuất sửa chữa đã duyệt để phân công kỹ thuật viên/người đi sửa.</p>
        </div>
        <a href="{{ route('inventory.reports') }}" class="rounded-lg border px-3 py-2 text-sm">Danh mục</a>
    </div>

    <form method="GET" class="mb-4">
        <label class="text-sm font-semibold">Lọc trạng thái
            <select name="status" class="mt-1 rounded-lg border px-3 py-2">
                <option value="" @selected(($status ?? request('status')) === null || ($status ?? request('status')) === '')>Đang mở (chờ + đang xử lý)</option>
                <option value="COMPLETED" @selected(($status ?? request('status')) === 'COMPLETED')>Đã hoàn thành</option>
                <option value="CANCELLED" @selected(($status ?? request('status')) === 'CANCELLED')>Đã hủy</option>
            </select>
        </label>
    </form>

    <div class="overflow-x-auto">
        <table class="w-full min-w-[950px] text-left text-sm">
            <thead class="border-b">
                <tr>
                    <th class="p-3">TT</th>
                    <th class="p-3">Thiết bị</th>
                    <th class="p-3">SL hỏng</th>
                    <th class="p-3">Phòng / Tầng / Tòa</th>
                    <th class="p-3">Ngày hư</th>
                    <th class="p-3">Nguồn / người đề nghị</th>
                    <th class="p-3">Người sửa</th>
                    <th class="p-3">Bắt đầu sửa</th>
                    <th class="p-3">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @forelse($repairs as $i => $repair)
                    @php($repairBreakReport = ($repairBreakReports ?? collect())->get($repair->source_id))
                    <tr class="border-b">
                        <td class="p-3">{{ $i + 1 }}</td>
                        <td class="p-3">
                            <div class="font-semibold">{{ $repair->asset?->name ?: '—' }}</div>
                            <div class="text-xs text-slate-500">{{ $repair->asset?->asset_code }}</div>
                        </td>
                        <td class="p-3">{{ (float) ($repairBreakReport?->quantity ?: $repair->asset?->broken_quantity ?: $repair->asset?->quantity ?: 0) }}</td>
                        <td class="p-3">{{ $repair->asset?->classroom?->name ?: '—' }}</td>
                        <td class="p-3">{{ $repair->opened_at?->format('d/m/Y') ?: '—' }}</td>
                        <td class="p-3">
                            <div>{{ $repair->source_type === 'PROPOSAL_REPAIR' ? 'Đề xuất sửa chữa' : 'Báo hỏng' }}</div>
                            <div class="text-xs text-slate-500">{{ $repair->requestedBy?->name ?: '—' }}</div>
                        </td>
                        <td class="p-3">{{ $repair->performer ?: $repair->assignee?->name ?: '—' }}</td>
                        <td class="p-3">{{ $repair->started_at?->format('d/m/Y') ?: '—' }}</td>
                        <td class="p-3">
                            @if($repair->status === 'OPEN')
                                <button type="button" class="rounded bg-slate-900 px-3 py-2 text-white" data-repair-open="{{ $repair->id }}">Phân công</button>
                            @elseif($repair->status === 'ASSIGNED')
                                <button type="button" class="rounded bg-slate-900 px-3 py-2 text-white" data-repair-open="{{ $repair->id }}">Sửa phân công</button>
                                <button type="button" class="mt-2 rounded border px-3 py-2" data-repair-complete-open="{{ $repair->id }}">Hoàn thành</button>
                            @endif
                        </td>
                    </tr>
                    <tr id="repair-modal-{{ $repair->id }}" class="hidden">
                        <td colspan="9" class="p-4">
                            <form method="POST" action="{{ route('inventory.repairs.assign', $repair) }}" class="grid gap-3 rounded-xl border bg-slate-50 p-4 md:grid-cols-3">
                                @csrf
                                @method('PATCH')
                                <label class="text-sm font-semibold">Tài khoản người sửa
                                    <select name="assigned_to" class="mt-1 w-full rounded-lg border p-2" data-repair-assignee-select="{{ $repair->id }}">
                                        <option value="">Chọn tài khoản</option>
                                        @foreach(($users ?? collect()) as $user)
                                            <option value="{{ $user->id }}" data-name="{{ $user->name }}" @selected((int) $repair->assigned_to === (int) $user->id)>{{ $user->name }} — {{ $user->email }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="text-sm font-semibold">Người đi sửa
                                    <input name="performer" value="{{ $repair->performer }}" placeholder="Nhập người đi sửa" class="mt-1 w-full rounded-lg border p-2" data-repair-performer-input="{{ $repair->id }}">
                                </label>
                                <label class="text-sm font-semibold">Ngày bắt đầu sửa
                                    <input name="started_at" type="date" value="{{ optional($repair->started_at)->toDateString() ?: now()->toDateString() }}" class="mt-1 w-full rounded-lg border p-2">
                                </label>
                                <div class="flex items-end">
                                    <button class="w-fit rounded bg-blue-600 px-4 py-2 text-white">Xác nhận phân công</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                    <tr id="repair-complete-{{ $repair->id }}" class="hidden">
                        <td colspan="9" class="p-4">
                            <form method="POST" action="{{ route('inventory.repairs.complete', $repair) }}" class="grid gap-3 rounded-xl border bg-emerald-50 p-4 md:grid-cols-3">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="status" value="COMPLETED">
                                <label class="text-sm font-semibold">Phân cấp sau sửa
                                    <select name="grade_after" required class="mt-1 w-full rounded-lg border p-2">
                                        <option value="">Chọn phân cấp</option>
                                        @for($grade = 1; $grade <= 5; $grade++)
                                            <option value="{{ $grade }}" @selected((int) ($repair->asset?->grade ?? 1) === $grade)>Cấp {{ $grade }}</option>
                                        @endfor
                                    </select>
                                </label>
                                <label class="text-sm font-semibold">Người sửa
                                    <input name="performer" value="{{ $repair->performer }}" class="mt-1 w-full rounded-lg border p-2">
                                </label>
                                <label class="text-sm font-semibold">Chi phí sửa chữa
                                    <input name="cost" type="number" min="0" step="1" required placeholder="Nhập chi phí" class="mt-1 w-full rounded-lg border p-2">
                                </label>
                                <label class="text-sm font-semibold">Ngày kết thúc sửa
                                    <input name="completed_at" type="date" value="{{ now()->toDateString() }}" required class="mt-1 w-full rounded-lg border p-2">
                                </label>
                                <label class="text-sm font-semibold md:col-span-3">Ghi chú kết quả
                                    <textarea name="result_note" rows="2" class="mt-1 w-full rounded-lg border p-2"></textarea>
                                </label>
                                <button class="w-fit rounded bg-emerald-600 px-4 py-2 text-white">Xác nhận hoàn thành</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="p-5 text-center text-slate-500">Chưa có phiếu.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<script>
document.querySelectorAll('[data-repair-open]').forEach((button) => {
    button.addEventListener('click', () => document.getElementById('repair-modal-' + button.dataset.repairOpen)?.classList.toggle('hidden'));
});
document.querySelectorAll('[data-repair-complete-open]').forEach((button) => {
    button.addEventListener('click', () => document.getElementById('repair-complete-' + button.dataset.repairCompleteOpen)?.classList.toggle('hidden'));
});
document.querySelectorAll('[data-repair-assignee-select]').forEach((select) => {
    select.addEventListener('change', () => {
        const input = document.querySelector('[data-repair-performer-input="' + select.dataset.repairAssigneeSelect + '"]');
        const name = select.selectedOptions[0]?.dataset.name || '';
        if (input && !input.value.trim()) input.value = name;
    });
});
</script>
