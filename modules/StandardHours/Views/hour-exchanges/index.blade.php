@extends('layouts.admin')

@section('title', 'Quy đổi giờ chuẩn')
@section('page-title', 'Quy đổi giờ chuẩn')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Giờ chuẩn GV', 'url' => route('standard-hours.hub')],
    ['title' => 'Quy đổi giờ chuẩn']
]" />

<x-page-header title="QUY ĐỔI GIỜ CHUẨN (NCKH ↔ HĐ CM)" :actions="[
    \Modules\StandardHours\Support\HubNavigation::backAction(),
]" />

@php
    $balResearch = $balances['research_hours'] ?? 0;
    $balConversion = $balances['conversion_hours'] ?? 0;
@endphp

<div class="bg-white rounded-lg shadow-sm border p-5 mb-6">
    <form method="GET" action="{{ route('standard-hours.hour-exchanges.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
        <div>
            <label class="block font-medium mb-2" for="filter_year">{{ $periodModeLabel }} <span class="text-red-500">*</span></label>
            <select name="year" id="filter_year" data-placeholder="Chọn {{ mb_strtolower($periodModeLabel) }}" class="w-full" required>
                @foreach($years as $key => $label)
                    <option value="{{ $key }}" {{ (string) $year === (string) $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        @if($isManager)
            <div>
                <label class="block font-medium mb-2" for="filter_unit_id">Khoa / Đơn vị</label>
                <select name="unit_id" id="filter_unit_id" data-placeholder="Tất cả đơn vị" class="w-full">
                    <option value="">Tất cả đơn vị</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}" {{ (int) $unitId === (int) $unit->id ? 'selected' : '' }}>
                            {{ $unit->name }} @if($unit->code)({{ $unit->code }})@endif
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block font-medium mb-2" for="filter_instructor_id">Giảng viên <span class="text-red-500">*</span></label>
                <select name="instructor_id" id="filter_instructor_id" data-placeholder="Chọn giảng viên" data-searchable="1" class="w-full">
                    <option value="">Chọn giảng viên</option>
                    @foreach($instructors as $ins)
                        <option value="{{ $ins->id }}" {{ (int) $instructorId === (int) $ins->id ? 'selected' : '' }}>
                            {{ $ins->name }} ({{ $ins->code }})
                        </option>
                    @endforeach
                </select>
            </div>
        @else
            <div class="md:col-span-2">
                <label class="block font-medium mb-2">Giảng viên</label>
                <div class="form-input bg-gray-50">
                    {{ $instructor?->name ?? auth()->user()->name }}
                    @if($instructor?->code)
                        <span class="text-gray-500">({{ $instructor->code }})</span>
                    @endif
                </div>
                <input type="hidden" name="instructor_id" value="{{ $instructorId }}">
            </div>
        @endif

        <div>
            <button type="submit" class="{{ \Modules\StandardHours\Support\ActionButton::classes('primary') }} w-full justify-center">
                <i class="bi bi-search mr-1"></i> Xem số dư
            </button>
        </div>
    </form>
</div>

@if(!$instructorId || !$balances)
    <div class="bg-white rounded-lg border p-10 text-center text-gray-500">
        <i class="bi bi-arrow-left-right text-4xl text-gray-300 mb-3 block"></i>
        @if($isManager)
            Vui lòng chọn <strong>năm</strong> và <strong>giảng viên</strong> để quy đổi giờ.
        @else
            Chọn <strong>năm</strong> rồi bấm <strong>Xem số dư</strong>.
        @endif
    </div>
@else
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-l-4 border-l-indigo-500 p-5 shadow-sm">
            <p class="text-sm text-gray-600">Giờ NCKH hiện có</p>
            <p class="text-3xl font-bold text-indigo-700 mt-1">{{ number_format($balResearch, 2) }} <span class="text-base font-medium text-gray-500">giờ</span></p>
            <p class="text-xs text-gray-400 mt-2">Từ kê khai NCKH đã duyệt ± quy đổi</p>
        </div>
        <div class="bg-white rounded-xl border border-l-4 border-l-teal-500 p-5 shadow-sm">
            <p class="text-sm text-gray-600">Giờ HĐ chuyên môn hiện có</p>
            <p class="text-3xl font-bold text-teal-700 mt-1">{{ number_format($balConversion, 2) }} <span class="text-base font-medium text-gray-500">giờ</span></p>
            <p class="text-xs text-gray-400 mt-2">Từ kê khai HĐ CM đã duyệt ± quy đổi</p>
        </div>
    </div>

    <div class="bg-amber-50 border border-amber-100 rounded-lg p-4 mb-6 text-sm text-amber-900">
        <i class="bi bi-info-circle mr-1"></i>
        Theo Thông tư nội bộ: <strong>3 giờ hành chính NCKH = 1 giờ chuẩn HĐ CM</strong>.
        Việc bù giờ do cấp quản lý quyết định từng trường hợp và bắt buộc ghi căn cứ.
    </div>

    <div class="grid grid-cols-1 xl:grid-cols-2 gap-6 mb-8">
        {{-- NCKH → HĐ CM --}}
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="px-5 py-4 border-b bg-indigo-50">
                <h3 class="font-semibold text-indigo-900"><i class="bi bi-arrow-right-circle mr-1"></i> NCKH → HĐ CM</h3>
                <p class="text-xs text-indigo-700 mt-1">Dùng giờ NCKH để bù giờ hoạt động chuyên môn</p>
            </div>
            <form method="POST" action="{{ route('standard-hours.hour-exchanges.store') }}" class="p-5 space-y-4"
                  data-exchange-form data-direction="nckh_to_cm" data-available="{{ $balResearch }}" data-rate="{{ $rates['nckh_to_cm'] }}">
                @csrf
                <input type="hidden" name="direction" value="nckh_to_cm">
                <input type="hidden" name="instructor_id" value="{{ $instructorId }}">
                <input type="hidden" name="year" value="{{ $year }}">
                @if($isManager && $unitId)
                    <input type="hidden" name="unit_id" value="{{ $unitId }}">
                @endif

                <div>
                    <label class="block font-medium mb-2">Số giờ NCKH muốn dùng <span class="text-red-500">*</span></label>
                    <input type="number" name="source_hours" min="0.01" step="0.01" max="{{ $balResearch }}"
                           class="form-input w-full exchange-source" placeholder="VD: 30" required>
                    <p class="text-xs text-gray-500 mt-1">Tối đa: {{ number_format($balResearch, 2) }} giờ</p>
                </div>
                <div>
                    <label class="block font-medium mb-2">Số giờ HĐ CM muốn nhận <span class="text-red-500">*</span></label>
                    <input type="number" name="target_hours" min="0.01" step="0.01"
                           class="form-input w-full exchange-target" placeholder="VD: 30" required>
                </div>

                <div class="rounded-lg bg-slate-50 border p-3 text-sm space-y-1">
                    <div class="flex justify-between"><span class="text-gray-600">Hao hụt giờ NCKH</span><strong class="text-red-600 exchange-consume">—</strong></div>
                    <div class="flex justify-between"><span class="text-gray-600">Nhận giờ HĐ CM</span><strong class="text-teal-700 exchange-receive">—</strong></div>
                    <div class="flex justify-between border-t pt-1 mt-1"><span class="text-gray-600">NCKH còn lại (dự kiến)</span><strong class="exchange-remain">—</strong></div>
                </div>

                <div>
                    <label class="block font-medium mb-2">Căn cứ quyết định <span class="text-red-500">*</span></label>
                    <textarea name="notes" rows="2" class="form-textarea w-full" placeholder="Ghi rõ căn cứ, quyết định hoặc ý kiến phê duyệt" required></textarea>
                </div>

                <button type="submit" class="{{ \Modules\StandardHours\Support\ActionButton::classes('primary') }} w-full justify-center"
                        @if($balResearch <= 0) disabled @endif>
                    Xác nhận quy đổi NCKH → HĐ CM
                </button>
            </form>
        </div>

        {{-- HĐ CM → NCKH --}}
        <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
            <div class="px-5 py-4 border-b bg-teal-50">
                <h3 class="font-semibold text-teal-900"><i class="bi bi-arrow-left-circle mr-1"></i> HĐ CM → NCKH</h3>
                <p class="text-xs text-teal-700 mt-1">Dùng giờ HĐ chuyên môn để bù giờ NCKH</p>
            </div>
            <form method="POST" action="{{ route('standard-hours.hour-exchanges.store') }}" class="p-5 space-y-4"
                  data-exchange-form data-direction="cm_to_nckh" data-available="{{ $balConversion }}" data-rate="{{ $rates['cm_to_nckh'] }}">
                @csrf
                <input type="hidden" name="direction" value="cm_to_nckh">
                <input type="hidden" name="instructor_id" value="{{ $instructorId }}">
                <input type="hidden" name="year" value="{{ $year }}">
                @if($isManager && $unitId)
                    <input type="hidden" name="unit_id" value="{{ $unitId }}">
                @endif

                <div>
                    <label class="block font-medium mb-2">Số giờ HĐ CM muốn dùng <span class="text-red-500">*</span></label>
                    <input type="number" name="source_hours" min="0.01" step="0.01" max="{{ $balConversion }}"
                           class="form-input w-full exchange-source" placeholder="VD: 20" required>
                    <p class="text-xs text-gray-500 mt-1">Tối đa: {{ number_format($balConversion, 2) }} giờ</p>
                </div>
                <div>
                    <label class="block font-medium mb-2">Số giờ NCKH muốn nhận <span class="text-red-500">*</span></label>
                    <input type="number" name="target_hours" min="0.01" step="0.01"
                           class="form-input w-full exchange-target" placeholder="VD: 20" required>
                </div>

                <div class="rounded-lg bg-slate-50 border p-3 text-sm space-y-1">
                    <div class="flex justify-between"><span class="text-gray-600">Hao hụt giờ HĐ CM</span><strong class="text-red-600 exchange-consume">—</strong></div>
                    <div class="flex justify-between"><span class="text-gray-600">Nhận giờ NCKH</span><strong class="text-indigo-700 exchange-receive">—</strong></div>
                    <div class="flex justify-between border-t pt-1 mt-1"><span class="text-gray-600">HĐ CM còn lại (dự kiến)</span><strong class="exchange-remain">—</strong></div>
                </div>

                <div>
                    <label class="block font-medium mb-2">Căn cứ quyết định <span class="text-red-500">*</span></label>
                    <textarea name="notes" rows="2" class="form-textarea w-full" placeholder="Ghi rõ căn cứ, quyết định hoặc ý kiến phê duyệt" required></textarea>
                </div>

                <button type="submit" class="{{ \Modules\StandardHours\Support\ActionButton::classes('primary') }} w-full justify-center"
                        @if($balConversion <= 0) disabled @endif>
                    Xác nhận quy đổi HĐ CM → NCKH
                </button>
            </form>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        <div class="px-5 py-3 border-b bg-gray-50">
            <h3 class="font-semibold text-gray-900">Lịch sử quy đổi — {{ $year }}</h3>
        </div>
        @if($exchanges->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-100 text-slate-800 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left">Thời gian</th>
                            <th class="px-4 py-3 text-left">Chiều</th>
                            <th class="px-4 py-3 text-left">Hao hụt</th>
                            <th class="px-4 py-3 text-left">Nhận</th>
                            <th class="px-4 py-3 text-left">Người thực hiện</th>
                            <th class="px-4 py-3 text-left">Ghi chú</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @foreach($exchanges as $ex)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap">{{ $ex->created_at?->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $ex->direction === 'nckh_to_cm' ? 'bg-indigo-100 text-indigo-800' : 'bg-teal-100 text-teal-800' }}">
                                        {{ $ex->direction_text }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-red-600 font-medium">{{ number_format($ex->source_hours, 2) }} giờ</td>
                                <td class="px-4 py-3 text-green-700 font-medium">{{ number_format($ex->target_hours, 2) }} giờ</td>
                                <td class="px-4 py-3">{{ $ex->creator?->name ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $ex->notes ?: '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="p-8 text-center text-gray-500 text-sm">Chưa có lần quy đổi nào trong {{ mb_strtolower($periodModeLabel) }} này.</div>
        @endif
    </div>
@endif

@push('scripts')
<script>
(function () {
    function bindExchangeForms() {
        document.querySelectorAll('[data-exchange-form]').forEach(function (form) {
            if (form.dataset.bound === '1') return;
            form.dataset.bound = '1';

            const rate = parseFloat(form.dataset.rate || '1') || 1;
            const available = parseFloat(form.dataset.available || '0') || 0;
            const source = form.querySelector('.exchange-source');
            const target = form.querySelector('.exchange-target');
            const consumeEl = form.querySelector('.exchange-consume');
            const receiveEl = form.querySelector('.exchange-receive');
            const remainEl = form.querySelector('.exchange-remain');
            if (!source || !target) return;
            let lock = false;

            function refresh(from) {
                if (lock) return;
                lock = true;
                let s = parseFloat(source.value);
                let t = parseFloat(target.value);

                if (from === 'source' && Number.isFinite(s)) {
                    t = Math.round(s * rate * 100) / 100;
                    target.value = t;
                } else if (from === 'target' && Number.isFinite(t)) {
                    s = Math.round((t / rate) * 100) / 100;
                    source.value = s;
                }

                s = parseFloat(source.value);
                t = parseFloat(target.value);

                if (!Number.isFinite(s) || !Number.isFinite(t) || s <= 0 || t <= 0) {
                    if (consumeEl) consumeEl.textContent = '—';
                    if (receiveEl) receiveEl.textContent = '—';
                    if (remainEl) remainEl.textContent = '—';
                    lock = false;
                    return;
                }

                if (consumeEl) consumeEl.textContent = s.toFixed(2) + ' giờ';
                if (receiveEl) receiveEl.textContent = t.toFixed(2) + ' giờ';
                const remain = Math.max(0, Math.round((available - s) * 100) / 100);
                if (remainEl) {
                    remainEl.textContent = remain.toFixed(2) + ' giờ';
                    remainEl.classList.toggle('text-red-600', remain < 0.0001 && s > available);
                }
                lock = false;
            }

            source.addEventListener('input', function () { refresh('source'); });
            target.addEventListener('input', function () { refresh('target'); });

            form.addEventListener('submit', function (e) {
                const s = parseFloat(source.value);
                if (!Number.isFinite(s) || s > available + 0.001) {
                    e.preventDefault();
                    window.PortalPopup.warning('Số giờ nguồn vượt quá số dư hiện có (' + available.toFixed(2) + ' giờ).');
                }
            });
        });
    }

    document.addEventListener('DOMContentLoaded', bindExchangeForms);
    document.addEventListener('turbo:load', bindExchangeForms);
    if (document.readyState !== 'loading') bindExchangeForms();
})();
</script>
@endpush
@endsection
