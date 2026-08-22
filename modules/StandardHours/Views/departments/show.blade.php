@extends('layouts.admin')

@section('title', $department->name)
@section('page-title', 'Chi tiết bộ môn')

@section('content')
    <x-breadcrumb :items="[
        ['title' => 'Trang chủ'],
        ['title' => 'Giờ chuẩn GV', 'url' => route('standard-hours.hub')],
        ['title' => 'Bộ môn', 'url' => route('standard-hours.departments.index')],
        ['title' => $department->name],
    ]" />

    <x-page-header
        title="{{ $department->name }}"
        :subtitle="($department->code ?? '').' · Khoa: '.($department->unit?->name ?? '—')"
        :actions="[
            [
                'url' => route('standard-hours.departments.index'),
                'label' => 'Danh sách',
                'icon' => 'arrow-left',
                'color' => 'gray',
            ],
            [
                'url' => route('standard-hours.department-overtime.show', $department),
                'label' => 'Vượt định mức',
                'icon' => 'graph-up-arrow',
                'color' => 'amber',
            ],
            [
                'url' => route('standard-hours.departments.edit', $department),
                'label' => 'Chỉnh sửa',
                'icon' => 'pencil',
                'color' => 'blue',
            ],
        ]"
    />

    {{-- Info strip --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="bg-white rounded-xl border shadow-sm px-4 py-3 border-l-4 border-blue-500">
            <p class="text-[11px] uppercase tracking-wide text-slate-400 font-semibold">Mã BM</p>
            <p class="font-mono font-semibold text-slate-900 mt-0.5">{{ $department->code }}</p>
        </div>
        <div class="bg-white rounded-xl border shadow-sm px-4 py-3 border-l-4 border-indigo-500">
            <p class="text-[11px] uppercase tracking-wide text-slate-400 font-semibold">Khoa</p>
            <p class="font-semibold text-slate-900 mt-0.5 truncate" title="{{ $department->unit?->name }}">
                {{ $department->unit?->name ?? '—' }}
            </p>
        </div>
        <div class="bg-white rounded-xl border shadow-sm px-4 py-3 border-l-4 border-teal-500">
            <p class="text-[11px] uppercase tracking-wide text-slate-400 font-semibold">Giảng viên</p>
            <p class="text-xl font-bold text-teal-800 mt-0.5 tabular-nums">{{ $department->instructors->count() }}</p>
        </div>
        <div class="bg-white rounded-xl border shadow-sm px-4 py-3 border-l-4 {{ $department->is_active ? 'border-emerald-500' : 'border-slate-300' }}">
            <p class="text-[11px] uppercase tracking-wide text-slate-400 font-semibold">Trạng thái</p>
            <p class="font-semibold mt-0.5 {{ $department->is_active ? 'text-emerald-700' : 'text-slate-500' }}">
                {{ $department->is_active ? 'Đang sử dụng' : 'Ngừng sử dụng' }}
            </p>
        </div>
    </div>

    @if($department->description)
        <div class="mb-6 bg-slate-50 border border-slate-100 rounded-xl px-4 py-3 text-sm text-slate-600">
            {{ $department->description }}
        </div>
    @endif

    <div class="grid lg:grid-cols-5 gap-5">
        {{-- Gán GV --}}
        <div class="lg:col-span-3 bg-white rounded-xl border shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex flex-wrap items-start justify-between gap-2">
                <div>
                    <h2 class="font-semibold text-slate-900 flex items-center gap-2">
                        <i class="bi bi-people text-blue-600"></i> Gán giảng viên
                    </h2>
                    <p class="text-xs text-slate-500 mt-1 leading-relaxed">
                        Chỉ GV cùng khoa. Dùng cho <strong>vượt định mức</strong> — không đổi lọc báo cáo theo khoa.
                    </p>
                </div>
                <span class="text-xs text-slate-400 bg-slate-50 px-2 py-1 rounded-lg">
                    {{ $candidates->count() }} GV trong khoa
                </span>
            </div>

            <form method="POST" action="{{ route('standard-hours.departments.sync-instructors', $department) }}" id="dept-assign-form">
                @csrf
                <div class="px-4 py-2 border-b border-slate-50 flex flex-wrap gap-2 items-center bg-slate-50/50">
                    <input type="search" id="dept-gv-filter" placeholder="Lọc tên / mã GV..."
                           class="flex-1 min-w-[10rem] text-sm border border-slate-200 rounded-lg px-3 py-1.5 focus:ring-2 focus:ring-blue-400 focus:border-blue-400"
                           autocomplete="off">
                    <button type="button" id="dept-select-all"
                            class="text-xs font-semibold text-blue-700 hover:bg-blue-50 px-2.5 py-1.5 rounded-lg transition">
                        Chọn tất cả
                    </button>
                    <button type="button" id="dept-select-none"
                            class="text-xs font-semibold text-slate-600 hover:bg-slate-100 px-2.5 py-1.5 rounded-lg transition">
                        Bỏ chọn
                    </button>
                </div>

                <div class="max-h-[22rem] overflow-y-auto divide-y divide-slate-100" id="dept-gv-list">
                    @forelse($candidates as $gv)
                        @php
                            $inThis = (int) $gv->department_id === (int) $department->id;
                            $inOther = $gv->department_id && ! $inThis;
                        @endphp
                        <label class="dept-gv-row flex items-center gap-3 px-4 py-2.5 hover:bg-blue-50/40 cursor-pointer transition
                                      {{ $inThis ? 'bg-teal-50/30' : '' }}"
                               data-search="{{ strtolower($gv->name.' '.$gv->code) }}">
                            <input type="checkbox" name="instructor_ids[]" value="{{ $gv->id }}"
                                   class="dept-gv-cb rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                   @checked($inThis)>
                            <span class="flex-1 min-w-0">
                                <span class="font-medium text-slate-900 block truncate">{{ $gv->name }}</span>
                                <span class="text-[11px] text-slate-400 font-mono">{{ $gv->code }}</span>
                            </span>
                            @if($inOther)
                                <span class="shrink-0 text-[10px] font-semibold text-amber-800 bg-amber-50 border border-amber-100 px-2 py-0.5 rounded-full">
                                    BM khác
                                </span>
                            @elseif($inThis)
                                <span class="shrink-0 text-[10px] font-semibold text-teal-800 bg-teal-50 border border-teal-100 px-2 py-0.5 rounded-full">
                                    Đang gán
                                </span>
                            @endif
                        </label>
                    @empty
                        <div class="px-4 py-12 text-center text-sm text-slate-500">
                            <i class="bi bi-person-x text-2xl text-slate-300 block mb-2"></i>
                            Không có giảng viên thuộc khoa này.
                        </div>
                    @endforelse
                </div>

                @can('standard-hours.departments.manage')
                    <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/80 flex flex-wrap justify-between items-center gap-2">
                        <p class="text-xs text-slate-500">
                            <span id="dept-selected-count" class="font-semibold text-slate-700">0</span> GV được chọn
                        </p>
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700
                                       text-white text-sm font-semibold shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400">
                            <i class="bi bi-check2-circle"></i>
                            Lưu danh sách GV
                        </button>
                    </div>
                @endcan
            </form>
        </div>

        {{-- Danh sách đã gán --}}
        <div class="lg:col-span-2 bg-white rounded-xl border shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100">
                <h2 class="font-semibold text-slate-900 flex items-center gap-2">
                    <i class="bi bi-person-check text-teal-600"></i>
                    Đang thuộc bộ môn
                    <span class="ml-auto text-xs font-semibold text-teal-800 bg-teal-50 px-2 py-0.5 rounded-full">
                        {{ $department->instructors->count() }}
                    </span>
                </h2>
            </div>
            <ul class="divide-y divide-slate-100 max-h-[28rem] overflow-y-auto">
                @forelse($department->instructors as $gv)
                    <li class="px-5 py-3 flex items-center gap-3">
                        <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-teal-50 text-teal-700 text-sm font-bold">
                            {{ mb_strtoupper(mb_substr($gv->name, 0, 1)) }}
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="font-medium text-slate-900 block truncate">{{ $gv->name }}</span>
                            <span class="text-[11px] text-slate-400 font-mono">{{ $gv->code }}</span>
                        </span>
                    </li>
                @empty
                    <li class="px-5 py-14 text-center text-sm text-slate-500">
                        <i class="bi bi-inbox text-2xl text-slate-300 block mb-2"></i>
                        Chưa gán giảng viên nào.
                    </li>
                @endforelse
            </ul>
            @if($department->instructors->isNotEmpty())
                <div class="px-5 py-3 border-t border-slate-100 bg-slate-50/50">
                    <a href="{{ route('standard-hours.department-overtime.show', $department) }}"
                       class="inline-flex w-full items-center justify-center gap-2 px-4 py-2.5 rounded-lg
                              bg-amber-500 hover:bg-amber-600 text-white text-sm font-semibold shadow-sm transition">
                        <i class="bi bi-graph-up-arrow"></i>
                        Tính vượt định mức bộ môn
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    function countSelected() {
        const n = document.querySelectorAll('.dept-gv-cb:checked').length;
        const el = document.getElementById('dept-selected-count');
        if (el) el.textContent = String(n);
    }
    document.getElementById('dept-gv-filter')?.addEventListener('input', function () {
        const q = (this.value || '').trim().toLowerCase();
        document.querySelectorAll('.dept-gv-row').forEach(function (row) {
            const hay = row.getAttribute('data-search') || '';
            row.classList.toggle('hidden', q && hay.indexOf(q) === -1);
        });
    });
    document.getElementById('dept-select-all')?.addEventListener('click', function () {
        document.querySelectorAll('.dept-gv-row:not(.hidden) .dept-gv-cb').forEach(function (cb) {
            cb.checked = true;
        });
        countSelected();
    });
    document.getElementById('dept-select-none')?.addEventListener('click', function () {
        document.querySelectorAll('.dept-gv-cb').forEach(function (cb) { cb.checked = false; });
        countSelected();
    });
    document.querySelectorAll('.dept-gv-cb').forEach(function (cb) {
        cb.addEventListener('change', countSelected);
    });
    countSelected();
})();
</script>
@endpush
