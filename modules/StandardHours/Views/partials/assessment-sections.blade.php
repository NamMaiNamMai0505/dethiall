@php
    $currentStatus = request('status');
    $baseQuery = request()->except(['status', 'page']);
    $pendingCount = (int) ($assessmentCounts[\Modules\StandardHours\Models\ConversionRecord::STATUS_SUBMITTED] ?? 0);
    $assessedCount = (int) ($assessmentCounts[\Modules\StandardHours\Models\ConversionRecord::STATUS_APPROVED] ?? 0);
    $sections = [
        [
            'status' => null,
            'label' => 'Tất cả hồ sơ',
            'description' => 'Gồm hồ sơ nháp, cần bổ sung và đã gửi',
            'icon' => 'bi-collection',
            'active' => blank($currentStatus),
            'active_classes' => 'border-slate-700 bg-slate-800 text-white shadow-lg shadow-slate-200',
            'idle_classes' => 'border-slate-200 bg-white text-slate-700 hover:border-slate-400 hover:bg-slate-50',
        ],
        [
            'status' => \Modules\StandardHours\Models\ConversionRecord::STATUS_SUBMITTED,
            'label' => 'Chờ thẩm định',
            'description' => 'Hồ sơ đã gửi, đang chờ cơ quan phụ trách xử lý',
            'icon' => 'bi-hourglass-split',
            'count' => $pendingCount,
            'active' => $currentStatus === \Modules\StandardHours\Models\ConversionRecord::STATUS_SUBMITTED,
            'active_classes' => 'border-amber-500 bg-amber-500 text-white shadow-lg shadow-amber-200',
            'idle_classes' => 'border-amber-200 bg-amber-50 text-amber-900 hover:border-amber-400 hover:bg-amber-100',
        ],
        [
            'status' => \Modules\StandardHours\Models\ConversionRecord::STATUS_APPROVED,
            'label' => 'Đã thẩm định',
            'description' => 'Hồ sơ đã được xác nhận và chốt kết quả',
            'icon' => 'bi-patch-check',
            'count' => $assessedCount,
            'active' => $currentStatus === \Modules\StandardHours\Models\ConversionRecord::STATUS_APPROVED,
            'active_classes' => 'border-emerald-600 bg-emerald-600 text-white shadow-lg shadow-emerald-200',
            'idle_classes' => 'border-emerald-200 bg-emerald-50 text-emerald-900 hover:border-emerald-400 hover:bg-emerald-100',
        ],
    ];
@endphp

<section class="mb-5" aria-label="Phân loại hồ sơ thẩm định">
    <div class="mb-2 flex flex-wrap items-end justify-between gap-2">
        <div>
            <h2 class="text-sm font-bold uppercase tracking-wide text-slate-800">Tiến độ thẩm định</h2>
            <p class="mt-0.5 text-xs text-slate-500">Chọn một nhóm để lọc nhanh danh sách hồ sơ.</p>
        </div>
        @if(in_array($currentStatus, [
            \Modules\StandardHours\Models\ConversionRecord::STATUS_DRAFT,
            \Modules\StandardHours\Models\ConversionRecord::STATUS_REJECTED,
        ], true))
            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">
                Đang xem: {{ $statuses[$currentStatus] ?? $currentStatus }}
            </span>
        @endif
    </div>

    <div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
        @foreach($sections as $section)
            @php
                $query = $baseQuery;
                if ($section['status']) {
                    $query['status'] = $section['status'];
                }
            @endphp
            <a
                href="{{ route($routeName, $query) }}"
                class="group flex min-h-20 items-center gap-3 rounded-2xl border px-4 py-3 transition duration-200 {{ $section['active'] ? $section['active_classes'] : $section['idle_classes'] }}"
                @if($section['active']) aria-current="page" @endif
            >
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $section['active'] ? 'bg-white/20' : 'bg-white shadow-sm' }}">
                    <i class="bi {{ $section['icon'] }} text-lg"></i>
                </span>
                <span class="min-w-0 flex-1">
                    <span class="flex items-center justify-between gap-3">
                        <span class="font-bold">{{ $section['label'] }}</span>
                        @isset($section['count'])
                            <span class="inline-flex min-w-8 items-center justify-center rounded-full px-2 py-0.5 text-sm font-extrabold {{ $section['active'] ? 'bg-white/20 text-white' : 'bg-white text-current shadow-sm' }}">
                                {{ number_format($section['count']) }}
                            </span>
                        @endisset
                    </span>
                    <span class="mt-0.5 block text-xs leading-5 {{ $section['active'] ? 'text-white/80' : 'opacity-70' }}">
                        {{ $section['description'] }}
                    </span>
                </span>
            </a>
        @endforeach
    </div>
</section>
