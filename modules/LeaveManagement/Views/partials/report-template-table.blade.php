<div class="mt-5 overflow-x-auto rounded border bg-white">
    <table class="w-full min-w-[980px] text-left text-sm">
        <thead class="bg-slate-100">
            <tr>
                <th class="p-3">Tên mẫu</th>
                @if($templateKind === 'report')
                    <th class="p-3">Loại báo cáo</th>
                    <th class="p-3">Diện quản lý</th>
                @endif
                <th class="p-3">File</th>
                <th class="p-3">Trạng thái</th>
                <th class="p-3">Cập nhật</th>
                <th class="p-3">Thao tác</th>
            </tr>
        </thead>
        <tbody>
            @forelse($templateItems as $item)
                <tr class="border-t align-top">
                    <td class="p-3">
                        <div class="font-bold text-slate-900">{{ $item->name }}</div>
                        @if($item->description)<div class="mt-1 text-xs text-slate-500">{{ $item->description }}</div>@endif
                    </td>
                    @if($templateKind === 'report')
                        <td class="p-3">{{ $typeLabels[$item->report_type] ?? $item->report_type }}</td>
                        <td class="p-3">{{ $agencyLabels[$item->managing_agency] ?? $item->managing_agency }}</td>
                    @endif
                    <td class="p-3">
                        <a class="font-semibold text-blue-700" href="{{ route('leave-management.report-templates.download', $item) }}">{{ $item->original_name ?: basename($item->file_path) }}</a>
                        <div class="text-xs text-slate-500">{{ number_format(((int) $item->file_size) / 1024, 1) }} KB</div>
                    </td>
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
                                <input type="hidden" name="template_kind" value="{{ $templateKind }}">
                                <input name="name" required maxlength="255" value="{{ $item->name }}" class="rounded border p-2">
                                @if($templateKind === 'report')
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
                                @endif
                                <textarea name="description" rows="2" class="rounded border p-2">{{ $item->description }}</textarea>
                                <input name="file" type="file" accept=".docx" class="rounded border p-2">
                                <label class="flex items-center gap-2 text-xs font-semibold"><input type="checkbox" name="active" value="1" @checked($item->active)> Đang hoạt động</label>
                                <button class="rounded bg-blue-600 px-3 py-2 text-sm font-semibold text-white">Lưu mẫu</button>
                            </form>
                        </details>
                        <form method="POST" action="{{ route('leave-management.report-templates.delete', $item) }}" class="mt-2" onsubmit="return confirm('Xóa mẫu này?');">
                            @csrf
                            @method('DELETE')
                            <button class="inline-flex min-w-[118px] items-center justify-center gap-1 rounded bg-red-600 px-4 py-2 text-sm font-semibold text-white hover:bg-red-700">
                                <i class="bi bi-trash"></i> Xóa
                            </button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ $templateKind === 'report' ? 7 : 5 }}" class="p-6 text-center text-slate-500">Chưa có mẫu nào.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
