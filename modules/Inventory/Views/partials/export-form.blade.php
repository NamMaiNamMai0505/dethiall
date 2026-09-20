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
        <p class="mt-1 text-sm text-slate-500">Chọn định dạng xuất và phạm vi dữ liệu muốn tạo.</p>

        <form method="GET" action="{{ route('inventory.reports.word.templates') }}" class="mt-5 grid gap-4 md:grid-cols-2">
            <label class="text-sm font-semibold">Định dạng
                <select name="format" data-native-select data-tom-select="off" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                    <option value="docx">Word (.docx) — mẫu báo cáo quản lý vật tư</option>
                    <option value="xlsx">Excel (.xlsx) — bảng dữ liệu báo cáo</option>
                </select>
            </label>

            <label id="paper-size-filter" class="text-sm font-semibold">Khổ giấy
                <select name="paper_size" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                    <option value="auto">Tự động / theo mẫu</option>
                    <option value="A4">A4</option>
                    <option value="A3">A3</option>
                    <option value="A2">A2</option>
                </select>
            </label>

            <label class="text-sm font-semibold">Loại báo cáo
                <select name="report_type" data-native-select data-tom-select="off" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                    <option value="summary">Thống kê thực lực hiện có</option>
                    <option value="unit">Thống kê thực lực vật tư theo đơn vị</option>
                    <option value="movement">Thống kê tăng, giảm thực lực vật tư</option>
                    <option value="period">Báo cáo tổng hợp theo kỳ</option>
                    <option value="using">Báo cáo vật tư đang sử dụng</option>
                    <option value="warehouse">Báo cáo kho</option>
                    <option value="system-warehouse">Báo cáo kho vật tư</option>
                    <option value="public-assets">Danh sách tài sản công</option>
                    <option value="xlsx-public-depreciation-detail" data-format="xlsx">Mẫu 01 - Chi tiết khấu hao TSCĐ</option>
                    <option value="xlsx-public-assets-current" data-format="xlsx">Mẫu 02 - Thực lực trang bị TSCĐ</option>
                    <option value="xlsx-public-assets-change" data-format="xlsx">Mẫu 03 - Tăng giảm tài sản công</option>
                    <option value="xlsx-public-assets-by-unit" data-format="xlsx">Mẫu 04 - Tài sản tại các đơn vị</option>
                    <option value="xlsx-public-assets-change-detail" data-format="xlsx">Mẫu 05 - Chi tiết tăng giảm TSCĐ</option>
                    <option value="transfer">Quyết định điều động</option>
                    <option value="recall">Quyết định thu hồi</option>
                    <option value="repair">Vật tư đang hư hại và sửa chữa</option>
                    <option value="update-log">Cập nhật vật tư</option>
                </select>
            </label>

            <label id="report-template-filter" class="text-sm font-semibold md:col-span-2">Mẫu báo cáo
                <select name="template_id" data-native-select data-tom-select="off" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                    <option value="">Dùng mẫu mặc định / bảng dữ liệu tự động</option>
                </select>
            </label>

            <div id="depreciation-year-filter" class="hidden rounded-lg border p-4 md:col-span-2">
                <p class="mb-3 text-sm font-semibold">Khoảng năm khấu hao</p>
                <div class="grid gap-3 md:grid-cols-2">
                    <label class="text-sm font-semibold">Từ năm
                        <input type="number" name="depreciation_from_year" min="1900" max="2200" placeholder="VD: 2026" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                    </label>
                    <label class="text-sm font-semibold">Đến năm
                        <input type="number" name="depreciation_to_year" min="1900" max="2200" placeholder="VD: 2029" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                    </label>
                </div>
            </div>

            <label id="unit-filter" class="hidden text-sm font-semibold">Đơn vị quản lý
                <select name="unit_id" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                    <option value="">Chọn đơn vị</option>
                    @foreach($units ?? [] as $unit)
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
                <button id="export-submit-button" class="rounded-lg bg-slate-900 px-5 py-2.5 font-bold text-white">Xuất Word</button>
            </div>
        </form>
    </section>

    <script>
        (() => {
            const building = document.querySelector('select[name="building_id"]');
            const room = document.querySelector('select[name="classroom_id"]');
            const type = document.querySelector('select[name="report_type"]');
            const format = document.querySelector('select[name="format"]');
            const template = document.querySelector('select[name="template_id"]');
            const templateWrap = document.getElementById('report-template-filter');
            const paperSizeWrap = document.getElementById('paper-size-filter');
            const depreciationYearWrap = document.getElementById('depreciation-year-filter');
            const submitButton = document.getElementById('export-submit-button');
            const unit = document.getElementById('unit-filter');
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
                const isExcel = format?.value === 'xlsx';
                if (type) {
                    [...type.options].forEach(option => {
                        const optionFormat = option.dataset.format || 'docx';
                        const visible = optionFormat === (isExcel ? 'xlsx' : 'docx');
                        option.hidden = !visible;
                        option.disabled = !visible;
                    });
                    if (type.selectedOptions[0]?.disabled) {
                        const firstVisible = [...type.options].find(option => !option.disabled);
                        if (firstVisible) type.value = firstVisible.value;
                    }
                    syncSelectUi(type);
                }
                const isUnit = type?.value === 'unit';
                const isSummary = type?.value === 'summary';
                const isScoped = isSummary || type?.value === 'using';
                const isDepreciationDetail = isExcel && type?.value === 'xlsx-public-depreciation-detail';
                if (template?.tomselect && typeof window.destroyTomSelect === 'function') window.destroyTomSelect(template);
                unit?.classList.toggle('hidden', !isUnit);
                scope?.classList.toggle('hidden', !isScoped);
                depreciationYearWrap?.classList.toggle('hidden', !isDepreciationDetail);
                if (!isUnit) unit?.querySelector('select') && (unit.querySelector('select').value = '');
                paperSizeWrap?.classList.toggle('hidden', isExcel);
                if (submitButton) submitButton.textContent = isExcel ? 'Xuất Excel' : 'Xuất Word';
                refreshTemplates();
            };
            const refreshTemplates = () => {
                if (!template) return;
                const selectedType = resolveReportType();
                const selectedFormat = format?.value || 'docx';
                const url = new URL('{{ route('inventory.templates.options') }}', window.location.origin);
                url.searchParams.set('format', selectedFormat);
                url.searchParams.set('report_type', selectedType);
                fetch(url, { headers: { Accept: 'application/json' } })
                    .then(response => response.ok ? response.json() : { templates: [] })
                    .then(data => {
                        template.innerHTML = '<option value="">'+(selectedFormat === 'xlsx' ? 'Dùng bảng Excel tự động' : 'Dùng mẫu mặc định')+'</option>';
                        (data.templates || []).forEach(item => {
                            const option = document.createElement('option');
                            option.value = item.id;
                            option.textContent = item.name + (item.file_name ? ' — ' + item.file_name : '');
                            template.append(option);
                        });
                        if (selectedFormat === 'xlsx' && (data.templates || []).length > 0) {
                            template.value = data.templates[0].id;
                        }
                        templateWrap?.classList.toggle('hidden', false);
                    })
                    .catch(() => {
                        template.innerHTML = '<option value="">Dùng mẫu mặc định / bảng dữ liệu tự động</option>';
                    });
            };
            scope?.querySelectorAll('input[name="scope"]').forEach(input => input.addEventListener('change', updateForm));
            type?.addEventListener('change', updateForm);
            format?.addEventListener('change', updateForm);
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
