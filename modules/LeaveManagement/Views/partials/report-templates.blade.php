@php
    $typeLabels = [
        'used' => 'QN đã nghỉ phép',
        'unused' => 'QN chưa nghỉ phép',
        'tracking' => 'Theo dõi thời gian nghỉ phép',
        'registered' => 'QN đăng ký nghỉ phép năm',
    ];
    $agencyLabels = [
        'QUAN_LUC' => 'Diện Quân lực quản lý',
        'CO_QUAN_CAN_BO' => 'Diện Cán bộ quản lý',
    ];
    $reportItems = $items->filter(fn ($item) => ($item->template_kind ?: 'report') === 'report')->values();
    $permitItems = $items->filter(fn ($item) => $item->template_kind === 'permit')->values();
    $reportPlaceholders = [
        'Thông tin chung' => ['nam', 'ngay_bao_cao', 'ngay', 'thang', 'nam_hien_tai', 'tieu_de', 'loai_bao_cao', 'dien_quan_ly', 'co_quan_quan_ly', 'don_vi_quan_nhan', 'nguoi_bao_cao', 'thu_truong', 'tong_so', 'so_phep_nam', 'so_phep_dac_biet', 'so_da_nghi', 'so_chua_nghi'],
        'Dòng dữ liệu' => ['stt', 'ho_ten', 'cap_bac', 'nhap_ngu', 'don_vi', 'tu_ngay', 'den_ngay', 'noi_nghi_phep', 'ly_do', 'que_quan', 'tru_quan', 'ghi_chu', 'tong_ngay', 'da_nghi', 'con_lai'],
    ];
    $permitPlaceholders = [
        'Thông tin giấy phép' => ['so_giay_phep', 'ma_don', 'ngay', 'thang', 'nam', 'ngay_lap', 'ngay_lap_ngay', 'ngay_lap_thang', 'ngay_lap_nam'],
        'Quân nhân' => ['ho_ten', 'ho_ten_thuong', 'ma_quan_nhan', 'cap_bac', 'chuc_vu', 'don_vi'],
        'Nghỉ phép' => ['tu_ngay', 'den_ngay', 'tu_gio', 'den_gio', 'tu_gio_so', 'den_gio_so', 'thoi_gian_nghi', 'tong_ngay', 'noi_nghi_phep', 'ly_do', 'loai_phep', 'ghi_chu'],
        'Ký duyệt' => ['nguoi_thay_the', 'chuc_vu_thay_the', 'y_kien_xu_ly', 'so_ngay_van_ban_ky', 'ngay_ky', 'nguoi_ky', 'thu_truong'],
    ];
@endphp

<div class="mb-5 flex flex-wrap gap-2 border-b border-slate-200">
    <button type="button" data-template-tab-button="report" class="border-b-2 border-blue-600 px-4 py-2 text-sm font-bold text-blue-700">Mẫu báo cáo</button>
    <button type="button" data-template-tab-button="permit" class="border-b-2 border-transparent px-4 py-2 text-sm font-bold text-slate-600">Mẫu in giấy phép nghỉ</button>
</div>

<div data-template-tab="report">
    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
        <form method="POST" action="{{ route('leave-management.report-templates.store') }}" enctype="multipart/form-data" class="rounded border bg-white p-5">
            @csrf
            <input type="hidden" name="template_kind" value="report">
            <h2 class="text-lg font-extrabold text-slate-900">Thêm mẫu báo cáo Word</h2>
            <div class="mt-4 grid gap-3 md:grid-cols-2">
                <label class="text-sm font-semibold">Tên mẫu
                    <input name="name" required maxlength="255" value="{{ old('template_kind', 'report') === 'report' ? old('name') : '' }}" class="mt-1 block w-full rounded border p-2">
                </label>
                <label class="text-sm font-semibold">Loại báo cáo
                    <select name="report_type" required class="mt-1 block w-full rounded border p-2">
                        @foreach($typeLabels as $type => $label)
                            <option value="{{ $type }}" @selected(old('report_type') === $type)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm font-semibold">Diện quản lý
                    <select name="managing_agency" required class="mt-1 block w-full rounded border p-2">
                        @foreach($agencyLabels as $agency => $label)
                            <option value="{{ $agency }}" @selected(old('managing_agency') === $agency)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="text-sm font-semibold md:col-span-2">File mẫu .docx
                    <input name="file" type="file" accept=".docx" required class="mt-1 block w-full rounded border p-2">
                </label>
                <label class="text-sm font-semibold md:col-span-2">Ghi chú
                    <textarea name="description" rows="3" class="mt-1 block w-full rounded border p-2">{{ old('template_kind', 'report') === 'report' ? old('description') : '' }}</textarea>
                </label>
                <label class="flex items-center gap-2 text-sm font-semibold">
                    <input type="checkbox" name="active" value="1" checked>
                    Dùng làm mẫu đang hoạt động cho loại báo cáo này
                </label>
            </div>
            <button class="mt-4 rounded bg-blue-600 px-4 py-2 font-semibold text-white">Thêm mẫu</button>
        </form>

        <div class="rounded border bg-white p-5">
            <h2 class="text-lg font-extrabold text-slate-900">Biến dùng trong mẫu báo cáo</h2>
            <p class="mt-2 text-sm text-slate-600">Trong file Word đặt biến dạng <code>${ten_bien}</code>. Với bảng dữ liệu, đặt các biến dòng trong cùng một hàng bảng để hệ thống nhân dòng khi in.</p>
            @foreach($reportPlaceholders as $title => $placeholders)
                <div class="mt-4">
                    <h3 class="text-sm font-bold text-slate-800">{{ $title }}</h3>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach($placeholders as $placeholder)
                            <code class="rounded border bg-slate-50 px-2 py-1 text-xs">${{{ $placeholder }}}</code>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @include('leave-management::partials.report-template-table', ['templateItems' => $reportItems, 'typeLabels' => $typeLabels, 'agencyLabels' => $agencyLabels, 'templateKind' => 'report'])
</div>

<div data-template-tab="permit" class="hidden">
    <div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
        <form method="POST" action="{{ route('leave-management.report-templates.store') }}" enctype="multipart/form-data" class="rounded border bg-white p-5">
            @csrf
            <input type="hidden" name="template_kind" value="permit">
            <h2 class="text-lg font-extrabold text-slate-900">Thêm mẫu in giấy phép nghỉ</h2>
            <div class="mt-4 grid gap-3">
                <label class="text-sm font-semibold">Tên mẫu
                    <input name="name" required maxlength="255" value="{{ old('template_kind') === 'permit' ? old('name') : '' }}" class="mt-1 block w-full rounded border p-2">
                </label>
                <label class="text-sm font-semibold">File mẫu .docx
                    <input name="file" type="file" accept=".docx" required class="mt-1 block w-full rounded border p-2">
                </label>
                <label class="text-sm font-semibold">Ghi chú
                    <textarea name="description" rows="3" class="mt-1 block w-full rounded border p-2">{{ old('template_kind') === 'permit' ? old('description') : '' }}</textarea>
                </label>
                <label class="flex items-center gap-2 text-sm font-semibold">
                    <input type="checkbox" name="active" value="1" checked>
                    Dùng làm mẫu đang hoạt động khi in giấy phép nghỉ
                </label>
            </div>
            <button class="mt-4 rounded bg-blue-600 px-4 py-2 font-semibold text-white">Thêm mẫu</button>
        </form>

        <div class="rounded border bg-white p-5">
            <h2 class="text-lg font-extrabold text-slate-900">Biến dùng trong mẫu giấy phép</h2>
            <p class="mt-2 text-sm text-slate-600">Các nút in giấy phép ở bước Ban Giám hiệu ký và trong hồ sơ phép sẽ ưu tiên mẫu đang hoạt động tại tab này.</p>
            @foreach($permitPlaceholders as $title => $placeholders)
                <div class="mt-4">
                    <h3 class="text-sm font-bold text-slate-800">{{ $title }}</h3>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach($placeholders as $placeholder)
                            <code class="rounded border bg-slate-50 px-2 py-1 text-xs">${{{ $placeholder }}}</code>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    @include('leave-management::partials.report-template-table', ['templateItems' => $permitItems, 'typeLabels' => $typeLabels, 'agencyLabels' => $agencyLabels, 'templateKind' => 'permit'])
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const buttons = document.querySelectorAll('[data-template-tab-button]');
    const panels = document.querySelectorAll('[data-template-tab]');
    const activate = function (target) {
        panels.forEach(panel => panel.classList.toggle('hidden', panel.dataset.templateTab !== target));
        buttons.forEach(button => {
            const active = button.dataset.templateTabButton === target;
            button.classList.toggle('border-blue-600', active);
            button.classList.toggle('text-blue-700', active);
            button.classList.toggle('border-transparent', !active);
            button.classList.toggle('text-slate-600', !active);
        });
    };
    buttons.forEach(button => button.addEventListener('click', () => activate(button.dataset.templateTabButton)));
    activate(@json(old('template_kind') === 'permit' ? 'permit' : 'report'));
});
</script>
