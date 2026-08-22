@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-[1800px] space-y-5 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-wrap items-start justify-between gap-4">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-600">Database Management Hub</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">ERD Schema Map</h1>
            <p class="mt-1 text-sm text-slate-500">Kéo các node để sắp xếp sơ đồ. Đường nối là foreign key thật đang tồn tại trong database.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('database-management.index') }}" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Schema Catalog</a>
            <span class="rounded-xl bg-amber-100 px-4 py-2 text-sm font-semibold text-amber-800">READ ONLY</span>
        </div>
    </div>

    <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-3 text-xs text-slate-500"><span class="inline-block h-2.5 w-8 rounded bg-violet-500"></span>Foreign key hiện có <span class="ml-3">{{ count($relations) }} quan hệ</span></div>
            <input id="schema-search" placeholder="Lọc bảng..." class="w-64 rounded-xl border border-slate-300 px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-violet-500">
        </div>
        <div id="erd-canvas" class="relative h-[72vh] min-h-[560px] overflow-auto rounded-xl border border-dashed border-slate-300 bg-slate-50">
            <svg id="erd-lines" class="pointer-events-none absolute left-0 top-0 h-full w-full"></svg>
            @foreach($catalog as $index => $table)
                <article class="erd-node absolute w-64 cursor-move rounded-xl border border-slate-300 bg-white shadow-md" data-table="{{ $table['name'] }}" data-index="{{ $index }}" style="left:{{ 24 + (($index % 5) * 290) }}px;top:{{ 24 + (floor($index / 5) * 230) }}px">
                    <header class="rounded-t-xl bg-slate-800 px-3 py-2 text-sm font-bold text-white">{{ $table['name'] }}</header>
                    <div class="max-h-40 overflow-auto p-2">
                        @foreach($table['columns'] as $column)
                            <div class="flex items-center justify-between gap-2 border-b border-slate-100 py-1 text-xs last:border-0" data-column="{{ $column['name'] }}"><span class="font-medium text-slate-700">{{ $column['name'] }}</span><span class="text-slate-400">{{ $column['type'] }}</span></div>
                        @endforeach
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</div>
<script>
(() => {
    const canvas = document.getElementById('erd-canvas');
    const svg = document.getElementById('erd-lines');
    const relations = @json($relations);
    const nodes = [...document.querySelectorAll('.erd-node')];
    const search = document.getElementById('schema-search');
    let drag = null;

    function point(node, side) {
        const canvasRect = canvas.getBoundingClientRect();
        const rect = node.getBoundingClientRect();
        return { x: rect.left - canvasRect.left + canvas.scrollLeft + (side === 'right' ? rect.width : 0), y: rect.top - canvasRect.top + canvas.scrollTop + rect.height / 2 };
    }
    function draw() {
        svg.innerHTML = '';
        svg.setAttribute('width', canvas.scrollWidth); svg.setAttribute('height', canvas.scrollHeight);
        relations.forEach((relation) => {
            const from = nodes.find((node) => node.dataset.table === relation.from_table);
            const to = nodes.find((node) => node.dataset.table === relation.to_table);
            if (!from || !to || from.hidden || to.hidden) return;
            const a = point(from, 'right'), b = point(to, 'left');
            const bend = Math.max(45, Math.abs(b.x - a.x) / 2);
            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
            path.setAttribute('d', `M ${a.x} ${a.y} C ${a.x + bend} ${a.y}, ${b.x - bend} ${b.y}, ${b.x} ${b.y}`);
            path.setAttribute('fill', 'none'); path.setAttribute('stroke', '#8b5cf6'); path.setAttribute('stroke-width', '2'); path.setAttribute('stroke-dasharray', '5 4');
            svg.appendChild(path);
        });
    }
    nodes.forEach((node) => {
        node.addEventListener('pointerdown', (event) => { drag = { node, x: event.clientX, y: event.clientY, left: node.offsetLeft, top: node.offsetTop }; node.setPointerCapture(event.pointerId); });
        node.addEventListener('pointermove', (event) => { if (!drag || drag.node !== node) return; node.style.left = `${Math.max(8, drag.left + event.clientX - drag.x)}px`; node.style.top = `${Math.max(8, drag.top + event.clientY - drag.y)}px`; draw(); });
        node.addEventListener('pointerup', () => { drag = null; });
    });
    search.addEventListener('input', () => { const value = search.value.toLowerCase().trim(); nodes.forEach((node) => { node.hidden = value !== '' && !node.dataset.table.toLowerCase().includes(value); }); draw(); });
    window.addEventListener('resize', draw); canvas.addEventListener('scroll', draw); draw();
})();
</script>
@endsection
