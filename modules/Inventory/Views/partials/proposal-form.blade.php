@php
    $currentUserUnitId = auth()->user()?->unit_id;
@endphp
<div id="create-proposal" class="rounded-xl border bg-white p-5 shadow-sm">
    <div class="mb-5 flex items-start justify-between gap-4">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Tạo đề xuất thanh lý</h2>
            <p class="mt-1 text-sm text-slate-500">Lập phiếu đề xuất thanh lý vật tư theo phòng và ngành phụ trách.</p>
        </div>
        <span class="rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700">Phiếu đề xuất</span>
    </div>

    <form method="POST" action="{{ route('inventory.proposals.store') }}" class="space-y-5">
        @csrf
        @if($currentUserUnitId)
            <input type="hidden" name="unit_id" value="{{ $currentUserUnitId }}">
        @endif
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="text-sm font-semibold text-slate-700">Loại đề xuất
                <div class="mt-1 rounded-lg border bg-slate-50 p-2.5 text-slate-900">Thanh lý</div>
            </div>
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

        <div class="grid gap-4 lg:grid-cols-5">
            <label class="text-sm font-semibold text-slate-700 lg:col-span-2">Tiêu đề <span class="text-red-600">*</span>
                <input name="title" required value="{{ old('title') }}" class="mt-1 w-full rounded-lg border p-2.5" placeholder="Nhập tiêu đề đề xuất">
            </label>
            <label class="text-sm font-semibold text-slate-700 lg:col-span-3">Lý do / mô tả
                <textarea name="description" rows="2" class="mt-1 w-full rounded-lg border p-2.5" placeholder="Nhập lý do hoặc mô tả đề xuất">{{ old('description') }}</textarea>
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
                <label class="text-sm font-semibold text-slate-700">Số lượng thực tế phòng
                    <input id="proposal-room-quantity" type="number" value="0" readonly class="mt-1 w-full rounded-lg border bg-slate-100 p-2.5 text-slate-700">
                </label>
                <label class="text-sm font-semibold text-slate-700 md:col-span-3">Vị trí chi tiết
                    <input name="location_note" value="{{ old('location_note') }}" class="mt-1 w-full rounded-lg border p-2.5" placeholder="Ví dụ: Tủ số 02, dãy A, tầng 1">
                </label>
                <label class="text-sm font-semibold text-slate-700">Số lượng đề xuất
                    <input name="quantity" type="number" min=".01" step=".01" value="{{ old('quantity', 1) }}" class="mt-1 w-full rounded-lg border p-2.5">
                </label>
                <button type="button" id="add-proposal-item" class="self-end rounded-lg border border-blue-600 px-4 py-2.5 font-semibold text-blue-700 hover:bg-blue-50">+ Thêm vào danh sách</button>
            </div>
            <div id="proposal-item-preview" class="mt-4 hidden overflow-x-auto rounded-lg border bg-white">
                <table class="w-full text-left text-sm"><thead class="bg-slate-100"><tr><th class="p-3">Phòng / vị trí</th><th class="p-3">Vật tư</th><th class="p-3">Số lượng thực tế phòng</th><th class="p-3">Số lượng đề xuất</th><th class="p-3">Vị trí chi tiết</th></tr></thead><tbody id="proposal-item-preview-body"></tbody></table>
            </div>
        </div>
        <div class="flex justify-end gap-3"><a href="{{ route('inventory.proposals') }}" class="rounded-lg border px-5 py-2.5">Hủy</a><button class="rounded-lg bg-blue-600 px-5 py-2.5 font-semibold text-white hover:bg-blue-700">Gửi đề xuất thanh lý</button></div>
    </form>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const industry = document.getElementById('proposal-category');
    const type = document.getElementById('proposal-type');
    const material = document.getElementById('proposal-material');
    const classroom = document.getElementById('proposal-classroom');
    const roomQuantity = document.getElementById('proposal-room-quantity');
    const requestedQuantity = document.querySelector('[name="quantity"]');
    const add = document.getElementById('add-proposal-item');
    const preview = document.getElementById('proposal-item-preview');
    const body = document.getElementById('proposal-item-preview-body');
    const roomQuantityMap = @json(($assets ?? collect())->groupBy(fn ($asset) => ($asset->material_id ?: 0).'|'.($asset->classroom_id ?: 0))->map(fn ($items) => (float) $items->sum('quantity')));
    const syncRoomQuantity = () => {
        if (!roomQuantity) return;
        const value = roomQuantityMap[(material?.value || '0') + '|' + (classroom?.value || '0')] || 0;
        roomQuantity.value = Number.isInteger(value) ? value : Number(value).toFixed(2);
        if (requestedQuantity) {
            requestedQuantity.max = value || '';
            if (value && Number(requestedQuantity.value || 0) > Number(value)) requestedQuantity.value = value;
        }
    };
    if (industry && type && material) {
        const typeOptions = [...type.options].slice(1).map(option => option.cloneNode(true));
        const materialOptions = [...material.options].slice(1).map(option => option.cloneNode(true));
        const rebuildMaterials = () => {
            const selectedType = type.value;
            material.innerHTML = '<option value="">Chọn vật tư</option>';
            materialOptions.filter(option => selectedType && option.dataset.typeId === selectedType)
                .forEach(option => material.append(option.cloneNode(true)));
            material.value = '';
            syncRoomQuantity();
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
        material.addEventListener('change', syncRoomQuantity);
        classroom?.addEventListener('change', syncRoomQuantity);
        rebuildTypes();
    }
    if (add) add.addEventListener('click', function () { const room = document.getElementById('proposal-classroom'), quantity = document.querySelector('[name="quantity"]'), note = document.querySelector('[name="location_note"]'); if (!room.value || !material.value) return; const roomText = room.options[room.selectedIndex].text, materialText = material.options[material.selectedIndex].text; body.innerHTML = `<tr><td class="p-3">${roomText}</td><td class="p-3">${materialText}</td><td class="p-3">${roomQuantity?.value || 0}</td><td class="p-3">${quantity.value || 1}</td><td class="p-3">${note.value || '—'}</td></tr>`; preview.classList.remove('hidden'); });
    syncRoomQuantity();
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
    $proposalRoomQuantitiesJson = ($assets ?? collect())
        ->groupBy(fn ($asset) => ($asset->material_id ?: 0).'|'.($asset->classroom_id ?: 0))
        ->map(fn ($items) => (float) $items->sum('quantity'));
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
        const roomQuantity=document.getElementById('proposal-room-quantity');
        const requestedQuantity=document.querySelector('[name="quantity"]');
        const roomQuantities=@json($proposalRoomQuantitiesJson);
        const materialRooms=@json(($assets ?? collect())->groupBy('material_id')->map(fn($items)=>$items->pluck('classroom_id')->filter()->unique()->values())->all());
        const materials=@json($proposalMaterialsJson);
        const syncRoomQuantity=()=>{
            if(!roomQuantity)return;
            const key=String(material.tomselect?material.tomselect.getValue():material.value||0)+'|'+String(classroom?.value||0);
            const value=roomQuantities[key]||0;
            roomQuantity.value=Number.isInteger(value)?value:Number(value).toFixed(2);
            if(requestedQuantity){requestedQuantity.max=value||'';if(value&&Number(requestedQuantity.value||0)>Number(value))requestedQuantity.value=value;}
        };
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
            syncRoomQuantity();
        };
        classroom?.addEventListener('change',()=>rebuild());
        industry.addEventListener('change',()=>rebuild({resetType:true}));
        type.addEventListener('change',()=>rebuild());
        material.addEventListener('change',syncRoomQuantity);
        const bindTomSelect=()=>{
            if(industry.tomselect&&!industry.dataset.tomBound){industry.tomselect.on('change',()=>rebuild({resetType:true}));industry.dataset.tomBound='1';}
            if(type.tomselect&&!type.dataset.tomBound){type.tomselect.on('change',()=>rebuild());type.dataset.tomBound='1';}
            if(material.tomselect&&!material.dataset.tomBound){material.tomselect.on('change',syncRoomQuantity);material.dataset.tomBound='1';}
            rebuild();
        };
        bindTomSelect();
        setTimeout(bindTomSelect,300);setTimeout(bindTomSelect,800);
        rebuild();
    };
    document.addEventListener('DOMContentLoaded',init);document.addEventListener('turbo:load',init);setTimeout(init,250);setTimeout(init,700);
})();
</script>
