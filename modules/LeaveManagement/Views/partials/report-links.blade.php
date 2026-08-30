<style>.leave-report-page > a { display: none !important; }</style>
<div class="flex flex-wrap items-end gap-3">
    <label class="text-sm font-semibold">Mẫu báo cáo
        <select id="leave-report-type" class="mt-1 block w-80 rounded border p-2">
            <option value="used" @selected(request('report_type','used')==='used')>QN đã nghỉ phép</option>
            <option value="unused" @selected(request('report_type')==='unused')>QN chưa nghỉ phép</option>
            <option value="tracking" @selected(request('report_type')==='tracking')>Theo dõi thời gian nghỉ phép</option>
            <option value="registered" @selected(request('report_type')==='registered')>QN đăng ký nghỉ phép năm</option>
        </select>
    </label>
    <label class="text-sm font-semibold">Diện quản lý
        <select id="leave-report-agency" class="mt-1 block w-64 rounded border p-2">
            <option value="QUAN_LUC" @selected(request('agency','QUAN_LUC')==='QUAN_LUC')>Diện Quân lực quản lý</option>
            <option value="CO_QUAN_CAN_BO" @selected(request('agency')==='CO_QUAN_CAN_BO')>Diện Cán bộ quản lý</option>
        </select>
    </label>
    <label class="text-sm font-semibold">Đơn vị báo cáo
        <select id="leave-report-unit" class="mt-1 block w-80 rounded border p-2">
            <option value="">Tất cả đơn vị</option>
            @foreach(\Modules\Unit\Models\Unit::active()->orderBy('name')->get() as $unit)<option value="{{ $unit->id }}" @selected((string)request('unit_id')===(string)$unit->id)>{{ $unit->name }}</option>@endforeach
        </select>
    </label>
    <label class="text-sm font-semibold">Tìm quân nhân
        <input id="leave-report-search" name="q" value="{{ request('q') }}" placeholder="Nhập tên hoặc mã quân nhân" class="mt-1 block w-72 rounded border p-2">
    </label>
    <label class="text-sm font-semibold">Năm báo cáo
        <input id="leave-report-year" name="year" type="number" min="2000" max="2100" value="{{ $year }}" class="mt-1 block w-32 rounded border p-2">
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
document.addEventListener('DOMContentLoaded',function(){const unit=document.getElementById('leave-report-unit'),input=document.getElementById('leave-report-recipient'),type=document.getElementById('leave-report-type'),agency=document.getElementById('leave-report-agency'),year=document.getElementById('leave-report-year'),search=document.getElementById('leave-report-search');const fillUrl=function(base){const url=new URL(base||window.location.href,window.location.origin);if(unit.value)url.searchParams.set('unit_id',unit.value);else url.searchParams.delete('unit_id');if(input.value)url.searchParams.set('noi_nhan',input.value);else url.searchParams.delete('noi_nhan');if(search&&search.value.trim())url.searchParams.set('q',search.value.trim());else url.searchParams.delete('q');if(type.value)url.searchParams.set('report_type',type.value);if(agency.value)url.searchParams.set('agency',agency.value);if(year&&year.value)url.searchParams.set('year',year.value);return url;};const applyFilter=function(){window.location.href=fillUrl(window.location.href).toString();};[unit,type,agency].forEach(function(el){el&&el.addEventListener('change',applyFilter);});if(year){year.addEventListener('change',applyFilter);year.addEventListener('keydown',function(e){if(e.key==='Enter'){e.preventDefault();applyFilter();}});}if(search){let timer=null;search.addEventListener('input',function(){clearTimeout(timer);timer=setTimeout(applyFilter,500);});search.addEventListener('keydown',function(e){if(e.key==='Enter'){e.preventDefault();applyFilter();}});}document.querySelectorAll('[data-report-export]').forEach(function(link){link.addEventListener('click',function(){link.href=fillUrl(link.href).toString();});});});
</script>
