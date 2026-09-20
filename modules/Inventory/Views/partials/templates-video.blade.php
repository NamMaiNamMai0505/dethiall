@php
    $templateTypeOptions = collect($defaultTemplates ?? [])->map(function ($template, $type) {
        $report = $template['report'] ?? $template['name'];
        $scope = $template['scope'] ?? null;

        return [
            'type' => $type,
            'report' => $report,
            'label' => $scope ? $report.' - '.$scope : $report,
        ];
    });
    $excelTypeOptions = collect($excelReportTemplates ?? [])->map(function ($template, $type) {
        return [
            'type' => $type,
            'report' => $template['report'] ?? $template['name'],
            'label' => $template['name'],
        ];
    });
@endphp
<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold">Mẫu báo cáo</h2>
            <p class="mt-1 text-sm text-slate-500">Quản lý riêng mẫu Word và mẫu Excel dùng khi xuất báo cáo vật tư.</p>
        </div>
        <a href="{{ route('inventory.reports') }}" class="rounded-lg border px-4 py-2 text-sm font-semibold">Báo cáo vật tư</a>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
    <form method="POST" action="{{ route('inventory.templates.store') }}" enctype="multipart/form-data" class="rounded-2xl border bg-white p-5">
        @csrf
        <h3 class="mb-4 text-lg font-bold">Thêm mẫu Word</h3>
        <div class="grid gap-4 md:grid-cols-2">
            <label class="text-sm font-semibold">Mã mẫu
                <input name="code" required placeholder="VD: BAO_CAO_KHO_01" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold">Tên mẫu
                <input name="name" required placeholder="VD: Mẫu báo cáo kho" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold">Loại báo cáo
                <select name="report_type" required class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                    @foreach($templateTypeOptions as $option)
                        <option value="{{ $option['type'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-semibold">File Word mẫu
                <input name="file" type="file" accept=".docx" required class="mt-1 block w-full rounded-lg border px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold md:col-span-2">Ghi chú
                <textarea name="description" rows="3" placeholder="Ghi loại báo cáo phù hợp hoặc ghi chú cấu trúc mẫu" class="mt-1 block w-full rounded-lg border px-3 py-2.5"></textarea>
            </label>
            <label class="flex items-center gap-2 text-sm font-semibold md:col-span-2">
                <input type="checkbox" name="active" value="1" checked class="rounded border">
                Cho phép chọn mẫu này khi xuất báo cáo
            </label>
        </div>
        <button class="mt-4 rounded-lg bg-slate-900 px-5 py-2.5 font-bold text-white">Lưu mẫu Word</button>
    </form>

    <form method="POST" action="{{ route('inventory.templates.store') }}" enctype="multipart/form-data" class="rounded-2xl border bg-white p-5">
        @csrf
        <h3 class="mb-4 text-lg font-bold">Thêm mẫu Excel</h3>
        <div class="grid gap-4 md:grid-cols-2">
            <label class="text-sm font-semibold">Mã mẫu
                <input name="code" required placeholder="VD: MAU_01_KHAU_HAO" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold">Tên mẫu
                <input name="name" required placeholder="VD: Mẫu 01 khấu hao" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold">Loại báo cáo Excel
                <select name="report_type" required class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                    @foreach($excelTypeOptions as $option)
                        <option value="{{ $option['type'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-semibold">File Excel mẫu
                <input name="file" type="file" accept=".xlsx" required class="mt-1 block w-full rounded-lg border px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold md:col-span-2">Ghi chú
                <textarea name="description" rows="3" placeholder="Ghi chú cho mẫu Excel" class="mt-1 block w-full rounded-lg border px-3 py-2.5"></textarea>
            </label>
            <label class="flex items-center gap-2 text-sm font-semibold md:col-span-2">
                <input type="checkbox" name="active" value="1" checked class="rounded border">
                Cho phép chọn mẫu này khi xuất Excel
            </label>
        </div>
        <button class="mt-4 rounded-lg bg-slate-900 px-5 py-2.5 font-bold text-white">Lưu mẫu Excel</button>
    </form>
    </div>

    @php
        $commonVariables = ['ngay_bao_cao','ngay','thang','nam','tu_ngay','den_ngay','tieu_de','loai_bao_cao','ten_mau','ma_mau','tong_so','tong_so_luong','tong_vat_tu','tong_so_luong_vat_tu'];
        $warehouseVariables = ['tong_so_luong_on_dinh','so_luong_vat_tu_on_dinh','so_dong_on_dinh','tong_so_luong_hu_hai','so_luong_vat_tu_hu_hai','so_dong_hu_hai','tong_so_luong_hu_hong','so_luong_vat_tu_hu_hong','so_dong_hu_hong'];
        $systemWarehouseVariables = ['tong_so_luong_kho_vat_tu','so_dong_kho_vat_tu','tong_so_kho','kho','ton_toi_thieu'];
        $publicAssetVariables = ['nam_thong_ke','tong_tai_san','tong_thanh_tien','tong_tien_khau_hao','tong_gia_tri_con_lai','ma_tai_san','ten_tai_san','loai_tai_san','don_gia','thanh_tien','dia_chi_lap_dat','don_vi_cung_cap','hop_dong_hoa_don','nam_khau_hao','ty_le_khau_hao','tien_khau_hao','gia_tri_con_lai'];
        $proposalVariables = ['ten_phieu','tieu_de_phieu','noi_dung_de_xuat','ly_do_de_xuat','vat_tu_de_xuat','so_phieu','ma_phieu','ngay_in','loai_de_xuat','trang_thai','don_vi_de_xuat','nguoi_de_xuat','nguoi_de_nghi','nganh_vat_tu','mo_ta','ly_do_tu_choi','nguoi_duyet','ho_ten_nguoi_duyet','chi_huy_xac_nhan','nguoi_xac_nhan_chi_huy','ngay_duyet','chu_ky_nguoi_duyet','chu_ky_nguoi_de_nghi','chu_ky_nguoi_de_xuat','chu_ky_chi_huy_xac_nhan'];
        $rowVariables = ['stt','ngay_du_lieu','ma_vat_tu','ten_vat_tu','nganh','loai_vat_tu','don_vi_tinh','so_luong','so_luong_thuc_te_phong','so_luong_de_xuat','phan_cap','trang_thai','toa_nha','phong','don_vi_quan_ly','vi_tri','loai_bien_dong','truoc','sau','nguoi_thuc_hien','ngay_hu','ngay_hong','ly_do','ly_do_hong','ghi_chu'];
    @endphp

    <div class="rounded-2xl border bg-white p-5">
        <h3 class="text-lg font-bold">Biến dùng trong mẫu báo cáo</h3>
        <p class="mt-1 text-sm text-slate-500">Trong file Word đặt biến dạng ${ten_bien}. Với bảng dữ liệu, đặt các biến dòng trong cùng một hàng bảng để hệ thống nhân dòng khi in.</p>
        <div class="mt-5 space-y-4 text-sm">
            <div>
                <p class="mb-2 font-semibold">Thông tin chung</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($commonVariables as $variable)
                        <span class="rounded border border-slate-900 px-2 py-1 font-mono text-xs">{{ '${'.$variable.'}' }}</span>
                    @endforeach
                </div>
            </div>
            <div>
                <p class="mb-2 font-semibold">Dòng dữ liệu</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($rowVariables as $variable)
                        <span class="rounded border border-slate-900 px-2 py-1 font-mono text-xs">{{ '${'.$variable.'}' }}</span>
                    @endforeach
                    <span class="rounded border border-slate-900 px-2 py-1 font-mono text-xs">{{ '${bang_du_lieu}' }}</span>
                </div>
            </div>
            <div>
                <p class="mb-2 font-semibold">Riêng mẫu báo cáo kho</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($warehouseVariables as $variable)
                        <span class="rounded border border-slate-900 px-2 py-1 font-mono text-xs">{{ '${'.$variable.'}' }}</span>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-slate-500">${tong_so_luong_on_dinh} là tổng số lượng vật tư ổn định trong kho; ${tong_so_luong_hu_hai} là tổng số lượng vật tư hư hại hoặc đang sửa trong kho. ${so_dong_on_dinh} và ${so_dong_hu_hai} là số dòng thật đang in trong từng bảng.</p>
            </div>
            <div>
                <p class="mb-2 font-semibold">Riêng mẫu báo cáo kho vật tư</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($systemWarehouseVariables as $variable)
                        <span class="rounded border border-slate-900 px-2 py-1 font-mono text-xs">{{ '${'.$variable.'}' }}</span>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-slate-500">Mẫu này lấy dữ liệu từ mục Kho vật tư trong quản trị phân hệ; ${kho} là tên kho, ${ton_toi_thieu} là mức tồn tối thiểu của dòng vật tư trong kho.</p>
            </div>
            <div>
                <p class="mb-2 font-semibold">Riêng mẫu danh sách tài sản công</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($publicAssetVariables as $variable)
                        <span class="rounded border border-slate-900 px-2 py-1 font-mono text-xs">{{ '${'.$variable.'}' }}</span>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-slate-500">${thanh_tien} là đơn giá nhân số lượng; ${tien_khau_hao} và ${gia_tri_con_lai} lấy theo năm thống kê đang xuất báo cáo.</p>
            </div>
            <div>
                <p class="mb-2 font-semibold">Riêng mẫu giấy đề xuất</p>
                <div class="flex flex-wrap gap-2">
                    @foreach($proposalVariables as $variable)
                        <span class="rounded border border-slate-900 px-2 py-1 font-mono text-xs">{{ '${'.$variable.'}' }}</span>
                    @endforeach
                </div>
                <p class="mt-2 text-xs text-slate-500">Mẫu sửa chữa chèn ${chu_ky_nguoi_de_nghi} từ chữ ký số tài khoản tạo đề nghị; bản in sau duyệt chèn thêm ${chu_ky_chi_huy_xac_nhan} từ chữ ký người duyệt/xác nhận.</p>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border bg-white">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b bg-slate-50 px-5 py-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Danh sách mẫu báo cáo</h3>
                <p class="mt-1 text-sm text-slate-500">Mỗi dòng là một mẫu theo loại báo cáo; sửa dòng nào thì khi xuất loại báo cáo đó sẽ dùng file vừa import.</p>
            </div>
            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">{{ count($defaultTemplates ?? []) + ($uploadTemplates ?? collect())->count() + ($excelTemplates ?? collect())->count() }} mẫu</span>
        </div>
        <div class="grid gap-3 border-b bg-white px-5 py-4 md:grid-cols-5">
            <label class="text-xs font-bold uppercase text-slate-500">Tìm mẫu
                <input id="template-filter-search" placeholder="Tên, mã, file..." class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm normal-case">
            </label>
            <label class="text-xs font-bold uppercase text-slate-500">Định dạng
                <select id="template-filter-format" class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm normal-case">
                    <option value="">Tất cả</option>
                    <option value="docx">Word</option>
                    <option value="xlsx">Excel</option>
                </select>
            </label>
            <label class="text-xs font-bold uppercase text-slate-500">Nhóm mẫu
                <select id="template-filter-group" class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm normal-case">
                    <option value="">Tất cả</option>
                    <option value="default">Mẫu báo cáo</option>
                    <option value="upload">Mẫu tải lên</option>
                </select>
            </label>
            <label class="text-xs font-bold uppercase text-slate-500">Loại báo cáo
                <select id="template-filter-type" class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm normal-case">
                    <option value="">Tất cả</option>
                    @foreach($templateTypeOptions as $option)
                        <option value="{{ $option['type'] }}">{{ $option['label'] }}</option>
                    @endforeach
                    @foreach($excelTypeOptions as $option)
                        <option value="{{ $option['type'] }}">{{ $option['label'] }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-xs font-bold uppercase text-slate-500">Trạng thái
                <select id="template-filter-status" class="mt-1 block w-full rounded-lg border px-3 py-2 text-sm normal-case">
                    <option value="">Tất cả</option>
                    <option value="active">Đang dùng</option>
                    <option value="inactive">Tạm ẩn</option>
                </select>
            </label>
        </div>
        <div class="overflow-x-auto">
        <table class="w-full min-w-[1280px] table-fixed text-left text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="w-[230px] p-3">Tên / mã mẫu</th>
                    <th class="w-[120px] p-3">Định dạng</th>
                    <th class="w-[150px] p-3">Nhóm mẫu</th>
                    <th class="w-[230px] p-3">Loại báo cáo</th>
                    <th class="w-[170px] p-3">Mã loại</th>
                    <th class="w-[230px] p-3">File</th>
                    <th class="w-[105px] p-3">Trạng thái</th>
                    <th class="w-[115px] p-3">Cập nhật</th>
                    <th class="w-[160px] p-3 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @foreach(($defaultTemplates ?? []) as $type => $template)
                    @php
                        $customTemplate = ($customTemplates ?? collect())->get($type);
                        $customDownloadUrl = $customTemplate && $customTemplate->file_path ? route('inventory.templates.download', $customTemplate).'?v='.$customTemplate->downloadVersion() : null;
                    @endphp
                    <tr class="border-t" data-template-row data-format="docx" data-group="default" data-type="{{ $type }}" data-status="active" data-search="{{ Str::lower(($template['name'] ?? '').' DEFAULT_'.strtoupper(str_replace('-', '_', $type)).' '.($template['report'] ?? '').' '.($template['variable_file'] ?? '')) }}">
                        <td class="p-3">
                            <p class="font-semibold">{{ $template['name'] }}</p>
                            <p class="mt-1 font-mono text-xs text-slate-500">{{ 'DEFAULT_'.strtoupper(str_replace('-', '_', $type)) }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $template['scope'] ?? ($customTemplate ? 'File Word đã import thay mẫu gốc.' : 'Mẫu gốc của hệ thống.') }}</p>
                        </td>
                        <td class="p-3"><span class="rounded bg-blue-100 px-2 py-1 text-xs font-bold text-blue-700">Word .docx</span></td>
                        <td class="p-3">Mẫu báo cáo</td>
                        <td class="p-3">{{ $template['report'] ?? $template['name'] }}</td>
                        <td class="p-3"><span class="font-mono text-xs text-slate-600">{{ $type }}</span></td>
                        <td class="p-3">
                            @if($customTemplate && $customTemplate->file_path)
                                <a href="{{ $customDownloadUrl }}" class="break-words text-blue-600 underline">{{ $customTemplate->downloadName() }}</a>
                            @else
                                <a href="{{ route('inventory.templates.variable.download', $type) }}" class="break-words text-blue-600 underline">{{ $template['variable_file'] ?? ('mau-bien-'.$type.'.docx') }}</a>
                            @endif
                        </td>
                        <td class="p-3"><span class="rounded bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Đang sử dụng</span></td>
                        <td class="p-3">{{ $customTemplate ? optional($customTemplate->updated_at)->format('d/m/Y H:i') : '—' }}</td>
                        <td class="p-3 text-right">
                            <div class="inline-flex flex-wrap justify-end gap-2">
                                <button type="button" data-template-toggle="default-{{ $type }}" class="rounded border px-3 py-1.5 text-xs font-semibold">Sửa</button>
                                <a href="{{ $customDownloadUrl ?: route('inventory.templates.variable.download', $type) }}" class="rounded border px-3 py-1.5 text-xs font-semibold">Tải mẫu</a>
                                <form method="POST" action="{{ route('inventory.templates.default.delete', $type) }}" onsubmit="return confirm('Xóa hoàn toàn mẫu báo cáo này? Hệ thống sẽ không quay về file mặc định.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded bg-red-600 px-3 py-1.5 text-xs font-semibold text-white">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr id="template-edit-default-{{ $type }}" class="hidden border-t bg-slate-50" data-template-edit-row="default-{{ $type }}">
                        <td colspan="9" class="p-4">
                            <form method="POST" action="{{ route('inventory.templates.default.replace', $type) }}" enctype="multipart/form-data" class="grid gap-3 md:grid-cols-3">
                                @csrf
                                <label class="text-sm font-semibold">Tên mẫu
                                    <input name="name" required value="{{ $template['name'] }}" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                                </label>
                                <label class="text-sm font-semibold">Loại báo cáo
                                    <select name="report_type" required class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                                        @foreach($templateTypeOptions as $option)
                                            <option value="{{ $option['type'] }}" @selected($type === $option['type'])>{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="text-sm font-semibold">File Word mới
                                    <input name="file" type="file" accept=".docx" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                                </label>
                                <label class="flex items-center gap-2 text-sm font-semibold">
                                    <input type="checkbox" name="active" value="1" @checked($customTemplate?->active ?? true) class="rounded border">
                                    Dùng mẫu này khi xuất báo cáo
                                </label>
                                <div class="flex items-end justify-end gap-2 md:col-span-2">
                                    <button type="button" data-template-toggle="default-{{ $type }}" class="rounded border px-4 py-2 text-sm font-semibold">Hủy</button>
                                    <button class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Lưu sửa</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @endforeach
                @foreach(($excelTemplates ?? collect()) as $item)
                    @php
                        $itemDownloadUrl = $item->file_path ? route('inventory.templates.download', $item).'?v='.$item->downloadVersion() : null;
                        $option = $excelTypeOptions->firstWhere('type', $item->report_type);
                    @endphp
                    <tr class="border-t" data-template-row data-format="xlsx" data-group="upload" data-type="{{ $item->report_type }}" data-status="{{ $item->active ? 'active' : 'inactive' }}" data-search="{{ Str::lower($item->name.' '.$item->code.' '.$item->report_type.' '.$item->downloadName().' '.($option['label'] ?? '')) }}">
                        <td class="p-3"><p class="font-semibold">{{ $item->name }}</p><p class="mt-1 font-mono text-xs text-slate-500">{{ $item->code }}</p></td>
                        <td class="p-3"><span class="rounded bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-700">Excel .xlsx</span></td>
                        <td class="p-3">Mẫu tải lên</td>
                        <td class="p-3">{{ $option['label'] ?? $item->report_type }}</td>
                        <td class="p-3"><span class="font-mono text-xs text-slate-600">{{ $item->report_type }}</span></td>
                        <td class="p-3">
                            @if($itemDownloadUrl)
                                <a href="{{ $itemDownloadUrl }}" class="break-words text-blue-600 underline">{{ $item->downloadName() }}</a>
                            @else
                                Chưa có file
                            @endif
                        </td>
                        <td class="p-3"><span class="rounded px-2 py-1 text-xs font-semibold {{ $item->active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $item->active ? 'Đang dùng' : 'Tạm ẩn' }}</span></td>
                        <td class="p-3">{{ optional($item->updated_at)->format('d/m/Y H:i') ?: '—' }}</td>
                        <td class="p-3 text-right">
                            <div class="inline-flex flex-wrap justify-end gap-2">
                                <button type="button" data-template-toggle="{{ $item->id }}" class="rounded border px-3 py-1.5 text-xs font-semibold">Sửa</button>
                                @if($itemDownloadUrl)
                                    <a href="{{ $itemDownloadUrl }}" class="rounded border px-3 py-1.5 text-xs font-semibold">Tải mẫu</a>
                                @endif
                                <form method="POST" action="{{ route('inventory.templates.delete', $item) }}" onsubmit="return confirm('Xóa mẫu Excel này?')">@csrf @method('DELETE')<button class="rounded bg-red-600 px-3 py-1.5 text-xs font-semibold text-white">Xóa</button></form>
                            </div>
                        </td>
                    </tr>
                    <tr id="template-edit-{{ $item->id }}" class="hidden border-t bg-slate-50" data-template-edit-row="{{ $item->id }}">
                        <td colspan="9" class="p-4">
                            <form method="POST" action="{{ route('inventory.templates.update', $item) }}" enctype="multipart/form-data" class="grid gap-3 md:grid-cols-3">
                                @csrf
                                @method('PATCH')
                                <label class="text-sm font-semibold">Mã mẫu
                                    <input name="code" required value="{{ $item->code }}" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                                </label>
                                <label class="text-sm font-semibold">Tên mẫu
                                    <input name="name" required value="{{ $item->name }}" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                                </label>
                                <label class="text-sm font-semibold">Loại báo cáo Excel
                                    <select name="report_type" required class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                                        @foreach($excelTypeOptions as $option)
                                            <option value="{{ $option['type'] }}" @selected($item->report_type === $option['type'])>{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="text-sm font-semibold">Thay file Excel
                                    <input name="file" type="file" accept=".xlsx" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                                </label>
                                <label class="text-sm font-semibold md:col-span-3">Ghi chú
                                    <textarea name="description" rows="2" class="mt-1 block w-full rounded-lg border px-3 py-2.5">{{ $item->description }}</textarea>
                                </label>
                                <label class="flex items-center gap-2 text-sm font-semibold">
                                    <input type="checkbox" name="active" value="1" @checked($item->active) class="rounded border">
                                    Cho phép chọn khi xuất Excel
                                </label>
                                <div class="flex items-end justify-end gap-2 md:col-span-2">
                                    <button type="button" data-template-toggle="{{ $item->id }}" class="rounded border px-4 py-2 text-sm font-semibold">Hủy</button>
                                    <button class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Lưu sửa</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @endforeach
                @foreach(($uploadTemplates ?? collect()) as $item)
                    @php
                        $itemDownloadUrl = $item->file_path ? route('inventory.templates.download', $item).'?v='.$item->downloadVersion() : null;
                        $templateOption = $templateTypeOptions->firstWhere('type', $item->report_type);
                    @endphp
                    <tr class="border-t" data-template-row data-format="docx" data-group="upload" data-type="{{ $item->report_type }}" data-status="{{ $item->active ? 'active' : 'inactive' }}" data-search="{{ Str::lower($item->name.' '.$item->code.' '.$item->report_type.' '.$item->downloadName().' '.($templateOption['label'] ?? '')) }}">
                        <td class="p-3">
                            <p class="font-semibold">{{ $item->name }}</p>
                            <p class="mt-1 font-mono text-xs text-slate-500">{{ $item->code }}</p>
                        </td>
                        <td class="p-3"><span class="rounded bg-blue-100 px-2 py-1 text-xs font-bold text-blue-700">Word .docx</span></td>
                        <td class="p-3">Mẫu tải lên</td>
                        <td class="p-3">{{ $templateOption['label'] ?? ($item->description ?: 'Theo file Word đã tải lên') }}</td>
                        <td class="p-3"><span class="font-mono text-xs text-slate-600">{{ $item->report_type ?: 'tu-nhan-dien' }}</span></td>
                        <td class="p-3">
                            @if($item->file_path)
                                <a href="{{ $itemDownloadUrl }}" class="break-words text-blue-600 underline">{{ $item->downloadName() }}</a>
                            @else
                                Chưa có file
                            @endif
                        </td>
                        <td class="p-3">
                            <span class="rounded px-2 py-1 text-xs font-semibold {{ $item->active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $item->active ? 'Đang dùng' : 'Tạm ẩn' }}</span>
                        </td>
                        <td class="p-3">{{ optional($item->updated_at)->format('d/m/Y H:i') ?: '—' }}</td>
                        <td class="p-3 text-right">
                            <div class="inline-flex flex-wrap justify-end gap-2">
                                <button type="button" data-template-toggle="{{ $item->id }}" class="rounded border px-3 py-1.5 text-xs font-semibold">Sửa</button>
                                @if($item->file_path)
                                    <a href="{{ $itemDownloadUrl }}" class="rounded border px-3 py-1.5 text-xs font-semibold">Tải mẫu</a>
                                @endif
                                <form method="POST" action="{{ route('inventory.templates.delete', $item) }}" onsubmit="return confirm('Xóa mẫu báo cáo này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded bg-red-600 px-3 py-1.5 text-xs font-semibold text-white">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr id="template-edit-{{ $item->id }}" class="hidden border-t bg-slate-50" data-template-edit-row="{{ $item->id }}">
                        <td colspan="9" class="p-4">
                            <form method="POST" action="{{ route('inventory.templates.update', $item) }}" enctype="multipart/form-data" class="grid gap-3 md:grid-cols-3">
                                @csrf
                                @method('PATCH')
                                <label class="text-sm font-semibold">Mã mẫu
                                    <input name="code" required value="{{ $item->code }}" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                                </label>
                                <label class="text-sm font-semibold">Tên mẫu
                                    <input name="name" required value="{{ $item->name }}" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                                </label>
                                <label class="text-sm font-semibold">Loại báo cáo
                                    <select name="report_type" required class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                                        @foreach($templateTypeOptions as $option)
                                            <option value="{{ $option['type'] }}" @selected($item->report_type === $option['type'])>{{ $option['label'] }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label class="text-sm font-semibold">Thay file Word
                                    <input name="file" type="file" accept=".docx" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                                </label>
                                <label class="text-sm font-semibold md:col-span-3">Ghi chú
                                    <textarea name="description" rows="2" class="mt-1 block w-full rounded-lg border px-3 py-2.5">{{ $item->description }}</textarea>
                                </label>
                                <label class="flex items-center gap-2 text-sm font-semibold">
                                    <input type="checkbox" name="active" value="1" @checked($item->active) class="rounded border">
                                    Cho phép chọn khi xuất báo cáo
                                </label>
                                <div class="flex items-end justify-end gap-2 md:col-span-2">
                                    <button type="button" data-template-toggle="{{ $item->id }}" class="rounded border px-4 py-2 text-sm font-semibold">Hủy</button>
                                    <button class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Lưu sửa</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const filters = {
        search: document.getElementById('template-filter-search'),
        format: document.getElementById('template-filter-format'),
        group: document.getElementById('template-filter-group'),
        type: document.getElementById('template-filter-type'),
        status: document.getElementById('template-filter-status'),
    };
    const applyTemplateFilters = function () {
        const query = (filters.search?.value || '').trim().toLowerCase();
        const format = filters.format?.value || '';
        const group = filters.group?.value || '';
        const type = filters.type?.value || '';
        const status = filters.status?.value || '';

        document.querySelectorAll('[data-template-row]').forEach(function (row) {
            const visible = (!query || (row.dataset.search || '').includes(query))
                && (!format || row.dataset.format === format)
                && (!group || row.dataset.group === group)
                && (!type || row.dataset.type === type)
                && (!status || row.dataset.status === status);
            row.classList.toggle('hidden', !visible);
            const toggle = row.querySelector('[data-template-toggle]');
            if (toggle) {
                document.querySelector('[data-template-edit-row="' + toggle.dataset.templateToggle + '"]')?.classList.add('hidden');
            }
        });
    };
    Object.values(filters).forEach(function (input) {
        input?.addEventListener('input', applyTemplateFilters);
        input?.addEventListener('change', applyTemplateFilters);
    });
    document.querySelectorAll('[data-template-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            const row = document.getElementById('template-edit-' + button.dataset.templateToggle);
            row?.classList.toggle('hidden');
        });
    });
    applyTemplateFilters();
});
</script>
