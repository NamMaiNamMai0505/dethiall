@php
    $canEditCategory = auth()->user()?->isSuperAdmin() || \App\Support\PermissionCheck::can(auth()->user(), 'inventory.categories.edit');
    $canDeleteCategory = auth()->user()?->isSuperAdmin() || \App\Support\PermissionCheck::can(auth()->user(), 'inventory.categories.delete');
@endphp

<div class="space-y-4">
    @if($isTypes ?? false)
        <form method="POST" action="{{ route('inventory.category.store') }}" class="grid gap-3 rounded border bg-white p-4 md:grid-cols-3">
            @csrf
            <h2 class="font-semibold md:col-span-3">Thêm loại vật tư</h2>
            <label class="text-sm font-semibold">Ngành vật tư <span class="text-red-600">*</span>
                <select name="parent_id" required class="mt-1 w-full rounded border p-2">
                    <option value="">Chọn ngành vật tư</option>
                    @foreach($parents as $parent)
                        <option value="{{ $parent->id }}">{{ $parent->code }} - {{ $parent->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-semibold md:col-span-2">Tên loại vật tư <span class="text-red-600">*</span>
                <input name="name" required placeholder="Nhập tên loại vật tư" class="mt-1 w-full rounded border p-2">
            </label>
            <p class="text-xs text-slate-500 md:col-span-3">Mã loại sẽ tự sinh theo mã ngành, ví dụ HC2A01, HC2A02...</p>
            <button class="rounded bg-blue-600 px-4 py-2 text-white md:col-span-3">Thêm loại vật tư</button>
        </form>

        <div class="overflow-x-auto rounded border bg-white p-4">
            <h2 class="mb-3 font-semibold">Danh sách loại vật tư</h2>
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="p-3">STT</th>
                        <th class="p-3">Mã loại</th>
                        <th class="p-3">Tên loại</th>
                        <th class="p-3">Ngành vật tư</th>
                        <th class="p-3">Số vật tư</th>
                        <th class="p-3">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $i => $type)
                        <tr class="border-t">
                            <td class="p-3">{{ $i + 1 }}</td>
                            <td class="p-3 font-semibold">{{ $type->code }}</td>
                            <td class="p-3">{{ $type->name }}</td>
                            <td class="p-3">{{ $type->parent?->code }} - {{ $type->parent?->name }}</td>
                            <td class="p-3">{{ $type->materials_count }}</td>
                            <td class="p-3">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('inventory.category.show', $type) }}" class="rounded border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-700">Chi tiết</a>
                                    @if($canEditCategory)
                                        <button type="button" class="rounded bg-blue-600 px-3 py-1.5 text-xs font-bold text-white" onclick="document.getElementById('category-edit-{{ $type->id }}').classList.toggle('hidden')">Sửa</button>
                                    @endif
                                    @if($canDeleteCategory)
                                        <form method="POST" action="{{ route('inventory.category.delete', $type) }}" onsubmit="return confirm('Xóa loại vật tư này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="rounded bg-rose-600 px-3 py-1.5 text-xs font-bold text-white">Xóa</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @if($canEditCategory)
                            <tr id="category-edit-{{ $type->id }}" class="hidden bg-blue-50/60">
                                <td colspan="6" class="p-4">
                                    <form method="POST" action="{{ route('inventory.category.update', $type) }}" class="grid gap-3 rounded border bg-white p-4 md:grid-cols-3">
                                        @csrf
                                        @method('PATCH')
                                        <label class="text-sm font-semibold">Tên loại vật tư
                                            <input name="name" value="{{ $type->name }}" required class="mt-1 w-full rounded border p-2">
                                        </label>
                                        <label class="text-sm font-semibold md:col-span-2">Mô tả
                                            <textarea name="description" rows="1" class="mt-1 w-full rounded border p-2">{{ $type->description }}</textarea>
                                        </label>
                                        <button class="w-fit rounded bg-blue-600 px-4 py-2 text-white">Lưu</button>
                                    </form>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="6" class="p-4 text-center text-slate-500">Chưa có loại vật tư.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <form method="POST" action="{{ route('inventory.category.store') }}" class="grid gap-3 rounded border bg-white p-4 md:grid-cols-3">
            @csrf
            <h2 class="font-semibold md:col-span-3">Thêm ngành vật tư</h2>
            <label class="text-sm font-semibold">Mã ngành <span class="text-red-600">*</span>
                <input name="code" required placeholder="Ví dụ: HC2A" class="mt-1 w-full rounded border p-2">
            </label>
            <label class="text-sm font-semibold md:col-span-2">Tên ngành vật tư <span class="text-red-600">*</span>
                <input name="name" required placeholder="Ví dụ: Công nghệ thông tin" class="mt-1 w-full rounded border p-2">
            </label>
            <button class="rounded bg-blue-600 px-4 py-2 text-white md:col-span-3">Thêm ngành vật tư</button>
        </form>

        <div class="overflow-x-auto rounded border bg-white p-4">
            <h2 class="mb-3 font-semibold">Danh sách ngành vật tư</h2>
            <table class="w-full min-w-[900px] text-left text-sm">
                <thead class="bg-slate-100">
                    <tr>
                        <th class="p-3">STT</th>
                        <th class="p-3">Mã ngành</th>
                        <th class="p-3">Tên ngành</th>
                        <th class="p-3">Số loại</th>
                        <th class="p-3">Số vật tư</th>
                        <th class="p-3">Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $i => $industry)
                        <tr class="border-t">
                            <td class="p-3">{{ $i + 1 }}</td>
                            <td class="p-3 font-semibold">{{ $industry->code }}</td>
                            <td class="p-3">{{ $industry->name }}</td>
                            <td class="p-3">{{ $industry->children()->count() }}</td>
                            <td class="p-3">{{ $industry->materials_count }}</td>
                            <td class="p-3">
                                <div class="flex flex-wrap gap-2">
                                    <a href="{{ route('inventory.category.show', $industry) }}" class="rounded border border-slate-300 px-3 py-1.5 text-xs font-bold text-slate-700">Chi tiết</a>
                                    @if($canEditCategory)
                                        <button type="button" class="rounded bg-blue-600 px-3 py-1.5 text-xs font-bold text-white" onclick="document.getElementById('category-edit-{{ $industry->id }}').classList.toggle('hidden')">Sửa</button>
                                    @endif
                                    @if($canDeleteCategory)
                                        <form method="POST" action="{{ route('inventory.category.delete', $industry) }}" onsubmit="return confirm('Xóa ngành vật tư này?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="rounded bg-rose-600 px-3 py-1.5 text-xs font-bold text-white">Xóa</button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @if($canEditCategory)
                            <tr id="category-edit-{{ $industry->id }}" class="hidden bg-blue-50/60">
                                <td colspan="6" class="p-4">
                                    <form method="POST" action="{{ route('inventory.category.update', $industry) }}" class="grid gap-3 rounded border bg-white p-4 md:grid-cols-3">
                                        @csrf
                                        @method('PATCH')
                                        <label class="text-sm font-semibold">Mã ngành
                                            <input name="code" value="{{ $industry->code }}" required class="mt-1 w-full rounded border p-2">
                                        </label>
                                        <label class="text-sm font-semibold">Tên ngành
                                            <input name="name" value="{{ $industry->name }}" required class="mt-1 w-full rounded border p-2">
                                        </label>
                                        <label class="text-sm font-semibold">Mô tả
                                            <input name="description" value="{{ $industry->description }}" class="mt-1 w-full rounded border p-2">
                                        </label>
                                        <button class="w-fit rounded bg-blue-600 px-4 py-2 text-white">Lưu</button>
                                    </form>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr><td colspan="6" class="p-4 text-center text-slate-500">Chưa có ngành vật tư.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
</div>
