<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name'))</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="{{ asset('build/vendor/bootstrap-icons/bootstrap-icons.min.css') }}" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet" crossorigin="anonymous" referrerpolicy="no-referrer">
    @include('partials.date-input-theme')
    @include('partials.tom-select-init')
    @stack('styles')
</head>
<body class="min-h-screen bg-slate-100 text-slate-900">
    <header class="border-b border-slate-200 bg-white shadow-sm">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-4">
            <a href="{{ $portalHome ?? url('/') }}" class="flex items-center gap-3 no-underline">
                <span class="flex h-11 w-11 items-center justify-center rounded-xl bg-blue-900 text-xl text-white"><i class="{{ $portalIcon ?? 'bi-grid-1x2' }}"></i></span>
                <span><strong class="block text-lg text-blue-950">{{ $portalTitle ?? config('app.name') }}</strong><small class="text-slate-500">Cổng nghiệp vụ độc lập</small></span>
            </a>
            <a href="{{ $dashboardUrl ?? route('dashboard') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"><i class="bi bi-arrow-left mr-1"></i> Về Dashboard</a>
        </div>
    </header>
    <main id="admin-content" class="mx-auto max-w-7xl px-5 py-6">
        @yield('content')
    </main>
    @stack('scripts')
</body>
</html>
