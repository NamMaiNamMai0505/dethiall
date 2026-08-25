@php($leaveTypeLabels=['ANNUAL'=>'Phép năm','EXTRA'=>'Phép thêm','SICK'=>'Nghỉ ốm','PERSONAL'=>'Việc riêng','SHORT_LEAVE'=>'Nghỉ tranh thủ','UNIT'=>'Phép đơn vị'])
<div class="mt-4 overflow-x-auto rounded border bg-white">
    <table class="w-full min-w-[1100px] text-left text-sm">
        <thead class="bg-slate-100"><tr><th class="p-3">STT</th><th class="p-3">Quân nhân</th><th class="p-3">Loại phép</th><th class="p-3">Thời gian nghỉ</th><th class="p-3">Tổng ngày</th><th class="p-3">Đã nghỉ đến hôm nay</th><th class="p-3">Còn lại</th><th class="p-3">Trạng thái</th></tr></thead>
        <tbody>
        @forelse($items as $index => $item)
            <tr class="border-t"><td class="p-3">{{ $index + 1 }}</td><td class="p-3 font-semibold">{{ $item->personnel?->name ?? $item->personnel_name ?? $item->request?->personnel?->name }}</td><td class="p-3">{{ $leaveTypeLabels[$item->leave_type] ?? $item->leave_type }}</td><td class="p-3">{{ $item->start_date?->format('d-m-Y') }} – {{ $item->end_date?->format('d-m-Y') }}</td><td class="p-3 font-bold">{{ $item->total_days }} ngày</td><td class="p-3 text-emerald-700">{{ $item->days_used }} ngày</td><td class="p-3 font-bold text-blue-700">{{ $item->days_remaining }} ngày</td><td class="p-3">{{ $item->leave_progress }}</td></tr>
        @empty
            <tr><td colspan="8" class="p-6 text-center text-slate-500">Chưa có đợt nghỉ.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
