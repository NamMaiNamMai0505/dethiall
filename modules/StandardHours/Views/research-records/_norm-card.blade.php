@php
    $interactive = (bool) ($interactive ?? false);
    $norm = $norm ?? null;
    $hasNorm = isset($norm['research_norm_hours']) && $norm['research_norm_hours'] !== null;
@endphp

<div id="research-norm-card"
     class="mb-6 overflow-hidden rounded-2xl border border-violet-200 bg-gradient-to-br from-violet-50 via-white to-indigo-50 shadow-sm"
     data-interactive="{{ $interactive ? '1' : '0' }}">
    <div class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex min-w-0 items-start gap-3">
            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-violet-600 text-xl text-white shadow-sm">
                <i class="bi bi-bullseye"></i>
            </span>
            <div class="min-w-0">
                <p class="text-xs font-bold uppercase tracking-[0.12em] text-violet-700">Định mức NCKH phải thực hiện</p>
                <h3 id="research-norm-object" class="mt-1 font-semibold text-slate-900">
                    @if($hasNorm)
                        {{ $norm['object_type_code'] }} — {{ $norm['object_type_name'] }}
                    @elseif($interactive)
                        Chọn giảng viên để xem định mức
                    @else
                        Chưa gắn đối tượng giờ chuẩn
                    @endif
                </h3>
                <p id="research-norm-period" class="mt-1 text-sm text-slate-500">
                    @if($hasNorm)
                        Áp dụng theo {{ mb_strtolower($norm['period_mode_label']) }} {{ $norm['period_label'] }}
                    @else
                        Định mức lấy từ Đối tượng đang gắn với hồ sơ giảng viên.
                    @endif
                </p>
            </div>
        </div>
        <div class="shrink-0 rounded-xl border border-violet-100 bg-white px-5 py-3 text-left shadow-sm sm:text-right">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Số giờ quy định</p>
            <p class="mt-1 text-3xl font-black text-violet-700">
                <span id="research-norm-hours">{{ $hasNorm ? number_format((float) $norm['research_norm_hours'], 2, ',', '.') : '—' }}</span>
                <span class="text-base font-bold">giờ</span>
            </p>
        </div>
    </div>
    <div id="research-norm-warning" class="{{ $hasNorm ? 'hidden ' : '' }}border-t border-amber-200 bg-amber-50 px-5 py-3 text-sm text-amber-800">
        <i class="bi bi-exclamation-triangle mr-1"></i>
        Giảng viên chưa được gắn Đối tượng hoặc Đối tượng chưa có định mức NCKH. Vui lòng liên hệ quản lý Giờ chuẩn GV.
    </div>
</div>
