<div class="space-y-4">
    <style>
        form label:has(input[name="scope"]) {
            display: block;
            margin-top: .65rem;
            line-height: 1.5;
        }

        form input[name="scope"] {
            margin-right: .5rem;
        }
    </style>

    <div class="flex flex-wrap justify-end gap-2">
        <a href="{{ route('inventory.reports') }}" class="rounded-lg border px-3 py-2 text-sm">Báo cáo vật tư</a>
        <form method="POST" action="{{ route('inventory.movement-report.sync') }}">
            @csrf
            <button type="submit" class="rounded-lg border px-3 py-2 text-sm">↻ Đồng bộ tòa nhà/phòng</button>
        </form>
    </div>
    <p class="text-right text-xs text-slate-500">Dữ liệu danh mục được đồng bộ thủ công lần cuối: {{ $locationsSyncedAt ?? 'chưa đồng bộ' }}</p>

    <section class="rounded-2xl border bg-white p-5">
        <h2 class="text-lg font-bold">Xuất báo cáo</h2>
        <p class="mt-1 text-sm text-slate-500">Chọn kiểu xuất Word và phạm vi dữ liệu muốn tạo.</p>

        <form method="GET" action="{{ route('inventory.reports.word.templates') }}" class="mt-5 grid gap-4 md:grid-cols-2">
            <label class="text-sm font-semibold">Định dạng
                <select class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                    <option>Word (.docx) — mẫu báo cáo quản lý vật tư</option>
                </select>
            </label>

            <label class="text-sm font-semibold">Loại báo cáo
                <select name="report_type" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                    <option value="summary">Thống kê thực lực hiện có</option>
                    <option value="unit">Thống kê thực lực vật tư theo đơn vị</option>
                    <option value="movement">Thống kê tăng, giảm thực lực vật tư</option>
                    <option value="period">Báo cáo tổng hợp theo kỳ</option>
                    <option value="using">Báo cáo vật tư đang sử dụng</option>
                    <option value="warehouse">Báo cáo kho vật tư</option>
                    <option value="system-warehouse">Báo cáo hệ thống kho-vật tư</option>
                    <option value="transfer">Quyết định điều động</option>
                    <option value="recall">Quyết định thu hồi</option>
                    <option value="repair">Vật tư đang hư hại và sửa chữa</option>
                    <option value="update-log">Cập nhật vật tư</option>
                </select>
            </label>

            <label class="text-sm font-semibold md:col-span-2">Mẫu Word
                <select name="template_id" data-native-select class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                    <option value="" data-report-type="*" data-default-label="Mẫu mặc định của hệ thống">Mẫu mặc định của hệ thống</option>
                    @foreach(($reportTemplates ?? collect()) as $template)
                        <option value="{{ $template->id }}" data-report-type="{{ $template->report_type ?: '*' }}">Mẫu báo cáo đã cấu hình — {{ $template->name }} — {{ $template->code }}</option>
                    @endforeach
                    @foreach(($defaultTemplates ?? []) as $type => $template)
                        <option value="default:{{ $type }}" data-report-type="{{ $type }}">Mẫu hệ thống — {{ ($template['report'] ?? $template['name']).(isset($template['scope']) ? ' - '.$template['scope'] : '') }}</option>
                    @endforeach
                </select>
            </label>

            <label id="unit-filter" class="hidden text-sm font-semibold">Đơn vị quản lý
                <select name="unit_id" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                    <option value="">Chọn đơn vị</option>
                    @foreach($units ?? [] as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                    @endforeach
                </select>
            </label>

            <label id="material-filter" class="hidden text-sm font-semibold">Vật tư cần thống kê
                <select name="material_id" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                    <option value="">Tất cả vật tư</option>
                    @foreach($materials ?? [] as $material)
                        <option value="{{ $material->id }}">{{ $material->code }} — {{ $material->name }}</option>
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
                        <option value="{{ $item->id }}" data-building-id="{{ $item->building_id }}">{{ $item->name }}</option>
                    @endforeach
                </select>
            </label>

            <label class="text-sm font-semibold">Từ ngày
                <input type="date" name="from" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
            </label>

            <label class="text-sm font-semibold">Đến ngày
                <input type="date" name="to" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
            </label>

            <div id="scope-filter" class="rounded-lg border p-4 text-sm md:col-span-2">
                <p class="mb-2 font-semibold">Phạm vi xuất</p>
                <label><input type="radio" name="scope" value="position" checked> Theo vị trí lắp đặt</label>
                <label><input type="radio" name="scope" value="all"> Tổng hợp toàn bộ phòng/tòa</label>
            </div>

            <div class="flex justify-end md:col-span-2">
                <button class="rounded-lg bg-slate-900 px-5 py-2.5 font-bold text-white">Xuất Word</button>
            </div>
        </form>
    </section>

    <script>
        (() => {
            const building = document.querySelector('select[name="building_id"]');
            const room = document.querySelector('select[name="classroom_id"]');
            const type = document.querySelector('select[name="report_type"]');
            const template = document.querySelector('select[name="template_id"]');
            const unit = document.getElementById('unit-filter');
            const material = document.getElementById('material-filter');
            const scope = document.getElementById('scope-filter');
            const resolveReportType = () => {
                const value = type?.value || 'summary';
                const all = scope?.querySelector('input[value="all"]')?.checked;
                if (value === 'summary') return all ? 'total-position' : 'position';
                if (value === 'movement') return 'increase-decrease';
                if (value === 'using') return all ? 'using-total' : 'using-position';
                return value;
            };
            const syncSelectUi = (select) => {
                if (select?.tomselect) {
                    select.tomselect.sync();
                    select.tomselect.refreshOptions(false);
                    select.tomselect.refreshItems();
                }
            };
            const updateForm = () => {
                const isUnit = type?.value === 'unit';
                const isSummary = type?.value === 'summary';
                const isScoped = isSummary || type?.value === 'using';
                if (template?.tomselect && typeof window.destroyTomSelect === 'function') window.destroyTomSelect(template);
                unit?.classList.toggle('hidden', !isUnit);
                scope?.classList.toggle('hidden', !isScoped);
                material?.classList.toggle('hidden', !isSummary);
                if (!isUnit) unit?.querySelector('select') && (unit.querySelector('select').value = '');
                if (!isSummary) material?.querySelector('select') && (material.querySelector('select').value = '');
                if (template) {
                    const selectedType = resolveReportType();
                    [...template.options].forEach(option => {
                        const reportType = option.dataset.reportType || '*';
                        const visible = reportType === '*' || reportType === selectedType;
                        option.hidden = !visible;
                        option.disabled = !visible;
                    });
                    if (template.selectedOptions[0]?.disabled) template.value = '';
                    if (!template.value) {
                        const configured = [...template.options].find(option => !option.disabled && /^\d+$/.test(option.value));
                        if (configured) template.value = configured.value;
                    }
                    template.options[0].textContent = 'Mẫu mặc định của ' + (type?.selectedOptions[0]?.textContent || 'hệ thống');
                    syncSelectUi(template);
                }
                syncSelectUi(material?.querySelector('select'));
            };
            scope?.querySelectorAll('input[name="scope"]').forEach(input => input.addEventListener('change', updateForm));
            type?.addEventListener('change', updateForm);
            updateForm();
            if (!building || !room) return;
            const filterRooms = () => {
                const buildingId = building.value;
                [...room.options].forEach((option, index) => {
                    if (index === 0) return;
                    option.hidden = Boolean(buildingId && option.dataset.buildingId !== buildingId);
                    if (option.hidden && option.selected) room.value = '';
                });
            };
            building.addEventListener('change', filterRooms);
            filterRooms();
        })();
    </script>
</div>
