<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold">Mẫu báo cáo Word vật tư</h2>
            <p class="mt-1 text-sm text-slate-500">Tải mẫu Word lên, sau đó chọn mẫu này khi xuất báo cáo vật tư.</p>
        </div>
        <a href="{{ route('inventory.reports') }}" class="rounded-lg border px-4 py-2 text-sm font-semibold">Báo cáo vật tư</a>
    </div>

    <form method="POST" action="{{ route('inventory.templates.store') }}" enctype="multipart/form-data" class="rounded-2xl border bg-white p-5">
        @csrf
        <h3 class="mb-4 text-lg font-bold">Thêm mẫu báo cáo</h3>
        <div class="grid gap-4 md:grid-cols-3">
            <label class="text-sm font-semibold">Mã mẫu
                <input name="code" required placeholder="VD: BAO_CAO_KHO_01" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold">Tên mẫu
                <input name="name" required placeholder="VD: Mẫu báo cáo kho" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold">File Word mẫu
                <input name="file" type="file" accept=".docx" required class="mt-1 block w-full rounded-lg border px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold md:col-span-3">Ghi chú
                <textarea name="description" rows="3" placeholder="Ghi loại báo cáo phù hợp hoặc ghi chú cấu trúc mẫu" class="mt-1 block w-full rounded-lg border px-3 py-2.5"></textarea>
            </label>
            <label class="flex items-center gap-2 text-sm font-semibold md:col-span-3">
                <input type="checkbox" name="active" value="1" checked class="rounded border">
                Cho phép chọn mẫu này khi xuất báo cáo
            </label>
        </div>
        <button class="mt-4 rounded-lg bg-slate-900 px-5 py-2.5 font-bold text-white">Lưu mẫu Word</button>
    </form>

    @php
        $commonVariables = ['ngay_bao_cao','ngay','thang','nam','tu_ngay','den_ngay','tieu_de','loai_bao_cao','ten_mau','ma_mau','tong_so','tong_so_luong','tong_vat_tu','tong_so_luong_vat_tu'];
        $rowVariables = ['stt','ngay_du_lieu','ma_vat_tu','ten_vat_tu','nganh','loai_vat_tu','don_vi_tinh','so_luong','phan_cap','trang_thai','toa_nha','phong','don_vi_quan_ly','vi_tri','loai_bien_dong','truoc','sau','nguoi_thuc_hien','ly_do','ghi_chu'];
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
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border bg-white">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b bg-slate-50 px-5 py-4">
            <div>
                <h3 class="text-lg font-bold text-slate-900">Danh sách mẫu báo cáo</h3>
                <p class="mt-1 text-sm text-slate-500">Mỗi dòng là một mẫu theo loại báo cáo; sửa dòng nào thì khi xuất loại báo cáo đó sẽ dùng file vừa import.</p>
            </div>
            <span class="rounded-full bg-blue-100 px-3 py-1 text-xs font-semibold text-blue-700">{{ count($defaultTemplates ?? []) + ($uploadTemplates ?? collect())->count() }} mẫu</span>
        </div>
        <div class="overflow-x-auto">
        <table class="w-full min-w-[1120px] table-fixed text-left text-sm">
            <thead class="bg-slate-50">
                <tr>
                    <th class="w-[230px] p-3">Tên mẫu</th>
                    <th class="w-[160px] p-3">Nhóm mẫu</th>
                    <th class="w-[145px] p-3">Theo báo cáo</th>
                    <th class="w-[230px] p-3">File</th>
                    <th class="w-[105px] p-3">Trạng thái</th>
                    <th class="w-[115px] p-3">Cập nhật</th>
                    <th class="w-[160px] p-3 text-right">Thao tác</th>
                </tr>
            </thead>
            <tbody>
                @foreach(($defaultTemplates ?? []) as $type => $template)
                    @php($customTemplate = ($customTemplates ?? collect())->get($type))
                    <tr class="border-t">
                        <td class="p-3">
                            <p class="font-semibold">{{ $template['name'] }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $customTemplate ? 'File Word đã import thay mẫu gốc.' : 'Mẫu gốc của hệ thống.' }}</p>
                        </td>
                        <td class="p-3">Mẫu báo cáo</td>
                        <td class="p-3">{{ $template['report'] ?? $template['name'] }}</td>
                        <td class="p-3">
                            @if($customTemplate)
                                <a href="{{ route('inventory.templates.download', $customTemplate) }}" class="break-words text-blue-600 underline">{{ basename($customTemplate->file_path) }}</a>
                            @else
                                <a href="{{ route('inventory.templates.variable.download', $type) }}" class="break-words text-blue-600 underline">{{ $template['variable_file'] ?? ('mau-bien-'.$type.'.docx') }}</a>
                            @endif
                        </td>
                        <td class="p-3"><span class="rounded bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">Đang sử dụng</span></td>
                        <td class="p-3">{{ $customTemplate ? optional($customTemplate->updated_at)->format('d/m/Y H:i') : '—' }}</td>
                        <td class="p-3 text-right">
                            <div class="inline-flex flex-wrap justify-end gap-2">
                                <button type="button" data-template-toggle="default-{{ $type }}" class="rounded border px-3 py-1.5 text-xs font-semibold">Sửa</button>
                                <a href="{{ $customTemplate ? route('inventory.templates.download', $customTemplate) : route('inventory.templates.variable.download', $type) }}" class="rounded bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white">Tải mẫu biến</a>
                                <form method="POST" action="{{ route('inventory.templates.default.delete', $type) }}" onsubmit="return confirm('Xóa mẫu tùy chỉnh của báo cáo này? Nếu đang dùng mẫu gốc thì hệ thống sẽ giữ nguyên mẫu gốc.')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded bg-red-600 px-3 py-1.5 text-xs font-semibold text-white">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr id="template-edit-default-{{ $type }}" class="hidden border-t bg-slate-50">
                        <td colspan="7" class="p-4">
                            <form method="POST" action="{{ route('inventory.templates.default.replace', $type) }}" enctype="multipart/form-data" class="grid gap-3 md:grid-cols-3">
                                @csrf
                                <div class="md:col-span-2">
                                    <p class="font-semibold text-slate-800">Import file Word thay mẫu: {{ $template['name'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500">File mới sẽ giữ form bạn sửa, hệ thống chỉ thay dữ liệu trong ô/bảng thành biến để khi xuất sẽ đổ dữ liệu database vào.</p>
                                </div>
                                <label class="text-sm font-semibold">File Word mới
                                    <input name="file" type="file" accept=".docx" required class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                                </label>
                                <label class="flex items-center gap-2 text-sm font-semibold">
                                    <input type="checkbox" name="active" value="1" checked class="rounded border">
                                    Dùng mẫu này khi xuất báo cáo
                                </label>
                                <div class="flex items-end justify-end gap-2 md:col-span-2">
                                    <button type="button" data-template-toggle="default-{{ $type }}" class="rounded border px-4 py-2 text-sm font-semibold">Hủy</button>
                                    <button class="rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white">Import thay mẫu</button>
                                </div>
                            </form>
                        </td>
                    </tr>
                @endforeach
                @foreach(($uploadTemplates ?? collect()) as $item)
                    <tr class="border-t">
                        <td class="p-3">
                            <p class="font-semibold">{{ $item->name }}</p>
                            <p class="mt-1 text-xs text-slate-500">{{ $item->code }}</p>
                        </td>
                        <td class="p-3">Mẫu tải lên</td>
                        <td class="p-3">{{ $item->description ?: 'Theo file Word đã tải lên' }}</td>
                        <td class="p-3">
                            @if($item->file_path)
                                <a href="{{ route('inventory.templates.download', $item) }}" class="break-words text-blue-600 underline">{{ basename($item->file_path) }}</a>
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
                                    <a href="{{ route('inventory.templates.download', $item) }}" class="rounded border px-3 py-1.5 text-xs font-semibold">Tải mẫu</a>
                                @endif
                                <form method="POST" action="{{ route('inventory.templates.delete', $item) }}" onsubmit="return confirm('Xóa mẫu báo cáo này?')">
                                    @csrf
                                    @method('DELETE')
                                    <button class="rounded bg-red-600 px-3 py-1.5 text-xs font-semibold text-white">Xóa</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <tr id="template-edit-{{ $item->id }}" class="hidden border-t bg-slate-50">
                        <td colspan="7" class="p-4">
                            <form method="POST" action="{{ route('inventory.templates.update', $item) }}" enctype="multipart/form-data" class="grid gap-3 md:grid-cols-3">
                                @csrf
                                @method('PATCH')
                                <label class="text-sm font-semibold">Mã mẫu
                                    <input name="code" required value="{{ $item->code }}" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
                                </label>
                                <label class="text-sm font-semibold">Tên mẫu
                                    <input name="name" required value="{{ $item->name }}" class="mt-1 block w-full rounded-lg border px-3 py-2.5">
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
    document.querySelectorAll('[data-template-toggle]').forEach(function (button) {
        button.addEventListener('click', function () {
            const row = document.getElementById('template-edit-' + button.dataset.templateToggle);
            row?.classList.toggle('hidden');
        });
    });
});
</script>
