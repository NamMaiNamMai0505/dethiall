@extends('layouts.admin')

@section('title', 'Kỳ tính Giờ chuẩn GV')
@section('page-title', 'Kỳ tính Giờ chuẩn GV')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Giờ chuẩn GV', 'url' => route('standard-hours.hub')],
    ['title' => 'Kỳ tính Năm / Năm học']
]" />

<x-page-header title="CẤU HÌNH KỲ TÍNH GIỜ CHUẨN GV" :actions="[[
    'url' => route('standard-hours.hub'), 'label' => 'Quay lại', 'icon' => 'arrow-left', 'color' => 'gray'
]]" />

<div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-950">
    <div class="flex gap-3">
        <i class="bi bi-shield-check mt-0.5 text-lg text-blue-700"></i>
        <div>
            <p class="font-semibold">Công tắc này chỉ áp dụng cho module Giờ chuẩn GV.</p>
            <p class="mt-1 text-blue-800">Năm học của Lịch huấn luyện, LMS và Quản lý điểm vẫn dùng danh mục năm học chung, không bị thay đổi.</p>
        </div>
    </div>
</div>

<form action="{{ route('standard-hours.settings.period-mode.update') }}"
      method="POST"
      data-turbo="true"
      data-turbo-action="replace"
      data-period-mode-form>
    @csrf
    @method('PUT')

    <div class="grid grid-cols-1 gap-5 lg:grid-cols-2">
        @foreach([
            \Modules\StandardHours\Services\PeriodService::MODE_CALENDAR_YEAR => [
                'title' => 'Theo năm',
                'icon' => 'bi-calendar3',
                'description' => 'Kỳ tính từ ngày 01/01 đến 31/12 của cùng một năm.',
            ],
            \Modules\StandardHours\Services\PeriodService::MODE_ACADEMIC_YEAR => [
                'title' => 'Theo năm học',
                'icon' => 'bi-calendar2-week',
                'description' => 'Kỳ tính lấy đúng ngày bắt đầu và kết thúc trong Danh mục năm học dùng chung.',
            ],
        ] as $value => $option)
            @php($selected = old('period_mode', $mode) === $value)
            <label data-period-card class="group relative cursor-pointer overflow-visible rounded-2xl border-2 bg-white p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-400 hover:shadow-lg {{ $selected ? 'border-blue-600 ring-4 ring-blue-100' : 'border-slate-200' }}">
                <input type="radio" name="period_mode" value="{{ $value }}" class="peer sr-only" {{ $selected ? 'checked' : '' }} required>
                <span data-period-indicator
                      class="absolute z-10 flex h-7 w-7 items-center justify-center rounded-full border-2 shadow-sm transition {{ $selected ? 'border-blue-600 bg-blue-600 text-white' : 'border-slate-300 bg-white text-transparent' }}"
                      style="left: auto !important; right: 1.25rem !important; top: 1.25rem !important; transform: none !important;">
                    <i class="bi bi-check-lg"></i>
                </span>
                <span class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-100 text-2xl text-blue-700">
                    <i class="bi {{ $option['icon'] }}"></i>
                </span>
                <h2 class="mt-4 text-xl font-bold text-slate-900">{{ $option['title'] }}</h2>
                <p class="mt-2 text-sm leading-6 text-slate-600">{{ $option['description'] }}</p>
                <div class="mt-5 rounded-xl bg-slate-50 p-4 text-sm">
                    <div class="font-semibold text-slate-800">Ví dụ kỳ {{ $samples[$value]['label'] }}</div>
                    <div class="mt-1 text-slate-600">
                        {{ \Carbon\Carbon::parse($samples[$value]['from_date'])->format('d/m/Y') }}
                        <i class="bi bi-arrow-right mx-1 text-blue-600"></i>
                        {{ \Carbon\Carbon::parse($samples[$value]['to_date'])->format('d/m/Y') }}
                    </div>
                </div>
            </label>
        @endforeach
    </div>

    @error('period_mode')
        <p class="mt-3 text-sm text-red-600">{{ $message }}</p>
    @enderror

    <div class="mt-7 flex flex-col-reverse items-stretch justify-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center">
        <a href="{{ route('standard-hours.hub') }}"
           class="{{ \Modules\StandardHours\Support\ActionButton::classes('secondary') }} justify-center sm:min-w-[110px]">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
            Hủy
        </a>
        <button type="submit"
                class="{{ \Modules\StandardHours\Support\ActionButton::classes('primary') }} min-w-[190px] justify-center disabled:cursor-wait disabled:opacity-70"
                data-period-submit>
            <span class="inline-flex items-center gap-2" data-period-submit-idle>
                <i class="bi bi-check2-circle" aria-hidden="true"></i>
                Lưu và áp dụng
            </span>
            <span class="hidden items-center gap-2" data-period-submit-busy role="status" aria-live="polite">
                <span class="h-4 w-4 animate-spin rounded-full border-2 border-white/40 border-t-white" aria-hidden="true"></span>
                Đang áp dụng...
            </span>
        </button>
    </div>
</form>

@push('scripts')
<script>
(function () {
    const form = document.querySelector('[data-period-mode-form]');
    if (!form || form.dataset.periodModeBound === '1') return;
    form.dataset.periodModeBound = '1';

    function syncPeriodCards() {
        form.querySelectorAll('[data-period-card]').forEach(function (card) {
            const input = card.querySelector('input[name="period_mode"]');
            const indicator = card.querySelector('[data-period-indicator]');
            const selected = Boolean(input?.checked);
            card.classList.toggle('border-blue-600', selected);
            card.classList.toggle('ring-4', selected);
            card.classList.toggle('ring-blue-100', selected);
            card.classList.toggle('border-slate-200', !selected);
            indicator?.classList.toggle('border-blue-600', selected);
            indicator?.classList.toggle('bg-blue-600', selected);
            indicator?.classList.toggle('text-white', selected);
            indicator?.classList.toggle('border-slate-300', !selected);
            indicator?.classList.toggle('bg-white', !selected);
            indicator?.classList.toggle('text-transparent', !selected);
        });
    }

    function setSubmitting(isSubmitting) {
        const button = form.querySelector('[data-period-submit]');
        const idle = form.querySelector('[data-period-submit-idle]');
        const busy = form.querySelector('[data-period-submit-busy]');

        if (!button) return;

        button.disabled = isSubmitting;
        button.setAttribute('aria-busy', isSubmitting ? 'true' : 'false');
        idle?.classList.toggle('hidden', isSubmitting);
        idle?.classList.toggle('inline-flex', !isSubmitting);
        busy?.classList.toggle('hidden', !isSubmitting);
        busy?.classList.toggle('inline-flex', isSubmitting);
    }

    form.querySelectorAll('input[name="period_mode"]').forEach(function (input) {
        input.addEventListener('change', syncPeriodCards);
    });
    form.addEventListener('turbo:submit-start', function () {
        setSubmitting(true);
    });
    form.addEventListener('turbo:submit-end', function () {
        setSubmitting(false);
    });

    syncPeriodCards();
})();
</script>
@endpush
@endsection
