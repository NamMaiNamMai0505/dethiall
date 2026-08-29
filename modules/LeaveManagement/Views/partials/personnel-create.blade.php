<form method="POST" action="{{ route('leave-management.personnel.store') }}" class="rounded-2xl border border-blue-100 bg-gradient-to-r from-blue-50 to-white p-5 shadow-sm">
    @csrf
    <div class="mb-4 flex items-center gap-3">
        <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-700 text-white shadow-md"><i class="bi bi-person-plus-fill"></i></span>
        <div><h2 class="text-lg font-extrabold text-slate-900">Thêm quân nhân</h2><p class="mt-1 text-sm font-medium text-slate-500">Nhập đầy đủ thông tin hồ sơ quân nhân để quản lý phép.</p></div>
    </div>
    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
        <div class="xl:col-span-2"><label class="mb-1.5 block text-sm font-bold text-slate-700">Họ và tên quân nhân <span class="text-rose-500">*</span></label><input name="name" required placeholder="Nhập họ và tên quân nhân" class="w-full rounded-xl border px-3 py-2.5"></div>
        <div class="xl:col-span-2"><label class="mb-1.5 block text-sm font-bold text-slate-700">Tài khoản quân nhân (nếu có)</label><select name="user_id" class="w-full rounded-xl border px-3 py-2.5"><option value="">Không liên kết tài khoản</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->code }} — {{ $user->name }}</option>@endforeach</select><span class="mt-1 block text-xs font-normal text-slate-500">Chỉ chọn khi quân nhân đã có tài khoản đăng nhập.</span></div>
        <div><label class="mb-1.5 block text-sm font-bold text-slate-700">Đối tượng</label><select name="object_type" class="w-full rounded-xl border px-3 py-2.5"><option value="">Chọn đối tượng</option>@foreach($objects as $object)<option value="{{ $object->code }}">{{ $object->name }}</option>@endforeach</select></div>
        <div><label class="mb-1.5 block text-sm font-bold text-slate-700">Cấp bậc</label><input name="rank" placeholder="Ví dụ: Đại úy" class="w-full rounded-xl border px-3 py-2.5"></div>
        <div>
            <label class="mb-1.5 block text-sm font-bold text-slate-700">Chức vụ</label>
            <select name="position" class="w-full rounded-xl border px-3 py-2.5">
                <option value="">Chọn chức vụ</option>
                @foreach(($positions ?? collect()) as $position)
                    <option value="{{ $position->name }}">{{ $position->name }}</option>
                @endforeach
                @if(!($positions ?? collect())->contains('name', 'Chỉ huy cơ quan/đơn vị'))
                    <option value="Chỉ huy cơ quan/đơn vị">Chỉ huy cơ quan/đơn vị</option>
                @endif
            </select>
        </div>
        <div><label class="mb-1.5 block text-sm font-bold text-slate-700">Ngày nhập ngũ</label><input name="enlistment_date" type="date" class="w-full rounded-xl border px-3 py-2.5"></div>
        <div class="xl:col-span-2"><label class="mb-1.5 block text-sm font-bold text-slate-700">Cơ quan tiếp nhận phép</label><input name="managing_agency_display" value="Tự động theo đối tượng" readonly class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5"></div>
        <div class="xl:col-span-2"><label class="mb-1.5 block text-sm font-bold text-slate-700">Đơn vị quân nhân</label><select name="unit_id" class="w-full rounded-xl border px-3 py-2.5"><option value="">Chọn đơn vị</option>@foreach($units as $unit)<option value="{{ $unit->id }}">{{ $unit->code }} — {{ $unit->name }}</option>@endforeach</select></div>
        <div class="xl:col-span-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"><i class="bi bi-info-circle mr-1"></i> Tài khoản tiếp nhận đề xuất được xác định tự động theo <strong>đơn vị quân nhân</strong>: tài khoản có role <strong>Chỉ huy cơ quan / đơn vị</strong> trong cùng đơn vị sẽ nhận đơn. Không cần liên kết thủ công từng quân nhân với tài khoản chỉ huy.</div>
        <div class="xl:col-span-2"><label class="mb-1.5 block text-sm font-bold text-slate-700">Quê quán</label><select name="hometown" class="w-full rounded-xl border px-3 py-2.5"><option value="">Chọn tỉnh/TP và phường/xã</option>@foreach(\Modules\LeaveManagement\Models\LeaveLocality::with('parent')->orderBy('level')->orderBy('name')->get() as $locality)@php($localityLabel=$locality->parent ? $locality->parent->name.' — '.$locality->name : $locality->name)<option value="{{ $localityLabel }}">{{ $localityLabel }}</option>@endforeach</select></div>
        <div class="xl:col-span-2"><label class="mb-1.5 block text-sm font-bold text-slate-700">Địa chỉ thường trú</label><select name="permanent_residence" class="w-full rounded-xl border px-3 py-2.5"><option value="">Chọn tỉnh/TP và phường/xã</option>@foreach(\Modules\LeaveManagement\Models\LeaveLocality::with('parent')->orderBy('level')->orderBy('name')->get() as $locality)@php($localityLabel=$locality->parent ? $locality->parent->name.' — '.$locality->name : $locality->name)<option value="{{ $localityLabel }}">{{ $localityLabel }}</option>@endforeach</select></div>
    </div>
    <button class="mt-4 rounded-xl bg-blue-700 px-5 py-2.5 font-bold text-white shadow-md shadow-blue-100 transition hover:bg-blue-800"><i class="bi bi-person-plus mr-1"></i>Thêm quân nhân</button>
    <div class="mt-3"><label class="mb-1.5 block text-sm font-bold text-slate-700">Gmail quân nhân</label><input name="gmail" type="email" placeholder="quannhan@gmail.com" class="w-full rounded-xl border px-3 py-2.5"></div>
<script>
setTimeout(() => { const position=document.querySelector('select[name="position"]'); const unit=document.querySelector('select[name="unit_id"]'); if(!position||!unit)return; const fields=['commander_name','commander_user_id','managing_agency_display'].map(name=>document.querySelector(`[name="${name}"]`)?.closest('div')).filter(Boolean); const commander=document.querySelector('[name="commander_user_id"]'); const isDepartment=()=>String(unit.selectedOptions[0]?.textContent||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().match(/(^|\s)(khoa|phong|ban)(\s|$)/); const sync=()=>{const key=String(position.value||'').normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase(); const noCommander=key.includes('hieu truong')||key.includes('pho hieu truong')||key.includes('quan luc')||key.includes('co quan can bo')||(key.includes('chi huy')&&!!isDepartment()); fields.forEach(field=>field.classList.toggle('hidden',noCommander)); if(commander){commander.required=!noCommander; if(noCommander)commander.value='';} document.querySelector('[name="commander_name"]')?.toggleAttribute('disabled',noCommander);}; position.addEventListener('change',sync); unit.addEventListener('change',sync); sync(); }, 0);
</script>
@php($commanderRecords = \Modules\LeaveManagement\Models\LeavePersonnel::withoutGlobalScopes()->with('user')->where('active', true)->get()->filter(fn($person) => \Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii((string) $person->position)), 'chi huy'))->map(function($person){ $account=$person->user?->status === 1 ? $person->user : \App\Models\User::where('status',1)->where('name',$person->name)->first(); return ['id'=>$account ? (int)$account->id : 'personnel:'.$person->id,'user_id'=>$account?->id,'name'=>$account?->name ?: $person->name,'code'=>$account?->code ?: $person->staff_code,'unit_id'=>(int)$person->unit_id]; })->unique('id')->values())
<script>
(() => {
    const unit = document.querySelector('select[name="commander_name"]');
    const commander = document.querySelector('select[name="commander_user_id"]');
    if (!unit || !commander) return;

    const records = @json($commanderRecords);
    const unitIds = @json(($units ?? collect())->mapWithKeys(fn($item) => [$item->name => $item->id]));
    window.leaveCommanderRecords = records;
    window.leaveUnitIds = unitIds;

    const label = record => `${record.code || ''} — ${record.name}${record.user_id ? '' : ' (chưa liên kết tài khoản)'}`;
    const matching = () => records.filter(record => Number(record.unit_id) === Number(unitIds[unit.value] || 0));
    const noCommanderRequired = () => {
        const position = document.querySelector('select[name="position"]');
        const unitOption = unit.selectedOptions[0]?.textContent || '';
        const positionKey = String(position?.value || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
        const unitKey = String(unitOption).normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
        return positionKey.includes('hieu truong') || positionKey.includes('pho hieu truong') || positionKey.includes('quan luc') || positionKey.includes('co quan can bo') || (positionKey.includes('chi huy') && /(^|\s)(khoa|phong|ban)(\s|$)/.test(unitKey));
    };

    const syncCommanderOptions = () => {
        if (noCommanderRequired()) {
            if (commander.tomselect) {
                commander.tomselect.clear(true);
                commander.tomselect.clearOptions();
                commander.tomselect.addOption({ value: '', text: 'Không cần tài khoản chỉ huy' });
                commander.tomselect.refreshOptions(false);
            } else {
                commander.value = '';
            }
            commander.required = false;
            return;
        }
        const options = matching().map(record => ({
            value: String(record.id),
            text: label(record),
            userId: record.user_id ? String(record.user_id) : '',
            disabled: !record.user_id,
        }));
        const firstLinked = options.find(option => option.userId);

        if (commander.tomselect) {
            const ts = commander.tomselect;
            ts.clear(true);
            ts.clearOptions();
            ts.addOption({ value: '', text: 'Tự động theo cơ quan đang chỉ huy' });
            options.forEach(option => ts.addOption(option));
            ts.refreshOptions(false);
            if (firstLinked) ts.setValue(firstLinked.value, true);
            return;
        }

        Array.from(commander.options).forEach(option => {
            if (!option.value) return;
            const allowed = options.some(item => item.value === option.value);
            option.hidden = !allowed;
            option.disabled = !allowed || option.dataset.userId === '';
        });
        commander.value = firstLinked?.value || '';
    };

    unit.addEventListener('change', syncCommanderOptions);
    if (unit.tomselect) unit.tomselect.on('change', syncCommanderOptions);
    requestAnimationFrame(syncCommanderOptions);
    setTimeout(syncCommanderOptions, 300);
})();
</script>
</form>
<script>
document.querySelectorAll('input[name="position"]').forEach(function(input){
    const select=document.createElement('select');
    select.name='position'; select.className=input.className;
    select.innerHTML='<option value="">Chọn chức vụ</option>'+(@json($positions ?? collect())).map(function(position){return '<option value="'+String(position.name).replace(/"/g,'&quot;')+'">'+String(position.name).replace(/</g,'&lt;')+'</option>';}).join('');
    input.replaceWith(select);
});
</script>
<script>
(() => { const objectSelect=document.querySelector('select[name="object_type"]'); const display=document.querySelector('input[name="managing_agency_display"]'); if(!objectSelect||!display)return; const map={SQ:'Cơ quan cán bộ',CNQP:'Cơ quan cán bộ',VCQP:'Cơ quan cán bộ',CB:'Cơ quan cán bộ'}; const sync=()=>display.value=map[objectSelect.value]||'Quân lực'; objectSelect.addEventListener('change',sync); sync(); })();
</script>
