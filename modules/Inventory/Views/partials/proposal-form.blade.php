@php
    $currentUserUnitId = auth()->user()?->unit_id;
@endphp
<div class="rounded-xl border bg-white p-5 shadow-sm">
    <div class="mb-5 flex items-start justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Tạo đề xuất</h2>
            <p class="mt-1 text-sm text-slate-500">Lập đề xuất sửa chữa, thu hồi/trả về kho hoặc thanh lý vật tư.</p>
        </div>
        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">Phiếu đề xuất</span>
    </div>

    <form method="POST" action="{{ route('inventory.proposals.store') }}" class="space-y-5">
        @csrf
        @if($currentUserUnitId)
            <input type="hidden" name="unit_id" value="{{ $currentUserUnitId }}">
        @endif
        <div class="grid gap-4 md:grid-cols-3">
            <label class="text-sm font-semibold text-slate-700">Loại đề xuất <span class="text-red-600">*</span>
                <select name="type" required class="mt-1 w-full rounded-lg border p-2.5">
                    <option value="">Chọn loại đề xuất</option>
                    <option value="REPAIR" @selected(old('type') === 'REPAIR' || ($section === 'proposals' && !old('type'))) >Sửa chữa</option>
                    <option value="RECALL" @selected(old('type') === 'RECALL')>Thu hồi / trả về kho</option>
                    <option value="LIQUIDATION" @selected(old('type') === 'LIQUIDATION' || $section === 'liquidation')>Thanh lý</option>
                </select>
            </label>
            <label class="text-sm font-semibold text-slate-700">Đơn vị đề xuất
                <select name="unit_id" @disabled($currentUserUnitId) class="mt-1 w-full rounded-lg border p-2.5">
                    <option value="">Chọn đơn vị đề xuất</option>
                    @foreach($units as $unit)<option value="{{ $unit->id }}" @selected(old('unit_id', $currentUserUnitId) == $unit->id)>{{ $unit->name }}</option>@endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-slate-700">Ngành nhận đề xuất <span class="text-red-600">*</span>
                <select name="nganh_code" id="proposal-category" required class="mt-1 w-full rounded-lg border p-2.5">
                    <option value="">Chọn ngành nhận đề xuất</option>
                    @foreach($categories as $category)<option value="{{ $category->code }}" data-industry-id="{{ $category->id }}" @selected(old('nganh_code') === $category->code)>{{ $category->code }} — {{ $category->name }}</option>@endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-slate-700">Loại vật tư <span class="text-red-600">*</span>
                <select name="loai_id" id="proposal-type" required class="mt-1 w-full rounded-lg border p-2.5">
                    <option value="">Chọn loại vật tư</option>
                    @foreach($types as $type)<option value="{{ $type->id }}" data-industry-id="{{ $type->parent_id }}">{{ $type->code }} — {{ $type->name }}</option>@endforeach
                </select>
            </label>
        </div>
        <p class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600">Chọn đúng ngành nhận đề xuất để bộ phận phụ trách tiếp nhận và xử lý phiếu.</p>

        <div class="grid gap-4 md:grid-cols-2">
            <label class="text-sm font-semibold text-slate-700">Tiêu đề <span class="text-red-600">*</span>
                <input name="title" required value="{{ old('title') }}" class="mt-1 w-full rounded-lg border p-2.5" placeholder="Nhập tiêu đề đề xuất">
            </label>
            <label class="text-sm font-semibold text-slate-700">Lý do / mô tả
                <textarea name="description" rows="2" class="mt-1 w-full rounded-lg border p-2.5" placeholder="Nhập lý do hoặc mô tả đề xuất">{{ old('description') }}</textarea>
            </label>
        </div>

        <div id="proposal-recall-warehouse" class="hidden rounded-lg border border-amber-200 bg-amber-50 p-3">
            <label class="text-sm font-semibold text-slate-700">Kho nhận khi thu hồi / trả kho <span class="text-red-600">*</span>
                <select name="warehouse_id" id="proposal-warehouse" class="mt-1 w-full rounded-lg border bg-white p-2.5">
                    <option value="">Chọn kho nhận</option>
                    @foreach(\Modules\Inventory\Models\InventoryWarehouse::where('active', true)->orderBy('name')->get() as $warehouse)<option value="{{ $warehouse->id }}" @selected(old('warehouse_id') == $warehouse->id)>{{ $warehouse->code }} — {{ $warehouse->name }}</option>@endforeach
                </select>
            </label>
        </div>
        <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50/60 p-4">
            <div class="mb-3 flex items-center justify-between"><div><h3 class="font-bold text-slate-800">Thêm vật tư</h3><p class="text-xs text-slate-500">Chọn phòng và vật tư cần xử lý trong đề xuất.</p></div></div>
            <div class="grid gap-4 md:grid-cols-5">
                <label class="text-sm font-semibold text-slate-700 md:col-span-2">Phòng / vị trí <span class="text-red-600">*</span>
                    <select name="classroom_id" id="proposal-classroom" required class="mt-1 w-full rounded-lg border bg-white p-2.5">
                        <option value="">Chọn phòng / vị trí</option>
                        @foreach($classrooms as $room)<option value="{{ $room->id }}" @selected(old('classroom_id') == $room->id)>{{ $room->building?->name ? $room->building->name.' — ' : '' }}{{ $room->name }}</option>@endforeach
                    </select>
                </label>
                <label class="text-sm font-semibold text-slate-700 md:col-span-2">Vật tư (theo ngành + phòng) <span class="text-red-600">*</span>
                    <select name="material_id" id="proposal-material" required class="mt-1 w-full rounded-lg border bg-white p-2.5">
                        <option value="">Chọn vật tư</option>
                        @foreach($materials as $material)<option value="{{ $material->id }}" data-type-id="{{ $material->category_id }}">{{ $material->code }} — {{ $material->name }}</option>@endforeach
                    </select>
                </label>
                <label class="text-sm font-semibold text-slate-700">Số lượng
                    <input name="quantity" type="number" min=".01" step=".01" value="{{ old('quantity', 1) }}" class="mt-1 w-full rounded-lg border p-2.5">
                </label>
                <label class="text-sm font-semibold text-slate-700 md:col-span-4">Vị trí chi tiết
                    <input name="location_note" value="{{ old('location_note') }}" class="mt-1 w-full rounded-lg border p-2.5" placeholder="Ví dụ: Tủ số 02, dãy A, tầng 1">
                </label>
                <button type="button" id="add-proposal-item" class="self-end rounded-lg border border-blue-600 px-4 py-2.5 font-semibold text-blue-700 hover:bg-blue-50">+ Thêm vào danh sách</button>
            </div>
            <div id="proposal-item-preview" class="mt-4 hidden overflow-x-auto rounded-lg border bg-white">
                <table class="w-full text-left text-sm"><thead class="bg-slate-100"><tr><th class="p-3">Phòng / vị trí</th><th class="p-3">Vật tư</th><th class="p-3">Số lượng</th><th class="p-3">Vị trí chi tiết</th></tr></thead><tbody id="proposal-item-preview-body"></tbody></table>
            </div>
        </div>
        <div class="flex justify-end gap-3"><a href="{{ route('inventory.proposals') }}" class="rounded-lg border px-5 py-2.5">Hủy</a><button class="rounded-lg bg-blue-600 px-5 py-2.5 font-semibold text-white hover:bg-blue-700">Gửi đề xuất</button></div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const industry = document.getElementById('proposal-category');
    const type = document.getElementById('proposal-type');
    const material = document.getElementById('proposal-material');
    const add = document.getElementById('add-proposal-item');
    const preview = document.getElementById('proposal-item-preview');
    const body = document.getElementById('proposal-item-preview-body');
    if (industry && type && material) {
        const typeOptions = [...type.options].slice(1).map(option => option.cloneNode(true));
        const materialOptions = [...material.options].slice(1).map(option => option.cloneNode(true));
        const rebuildMaterials = () => {
            const selectedType = type.value;
            material.innerHTML = '<option value="">Chọn vật tư</option>';
            materialOptions.filter(option => selectedType && option.dataset.typeId === selectedType)
                .forEach(option => material.append(option.cloneNode(true)));
            material.value = '';
        };
        const rebuildTypes = () => {
            type.innerHTML = '<option value="">Chọn loại vật tư</option>';
            const industryId = industry.selectedOptions[0]?.dataset.industryId || '';
            typeOptions.filter(option => industryId && option.dataset.industryId === industryId)
                .forEach(option => type.append(option.cloneNode(true)));
            type.value = '';
            rebuildMaterials();
        };
        industry.addEventListener('change', rebuildTypes);
        type.addEventListener('change', rebuildMaterials);
        rebuildTypes();
    }
    if (add) add.addEventListener('click', function () { const room = document.getElementById('proposal-classroom'), quantity = document.querySelector('[name="quantity"]'), note = document.querySelector('[name="location_note"]'); if (!room.value || !material.value) return; const roomText = room.options[room.selectedIndex].text, materialText = material.options[material.selectedIndex].text; body.innerHTML = `<tr><td class="p-3">${roomText}</td><td class="p-3">${materialText}</td><td class="p-3">${quantity.value || 1}</td><td class="p-3">${note.value || '—'}</td></tr>`; preview.classList.remove('hidden'); });
});
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const type = document.querySelector('select[name="type"]');
    const box = document.getElementById('proposal-recall-warehouse');
    const warehouse = document.getElementById('proposal-warehouse');
    const sync = () => { const recall = type?.value === 'RECALL'; box?.classList.toggle('hidden', !recall); if (warehouse) warehouse.required = recall; };
    type?.addEventListener('change', sync); sync();
});
</script>
@php
    $proposalTypesJson = ($types ?? collect())->map(fn ($item) => [
        'value' => (string) $item->id,
        'text' => $item->code.' — '.$item->name,
        'parent' => (string) $item->parent_id,
    ])->values();
    $proposalMaterialRooms = ($assets ?? collect())
        ->groupBy('material_id')
        ->map(fn ($items) => $items->pluck('classroom_id')->filter()->unique()->values())
        ->all();
    $proposalMaterialsJson = ($materials ?? collect())->map(fn ($item) => [
        'value' => (string) $item->id,
        'text' => $item->code.' — '.$item->name,
        'type' => (string) $item->category_id,
        'rooms' => ($proposalMaterialRooms[$item->id] ?? collect())
            ->map(fn ($id) => (string) $id)->values()->all(),
    ])->values();
@endphp
<script>
(()=>{
    const init=()=>{
        const industry=document.getElementById('proposal-category'),type=document.getElementById('proposal-type'),material=document.getElementById('proposal-material');
        if(!industry||!type||!material||industry.dataset.cascadeFinal==='1')return;
        industry.dataset.cascadeFinal='1';
        const types=@json($proposalTypesJson);
        const classroom=document.getElementById('proposal-classroom');
        const materialRooms=@json(($assets ?? collect())->groupBy('material_id')->map(fn($items)=>$items->pluck('classroom_id')->filter()->unique()->values())->all());
        const materials=@json($proposalMaterialsJson);
        const setOptions=(select,items,empty)=>{
            const current=select.value;
            if(select.tomselect){
                select.tomselect.clear(true);select.tomselect.clearOptions();select.tomselect.addOptions(items.map(item=>({value:String(item.value),text:item.text})));select.tomselect.refreshOptions(false);
                if(items.some(item=>String(item.value)===String(current)))select.tomselect.setValue(String(current),true);
            }else{
                select.innerHTML=`<option value="">${empty}</option>`+items.map(item=>`<option value="${item.value}">${item.text}</option>`).join('');
                select.value=items.some(item=>String(item.value)===String(current))?current:'';
            }
        };
        const rebuild=({resetType=false}={})=>{
            const industryId=industry.selectedOptions[0]?.dataset.industryId||[...industry.options].find(option=>option.value===industry.value)?.dataset.industryId||'';
            const selectedType=resetType?'':type.value;
            const allowedTypes=types.filter(item=>industryId&&String(item.parent)===String(industryId));
            setOptions(type,allowedTypes,'Chọn loại vật tư');
            if(resetType||!allowedTypes.some(item=>String(item.value)===String(selectedType))){
                if(type.tomselect)type.tomselect.clear(true);else type.value='';
            }
            const typeId=type.tomselect?type.tomselect.getValue():type.value;
            const roomId=classroom?.value||'';
            setOptions(material,materials.filter(item=>typeId&&String(item.type)===String(typeId)&&roomId&&(item.rooms||[]).includes(String(roomId))),'Chọn vật tư');
        };
        classroom?.addEventListener('change',()=>rebuild());
        industry.addEventListener('change',()=>rebuild({resetType:true}));
        type.addEventListener('change',()=>rebuild());
        const bindTomSelect=()=>{
            if(industry.tomselect&&!industry.dataset.tomBound){industry.tomselect.on('change',()=>rebuild({resetType:true}));industry.dataset.tomBound='1';}
            if(type.tomselect&&!type.dataset.tomBound){type.tomselect.on('change',()=>rebuild());type.dataset.tomBound='1';}
            rebuild();
        };
        bindTomSelect();
        setTimeout(bindTomSelect,300);setTimeout(bindTomSelect,800);
        rebuild();
    };
    document.addEventListener('DOMContentLoaded',init);document.addEventListener('turbo:load',init);setTimeout(init,250);setTimeout(init,700);
})();
</script>
