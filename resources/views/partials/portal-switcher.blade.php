{{-- Chuyển cổng: Dashboard | LMS | Quản lý điểm --}}
@php
    $user = auth()->user();
    $showGrades = false;
    if ($user && ! $user->isStudent()) {
        if (method_exists($user, 'canAccessGrades')) {
            $showGrades = $user->canAccessGrades();
        } elseif (class_exists(\Modules\Grades\Services\GradeAccess::class)) {
            $showGrades = \Modules\Grades\Services\GradeAccess::canEnter($user);
        } else {
            $showGrades = $user->isSuperAdmin()
                || $user->isManager()
                || $user->isInstructor()
                || $user->can('grades.index')
                || $user->can('grades.manage')
                || $user->hasRole('super-admin');
        }
        // Super-admin luôn thấy (kể cả khi permission chưa sync)
        if ($user->isSuperAdmin() || $user->hasRole('super-admin')) {
            $showGrades = true;
        }
    }
    $compact = $compact ?? false;
@endphp

@if($user)
<nav class="portal-switcher flex flex-wrap items-center gap-1.5 {{ $compact ? '' : 'mb-4' }}" aria-label="Chuyển cổng ứng dụng">
    @if(Route::has('dashboard') && ($user->can('dashboards.index') || $user->isSuperAdmin()))
        <a href="{{ route('dashboard') }}"
           class="portal-chip portal-chip-dash {{ request()->routeIs('dashboard*') ? 'is-active' : '' }}"
           data-turbo="false">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>
    @endif

    @if(Route::has('lms.entry') || Route::has('lms.hub') || Route::has('lms.learn.home'))
        @php
            $lmsUrl = Route::has('lms.entry') ? route('lms.entry')
                : (Route::has('lms.hub') ? route('lms.hub') : route('lms.learn.home'));
        @endphp
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
