@php
    $activeReportTarget = [
        'used' => 'report-used',
        'unused' => 'report-not-used',
        'tracking' => 'report-compare',
        'registered' => 'report-registered',
    ][request('report_type')] ?? '';

    $rowMeta = function ($item, string $fallbackName = '') {
        $person = $item->personnel ?? null;
        $unit = $item->unitRelation ?? $person?->unitRelation ?? null;
        $name = $item->personnel_name ?? $item->name ?? $person?->name ?? $fallbackName;
        $code = $item->personnel_code ?? $item->staff_code ?? $person?->staff_code ?? '';
        return [
            'agency' => $item->managing_agency ?? $person?->managing_agency ?? '',
            'unit' => (string) ($item->unit_id ?? $person?->unit_id ?? $unit?->id ?? ''),
            'search' => trim($name.' '.$code),
        ];
    };
@endphp

<div class="mt-5 rounded-xl border bg-white p-3">
    <div class="mb-3 border-b pb-3">
        @include('leave-management::partials.report-links', ['year' => $year, 'reportTemplates' => $reportTemplates ?? collect()])
    </div>

    <div class="flex flex-wrap gap-2 border-b pb-3">
        <button type="button" class="report-tab rounded border px-3 py-2 text-sm font-bold" data-target="report-used">Đã nghỉ</button>
        <button type="button" class="report-tab rounded border px-3 py-2 text-sm font-bold" data-target="report-not-used">Chưa nghỉ</button>
        <button type="button" class="report-tab rounded border px-3 py-2 text-sm font-bold" data-target="report-compare">Theo dõi ngày</button>
        <button type="button" class="report-tab rounded border px-3 py-2 text-sm font-bold" data-target="report-registered">Đăng ký phép năm</button>
    </div>

    <div id="report-used" class="report-panel hidden overflow-x-auto pt-3">
        <table class="w-full min-w-[1000px] text-left text-sm">
            <thead class="bg-slate-100"><tr><th class="p-3">STT</th><th class="p-3">Quân nhân</th><th class="p-3">Người đề xuất</th><th class="p-3">Từ ngày</th><th class="p-3">Đến ngày</th><th class="p-3">Tổng ngày</th><th class="p-3">Đã nghỉ</th><th class="p-3">Người duyệt</th></tr></thead>
            <tbody>
                @foreach($taken as $i => $item)
                    @php($meta = $rowMeta($item))
                    <tr class="border-t" data-report-row data-agency="{{ $meta['agency'] }}" data-unit-id="{{ $meta['unit'] }}" data-search="{{ $meta['search'] }}">
                        <td class="p-3" data-row-index>{{ $i + 1 }}</td>
                        <td class="p-3 font-semibold">{{ $item->personnel?->name }}</td>
                        <td class="p-3">{{ $item->proposed_by_display_name ?? $item->proposed_by_username ?? '—' }}</td>
                        <td class="p-3">{{ $item->from_date?->format('d-m-Y') }}</td>
                        <td class="p-3">{{ $item->to_date?->format('d-m-Y') }}</td>
                        <td class="p-3">{{ $item->total_days }}</td>
                        <td class="p-3 text-emerald-700">{{ $item->days_used }}</td>
                        <td class="p-3">{{ $item->decided_by_username ?? '—' }}</td>
                    </tr>
                @endforeach
                <tr class="hidden" data-filter-empty><td colspan="8" class="p-6 text-center">Không có dữ liệu phù hợp bộ lọc.</td></tr>
            </tbody>
        </table>
    </div>

    <div id="report-not-used" class="report-panel hidden overflow-x-auto pt-3">
        <table class="w-full min-w-[800px] text-left text-sm">
            <thead class="bg-slate-100"><tr><th class="p-3">STT</th><th class="p-3">Quân nhân</th><th class="p-3">Đơn vị</th><th class="p-3">Đơn đã duyệt sắp tới</th><th class="p-3">Thời gian</th></tr></thead>
            <tbody>
                @foreach($notYet as $i => $person)
                    @php($meta = $rowMeta($person))
                    <tr class="border-t" data-report-row data-agency="{{ $meta['agency'] }}" data-unit-id="{{ $meta['unit'] }}" data-search="{{ $meta['search'] }}">
                        <td class="p-3" data-row-index>{{ $i + 1 }}</td>
                        <td class="p-3 font-semibold">{{ $person->name }}</td>
                        <td class="p-3">{{ $person->unitRelation?->name ?? $person->unit }}</td>
                        <td class="p-3">{{ $person->requests?->where('status','APPROVED')->first()?->leave_type ?? 'Chưa có' }}</td>
                        <td class="p-3">{{ $person->requests?->where('status','APPROVED')->first()?->from_date?->format('d-m-Y') ?? '—' }} – {{ $person->requests?->where('status','APPROVED')->first()?->to_date?->format('d-m-Y') ?? '—' }}</td>
                    </tr>
                @endforeach
                <tr class="hidden" data-filter-empty><td colspan="5" class="p-6 text-center">Không có dữ liệu phù hợp bộ lọc.</td></tr>
            </tbody>
        </table>
    </div>

    <div id="report-compare" class="report-panel hidden overflow-x-auto pt-3">
        <table class="w-full min-w-[1000px] text-left text-sm">
            <thead class="bg-slate-100"><tr><th class="p-3">STT</th><th class="p-3">Quân nhân</th><th class="p-3">Từ ngày</th><th class="p-3">Đến ngày</th><th class="p-3">Tổng ngày</th><th class="p-3">Đã nghỉ</th><th class="p-3">Ngày còn lại</th><th class="p-3">Người duyệt</th></tr></thead>
            <tbody>
                @foreach($comparison as $i => $item)
                    @php($meta = $rowMeta($item))
                    <tr class="border-t" data-report-row data-agency="{{ $meta['agency'] }}" data-unit-id="{{ $meta['unit'] }}" data-search="{{ $meta['search'] }}">
                        <td class="p-3" data-row-index>{{ $i + 1 }}</td>
                        <td class="p-3 font-semibold">{{ $item->personnel?->name }}</td>
                        <td class="p-3">{{ $item->from_date?->format('d-m-Y') }}</td>
                        <td class="p-3">{{ $item->to_date?->format('d-m-Y') }}</td>
                        <td class="p-3">{{ $item->total_days }}</td>
                        <td class="p-3 text-emerald-700">{{ $item->days_used }}</td>
                        <td class="p-3 font-bold text-blue-700">{{ $item->days_remaining }}</td>
                        <td class="p-3">{{ $item->decided_by_username ?? '—' }}</td>
                    </tr>
                @endforeach
                <tr class="hidden" data-filter-empty><td colspan="8" class="p-6 text-center">Không có dữ liệu phù hợp bộ lọc.</td></tr>
            </tbody>
        </table>
    </div>

    <div id="report-registered" class="report-panel hidden overflow-x-auto pt-3">
        <table class="w-full min-w-[1000px] text-left text-sm">
            <thead class="bg-slate-100"><tr><th class="p-3">STT</th><th class="p-3">Quân nhân</th><th class="p-3">Đơn vị</th><th class="p-3">Từ ngày</th><th class="p-3">Đến ngày</th><th class="p-3">Nơi nghỉ phép</th><th class="p-3">Ghi chú</th></tr></thead>
            <tbody>
                @foreach(($registered ?? collect()) as $i => $item)
                    @php($meta = $rowMeta($item))
                    <tr class="border-t" data-report-row data-agency="{{ $meta['agency'] }}" data-unit-id="{{ $meta['unit'] }}" data-search="{{ $meta['search'] }}">
                        <td class="p-3" data-row-index>{{ $i + 1 }}</td>
                        <td class="p-3 font-semibold">{{ $item->personnel_name ?: $item->personnel?->name }}</td>
                        <td class="p-3">{{ $item->unit_name ?: $item->personnel?->unitRelation?->name ?: $item->personnel?->unit }}</td>
                        <td class="p-3">{{ $item->from_date?->format('d-m-Y') }}</td>
                        <td class="p-3">{{ $item->to_date?->format('d-m-Y') }}</td>
                        <td class="p-3">{{ $item->locality_path ?: $item->reason ?: '—' }}</td>
                        <td class="p-3">{{ $item->status === 'APPROVED' && $item->from_date && !now()->startOfDay()->lt($item->from_date->copy()->startOfDay()) ? 'Đã nghỉ' : 'Chưa nghỉ' }}</td>
                    </tr>
                @endforeach
                <tr class="hidden" data-filter-empty><td colspan="7" class="p-6 text-center">Không có dữ liệu phù hợp bộ lọc.</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded',function(){
    const type=document.getElementById('leave-report-type');
    const agency=document.getElementById('leave-report-agency');
    const unit=document.getElementById('leave-report-unit');
    const search=document.getElementById('leave-report-search');
    const targetToType={used:'report-used',unused:'report-not-used',tracking:'report-compare',registered:'report-registered'};
    const typeToTarget={'report-used':'used','report-not-used':'unused','report-compare':'tracking','report-registered':'registered'};
    const applyFilters=function(){
        const agencyValue=agency?.value||'';
        const unitValue=unit?.value||'';
        const searchValue=(search?.value||'').trim().toLowerCase();
        document.querySelectorAll('[data-report-row]').forEach(function(row){
            const matchesAgency=!agencyValue||row.dataset.agency===agencyValue;
            const matchesUnit=!unitValue||row.dataset.unitId===unitValue;
            const matchesSearch=!searchValue||(row.dataset.search||'').toLowerCase().includes(searchValue);
            row.classList.toggle('hidden',!(matchesAgency&&matchesUnit&&matchesSearch));
        });
        document.querySelectorAll('.report-panel tbody').forEach(function(tbody){
            let index=1,visible=0;
            tbody.querySelectorAll('[data-report-row]').forEach(function(row){
                if(row.classList.contains('hidden'))return;
                visible++;
                const cell=row.querySelector('[data-row-index]');
                if(cell)cell.textContent=index++;
            });
            tbody.querySelector('[data-filter-empty]')?.classList.toggle('hidden',visible>0);
        });
    };
    const activate=function(tab){
        if(!tab)return;
        document.querySelectorAll('.report-panel').forEach(function(panel){panel.classList.add('hidden');});
        document.getElementById(tab.dataset.target)?.classList.remove('hidden');
        document.querySelectorAll('.report-tab').forEach(function(x){x.classList.remove('bg-blue-600','text-white');});
        tab.classList.add('bg-blue-600','text-white');
        if(type&&typeToTarget[tab.dataset.target]&&type.value!==typeToTarget[tab.dataset.target]){
            type.value=typeToTarget[tab.dataset.target];
            type.dispatchEvent(new Event('change'));
        }
        applyFilters();
    };
    window.applyLeaveReportFilters=applyFilters;
    document.querySelectorAll('.report-tab').forEach(function(tab){tab.addEventListener('click',function(){activate(tab);});});
    [agency,unit,search].forEach(function(el){el&&el.addEventListener('input',applyFilters);el&&el.addEventListener('change',applyFilters);});
    type&&type.addEventListener('change',function(){
        const target=targetToType[type.value];
        if(!target)return;
        activate(document.querySelector('.report-tab[data-target="'+target+'"]'));
    });
    const initial=document.querySelector('.report-tab[data-target="{{ $activeReportTarget }}"]');
    if(initial)activate(initial);
    else applyFilters();
});
</script>
