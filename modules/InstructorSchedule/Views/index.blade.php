@extends('layouts.admin')

@section('title', 'Lịch giảng dạy của tôi')
@section('page-title', 'Lịch giảng dạy của tôi')

@php
    $displayName = $instructor?->name ?? auth()->user()->name;
    $displayUnit = $instructor?->unit?->name ?? auth()->user()->unit?->name ?? 'Chưa có đơn vị';
    $displayEmail = $instructor?->email ?? auth()->user()->email;
    $displayCode = $instructor?->code ?? 'Chưa cập nhật';
    $rangeDays = \Carbon\Carbon::parse($dateRange['start'])->diffInDays(\Carbon\Carbon::parse($dateRange['end'])) + 1;
    $monthStart = now()->startOfMonth()->toDateString();
    $monthEnd = now()->endOfMonth()->toDateString();
@endphp

@section('content')
<div class="instructor-schedule-page" data-instructor-schedule data-active-tab="{{ $activeTab }}">
    <x-breadcrumb :items="[
        ['title' => 'Trang chủ'],
        ['title' => 'Lịch giảng dạy của tôi'],
    ]" />

    <section class="is-profile-hero">
        <div class="is-profile-hero__glow is-profile-hero__glow--one"></div>
        <div class="is-profile-hero__glow is-profile-hero__glow--two"></div>
        <div class="is-profile-hero__main">
            <div class="is-profile-avatar" aria-hidden="true">
                {{ mb_strtoupper(mb_substr($displayName, 0, 1)) }}
            </div>
            <div class="is-profile-copy">
                <p class="is-eyebrow">Lịch giảng dạy cá nhân</p>
                <h1>{{ $displayName }}</h1>
                <div class="is-profile-meta">
                    <span><i class="bi bi-person-vcard"></i> {{ $displayCode }}</span>
                    <span><i class="bi bi-building"></i> {{ $displayUnit }}</span>
                    <span><i class="bi bi-envelope"></i> {{ $displayEmail }}</span>
                </div>
            </div>
        </div>
        <div class="is-profile-hero__summary">
            <span>Khoảng đang xem</span>
            <strong>{{ $dateRangeLabel }}</strong>
            <small>{{ $rangeDays }} ngày · {{ $stats['total_hours'] }} tiết đã xếp</small>
        </div>
    </section>

    @if(Route::has('lms.teach.schedule'))
        <div class="is-crosslink">
            <div class="is-crosslink__icon"><i class="bi bi-mortarboard"></i></div>
            <div>
                <strong>Không gian giảng dạy LMS</strong>
                <p>Mở lớp học, học liệu và hoạt động giảng dạy từ cổng LMS.</p>
            </div>
            <a href="{{ route('lms.teach.schedule') }}" class="is-btn is-btn--teal" data-turbo="false">
                Mở lịch LMS <i class="bi bi-arrow-up-right"></i>
            </a>
        </div>
    @endif

    <section class="is-filter-shell" aria-label="Bộ lọc lịch giảng dạy">
        <form method="GET"
              action="{{ route('instructor-schedule.index') }}"
              id="instructor-schedule-filter"
              class="is-filter-form"
              data-turbo="true"
              data-turbo-action="replace">
            <input type="hidden" name="tab" value="{{ $activeTab }}" data-active-tab-input>

            <div class="is-date-field">
                <label for="date_from">Từ ngày</label>
                <input type="date"
                       id="date_from"
                       name="date_from"
                       value="{{ request('date_from', $dateRange['start']) }}"
                       required>
            </div>
            <div class="is-date-field">
                <label for="date_to">Đến ngày</label>
                <input type="date"
                       id="date_to"
                       name="date_to"
                       value="{{ request('date_to', $dateRange['end']) }}"
                       required>
            </div>

            <div class="is-filter-actions">
                <button type="submit" class="is-btn is-btn--primary" data-filter-submit>
                    <i class="bi bi-funnel"></i>
                    <span data-filter-label>Áp dụng</span>
                </button>
                <a href="{{ route('instructor-schedule.index', ['tab' => $activeTab]) }}" class="is-btn is-btn--soft">
                    <i class="bi bi-arrow-counterclockwise"></i> Tuần này
                </a>
            </div>
        </form>

        <div class="is-quick-actions">
            <a href="{{ route('instructor-schedule.index', [
                'date_from' => now()->toDateString(),
                'date_to' => now()->toDateString(),
                'tab' => $activeTab,
            ]) }}" class="is-quick-link">
                Hôm nay
            </a>
            <a href="{{ route('instructor-schedule.index', [
                'date_from' => $monthStart,
                'date_to' => $monthEnd,
                'tab' => $activeTab,
            ]) }}" class="is-quick-link">
                Tháng này
            </a>
            <form method="POST"
                  action="{{ route('instructor-schedule.export') }}"
                  data-turbo="false"
                  class="is-export-form">
                @csrf
                <input type="hidden" name="start_date" value="{{ $dateRange['start'] }}">
                <input type="hidden" name="end_date" value="{{ $dateRange['end'] }}">
                <button type="submit" class="is-btn is-btn--excel">
                    <i class="bi bi-file-earmark-excel"></i> Xuất Excel
                </button>
            </form>
        </div>
    </section>

    @error('date_from')
        <div class="is-validation-message"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
    @enderror
    @error('date_to')
        <div class="is-validation-message"><i class="bi bi-exclamation-circle"></i> {{ $message }}</div>
    @enderror

    <section class="is-workspace">
        <div class="is-tabs" role="tablist" aria-label="Nội dung lịch giảng dạy">
            <button type="button"
                    class="is-tab"
                    role="tab"
                    aria-controls="schedule-panel"
                    data-schedule-tab="schedule">
                <i class="bi bi-calendar3"></i>
                <span>Lịch giảng dạy</span>
            </button>
            <button type="button"
                    class="is-tab"
                    role="tab"
                    aria-controls="statistics-panel"
                    data-schedule-tab="statistics">
                <i class="bi bi-bar-chart-line"></i>
                <span>Thống kê số tiết</span>
                <span class="is-tab__count">{{ $stats['total_hours'] }}</span>
            </button>
        </div>

        <div id="schedule-panel" class="is-tab-panel" role="tabpanel" data-schedule-panel="schedule">
            <div class="is-range-bar">
                @if(!$isCustomRange)
                    <a href="{{ route('instructor-schedule.index', [
                        'week_offset' => $weekOffset - 1,
                        'tab' => 'schedule',
                    ]) }}" class="is-range-nav" aria-label="Xem tuần trước">
                        <i class="bi bi-chevron-left"></i><span>Tuần trước</span>
                    </a>
                @else
                    <a href="{{ route('instructor-schedule.index', ['tab' => 'schedule']) }}" class="is-range-nav">
                        <i class="bi bi-calendar-week"></i><span>Về tuần này</span>
                    </a>
                @endif

                <div class="is-range-title">
                    <span>{{ $isCustomRange ? 'Khoảng thời gian đã chọn' : ($weekOffset === 0 ? 'Tuần hiện tại' : 'Tuần đang xem') }}</span>
                    <strong>{{ $dateRangeLabel }}</strong>
                    <small>{{ $stats['teaching_days'] }} ngày có lịch · {{ $stats['total_hours'] }} tiết</small>
                </div>

                @if(!$isCustomRange)
                    <a href="{{ route('instructor-schedule.index', [
                        'week_offset' => $weekOffset + 1,
                        'tab' => 'schedule',
                    ]) }}" class="is-range-nav is-range-nav--next" aria-label="Xem tuần sau">
                        <span>Tuần sau</span><i class="bi bi-chevron-right"></i>
                    </a>
                @else
                    <button type="button" class="is-range-nav is-range-nav--next" data-open-statistics>
                        <span>Xem thống kê</span><i class="bi bi-bar-chart-line"></i>
                    </button>
                @endif
            </div>

            <div class="is-calendar-shell">
                <div class="is-calendar-scroll">
                    <table class="is-calendar-table" style="--is-day-count: {{ max(count($calendar), 1) }}">
                        <thead>
                            <tr>
                                <th class="is-period-heading">Tiết</th>
                                @foreach($calendar as $dayData)
                                    <th class="{{ $dayData['is_today'] ? 'is-today' : '' }}">
                                        <span class="is-day-name">{{ $dayData['weekday'] }}</span>
                                        <strong>{{ str_pad($dayData['day_number'], 2, '0', STR_PAD_LEFT) }}/{{ str_pad($dayData['month_number'], 2, '0', STR_PAD_LEFT) }}</strong>
                                        @if($dayData['is_today'])
                                            <small>Hôm nay</small>
                                        @endif
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @for($period = 1; $period <= 9; $period++)
                                <tr>
                                    <th scope="row" class="is-period-cell">
                                        <span>{{ $period }}</span>
                                    </th>
                                    @foreach($calendar as $dayData)
                                        @php
                                            $detail = $dayData['periods'][$period];
                                            $lessonType = $detail?->lesson_type;
                                            $safeLessonType = array_key_exists((string) $lessonType, $lessonTypes)
                                                ? $lessonType
                                                : 'unknown';
                                            $lessonMeta = $lessonTypes[$lessonType] ?? [
                                                'label' => 'Chưa xác định',
                                                'short_label' => '—',
                                                'icon' => 'bi-question-circle',
                                            ];
                                        @endphp
                                        <td class="{{ $dayData['is_today'] ? 'is-today-column' : '' }}">
                                            @if($detail)
                                                <article class="is-lesson-card is-type--{{ $safeLessonType }}">
                                                    <div class="is-lesson-card__top">
                                                        <span class="is-type-badge">
                                                            <i class="bi {{ $lessonMeta['icon'] }}"></i>
                                                            {{ $lessonMeta['short_label'] }}
                                                        </span>
                                                        <span class="is-period-caption">Tiết {{ $period }}</span>
                                                    </div>
                                                    <h3>{{ $detail->subject?->name ?? 'Chưa xác định môn học' }}</h3>
                                                    <div class="is-lesson-card__meta">
                                                        <span>
                                                            <i class="bi bi-people"></i>
                                                            {{ $detail->trainingSchedule?->classModel?->code
                                                                ?? $detail->trainingSchedule?->class_code
                                                                ?? 'Chưa có lớp' }}
                                                        </span>
                                                        <span>
                                                            <i class="bi bi-geo-alt"></i>
                                                            {{ $detail->classroom?->name ?? 'Chưa có phòng' }}
                                                            @if($detail->classroom?->building?->name)
                                                                · {{ $detail->classroom->building->name }}
                                                            @endif
                                                        </span>
                                                    </div>
                                                </article>
                                            @else
                                                <span class="is-empty-period" aria-label="Không có lịch ở tiết {{ $period }}">—</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="is-legend" aria-label="Chú thích loại tiết">
                <strong>Chú thích</strong>
                @foreach($lessonTypes as $type => $meta)
                    <span class="is-legend-item is-type--{{ $type }}">
                        <i class="bi {{ $meta['icon'] }}"></i>{{ $meta['label'] }}
                    </span>
                @endforeach
                <span class="is-legend-item is-legend-item--today"><i class="bi bi-calendar-check"></i> Hôm nay</span>
            </div>
        </div>

        <div id="statistics-panel" class="is-tab-panel" role="tabpanel" data-schedule-panel="statistics">
            <div class="is-statistics-header">
                <div>
                    <span class="is-section-kicker">Dữ liệu đồng bộ theo lịch đang xem</span>
                    <h2>Thống kê số tiết từ {{ $dateRangeLabel }}</h2>
                    <p>Mỗi dòng tiết trong lịch huấn luyện được tính là 01 tiết giảng dạy.</p>
                </div>
                <span class="is-range-pill"><i class="bi bi-calendar-range"></i> {{ $rangeDays }} ngày</span>
            </div>

            <div class="is-kpi-grid">
                <article class="is-kpi-card is-kpi-card--primary">
                    <div class="is-kpi-icon"><i class="bi bi-clock-history"></i></div>
                    <div><span>Tổng số tiết</span><strong>{{ $stats['total_hours'] }}</strong><small>tiết đã được xếp</small></div>
                </article>
                <article class="is-kpi-card">
                    <div class="is-kpi-icon"><i class="bi bi-calendar2-check"></i></div>
                    <div><span>Ngày có lịch</span><strong>{{ $stats['teaching_days'] }}</strong><small>bình quân {{ $stats['average_hours_per_day'] }} tiết/ngày</small></div>
                </article>
                <article class="is-kpi-card">
                    <div class="is-kpi-icon"><i class="bi bi-people"></i></div>
                    <div><span>Lớp giảng dạy</span><strong>{{ $stats['total_classes'] }}</strong><small>{{ $stats['total_rooms'] }} phòng học</small></div>
                </article>
                <article class="is-kpi-card">
                    <div class="is-kpi-icon"><i class="bi bi-book"></i></div>
                    <div><span>Môn học</span><strong>{{ $stats['total_subjects'] }}</strong><small>trong khoảng đã chọn</small></div>
                </article>
            </div>

            <div class="is-type-grid">
                @foreach($stats['type_breakdown'] as $item)
                    <article class="is-type-stat is-type--{{ $item['key'] }}">
                        <div class="is-type-stat__head">
                            <span class="is-type-stat__icon"><i class="bi {{ $item['icon'] }}"></i></span>
                            <span>{{ $item['label'] }}</span>
                            <strong>{{ $item['hours'] }}</strong>
                        </div>
                        <div class="is-progress-track">
                            <span style="width: {{ min(100, $item['percentage']) }}%"></span>
                        </div>
                        <small>{{ $item['percentage'] }}% tổng số tiết</small>
                    </article>
                @endforeach
            </div>

            <div class="is-operational-grid">
                <div><i class="bi bi-sunrise"></i><span>Buổi sáng</span><strong>{{ $stats['morning_hours'] }} tiết</strong><small>Tiết 1–5</small></div>
                <div><i class="bi bi-sunset"></i><span>Buổi chiều</span><strong>{{ $stats['afternoon_hours'] }} tiết</strong><small>Tiết 6–9</small></div>
                <div><i class="bi bi-graph-up-arrow"></i><span>Ngày dạy nhiều nhất</span><strong>{{ $stats['peak_day']['total_hours'] ?? 0 }} tiết</strong><small>{{ $stats['peak_day']['date_label'] ?? 'Chưa có dữ liệu' }}</small></div>
                <div><i class="bi bi-question-diamond"></i><span>Chưa phân loại</span><strong>{{ $stats['unclassified_hours'] }} tiết</strong><small>Ngoài 4 loại chuẩn</small></div>
            </div>

            @if($stats['total_hours'] > 0)
                <div class="is-report-grid">
                    <section class="is-report-card is-report-card--wide">
                        <div class="is-report-card__header">
                            <div><span>Chi tiết theo môn</span><h3>Phân bổ số tiết giảng dạy</h3></div>
                            <span>{{ count($stats['subject_breakdown']) }} môn</span>
                        </div>
                        <div class="is-table-scroll">
                            <table class="is-report-table">
                                <thead>
                                    <tr>
                                        <th>Mã môn</th>
                                        <th>Môn học</th>
                                        <th class="is-number">LT</th>
                                        <th class="is-number">TH</th>
                                        <th class="is-number">Tự học</th>
                                        <th class="is-number">Thi/KT</th>
                                        <th class="is-number">Tổng</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($stats['subject_breakdown'] as $row)
                                        <tr>
                                            <td><span class="is-code">{{ $row['subject_code'] }}</span></td>
                                            <td><strong>{{ $row['subject_name'] }}</strong><small>{{ $row['class_count'] }} lớp</small></td>
                                            <td class="is-number">{{ $row['theory'] }}</td>
                                            <td class="is-number">{{ $row['practice'] }}</td>
                                            <td class="is-number">{{ $row['self_study'] }}</td>
                                            <td class="is-number">{{ $row['final_exam'] }}</td>
                                            <td class="is-number"><strong>{{ $row['total_hours'] }}</strong></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </section>

                    <section class="is-report-card">
                        <div class="is-report-card__header">
                            <div><span>Chi tiết theo lớp</span><h3>Khối lượng lớp phụ trách</h3></div>
                            <span>{{ count($stats['class_breakdown']) }} lớp</span>
                        </div>
                        <div class="is-class-list">
                            @foreach($stats['class_breakdown'] as $row)
                                <div class="is-class-row">
                                    <div><strong>{{ $row['class_name'] }}</strong><small>{{ $row['subject_count'] }} môn học</small></div>
                                    <span>{{ $row['total_hours'] }} tiết</span>
                                </div>
                            @endforeach
                        </div>
                    </section>
                </div>

                <section class="is-report-card is-daily-report">
                    <div class="is-report-card__header">
                        <div><span>Chi tiết theo ngày</span><h3>Nhật ký tiết giảng trong khoảng đã chọn</h3></div>
                        <span>{{ count($stats['daily_breakdown']) }} ngày có lịch</span>
                    </div>
                    <div class="is-table-scroll">
                        <table class="is-report-table">
                            <thead>
                                <tr>
                                    <th>Ngày dạy</th>
                                    <th class="is-number">Số lớp</th>
                                    <th class="is-number">Lý thuyết</th>
                                    <th class="is-number">Thực hành</th>
                                    <th class="is-number">Tự học</th>
                                    <th class="is-number">Thi/KT</th>
                                    <th class="is-number">Tổng tiết</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($stats['daily_breakdown'] as $row)
                                    <tr>
                                        <td><strong>{{ $row['weekday'] }}</strong><small>{{ $row['date_label'] }}</small></td>
                                        <td class="is-number">{{ $row['class_count'] }}</td>
                                        <td class="is-number">{{ $row['theory'] }}</td>
                                        <td class="is-number">{{ $row['practice'] }}</td>
                                        <td class="is-number">{{ $row['self_study'] }}</td>
                                        <td class="is-number">{{ $row['final_exam'] }}</td>
                                        <td class="is-number"><strong>{{ $row['total_hours'] }}</strong></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </section>
            @else
                <div class="is-empty-state">
                    <span><i class="bi bi-calendar2-x"></i></span>
                    <h3>Chưa có tiết giảng trong khoảng này</h3>
                    <p>Hãy chọn một khoảng ngày khác hoặc kiểm tra lại lịch huấn luyện đã được phân công.</p>
                    <button type="button" class="is-btn is-btn--primary" data-open-schedule>
                        <i class="bi bi-calendar3"></i> Quay lại xem lịch
                    </button>
                </div>
            @endif
        </div>
    </section>
</div>
@endsection

@push('styles')
<style>
    .instructor-schedule-page {
        --is-primary: #2563eb;
        --is-primary-dark: #1d4ed8;
        --is-navy: #123a55;
        --is-border: #dbe3ee;
        --is-muted: #64748b;
        --is-surface: #ffffff;
        color: #172033;
    }

    .is-profile-hero {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 2rem;
        min-height: 168px;
        margin-bottom: 1rem;
        padding: 1.75rem 2rem;
        border: 1px solid rgba(255, 255, 255, .28);
        border-radius: 1.25rem;
        color: #fff;
        background: linear-gradient(125deg, #174766 0%, #2563eb 58%, #4ea1ff 100%);
        box-shadow: 0 20px 45px -25px rgba(30, 64, 175, .7);
    }

    .is-profile-hero__glow {
        position: absolute;
        z-index: -1;
        border-radius: 999px;
        filter: blur(2px);
        opacity: .3;
    }

    .is-profile-hero__glow--one { width: 260px; height: 260px; top: -160px; right: 15%; background: #bfdbfe; }
    .is-profile-hero__glow--two { width: 210px; height: 210px; bottom: -145px; left: 32%; background: #93c5fd; }
    .is-profile-hero__main { display: flex; align-items: center; gap: 1.15rem; min-width: 0; }
    .is-profile-avatar {
        display: grid;
        flex: 0 0 68px;
        width: 68px;
        height: 68px;
        place-items: center;
        border: 1px solid rgba(255, 255, 255, .42);
        border-radius: 1.1rem;
        color: #174766;
        background: rgba(255, 255, 255, .92);
        box-shadow: 0 12px 30px -18px rgba(15, 23, 42, .75);
        font-size: 1.65rem;
        font-weight: 800;
    }

    .is-profile-copy { min-width: 0; }
    .is-eyebrow, .is-section-kicker {
        margin: 0 0 .25rem;
        font-size: .7rem;
        font-weight: 800;
        letter-spacing: .12em;
        text-transform: uppercase;
    }

    .is-eyebrow { color: #dbeafe; }
    .is-profile-copy h1 { margin: 0; font-size: clamp(1.45rem, 2.5vw, 2rem); font-weight: 800; line-height: 1.2; }
    .is-profile-meta { display: flex; flex-wrap: wrap; gap: .5rem 1rem; margin-top: .75rem; color: #e0f2fe; font-size: .82rem; }
    .is-profile-meta span { display: inline-flex; align-items: center; gap: .4rem; }
    .is-profile-hero__summary {
        display: flex;
        flex: 0 0 auto;
        flex-direction: column;
        min-width: 230px;
        padding: 1rem 1.15rem;
        border: 1px solid rgba(255, 255, 255, .25);
        border-radius: 1rem;
        background: rgba(15, 45, 74, .3);
        backdrop-filter: blur(8px);
    }

    .is-profile-hero__summary span { color: #dbeafe; font-size: .72rem; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; }
    .is-profile-hero__summary strong { margin-top: .3rem; font-size: 1.05rem; }
    .is-profile-hero__summary small { margin-top: .25rem; color: #e0f2fe; }

    .is-crosslink {
        display: flex;
        align-items: center;
        gap: .85rem;
        margin-bottom: 1rem;
        padding: .8rem 1rem;
        border: 1px solid #99f6e4;
        border-radius: 1rem;
        background: linear-gradient(135deg, #f0fdfa, #ecfeff);
        color: #134e4a;
    }

    .is-crosslink__icon { display: grid; flex: 0 0 38px; width: 38px; height: 38px; place-items: center; border-radius: .75rem; color: #fff; background: #0f766e; }
    .is-crosslink strong { display: block; font-size: .86rem; }
    .is-crosslink p { margin: .1rem 0 0; color: #52716f; font-size: .78rem; }
    .is-crosslink .is-btn { margin-left: auto; }

    .is-filter-shell {
        display: flex;
        align-items: end;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
        padding: 1rem;
        border: 1px solid var(--is-border);
        border-radius: 1rem;
        background: rgba(255, 255, 255, .96);
        box-shadow: 0 12px 30px -26px rgba(23, 71, 102, .65);
    }

    .is-filter-form { display: flex; flex: 1; align-items: end; gap: .75rem; }
    .is-date-field { flex: 1 1 190px; max-width: 260px; }
    .is-date-field label { display: block; margin-bottom: .35rem; color: #475569; font-size: .75rem; font-weight: 700; }
    .is-date-field input { width: 100%; min-height: 42px; border-radius: .7rem !important; }
    .is-filter-actions, .is-quick-actions { display: flex; align-items: center; gap: .55rem; }
    .is-quick-actions { flex-wrap: wrap; justify-content: flex-end; }
    .is-export-form { display: inline-flex; }

    .is-btn {
        display: inline-flex;
        min-height: 40px;
        align-items: center;
        justify-content: center;
        gap: .45rem;
        padding: .58rem .9rem;
        border: 1px solid transparent;
        border-radius: .7rem;
        font-size: .8rem;
        font-weight: 750;
        line-height: 1;
        white-space: nowrap;
        transition: border-color .2s ease, box-shadow .2s ease, filter .2s ease, background .2s ease;
    }

    .is-btn:hover { box-shadow: 0 0 0 3px rgba(78, 161, 255, .14), 0 9px 20px -15px rgba(37, 99, 235, .7); }
    .is-btn--primary { color: #fff; background: linear-gradient(135deg, #4ea1ff, #2563eb); border-color: #60a5fa; }
    .is-btn--soft { color: #334155; background: #f8fafc; border-color: #dbe3ee; }
    .is-btn--soft:hover { color: #1d4ed8; border-color: #93c5fd; background: #eff6ff; }
    .is-btn--excel { color: #fff; background: linear-gradient(135deg, #22c55e, #15803d); border-color: #4ade80; }
    .is-btn--teal { min-height: 36px; color: #fff; background: linear-gradient(135deg, #14b8a6, #0f766e); }
    .is-quick-link { color: #475569; padding: .45rem .15rem; border-bottom: 1px dashed #94a3b8; font-size: .76rem; font-weight: 700; }
    .is-quick-link:hover { color: #2563eb; border-color: #2563eb; }
    .is-validation-message { display: flex; align-items: center; gap: .5rem; margin: -.35rem 0 1rem; padding: .7rem .85rem; border: 1px solid #fecaca; border-radius: .75rem; color: #b91c1c; background: #fff1f2; font-size: .8rem; }

    .is-workspace {
        overflow: hidden;
        border: 1px solid var(--is-border);
        border-radius: 1.15rem;
        background: var(--is-surface);
        box-shadow: 0 18px 42px -32px rgba(23, 71, 102, .68);
    }

    .is-tabs { display: flex; gap: .35rem; padding: .6rem .65rem 0; border-bottom: 1px solid #e2e8f0; background: linear-gradient(180deg, #f8fbff, #fff); }
    .is-tab {
        position: relative;
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .78rem 1rem;
        border: 0;
        border-radius: .75rem .75rem 0 0;
        color: #64748b;
        background: transparent;
        font-size: .84rem;
        font-weight: 750;
        cursor: pointer;
    }

    .is-tab::after { position: absolute; right: .7rem; bottom: -1px; left: .7rem; height: 3px; border-radius: 999px 999px 0 0; background: transparent; content: ""; }
    .is-tab:hover { color: #1d4ed8; background: #eff6ff; }
    .is-tab.is-active { color: #1d4ed8; background: #fff; box-shadow: 0 -5px 18px -15px rgba(37, 99, 235, .85); }
    .is-tab.is-active::after { background: #4ea1ff; }
    .is-tab__count { display: inline-grid; min-width: 24px; height: 21px; place-items: center; padding: 0 .38rem; border-radius: 999px; color: #1d4ed8; background: #dbeafe; font-size: .7rem; }
    .is-tab-panel { padding: 1rem; }
    .is-tab-panel[hidden] { display: none !important; }

    .is-range-bar { display: grid; grid-template-columns: minmax(120px, 1fr) auto minmax(120px, 1fr); align-items: center; gap: 1rem; margin-bottom: 1rem; padding: .7rem; border: 1px solid #e2e8f0; border-radius: .9rem; background: #f8fafc; }
    .is-range-nav { display: inline-flex; width: fit-content; align-items: center; gap: .4rem; padding: .5rem .65rem; border: 1px solid #dbe3ee; border-radius: .65rem; color: #475569; background: #fff; font-size: .76rem; font-weight: 700; }
    button.is-range-nav { cursor: pointer; }
    .is-range-nav:hover { color: #1d4ed8; border-color: #93c5fd; box-shadow: 0 0 0 3px rgba(147, 197, 253, .15); }
    .is-range-nav--next { justify-self: end; }
    .is-range-title { display: flex; flex-direction: column; align-items: center; text-align: center; }
    .is-range-title span { color: #64748b; font-size: .68rem; font-weight: 750; text-transform: uppercase; letter-spacing: .08em; }
    .is-range-title strong { margin-top: .15rem; color: #172033; font-size: 1rem; }
    .is-range-title small { margin-top: .15rem; color: #64748b; font-size: .72rem; }

    .is-calendar-shell { overflow: hidden; border: 1px solid #dbe3ee; border-radius: .9rem; }
    .is-calendar-scroll { overflow: auto; max-height: 660px; scrollbar-color: #93c5fd #eff6ff; scrollbar-width: thin; }
    .is-calendar-table { width: max(100%, calc(92px + (var(--is-day-count) * 184px))); min-width: 100%; border-collapse: separate; border-spacing: 0; table-layout: fixed; }
    .is-calendar-table th, .is-calendar-table td { border-right: 1px solid #e2e8f0; border-bottom: 1px solid #e2e8f0; }
    .is-calendar-table thead th { position: sticky; z-index: 20; top: 0; min-width: 184px; height: 74px; padding: .65rem; color: #fff; background: linear-gradient(180deg, #2563eb, #1d4ed8); text-align: center; }
    .is-calendar-table thead th.is-today { color: #172033; background: linear-gradient(180deg, #fde68a, #fbbf24); }
    .is-calendar-table thead th:last-child, .is-calendar-table td:last-child { border-right: 0; }
    .is-calendar-table thead .is-period-heading { left: 0; z-index: 35; width: 92px; min-width: 92px; color: #dbeafe; background: #174766; font-size: .72rem; text-transform: uppercase; letter-spacing: .08em; }
    .is-day-name { display: block; color: #dbeafe; font-size: .68rem; font-weight: 700; text-transform: uppercase; }
    .is-calendar-table thead .is-today .is-day-name { color: #713f12; }
    .is-calendar-table thead strong { display: block; margin-top: .1rem; font-size: 1.08rem; }
    .is-calendar-table thead small { display: inline-block; margin-top: .1rem; padding: .08rem .42rem; border-radius: 999px; color: #854d0e; background: rgba(255, 255, 255, .55); font-size: .62rem; font-weight: 800; text-transform: uppercase; }
    .is-calendar-table tbody td { height: 118px; padding: .55rem; vertical-align: top; background: #fff; }
    .is-calendar-table tbody tr:nth-child(even) td { background: #fbfdff; }
    .is-calendar-table tbody td.is-today-column { background: #fffbeb; }
    .is-period-cell { position: sticky; z-index: 15; left: 0; width: 92px; min-width: 92px; padding: .55rem; background: #f8fafc; text-align: center; }
    .is-period-cell span { display: inline-grid; width: 36px; height: 36px; place-items: center; border: 1px solid #bfdbfe; border-radius: .7rem; color: #1d4ed8; background: #eff6ff; font-size: .88rem; font-weight: 800; box-shadow: 0 5px 12px -10px rgba(37, 99, 235, .8); }

    .is-lesson-card { height: 100%; min-height: 98px; padding: .55rem .6rem; border: 1px solid var(--type-border, #cbd5e1); border-left: 4px solid var(--type-color, #64748b); border-radius: .7rem; background: var(--type-bg, #f8fafc); box-shadow: 0 7px 16px -16px rgba(15, 23, 42, .8); }
    .is-lesson-card__top { display: flex; align-items: center; justify-content: space-between; gap: .5rem; }
    .is-type-badge { display: inline-flex; align-items: center; gap: .3rem; padding: .16rem .42rem; border-radius: 999px; color: var(--type-text, #475569); background: rgba(255, 255, 255, .72); font-size: .62rem; font-weight: 800; }
    .is-period-caption { color: #94a3b8; font-size: .62rem; font-weight: 700; }
    .is-lesson-card h3 { display: -webkit-box; overflow: hidden; margin: .45rem 0 .4rem; color: #1e293b; font-size: .76rem; font-weight: 800; line-height: 1.25; -webkit-box-orient: vertical; -webkit-line-clamp: 2; }
    .is-lesson-card__meta { display: flex; flex-direction: column; gap: .22rem; color: #64748b; font-size: .66rem; }
    .is-lesson-card__meta span { display: flex; align-items: flex-start; gap: .3rem; }
    .is-lesson-card__meta i { color: var(--type-color, #64748b); }
    .is-empty-period { display: grid; height: 100%; min-height: 96px; place-items: center; color: #cbd5e1; font-size: 1rem; }

    .is-type--theory { --type-color: #2563eb; --type-text: #1d4ed8; --type-border: #bfdbfe; --type-bg: #eff6ff; }
    .is-type--practice { --type-color: #059669; --type-text: #047857; --type-border: #a7f3d0; --type-bg: #ecfdf5; }
    .is-type--self_study { --type-color: #7c3aed; --type-text: #6d28d9; --type-border: #ddd6fe; --type-bg: #f5f3ff; }
    .is-type--final_exam { --type-color: #e11d48; --type-text: #be123c; --type-border: #fecdd3; --type-bg: #fff1f2; }
    .is-type--unknown { --type-color: #64748b; --type-text: #475569; --type-border: #cbd5e1; --type-bg: #f8fafc; }

    .is-legend { display: flex; flex-wrap: wrap; align-items: center; gap: .55rem; margin-top: .8rem; padding: .7rem .8rem; border: 1px solid #e2e8f0; border-radius: .8rem; color: #64748b; background: #f8fafc; font-size: .7rem; }
    .is-legend > strong { margin-right: .2rem; color: #334155; }
    .is-legend-item { display: inline-flex; align-items: center; gap: .3rem; padding: .25rem .5rem; border: 1px solid var(--type-border, #dbe3ee); border-radius: 999px; color: var(--type-text, #475569); background: var(--type-bg, #fff); font-weight: 700; }
    .is-legend-item--today { color: #854d0e; border-color: #fde68a; background: #fffbeb; }

    .is-statistics-header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: 1rem; padding: .3rem .2rem; }
    .is-section-kicker { color: #2563eb; }
    .is-statistics-header h2 { margin: .1rem 0 0; color: #172033; font-size: 1.2rem; font-weight: 800; }
    .is-statistics-header p { margin: .25rem 0 0; color: #64748b; font-size: .78rem; }
    .is-range-pill { display: inline-flex; align-items: center; gap: .4rem; padding: .48rem .7rem; border: 1px solid #bfdbfe; border-radius: 999px; color: #1d4ed8; background: #eff6ff; font-size: .74rem; font-weight: 750; white-space: nowrap; }

    .is-kpi-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .8rem; }
    .is-kpi-card { display: flex; align-items: center; gap: .8rem; min-height: 112px; padding: 1rem; border: 1px solid #e2e8f0; border-radius: .9rem; background: #fff; box-shadow: 0 10px 24px -22px rgba(23, 71, 102, .7); }
    .is-kpi-card--primary { color: #fff; border-color: #60a5fa; background: linear-gradient(135deg, #174766, #2563eb); }
    .is-kpi-icon { display: grid; flex: 0 0 43px; width: 43px; height: 43px; place-items: center; border-radius: .78rem; color: #1d4ed8; background: #eff6ff; font-size: 1.05rem; }
    .is-kpi-card--primary .is-kpi-icon { color: #fff; background: rgba(255, 255, 255, .17); }
    .is-kpi-card div:last-child { display: flex; min-width: 0; flex-direction: column; }
    .is-kpi-card span { color: #64748b; font-size: .72rem; font-weight: 700; }
    .is-kpi-card--primary span, .is-kpi-card--primary small { color: #dbeafe; }
    .is-kpi-card strong { margin: .08rem 0; color: #172033; font-size: 1.65rem; line-height: 1; }
    .is-kpi-card--primary strong { color: #fff; }
    .is-kpi-card small { color: #94a3b8; font-size: .66rem; }

    .is-type-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .8rem; margin-top: .8rem; }
    .is-type-stat { padding: .85rem; border: 1px solid var(--type-border); border-radius: .85rem; background: var(--type-bg); }
    .is-type-stat__head { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: .5rem; color: var(--type-text); font-size: .76rem; font-weight: 750; }
    .is-type-stat__icon { display: grid; width: 30px; height: 30px; place-items: center; border-radius: .55rem; color: #fff; background: var(--type-color); }
    .is-type-stat__head strong { color: #172033; font-size: 1.25rem; }
    .is-progress-track { overflow: hidden; height: 6px; margin: .65rem 0 .35rem; border-radius: 999px; background: rgba(255, 255, 255, .8); }
    .is-progress-track span { display: block; height: 100%; border-radius: inherit; background: var(--type-color); }
    .is-type-stat small { color: #64748b; font-size: .65rem; }

    .is-operational-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: .65rem; margin-top: .8rem; }
    .is-operational-grid > div { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: .2rem .55rem; padding: .75rem .8rem; border: 1px dashed #cbd5e1; border-radius: .8rem; background: #f8fafc; }
    .is-operational-grid i { grid-row: 1 / span 2; color: #2563eb; font-size: 1.1rem; }
    .is-operational-grid span { color: #64748b; font-size: .68rem; font-weight: 700; }
    .is-operational-grid strong { justify-self: end; color: #172033; font-size: .8rem; }
    .is-operational-grid small { color: #94a3b8; font-size: .62rem; }

    .is-report-grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(260px, 1fr); gap: .8rem; margin-top: .8rem; }
    .is-report-card { overflow: hidden; border: 1px solid #e2e8f0; border-radius: .9rem; background: #fff; }
    .is-report-card__header { display: flex; align-items: center; justify-content: space-between; gap: 1rem; padding: .8rem .9rem; border-bottom: 1px solid #e2e8f0; background: #f8fafc; }
    .is-report-card__header div { display: flex; flex-direction: column; }
    .is-report-card__header div > span { color: #64748b; font-size: .64rem; font-weight: 750; text-transform: uppercase; letter-spacing: .06em; }
    .is-report-card__header h3 { margin: .1rem 0 0; color: #172033; font-size: .88rem; font-weight: 800; }
    .is-report-card__header > span { padding: .22rem .5rem; border-radius: 999px; color: #1d4ed8; background: #dbeafe; font-size: .66rem; font-weight: 750; white-space: nowrap; }
    .is-table-scroll { overflow-x: auto; }
    .is-report-table { width: 100%; min-width: 650px; border-collapse: collapse; font-size: .74rem; }
    .is-report-table th { padding: .65rem .75rem; color: #64748b; background: #fff; font-size: .64rem; text-align: left; text-transform: uppercase; letter-spacing: .04em; }
    .is-report-table td { padding: .65rem .75rem; border-top: 1px solid #edf2f7; color: #475569; }
    .is-report-table tbody tr:hover { background: #f8fbff; }
    .is-report-table td strong { display: block; color: #1e293b; }
    .is-report-table td small { display: block; margin-top: .1rem; color: #94a3b8; font-size: .63rem; }
    .is-report-table .is-number { text-align: right; font-variant-numeric: tabular-nums; }
    .is-code { display: inline-block; padding: .2rem .4rem; border-radius: .4rem; color: #1d4ed8; background: #eff6ff; font-size: .65rem; font-weight: 750; }
    .is-class-list { max-height: 330px; overflow-y: auto; padding: .35rem .8rem; }
    .is-class-row { display: flex; align-items: center; justify-content: space-between; gap: .75rem; padding: .65rem .1rem; border-bottom: 1px solid #edf2f7; }
    .is-class-row:last-child { border-bottom: 0; }
    .is-class-row div { display: flex; min-width: 0; flex-direction: column; }
    .is-class-row strong { overflow: hidden; color: #1e293b; font-size: .76rem; text-overflow: ellipsis; white-space: nowrap; }
    .is-class-row small { margin-top: .1rem; color: #94a3b8; font-size: .64rem; }
    .is-class-row > span { flex: 0 0 auto; padding: .24rem .45rem; border-radius: .45rem; color: #1d4ed8; background: #eff6ff; font-size: .67rem; font-weight: 800; }
    .is-daily-report { margin-top: .8rem; }

    .is-empty-state { display: flex; min-height: 330px; flex-direction: column; align-items: center; justify-content: center; padding: 2rem; text-align: center; }
    .is-empty-state > span { display: grid; width: 64px; height: 64px; place-items: center; border-radius: 1rem; color: #2563eb; background: #eff6ff; font-size: 1.7rem; }
    .is-empty-state h3 { margin: .85rem 0 .25rem; color: #172033; font-size: 1rem; font-weight: 800; }
    .is-empty-state p { max-width: 480px; margin: 0 0 1rem; color: #64748b; font-size: .78rem; }

    @media (max-width: 1100px) {
        .is-filter-shell { align-items: stretch; flex-direction: column; }
        .is-quick-actions { justify-content: flex-start; }
        .is-kpi-grid, .is-type-grid, .is-operational-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .is-report-grid { grid-template-columns: 1fr; }
    }

    @media (max-width: 760px) {
        .is-profile-hero { align-items: flex-start; flex-direction: column; padding: 1.25rem; }
        .is-profile-hero__summary { width: 100%; min-width: 0; }
        .is-profile-meta { flex-direction: column; gap: .35rem; }
        .is-crosslink { align-items: flex-start; flex-wrap: wrap; }
        .is-crosslink .is-btn { width: 100%; margin-left: 0; }
        .is-filter-form { align-items: stretch; flex-direction: column; }
        .is-date-field { width: 100%; max-width: none; }
        .is-filter-actions { display: grid; grid-template-columns: 1fr 1fr; }
        .is-quick-actions { display: grid; grid-template-columns: 1fr 1fr; }
        .is-quick-link { text-align: center; }
        .is-export-form { grid-column: 1 / -1; }
        .is-export-form .is-btn { width: 100%; }
        .is-tab { flex: 1; justify-content: center; padding-inline: .6rem; }
        .is-range-bar { grid-template-columns: 1fr 1fr; }
        .is-range-title { grid-column: 1 / -1; grid-row: 1; }
        .is-range-nav { grid-row: 2; }
        .is-kpi-grid, .is-type-grid, .is-operational-grid { grid-template-columns: 1fr; }
        .is-statistics-header { align-items: flex-start; flex-direction: column; }
    }

    @media (max-width: 460px) {
        .is-profile-hero__main { align-items: flex-start; }
        .is-profile-avatar { flex-basis: 52px; width: 52px; height: 52px; border-radius: .85rem; font-size: 1.25rem; }
        .is-filter-actions, .is-quick-actions { grid-template-columns: 1fr; }
        .is-export-form { grid-column: auto; }
        .is-tab span:not(.is-tab__count) { font-size: .72rem; }
        .is-tab-panel { padding: .65rem; }
        .is-range-nav span { display: none; }
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const page = document.querySelector('[data-instructor-schedule]');
    if (!page || page.dataset.bound === '1') return;
    page.dataset.bound = '1';

    const tabButtons = Array.from(page.querySelectorAll('[data-schedule-tab]'));
    const tabPanels = Array.from(page.querySelectorAll('[data-schedule-panel]'));
    const activeTabInput = page.querySelector('[data-active-tab-input]');

    function activateTab(tab, updateUrl) {
        const safeTab = tab === 'statistics' ? 'statistics' : 'schedule';

        tabButtons.forEach(function (button) {
            const active = button.dataset.scheduleTab === safeTab;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
            button.setAttribute('tabindex', active ? '0' : '-1');
        });

        tabPanels.forEach(function (panel) {
            panel.hidden = panel.dataset.schedulePanel !== safeTab;
        });

        if (activeTabInput) activeTabInput.value = safeTab;
        page.dataset.activeTab = safeTab;

        if (updateUrl && window.history?.replaceState) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', safeTab);
            window.history.replaceState(window.history.state, '', url);
        }
    }

    tabButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            activateTab(button.dataset.scheduleTab, true);
        });
    });

    page.querySelectorAll('[data-open-statistics]').forEach(function (button) {
        button.addEventListener('click', function () {
            activateTab('statistics', true);
            page.querySelector('.is-tabs')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    page.querySelectorAll('[data-open-schedule]').forEach(function (button) {
        button.addEventListener('click', function () {
            activateTab('schedule', true);
            page.querySelector('.is-tabs')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });

    const filterForm = page.querySelector('#instructor-schedule-filter');
    filterForm?.addEventListener('submit', function (event) {
        const from = filterForm.querySelector('[name="date_from"]')?.value || '';
        const to = filterForm.querySelector('[name="date_to"]')?.value || '';

        if (!from || !to) {
            event.preventDefault();
            window.Notify?.warning?.('Vui lòng chọn đầy đủ Từ ngày và Đến ngày.');
            return;
        }

        if (to < from) {
            event.preventDefault();
            window.Notify?.warning?.('Đến ngày phải lớn hơn hoặc bằng Từ ngày.');
        }
    });

    filterForm?.addEventListener('turbo:submit-start', function () {
        const button = filterForm.querySelector('[data-filter-submit]');
        const label = filterForm.querySelector('[data-filter-label]');
        if (button) {
            button.disabled = true;
            button.setAttribute('aria-busy', 'true');
        }
        if (label) label.textContent = 'Đang tải...';
    });

    filterForm?.addEventListener('turbo:submit-end', function () {
        const button = filterForm.querySelector('[data-filter-submit]');
        const label = filterForm.querySelector('[data-filter-label]');
        if (button) {
            button.disabled = false;
            button.setAttribute('aria-busy', 'false');
        }
        if (label) label.textContent = 'Áp dụng';
    });

    activateTab(page.dataset.activeTab, false);
})();
</script>
@endpush
