@foreach($nodes->sortBy('name') as $node)
    @php($isCompany = str_contains(mb_strtolower((string) $node->name, 'UTF-8'), 'đại đội'))
    @php($children = $allUnits->where('parent_id', $node->id))
    <div style="margin-left: {{ $depth * 2 }}rem" class="rounded-xl border {{ $isCompany ? 'border-blue-100 bg-blue-50/70' : ($depth > 0 ? 'border-indigo-100 bg-indigo-50/50' : 'border-slate-200 bg-slate-50') }} px-4 py-3">
        <div class="flex items-center justify-between gap-3"><div class="flex min-w-0 items-center gap-3"><i class="bi {{ $isCompany ? 'bi-diagram-2 text-blue-600' : ($depth > 0 ? 'bi-diagram-3 text-indigo-600' : 'bi-building text-slate-700') }} text-lg"></i><div><div class="font-bold text-slate-900">{{ $node->name }}</div><div class="text-xs text-slate-500">{{ $node->code }}@if($node->parent) · trực thuộc {{ $node->parent->name }} @endif</div></div></div><div class="flex items-start gap-2"><details class="relative"><summary class="cursor-pointer list-none rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-blue-700 hover:bg-blue-50">Sửa</summary><form method="POST" action="{{ route('leave-management.units.update',$node) }}" class="absolute right-0 z-20 mt-2 grid w-72 gap-2 rounded-xl border border-slate-200 bg-white p-3 shadow-xl">@csrf @method('PATCH')<input name="code" value="{{ $node->code }}" required class="rounded-lg border p-2"><input name="name" value="{{ $node->name }}" required class="rounded-lg border p-2"><input name="level" type="number" min="1" value="{{ $node->level }}" class="rounded-lg border p-2"><input type="hidden" name="status" value="{{ $node->status }}"><button class="rounded-lg bg-blue-700 px-3 py-2 text-xs font-bold text-white">Lưu thay đổi</button></form></details><form method="POST" action="{{ route('leave-management.units.delete',$node) }}" onsubmit="return confirm('Xóa đơn vị {{ addslashes($node->name) }}? Chỉ đơn vị không còn dữ liệu liên quan mới được xóa.');">@csrf @method('DELETE')<button type="submit" class="rounded-lg border border-rose-200 bg-white px-3 py-1.5 text-xs font-bold text-rose-600 hover:bg-rose-50">Xóa</button></form></div></div>
    </div>
    @if($children->isNotEmpty())
        @include('leave-management::partials.unit-tree', ['nodes' => $children, 'allUnits' => $allUnits, 'managedClasses' => $managedClasses, 'depth' => $depth + 1])
    @endif
    @if($isCompany && $managedClasses->has($node->id))
        @foreach($managedClasses->get($node->id) as $class)
            <div style="margin-left: {{ ($depth + 1) * 2 }}rem" class="flex items-center gap-3 rounded-xl border border-emerald-100 bg-emerald-50/70 px-4 py-2.5"><i class="bi bi-people text-emerald-600"></i><div><div class="font-semibold text-slate-800">{{ $class->name }}</div><div class="text-xs text-slate-500">Lớp thuộc {{ $node->name }}</div></div></div>
        @endforeach
    @endif
@endforeach
