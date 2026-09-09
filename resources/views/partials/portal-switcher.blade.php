{{-- Chuyển cổng: Dashboard | LMS | Quản lý điểm --}}
@php
    $user = auth()->user();
    $showGrades = false;
    $showDashboard = false;
    $lmsUrl = null;
    if ($user && ! $user->isStudent()) {
        $showDashboard = Route::has('dashboard') && \App\Support\PermissionCheck::can($user, 'dashboards.index');
        if (class_exists(\Modules\Grades\Services\GradeAccess::class)) {
            try {
                $showGrades = \Modules\Grades\Services\GradeAccess::canEnter($user);
            } catch (\Throwable) {
                $showGrades = false;
            }
        }
    }
    if ($user && \App\Support\PermissionCheck::can($user, 'lms.index')) {
        $entryRoute = class_exists(\Modules\Lms\Support\LmsAccess::class)
            ? \Modules\Lms\Support\LmsAccess::entryRouteName($user)
            : null;
        if ($entryRoute && Route::has($entryRoute)) {
            $lmsUrl = route($entryRoute);
        } elseif (Route::has('lms.entry')) {
            $lmsUrl = route('lms.entry');
        } elseif (Route::has('lms.hub')) {
            $lmsUrl = route('lms.hub');
        } elseif (Route::has('lms.learn.home')) {
            $lmsUrl = route('lms.learn.home');
        }
    }
    $compact = $compact ?? false;
@endphp

@if($user)
<nav class="portal-switcher flex flex-wrap items-center gap-1.5 {{ $compact ? '' : 'mb-4' }}" aria-label="Chuyển cổng ứng dụng">
    @if($showDashboard)
        <a href="{{ route('dashboard') }}"
           class="portal-chip portal-chip-dash {{ request()->routeIs('dashboard*') ? 'is-active' : '' }}"
           data-turbo="false">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>
    @endif

    @if($lmsUrl)
        <a href="{{ $lmsUrl }}"
           class="portal-chip portal-chip-lms {{ request()->routeIs('lms.*') ? 'is-active' : '' }}"
           data-turbo="false">
            <i class="bi bi-mortarboard"></i>
            <span>LMS</span>
        </a>
    @endif

    @if($showGrades && Route::has('grades.hub'))
        <a href="{{ route('grades.hub') }}"
           class="portal-chip portal-chip-grades {{ request()->routeIs('grades.*') ? 'is-active' : '' }}"
           data-turbo="false">
            <i class="bi bi-clipboard-data"></i>
            <span>Quản lý điểm</span>
        </a>
    @endif
</nav>
@endif
