@extends('layouts.lms-learner')

@section('title', 'Lịch học')

@section('content')
@php
    $start = $month->copy()->startOfMonth();
    $end = $month->copy()->endOfMonth();
    $pad = $start->dayOfWeekIso - 1;
@endphp

<div class="mb-4">
    <h1 class="text-2xl font-bold text-slate-900">Lịch học của tôi</h1>
    <p class="text-sm text-slate-500 mt-1">
        Xem ngay trên cổng LMS · {{ $student->class->name ?? 'Chưa có lớp' }}
        @if(!empty($activeTrainingSchedule))
            · Khung: {{ $activeTrainingSchedule->name ?? ('#'.$activeTrainingSchedule->id) }}
        @endif
    </p>
</div>

@if(!empty($noClass))
    <div class="lms-card p-8 text-center text-slate-500 text-sm">Bạn chưa được xếp lớp — chưa có lịch học.</div>
@elseif(!empty($noSchedule))
    <div class="lms-card p-8 text-center text-slate-500 text-sm">Chưa có khung lịch đào tạo đang hoạt động cho lớp của bạn.</div>
@else
    @if($stats)
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
            <div class="lms-card p-3 text-center">
                <div class="text-[11px] text-slate-500">Môn</div>
                <div class="text-xl font-bold text-teal-800">{{ $stats['total_subjects'] ?? 0 }}</div>
            </div>
            <div class="lms-card p-3 text-center">
                <div class="text-[11px] text-slate-500">LT</div>
                <div class="text-xl font-bold">{{ $stats['theory_hours'] ?? 0 }}</div>
            </div>
            <div class="lms-card p-3 text-center">
                <div class="text-[11px] text-slate-500">TH</div>
                <div class="text-xl font-bold">{{ $stats['practice_hours'] ?? 0 }}</div>
            </div>
            <div class="lms-card p-3 text-center">
                <div class="text-[11px] text-slate-500">Tổng tiết</div>
                <div class="text-xl font-bold text-teal-700">{{ $stats['total_hours'] ?? 0 }}</div>
            </div>
        </div>
    @endif

    <div class="lms-card p-5">
        <div class="flex items-center justify-between mb-4">
            <a href="{{ route('lms.learn.schedule', ['m' => $month->copy()->subMonth()->format('Y-m')]) }}"
               class="lms-btn lms-btn-ghost">←</a>
            <h2 class="font-bold text-slate-800 capitalize">{{ $month->translatedFormat('F Y') }}</h2>
            <a href="{{ route('lms.learn.schedule', ['m' => $month->copy()->addMonth()->format('Y-m')]) }}"
               class="lms-btn lms-btn-ghost">→</a>
        </div>

        <div class="lms-cal mb-2">
            @foreach(['T2','T3','T4','T5','T6','T7','CN'] as $d)
                <div class="lms-cal-dow">{{ $d }}</div>
            @endforeach
            @for($i = 0; $i < $pad; $i++)
                <div class="lms-cal-day is-empty"></div>
            @endfor
            @for($day = 1; $day <= $end->day; $day++)
                @php
                    $date = $month->copy()->day($day);
                    $key = $date->format('Y-m-d');
                    $items = $eventsByDate[$key] ?? [];
                    $cls = ['lms-cal-day'];
                    if ($date->isToday()) $cls[] = 'is-today';
                    if (count($items)) $cls[] = 'has-session';
                    $hasExam = collect($items)->contains(fn ($e) => !empty($e['is_exam']));
                    if ($hasExam) $cls[] = 'is-exam-day';
                @endphp
                <button type="button"
                        class="{{ implode(' ', $cls) }}"
                        data-sch-day
                        data-date="{{ $key }}"
                        data-items='@json($items)'>
                    <span>{{ $day }}</span>
                    @if(count($items))
                        <span class="lms-cal-count">{{ count($items) }}</span>
                    @endif
                </button>
            @endfor
        </div>
        <p class="text-xs text-slate-500 mt-3">
            Chấm xanh = có lịch. Chấm cam = có lịch thi. Bấm ngày để xem chi tiết tiết học.
        </p>
        <div id="sch-detail" class="mt-4 hidden"></div>
    </div>
@endif
@endsection

@push('scripts')
<script>
(function () {
    const box = document.getElementById('sch-detail');
    document.querySelectorAll('[data-sch-day]').forEach(btn => {
        btn.addEventListener('click', () => {
            document.querySelectorAll('.lms-cal-day.is-selected').forEach(d => d.classList.remove('is-selected'));
            btn.classList.add('is-selected');
            if (!box) return;
            let items = [];
            try { items = JSON.parse(btn.dataset.items || '[]'); } catch (e) {}
            box.classList.remove('hidden');
            if (!items.length) {
                box.innerHTML = '<div class="lms-card p-4 text-sm text-slate-500">Không có tiết học ngày ' + btn.dataset.date + '.</div>';
                return;
            }
            const typeLabel = { theory: 'Lý thuyết', practice: 'Thực hành', self_study: 'Tự học', final_exam: 'Thi', exam: 'Thi' };
            box.innerHTML = '<div class="lms-card overflow-hidden"><div class="lms-card-head">Ngày ' + btn.dataset.date + ' · ' + items.length + ' tiết</div><ul class="divide-y divide-slate-100">' +
                items.map(it => {
                    const badge = it.is_exam ? 'lms-chip lms-chip-amber' : 'lms-chip lms-chip-teal';
                    return '<li class="px-4 py-3 flex flex-wrap gap-2 justify-between items-start">' +
                        '<div><div class="font-semibold text-slate-900">' + (it.subject || '') + '</div>' +
                        '<div class="text-xs text-slate-500 mt-0.5">Tiết ' + (it.period || '?') +
                        (it.room ? ' · P.' + it.room : '') +
                        (it.building ? ' · ' + it.building : '') +
                        (it.instructor ? ' · GV: ' + it.instructor : '') +
                        '</div></div>' +
                        '<span class="' + badge + '">' + (typeLabel[it.lesson_type] || it.lesson_type || 'Học') + '</span></li>';
                }).join('') + '</ul></div>';
        });
    });
})();
</script>
@endpush
