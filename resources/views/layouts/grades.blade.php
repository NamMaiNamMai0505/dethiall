<!DOCTYPE html>
<html lang="vi" class="grades-html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="turbo-prefetch" content="false">
    <title>@yield('title', 'Quản lý điểm') · CDHC2</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="{{ asset('build/vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    @include('partials.grades-theme')
    <link href="https://cdn.jsdelivr.net/npm/tom-select@2.4.1/dist/css/tom-select.css" rel="stylesheet">
    @stack('styles')
</head>
@php
    $gradesUser = auth()->user();
    $gradesUser->loadMissing(['position', 'roleRelation', 'unit']);
    $gradesRoleLabel = $gradesUser?->position?->name
        ?: ($gradesUser?->getRoleNames()->first()
            ?: ($gradesUser?->roleRelation?->name ?? 'Tài khoản'));
    if ($gradesUser?->unit?->name) {
        $gradesRoleLabel .= ' · '.$gradesUser->unit->name;
    }
    $brandLogo = asset('images/brand-logo.png');
@endphp
<body class="grades-shell">
    {{-- Không turbo-permanent: header re-render mỗi navigate để highlight đúng tab --}}
    <header class="grades-top" id="grades-header">
        <div class="grades-top-inner flex flex-wrap items-center justify-between gap-x-4 gap-y-2 py-3">
            <a href="{{ route('grades.hub') }}" class="grades-brand flex items-center no-underline text-inherit min-w-0">
                <img src="{{ $brandLogo }}?v=2" alt="Logo CDHC2" width="40" height="40"
                     class="grades-brand-logo flex-shrink-0"
                     onerror="this.onerror=null;this.src='{{ asset('images/brand-logo.png') }}';">
                <span class="grades-brand-text min-w-0">
                    <strong class="block text-slate-900 leading-tight">Quản lý điểm</strong>
                    <small class="block text-slate-500 truncate" title="{{ $gradesUser?->name }} · {{ $gradesRoleLabel }}">
                        {{ $gradesUser?->name ?? '—' }}
                        <span class="text-slate-400">·</span>
                        {{ $gradesRoleLabel }}
                    </small>
                </span>
            </a>
            <nav class="grades-nav flex flex-wrap items-center gap-1" data-grades-nav>
                <a href="{{ route('grades.hub') }}"
                   data-nav-match="grades-entry"
                   class="{{ request()->routeIs('grades.hub') || request()->routeIs('grades.subjects.*') || request()->routeIs('grades.faculties.*') || request()->routeIs('grades.room') ? 'is-active' : '' }}">Nhập điểm</a>
                <a href="{{ route('grades.academic.hub') }}"
                   data-nav-match="grades-academic"
                   class="{{ request()->routeIs('grades.academic.*') ? 'is-active' : '' }}">Tổng kết · TN</a>
                <a href="{{ route('grades.books.index') }}"
                   data-nav-match="grades-books"
                   class="{{ request()->routeIs('grades.books.*') ? 'is-active' : '' }}">Bảng điểm</a>
                @if(Route::has('export-templates.portal.index'))
                    <a href="{{ route('export-templates.portal.index', ['portal' => 'grades']) }}"
                       data-nav-match="grades-export"
                       class="{{ request()->routeIs('export-templates.portal.*') && request()->route('portal') === 'grades' ? 'is-active' : '' }}">Mẫu xuất</a>
                @endif
                @if(Route::has('settings.grades'))
                    <a href="{{ route('settings.grades') }}"
                       data-nav-match="grades-settings"
                       class="{{ request()->routeIs('settings.grades') ? 'is-active' : '' }}">
                        Cài đặt
                    </a>
                @endif
                @if(Route::has('lms.learn.home'))
                    <a href="{{ route('lms.learn.home') }}" data-turbo="false">← LMS</a>
                @elseif(Route::has('lms.entry'))
                    <a href="{{ route('lms.entry') }}" data-turbo="false">← LMS</a>
                @endif
                @if(Route::has('dashboard'))
                    <a href="{{ route('dashboard') }}" data-turbo="false">Dashboard</a>
                @endif

                {{-- Chuông thông báo — đồng bộ LMS (delegation JS, không cần permanent) --}}
                <div class="relative" id="notification-root">
                    <button type="button"
                            data-notification-trigger
                            class="grades-nav-icon-btn relative"
                            aria-label="Thông báo"
                            id="notification-bell">
                        <i class="bi bi-bell"></i>
                        <span id="notification-badge" data-count="0" class="grades-notif-badge"></span>
                    </button>
                    <div id="notification-panel"
                         class="absolute right-0 top-full mt-2 rounded-xl shadow-xl z-[300] hidden overflow-hidden bg-white border border-slate-200"
                         style="width:22rem;max-height:24rem">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-slate-100 bg-orange-50/60">
                            <div class="flex items-center gap-2 text-slate-800">
                                <i class="bi bi-bell text-orange-600"></i>
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
            </nav>
        </div>
    </header>

    <main id="grades-main" class="grades-main">
        {{-- Flash → popup toast (Notify), đồng bộ LMS — không banner inline --}}
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
    @include('partials.notification-center')
    @include('partials.turbo-grades')
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.1/dist/js/tom-select.complete.min.js"></script>
    @include('partials.tom-select-init')
    @stack('scripts')
    <script>
    (function () {
        function consume() {
            if (typeof window.consumeSessionFlash === 'function') {
                window.consumeSessionFlash();
            }
        }
        document.addEventListener('turbo:load', consume);
        document.addEventListener('DOMContentLoaded', consume);
        if (document.readyState !== 'loading') setTimeout(consume, 0);
    })();
    </script>
</body>
</html>
