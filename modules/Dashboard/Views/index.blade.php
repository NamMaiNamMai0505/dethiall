@extends('layouts.admin')

@section('title', 'Dashboard tổng quan')
@section('page-title', 'Dashboard tổng quan')

@include('partials.chart-js')

@php
    $hour = now('Asia/Ho_Chi_Minh')->hour;
    $greeting = $hour < 11 ? 'Chào buổi sáng' : ($hour < 18 ? 'Chào buổi chiều' : 'Chào buổi tối');
    $roleScopeLabel = $dashboard_scope['is_global']
        ? 'Dữ liệu toàn hệ thống'
        : 'Chỉ hiển thị dữ liệu thuộc '.$dashboard_scope['short_label'];
    $dashboardSectionKeys = ['overview', 'stat_class', 'stat_instructor', 'lms'];
    $requestedDashboardSection = request()->query('section', request()->query('tab', 'overview'));
    $initialDashboardSection = in_array($requestedDashboardSection, $dashboardSectionKeys, true)
        ? $requestedDashboardSection
        : 'overview';
@endphp

@section('content')
<div class="dashboard-page" data-dashboard-page data-default-section="{{ $initialDashboardSection }}">
    <x-breadcrumb :items="[
        ['title' => 'Trang chủ', 'url' => route('dashboard')],
        ['title' => 'Dashboard tổng quan'],
    ]" />

    <section class="dash-hero">
        <span class="dash-hero__orb dash-hero__orb--one"></span>
        <span class="dash-hero__orb dash-hero__orb--two"></span>
        <div class="dash-hero__identity">
            <div class="dash-avatar" aria-hidden="true">
                {{ mb_strtoupper(mb_substr($dashboard_identity['name'], 0, 1)) }}
            </div>
            <div>
                <span class="dash-hero__eyebrow">{{ $greeting }}</span>
                <h1>{{ $dashboard_identity['name'] }}</h1>
                <div class="dash-identity-meta">
                    @if($dashboard_identity['rank'])
                        <span><i class="bi bi-shield-check"></i>{{ $dashboard_identity['rank'] }}</span>
                    @endif
                    <span><i class="bi bi-person-badge"></i>{{ $dashboard_identity['role'] }}</span>
                    @if($dashboard_identity['unit'])
                        <span><i class="bi bi-building"></i>{{ $dashboard_identity['unit'] }}</span>
                    @endif
                </div>
            </div>
        </div>

        <div class="dash-hero__side">
            <div class="dash-hero-date">
                <span>{{ ucfirst(now()->locale('vi')->dayName) }}</span>
                <strong>{{ now()->format('d/m/Y') }}</strong>
                <small>Cập nhật lúc {{ now('Asia/Ho_Chi_Minh')->format('H:i') }}</small>
            </div>
            <div class="dash-hero-scope">
                <i class="bi {{ $dashboard_scope['is_global'] ? 'bi-globe2' : 'bi-shield-lock' }}"></i>
                <div><span>Phạm vi thống kê</span><strong>{{ $roleScopeLabel }}</strong></div>
            </div>
        </div>
    </section>

    <nav class="dash-quick-nav" aria-label="Truy cập nhanh">
        @if(Route::has('training-schedules.calendar'))
            <a href="{{ route('training-schedules.calendar') }}">
                <i class="bi bi-calendar-week"></i><span>Lịch huấn luyện</span><i class="bi bi-arrow-right"></i>
            </a>
        @endif
        @if(Route::has('lms.entry'))
            <a href="{{ route('lms.entry') }}" data-turbo="false">
                <i class="bi bi-mortarboard"></i><span>Cổng LMS</span><i class="bi bi-arrow-right"></i>
            </a>
        @elseif(Route::has('lms.hub'))
            <a href="{{ route('lms.hub') }}" data-turbo="false">
                <i class="bi bi-mortarboard"></i><span>Cổng LMS</span><i class="bi bi-arrow-right"></i>
            </a>
        @endif
        @if(Route::has('standard-hours.my-results.index') && (auth()->user()->can('standard-hours.view') || auth()->user()->can('standard-hours.index')))
            <a href="{{ route('standard-hours.my-results.index') }}">
                <i class="bi bi-hourglass-split"></i><span>Giờ chuẩn GV</span><i class="bi bi-arrow-right"></i>
            </a>
        @endif
        @if(Route::has('settings.dashboard'))
            <a href="{{ route('settings.dashboard') }}">
                <i class="bi bi-sliders"></i><span>Cài đặt Dashboard</span><i class="bi bi-arrow-right"></i>
            </a>
        @endif
    </nav>

    @include('dashboard::dashboard_account')

    <section class="dash-workspace">
        <div class="dash-workspace-heading">
            <div>
                <span>Phân tích chi tiết</span>
                <h2>Trung tâm thống kê Dashboard</h2>
                <p>Các bộ lọc và chức năng hiện có được giữ nguyên trong phạm vi tài khoản.</p>
            </div>
            <span class="dash-workspace-scope"><i class="bi bi-shield-check"></i>{{ $dashboard_scope['label'] }}</span>
        </div>

        @foreach($dashboardSectionKeys as $sectionKey)
            <input type="radio"
                   class="dashboard-section-control"
                   name="dashboard-section"
                   id="dashboardSectionControl{{ \Illuminate\Support\Str::studly($sectionKey) }}"
                   value="{{ $sectionKey }}"
                   data-dashboard-section-control
                   {{ $initialDashboardSection === $sectionKey ? 'checked' : '' }}>
        @endforeach

        <div class="dashboard-section-heading">
            <div>
                <span><i class="bi bi-grid-1x2"></i> Trung tâm thống kê</span>
                <h3>Chọn khu vực cần theo dõi</h3>
                <p>Mỗi khu vực mở ngay bên dưới, không tải lại trang và không làm thay đổi vị trí đang xem.</p>
            </div>
            <span class="dashboard-section-count">4 khu vực</span>
        </div>

        <div class="dashboard-section-grid" role="tablist" aria-label="Các khu vực thống kê Dashboard">
            <label id="dashboardSectionOverview"
                    for="dashboardSectionControlOverview" tabindex="0"
                    role="tab" aria-selected="{{ $initialDashboardSection === 'overview' ? 'true' : 'false' }}"
                    data-dashboard-section-trigger="overview"
                    class="dashboard-section-tile">
                <span class="dashboard-section-tile__icon"><i class="bi bi-speedometer2" aria-hidden="true"></i></span>
                <span class="dashboard-section-tile__content">
                    <strong>Hôm nay</strong>
                    <small>Tổng quan lịch, lớp học và các chỉ số hiện tại.</small>
                    <span>Cập nhật nhanh</span>
                </span>
                <i class="bi bi-arrow-right-short dashboard-section-tile__arrow" aria-hidden="true"></i>
            </label>
            <label id="dashboardSectionClass"
                    for="dashboardSectionControlStatClass" tabindex="0"
                    role="tab" aria-selected="{{ $initialDashboardSection === 'stat_class' ? 'true' : 'false' }}"
                    data-dashboard-section-trigger="stat_class"
                    class="dashboard-section-tile">
                <span class="dashboard-section-tile__icon"><i class="bi bi-grid-3x3-gap" aria-hidden="true"></i></span>
                <span class="dashboard-section-tile__content">
                    <strong>Ngành / Lớp</strong>
                    <small>Phân tích tiến độ theo ngành đào tạo và từng lớp.</small>
                    <span>Theo đào tạo</span>
                </span>
                <i class="bi bi-arrow-right-short dashboard-section-tile__arrow" aria-hidden="true"></i>
            </label>
            <label id="dashboardSectionInstructor"
                    for="dashboardSectionControlStatInstructor" tabindex="0"
                    role="tab" aria-selected="{{ $initialDashboardSection === 'stat_instructor' ? 'true' : 'false' }}"
                    data-dashboard-section-trigger="stat_instructor"
                    class="dashboard-section-tile">
                <span class="dashboard-section-tile__icon"><i class="bi bi-person-badge" aria-hidden="true"></i></span>
                <span class="dashboard-section-tile__content">
                    <strong>{{ $dashboard_scope['type'] === 'instructor' ? 'Giảng dạy của tôi' : 'Khoa / Giảng viên' }}</strong>
                    <small>Khối lượng giảng dạy, loại tiết và các lớp phụ trách.</small>
                    <span>{{ $dashboard_scope['type'] === 'instructor' ? 'Cá nhân' : 'Theo đơn vị' }}</span>
                </span>
                <i class="bi bi-arrow-right-short dashboard-section-tile__arrow" aria-hidden="true"></i>
            </label>
            <label id="dashboardSectionLms"
                    for="dashboardSectionControlLms" tabindex="0"
                    role="tab" aria-selected="{{ $initialDashboardSection === 'lms' ? 'true' : 'false' }}"
                    data-dashboard-section-trigger="lms"
                    class="dashboard-section-tile">
                <span class="dashboard-section-tile__icon"><i class="bi bi-mortarboard" aria-hidden="true"></i></span>
                <span class="dashboard-section-tile__content">
                    <strong>LMS</strong>
                    <small>Chuyên cần, tiến độ học tập và bài đang chờ xử lý.</small>
                    <span>Dạy và học</span>
                </span>
                <i class="bi bi-arrow-right-short dashboard-section-tile__arrow" aria-hidden="true"></i>
            </label>
        </div>

        <div class="dashboard-section-body">
            <div id="dashboardSectionContentOverview" data-dashboard-section-panel="overview" role="tabpanel"
                 aria-labelledby="dashboardSectionOverview" class="dash-legacy-panel">
                @include('dashboard::dashboard_overview')
            </div>

            <div id="dashboardSectionContentClass" data-dashboard-section-panel="stat_class" role="tabpanel"
                 aria-labelledby="dashboardSectionClass" class="dash-legacy-panel">
                @include('dashboard::dashboard_stat_class')
            </div>

            <div id="dashboardSectionContentInstructor" data-dashboard-section-panel="stat_instructor" role="tabpanel"
                 aria-labelledby="dashboardSectionInstructor" class="dash-legacy-panel">
                @include('dashboard::dashboard_stat_instructor')
            </div>

            <div id="dashboardSectionContentLms" data-dashboard-section-panel="lms" role="tabpanel"
                 aria-labelledby="dashboardSectionLms" class="dash-legacy-panel">
                @include('dashboard::dashboard_lms')
            </div>
        </div>
    </section>
</div>
@endsection

@push('styles')
<style>
    .dashboard-page {
        --dash-brand: #4ea1ff;
        --dash-brand-dark: #2563eb;
        --dash-navy: #174766;
        --dash-border: #dbe3ee;
        --dash-muted: #64748b;
        color: #172033;
    }

    .dash-hero {
        position: relative;
        isolation: isolate;
        overflow: hidden;
        display: flex;
        min-height: 190px;
        align-items: center;
        justify-content: space-between;
        gap: 2rem;
        padding: 2rem;
        border: 1px solid rgba(255,255,255,.32);
        border-radius: 1.35rem;
        color: #fff;
        background: linear-gradient(120deg, #123a55 0%, #2563eb 58%, #6eb5ff 100%);
        box-shadow: 0 22px 48px -28px rgba(30, 64, 175, .82);
    }

    .dash-hero__orb { position: absolute; z-index: -1; border-radius: 999px; opacity: .25; }
    .dash-hero__orb--one { width: 290px; height: 290px; top: -190px; right: 22%; background: #fff; }
    .dash-hero__orb--two { width: 230px; height: 230px; bottom: -170px; left: 35%; background: #93c5fd; }
    .dash-hero__identity { display: flex; min-width: 0; align-items: center; gap: 1.15rem; }
    .dash-avatar {
        display: grid;
        flex: 0 0 72px;
        width: 72px;
        height: 72px;
        place-items: center;
        border: 1px solid rgba(255,255,255,.48);
        border-radius: 1.2rem;
        color: #174766;
        background: rgba(255,255,255,.94);
        box-shadow: 0 14px 30px -20px rgba(15,23,42,.85);
        font-size: 1.8rem;
        font-weight: 850;
    }

    .dash-hero__eyebrow { color: #dbeafe; font-size: .72rem; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
    .dash-hero h1 { margin: .15rem 0 0; font-size: clamp(1.55rem, 2.6vw, 2.15rem); font-weight: 850; line-height: 1.18; }
    .dash-identity-meta { display: flex; flex-wrap: wrap; gap: .5rem 1rem; margin-top: .7rem; color: #e0f2fe; font-size: .78rem; }
    .dash-identity-meta span { display: inline-flex; align-items: center; gap: .38rem; }
    .dash-hero__side { display: grid; flex: 0 0 min(420px, 42%); grid-template-columns: .8fr 1.25fr; gap: .7rem; }
    .dash-hero-date, .dash-hero-scope { padding: .85rem .95rem; border: 1px solid rgba(255,255,255,.24); border-radius: 1rem; background: rgba(15,45,74,.3); backdrop-filter: blur(8px); }
    .dash-hero-date { display: flex; flex-direction: column; }
    .dash-hero-date span, .dash-hero-scope span { color: #bfdbfe; font-size: .64rem; font-weight: 750; text-transform: uppercase; letter-spacing: .07em; }
    .dash-hero-date strong { margin-top: .15rem; font-size: 1.05rem; }
    .dash-hero-date small { margin-top: .18rem; color: #dbeafe; font-size: .65rem; }
    .dash-hero-scope { display: flex; align-items: center; gap: .65rem; }
    .dash-hero-scope > i { display: grid; flex: 0 0 36px; width: 36px; height: 36px; place-items: center; border-radius: .7rem; color: #174766; background: rgba(255,255,255,.9); }
    .dash-hero-scope div { display: flex; min-width: 0; flex-direction: column; }
    .dash-hero-scope strong { overflow: hidden; margin-top: .13rem; color: #fff; font-size: .73rem; text-overflow: ellipsis; }

    .dash-quick-nav { display: flex; flex-wrap: wrap; gap: .55rem; margin: .8rem 0 1.2rem; }
    .dash-quick-nav a {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .55rem .72rem;
        border: 1px solid var(--dash-border);
        border-radius: .72rem;
        color: #475569;
        background: rgba(255,255,255,.94);
        box-shadow: 0 8px 18px -18px rgba(23,71,102,.8);
        font-size: .74rem;
        font-weight: 750;
        transition: color .18s ease, border-color .18s ease, box-shadow .2s ease;
    }
    .dash-quick-nav a i:first-child { color: #2563eb; }
    .dash-quick-nav a i:last-child { margin-left: .25rem; color: #94a3b8; }
    .dash-quick-nav a:hover { color: #1d4ed8; border-color: #93c5fd; box-shadow: 0 0 0 3px rgba(78,161,255,.12); }

    .dash-account-section { margin-bottom: 1.2rem; padding: 1.1rem; border: 1px solid var(--dash-border); border-radius: 1.15rem; background: rgba(255,255,255,.96); box-shadow: 0 18px 42px -34px rgba(23,71,102,.72); }
    .dash-section-heading, .dash-workspace-heading { display: flex; align-items: center; justify-content: space-between; gap: 1rem; margin-bottom: .85rem; }
    .dash-section-heading > div, .dash-workspace-heading > div { display: flex; flex-direction: column; }
    .dash-section-heading > div > span, .dash-workspace-heading > div > span { color: #2563eb; font-size: .66rem; font-weight: 800; letter-spacing: .09em; text-transform: uppercase; }
    .dash-section-heading h2, .dash-workspace-heading h2 { margin: .12rem 0 0; color: #172033; font-size: 1.08rem; font-weight: 850; }
    .dash-section-heading p, .dash-workspace-heading p { margin: .16rem 0 0; color: #64748b; font-size: .72rem; }
    .dash-scope-badge, .dash-workspace-scope { display: inline-flex; align-items: center; gap: .4rem; padding: .43rem .65rem; border: 1px solid #bfdbfe; border-radius: 999px; color: #1d4ed8; background: #eff6ff; font-size: .68rem; font-weight: 750; white-space: nowrap; }

    .dash-auto-kpis { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: .7rem; }
    .dash-auto-kpi { display: flex; min-height: 104px; align-items: center; gap: .75rem; padding: .85rem; border: 1px solid #e2e8f0; border-radius: .9rem; background: #fff; }
    .dash-auto-kpi--primary { color: #fff; border-color: #60a5fa; background: linear-gradient(135deg, #174766, #2563eb); }
    .dash-auto-kpi__icon { display: grid; flex: 0 0 40px; width: 40px; height: 40px; place-items: center; border-radius: .75rem; color: #2563eb; background: #eff6ff; font-size: 1rem; }
    .dash-auto-kpi--primary .dash-auto-kpi__icon { color: #fff; background: rgba(255,255,255,.16); }
    .dash-auto-kpi div { display: flex; min-width: 0; flex-direction: column; }
    .dash-auto-kpi div > span { color: #64748b; font-size: .68rem; font-weight: 750; }
    .dash-auto-kpi--primary div > span, .dash-auto-kpi--primary small { color: #dbeafe; }
    .dash-auto-kpi strong { margin: .1rem 0; color: #172033; font-size: 1.5rem; line-height: 1; }
    .dash-auto-kpi--primary strong { color: #fff; }
    .dash-auto-kpi small { color: #94a3b8; font-size: .62rem; }

    .dash-account-grid { display: grid; grid-template-columns: minmax(0, 2fr) minmax(280px, .85fr); gap: .75rem; margin-top: .75rem; }
    .dash-account-main { min-width: 0; }
    .dash-type-strip { display: grid; grid-template-columns: repeat(4, minmax(0,1fr)); gap: .55rem; }
    .dash-type-chip { display: grid; grid-template-columns: auto 1fr auto; align-items: center; gap: .25rem .45rem; padding: .68rem .72rem; border: 1px solid var(--type-border); border-radius: .8rem; color: var(--type-color); background: var(--type-bg); }
    .dash-type-chip > i { grid-row: 1 / span 2; font-size: 1rem; }
    .dash-type-chip span { font-size: .68rem; font-weight: 750; }
    .dash-type-chip strong { justify-self: end; color: #172033; font-size: 1.05rem; }
    .dash-type-chip small { color: #94a3b8; font-size: .6rem; }
    .dash-type-chip--theory { --type-color:#2563eb;--type-border:#bfdbfe;--type-bg:#eff6ff; }
    .dash-type-chip--practice { --type-color:#059669;--type-border:#a7f3d0;--type-bg:#ecfdf5; }
    .dash-type-chip--self { --type-color:#7c3aed;--type-border:#ddd6fe;--type-bg:#f5f3ff; }
    .dash-type-chip--exam { --type-color:#e11d48;--type-border:#fecdd3;--type-bg:#fff1f2; }

    .dash-chart-grid { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: .55rem; margin-top: .55rem; }
    .dash-chart-card, .dash-upcoming-card { overflow: hidden; border: 1px solid #e2e8f0; border-radius: .85rem; background: #fff; }
    .dash-card-heading { display: flex; align-items: center; justify-content: space-between; gap: .7rem; padding: .68rem .75rem; border-bottom: 1px solid #edf2f7; background: #f8fafc; }
    .dash-card-heading div { display: flex; flex-direction: column; }
    .dash-card-heading div span { color: #64748b; font-size: .58rem; font-weight: 750; text-transform: uppercase; letter-spacing: .06em; }
    .dash-card-heading div strong { margin-top: .08rem; color: #1e293b; font-size: .76rem; }
    .dash-card-heading > i { color: #2563eb; }
    .dash-card-heading > a { color: #2563eb; font-size: .65rem; font-weight: 750; }
    .dash-chart-frame { position: relative; height: 210px; padding: .65rem; }
    .dash-upcoming-card { min-width: 0; }
    .dash-upcoming-list { max-height: 371px; overflow-y: auto; padding: .25rem .65rem; }
    .dash-upcoming-row { display: flex; align-items: flex-start; gap: .65rem; padding: .62rem .05rem; border-bottom: 1px solid #edf2f7; }
    .dash-upcoming-row:last-child { border-bottom: 0; }
    .dash-upcoming-date { display: grid; flex: 0 0 38px; width: 38px; height: 42px; place-items: center; align-content: center; border: 1px solid #bfdbfe; border-radius: .65rem; color: #1d4ed8; background: #eff6ff; line-height: 1; }
    .dash-upcoming-date strong { font-size: .85rem; }
    .dash-upcoming-date span { margin-top: .15rem; font-size: .54rem; text-transform: uppercase; }
    .dash-upcoming-copy { display: flex; min-width: 0; flex-direction: column; }
    .dash-upcoming-copy strong { overflow: hidden; color: #1e293b; font-size: .72rem; text-overflow: ellipsis; white-space: nowrap; }
    .dash-upcoming-copy span { margin-top: .18rem; color: #64748b; font-size: .62rem; }
    .dash-upcoming-copy small { margin-top: .17rem; color: #94a3b8; font-size: .6rem; }
    .dash-upcoming-empty { display: flex; min-height: 230px; flex-direction: column; align-items: center; justify-content: center; padding: 1rem; text-align: center; }
    .dash-upcoming-empty i { color: #93c5fd; font-size: 1.8rem; }
    .dash-upcoming-empty strong { margin-top: .5rem; color: #334155; font-size: .78rem; }
    .dash-upcoming-empty span { margin-top: .2rem; color: #94a3b8; font-size: .65rem; }

    .dash-workspace { overflow: hidden; border: 1px solid var(--dash-border); border-radius: 1.15rem; background: rgba(255,255,255,.96); box-shadow: 0 18px 42px -34px rgba(23,71,102,.72); }
    .dash-workspace-heading { margin: 0; padding: 1rem 1.1rem .8rem; }
    .dashboard-section-heading {
        display: flex;
        align-items: flex-end;
        justify-content: space-between;
        gap: 1rem;
        padding: .85rem 1.1rem .65rem;
        border-top: 1px solid #edf2f7;
        background: linear-gradient(180deg, #f8fbff 0%, #fff 100%);
    }
    .dashboard-section-heading > div { display: flex; min-width: 0; flex-direction: column; }
    .dashboard-section-heading > div > span {
        display: inline-flex;
        align-items: center;
        gap: .35rem;
        color: #2563eb;
        font-size: .64rem;
        font-weight: 800;
        letter-spacing: .08em;
        text-transform: uppercase;
    }
    .dashboard-section-heading h3 { margin: .14rem 0 0; color: #172033; font-size: .96rem; font-weight: 850; }
    .dashboard-section-heading p { margin: .16rem 0 0; color: #64748b; font-size: .68rem; }
    .dashboard-section-count {
        display: inline-flex;
        flex: 0 0 auto;
        align-items: center;
        padding: .38rem .62rem;
        border: 1px solid #dbeafe;
        border-radius: 999px;
        color: #2563eb;
        background: #eff6ff;
        font-size: .65rem;
        font-weight: 750;
    }
    .dashboard-section-control {
        position: absolute;
        width: 1px;
        height: 1px;
        overflow: hidden;
        opacity: 0;
        pointer-events: none;
    }
    .dashboard-section-grid {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: .65rem;
        padding: .5rem 1.1rem 1rem;
        border-bottom: 1px solid #e2e8f0;
        background: #fff;
    }
    .dashboard-section-tile {
        position: relative;
        display: grid;
        min-width: 0;
        min-height: 112px;
        grid-template-columns: auto minmax(0, 1fr) auto;
        align-items: start;
        gap: .65rem;
        padding: .82rem;
        border: 1px solid #e2e8f0;
        border-radius: .9rem;
        color: #475569;
        background: linear-gradient(145deg, #fff 0%, #f8fafc 100%);
        box-shadow: 0 10px 24px -24px rgba(23,71,102,.85);
        text-align: left;
        cursor: pointer;
        transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease, background .18s ease;
    }
    .dashboard-section-tile:hover {
        transform: translateY(-2px);
        border-color: #93c5fd;
        background: linear-gradient(145deg, #fff 0%, #eff6ff 100%);
        box-shadow: 0 15px 28px -22px rgba(37,99,235,.72);
    }
    .dashboard-section-tile.is-active,
    #dashboardSectionControlOverview:checked ~ .dashboard-section-grid [for="dashboardSectionControlOverview"],
    #dashboardSectionControlStatClass:checked ~ .dashboard-section-grid [for="dashboardSectionControlStatClass"],
    #dashboardSectionControlStatInstructor:checked ~ .dashboard-section-grid [for="dashboardSectionControlStatInstructor"],
    #dashboardSectionControlLms:checked ~ .dashboard-section-grid [for="dashboardSectionControlLms"] {
        border-color: #60a5fa;
        color: #1d4ed8;
        background: linear-gradient(145deg, #eff6ff 0%, #fff 100%);
        box-shadow: 0 0 0 2px rgba(78,161,255,.12), 0 16px 30px -23px rgba(37,99,235,.8);
    }
    .dashboard-section-tile:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(78,161,255,.22); }
    .dashboard-section-tile__icon {
        display: grid;
        width: 38px;
        height: 38px;
        place-items: center;
        border: 1px solid #dbeafe;
        border-radius: .72rem;
        color: #2563eb;
        background: #eff6ff;
        font-size: .95rem;
        transition: color .18s ease, background .18s ease, border-color .18s ease;
    }
    .dashboard-section-tile.is-active .dashboard-section-tile__icon,
    #dashboardSectionControlOverview:checked ~ .dashboard-section-grid [for="dashboardSectionControlOverview"] .dashboard-section-tile__icon,
    #dashboardSectionControlStatClass:checked ~ .dashboard-section-grid [for="dashboardSectionControlStatClass"] .dashboard-section-tile__icon,
    #dashboardSectionControlStatInstructor:checked ~ .dashboard-section-grid [for="dashboardSectionControlStatInstructor"] .dashboard-section-tile__icon,
    #dashboardSectionControlLms:checked ~ .dashboard-section-grid [for="dashboardSectionControlLms"] .dashboard-section-tile__icon {
        border-color: #3b82f6;
        color: #fff;
        background: linear-gradient(135deg, #174766, #3b82f6);
    }
    .dashboard-section-tile__content { display: flex; min-width: 0; flex-direction: column; }
    .dashboard-section-tile__content strong { overflow: hidden; color: #1e293b; font-size: .76rem; font-weight: 850; text-overflow: ellipsis; white-space: nowrap; }
    .dashboard-section-tile__content small { margin-top: .18rem; color: #64748b; font-size: .62rem; line-height: 1.42; }
    .dashboard-section-tile__content > span {
        align-self: flex-start;
        margin-top: .5rem;
        padding: .2rem .4rem;
        border-radius: 999px;
        color: #64748b;
        background: #f1f5f9;
        font-size: .56rem;
        font-weight: 750;
    }
    .dashboard-section-tile.is-active .dashboard-section-tile__content > span,
    #dashboardSectionControlOverview:checked ~ .dashboard-section-grid [for="dashboardSectionControlOverview"] .dashboard-section-tile__content > span,
    #dashboardSectionControlStatClass:checked ~ .dashboard-section-grid [for="dashboardSectionControlStatClass"] .dashboard-section-tile__content > span,
    #dashboardSectionControlStatInstructor:checked ~ .dashboard-section-grid [for="dashboardSectionControlStatInstructor"] .dashboard-section-tile__content > span,
    #dashboardSectionControlLms:checked ~ .dashboard-section-grid [for="dashboardSectionControlLms"] .dashboard-section-tile__content > span {
        color: #1d4ed8;
        background: #dbeafe;
    }
    .dashboard-section-tile__arrow { align-self: center; color: #94a3b8; font-size: 1rem; transition: transform .18s ease, color .18s ease; }
    .dashboard-section-tile:hover .dashboard-section-tile__arrow,
    .dashboard-section-tile.is-active .dashboard-section-tile__arrow { transform: translateX(2px); color: #2563eb; }
    .dashboard-section-body { padding: .9rem; }
    .dashboard-section-body [data-dashboard-section-panel] { display: none; }
    #dashboardSectionControlOverview:checked ~ .dashboard-section-body [data-dashboard-section-panel="overview"],
    #dashboardSectionControlStatClass:checked ~ .dashboard-section-body [data-dashboard-section-panel="stat_class"],
    #dashboardSectionControlStatInstructor:checked ~ .dashboard-section-body [data-dashboard-section-panel="stat_instructor"],
    #dashboardSectionControlLms:checked ~ .dashboard-section-body [data-dashboard-section-panel="lms"] {
        display: block;
    }

    /* Đồng bộ các card/form/table cũ trong bốn tab với giao diện mới. */
    .dashboard-page .dash-legacy-panel .bg-white.rounded-xl {
        border: 1px solid #e2e8f0 !important;
        border-radius: .9rem !important;
        box-shadow: 0 10px 26px -25px rgba(23,71,102,.72) !important;
        background: #fff !important;
    }
    .dashboard-page .dash-legacy-panel .border-l-4 { border-left-width: 3px !important; }
    .dashboard-page .dash-legacy-panel table thead { background: #f8fafc !important; }
    .dashboard-page .dash-legacy-panel table th { color: #64748b; font-size: .65rem; letter-spacing: .04em; }
    .dashboard-page .dash-legacy-panel table td { font-size: .74rem; }
    .dashboard-page .dash-legacy-panel input,
    .dashboard-page .dash-legacy-panel select,
    .dashboard-page .dash-legacy-panel .ts-control { border-radius: .65rem !important; }
    .dashboard-page .dash-legacy-panel canvas { max-width: 100%; }

    /* Hệ thống hiển thị thống kê: rõ lớp thông tin, dễ quét KPI và bảng số liệu. */
    .dashboard-stat-view {
        --stat-accent: #2563eb;
        --stat-accent-soft: #eff6ff;
        --stat-accent-border: #bfdbfe;
        color: #1e293b;
    }
    .dashboard-stat-view--class {
        --stat-accent: #0284c7;
        --stat-accent-soft: #f0f9ff;
        --stat-accent-border: #bae6fd;
    }
    .dashboard-stat-view--instructor {
        --stat-accent: #4f46e5;
        --stat-accent-soft: #eef2ff;
        --stat-accent-border: #c7d2fe;
    }
    .dashboard-page .dashboard-stat-banner {
        position: relative;
        overflow: hidden;
        border: 1px solid #bfdbfe !important;
        border-left: 4px solid #2563eb !important;
        background: linear-gradient(120deg, #f8fbff 0%, #eff6ff 62%, #fff 100%) !important;
        box-shadow: 0 16px 34px -29px rgba(37, 99, 235, .9) !important;
    }
    .dashboard-stat-banner::after {
        position: absolute;
        top: -70px;
        right: -42px;
        width: 150px;
        height: 150px;
        border-radius: 999px;
        background: rgba(96, 165, 250, .1);
        content: "";
        pointer-events: none;
    }
    .dashboard-stat-banner h2 { color: #173b66 !important; font-size: 1.08rem !important; }
    .dashboard-stat-banner h2 i {
        display: grid;
        width: 34px;
        height: 34px;
        place-items: center;
        border-radius: .65rem;
        color: #fff !important;
        background: linear-gradient(135deg, #174766, #3b82f6);
        font-size: .9rem;
    }
    .dashboard-page .dashboard-stat-filter {
        position: relative;
        overflow: visible;
        border: 1px solid var(--stat-accent-border) !important;
        border-top: 3px solid var(--stat-accent) !important;
        background: linear-gradient(180deg, var(--stat-accent-soft) 0%, #fff 54%) !important;
        box-shadow: 0 16px 34px -31px rgba(15, 71, 112, .9) !important;
    }
    .dashboard-stat-filter > h3 {
        margin-bottom: .9rem !important;
        padding-bottom: .7rem;
        border-bottom: 1px solid var(--stat-accent-border);
        color: #1e3a5f !important;
        font-size: .82rem !important;
        font-weight: 850 !important;
    }
    .dashboard-stat-filter > h3 i {
        display: grid;
        width: 30px;
        height: 30px;
        place-items: center;
        border-radius: .58rem;
        color: var(--stat-accent) !important;
        background: #fff;
        box-shadow: 0 0 0 1px var(--stat-accent-border);
        font-size: .78rem;
    }
    .dashboard-stat-filter label {
        color: #334155 !important;
        font-size: .7rem !important;
        font-weight: 800 !important;
    }
    .dashboard-stat-filter .ts-control,
    .dashboard-stat-filter .date-input-control {
        min-height: 42px;
        border-color: #cbd5e1 !important;
        background: #fff !important;
        box-shadow: 0 5px 12px -12px rgba(15, 23, 42, .6);
    }
    .dashboard-stat-filter .ts-wrapper.focus .ts-control,
    .dashboard-stat-filter .date-input-control:focus-within {
        border-color: #60a5fa !important;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, .11) !important;
    }
    .dashboard-stat-filter button[type="submit"] {
        border: 1px solid rgba(29, 78, 216, .28);
        background: linear-gradient(135deg, #174766, #2563eb) !important;
        box-shadow: 0 10px 20px -15px rgba(37, 99, 235, .95) !important;
        transition: transform .18s ease, filter .18s ease, box-shadow .18s ease;
    }
    .dashboard-stat-filter button[type="submit"]:hover {
        transform: translateY(-1px);
        filter: brightness(1.06);
        box-shadow: 0 13px 24px -16px rgba(37, 99, 235, .95) !important;
    }
    .dashboard-stat-filter button[type="button"] { transition: color .18s ease, border-color .18s ease, background .18s ease; }
    .dashboard-stat-filter button[type="button"]:hover { color: #1d4ed8 !important; border-color: #93c5fd !important; background: #eff6ff !important; }
    .dashboard-page .dashboard-stat-context {
        border: 1px solid #bfdbfe !important;
        border-left: 4px solid #2563eb !important;
        background: linear-gradient(115deg, #eff6ff 0%, #fff 72%) !important;
    }
    .dashboard-stat-context > div > div {
        min-width: 0;
        padding: .65rem;
        border: 1px solid #e2e8f0;
        border-radius: .72rem;
        background: rgba(255, 255, 255, .86);
    }
    .dashboard-page .dashboard-stat-kpi {
        --kpi-color: #2563eb;
        position: relative;
        overflow: hidden;
        min-height: 116px;
        border: 1px solid #e2e8f0 !important;
        border-left: 4px solid var(--kpi-color) !important;
        background: linear-gradient(145deg, #fff 0%, #f8fafc 100%) !important;
        box-shadow: 0 13px 28px -27px rgba(15, 71, 112, .95) !important;
        transition: transform .18s ease, border-color .18s ease, box-shadow .18s ease;
    }
    .dashboard-stat-kpi-grid .dashboard-stat-kpi:nth-child(2) { --kpi-color: #059669; }
    .dashboard-stat-kpi-grid .dashboard-stat-kpi:nth-child(3) { --kpi-color: #7c3aed; }
    .dashboard-stat-kpi-grid .dashboard-stat-kpi:nth-child(4) { --kpi-color: #ea580c; }
    .dashboard-stat-kpi::after {
        position: absolute;
        top: -24px;
        right: -24px;
        width: 76px;
        height: 76px;
        border-radius: 999px;
        background: color-mix(in srgb, var(--kpi-color) 9%, transparent);
        content: "";
        pointer-events: none;
    }
    .dashboard-stat-kpi:hover {
        transform: translateY(-2px);
        border-color: color-mix(in srgb, var(--kpi-color) 35%, #e2e8f0) !important;
        box-shadow: 0 17px 31px -25px color-mix(in srgb, var(--kpi-color) 55%, transparent) !important;
    }
    .dashboard-stat-kpi > p:first-child,
    .dashboard-stat-kpi > div > p:first-child {
        color: #64748b !important;
        font-size: .68rem !important;
        font-weight: 800 !important;
        letter-spacing: .025em;
    }
    .dashboard-stat-kpi .text-3xl {
        color: #172033 !important;
        font-size: 1.72rem !important;
        font-weight: 880 !important;
        line-height: 1.1;
    }
    .dashboard-page .dashboard-stat-chart-card {
        border: 1px solid #dbe3ee !important;
        background: linear-gradient(180deg, #fff 0%, #fbfdff 100%) !important;
        box-shadow: 0 16px 34px -31px rgba(23, 71, 102, .95) !important;
    }
    .dashboard-stat-chart-card > h3 {
        margin-bottom: .75rem !important;
        padding-bottom: .68rem;
        border-bottom: 1px solid #edf2f7;
        color: #24364b !important;
        font-size: .78rem !important;
        font-weight: 850 !important;
    }
    .dashboard-stat-chart-card > h3 i {
        display: grid;
        width: 28px;
        height: 28px;
        place-items: center;
        border-radius: .55rem;
        background: #eff6ff;
        font-size: .75rem;
    }
    .dashboard-stat-chart-card canvas { filter: saturate(.94) contrast(1.02); }
    .dashboard-page .dashboard-stat-table {
        border: 1px solid #dbe3ee !important;
        box-shadow: 0 16px 34px -31px rgba(23, 71, 102, .95) !important;
    }
    .dashboard-stat-table > div:first-child {
        background: linear-gradient(180deg, #f8fbff, #fff);
    }
    .dashboard-stat-table > div:first-child h3 { color: #24364b !important; font-size: .8rem !important; font-weight: 850 !important; }
    .dashboard-stat-table table { border-collapse: separate; border-spacing: 0; }
    .dashboard-stat-table table thead {
        position: sticky;
        top: 0;
        z-index: 2;
        background: #f1f5f9 !important;
    }
    .dashboard-stat-table table th {
        padding-top: .72rem !important;
        padding-bottom: .72rem !important;
        color: #475569 !important;
        font-weight: 850 !important;
        white-space: nowrap;
    }
    .dashboard-stat-table table tbody tr { transition: background-color .15s ease; }
    .dashboard-stat-table table tbody tr:nth-child(even) { background: #fbfdff; }
    .dashboard-stat-table table tbody tr:hover { background: #eff6ff !important; }
    .dashboard-stat-table table td { color: #334155; border-bottom-color: #edf2f7; }
    .dashboard-page .dashboard-stat-empty,
    .dashboard-page .dashboard-stat-loading {
        border: 1px dashed #bfdbfe !important;
        background: linear-gradient(145deg, #f8fbff 0%, #fff 100%) !important;
        box-shadow: none !important;
    }
    .dashboard-stat-empty > i { color: #93c5fd !important; }
    .dashboard-stat-empty h3 { color: #334155 !important; font-size: .9rem !important; font-weight: 850 !important; }
    .dashboard-stat-empty p,
    .dashboard-stat-loading p { color: #64748b !important; font-size: .7rem !important; }

    .dash-fixed-instructor {
        display: flex;
        align-items: center;
        gap: .85rem;
        padding: .85rem;
        border: 1px solid #bfdbfe;
        border-radius: .9rem;
        background: linear-gradient(120deg, #f8fbff, #eff6ff);
    }
    .dash-fixed-instructor__avatar {
        display: grid;
        flex: 0 0 48px;
        width: 48px;
        height: 48px;
        place-items: center;
        border-radius: .8rem;
        color: #fff;
        background: linear-gradient(135deg, #174766, #3b82f6);
        box-shadow: 0 10px 20px -14px rgba(37,99,235,.85);
        font-size: 1.15rem;
        font-weight: 850;
    }
    .dash-fixed-instructor__copy { display: flex; min-width: 0; flex: 1; flex-direction: column; }
    .dash-fixed-instructor__copy > span { color: #64748b; font-size: .62rem; font-weight: 750; letter-spacing: .05em; text-transform: uppercase; }
    .dash-fixed-instructor__copy > strong { overflow: hidden; margin-top: .08rem; color: #172033; font-size: .92rem; text-overflow: ellipsis; white-space: nowrap; }
    .dash-fixed-instructor__copy > div { display: flex; flex-wrap: wrap; gap: .35rem .8rem; margin-top: .28rem; }
    .dash-fixed-instructor__copy > div span { display: inline-flex; align-items: center; gap: .3rem; color: #64748b; font-size: .65rem; }
    .dash-fixed-instructor__copy > div i { color: #3b82f6; }
    .dash-fixed-instructor__lock { display: inline-flex; flex: 0 0 auto; align-items: center; gap: .38rem; padding: .4rem .6rem; border: 1px solid #93c5fd; border-radius: 999px; color: #1d4ed8; background: #fff; font-size: .64rem; font-weight: 750; }
    .dash-personal-period { display: grid; grid-template-columns: minmax(0,1fr) minmax(220px,.45fr); align-items: end; gap: 1rem; padding: .75rem .85rem; border: 1px solid #e2e8f0; border-radius: .8rem; background: #f8fafc; }
    .dash-personal-period > div:first-child { display: flex; flex-direction: column; align-self: center; }
    .dash-personal-period > div:first-child > span { color: #64748b; font-size: .62rem; font-weight: 750; text-transform: uppercase; letter-spacing: .05em; }
    .dash-personal-period > div:first-child > strong { margin-top: .16rem; color: #334155; font-size: .78rem; }

    @media (max-width: 1180px) {
        .dash-auto-kpis, .dash-type-strip { grid-template-columns: repeat(2,minmax(0,1fr)); }
        .dash-account-grid { grid-template-columns: 1fr; }
        .dash-upcoming-list { max-height: 290px; }
        .dashboard-section-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    }
    @media (max-width: 820px) {
        .dash-hero { align-items: flex-start; flex-direction: column; padding: 1.3rem; }
        .dash-hero__side { width: 100%; flex-basis: auto; }
        .dash-chart-grid { grid-template-columns: 1fr; }
        .dash-section-heading, .dash-workspace-heading, .dashboard-section-heading { align-items: flex-start; flex-direction: column; }
        .dash-personal-period { grid-template-columns: 1fr; }
    }
    @media (max-width: 560px) {
        .dash-avatar { flex-basis: 54px; width:54px;height:54px;border-radius:.9rem;font-size:1.3rem; }
        .dash-identity-meta { flex-direction:column;gap:.32rem; }
        .dash-hero__side { grid-template-columns: 1fr; }
        .dash-quick-nav { display:grid;grid-template-columns:1fr 1fr; }
        .dash-quick-nav a { justify-content:flex-start; }
        .dash-quick-nav a i:last-child { margin-left:auto; }
        .dash-auto-kpis, .dash-type-strip { grid-template-columns: 1fr; }
        .dash-account-section { padding:.75rem; }
        .dashboard-section-grid { grid-template-columns: 1fr; padding: .45rem .7rem .75rem; }
        .dashboard-section-heading { padding: .75rem .7rem .55rem; }
        .dashboard-section-tile { min-height: 96px; }
        .dashboard-section-body { padding:.55rem; }
        .dash-fixed-instructor { align-items:flex-start; flex-wrap:wrap; }
        .dash-fixed-instructor__lock { margin-left:63px; }
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const controllerVersion = '2026-07-30.4';

    if (!window.DashboardSections || window.DashboardSections.version !== controllerVersion) {
        const autoSubmittedForms = new WeakSet();
        const sectionKeys = ['overview', 'stat_class', 'stat_instructor', 'lms'];

        function controls(page) {
            return {
                triggers: Array.from(page.querySelectorAll('[data-dashboard-section-trigger]')),
                panels: Array.from(page.querySelectorAll('[data-dashboard-section-panel]')),
                radios: Array.from(page.querySelectorAll('[data-dashboard-section-control]')),
            };
        }

        function refreshSectionControls(panel) {
            if (!panel) return;
            if (typeof window.initTomSelects === 'function') window.initTomSelects(panel);
            if (typeof window.initDateInputs === 'function') window.initDateInputs(panel);

            requestAnimationFrame(function () {
                if (typeof Chart === 'undefined' || typeof Chart.getChart !== 'function') return;

                panel.querySelectorAll('canvas').forEach(function (canvas) {
                    const chart = Chart.getChart(canvas);
                    if (chart) chart.resize();
                });
            });

            const autoForm = panel.querySelector('[data-dashboard-auto-submit="1"]');
            if (autoForm && !autoSubmittedForms.has(autoForm)) {
                autoSubmittedForms.add(autoForm);
                requestAnimationFrame(function () {
                    if (autoForm.isConnected) autoForm.requestSubmit();
                });
            }
        }

        function show(section, updateUrl, page) {
            page = page || document.querySelector('[data-dashboard-page]');
            if (!page) return;

            const sectionControls = controls(page);
            const safeSection = sectionKeys.includes(section) ? section : 'overview';

            sectionControls.radios.forEach(function (radio) {
                radio.checked = radio.value === safeSection;
            });
            sectionControls.triggers.forEach(function (trigger) {
                const active = trigger.dataset.dashboardSectionTrigger === safeSection;
                trigger.classList.toggle('is-active', active);
                trigger.setAttribute('aria-selected', active ? 'true' : 'false');
                trigger.setAttribute('tabindex', active ? '0' : '-1');
            });

            sectionControls.panels.forEach(function (panel) {
                const active = panel.dataset.dashboardSectionPanel === safeSection;
                panel.setAttribute('aria-hidden', active ? 'false' : 'true');
            });

            if (updateUrl && window.history && typeof window.history.replaceState === 'function') {
                const url = new URL(window.location.href);
                url.searchParams.delete('tab');
                url.searchParams.set('section', safeSection);
                window.history.replaceState(window.history.state, '', url);
            }

            page.dataset.activeSection = safeSection;
            const activePanel = sectionControls.panels.find(function (panel) {
                return panel.dataset.dashboardSectionPanel === safeSection;
            });

            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    refreshSectionControls(activePanel);
                });
            });
        }

        function boot() {
            const page = document.querySelector('[data-dashboard-page]');
            if (!page) return;

            const url = new URL(window.location.href);
            const requestedSection = url.searchParams.get('section')
                || url.searchParams.get('tab')
                || page.dataset.defaultSection
                || 'overview';
            show(requestedSection, false, page);
        }

        document.addEventListener('change', function (event) {
            const radio = event.target.closest('[data-dashboard-page] [data-dashboard-section-control]');
            if (!radio || !radio.checked) return;
            show(radio.value, true, radio.closest('[data-dashboard-page]'));
        });
        document.addEventListener('keydown', function (event) {
            const trigger = event.target.closest('[data-dashboard-page] [data-dashboard-section-trigger]');
            if (!trigger || !['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) return;

            const page = trigger.closest('[data-dashboard-page]');
            const triggers = controls(page).triggers;
            const currentIndex = triggers.indexOf(trigger);
            const delta = ['ArrowRight', 'ArrowDown'].includes(event.key) ? 1 : -1;
            const next = triggers[(currentIndex + delta + triggers.length) % triggers.length];

            event.preventDefault();
            next.focus();
            document.getElementById(next.htmlFor)?.click();
        });
        document.addEventListener('turbo:load', boot);
        document.addEventListener('DOMContentLoaded', boot);

        window.DashboardSections = {
            version: controllerVersion,
            boot: boot,
            show: show,
        };
    }

    window.dashboardShowTab = function (tab) {
        window.DashboardSections.show(tab, true);
    };
    window.DashboardSections.boot();
})();
</script>
@endpush
