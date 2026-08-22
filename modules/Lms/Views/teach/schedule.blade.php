@extends('layouts.lms-learner')

@section('title', 'Lịch dạy')

@section('content')
@php
    $start = $month->copy()->startOfMonth();
    $end = $month->copy()->endOfMonth();
    $pad = $start->dayOfWeekIso - 1;
@endphp

<div class="mb-4">
    <h1 class="text-2xl font-bold text-slate-900">Lịch dạy của tôi</h1>
    <p class="text-sm text-slate-500 mt-1">
        Xem ngay trên cổng LMS
        @if(!empty($instructor)) · {{ $instructor->name }} @endif
    </p>
</div>

@if(!empty($noInstructor))
    <div class="lms-card p-8 text-center text-slate-500 text-sm">Tài khoản chưa gắn hồ sơ giảng viên.</div>
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
            <a href="{{ route('lms.teach.schedule', ['m' => $month->copy()->subMonth()->format('Y-m')]) }}" class="lms-btn lms-btn-ghost">←</a>
            <h2 class="font-bold text-slate-800 capitalize">{{ $month->translatedFormat('F Y') }}</h2>
            <a href="{{ route('lms.teach.schedule', ['m' => $month->copy()->addMonth()->format('Y-m')]) }}" class="lms-btn lms-btn-ghost">→</a>
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
                    if (collect($items)->contains(fn ($e) => !empty($e['is_exam']))) $cls[] = 'is-exam-day';
                @endphp
                <button type="button" class="{{ implode(' ', $cls) }}"
                        data-sch-day data-date="{{ $key }}" data-items='@json($items)'>
                    <span>{{ $day }}</span>
                    @if(count($items))
                        <span class="lms-cal-count">{{ count($items) }}</span>
                    @endif
                </button>
            @endfor
        </div>
        <p class="text-xs text-slate-500 mt-3">Chấm xanh = có lịch. Cam = có thi. Bấm ngày để xem tiết.</p>
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
                box.innerHTML = '<div class="lms-card p-4 text-sm text-slate-500">Không có tiết ngày ' + btn.dataset.date + '.</div>';
                return;
            }
            const typeLabel = { theory: 'LT', practice: 'TH', self_study: 'TH', final_exam: 'Thi', exam: 'Thi' };
            box.innerHTML = '<div class="lms-card overflow-hidden"><div class="lms-card-head">Ngày ' + btn.dataset.date + '</div><ul class="divide-y">' +
                items.map(it => {
                    let link = '';
                    if (it.lms_url) {
                        link = '<div class="mt-1.5"><a href="' + it.lms_url + '" class="text-xs font-semibold text-teal-700 hover:underline">' +
                            '<i class="bi bi-easel"></i> Vào khóa LMS' +
                            (it.lms_course_title ? ' · ' + it.lms_course_title : '') +
                            '</a></div>';
                    } else if (it.lms_alternatives && it.lms_alternatives.length) {
                        // Sprint 8 G10: 1 môn nhiều lớp — liệt kê để chọn
                        link = '<div class="mt-1.5 space-y-1"><div class="text-[11px] text-amber-700 font-medium">Nhiều khóa LMS cùng môn — chọn lớp:</div>' +
                            it.lms_alternatives.map(function (alt) {
                                return '<a href="' + alt.url + '" class="block text-xs font-semibold text-teal-700 hover:underline">' +
                                    '<i class="bi bi-easel"></i> ' + (alt.class || alt.title) +
                                    (alt.title ? ' · ' + alt.title : '') +
                                    '</a>';
                            }).join('') + '</div>';
                    } else {
                        link = '<div class="mt-1 text-[11px] text-slate-400">Chưa map khóa LMS (subject/class)</div>';
                    }
                    return '<li class="px-4 py-3 text-sm"><strong>' + (it.subject || '') + '</strong>' +
                        '<div class="text-xs text-slate-500 mt-0.5">Tiết ' + (it.period || '?') +
                        (it.class ? ' · ' + it.class : '') +
                        (it.room ? ' · P.' + it.room : '') +
                        (it.building ? ' · ' + it.building : '') +
                        ' · ' + (typeLabel[it.lesson_type] || it.lesson_type || '') +
                        '</div>' + link + '</li>';
                }).join('') + '</ul></div>';
        });
    });
})();
</script>
@endpush
