<div class="space-y-4">
    <div class="flex justify-end">
        <button type="button" id="open-export-panel" class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-bold text-white">Xuất báo cáo</button>
    </div>
    <section class="overflow-x-auto rounded-2xl border bg-white p-5">
        <h2 class="mb-3 text-lg font-bold">Bảng dữ liệu báo cáo biến động</h2>
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="bg-slate-50">
                <tr><th class="p-3">Thời gian</th><th class="p-3">Vật tư</th><th class="p-3">Loại biến động</th><th class="p-3">Số lượng</th><th class="p-3">Tham chiếu</th><th class="p-3">Người thực hiện</th></tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
                <tr class="border-t"><td class="p-3">{{ $row->created_at?->format('d/m/Y H:i') }}</td><td class="p-3">{{ $row->material?->code }} — {{ $row->material?->name }}</td><td class="p-3">{{ ['IN'=>'Tăng','OUT'=>'Giảm','ADJUST'=>'Điều chỉnh'][$row->type]??$row->type }}</td><td class="p-3">{{ $row->quantity }}</td><td class="p-3">{{ $row->reference?:'—' }}</td><td class="p-3">{{ $row->user?->name }}</td></tr>
            @empty
                <tr><td colspan="6" class="p-6 text-center text-slate-500">Chưa có dữ liệu biến động.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="mt-3">{{ $rows->links() }}</div>
    </section>
    <div id="export-panel" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
        <div class="w-full max-w-md rounded-2xl border bg-white p-5 shadow-xl">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-bold">Xuất báo cáo</h2>
                <button type="button" id="close-export-panel" class="text-xl">×</button>
            </div>
            <form method="GET" action="{{ route('inventory.reports.word.templates') }}" class="mt-4">
                <label class="block text-sm font-semibold">Định dạng
                    <select name="format" data-native-select data-tom-select="off" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                        <option value="docx">Word (.docx)</option>
                        <option value="xlsx">Excel (.xlsx)</option>
                    </select>
                </label>
                <label id="paper-size-filter-modal" class="mt-3 block text-sm font-semibold">Khổ giấy
                    <select name="paper_size" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                        <option value="auto">Tự động / theo mẫu</option>
                        <option value="A4">A4</option>
                        <option value="A3">A3</option>
                        <option value="A2">A2</option>
                    </select>
                </label>
                <label class="mt-3 block text-sm font-semibold">Loại báo cáo
                    <select name="report_type" data-native-select data-tom-select="off" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                        <option value="summary">Thống kê thực lực hiện có</option>
                        <option value="unit">Thống kê thực lực vật tư theo đơn vị</option>
                        <option value="movement">Thống kê tăng, giảm thực lực vật tư</option>
                        <option value="warehouse">Báo cáo kho</option>
                        <option value="system-warehouse">Báo cáo kho vật tư</option>
                        <option value="public-assets">Danh sách tài sản công</option>
                        <option value="xlsx-public-depreciation-detail" data-format="xlsx">Mẫu 01 - Chi tiết khấu hao TSCĐ</option>
                        <option value="xlsx-public-assets-current" data-format="xlsx">Mẫu 02 - Thực lực trang bị TSCĐ</option>
                        <option value="xlsx-public-assets-change" data-format="xlsx">Mẫu 03 - Tăng giảm tài sản công</option>
                        <option value="xlsx-public-assets-by-unit" data-format="xlsx">Mẫu 04 - Tài sản tại các đơn vị</option>
                        <option value="xlsx-public-assets-change-detail" data-format="xlsx">Mẫu 05 - Chi tiết tăng giảm TSCĐ</option>
                        <option value="transfer">Báo cáo quyết định điều động</option>
                        <option value="recall">Báo cáo quyết định thu hồi</option>
                        <option value="repair">Báo cáo vật tư hư hại và sửa chữa</option>
                        <option value="update-log">Cập nhật vật tư</option>
                    </select>
                </label>
                <label id="report-template-filter-modal" class="mt-3 block text-sm font-semibold">Mẫu báo cáo
                    <select name="template_id" data-native-select data-tom-select="off" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                        <option value="">Dùng mẫu mặc định / bảng dữ liệu tự động</option>
                    </select>
                </label>
                <div id="depreciation-year-filter-modal" class="mt-3 hidden rounded-lg border p-3">
                    <p class="mb-2 text-sm font-semibold">Khoảng năm khấu hao</p>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="text-sm font-semibold">Từ năm
                            <input type="number" name="depreciation_from_year" min="1900" max="2200" placeholder="2026" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                        </label>
                        <label class="text-sm font-semibold">Đến năm
                            <input type="number" name="depreciation_to_year" min="1900" max="2200" placeholder="2029" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                        </label>
                    </div>
                </div>
                <label id="unit-filter-modal" class="mt-3 hidden block text-sm font-semibold">Đơn vị quản lý
                    <select name="unit_id" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                        <option value="">Chọn đơn vị</option>
                        @foreach($units ?? [] as $unit)
                            <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="mt-4 rounded-lg border p-3 text-sm">
                    <p class="mb-2 font-semibold">Phạm vi</p>
                    <label class="block"><input type="radio" name="scope" value="position" checked> Theo vị trí lắp đặt</label>
                    <label class="mt-2 block"><input type="radio" name="scope" value="all"> Tổng hợp toàn bộ phòng/tòa</label>
                </div>
                <div class="mt-5 flex justify-end gap-2">
                    <button type="button" id="cancel-export-panel" class="rounded-lg border px-4 py-2">Hủy</button>
                    <button id="export-modal-submit" class="rounded-lg bg-slate-900 px-4 py-2 font-bold text-white">Xuất Word</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const exportPanel = document.getElementById('export-panel');
    const reportType = document.querySelector('#export-panel select[name="report_type"]');
    const exportFormat = document.querySelector('#export-panel select[name="format"]');
    const unitFilter = document.getElementById('unit-filter-modal');
    const paperSizeFilter = document.getElementById('paper-size-filter-modal');
    const depreciationYearFilter = document.getElementById('depreciation-year-filter-modal');
    const exportSubmit = document.getElementById('export-modal-submit');
    const templateSelect = document.querySelector('#export-panel select[name="template_id"]');
    const templateUrl = @json(route('inventory.templates.options'));

    document.getElementById('open-export-panel')?.addEventListener('click', () => exportPanel?.classList.replace('hidden', 'flex'));
    const closeExport = () => exportPanel?.classList.replace('flex', 'hidden');
    document.getElementById('close-export-panel')?.addEventListener('click', closeExport);
    document.getElementById('cancel-export-panel')?.addEventListener('click', closeExport);

    const resolveReportType = () => {
        if (reportType?.value === 'summary') return 'position';
        if (reportType?.value === 'movement') return 'increase-decrease';
        return reportType?.value || 'position';
    };
    const refreshTemplates = async () => {
        if (!templateSelect || !exportFormat || !reportType) return;
        templateSelect.innerHTML = '<option value="">Đang tải mẫu...</option>';
        const params = new URLSearchParams({ format: exportFormat.value, report_type: resolveReportType() });
        try {
            const response = await fetch(`${templateUrl}?${params.toString()}`, { headers: { 'Accept': 'application/json' } });
            const data = await response.json();
            templateSelect.innerHTML = '<option value="">Dùng mẫu mặc định / bảng dữ liệu tự động</option>';
            (data.templates || []).forEach(template => {
                const option = document.createElement('option');
                option.value = template.id;
                option.textContent = `${template.name} (${template.code})`;
                templateSelect.appendChild(option);
            });
            if (exportFormat.value === 'xlsx' && (data.templates || []).length > 0) {
                templateSelect.value = data.templates[0].id;
            }
        } catch (error) {
            templateSelect.innerHTML = '<option value="">Không tải được danh sách mẫu</option>';
        }
    };
    const updateExport = () => {
        const isExcel = exportFormat?.value === 'xlsx';
        reportType?.querySelectorAll('option').forEach(option => {
            const optionFormat = option.dataset.format || 'docx';
            const visible = optionFormat === (isExcel ? 'xlsx' : 'docx');
            option.hidden = !visible;
            option.disabled = !visible;
        });
        if (reportType?.selectedOptions[0]?.disabled) {
            const firstVisible = [...reportType.options].find(option => !option.disabled);
            if (firstVisible) reportType.value = firstVisible.value;
        }
        unitFilter?.classList.toggle('hidden', reportType?.value !== 'unit');
        paperSizeFilter?.classList.toggle('hidden', isExcel);
        depreciationYearFilter?.classList.toggle('hidden', !(isExcel && reportType?.value === 'xlsx-public-depreciation-detail'));
        if (exportSubmit) exportSubmit.textContent = isExcel ? 'Xuất Excel' : 'Xuất Word';
        refreshTemplates();
    };
    reportType?.addEventListener('change', updateExport);
    exportFormat?.addEventListener('change', updateExport);
    updateExport();
});
</script>
