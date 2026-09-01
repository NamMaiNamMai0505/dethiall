@php
    $leaveTypeLabels = ['ANNUAL' => 'Phép năm', 'EXTRA' => 'Phép thêm', 'SICK' => 'Nghỉ ốm', 'PERSONAL' => 'Việc riêng', 'SHORT_LEAVE' => 'Nghỉ tranh thủ', 'SPECIAL' => 'Phép đặc biệt', 'UNIT' => 'Phép đơn vị'];
    $leaveStatusLabels = ['PENDING' => 'Chờ gửi', 'DRAFT' => 'Bản nháp', 'PENDING_COMMANDER' => 'Chờ chỉ huy', 'PENDING_AGENCY' => 'Chờ cơ quan quản lý', 'PENDING_HEAD' => 'Chờ thủ trưởng ký', 'RETURNED' => 'Trả lại', 'APPROVED' => 'Đã duyệt', 'REJECTED' => 'Từ chối', 'CANCELLED' => 'Đã hủy'];
    $canPrintLeavePermit = \App\Support\PermissionCheck::isLeaveAgency(auth()->user()) || \Modules\LeaveManagement\Support\LeaveAccess::canApprove(auth()->user()) || \Modules\LeaveManagement\Support\LeaveAccess::canHeadSign(auth()->user());
    $canEditLeaveRecord = auth()->user()?->isSuperAdmin() || \App\Support\PermissionCheck::can(auth()->user(), 'leave-management.edit');
    $canDeleteLeaveRecord = auth()->user()?->isSuperAdmin() || \App\Support\PermissionCheck::can(auth()->user(), 'leave-management.delete');
@endphp

<form id="leave-record-filter" method="GET" action="{{ route('leave-management.records') }}" class="mt-4 grid gap-3 rounded border bg-white p-4 md:grid-cols-[1fr_140px_220px_auto]">
    <input name="q" value="{{ $recordKeyword ?? request('q') }}" placeholder="Tìm tên hoặc mã quân nhân" class="rounded border px-3 py-2">
    <input name="year" type="number" min="2000" max="2100" value="{{ $year ?? request('year', now()->year) }}" placeholder="Năm" class="rounded border px-3 py-2">
    <select name="unit_id" class="rounded border px-3 py-2">
        <option value="">Tất cả đơn vị</option>
        @foreach(($units ?? collect()) as $unit)
            @php($level = max(1, (int) ($unit->level ?: 1)))
            <option value="{{ $unit->id }}" @selected((string)($recordUnitId ?? request('unit_id')) === (string) $unit->id)>{{ str_repeat('— ', max(0, $level - 1)) }}{{ $unit->code ? $unit->code.' — ' : '' }}{{ $unit->leafFirstHierarchyPath(' / ') }}</option>
        @endforeach
    </select>
    <div class="flex gap-2">
        <a href="{{ route('leave-management.records') }}" class="rounded border px-4 py-2 font-semibold text-slate-700">Xóa lọc</a>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('leave-record-filter');
    if (!form) return;
    const search = form.querySelector('input[name="q"]');
    const year = form.querySelector('input[name="year"]');
    const unit = form.querySelector('select[name="unit_id"]');
    let timer = null;
    const submit = function () { form.requestSubmit ? form.requestSubmit() : form.submit(); };
    search?.addEventListener('input', function () {
        clearTimeout(timer);
        timer = setTimeout(submit, 450);
    });
    [year, unit].forEach(function (control) {
        control?.addEventListener('change', submit);
        control?.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                submit();
            }
        });
    });
});
</script>

<div class="mt-4 overflow-x-auto rounded border bg-white">
    <table class="w-full min-w-[1320px] text-left text-sm">
        <thead class="bg-slate-100">
            <tr>
                <th class="p-3">STT</th>
                <th class="p-3">Quân nhân</th>
                <th class="p-3">Người đề xuất</th>
                <th class="p-3">Loại phép</th>
                <th class="p-3">Thời gian</th>
                <th class="p-3">Số ngày</th>
                <th class="p-3">Người duyệt</th>
                <th class="p-3">Trạng thái</th>
                <th class="p-3">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $index => $item)
                <tr class="border-t align-top">
                    <td class="p-3">{{ $index + 1 }}</td>
                    <td class="p-3 font-semibold">{{ $item->personnel_name }}</td>
                    <td class="p-3">{{ $item->proposed_by_display_name ?? $item->proposed_by_username ?? '—' }}</td>
                    <td class="p-3">{{ $leaveTypeLabels[$item->leave_type] ?? $item->leave_type }}</td>
                    <td class="p-3">{{ $item->start_date?->format('d-m-Y') }} – {{ $item->end_date?->format('d-m-Y') }}</td>
                    <td class="p-3 font-bold">{{ $item->total_days }} ngày</td>
                    <td class="p-3">{{ $item->decidedBy?->name ?? $item->decided_by_username ?? '—' }}</td>
                    <td class="p-3">{{ $leaveStatusLabels[$item->status] ?? $item->status }} @if($item->archived_at)<span class="text-xs text-slate-500">(đã lưu trữ)</span>@endif</td>
                    <td class="p-3">
                        <div class="flex flex-wrap gap-2">
                            @if($canPrintLeavePermit)
                                <a target="_blank" href="{{ route('leave-management.records.print', ['record' => $item, 'format' => 'print']) }}" class="rounded bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-emerald-700">In phép</a>
                            @endif

                            @if($canEditLeaveRecord)
                                <details class="relative">
                                    <summary class="cursor-pointer list-none rounded bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-blue-700">Sửa</summary>
                                    <form method="POST" action="{{ route('leave-management.records.update', $item) }}" class="absolute right-0 z-30 mt-2 grid w-[min(92vw,520px)] gap-3 rounded-xl border border-slate-200 bg-white p-4 text-left shadow-xl md:grid-cols-2">
                                        @csrf
                                        @method('PATCH')
                                        <label class="block">
                                            <span class="mb-1 block text-xs font-bold text-slate-600">Loại phép</span>
                                            <select name="leave_type" required class="w-full rounded border px-3 py-2">
                                                @foreach($leaveTypeLabels as $value => $label)
                                                    <option value="{{ $value }}" @selected($item->leave_type === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </label>
                                        <label class="block">
                                            <span class="mb-1 block text-xs font-bold text-slate-600">Số ngày</span>
                                            <input name="total_days" type="number" min="1" required value="{{ $item->total_days }}" class="w-full rounded border px-3 py-2">
                                        </label>
                                        <label class="block">
                                            <span class="mb-1 block text-xs font-bold text-slate-600">Ngày bắt đầu</span>
                                            <input name="start_date" type="date" required value="{{ $item->start_date?->format('Y-m-d') }}" class="w-full rounded border px-3 py-2">
                                        </label>
                                        <label class="block">
                                            <span class="mb-1 block text-xs font-bold text-slate-600">Ngày kết thúc</span>
                                            <input name="end_date" type="date" required value="{{ $item->end_date?->format('Y-m-d') }}" class="w-full rounded border px-3 py-2">
                                        </label>
                                        <label class="block md:col-span-2">
                                            <span class="mb-1 block text-xs font-bold text-slate-600">Ghi chú</span>
                                            <textarea name="note" rows="2" class="w-full rounded border px-3 py-2">{{ $item->note }}</textarea>
                                        </label>
                                        <label class="block md:col-span-2">
                                            <span class="mb-1 block text-xs font-bold text-slate-600">Ghi chú quản trị</span>
                                            <textarea name="admin_note" rows="2" class="w-full rounded border px-3 py-2">{{ $item->admin_note }}</textarea>
                                        </label>
                                        <div class="md:col-span-2">
                                            <button class="rounded bg-blue-700 px-4 py-2 font-bold text-white">Lưu thay đổi</button>
                                        </div>
                                    </form>
                                </details>
                            @endif

                            @if($canEditLeaveRecord && !$item->archived_at)
                                <form method="POST" action="{{ route('leave-management.records.archive', $item) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button class="rounded border border-slate-300 bg-white px-3 py-1.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">Lưu trữ</button>
                                </form>
                            @endif

                            @if($canDeleteLeaveRecord)
                                <form method="POST" action="{{ route('leave-management.records.delete', $item) }}" onsubmit="return confirm('Xóa hồ sơ phép này khỏi danh sách? Đơn đề xuất gốc vẫn được giữ lại.');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded bg-rose-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-rose-700">Xóa</button>
                                </form>
                            @endif

                            @if(!$canPrintLeavePermit && !$canEditLeaveRecord && !$canDeleteLeaveRecord)
                                <span class="text-slate-500">—</span>
                            @endif
                        </div>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="p-6 text-center text-slate-500">Chưa có hồ sơ phép.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
