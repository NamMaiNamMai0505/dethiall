<div id="inventory-report-video" class="space-y-4">
    <div class="flex flex-wrap justify-end gap-2"><a href="{{ route('inventory.portal') }}" class="rounded-lg border px-3 py-2 text-sm font-semibold">Danh mục tòa nhà</a><a href="{{ route('inventory.reports') }}" class="rounded-lg border px-3 py-2 text-sm font-semibold">↻ Làm mới</a></div>
    <form method="GET" action="{{ route('inventory.reports.word') }}" class="rounded-2xl border bg-white p-4">
        <h2 class="font-bold">Xuất báo cáo Word</h2>
        <div class="mt-4 grid gap-3 md:grid-cols-4">
            <label class="text-sm font-semibold">Loại báo cáo
                <select name="report_type" id="inventory-report-type" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                    <option value="summary">Thống kê thực lực hiện có</option>
                    <option value="unit">Thống kê thực lực vật tư theo đơn vị</option>
                    <option value="movement">Thống kê tăng, giảm thực lực vật tư</option>
                    <option value="period">Báo cáo tổng hợp theo kỳ</option>
                    <option value="warehouse">Báo cáo kho vật tư</option>
                    <option value="using">Báo cáo vật tư đang sử dụng</option>
                    <option value="system-warehouse">Báo cáo hệ thống kho-vật tư</option>
                    <option value="transfer">Quyết định điều động</option>
                    <option value="recall">Quyết định thu hồi</option>
                    <option value="repair">Vật tư đang hư hại và sửa chữa</option>
                    <option value="update-log">Cập nhật vật tư</option>
                </select>
            </label>
            <label class="text-sm font-semibold">Mẫu Word
                <select name="template_id" id="inventory-template-select" data-native-select class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                    @foreach(($reportTemplates ?? collect()) as $template)
                        <option value="{{ $template->id }}" data-report-type="{{ $template->report_type ?: '*' }}">Mẫu đã cấu hình — {{ $template->description ?: $template->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-semibold">Từ ngày
                <input type="date" name="from" value="{{ $from }}" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold">Đến ngày
                <input type="date" name="to" value="{{ $to }}" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold">Đơn vị quản lý
                <select name="unit_id" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                    <option value="">Tất cả đơn vị</option>
                    @foreach(\Modules\Unit\Models\Unit::active()->orderBy('name')->get() as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-semibold">Tòa nhà
                <select name="building_id" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                    <option value="">Tất cả tòa nhà</option>
                    @foreach($buildings as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-semibold">Phòng
                <select name="classroom_id" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                    <option value="">Tất cả phòng</option>
                    @foreach($classrooms as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </label>
            <div id="inventory-scope-filter" class="rounded-lg border p-3 text-sm md:col-span-2">
                <p class="mb-2 font-semibold">Phạm vi xuất</p>
                <label class="mr-4"><input type="radio" name="scope" value="position" checked class="mr-1"> Theo vị trí lắp đặt</label>
                <label><input type="radio" name="scope" value="all" class="mr-1"> Tổng hợp toàn bộ phòng/tòa</label>
            </div>
            <div class="flex items-end">
                <button class="w-full rounded-lg bg-slate-900 px-5 py-2.5 font-bold text-white">Xuất Word</button>
            </div>
        </div>
    </form>
    <form method="GET" action="{{ route('inventory.reports') }}" class="rounded-2xl border p-4"><h2 class="font-bold">Bộ lọc</h2><p class="mt-1 text-sm text-slate-500">Áp dụng cho thống kê, kho, hỏng/sửa, hết hạn. Khoảng ngày dùng cho lịch sử sửa chữa và báo cáo biến động.</p><div class="mt-4 grid gap-3 md:grid-cols-5"><label class="text-sm font-semibold">Tòa nhà<select name="building_id" class="mt-1 block w-full rounded-lg border px-3 py-2.5"><option value="">Tất cả</option>@foreach($buildings as $item)<option value="{{ $item->id }}" @selected(request('building_id')==$item->id)>{{ $item->name }}</option>@endforeach</select></label><label class="text-sm font-semibold">Tầng<select name="floor" class="mt-1 block w-full rounded-lg border px-3 py-2.5"><option value="">Tất cả</option>@for($f=1;$f<=20;$f++)<option value="{{ $f }}" @selected(request('floor')==$f)>Tầng {{ $f }}</option>@endfor</select></label><label class="text-sm font-semibold">Loại / nhóm vật tư<select name="category_id" class="mt-1 block w-full rounded-lg border px-3 py-2.5"><option value="">VD: IT, Điện lạnh</option>@foreach($categories as $item)<option value="{{ $item->id }}" @selected(request('category_id')==$item->id)>{{ $item->name }}</option>@endforeach</select></label><label class="text-sm font-semibold">Từ ngày<input type="date" name="from" value="{{ $from }}" class="mt-1 block w-full rounded-lg border px-3 py-2.5"></label><label class="text-sm font-semibold">Đến ngày<input type="date" name="to" value="{{ $to }}" class="mt-1 block w-full rounded-lg border px-3 py-2.5"></label></div></form>
    <div class="flex flex-wrap gap-1 rounded-lg border p-1">@foreach([['summary','▥ Thống kê',$stats['records']],['broken','⚠ Hỏng / Đang sửa',$stats['broken']],['warehouse','▣ Kho ổn định / hư hỏng',$assets->whereIn('status',['BROKEN','REPAIRING'])->count()],['expiry','◷ Sắp hết hạn',$expiringAssets->count()],['repairs','♧ Lịch sử sửa chữa',$repairs->count()],['logs','▤ Báo cáo biến động',$auditLogs->count()]] as [$key,$label,$count])<button type="button" class="report-tab rounded-md px-3 py-2 text-xs font-semibold {{ $loop->first?'bg-slate-800 text-white':'text-slate-500' }}" data-report-tab="{{ $key }}">{{ $label }} <span class="ml-1">{{ $count }}</span></button>@endforeach</div>
    <section data-report-panel="summary" class="report-panel rounded-2xl border p-4"><div class="grid gap-3 md:grid-cols-4">@foreach([['Tổng bản ghi vật tư',$stats['records']],['Tổng số lượng',number_format($stats['quantity'],0,',','.')],['Số nhóm',$stats['groups']],['Số tòa (có VT)',$stats['buildings']]] as [$label,$value])<div class="rounded-xl border p-5"><p class="text-sm">{{ $label }}</p><p class="mt-5 text-2xl font-bold">{{ $value }}</p></div>@endforeach</div><div class="mt-4 grid gap-4 lg:grid-cols-2"><div class="rounded-xl border p-4"><h3 class="font-bold">Theo trạng thái</h3><p class="mb-3 text-xs text-slate-500">Bấm dòng để xem thiết bị trong trạng thái</p>@forelse($assets->groupBy('status') as $status=>$items)<details class="border-b py-2"><summary class="flex cursor-pointer justify-between text-sm"><span>› {{ ['NORMAL'=>'Bình thường','BROKEN'=>'Hỏng','REPAIRING'=>'Đang sửa','LIQUIDATED'=>'Đã thanh lý'][$status]??$status }}</span><span>{{ $items->count() }} SP · SL {{ $items->sum('quantity') }}</span></summary><div class="mt-2 pl-4 text-xs">@foreach($items as $item)<p>{{ $item->asset_code }} — {{ $item->name }} — {{ $item->quantity }}</p>@endforeach</div></details>@empty<p class="mt-3 text-sm text-slate-500">Không có dữ liệu</p>@endforelse</div><div class="rounded-xl border p-4"><h3 class="font-bold">Theo loại</h3><p class="mb-3 text-xs text-slate-500">Bấm dòng để xem thiết bị thuộc loại</p>@forelse($materials->groupBy(fn($m)=>$m->category?->name?:'Chưa phân loại') as $name=>$items)<details class="border-b py-2"><summary class="flex cursor-pointer justify-between text-sm"><span>› {{ $name }}</span><span>{{ $items->count() }} SP · SL {{ $items->sum('quantity') }}</span></summary><div class="mt-2 pl-4 text-xs">@foreach($items as $item)<p>{{ $item->code }} — {{ $item->name }} — {{ $item->quantity }}</p>@endforeach</div></details>@empty<p class="mt-3 text-sm text-slate-500">Không có dữ liệu</p>@endforelse</div></div></section>
    <section data-report-panel="broken" class="report-panel hidden space-y-4 rounded-2xl border p-4"><div class="flex flex-wrap items-center justify-between gap-2"><h2 class="font-bold">Gồm cả đang sửa (REPAIRING)</h2><div class="flex gap-2"><button class="rounded border px-3 py-1 text-xs">⇩ CSV nhật ký hỏng/SỬC</button><button class="rounded border px-3 py-1 text-xs">⇩ CSV phiếu BH ({{ $repairs->count() }})</button></div></div><p class="text-xs text-slate-500">Nhật ký hỏng / cần sửa được lưu từ đề xuất và báo hỏng; hiển thị nguyên cấp, báo hỏng phòng và cấp 5 — sửa xong cấp 2.</p><div class="rounded-xl border p-4"><h3 class="font-bold">Nhật ký vật tư hỏng / cần sửa chữa</h3><table class="mt-3 w-full text-left text-xs"><thead><tr><th class="p-2">Thời gian</th><th class="p-2">Thiết bị</th><th class="p-2">Phân loại</th><th class="p-2">Trạng thái</th><th class="p-2">Lý do</th></tr></thead><tbody>@forelse($brokenLogs as $log)<tr class="border-t"><td class="p-2">{{ $log->event_at?->format('d/m/Y H:i') }}</td><td class="p-2">{{ $log->asset_name }}</td><td class="p-2">Cấp {{ $log->grade_after }}</td><td class="p-2">{{ $log->status_after }}</td><td class="p-2">{{ $log->reason }}</td></tr>@empty<tr><td colspan="5" class="p-4 text-center text-slate-500">Chưa có sự kiện hỏng/sửa.</td></tr>@endforelse</tbody></table></div><div class="rounded-xl border p-4"><h3 class="font-bold">Phiếu báo hỏng đang xử lý</h3><table class="mt-3 w-full text-left text-xs"><thead><tr><th class="p-2">Phiếu</th><th class="p-2">Thiết bị</th><th class="p-2">Nội dung</th><th class="p-2">Ngày hư</th><th class="p-2">Người sửa</th><th class="p-2">Trạng thái</th></tr></thead><tbody>@forelse($repairs->whereIn('status',['OPEN','ASSIGNED','REPAIRING']) as $repair)<tr class="border-t"><td class="p-2">#{{ $repair->id }}</td><td class="p-2">{{ $repair->asset?->name }}</td><td class="p-2">{{ $repair->content }}</td><td class="p-2">{{ $repair->opened_at?->format('d/m/Y') }}</td><td class="p-2">{{ $repair->performer ?: $repair->assignee?->name ?: '—' }}</td><td class="p-2">{{ $repair->status }}</td></tr>@empty<tr><td colspan="6" class="p-4 text-center text-slate-500">Chưa có phiếu đang xử lý.</td></tr>@endforelse</tbody></table></div><div class="rounded-xl border p-4"><h3 class="font-bold">Phiếu báo hỏng đã hoàn thành / hủy</h3><table class="mt-3 w-full text-left text-xs"><thead><tr><th class="p-2">Phiếu</th><th class="p-2">Thiết bị</th><th class="p-2">Nội dung</th><th class="p-2">Trạng thái</th><th class="p-2">Ngày hoàn thành</th></tr></thead><tbody>@forelse($repairs->whereIn('status',['COMPLETED','CANCELLED']) as $repair)<tr class="border-t"><td class="p-2">#{{ $repair->id }}</td><td class="p-2">{{ $repair->asset?->name }}</td><td class="p-2">{{ $repair->content }}</td><td class="p-2">{{ $repair->status==='COMPLETED'?'Đã hoàn thành':'Đã hủy' }}</td><td class="p-2">{{ $repair->completed_at?->format('d/m/Y') }}</td></tr>@empty<tr><td colspan="5" class="p-4 text-center text-slate-500">Chưa có phiếu hoàn thành/hủy.</td></tr>@endforelse</tbody></table></div></section>
    <section data-report-panel="warehouse" class="report-panel hidden space-y-4 rounded-2xl border p-4"><div class="grid gap-3 md:grid-cols-2"><div class="rounded-xl border p-4"><p class="text-xs">Tổng SL kho ổn định (cấp 1–4)</p><p class="mt-3 text-2xl font-bold text-emerald-500">{{ number_format($assets->whereIn('grade',[1,2,3,4])->sum('quantity'),0,',','.') }}</p></div><div class="rounded-xl border p-4"><p class="text-xs">Tổng SL kho hư hỏng (cấp 5)</p><p class="mt-3 text-2xl font-bold text-red-500">{{ number_format($assets->where('grade',5)->sum('quantity'),0,',','.') }}</p></div></div><div class="grid gap-4 lg:grid-cols-2"><div class="rounded-xl border p-4"><h3 class="font-bold">Vật tư ổn định (cấp 1–4)</h3><table class="mt-3 w-full text-left text-xs"><thead><tr><th class="p-2">Tên thiết bị</th><th class="p-2">Số lượng</th><th class="p-2">Năm SX</th><th class="p-2">Năm SD</th><th class="p-2">Phân cấp</th><th class="p-2">Địa chỉ</th></tr></thead><tbody>@foreach($assets->whereIn('grade',[1,2,3,4]) as $asset)<tr class="border-t"><td class="p-2">{{ $asset->name }}</td><td class="p-2">{{ $asset->quantity }}</td><td class="p-2">{{ $asset->manufacture_year?:'—' }}</td><td class="p-2">{{ $asset->usage_year?:'—' }}</td><td class="p-2">{{ $asset->grade }}</td><td class="p-2">{{ $asset->install_address?:'—' }}</td></tr>@endforeach</tbody></table></div><div class="rounded-xl border p-4"><h3 class="font-bold text-red-500">Vật tư hỏng / đang sửa chữa</h3><table class="mt-3 w-full text-left text-xs"><thead><tr><th class="p-2">Tên thiết bị</th><th class="p-2">Số lượng</th><th class="p-2">Phân cấp</th><th class="p-2">Lý do hỏng</th><th class="p-2">Ngày hư</th></tr></thead><tbody>@foreach($assets->whereIn('status',['BROKEN','REPAIRING']) as $asset)<tr class="border-t"><td class="p-2">{{ $asset->name }}</td><td class="p-2">{{ $asset->quantity }}</td><td class="p-2">{{ $asset->grade }}</td><td class="p-2">{{ $asset->note?:'—' }}</td><td class="p-2">{{ $asset->broken_at?->format('d/m/Y')?:'—' }}</td></tr>@endforeach</tbody></table></div></div></section>
    <section data-report-panel="expiry" class="report-panel hidden rounded-2xl border p-4"><form method="GET" class="mb-4"><label class="text-sm font-semibold">Trong vòng (ngày)<input type="number" name="days" min="1" max="3650" value="{{ request('days',30) }}" class="mt-1 block w-24 rounded-lg border px-3 py-2.5"></label><button class="mt-2 rounded border px-4 py-2 text-sm">Lọc</button></form><table class="w-full text-left text-sm"><thead><tr><th class="p-3">Thiết bị</th><th class="p-3">Loại</th><th class="p-3">Hết hạn</th><th class="p-3">Còn</th><th class="p-3">Phòng</th><th class="p-3">Tòa</th></tr></thead><tbody>@forelse($expiringAssets as $asset)<tr class="border-t"><td class="p-3">{{ $asset->name }}</td><td class="p-3">{{ $asset->category?->name }}</td><td class="p-3">{{ $asset->expiry_date?->format('d/m/Y') }}</td><td class="p-3">{{ now()->diffInDays($asset->expiry_date,false) }} ngày</td><td class="p-3">{{ $asset->classroom?->name }}</td><td class="p-3">{{ $asset->building?->name }}</td></tr>@empty<tr><td colspan="6" class="p-5 text-center text-slate-500">Không có vật tư sắp hết hạn.</td></tr>@endforelse</tbody></table></section>
    <section data-report-panel="repairs" class="report-panel hidden rounded-2xl border p-4"><form method="GET" class="mb-4 flex flex-wrap items-end gap-3"><label class="text-sm font-semibold">Từ ngày<input type="date" name="from" value="{{ $from }}" class="mt-1 block rounded-lg border px-3 py-2.5"></label><label class="text-sm font-semibold">Đến ngày<input type="date" name="to" value="{{ $to }}" class="mt-1 block rounded-lg border px-3 py-2.5"></label><button class="rounded border px-4 py-2.5 text-sm">Lọc lịch sử sửa chữa</button></form><table class="w-full text-left text-sm"><thead><tr><th class="p-3">Ngày</th><th class="p-3">Vật tư</th><th class="p-3">Nội dung</th><th class="p-3">Người sửa</th><th class="p-3">Phòng</th><th class="p-3">Tòa</th></tr></thead><tbody>@forelse($repairs as $repair)<tr class="border-t"><td class="p-3">{{ $repair->opened_at?->format('d/m/Y') }}</td><td class="p-3">{{ $repair->asset?->name }}</td><td class="p-3">{{ $repair->content }}</td><td class="p-3">{{ $repair->performer ?: $repair->assignee?->name ?: '—' }}</td><td class="p-3">{{ $repair->asset?->classroom?->name }}</td><td class="p-3">{{ $repair->asset?->classroom?->building?->name }}</td></tr>@empty<tr><td colspan="6" class="p-5 text-center text-slate-500">Chưa có lịch sử sửa chữa.</td></tr>@endforelse</tbody></table></section>
    <section data-report-panel="logs" class="report-panel hidden space-y-4 rounded-2xl border p-4"><div class="flex flex-wrap justify-between gap-2"><div><h2 class="font-bold">Bộ lọc báo cáo biến động</h2><p class="text-xs text-slate-500">Từ ngày — đến ngày, tìm kiếm vật tư, ngành, loại biến động, đơn vị quản lý, phòng.</p></div><div class="flex gap-2"><button class="rounded border px-3 py-1 text-xs">Xóa lọc</button><a href="{{ route('inventory.reports.csv') }}" class="rounded border px-3 py-1 text-xs">⇩ Xuất CSV</a><a href="{{ route('inventory.reports.word') }}" class="rounded border px-3 py-1 text-xs">▣ Xuất Excel</a></div></div><div class="grid gap-3 md:grid-cols-3"><label class="text-sm font-semibold">Từ ngày<input type="date" class="mt-1 block w-full rounded border px-3 py-2"></label><label class="text-sm font-semibold">Đến ngày<input type="date" class="mt-1 block w-full rounded border px-3 py-2"></label><label class="text-sm font-semibold">Tìm kiếm<input placeholder="Tên VT, mã, phòng, tòa, lý do..." class="mt-1 block w-full rounded border px-3 py-2"></label><label class="text-sm font-semibold">Ngành<select class="mt-1 block w-full rounded border px-3 py-2"><option>Tất cả ngành</option></select></label><label class="text-sm font-semibold">Loại biến động<select class="mt-1 block w-full rounded border px-3 py-2"><option>Tất cả (tăng / giảm / ĐC)</option></select></label><label class="text-sm font-semibold">Đơn vị quản lý / Phòng<select class="mt-1 block w-full rounded border px-3 py-2"><option>Tất cả</option></select></label></div><div class="overflow-x-auto rounded-xl border"><table class="w-full min-w-[900px] text-left text-xs"><thead><tr><th class="p-3">Ngày giờ</th><th class="p-3">Loại</th><th class="p-3">Vị trí</th><th class="p-3">Mã</th><th class="p-3">Tên</th><th class="p-3">SL</th><th class="p-3">Trước → Sau</th><th class="p-3">Cấp</th><th class="p-3">Lý do</th></tr></thead><tbody>@forelse($auditLogs as $log)<tr class="border-t"><td class="p-3">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td><td class="p-3">{{ ['INCREASE'=>'Tăng','DECREASE'=>'Giảm','ADJUST'=>'Điều chỉnh'][$log->action]??$log->action }}</td><td class="p-3">{{ $log->details['building_name']??'' }} / {{ $log->details['classroom_id']??'' }}</td><td class="p-3">{{ $log->details['asset_code']??$log->details['code']??'—' }}</td><td class="p-3">{{ $log->details['name']??'' }}</td><td class="p-3">{{ $log->details['quantity']??$log->details['change']??'—' }}</td><td class="p-3">{{ $log->details['before']??'—' }} → {{ $log->details['after']??'—' }}</td><td class="p-3">{{ $log->details['grade']??'—' }}</td><td class="p-3">{{ $log->details['reason']??$log->details['note']??'' }}</td></tr>@empty<tr><td colspan="9" class="p-5 text-center text-slate-500">Chưa có nhật ký cập nhật.</td></tr>@endforelse</tbody></table></div></section>
    <script>
    document.querySelectorAll('.report-tab').forEach(tab=>tab.addEventListener('click',()=>{document.querySelectorAll('.report-tab').forEach(x=>{x.classList.toggle('bg-slate-800',x===tab);x.classList.toggle('text-white',x===tab)});document.querySelectorAll('.report-panel').forEach(x=>x.classList.toggle('hidden',x.dataset.reportPanel!==tab.dataset.reportTab))}));
    (() => {
        const reportType = document.getElementById('inventory-report-type');
        const templateSelect = document.getElementById('inventory-template-select');
        const scopeFilter = document.getElementById('inventory-scope-filter');
        if (!reportType || !templateSelect) return;
        const resolveReportType = () => {
            const selected = reportType.value;
            const all = scopeFilter?.querySelector('input[value="all"]')?.checked;
            if (selected === 'summary') return all ? 'total-position' : 'position';
            if (selected === 'movement') return 'increase-decrease';
            if (selected === 'using') return all ? 'using-total' : 'using-position';
            return selected;
        };
        const syncSelectUi = (select) => {
            if (select?.tomselect) {
                select.tomselect.sync();
                select.tomselect.refreshOptions(false);
                select.tomselect.refreshItems();
            }
        };
        const filterTemplates = () => {
            const isSummary = reportType.value === 'summary';
            const isScoped = isSummary || reportType.value === 'using';
            if (templateSelect.tomselect && typeof window.destroyTomSelect === 'function') window.destroyTomSelect(templateSelect);
            scopeFilter?.classList.toggle('hidden', !isScoped);
            const selectedType = resolveReportType();
            [...templateSelect.options].forEach(option => {
                const type = option.dataset.reportType || '*';
                const visible = type === selectedType && /^\d+$/.test(option.value);
                option.hidden = !visible;
                option.disabled = !visible;
            });
            if (templateSelect.selectedOptions[0]?.disabled) templateSelect.value = '';
            if (!templateSelect.value) {
                const configured = [...templateSelect.options].find(option => !option.disabled && /^\d+$/.test(option.value));
                if (configured) templateSelect.value = configured.value;
            }
            syncSelectUi(templateSelect);
        };
        reportType.addEventListener('change', filterTemplates);
        scopeFilter?.querySelectorAll('input[name="scope"]').forEach(input => input.addEventListener('change', filterTemplates));
        filterTemplates();
    })();
    </script>
</div>
