<!DOCTYPE html>
<html lang="vi" class="lms-html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="turbo-prefetch" content="false">
    <meta name="color-scheme" content="light">
    <meta name="theme-color" content="#0f766e">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <title>@yield('title', 'LMS') · {{ \Modules\Lms\Support\LmsAccess::isInstructorUser() ? 'Giảng dạy' : 'Học tập' }} CDHC2</title>
    @include('partials.favicon')
    <style>
        /*
         * Scroll LMS: luôn cuộn trên document (html).
         * Tránh height:100%/overflow:hidden (kiểu admin) và style inline dính sau modal/Turbo.
         */
        html.lms-html {
            margin: 0 !important;
            min-height: 100% !important;
            height: auto !important;
            max-height: none !important;
            overflow-x: hidden !important;
            overflow-y: auto !important;
            overscroll-behavior-y: auto;
            background: #f0fdfa !important;
            color: #1f2937;
        }
        html.lms-html body,
        body.lms-shell {
            margin: 0 !important;
            min-height: 100% !important;
            height: auto !important;
            max-height: none !important;
            /* Khong duoc pair hidden(x)+visible(y): CSS Overflow spec ep truc con
               lai (y) thanh auto khi truc kia khac visible, khien body vo tinh
               tro thanh hop cuon (auto ca 2 truc) du no khong thuc su cuon -
               pha het position:sticky trong toan bo khung LMS hoc vien. html da
               tu overflow-x:hidden roi nen body khong can dat lai. */
            overflow: visible !important;
            position: static !important;
            font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Be Vietnam Pro", sans-serif;
            color: #1f2937;
            background:
                radial-gradient(1100px 520px at 8% -8%, rgba(13, 148, 136, 0.16), transparent 55%),
                radial-gradient(900px 480px at 96% 0%, rgba(20, 184, 166, 0.1), transparent 50%),
                linear-gradient(180deg, #faf8f4 0%, #f0fdfa 45%, #eef2f7 100%) !important;
        }
        /* Khóa scroll khi modal — class, không inline (dễ dọn bằng Turbo) */
        html.lms-html.lms-scroll-lock,
        html.lms-html.lms-scroll-lock body,
        body.lms-shell.lms-scroll-lock {
            overflow: hidden !important;
            overscroll-behavior: none;
        }
        .lms-main {
            height: auto !important;
            max-height: none !important;
            min-height: 0 !important;
            overflow: visible !important;
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="{{ asset('build/vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" crossorigin="anonymous" referrerpolicy="no-referrer">
    @include('partials.lms-theme')
    {{-- Tom Select + Flatpickr cho form LMS (dropdown + chọn ngày) --}}
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.4.1/dist/css/tom-select.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    @stack('styles')
</head>
@php
    $u = auth()->user();
    $isInstructor = $u && \Modules\Lms\Support\LmsAccess::isInstructorUser($u);
    $isStudent = $u && \Modules\Lms\Support\LmsAccess::isStudentUser($u);
    $teachMode = \Modules\Lms\Support\LmsAccess::isTeachMode() || request()->routeIs('lms.teach.*');
    $homeRoute = $isInstructor
        ? route('lms.teach.home')
        : route('lms.learn.home');
@endphp
<body class="lms-shell {{ $teachMode || $isInstructor ? 'lms-shell-teach' : '' }}">
    <header class="lms-top" id="lms-header" data-turbo-permanent>
        <div class="lms-top-inner">
            <a href="{{ $homeRoute }}" class="lms-brand">
                <span class="lms-brand-badge">
                    <img src="{{ asset('images/brand-logo.png') }}?v=2"
                         width="40" height="40"
                         alt="Logo CDHC2">
                </span>
                <span class="lms-brand-text">
                    @if($isInstructor)
                        <strong>Cổng giảng dạy</strong>
                        <small>CDHC2 · LMS GV</small>
                    @else
                        <strong>Cổng học tập</strong>
                        <small>CDHC2 · LMS</small>
                    @endif
                </span>
            </a>
            <nav class="lms-nav">
                @if($isInstructor)
                    <a href="{{ route('lms.teach.home') }}"
                       class="{{ request()->routeIs('lms.teach.home') || ($teachMode && request()->routeIs('lms.learn.courses.*')) ? 'is-active' : '' }}"
                       data-lms-nav="teach-home">
                        <i class="bi bi-easel" style="margin-right:0.3rem"></i>Khóa dạy
                    </a>
                    <a href="{{ route('lms.teach.schedule') }}"
                       class="{{ request()->routeIs('lms.teach.schedule') ? 'is-active' : '' }}"
                       data-lms-nav="schedule">
                        <i class="bi bi-calendar3" style="margin-right:0.3rem"></i>Lịch dạy
                    </a>
                    @if(Route::has('grades.hub'))
                        <a href="{{ route('grades.hub') }}"
                           class="{{ request()->routeIs('grades.*') ? 'is-active' : '' }}"
                           data-turbo="false"
                           data-lms-nav="grades">
                            <i class="bi bi-clipboard-data" style="margin-right:0.3rem"></i>Quản lý điểm
                        </a>
                    @endif
                    @if(Route::has('export-templates.portal.index') && ($u->can('export-templates.index') || $u->isSuperAdmin() || $u->isManager() || $u->isInstructor()))
                        <a href="{{ route('export-templates.portal.index', ['portal' => 'lms']) }}"
                           class="{{ request()->routeIs('export-templates.portal.*') && request()->route('portal') === 'lms' ? 'is-active' : '' }}"
                           data-turbo="false"
                           data-lms-nav="export-templates">
                            <i class="bi bi-file-earmark-ruled" style="margin-right:0.3rem"></i>Quản lý mẫu xuất
                        </a>
                    @endif
                    @if($u->can('standard-hours.index') || Route::has('lms.teach.standard-hours.hub'))
                        <a href="{{ Route::has('lms.teach.standard-hours.my-results.index') ? route('lms.teach.standard-hours.my-results.index') : route('lms.teach.standard-hours.hub') }}"
                           class="{{ request()->routeIs('lms.teach.standard-hours.*') ? 'is-active' : '' }}"
                           data-lms-nav="standard-hours"
                           title="Giờ chuẩn">
                            <i class="bi bi-hourglass-split" style="margin-right:0.3rem"></i>Giờ chuẩn
                        </a>
                    @endif
                    @if($u->can('dashboards.index'))
                        <a href="{{ route('dashboard') }}"
                           data-turbo="false"
                           data-lms-nav="dashboard">
                            <i class="bi bi-speedometer2" style="margin-right:0.3rem"></i>Dashboard
                        </a>
                    @endif
                @else
                    <a href="{{ route('lms.learn.home') }}"
                       class="{{ request()->routeIs('lms.learn.home') ? 'is-active' : '' }}"
                       data-lms-nav="home">
                        <i class="bi bi-journal-bookmark" style="margin-right:0.3rem"></i>Khóa
                    </a>
                    @if($isStudent)
                        <a href="{{ route('lms.learn.schedule') }}"
                           class="{{ request()->routeIs('lms.learn.schedule') ? 'is-active' : '' }}"
                           data-lms-nav="schedule">
                            <i class="bi bi-calendar3" style="margin-right:0.3rem"></i>Lịch học
                        </a>
                    @endif
                @endif

                <a href="{{ route('lms.learn.profile') }}"
                   class="{{ request()->routeIs('lms.learn.profile*') ? 'is-active' : '' }}"
                   data-lms-nav="profile">
                    <i class="bi bi-person" style="margin-right:0.3rem"></i>Cá nhân
                </a>
                @if($isInstructor && Route::has('settings.lms'))
                    <a href="{{ route('settings.lms') }}"
                       class="{{ request()->routeIs('settings.lms') ? 'is-active' : '' }}"
                       data-lms-nav="settings">
                        <i class="bi bi-gear" style="margin-right:0.3rem"></i>Cài đặt
                    </a>
                @endif

                <div class="relative" id="notification-root" data-turbo-permanent>
                    <button type="button"
                            data-notification-trigger
                            class="lms-nav-icon-btn relative"
                            aria-label="Thông báo"
                            id="notification-bell">
                        <i class="bi bi-bell"></i>
                        <span id="notification-badge" data-count="0" class="lms-notif-badge"></span>
                    </button>
                    <div id="notification-panel"
                         class="absolute right-0 top-full mt-2 rounded-xl shadow-xl z-[300] hidden overflow-hidden bg-white border border-slate-200"
                         style="width:22rem;max-height:24rem">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 bg-slate-50/80">
                            <div class="flex items-center gap-2 text-slate-800">
                                <i class="bi bi-bell text-teal-700"></i>
                                <span class="text-sm font-semibold">Thông báo</span>
                            </div>
                            <button type="button" id="notification-mark-all"
                                    class="text-xs text-teal-700 hover:text-teal-900 font-medium">
                                Đánh dấu đã đọc
                            </button>
                        </div>
                        <div id="notification-list" class="notification-list overflow-y-auto" style="max-height:18rem"></div>
                    </div>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="inline" data-turbo="false">
                    @csrf
                    <button type="submit">
                        <i class="bi bi-box-arrow-right" style="margin-right:0.3rem"></i>Thoát
                    </button>
                </form>
            </nav>
        </div>
    </header>

    <main class="lms-main" id="lms-main">
        {{-- Flash → popup toast (Notify), không banner inline --}}
        @if(session('success') || session('error') || session('warning') || session('info') || $errors->any())
            <script type="application/json" id="session-flash-payload">
                @php
                    $flashPayload = [
                        'success' => session('success'),
                        'error' => session('error'),
                        'warning' => session('warning'),
                        'info' => session('info'),
                        'errors' => $errors->any() ? $errors->all() : [],
                    ];
                @endphp
                {!! json_encode($flashPayload, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}
            </script>
        @endif

        @yield('content')
    </main>

    <div id="admin-notifications" data-turbo-permanent style="display:contents">
        @include('partials.notifications')
    </div>
    @include('partials.ui-motion')
    @include('partials.notification-center')
    @include('partials.turbo-lms')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/vn.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    @include('partials.tom-select-init')
    @stack('scripts')
    <script>
    (function () {
        /**
         * Tom Select dùng initializer chung của hệ thống; LMS chỉ giữ theme riêng.
         * Flatpickr tiếp tục được khởi tạo tại shell này.
         */
        function initLmsWidgets(root) {
            root = root || document;
            if (typeof window.initTomSelects === 'function') {
                window.initTomSelects(root);
            }
            if (window.flatpickr) {
                // Date: class hoặc input type=date trong shell LMS
                root.querySelectorAll(
                    'input.flatpickr-date, input[type="date"]:not([data-native-date])'
                ).forEach(function (el) {
                    if (el._flatpickr) return;
                    flatpickr(el, { locale: 'vn', dateFormat: 'Y-m-d', allowInput: true });
                });
                root.querySelectorAll(
                    'input.flatpickr-datetime, input[type="datetime-local"]:not([data-native-date])'
                ).forEach(function (el) {
                    if (el._flatpickr) return;
                    // Convert datetime-local → text-friendly format for Laravel
                    if (el.type === 'datetime-local') {
                        el.type = 'text';
                        el.autocomplete = 'off';
                    }
                    flatpickr(el, {
                        locale: 'vn',
                        enableTime: true,
                        time_24hr: true,
                        dateFormat: 'Y-m-d H:i',
                        allowInput: true,
                    });
                });
            }
        }
        window.initLmsWidgets = initLmsWidgets;
        document.addEventListener('turbo:load', function () {
            initLmsWidgets();
            if (typeof window.consumeSessionFlash === 'function') window.consumeSessionFlash();
        });
        document.addEventListener('DOMContentLoaded', function () {
            initLmsWidgets();
            if (typeof window.consumeSessionFlash === 'function') window.consumeSessionFlash();
        });
        if (document.readyState !== 'loading') initLmsWidgets();
    })();
    // Sprint 9 H4 — light PWA (shell cache only; no offline submit queue)
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function () {
            navigator.serviceWorker.register(@json(asset('sw-lms.js')), { scope: '/lms/' }).catch(function () {});
        });
    }
    </script>
</body>
</html>
