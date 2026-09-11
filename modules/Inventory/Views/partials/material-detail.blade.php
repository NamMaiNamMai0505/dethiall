@php
    $statuses = ['ACTIVE'=>'Đang hoạt động','NORMAL'=>'Bình thường','BROKEN'=>'Hỏng','REPAIRING'=>'Đang sửa chữa','LIQUIDATED'=>'Đã thanh lý','INACTIVE'=>'Ngừng hoạt động'];
@endphp

<div class="space-y-4">
    <div class="flex flex-wrap items-center justify-between gap-3 rounded border bg-white p-4">
        <div>
            <p class="text-xs uppercase text-slate-500">Vật tư thuộc {{ $material->category?->parent?->code ?: '—' }} / {{ $material->category?->code ?: '—' }}</p>
            <h2 class="text-xl font-bold">{{ $material->code }} — {{ $material->name }}</h2>
        </div>
        <a href="{{ route('inventory.category') }}" class="rounded border px-3 py-2">Quay lại danh mục vật tư</a>
    </div>

    <section class="grid gap-3 rounded border bg-white p-4 md:grid-cols-4">
        <div><b>Ngành vật tư</b><p>{{ $material->category?->parent?->code }} — {{ $material->category?->parent?->name }}</p></div>
        <div><b>Loại vật tư</b><p>{{ $material->category?->code }} — {{ $material->category?->name }}</p></div>
        <div><b>Số lượng</b><p>{{ $material->quantity }} {{ $material->unit }}</p></div>
        <div><b>Phân cấp</b><p>{{ $material->classification ?: '—' }}</p></div>
        <div><b>Năm sản xuất</b><p>{{ $material->manufacture_year ?: '—' }}</p></div>
        <div><b>Năm sử dụng</b><p>{{ $material->usage_year ?: '—' }}</p></div>
        <div><b>Trạng thái</b><p>{{ $statuses[$material->status] ?? ($material->status ?: '—') }}</p></div>
        <div><b>Vị trí</b><p>{{ $material->location ?: '—' }}</p></div>
        <div><b>Ngày mua</b><p>{{ $material->purchase_date?->format('d/m/Y') ?: '—' }}</p></div>
        <div><b>Hết hạn bảo hành</b><p>{{ $material->expiry_date?->format('d/m/Y') ?: '—' }}</p></div>
        <div><b>Số tài sản</b><p>{{ $material->assets_count }}</p></div>
        <div><b>Số lần biến động</b><p>{{ $material->movements_count }}</p></div>
        <div class="md:col-span-4"><b>Ghi chú / mô tả</b><p>{{ $material->description ?: ($material->note ?: '—') }}</p></div>
    </section>

    <section class="overflow-x-auto rounded border bg-white p-4">
        <h3 class="mb-3 font-semibold">Vật tư trong phòng</h3>
        <table class="w-full min-w-[800px] text-left text-sm">
            <thead class="bg-slate-100"><tr><th class="p-3">Mã</th><th class="p-3">Tên</th><th class="p-3">Phòng</th><th class="p-3">Số lượng</th><th class="p-3">Phân cấp</th><th class="p-3">Trạng thái</th></tr></thead>
            <tbody>
                @forelse($material->assets as $asset)
                    <tr class="border-t"><td class="p-3">{{ $asset->asset_code }}</td><td class="p-3">{{ $asset->name }}</td><td class="p-3">{{ $asset->classroom?->name ?: '—' }}</td><td class="p-3">{{ $asset->quantity }} {{ $asset->unit }}</td><td class="p-3">{{ $asset->grade ?: '—' }}</td><td class="p-3">{{ $statuses[$asset->status] ?? ($asset->status ?: '—') }}</td></tr>
                @empty
                    <tr><td colspan="6" class="p-4 text-center text-slate-500">Vật tư này chưa được đưa vào phòng.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
