<div class="space-y-4" data-room-detail>
    <div class="flex flex-wrap gap-2 rounded border bg-white p-3" role="tablist" aria-label="Thông tin phòng">
        <button type="button" class="room-detail-tab rounded bg-blue-600 px-3 py-2 text-sm font-semibold text-white" data-room-tab-button="info">Thông tin chung</button>
        <button type="button" class="room-detail-tab rounded border px-3 py-2 text-sm font-semibold" data-room-tab-button="materials">Vật tư trong phòng</button>
        <button type="button" class="room-detail-tab rounded border px-3 py-2 text-sm font-semibold" data-room-tab-button="broken">Phiếu báo hỏng</button>
        <button type="button" class="room-detail-tab rounded border px-3 py-2 text-sm font-semibold" data-room-tab-button="repairs">Sửa chữa</button>
        <button type="button" class="room-detail-tab rounded border px-3 py-2 text-sm font-semibold" data-room-tab-button="inventories">Kiểm kê</button>
        <button type="button" class="room-detail-tab rounded border px-3 py-2 text-sm font-semibold" data-room-tab-button="replacements">Thay thế</button>
        <button type="button" class="room-detail-tab rounded border px-3 py-2 text-sm font-semibold" data-room-tab-button="management">Ảnh và quản lý phòng</button>
    </div>
    <section class="rounded border bg-white p-4" data-room-tab-panel="info">
        <div class="flex items-center justify-between"><h2 class="font-semibold">Thông tin phòng</h2><a href="{{ route('classrooms.edit', $classroom) }}" class="rounded bg-blue-600 px-3 py-2 text-sm text-white">Sửa thông tin phòng</a></div>
        <div class="mt-3 grid gap-3 text-sm md:grid-cols-4">
            <div><span class="text-slate-500">Mã phòng</span><p class="font-semibold">{{ $classroom->code ?: '—' }}</p></div>
            <div><span class="text-slate-500">Tên phòng</span><p class="font-semibold">{{ $classroom->name }}</p></div>
            <div><span class="text-slate-500">Loại phòng</span><p>{{ $classroom->room_type ?: '—' }}</p></div>
            <div><span class="text-slate-500">Tòa nhà / Tầng</span><p>{{ $classroom->building?->name ?: '—' }} / {{ $classroom->floor ?: '—' }}</p></div>
            <div><span class="text-slate-500">Sức chứa</span><p>{{ $classroom->capacity ?: '—' }}</p></div>
            <div><span class="text-slate-500">Trạng thái</span><p>{{ $classroom->status ? 'Hoạt động' : 'Ngừng hoạt động' }}</p></div>
            <div><span class="text-slate-500">Đơn vị quản lý</span><p>{{ $classroom->managingUnit?->name ?: '—' }}</p></div>
            <div><span class="text-slate-500">Tổng số lượng vật tư</span><p class="font-semibold">{{ number_format($assets->sum('quantity'), 2, ',', '.') }}</p></div>
            <div class="md:col-span-4"><span class="text-slate-500">Mô tả</span><p>{{ $classroom->description ?: '—' }}</p></div>
        </div>
    </section>

    <section class="hidden overflow-x-auto rounded border bg-white p-4" data-room-tab-panel="materials">@include('inventory::partials.room-assets', ['classroom' => $classroom, 'assets' => $assets, 'units' => $units ?? collect()])</section>

    <div class="grid gap-4 xl:grid-cols-2">
        <section class="hidden overflow-x-auto rounded border bg-white p-4" data-room-tab-panel="broken"><h2 class="mb-3 font-semibold">Phiếu báo hỏng</h2><table class="w-full min-w-[700px] text-left text-sm"><thead class="bg-slate-100"><tr><th class="p-2">Thiết bị</th><th class="p-2">Số lượng</th><th class="p-2">Ngày hư</th><th class="p-2">Trạng thái</th><th class="p-2">Người báo</th><th class="p-2">Ghi chú</th></tr></thead><tbody>@forelse($breakReports as $item)<tr class="border-t"><td class="p-2">{{ $item->equipment_name }}</td><td class="p-2">{{ $item->quantity }}</td><td class="p-2">{{ $item->broken_at?->format('d/m/Y') }}</td><td class="p-2">{{ $item->status }}</td><td class="p-2">{{ $item->reporter?->name ?: '—' }}</td><td class="p-2">{{ $item->note ?: $item->condition_description ?: '—' }}</td></tr>@empty<tr><td colspan="6" class="p-4 text-center text-slate-500">Chưa có phiếu báo hỏng.</td></tr>@endforelse</tbody></table></section>
        <section class="hidden overflow-x-auto rounded border bg-white p-4" data-room-tab-panel="repairs"><h2 class="mb-3 font-semibold">Sửa chữa</h2><table class="w-full min-w-[700px] text-left text-sm"><thead class="bg-slate-100"><tr><th class="p-2">Ngày sửa</th><th class="p-2">Vật tư</th><th class="p-2">Nội dung</th><th class="p-2">Chi phí</th><th class="p-2">Người sửa</th></tr></thead><tbody>@forelse($roomRepairs as $item)<tr class="border-t"><td class="p-2">{{ $item->repair_date?->format('d/m/Y') }}</td><td class="p-2">{{ $item->equipment_name }}</td><td class="p-2">{{ $item->content ?: '—' }}</td><td class="p-2">{{ number_format($item->cost, 0, ',', '.') }}</td><td class="p-2">{{ $item->repaired_by ?: '—' }}</td></tr>@empty<tr><td colspan="5" class="p-4 text-center text-slate-500">Chưa có nhật ký sửa chữa.</td></tr>@endforelse</tbody></table></section>
        <section class="hidden overflow-x-auto rounded border bg-white p-4" data-room-tab-panel="inventories"><h2 class="mb-3 font-semibold">Kiểm kê</h2><table class="w-full min-w-[600px] text-left text-sm"><thead class="bg-slate-100"><tr><th class="p-2">Ngày</th><th class="p-2">Vật tư</th><th class="p-2">Thực tế</th><th class="p-2">Sổ sách</th><th class="p-2">Kết quả</th></tr></thead><tbody>@forelse($roomInventories as $item)<tr class="border-t"><td class="p-2">{{ $item->inventory_date?->format('d/m/Y') }}</td><td class="p-2">{{ $item->equipment_name }}</td><td class="p-2">{{ $item->actual_quantity }}</td><td class="p-2">{{ $item->book_quantity }}</td><td class="p-2">{{ $item->result ?: '—' }}</td></tr>@empty<tr><td colspan="5" class="p-4 text-center text-slate-500">Chưa có biên bản kiểm kê.</td></tr>@endforelse</tbody></table></section>
        <section class="hidden overflow-x-auto rounded border bg-white p-4" data-room-tab-panel="replacements"><h2 class="mb-3 font-semibold">Thay thế</h2><table class="w-full min-w-[600px] text-left text-sm"><thead class="bg-slate-100"><tr><th class="p-2">Ngày</th><th class="p-2">Vật tư cũ</th><th class="p-2">Vật tư mới</th><th class="p-2">Lý do</th></tr></thead><tbody>@forelse($roomReplacements as $item)<tr class="border-t"><td class="p-2">{{ $item->replaced_at?->format('d/m/Y') }}</td><td class="p-2">{{ $item->old_equipment_name }}</td><td class="p-2">{{ $item->new_equipment_name }}</td><td class="p-2">{{ $item->reason ?: '—' }}</td></tr>@empty<tr><td colspan="4" class="p-4 text-center text-slate-500">Chưa có phiếu thay thế.</td></tr>@endforelse</tbody></table></section>
    </div>

    <div class="hidden grid gap-4 lg:grid-cols-2" data-room-tab-panel="management"><div class="rounded border bg-white p-4"><h2 class="mb-2 font-semibold">Ảnh phòng</h2><form method="POST" action="{{ route('inventory.room.images.store',$classroom) }}" enctype="multipart/form-data">@csrf<input name="image" type="file" accept="image/*" required class="w-full rounded border p-2"><input name="caption" placeholder="Chú thích" class="my-2 w-full rounded border p-2"><button class="rounded bg-blue-600 px-3 py-2 text-white">Tải ảnh lên</button></form>@foreach($roomImages as $image)<div class="mt-2 flex items-center justify-between border-t pt-2"><a href="{{ asset('storage/'.$image->path) }}" target="_blank">{{ $image->caption ?: basename($image->path) }}</a><form method="POST" action="{{ route('inventory.room.images.delete',$image) }}">@csrf @method('DELETE')<button class="text-red-600">Xóa</button></form></div>@endforeach</div><div class="rounded border bg-white p-4"><h2 class="mb-2 font-semibold">Người quản lý phòng</h2><form method="POST" action="{{ route('inventory.room.users.store',$classroom) }}">@csrf<select name="user_id" required class="w-full rounded border p-2"><option value="">Chọn tài khoản</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }}</option>@endforeach</select><input name="role" placeholder="Vai trò" class="my-2 w-full rounded border p-2"><button class="rounded bg-blue-600 px-3 py-2 text-white">Gán phụ trách</button></form>@foreach($roomUsers as $roomUser)<div class="mt-2 flex justify-between border-t pt-2"><span>{{ $roomUser->user?->name }} — {{ $roomUser->role }}</span><form method="POST" action="{{ route('inventory.room.users.delete',$roomUser) }}">@csrf @method('DELETE')<button class="text-red-600">Bỏ</button></form></div>@endforeach</div></div>
</div>


<script>
document.querySelectorAll('[data-room-detail]').forEach((root) => {
    const buttons = root.querySelectorAll('[data-room-tab-button]');
    const panels = root.querySelectorAll('[data-room-tab-panel]');
    const activate = (name) => {
        buttons.forEach((button) => {
            const active = button.dataset.roomTabButton === name;
            button.classList.toggle('bg-blue-600', active);
            button.classList.toggle('text-white', active);
            button.classList.toggle('border', !active);
        });
        panels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.roomTabPanel !== name));
    };
    buttons.forEach((button) => button.addEventListener('click', () => activate(button.dataset.roomTabButton)));
    activate('info');
});
</script>
