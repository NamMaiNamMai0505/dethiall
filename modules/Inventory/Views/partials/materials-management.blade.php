<div class="space-y-4">
    <form method="GET" class="flex flex-wrap items-end gap-3 rounded border bg-white p-4">
        <label class="text-sm font-semibold">Ngành vật tư<select name="industry_id" class="mt-1 w-full rounded border p-2 md:w-56"><option value="">Tất cả ngành</option>@foreach($industries as $industry)<option value="{{ $industry->id }}" @selected(($industryId ?? '') == $industry->id)>{{ $industry->code }} — {{ $industry->name }}</option>@endforeach</select></label>
        <label class="text-sm font-semibold">Loại vật tư<select name="category_id" class="mt-1 w-full rounded border p-2 md:w-56"><option value="">Tất cả loại</option>@foreach($categories as $category)<option value="{{ $category->id }}" data-industry="{{ $category->parent_id }}" @selected(($typeId ?? '') == $category->id)>{{ $category->code }} — {{ $category->name }}</option>@endforeach</select></label>
        <label class="w-full text-sm font-semibold md:w-auto">Tìm theo mã/tên<input name="search" value="{{ request('search') }}" placeholder="Mã hoặc tên vật tư" class="mt-1 w-full rounded border p-2 md:w-80"></label>
        <button class="rounded bg-blue-600 px-4 py-2 text-white">Tìm kiếm</button><a href="{{ route('inventory.materials') }}" class="rounded border px-4 py-2">Xóa lọc</a>
    </form>
    <form method="POST" action="{{ route('inventory.store') }}" class="grid gap-3 rounded border bg-white p-4 md:grid-cols-4">
        @csrf
        <h2 class="font-semibold md:col-span-4">Thêm vật tư</h2>
        <label class="text-sm font-semibold text-slate-700">Mã vật tư <b class="text-red-500">*</b><input name="code" required placeholder="Nhập mã vật tư" class="mt-1 w-full rounded border p-2"></label>
        <label class="text-sm font-semibold text-slate-700">Tên vật tư <b class="text-red-500">*</b><input name="name" required placeholder="Nhập tên vật tư" class="mt-1 w-full rounded border p-2"></label>
        <label class="text-sm font-semibold text-slate-700">Ngành vật tư <b class="text-red-500">*</b><select name="industry_id" id="material-industry" required class="mt-1 w-full rounded border p-2"><option value="">Chọn ngành vật tư</option>@foreach($industries as $industry)<option value="{{ $industry->id }}">{{ $industry->code }} — {{ $industry->name }}</option>@endforeach</select></label>
        <label class="text-sm font-semibold text-slate-700">Loại vật tư <b class="text-red-500">*</b><select name="category_id" id="material-category" required class="mt-1 w-full rounded border p-2"><option value="">Chọn loại vật tư</option>@foreach(($allCategories ?? $categories) as $category)<option value="{{ $category->id }}" data-industry="{{ $category->parent_id }}">{{ $category->code }} — {{ $category->name }}</option>@endforeach</select></label>
        <label class="text-sm font-semibold text-slate-700">Đơn vị tính <b class="text-red-500">*</b><input name="unit" required value="cái" placeholder="Ví dụ: cái, bộ, hộp" class="mt-1 w-full rounded border p-2"></label>
        <label class="text-sm font-semibold text-slate-700">Số lượng<input name="quantity" type="number" min="0" step=".01" value="0" placeholder="Nhập số lượng" class="mt-1 w-full rounded border p-2"></label>
        <label class="text-sm font-semibold text-slate-700">Năm sản xuất<input name="manufacture_year" type="number" placeholder="Nhập năm sản xuất" class="mt-1 w-full rounded border p-2"></label>
        <label class="text-sm font-semibold text-slate-700">Năm sử dụng<input name="usage_year" type="number" placeholder="Nhập năm sử dụng" class="mt-1 w-full rounded border p-2"></label>
        <label class="text-sm font-semibold text-slate-700">Phân cấp<input name="classification" placeholder="Nhập phân cấp" class="mt-1 w-full rounded border p-2"></label>
        <label class="text-sm font-semibold text-slate-700">Trạng thái<select name="status" class="mt-1 w-full rounded border p-2"><option value="ACTIVE">Đang hoạt động</option><option value="INACTIVE">Ngừng hoạt động</option></select></label>
        <label class="text-sm font-semibold text-slate-700">Ngày mua<input name="purchase_date" type="date" class="mt-1 w-full rounded border p-2"></label>
        <label class="text-sm font-semibold text-slate-700">Ngày hết hạn bảo hành<input name="expiry_date" type="date" class="mt-1 w-full rounded border p-2"></label>
        <button class="rounded bg-blue-600 px-4 py-2 text-white md:col-span-4">Thêm vật tư</button>
    </form>
    @php($materialStatuses = ['ACTIVE' => 'Đang hoạt động', 'NORMAL' => 'Bình thường', 'BROKEN' => 'Hỏng', 'REPAIRING' => 'Đang sửa chữa', 'LIQUIDATED' => 'Đã thanh lý', 'INACTIVE' => 'Ngừng hoạt động'])
    <div class="overflow-x-auto rounded border bg-white p-4"><h2 class="mb-3 font-semibold">Bảng vật tư</h2><table class="w-full min-w-[1200px] text-left text-sm"><thead class="bg-slate-100"><tr><th class="p-3">STT</th><th class="p-3">Mã</th><th class="p-3">Tên vật tư</th><th class="p-3">Loại</th><th class="p-3">Đơn vị tính</th><th class="p-3">Số lượng</th><th class="p-3">Năm SX</th><th class="p-3">Năm SD</th><th class="p-3">Phân cấp</th><th class="p-3">Trạng thái</th><th class="p-3">Ngày mua</th><th class="p-3">HSD bảo hành</th><th class="p-3">Thao tác</th></tr></thead><tbody>
        @forelse($materials as $i=>$item)
            @php($cannotDelete = (($item->warehouse_items_count ?? 0) + ($item->proposal_items_count ?? 0) + ($item->transfers_count ?? 0)) > 0)
            <tr class="border-t align-top"><td class="p-3">{{ $i+1 }}</td><td class="p-3">{{ $item->code }}</td><td class="p-3 font-semibold">{{ $item->name }}</td><td class="p-3">{{ $item->category?->name ?: '—' }}</td><td class="p-3">{{ $item->unit }}</td><td class="p-3">{{ $item->quantity }}</td><td class="p-3">{{ $item->manufacture_year ?: '—' }}</td><td class="p-3">{{ $item->usage_year ?: '—' }}</td><td class="p-3">{{ $item->classification ?: '—' }}</td><td class="p-3">{{ $materialStatuses[$item->status] ?? 'Không xác định' }}</td><td class="p-3">{{ $item->purchase_date?->format('d/m/Y') ?: '—' }}</td><td class="p-3">{{ $item->expiry_date?->format('d/m/Y') ?: '—' }}</td><td class="p-3"><button type="button" class="rounded bg-blue-600 px-3 py-2 text-white" onclick="document.getElementById('edit-row-{{ $item->id }}').classList.toggle('hidden')">Sửa</button>@if($cannotDelete)<div class="mt-2 rounded border border-amber-200 bg-amber-50 px-3 py-2 text-xs font-semibold text-amber-700">Đang sử dụng</div>@else<form method="POST" action="{{ route('inventory.destroy',$item) }}" class="mt-2">@csrf @method('DELETE')<button class="rounded bg-rose-600 px-3 py-2 text-white" onclick="return confirm('Bạn có chắc muốn xóa vật tư này?')">Xóa</button></form>@endif</td></tr>
            <tr id="edit-row-{{ $item->id }}" class="hidden bg-blue-50"><td colspan="13" class="p-4"><form method="POST" action="{{ route('inventory.update',$item) }}" class="grid gap-3 rounded-lg border bg-white p-4 md:grid-cols-4">@csrf @method('PATCH')<div class="font-bold md:col-span-4">Chỉnh sửa đầy đủ thông tin vật tư: {{ $item->code }}</div><label class="text-sm font-semibold">Mã vật tư<input name="code" value="{{ $item->code }}" required class="mt-1 w-full rounded border p-2"></label><label class="text-sm font-semibold">Tên vật tư<input name="name" value="{{ $item->name }}" required class="mt-1 w-full rounded border p-2"></label><label class="text-sm font-semibold">Ngành vật tư<select name="industry_id" class="mt-1 w-full rounded border p-2"><option value="">Chọn ngành vật tư</option>@foreach($industries as $industry)<option value="{{ $industry->id }}" @selected($item->category?->parent_id == $industry->id)>{{ $industry->code }} — {{ $industry->name }}</option>@endforeach</select></label><label class="text-sm font-semibold">Loại vật tư<select name="category_id" required class="mt-1 w-full rounded border p-2"><option value="">Chọn loại vật tư</option>@foreach(($allCategories ?? $categories) as $category)<option value="{{ $category->id }}" data-industry="{{ $category->parent_id }}" @selected($item->category_id == $category->id)>{{ $category->code }} — {{ $category->name }}</option>@endforeach</select></label><label class="text-sm font-semibold">Đơn vị tính<input name="unit" value="{{ $item->unit }}" required class="mt-1 w-full rounded border p-2"></label><label class="text-sm font-semibold">Số lượng<input name="quantity" type="number" min="0" step=".01" value="{{ $item->quantity }}" required class="mt-1 w-full rounded border p-2"></label><label class="text-sm font-semibold">Năm sản xuất<input name="manufacture_year" type="number" value="{{ $item->manufacture_year }}" class="mt-1 w-full rounded border p-2"></label><label class="text-sm font-semibold">Năm sử dụng<input name="usage_year" type="number" value="{{ $item->usage_year }}" class="mt-1 w-full rounded border p-2"></label><label class="text-sm font-semibold">Phân cấp<input name="classification" value="{{ $item->classification }}" class="mt-1 w-full rounded border p-2"></label><label class="text-sm font-semibold">Trạng thái<select name="status" class="mt-1 w-full rounded border p-2"><option value="ACTIVE" @selected($item->status === 'ACTIVE')>Đang hoạt động</option><option value="NORMAL" @selected($item->status === 'NORMAL')>Bình thường</option><option value="BROKEN" @selected($item->status === 'BROKEN')>Hỏng</option><option value="REPAIRING" @selected($item->status === 'REPAIRING')>Đang sửa chữa</option><option value="LIQUIDATED" @selected($item->status === 'LIQUIDATED')>Đã thanh lý</option><option value="INACTIVE" @selected($item->status === 'INACTIVE')>Ngừng hoạt động</option></select></label><label class="text-sm font-semibold">Ngày mua<input name="purchase_date" type="date" value="{{ $item->purchase_date?->format('Y-m-d') }}" class="mt-1 w-full rounded border p-2"></label><label class="text-sm font-semibold">Ngày hết hạn bảo hành<input name="expiry_date" type="date" value="{{ $item->expiry_date?->format('Y-m-d') }}" class="mt-1 w-full rounded border p-2"></label><label class="text-sm font-semibold md:col-span-2">Vị trí / địa chỉ<input name="location" value="{{ $item->location }}" class="mt-1 w-full rounded border p-2"></label><label class="text-sm font-semibold md:col-span-4">Ghi chú / mô tả<textarea name="description" rows="2" class="mt-1 w-full rounded border p-2">{{ $item->description }}</textarea></label><input type="hidden" name="building_id" value="{{ $item->building_id }}"><input type="hidden" name="classroom_id" value="{{ $item->classroom_id }}"><div class="flex gap-2 md:col-span-4"><button class="rounded bg-blue-600 px-4 py-2 text-white">Lưu thay đổi</button><button type="button" onclick="document.getElementById('edit-row-{{ $item->id }}').classList.add('hidden')" class="rounded border px-4 py-2">Hủy</button></div></form></td></tr>
        @empty
            <tr><td colspan="13" class="p-4 text-center text-slate-500">Chưa có vật tư.</td></tr>
        @endforelse
    </tbody></table></div>
</div>
<script>
document.addEventListener('DOMContentLoaded',function(){
    const categoryData=@json((($allCategories ?? $categories) ?? collect())->map(fn($category)=>['id'=>(string)$category->id,'parent'=>(string)$category->parent_id,'text'=>$category->code.' — '.$category->name])->values());
    const bind=function(industry){
        const form=industry.closest('form'),category=form?.querySelector('select[name="category_id"]');
        if(!category||category.dataset.industryCascade==='1')return;
        category.dataset.industryCascade='1';
        const emptyText=category.options[0]?.textContent||'Chọn loại vật tư';
        const optionHtml=function(items){return '<option value="">'+emptyText+'</option>'+items.map(item=>'<option value="'+item.id+'" data-industry="'+item.parent+'">'+item.text+'</option>').join('');};
        const sync=function(reset){
            const current=reset?'':(category.tomselect?category.tomselect.getValue():category.value);
            const items=categoryData.filter(item=>!industry.value||item.parent===String(industry.value));
            if(category.tomselect){
                category.tomselect.clear(true);
                category.tomselect.clearOptions();
                category.tomselect.addOptions(items.map(item=>({value:item.id,text:item.text})));
                category.tomselect.refreshOptions(false);
                if(current&&items.some(item=>item.id===String(current)))category.tomselect.setValue(current,true);
            }else{
                category.innerHTML=optionHtml(items);
                if(current&&items.some(item=>item.id===String(current)))category.value=current;
            }
        };
        industry.addEventListener('change',function(){sync(true)});
        if(industry.tomselect)industry.tomselect.on('change',function(){sync(true)});
        sync(false);
    };
    document.querySelectorAll('select[name="industry_id"]').forEach(bind);
    document.addEventListener('turbo:load',function(){document.querySelectorAll('select[name="industry_id"]').forEach(bind);});
});
</script>
