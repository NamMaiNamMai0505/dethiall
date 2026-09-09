@php
    $selectedLogType = $logType ?? request('log_type') ?: 'all';
    $statuses = ['PENDING'=>'Chờ duyệt','APPROVED'=>'Đã duyệt','REJECTED'=>'Từ chối','COMPLETED'=>'Đã hoàn thành','OPEN'=>'Đang xử lý','ASSIGNED'=>'Đã phân công','CANCELLED'=>'Đã hủy'];
@endphp
<form method="GET" id="inventory-log-filter-form" class="rounded border bg-white p-4">
    <h2 class="mb-3 font-semibold">Bộ lọc nhật ký vật tư</h2>
    <div class="grid gap-3 md:grid-cols-12">
        <label class="text-sm font-semibold md:col-span-4">Loại nhật ký
            <select name="log_type" id="inventory-log-type" class="mt-1 w-full rounded border p-2">
                <option value="all" @selected($selectedLogType === 'all')>Tất cả</option>
                <option value="update" @selected($selectedLogType === 'update')>Cập nhật</option>
                <option value="movement" @selected($selectedLogType === 'movement')>Nhập xuất</option>
                <option value="broken" @selected($selectedLogType === 'broken')>Hỏng / sửa chữa</option>
                <option value="transfer" @selected($selectedLogType === 'transfer')>Điều động / thu hồi</option>
                <option value="proposal" @selected($selectedLogType === 'proposal')>Đề xuất / thanh lý</option>
                <option value="repair" @selected($selectedLogType === 'repair')>Lịch sử sửa chữa</option>
            </select>
        </label>
        <label class="text-sm font-semibold md:col-span-6">Mã số vật tư
            <input name="asset_code" value="{{ request('asset_code') }}" placeholder="Nhập mã vật tư" class="mt-1 w-full rounded border p-2">
        </label>
        <div class="flex items-end md:col-span-2">
            <a href="{{ route('inventory.logs') }}" class="w-full rounded border px-4 py-2 text-center">Xóa lọc</a>
        </div>
    </div>
    <div class="mt-4 grid gap-3 md:grid-cols-4">
        <label class="log-filter-field text-sm font-semibold" data-log-filter="update">Loại cập nhật
            <select name="update_action" class="mt-1 w-full rounded border p-2">
                <option value="">Tất cả</option>
                <option value="CREATE" @selected(request('update_action') === 'CREATE')>Thêm mới</option>
                <option value="Thu hồi / trả về kho" @selected(request('update_action') === 'Thu hồi / trả về kho')>Thu hồi / trả kho</option>
                <option value="DECREASE" @selected(request('update_action') === 'DECREASE')>Giảm</option>
                <option value="INCREASE" @selected(request('update_action') === 'INCREASE')>Tăng</option>
                <option value="Thanh lý" @selected(request('update_action') === 'Thanh lý')>Thanh lý</option>
                <option value="UPDATE" @selected(request('update_action') === 'UPDATE')>Cập nhật</option>
                <option value="TRANSFER" @selected(request('update_action') === 'TRANSFER')>Điều động</option>
            </select>
        </label>
        <label class="log-filter-field text-sm font-semibold" data-log-filter="movement">Loại nhập xuất
            <select name="movement_type" class="mt-1 w-full rounded border p-2">
                <option value="">Tất cả</option>
                <option value="IN" @selected(request('movement_type') === 'IN')>Nhập</option>
                <option value="OUT" @selected(request('movement_type') === 'OUT')>Xuất</option>
                <option value="ADJUST" @selected(request('movement_type') === 'ADJUST')>Điều chỉnh</option>
            </select>
        </label>
        <label class="log-filter-field text-sm font-semibold" data-log-filter="movement">Người thực hiện
            <select name="performed_by" class="mt-1 w-full rounded border p-2">
                <option value="">Tất cả</option>
                @foreach(($users ?? collect()) as $user)
                    <option value="{{ $user->id }}" @selected(request('performed_by') == $user->id)>{{ $user->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="log-filter-field text-sm font-semibold" data-log-filter="transfer">Loại điều động
            <select name="transfer_type" class="mt-1 w-full rounded border p-2">
                <option value="">Tất cả</option>
                <option value="TRANSFER" @selected(request('transfer_type') === 'TRANSFER')>Điều động</option>
                <option value="RECALL" @selected(request('transfer_type') === 'RECALL')>Thu hồi</option>
            </select>
        </label>
        <label class="log-filter-field text-sm font-semibold" data-log-filter="broken transfer proposal">Từ ngày
            <input type="date" name="from" value="{{ request('from') }}" class="mt-1 w-full rounded border p-2">
        </label>
        <label class="log-filter-field text-sm font-semibold" data-log-filter="broken transfer proposal">Đến ngày
            <input type="date" name="to" value="{{ request('to') }}" class="mt-1 w-full rounded border p-2">
        </label>
        <label class="log-filter-field text-sm font-semibold" data-log-filter="broken">Loại hỏng / sửa chữa
            <select name="broken_event_type" class="mt-1 w-full rounded border p-2">
                <option value="">Tất cả</option>
                <option value="BROKEN" @selected(request('broken_event_type') === 'BROKEN')>Hỏng</option>
                <option value="REPAIR" @selected(request('broken_event_type') === 'REPAIR')>Sửa chữa</option>
            </select>
        </label>
        <label class="log-filter-field text-sm font-semibold" data-log-filter="broken">Trạng thái sau
            <select name="broken_status" class="mt-1 w-full rounded border p-2">
                <option value="">Tất cả</option>
                <option value="BROKEN" @selected(request('broken_status') === 'BROKEN')>Hỏng</option>
                <option value="REPAIRING" @selected(request('broken_status') === 'REPAIRING')>Đang sửa chữa</option>
                <option value="NORMAL" @selected(request('broken_status') === 'NORMAL')>Bình thường</option>
            </select>
        </label>
        <label class="log-filter-field text-sm font-semibold" data-log-filter="transfer">Phòng nguồn
            <select name="from_classroom_id" class="mt-1 w-full rounded border p-2">
                <option value="">Tất cả</option>
                @foreach(($classrooms ?? collect()) as $room)
                    <option value="{{ $room->id }}" @selected(request('from_classroom_id') == $room->id)>{{ $room->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="log-filter-field text-sm font-semibold" data-log-filter="transfer">Phòng đích
            <select name="to_classroom_id" class="mt-1 w-full rounded border p-2">
                <option value="">Tất cả</option>
                @foreach(($classrooms ?? collect()) as $room)
                    <option value="{{ $room->id }}" @selected(request('to_classroom_id') == $room->id)>{{ $room->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="log-filter-field text-sm font-semibold" data-log-filter="transfer">Đơn vị thực hiện
            <input name="performing_unit" value="{{ request('performing_unit') }}" class="mt-1 w-full rounded border p-2">
        </label>
        <label class="log-filter-field text-sm font-semibold" data-log-filter="proposal">Loại đề xuất
            <select name="proposal_type" class="mt-1 w-full rounded border p-2">
                <option value="">Tất cả</option>
                <option value="LIQUIDATION" @selected(request('proposal_type') === 'LIQUIDATION')>Thanh lý</option>
                <option value="RECALL" @selected(request('proposal_type') === 'RECALL')>Thu hồi</option>
                <option value="REPAIR" @selected(request('proposal_type') === 'REPAIR')>Sửa chữa</option>
            </select>
        </label>
        <label class="log-filter-field text-sm font-semibold" data-log-filter="proposal">Đơn vị đề xuất
            <select name="unit_id" class="mt-1 w-full rounded border p-2">
                <option value="">Tất cả</option>
                @foreach(($units ?? collect()) as $unit)
                    <option value="{{ $unit->id }}" @selected(request('unit_id') == $unit->id)>{{ $unit->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="log-filter-field text-sm font-semibold" data-log-filter="transfer proposal repair">Trạng thái
            <select name="status" class="mt-1 w-full rounded border p-2">
                <option value="">Tất cả</option>
                @foreach($statuses as $key => $label)
                    <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        <label class="log-filter-field text-sm font-semibold" data-log-filter="repair">Người sửa
            <input name="repairer" value="{{ request('repairer') }}" class="mt-1 w-full rounded border p-2">
        </label>
        <label class="log-filter-field text-sm font-semibold" data-log-filter="repair">Ngày bắt đầu
            <input type="date" name="started_from" value="{{ request('started_from') }}" class="mt-1 w-full rounded border p-2">
        </label>
    </div>
</form>
<script>
    document.addEventListener('DOMContentLoaded',()=>{
        const form=document.getElementById('inventory-log-filter-form'),select=document.getElementById('inventory-log-type');
        if(!form||!select)return;
        const text=v=>(v||'').toString().trim().toLowerCase();
        const matchesTerm=(content,term)=>term&&(term.length<=2?content.split(/\s+/).includes(term):content.includes(term));
        const valueAliases=value=>({in:['in','nhập'],out:['out','xuất'],adjust:['adjust','điều chỉnh'],create:['create','thêm mới'],update:['update','cập nhật'],delete:['delete','xóa'],transfer:['transfer','điều động'],recall:['recall','thu hồi'],purchase:['purchase','đề xuất'],liquidation:['liquidation','thanh lý'],broken:['broken','hỏng'],repair:['repair','sửa chữa']}[value]||[value]);
        const parseDate=value=>{
            const raw=(value||'').toString().trim();
            let match=raw.match(/^(\d{4})-(\d{2})-(\d{2})$/);
            if(match)return new Date(+match[1],+match[2]-1,+match[3]);
            match=raw.match(/(\d{1,2})\/(\d{1,2})\/(\d{4})/);
            if(match)return new Date(+match[3],+match[2]-1,+match[1]);
            return null;
        };
        const rowDate=row=>{
            const match=((row.dataset.filterText||'')+' '+row.textContent).match(/(\d{1,2})\/(\d{1,2})\/(\d{4})/);
            return match?new Date(+match[3],+match[2]-1,+match[1]):null;
        };
        const isActiveInput=input=>{
            if(!text(input.value))return false;
            if(input.tagName==='SELECT'){
                const label=text(input.selectedOptions?.[0]?.textContent);
                return label&&label!=='tất cả';
            }
            return true;
        };
        const filterControls=field=>[...field.querySelectorAll('input[name],select[name],textarea[name]')];
        const clearChildFilters=()=>document.querySelectorAll('.log-filter-field input[name],.log-filter-field select[name],.log-filter-field textarea[name]').forEach(input=>{if(input.tomselect)input.tomselect.clear(true);else input.value='';});
        const activeFilters=type=>[...document.querySelectorAll('.log-filter-field')].filter(field=>!field.classList.contains('hidden')&&type!=='all'&&field.dataset.logFilter.split(' ').includes(type)).flatMap(filterControls).filter(isActiveInput);
        const rowMatches=(row,filters)=>{
            const content=text((row.dataset.filterText||'')+' '+row.textContent);
            return filters.every(input=>{
                if(input.type==='date'||input.name==='from'||input.name==='to'){
                    const expected=parseDate(input.value),actual=rowDate(row);
                    if(!expected)return true;
                    if(!actual)return false;
                    return input.name==='from'?actual>=expected:actual<=expected;
                }
                if(input.tagName==='SELECT'){
                    const label=text(input.selectedOptions?.[0]?.textContent);
                    const value=text(input.value);
                    if(input.name==='from_classroom_id')return text(row.dataset.fromRoomId)===value||text(row.dataset.fromRoom)===label;
                    if(input.name==='to_classroom_id')return text(row.dataset.toRoomId)===value||text(row.dataset.toRoom)===label;
                    if(input.name==='proposal_type')return text(row.dataset.proposalType)===value;
                    if(input.name==='unit_id')return text(row.dataset.unitId)===value;
                    if(input.name==='status'&&row.dataset.status)return text(row.dataset.status)===value;
                    return !label||label==='tất cả'||matchesTerm(content,label)||valueAliases(value).some(term=>matchesTerm(content,term));
                }
                return content.includes(text(input.value));
            });
        };
        const applyRowFilters=type=>{
            document.querySelectorAll('[data-log-panel]').forEach(panel=>{
                const filters=panel.dataset.logPanel===type?activeFilters(type):[];
                panel.querySelectorAll('tbody tr').forEach(row=>{
                    const isEdit=row.id&&row.id.includes('edit');
                    if(isEdit){row.classList.add('hidden');return;}
                    row.classList.toggle('hidden',filters.length>0&&!rowMatches(row,filters));
                });
            });
        };
        const sync=()=>{
            const type=select.value||'all',fields=[...document.querySelectorAll('.log-filter-field')],panels=[...document.querySelectorAll('[data-log-panel]')];
            fields.forEach(field=>field.classList.toggle('hidden',type==='all'||!field.dataset.logFilter.split(' ').includes(type)));
            panels.forEach(panel=>panel.classList.toggle('hidden',type!=='all'&&panel.dataset.logPanel!==type));
            applyRowFilters(type);
            const url=new URL(window.location.href);
            if(type==='all')url.searchParams.delete('log_type');else url.searchParams.set('log_type',type);
            window.history.replaceState({},'',url);
        };
        select.addEventListener('change',()=>{clearChildFilters();sync();});
        form.querySelectorAll('input[name],select[name],textarea[name]').forEach(input=>{if(input!==select)input.addEventListener(input.tagName==='SELECT'||input.type==='date'?'change':'input',sync);});
        form.addEventListener('submit',event=>event.preventDefault());
        sync();
    });
</script>
