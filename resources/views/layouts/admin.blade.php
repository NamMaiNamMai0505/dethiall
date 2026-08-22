<!DOCTYPE html>
{{-- Không đặt overflow-hidden lên <html>: Turbo chỉ thay <body> khi chuyển
     trang, class trên <html> ở lại và làm trang công khai mất cuộn cho tới khi
     tải lại. Khoá cuộn đặt ở <body> của riêng shell quản trị. --}}
<html lang="vi" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="turbo-cache-control" content="@yield('turbo-cache-control', 'no-preview')">
    @hasSection('turbo-visit-control')
        <meta name="turbo-visit-control" content="@yield('turbo-visit-control')">
    @endif
    {{-- Prefetch off: reduces background network load on LAN/private IP --}}
    <meta name="turbo-prefetch" content="false">
    <title>@yield('title', config('app.name'))</title>
    @include('partials.favicon')

    {{-- Local Vite build (Tailwind compiled once) — no cdn.tailwindcss.com --}}
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    {{-- Static, không qua Vite/Lightning CSS — build làm hỏng escape Unicode
         của icon font (content:"\fXXX" → content:""). Xem app.css. --}}
    <link href="{{ asset('build/vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">

    @include('partials.admin-theme')
    {{-- Icons: app dùng cả Bootstrap Icons + Font Awesome --}}
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" crossorigin="anonymous" referrerpolicy="no-referrer">
    <style>
        .portal-chip{display:inline-flex;align-items:center;gap:.35rem;padding:.4rem .75rem;border-radius:.65rem;font-size:.8125rem;font-weight:600;text-decoration:none;border:1px solid transparent;white-space:nowrap}
        .admin-top-header .portal-chip-dash{color:#e0f2fe;background:rgba(255,255,255,.12);border-color:rgba(255,255,255,.22)}
        .admin-top-header .portal-chip-dash:hover,.admin-top-header .portal-chip-dash.is-active{background:#fff;color:#1e40af}
        .admin-top-header .portal-chip-lms{color:#ccfbf1;background:rgba(13,148,136,.35);border-color:rgba(204,251,241,.35)}
        .admin-top-header .portal-chip-lms:hover,.admin-top-header .portal-chip-lms.is-active{background:#0d9488;color:#fff}
        .admin-top-header .portal-chip-grades{color:#fff7ed;background:linear-gradient(135deg,rgba(234,88,12,.9),rgba(13,148,136,.8));border-color:rgba(255,237,213,.4)}
        .admin-top-header .portal-chip-grades:hover,.admin-top-header .portal-chip-grades.is-active{filter:brightness(1.08);color:#fff}
        .portal-chip-dash{color:#1e40af;background:rgba(78,161,255,.12);border-color:rgba(78,161,255,.35)}
        .portal-chip-dash:hover,.portal-chip-dash.is-active{background:#4ea1ff;color:#fff}
        .portal-chip-lms{color:#0f766e;background:rgba(13,148,136,.1);border-color:rgba(13,148,136,.3)}
        .portal-chip-lms:hover,.portal-chip-lms.is-active{background:linear-gradient(135deg,#0d9488,#0f766e);color:#fff}
        .portal-chip-grades{color:#9a3412;background:linear-gradient(135deg,rgba(234,88,12,.14),rgba(13,148,136,.12));border-color:rgba(234,88,12,.45)}
        .portal-chip-grades:hover,.portal-chip-grades.is-active{background:linear-gradient(135deg,#ea580c,#c2410c 50%,#0d9488);color:#fff}
    </style>
    @stack('styles')
</head>
<body class="dashboard-shell h-screen overflow-hidden">
    <div id="admin-notifications" data-turbo-permanent>
        @include('partials.notifications')
        @include('partials.notification-center')
    </div>

    <div class="flex h-full min-h-0">
        {{-- Sidebar --}}
        @include('partials.sidebar')

        {{-- Main Content --}}
        <div class="flex-1 flex flex-col min-h-0 min-w-0">
            {{-- Top Header --}}
            @include('partials.top-header', ['title' => View::yieldContent('page-title', 'Hệ thống quản lý đào tạo')])

            {{-- Content Area --}}
            <main id="admin-content" class="flex-1 overflow-x-hidden overflow-y-auto min-h-0">
                <div class="container mx-auto px-6 py-4">
                    {{-- Flash Messages --}}
                    @include('partials.flash-messages')

                    @yield('content')
                </div>
            </main>
        </div>
    </div>

    @include('partials.turbo-admin')
    @include('partials.ui-motion')
    @include('partials.tom-select-init')
    @include('partials.date-input-theme')
    @include('partials.file-input-theme')
    @stack('scripts')
</body>
</html>
