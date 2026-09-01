<style>.leave-report-page > a { display: none !important; }</style>
<div class="flex flex-wrap items-end gap-3">
    <label class="text-sm font-semibold">Loại báo cáo
        <select id="leave-report-type" data-native-select class="mt-1 block w-80 rounded border p-2">
            <option value="">Chọn loại báo cáo</option>
            <option value="used" @selected(request('report_type')==='used')>QN đã nghỉ phép</option>
            <option value="unused" @selected(request('report_type')==='unused')>QN chưa nghỉ phép</option>
            <option value="tracking" @selected(request('report_type')==='tracking')>Theo dõi thời gian nghỉ phép</option>
            <option value="registered" @selected(request('report_type')==='registered')>QN đăng ký nghỉ phép năm</option>
        </select>
    </label>
    <label class="text-sm font-semibold">Diện quản lý
        <select id="leave-report-agency" data-native-select class="mt-1 block w-64 rounded border p-2">
            <option value="">Chọn diện quản lý</option>
            <option value="QUAN_LUC" @selected(request('agency')==='QUAN_LUC')>Diện Quân lực quản lý</option>
            <option value="CO_QUAN_CAN_BO" @selected(request('agency')==='CO_QUAN_CAN_BO')>Diện Cán bộ quản lý</option>
        </select>
    </label>
    <label class="text-sm font-semibold">Đơn vị báo cáo
        <select id="leave-report-unit" data-native-select class="mt-1 block w-80 rounded border p-2">
            <option value="">Tất cả đơn vị</option>
            @foreach(\Modules\Unit\Models\Unit::active()->with('parent.parent.parent')->orderBy('level')->orderBy('name')->get() as $unit)@php($level=max(1,(int)($unit->level ?: 1)))<option value="{{ $unit->id }}" @selected((string)request('unit_id')===(string)$unit->id)>{{ str_repeat('— ', max(0, $level - 1)) }}{{ $unit->code ? $unit->code.' — ' : '' }}{{ $unit->leafFirstHierarchyPath(' / ') }}</option>@endforeach
        </select>
    </label>
    <label class="text-sm font-semibold">Tìm quân nhân
        <input id="leave-report-search" name="q" value="{{ request('q') }}" placeholder="Nhập tên hoặc mã quân nhân" class="mt-1 block w-72 rounded border p-2">
    </label>
    <label class="text-sm font-semibold">Năm báo cáo
        <input id="leave-report-year" name="year" type="number" min="2000" max="2100" value="{{ $year }}" class="mt-1 block w-32 rounded border p-2">
    </label>
    <label class="text-sm font-semibold">Mẫu Word in ra
        <select id="leave-report-template" data-native-select class="mt-1 block w-80 rounded border p-2">
            <option value="">Mẫu mặc định của hệ thống</option>
            @foreach(($reportTemplates ?? collect()) as $template)
                <option value="{{ $template->id }}" data-report-type="{{ $template->report_type }}" data-agency="{{ $template->managing_agency }}" @selected((string) request('template_id') === (string) $template->id)>{{ $template->name }} - {{ $template->original_name }}</option>
            @endforeach
        </select>
    </label>
    <label class="text-sm font-semibold">Nơi nhận báo cáo
        <input id="leave-report-recipient" name="noi_nhan" value="{{ request('noi_nhan') }}" placeholder="Nhập nơi nhận" class="mt-1 block w-72 rounded border p-2">
    </label>
    @can('leave-management.export')
        <a data-report-export data-report-word href="{{ url('/quan-ly-phep/bao-cao/word') }}?year={{ $year }}" class="rounded bg-blue-600 px-4 py-2 text-white">Xuất Word</a>
        <a data-report-export href="{{ route('leave-management.reports.csv',['year'=>$year]) }}" class="rounded bg-slate-700 px-4 py-2 text-white">Xuất CSV</a>
    @endcan
</div>
<script>
document.addEventListener('DOMContentLoaded',function(){
    const unit=document.getElementById('leave-report-unit'),input=document.getElementById('leave-report-recipient'),type=document.getElementById('leave-report-type'),agency=document.getElementById('leave-report-agency'),year=document.getElementById('leave-report-year'),search=document.getElementById('leave-report-search'),template=document.getElementById('leave-report-template');
    const allTemplates=template?Array.from(template.options).filter(function(option){return option.value;}).map(function(option){return {value:option.value,text:option.textContent,reportType:option.dataset.reportType||'',agency:option.dataset.agency||''};}):[];
    const syncTemplates=function(){
        if(!template||!type||!agency)return;
        const current=template.value;
        const items=[{value:'',text:'Mẫu mặc định của hệ thống'}];
        allTemplates.forEach(function(item){
            if(type.value&&agency.value&&item.reportType===type.value&&item.agency===agency.value)items.push({value:item.value,text:item.text,data:{reportType:item.reportType,agency:item.agency}});
        });
        const selected=items.some(function(item){return item.value===current;})?current:'';
        if(typeof window.setTomSelectOptions==='function'&&template.tomselect){
            window.setTomSelectOptions(template,items,{selected:selected,enabled:true});
        }else{
            template.innerHTML='';
            items.forEach(function(item){
                const option=document.createElement('option');
                option.value=item.value;option.textContent=item.text;
                if(item.data){option.dataset.reportType=item.data.reportType;option.dataset.agency=item.data.agency;}
                template.appendChild(option);
            });
            template.value=selected;
        }
    };
    const fillUrl=function(base){
        const url=new URL(base||window.location.href,window.location.origin);
        if(unit.value)url.searchParams.set('unit_id',unit.value);else url.searchParams.delete('unit_id');
        if(input.value)url.searchParams.set('noi_nhan',input.value);else url.searchParams.delete('noi_nhan');
        if(search&&search.value.trim())url.searchParams.set('q',search.value.trim());else url.searchParams.delete('q');
        if(type.value)url.searchParams.set('report_type',type.value);
        if(agency.value)url.searchParams.set('agency',agency.value);
        if(year&&year.value)url.searchParams.set('year',year.value);
        if(template&&template.value)url.searchParams.set('template_id',template.value);else url.searchParams.delete('template_id');
        return url;
    };
    const applyFilter=function(){syncTemplates();window.location.href=fillUrl(window.location.href).toString();};
    syncTemplates();
    unit&&unit.addEventListener('change',function(){window.applyLeaveReportFilters?.();});
    agency&&agency.addEventListener('change',function(){syncTemplates();window.applyLeaveReportFilters?.();});
    const activateReportType=function(){
        const map={used:'report-used',unused:'report-not-used',tracking:'report-compare',registered:'report-registered'};
        document.querySelectorAll('.report-panel').forEach(function(panel){panel.classList.add('hidden');});
        document.querySelectorAll('.report-tab').forEach(function(tab){tab.classList.remove('bg-blue-600','text-white');});
        if(!type||!map[type.value])return;
        document.getElementById(map[type.value])?.classList.remove('hidden');
        document.querySelector('.report-tab[data-target="'+map[type.value]+'"]')?.classList.add('bg-blue-600','text-white');
    };
    type&&type.addEventListener('change',function(){syncTemplates();activateReportType();});
    if(year){
        year.addEventListener('change',applyFilter);
        year.addEventListener('keydown',function(e){if(e.key==='Enter'){e.preventDefault();applyFilter();}});
    }
    if(search){
        let timer=null;
        search.addEventListener('input',function(){clearTimeout(timer);timer=setTimeout(function(){window.applyLeaveReportFilters?.();},250);});
        search.addEventListener('keydown',function(e){if(e.key==='Enter'){e.preventDefault();window.applyLeaveReportFilters?.();}});
    }
    document.querySelectorAll('[data-report-export]').forEach(function(link){link.addEventListener('click',function(e){syncTemplates();if(type&&!type.value){e.preventDefault();alert('Vui lòng chọn loại báo cáo trước khi xuất.');return;}if(agency&&!agency.value){e.preventDefault();alert('Vui lòng chọn diện quản lý trước khi xuất.');return;}link.href=fillUrl(link.href).toString();});});
});
</script>
