<div class="space-y-4">
    <form method="GET" class="flex flex-wrap items-end gap-3 rounded border bg-white p-4">
        <label class="text-sm font-semibold">Ngành vật tư
            <select name="industry_id" class="mt-1 w-full rounded border p-2 md:w-56">
                <option value="">Tất cả ngành</option>
                @foreach($industries as $industry)
                    <option value="{{ $industry->id }}" @selected(($industryId ?? '') == $industry->id)>{{ $industry->code }} — {{ $industry->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm font-semibold">Loại vật tư
            <select name="category_id" class="mt-1 w-full rounded border p-2 md:w-56">
                <option value="">Tất cả loại</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" data-industry="{{ $category->parent_id }}" @selected(($typeId ?? '') == $category->id)>{{ $category->code }} — {{ $category->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="w-full text-sm font-semibold md:w-auto">Tìm theo mã/tên
            <input name="search" value="{{ request('search') }}" placeholder="Mã hoặc tên vật tư" class="mt-1 w-full rounded border p-2 md:w-80">
        </label>
        <button class="rounded bg-blue-600 px-4 py-2 text-white">Tìm kiếm</button>
        <a href="{{ route('inventory.materials') }}" class="rounded border px-4 py-2">Xóa lọc</a>
    </form>

    <section class="rounded border bg-white p-4">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="font-semibold">Import vật tư</h2>
                <p class="mt-1 text-sm text-slate-500">File vật tư chỉ cần Mã loại vật tư, Tên vật tư và Đơn vị tính; mã vật tư tự sinh theo loại.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('inventory.import.template', ['type' => 'material']) }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-emerald-700"><i class="bi bi-file-earmark-excel"></i> Tải mẫu Excel (.xlsx)</a>
                <a href="{{ route('inventory.import.template.word', ['type' => 'material']) }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-blue-700"><i class="bi bi-file-earmark-word"></i> Tải mẫu DOCX (.docx)</a>
            </div>
        </div>
        <form method="POST" action="{{ route('inventory.import') }}" enctype="multipart/form-data" class="grid gap-3 md:grid-cols-4">
            @csrf
            <input type="hidden" name="import_type" value="material">
            <label class="text-sm font-semibold text-slate-700 md:col-span-3">Tệp import vật tư <b class="text-red-500">*</b>
                <input name="file" type="file" accept=".xlsx,.xls,.csv,.txt,.docx" required class="mt-1 w-full rounded border p-2">
            </label>
            <div class="flex items-end"><button class="w-full rounded bg-slate-700 px-4 py-2 text-white">Import vật tư</button></div>
        </form>
    </section>

    <form method="POST" action="{{ route('inventory.store') }}" class="grid gap-3 rounded border bg-white p-4 md:grid-cols-4">
        @csrf
        <h2 class="font-semibold md:col-span-4">Thêm vật tư</h2>
        <label class="text-sm font-semibold text-slate-700">Tên vật tư <b class="text-red-500">*</b><input name="name" required placeholder="Nhập tên vật tư" class="mt-1 w-full rounded border p-2"></label>
        <label class="text-sm font-semibold text-slate-700">Đơn vị tính <b class="text-red-500">*</b><input name="unit" required value="cái" placeholder="Ví dụ: cái, bộ, hộp" class="mt-1 w-full rounded border p-2"></label>
        <label class="text-sm font-semibold text-slate-700">Ngành vật tư <b class="text-red-500">*</b>
            <select name="industry_id" id="material-industry" required class="mt-1 w-full rounded border p-2">
                <option value="">Chọn ngành vật tư</option>
                @foreach($industries as $industry)
                    <option value="{{ $industry->id }}">{{ $industry->code }} — {{ $industry->name }}</option>
                @endforeach
            </select>
        </label>
        <label class="text-sm font-semibold text-slate-700 md:col-span-3">Loại vật tư <b class="text-red-500">*</b>
            <select name="category_id" id="material-category" required class="mt-1 w-full rounded border p-2">
                <option value="">Chọn loại vật tư</option>
                @foreach(($allCategories ?? $categories) as $category)
                    <option value="{{ $category->id }}" data-industry="{{ $category->parent_id }}">{{ $category->code }} — {{ $category->name }}</option>
                @endforeach
            </select>
        </label>
        <button class="rounded bg-blue-600 px-4 py-2 text-white md:self-end">Thêm vật tư</button>
    </form>

    <div class="overflow-x-auto rounded border bg-white p-4">
        <h2 class="mb-3 font-semibold">Bảng vật tư</h2>
        <table class="w-full min-w-[850px] text-left text-sm">
            <thead class="bg-slate-100">
                <tr><th class="p-3">STT</th><th class="p-3">Mã vật tư</th><th class="p-3">Tên vật tư</th><th class="p-3">Đơn vị tính</th><th class="p-3">Mã loại vật tư</th><th class="p-3">Loại vật tư</th><th class="p-3">Thao tác</th></tr>
            </thead>
            <tbody>
                @forelse(($materials ?? collect()) as $i=>$item)
                    @php($cannotDelete = (($item->warehouse_items_count ?? 0) + ($item->proposal_items_count ?? 0) + ($item->transfers_count ?? 0)) > 0)
                    <tr class="border-t align-top">
                        <td class="p-3">{{ $i+1 }}</td>
                        <td class="p-3 font-semibold">{{ $item->code }}</td>
                        <td class="p-3">{{ $item->name }}</td>
                        <td class="p-3">{{ $item->unit }}</td>
                        <td class="p-3">{{ $item->category?->code ?: '—' }}</td>
                        <td class="p-3">{{ $item->category?->name ?: '—' }}</td>
                        <td class="p-3">
                            <button type="button" class="rounded bg-blue-600 px-3 py-2 text-white" onclick="document.getElementById('edit-row-{{ $item->id }}').classList.toggle('hidden')">Sửa</button>
                            @unless($cannotDelete)
                                <form method="POST" action="{{ route('inventory.destroy',$item) }}" class="mt-2">@csrf @method('DELETE')<button class="rounded bg-rose-600 px-3 py-2 text-white" onclick="return confirm('Bạn có chắc muốn xóa vật tư này?')">Xóa</button></form>
                            @endunless
                        </td>
                    </tr>
                    <tr id="edit-row-{{ $item->id }}" class="hidden bg-blue-50">
                        <td colspan="7" class="p-4">
                            <form method="POST" action="{{ route('inventory.update',$item) }}" class="grid gap-3 rounded-lg border bg-white p-4 md:grid-cols-4">
                                @csrf @method('PATCH')
                                <div class="font-bold md:col-span-4">Chỉnh sửa vật tư: {{ $item->code }}</div>
                                <label class="text-sm font-semibold">Mã vật tư<input name="code" value="{{ $item->code }}" required readonly class="mt-1 w-full rounded border bg-slate-50 p-2"></label>
                                <label class="text-sm font-semibold">Tên vật tư<input name="name" value="{{ $item->name }}" required class="mt-1 w-full rounded border p-2"></label>
                                <label class="text-sm font-semibold">Đơn vị tính<input name="unit" value="{{ $item->unit }}" required class="mt-1 w-full rounded border p-2"></label>
                                <label class="text-sm font-semibold">Ngành vật tư<select name="industry_id" class="mt-1 w-full rounded border p-2"><option value="">Chọn ngành vật tư</option>@foreach($industries as $industry)<option value="{{ $industry->id }}" @selected($item->category?->parent_id == $industry->id)>{{ $industry->code }} — {{ $industry->name }}</option>@endforeach</select></label>
                                <label class="text-sm font-semibold md:col-span-3">Loại vật tư<select name="category_id" required class="mt-1 w-full rounded border p-2"><option value="">Chọn loại vật tư</option>@foreach(($allCategories ?? $categories) as $category)<option value="{{ $category->id }}" data-industry="{{ $category->parent_id }}" @selected($item->category_id == $category->id)>{{ $category->code }} — {{ $category->name }}</option>@endforeach</select></label>
                                <div class="flex gap-2 md:self-end"><button class="rounded bg-blue-600 px-4 py-2 text-white">Lưu thay đổi</button><button type="button" onclick="document.getElementById('edit-row-{{ $item->id }}').classList.add('hidden')" class="rounded border px-4 py-2">Hủy</button></div>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-4 text-center text-slate-500">Chưa có vật tư.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
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
