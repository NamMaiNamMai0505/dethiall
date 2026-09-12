@php
    $canEditCategory = auth()->user()?->isSuperAdmin() || \App\Support\PermissionCheck::can(auth()->user(), 'inventory.categories.edit');
    $canDeleteCategory = auth()->user()?->isSuperAdmin() || \App\Support\PermissionCheck::can(auth()->user(), 'inventory.categories.delete');
    $industryCount = ($categories ?? collect())->count();
    $typeCount = ($categories ?? collect())->sum(fn ($industry) => $industry->children->count());
    $materialCount = ($categories ?? collect())->sum(fn ($industry) => $industry->children->sum(fn ($type) => $type->materials->count()));
@endphp

<div class="space-y-5">
    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-blue-100 bg-gradient-to-r from-blue-50 to-sky-50 px-5 py-4 text-slate-900">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-xl font-extrabold">Danh mục vật tư</h2>
                    <p class="mt-1 text-sm font-semibold text-slate-600">Quản lý theo cây Ngành vật tư, Loại vật tư và Vật tư.</p>
                </div>
                <a href="{{ route('inventory.materials') }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-blue-700">
                    <i class="bi bi-table"></i> Danh sách vật tư
                </a>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-3">
                <div class="rounded-xl border border-blue-100 bg-white p-3 shadow-sm"><p class="text-xs font-bold text-blue-700">Ngành</p><p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $industryCount }}</p></div>
                <div class="rounded-xl border border-indigo-100 bg-white p-3 shadow-sm"><p class="text-xs font-bold text-indigo-700">Loại</p><p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $typeCount }}</p></div>
                <div class="rounded-xl border border-emerald-100 bg-white p-3 shadow-sm"><p class="text-xs font-bold text-emerald-700">Vật tư</p><p class="mt-1 text-2xl font-extrabold text-slate-900">{{ $materialCount }}</p></div>
            </div>
        </div>

        <div class="grid gap-4 p-5 xl:grid-cols-3">
            <form method="POST" action="{{ route('inventory.import') }}" enctype="multipart/form-data" class="rounded-xl border border-emerald-100 bg-emerald-50/50 p-4 xl:col-span-3">
                @csrf
                <input type="hidden" name="update_type" value="IN">
                <input type="hidden" name="reason" value="Import cây danh mục vật tư">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-600 text-white"><i class="bi bi-file-earmark-arrow-up"></i></span>
                        <h3 class="font-bold text-slate-900">Import ngành / loại / vật tư</h3>
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <a href="{{ route('inventory.import.template') }}" class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-3 py-2 text-xs font-bold text-white shadow-sm hover:bg-emerald-700"><i class="bi bi-file-earmark-excel"></i> Mẫu Excel</a>
                        <a href="{{ route('inventory.import.template.word') }}" class="inline-flex items-center gap-2 rounded-lg bg-blue-600 px-3 py-2 text-xs font-bold text-white shadow-sm hover:bg-blue-700"><i class="bi bi-file-earmark-word"></i> Mẫu DOCX</a>
                    </div>
                </div>
                <div class="grid gap-3 lg:grid-cols-4">
                    <label class="text-sm font-semibold text-slate-700 lg:col-span-3">File import cây danh mục <span class="text-red-600">*</span>
                        <input name="file" type="file" accept=".xlsx,.xls,.csv,.txt,.docx" required class="mt-1 w-full rounded-lg border bg-white p-2.5">
                        <span class="mt-1 block text-xs font-medium text-slate-500">Có thể import riêng Ngành, Ngành + Loại, hoặc đầy đủ Ngành + Loại + Vật tư.</span>
                    </label>
                    <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 font-bold text-white lg:self-end">
                        <i class="bi bi-upload"></i> Import cây
                    </button>
                </div>
            </form>

            <form method="POST" action="{{ route('inventory.category.store') }}" class="rounded-xl border border-blue-100 bg-blue-50/50 p-4">
                @csrf
                <div class="mb-3 flex items-center gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-600 text-white"><i class="bi bi-diagram-3"></i></span>
                    <h3 class="font-bold text-slate-900">Thêm ngành vật tư</h3>
                </div>
                <div class="grid gap-3 md:grid-cols-3">
                    <label class="text-sm font-semibold text-slate-700">Mã ngành <span class="text-red-600">*</span>
                        <input name="code" required placeholder="Ví dụ: HC2A" class="mt-1 w-full rounded-lg border bg-white p-2.5">
                    </label>
                    <label class="text-sm font-semibold text-slate-700 md:col-span-2">Tên ngành vật tư <span class="text-red-600">*</span>
                        <input name="name" required placeholder="Nhập tên ngành" class="mt-1 w-full rounded-lg border bg-white p-2.5">
                    </label>
                    <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-2.5 font-bold text-white md:col-span-3">
                        <i class="bi bi-plus-lg"></i> Thêm ngành
                    </button>
                </div>
            </form>

            <form method="POST" action="{{ route('inventory.category.store') }}" class="rounded-xl border border-indigo-100 bg-indigo-50/50 p-4">
                @csrf
                <div class="mb-3 flex items-center gap-2">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-white"><i class="bi bi-tags"></i></span>
                    <h3 class="font-bold text-slate-900">Thêm loại vật tư</h3>
                </div>
                <div class="grid gap-3 md:grid-cols-3">
                    <label class="text-sm font-semibold text-slate-700">Ngành vật tư <span class="text-red-600">*</span>
                        <select name="parent_id" required class="mt-1 w-full rounded-lg border bg-white p-2.5">
                            <option value="">Chọn ngành</option>
                            @foreach($parents as $parent)
                                <option value="{{ $parent->id }}">{{ $parent->code }} — {{ $parent->name }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="text-sm font-semibold text-slate-700 md:col-span-2">Tên loại vật tư <span class="text-red-600">*</span>
                        <input name="name" required placeholder="Nhập tên loại" class="mt-1 w-full rounded-lg border bg-white p-2.5">
                    </label>
                    <button class="inline-flex items-center justify-center gap-2 rounded-lg bg-indigo-600 px-4 py-2.5 font-bold text-white md:col-span-3">
                        <i class="bi bi-plus-lg"></i> Thêm loại
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h3 class="text-lg font-extrabold text-slate-900">Cây danh mục</h3>
                <p class="mt-1 text-sm text-slate-500">Bấm vào từng ngành, loại hoặc vật tư để xem chi tiết.</p>
            </div>
        </div>

        <div class="space-y-4">
            @forelse($categories as $industry)
                <details class="group overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm" open>
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 bg-slate-50 px-4 py-3 hover:bg-blue-50">
                        <div class="flex min-w-0 items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-600 text-white"><i class="bi bi-diagram-3"></i></span>
                            <div class="min-w-0">
                                <p class="truncate font-extrabold text-slate-900">{{ $industry->code }} — {{ $industry->name }}</p>
                                <p class="text-xs font-semibold text-slate-500">Ngành vật tư</p>
                            </div>
                        </div>
                        <span class="shrink-0 rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">{{ $industry->children_count }} loại</span>
                    </summary>
                    <div class="border-t border-slate-100 p-4">
                        <div class="mb-3 flex flex-wrap gap-2">
                            <a href="{{ route('inventory.category.show', $industry) }}" class="inline-flex items-center gap-1 rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700"><i class="bi bi-eye"></i> Xem ngành</a>
                            @if($canEditCategory)
                                <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700" onclick="document.getElementById('category-edit-{{ $industry->id }}').classList.toggle('hidden')"><i class="bi bi-pencil-square"></i> Sửa</button>
                            @endif
                            @if($canDeleteCategory)
                                <form method="POST" action="{{ route('inventory.category.delete', $industry) }}" onsubmit="return confirm('Xóa ngành vật tư này?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="inline-flex items-center gap-1 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-bold text-white"><i class="bi bi-trash3"></i> Xóa</button>
                                </form>
                            @endif
                        </div>
                        @if($canEditCategory)
                            <form id="category-edit-{{ $industry->id }}" method="POST" action="{{ route('inventory.category.update', $industry) }}" class="mb-3 hidden grid gap-3 rounded-xl border border-blue-100 bg-blue-50/60 p-3 md:grid-cols-4">
                                @csrf
                                @method('PATCH')
                                <input name="code" value="{{ $industry->code }}" required class="rounded-lg border bg-white p-2.5">
                                <input name="name" value="{{ $industry->name }}" required class="rounded-lg border bg-white p-2.5 md:col-span-2">
                                <input name="description" value="{{ $industry->description }}" placeholder="Mô tả" class="rounded-lg border bg-white p-2.5">
                                <button class="w-fit rounded-lg bg-blue-600 px-4 py-2 font-bold text-white">Lưu ngành</button>
                            </form>
                        @endif

                        <div class="space-y-3">
                            @forelse($industry->children as $type)
                                <details class="overflow-hidden rounded-xl border border-indigo-100 bg-white" open>
                                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 bg-indigo-50/70 px-4 py-3 hover:bg-indigo-50">
                                        <div class="flex min-w-0 items-center gap-3">
                                            <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-indigo-600 text-white"><i class="bi bi-tags"></i></span>
                                            <div class="min-w-0">
                                                <p class="truncate font-bold text-slate-900">{{ $type->code }} — {{ $type->name }}</p>
                                                <p class="text-xs font-semibold text-slate-500">Loại vật tư thuộc {{ $industry->code }}</p>
                                            </div>
                                        </div>
                                        <span class="shrink-0 rounded-full bg-indigo-100 px-3 py-1 text-xs font-bold text-indigo-700">{{ $type->materials_count }} vật tư</span>
                                    </summary>
                                    <div class="border-t border-indigo-100 p-3">
                                        <div class="mb-3 flex flex-wrap gap-2">
                                            <a href="{{ route('inventory.category.show', $type) }}" class="inline-flex items-center gap-1 rounded-lg border border-indigo-200 bg-indigo-50 px-3 py-1.5 text-xs font-bold text-indigo-700"><i class="bi bi-eye"></i> Xem loại</a>
                                            @if($canEditCategory)
                                                <button type="button" class="inline-flex items-center gap-1 rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-bold text-slate-700" onclick="document.getElementById('category-edit-{{ $type->id }}').classList.toggle('hidden')"><i class="bi bi-pencil-square"></i> Sửa</button>
                                            @endif
                                            @if($canDeleteCategory)
                                                <form method="POST" action="{{ route('inventory.category.delete', $type) }}" onsubmit="return confirm('Xóa loại vật tư này?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button class="inline-flex items-center gap-1 rounded-lg bg-rose-600 px-3 py-1.5 text-xs font-bold text-white"><i class="bi bi-trash3"></i> Xóa</button>
                                                </form>
                                            @endif
                                        </div>
                                        @if($canEditCategory)
                                            <form id="category-edit-{{ $type->id }}" method="POST" action="{{ route('inventory.category.update', $type) }}" class="mb-3 hidden grid gap-3 rounded-xl border border-indigo-100 bg-indigo-50/60 p-3 md:grid-cols-3">
                                                @csrf
                                                @method('PATCH')
                                                <input name="name" value="{{ $type->name }}" required class="rounded-lg border bg-white p-2.5">
                                                <input name="description" value="{{ $type->description }}" placeholder="Mô tả" class="rounded-lg border bg-white p-2.5">
                                                <button class="w-fit rounded-lg bg-blue-600 px-4 py-2 font-bold text-white">Lưu loại</button>
                                            </form>
                                        @endif

                                        <div class="grid gap-2 sm:grid-cols-2 xl:grid-cols-3">
                                            @forelse($type->materials as $material)
                                                <a href="{{ route('inventory.materials.show', $material) }}" class="flex items-center justify-between gap-3 rounded-lg border border-slate-100 bg-slate-50 px-3 py-2 transition hover:border-blue-200 hover:bg-blue-50">
                                                    <span class="min-w-0">
                                                        <span class="block truncate font-bold text-slate-800"><i class="bi bi-box-seam mr-1 text-slate-500"></i>{{ $material->code }}</span>
                                                        <span class="block truncate text-sm text-slate-600">{{ $material->name }}</span>
                                                    </span>
                                                    <span class="shrink-0 rounded-full bg-white px-2.5 py-1 text-xs font-bold text-slate-600">{{ $material->quantity }} {{ $material->unit }}</span>
                                                </a>
                                            @empty
                                                <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-500 sm:col-span-2 xl:col-span-3">Loại này chưa có vật tư.</div>
                                            @endforelse
                                        </div>
                                    </div>
                                </details>
                            @empty
                                <div class="rounded-lg border border-dashed border-slate-200 bg-slate-50 p-4 text-sm text-slate-500">Ngành này chưa có loại vật tư.</div>
                            @endforelse
                        </div>
                    </div>
                </details>
            @empty
                <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-8 text-center text-slate-500">Chưa có danh mục vật tư.</div>
            @endforelse
        </div>
    </section>
</div>
