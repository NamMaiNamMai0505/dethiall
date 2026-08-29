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
                    <option value="summary">Thống kê thực lực vật tư hiện có</option>
                    <option value="unit">Thống kê thực lực vật tư theo đơn vị</option>
                    <option value="movement">Thực lực tăng, giảm vật tư</option>
                    <option value="warehouse">Báo cáo kho vật tư</option>
                    <option value="transfer">Quyết định điều động</option>
                    <option value="recall">Quyết định thu hồi</option>
                    <option value="repair">Vật tư đang hư hại và sửa chữa</option>
                    <option value="update-log">Cập nhật vật tư</option>
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
            const unit = document.getElementById('unit-filter');
            const material = document.getElementById('material-filter');
            const scope = document.getElementById('scope-filter');
            const updateForm = () => {
                const isUnit = type?.value === 'unit';
                const isSummary = type?.value === 'summary';
                unit?.classList.toggle('hidden', !isUnit);
                scope?.classList.toggle('hidden', !isSummary);
                material?.classList.toggle('hidden', !(isSummary && scope?.querySelector('input[value="position"]')?.checked));
                const summary = type?.querySelector('option[value="summary"]');
                const movement = type?.querySelector('option[value="movement"]');
                const all = scope?.querySelector('input[value="all"]')?.checked;
                if (summary) summary.textContent = all ? 'Thống kê thực lực hiện có — tổng hợp phòng/tòa' : 'Thống kê thực lực hiện có — theo vị trí lắp đặt';
                if (movement) movement.textContent = all ? 'Thực lực tăng, giảm — theo tòa nhà/phòng' : 'Thực lực tăng, giảm — theo phòng';
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
