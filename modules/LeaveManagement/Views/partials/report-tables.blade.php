@php
    $reportUnitId = request('unit_id');
    if ($reportUnitId) {
        $selectedUnitName = \Modules\Unit\Models\Unit::find($reportUnitId)?->name;
        $belongsToUnit = fn($item) => (int)($item->unit_id ?: $item->personnel?->unit_id) === (int)$reportUnitId || ($selectedUnitName && (strcasecmp(trim((string)$item->unit_name), trim((string)$selectedUnitName)) === 0 || strcasecmp(trim((string)$item->personnel?->unit), trim((string)$selectedUnitName)) === 0 || strcasecmp(trim((string)$item->personnel?->unitRelation?->name), trim((string)$selectedUnitName)) === 0));
        $taken = $taken->filter($belongsToUnit)->values();
        $comparison = $comparison->filter($belongsToUnit)->values();
        $registered = ($registered ?? collect())->filter($belongsToUnit)->values();
        $notYet = $notYet->where('unit_id', $reportUnitId)->values();
    }
    $activeReportTarget = [
        'used' => 'report-used',
        'unused' => 'report-not-used',
        'tracking' => 'report-compare',
        'registered' => 'report-registered',
    ][request('report_type', 'used')] ?? 'report-used';
    if (request('report_tab') === 'notifications') {
        $activeReportTarget = 'report-notifications';
    }
    $unreadLeaveNotifications = ($leaveNotifications ?? collect())->whereNull('read_at')->count();
@endphp
<div class="mt-5 rounded-xl border bg-white p-3">
    <div class="mb-3 border-b pb-3">
        @include('leave-management::partials.report-links', ['year' => $year, 'reportTemplates' => $reportTemplates ?? collect()])
    </div>
    <div class="flex flex-wrap gap-2 border-b pb-3"><button type="button" class="report-tab rounded border border-amber-200 px-3 py-2 text-sm font-bold text-amber-700" data-target="report-notifications"><i class="bi bi-bell mr-1"></i>Thông báo @if($unreadLeaveNotifications > 0)<span class="ml-1 rounded-full bg-red-600 px-2 py-0.5 text-xs text-white">{{ $unreadLeaveNotifications }}</span>@endif</button><button type="button" class="report-tab rounded border px-3 py-2 text-sm font-bold" data-target="report-used">Đã nghỉ</button><button type="button" class="report-tab rounded border px-3 py-2 text-sm font-bold" data-target="report-not-used">Chưa nghỉ</button><button type="button" class="report-tab rounded border px-3 py-2 text-sm font-bold" data-target="report-compare">Theo dõi ngày</button><button type="button" class="report-tab rounded border px-3 py-2 text-sm font-bold" data-target="report-registered">Đăng ký phép năm</button></div>
    <div id="report-used" class="report-panel hidden overflow-x-auto pt-3"><table class="w-full min-w-[1000px] text-left text-sm"><thead class="bg-slate-100"><tr><th class="p-3">STT</th><th class="p-3">Quân nhân</th><th class="p-3">Người đề xuất</th><th class="p-3">Từ ngày</th><th class="p-3">Đến ngày</th><th class="p-3">Tổng ngày</th><th class="p-3">Đã nghỉ</th><th class="p-3">Người duyệt</th></tr></thead><tbody>@forelse($taken as $i=>$item)<tr class="border-t"><td class="p-3">{{ $i+1 }}</td><td class="p-3 font-semibold">{{ $item->personnel?->name }}</td><td class="p-3">{{ $item->proposed_by_display_name ?? $item->proposed_by_username ?? '—' }}</td><td class="p-3">{{ $item->from_date?->format('d-m-Y') }}</td><td class="p-3">{{ $item->to_date?->format('d-m-Y') }}</td><td class="p-3">{{ $item->total_days }}</td><td class="p-3 text-emerald-700">{{ $item->days_used }}</td><td class="p-3">{{ $item->decided_by_username ?? '—' }}</td></tr>@empty<tr><td colspan="8" class="p-6 text-center">Chưa có quân nhân đã nghỉ.</td></tr>@endforelse</tbody></table></div>
    <div id="report-not-used" class="report-panel hidden overflow-x-auto pt-3"><table class="w-full min-w-[800px] text-left text-sm"><thead class="bg-slate-100"><tr><th class="p-3">STT</th><th class="p-3">Quân nhân</th><th class="p-3">Đơn vị</th><th class="p-3">Đơn đã duyệt sắp tới</th><th class="p-3">Thời gian</th></tr></thead><tbody>@forelse($notYet as $i=>$person)<tr class="border-t"><td class="p-3">{{ $i+1 }}</td><td class="p-3 font-semibold">{{ $person->name }}</td><td class="p-3">{{ $person->unitRelation?->name ?? $person->unit }}</td><td class="p-3">{{ $person->requests?->where('status','APPROVED')->first()?->leave_type ?? 'Chưa có' }}</td><td class="p-3">{{ $person->requests?->where('status','APPROVED')->first()?->from_date?->format('d-m-Y') ?? '—' }} – {{ $person->requests?->where('status','APPROVED')->first()?->to_date?->format('d-m-Y') ?? '—' }}</td></tr>@empty<tr><td colspan="5" class="p-6 text-center">Không có dữ liệu.</td></tr>@endforelse</tbody></table></div>
    <div id="report-compare" class="report-panel hidden overflow-x-auto pt-3"><table class="w-full min-w-[1000px] text-left text-sm"><thead class="bg-slate-100"><tr><th class="p-3">STT</th><th class="p-3">Quân nhân</th><th class="p-3">Từ ngày</th><th class="p-3">Đến ngày</th><th class="p-3">Tổng ngày</th><th class="p-3">Đã nghỉ</th><th class="p-3">Ngày còn lại</th><th class="p-3">Người duyệt</th></tr></thead><tbody>@forelse($comparison as $i=>$item)<tr class="border-t"><td class="p-3">{{ $i+1 }}</td><td class="p-3 font-semibold">{{ $item->personnel?->name }}</td><td class="p-3">{{ $item->from_date?->format('d-m-Y') }}</td><td class="p-3">{{ $item->to_date?->format('d-m-Y') }}</td><td class="p-3">{{ $item->total_days }}</td><td class="p-3 text-emerald-700">{{ $item->days_used }}</td><td class="p-3 font-bold text-blue-700">{{ $item->days_remaining }}</td><td class="p-3">{{ $item->decided_by_username ?? '—' }}</td></tr>@empty<tr><td colspan="8" class="p-6 text-center">Chưa có dữ liệu đối chiếu.</td></tr>@endforelse</tbody></table></div>
    <div id="report-registered" class="report-panel hidden overflow-x-auto pt-3"><table class="w-full min-w-[1000px] text-left text-sm"><thead class="bg-slate-100"><tr><th class="p-3">STT</th><th class="p-3">Quân nhân</th><th class="p-3">Đơn vị</th><th class="p-3">Từ ngày</th><th class="p-3">Đến ngày</th><th class="p-3">Nơi nghỉ phép</th><th class="p-3">Ghi chú</th></tr></thead><tbody>@forelse(($registered ?? collect()) as $i=>$item)<tr class="border-t"><td class="p-3">{{ $i+1 }}</td><td class="p-3 font-semibold">{{ $item->personnel_name ?: $item->personnel?->name }}</td><td class="p-3">{{ $item->unit_name ?: $item->personnel?->unitRelation?->name ?: $item->personnel?->unit }}</td><td class="p-3">{{ $item->from_date?->format('d-m-Y') }}</td><td class="p-3">{{ $item->to_date?->format('d-m-Y') }}</td><td class="p-3">{{ $item->locality_path ?: $item->reason ?: '—' }}</td><td class="p-3">{{ $item->status === 'APPROVED' && $item->from_date && !now()->startOfDay()->lt($item->from_date->copy()->startOfDay()) ? 'Đã nghỉ' : 'Chưa nghỉ' }}</td></tr>@empty<tr><td colspan="7" class="p-6 text-center">Chưa có dữ liệu đăng ký phép năm.</td></tr>@endforelse</tbody></table></div>
    <div id="report-notifications" class="report-panel hidden pt-3">
        <div class="rounded border bg-white">
            @forelse(($leaveNotifications ?? collect()) as $notice)
                <div class="flex flex-col gap-3 border-b p-3 {{ $notice->read_at ? 'opacity-60' : '' }} md:flex-row md:items-center md:justify-between">
                    <div>
                        <div class="font-semibold text-slate-900">{{ $notice->title }}</div>
                        <div class="mt-1 text-sm text-slate-600">{{ $notice->body }}</div>
                        <div class="mt-1 text-xs text-slate-500">{{ $notice->created_at?->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @if($notice->request_id)
                            <a href="{{ route('leave-management.requests.show', $notice->request_id) }}" class="rounded border border-blue-200 bg-white px-3 py-1.5 text-sm font-semibold text-blue-700">Xem đề xuất</a>
                        @endif
                        @if(!$notice->read_at)
                            <form method="POST" action="{{ route('leave-management.alerts.read',$notice) }}">@csrf @method('PATCH')<button class="rounded bg-blue-600 px-3 py-1.5 text-sm font-semibold text-white">Đã đọc</button></form>
                        @endif
                    </div>
                </div>
            @empty
                <p class="p-6 text-center text-slate-500">Chưa có thông báo phép.</p>
            @endforelse
        </div>
    </div>
</div>
<script>document.addEventListener('DOMContentLoaded',function(){const activate=function(tab){document.querySelectorAll('.report-panel').forEach(function(panel){panel.classList.add('hidden');});document.getElementById(tab.dataset.target)?.classList.remove('hidden');document.querySelectorAll('.report-tab').forEach(function(x){x.classList.remove('bg-blue-600','text-white');});tab.classList.add('bg-blue-600','text-white');};document.querySelectorAll('.report-tab').forEach(function(tab){tab.addEventListener('click',function(){activate(tab);});});activate(document.querySelector('.report-tab[data-target="{{ $activeReportTarget }}"]')||document.querySelector('.report-tab'));});</script>
