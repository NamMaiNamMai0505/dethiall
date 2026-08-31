@php($leaveTypeLabels=['ANNUAL'=>'Phép năm','EXTRA'=>'Phép thêm','SICK'=>'Nghỉ ốm','PERSONAL'=>'Việc riêng','SHORT_LEAVE'=>'Nghỉ tranh thủ','UNIT'=>'Phép đơn vị'])
@php($leaveStatusLabels=['PENDING'=>'Chờ gửi','DRAFT'=>'Bản nháp','PENDING_COMMANDER'=>'Chờ chỉ huy','PENDING_AGENCY'=>'Chờ cơ quan quản lý','PENDING_HEAD'=>'Chờ thủ trưởng ký','RETURNED'=>'Trả lại','APPROVED'=>'Đã duyệt','REJECTED'=>'Từ chối','CANCELLED'=>'Đã hủy'])
@php($canPrintLeavePermit=\App\Support\PermissionCheck::isLeaveAgency(auth()->user()) || \Modules\LeaveManagement\Support\LeaveAccess::canApprove(auth()->user()) || \Modules\LeaveManagement\Support\LeaveAccess::canHeadSign(auth()->user()))
<form id="leave-record-filter" method="GET" action="{{ route('leave-management.records') }}" class="mt-4 grid gap-3 rounded border bg-white p-4 md:grid-cols-[1fr_140px_220px_auto]">
    <input name="q" value="{{ $recordKeyword ?? request('q') }}" placeholder="Tìm tên hoặc mã quân nhân" class="rounded border px-3 py-2">
    <input name="year" type="number" min="2000" max="2100" value="{{ $year ?? request('year', now()->year) }}" placeholder="Năm" class="rounded border px-3 py-2">
    <select name="unit_id" class="rounded border px-3 py-2">
        <option value="">Tất cả đơn vị</option>
        @foreach(($units ?? collect()) as $unit)
            <option value="{{ $unit->id }}" @selected((string)($recordUnitId ?? request('unit_id'))===(string)$unit->id)>{{ $unit->code ? $unit->code.' — ' : '' }}{{ $unit->name }}</option>
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
<div class="mt-4 overflow-x-auto rounded border bg-white"><table class="w-full min-w-[1240px] text-left text-sm"><thead class="bg-slate-100"><tr><th class="p-3">STT</th><th class="p-3">Quân nhân</th><th class="p-3">Người đề xuất</th><th class="p-3">Loại phép</th><th class="p-3">Thời gian</th><th class="p-3">Số ngày</th><th class="p-3">Người duyệt</th><th class="p-3">Trạng thái</th><th class="p-3">Thao tác</th></tr></thead><tbody>@forelse($items as $index=>$item)<tr class="border-t"><td class="p-3">{{ $index+1 }}</td><td class="p-3 font-semibold">{{ $item->personnel_name }}</td><td class="p-3">{{ $item->proposed_by_display_name ?? $item->proposed_by_username ?? '—' }}</td><td class="p-3">{{ $leaveTypeLabels[$item->leave_type] ?? $item->leave_type }}</td><td class="p-3">{{ $item->start_date?->format('d-m-Y') }} – {{ $item->end_date?->format('d-m-Y') }}</td><td class="p-3 font-bold">{{ $item->total_days }} ngày</td><td class="p-3">{{ $item->decidedBy?->name ?? $item->decided_by_username ?? '—' }}</td><td class="p-3">{{ $leaveStatusLabels[$item->status] ?? $item->status }}</td><td class="p-3">@if($canPrintLeavePermit && $item->request_id)<a target="_blank" href="{{ route('leave-management.requests.print',$item->request_id) }}" class="rounded bg-emerald-600 px-3 py-1.5 text-sm font-semibold text-white hover:bg-emerald-700">In phép</a>@else<span class="text-slate-500">—</span>@endif</td></tr>@empty<tr><td colspan="9" class="p-6 text-center text-slate-500">Chưa có hồ sơ phép.</td></tr>@endforelse</tbody></table></div>
