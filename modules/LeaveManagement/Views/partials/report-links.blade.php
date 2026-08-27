<style>.leave-report-page > a { display: none !important; }</style>
<div class="flex flex-wrap items-end gap-3">
    <label class="text-sm font-semibold">Đơn vị báo cáo
        <select id="leave-report-unit" class="mt-1 block w-80 rounded border p-2">
            <option value="">Tất cả đơn vị</option>
            @foreach(\Modules\Unit\Models\Unit::active()->orderBy('name')->get() as $unit)<option value="{{ $unit->id }}">{{ $unit->name }}</option>@endforeach
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
document.addEventListener('DOMContentLoaded',function(){const unit=document.getElementById('leave-report-unit'),input=document.getElementById('leave-report-recipient');document.querySelectorAll('[data-report-export]').forEach(function(link){link.addEventListener('click',function(){const url=new URL(link.href,window.location.origin);if(unit.value)url.searchParams.set('unit_id',unit.value);if(input.value)url.searchParams.set('noi_nhan',input.value);link.href=url.toString();});});});
</script>
