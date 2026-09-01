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
    $placeholderGroups = [
        'Thông tin chung' => ['nam', 'ngay_bao_cao', 'tieu_de', 'dien_quan_ly', 'co_quan_quan_ly', 'nguoi_bao_cao', 'thu_truong', 'tong_so', 'so_phep_nam', 'so_phep_dac_biet', 'so_da_nghi', 'so_chua_nghi'],
        'Dòng dữ liệu' => ['stt', 'ho_ten', 'cap_bac', 'nhap_ngu', 'don_vi', 'tu_ngay', 'den_ngay', 'noi_nghi_phep', 'ly_do', 'que_quan', 'tru_quan', 'ghi_chu', 'tong_ngay', 'da_nghi', 'con_lai'],
    ];
@endphp

<div class="grid gap-5 lg:grid-cols-[minmax(0,1fr)_360px]">
    <form method="POST" action="{{ route('leave-management.report-templates.store') }}" enctype="multipart/form-data" class="rounded border bg-white p-5">
        @csrf
        <h2 class="text-lg font-extrabold text-slate-900">Thêm mẫu báo cáo Word</h2>
        <div class="mt-4 grid gap-3 md:grid-cols-2">
            <label class="text-sm font-semibold">Tên mẫu
                <input name="name" required maxlength="255" value="{{ old('name') }}" class="mt-1 block w-full rounded border p-2">
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
                <textarea name="description" rows="3" class="mt-1 block w-full rounded border p-2">{{ old('description') }}</textarea>
            </label>
            <label class="flex items-center gap-2 text-sm font-semibold">
                <input type="checkbox" name="active" value="1" checked>
                Dùng làm mẫu đang hoạt động cho loại báo cáo này
            </label>
        </div>
        <button class="mt-4 rounded bg-blue-600 px-4 py-2 font-semibold text-white">Thêm mẫu</button>
    </form>

    <div class="rounded border bg-white p-5">
        <h2 class="text-lg font-extrabold text-slate-900">Biến dùng trong mẫu</h2>
        <p class="mt-2 text-sm text-slate-600">Trong file Word đặt biến dạng <code>${ten_bien}</code>. Với bảng dữ liệu, đặt các biến dòng trong cùng một hàng bảng để hệ thống nhân dòng khi in.</p>
        @foreach($placeholderGroups as $title => $placeholders)
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

<div class="mt-5 overflow-x-auto rounded border bg-white">
    <table class="w-full min-w-[980px] text-left text-sm">
        <thead class="bg-slate-100">
            <tr>
                <th class="p-3">Tên mẫu</th>
                <th class="p-3">Loại báo cáo</th>
                <th class="p-3">File</th>
                <th class="p-3">Diện quản lý</th>
                <th class="p-3">Trạng thái</th>
                <th class="p-3">Cập nhật</th>
                <th class="p-3">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($items as $item)
                <tr class="border-t align-top">
                    <td class="p-3">
                        <div class="font-bold text-slate-900">{{ $item->name }}</div>
                        @if($item->description)<div class="mt-1 text-xs text-slate-500">{{ $item->description }}</div>@endif
                    </td>
                    <td class="p-3">{{ $typeLabels[$item->report_type] ?? $item->report_type }}</td>
                    <td class="p-3">
                        <a class="font-semibold text-blue-700" href="{{ route('leave-management.report-templates.download', $item) }}">{{ $item->original_name ?: basename($item->file_path) }}</a>
                        <div class="text-xs text-slate-500">{{ number_format(((int) $item->file_size) / 1024, 1) }} KB</div>
                    </td>
                    <td class="p-3">{{ $agencyLabels[$item->managing_agency] ?? $item->managing_agency }}</td>
                    <td class="p-3">
                        <span class="rounded-full px-2 py-1 text-xs font-bold {{ $item->active ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $item->active ? 'Đang dùng' : 'Tắt' }}</span>
                    </td>
                    <td class="p-3">{{ $item->updated_at?->format('d/m/Y H:i') }}</td>
                    <td class="p-3">
                        <a class="mb-2 inline-flex min-w-[118px] items-center justify-center gap-1 rounded bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700" href="{{ route('leave-management.report-templates.download', $item) }}">
                            <i class="bi bi-download"></i> Tải về
                        </a>
                        <details>
                            <summary class="inline-flex min-w-[118px] cursor-pointer list-none items-center justify-center gap-1 rounded bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700">
                                <i class="bi bi-pencil-square"></i> Sửa
                            </summary>
                            <form method="POST" action="{{ route('leave-management.report-templates.update', $item) }}" enctype="multipart/form-data" class="mt-3 grid gap-2 rounded border bg-slate-50 p-3">
                                @csrf
                                @method('PATCH')
                                <input name="name" required maxlength="255" value="{{ $item->name }}" class="rounded border p-2">
                                <select name="report_type" required class="rounded border p-2">
                                    @foreach($typeLabels as $type => $label)
                                        <option value="{{ $type }}" @selected($item->report_type === $type)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <select name="managing_agency" required class="rounded border p-2">
                                    @foreach($agencyLabels as $agency => $label)
                                        <option value="{{ $agency }}" @selected($item->managing_agency === $agency)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <textarea name="description" rows="2" class="rounded border p-2">{{ $item->description }}</textarea>
                                <input name="file" type="file" accept=".docx" class="rounded border p-2">
                                <label class="flex items-center gap-2 text-xs font-semibold"><input type="checkbox" name="active" value="1" @checked($item->active)> Đang hoạt động</label>
                                <button class="rounded bg-blue-600 px-3 py-2 text-sm font-semibold text-white">Lưu mẫu</button>
                            </form>
                        </details>
                        <form method="POST" action="{{ route('leave-management.report-templates.delete', $item) }}" class="mt-2" onsubmit="return confirm('Xóa mẫu báo cáo này?');">
                            @csrf
                            @method('DELETE')
                            <button class="inline-flex min-w-[118px] items-center justify-center gap-1 rounded bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                                <i class="bi bi-trash"></i> Xóa
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="p-6 text-center text-slate-500">Chưa có mẫu báo cáo nào.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
