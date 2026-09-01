<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hệ thống · Quản Lý Đào Tạo - CDHC2</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @php
        $siteFavicon = config(
            'app.favicon_url',
            'https://files-cdn.chatway.app/p0spV8BWsf2rRNReVDbnLUSpBQSg5yLEzotD4ZYm5fQPvUxe_88x88.png'
        );
        $dashboardRoute = null;
        $lmsRoute = null;
        $gradesRoute = null;
        $inventoryRoute = null;
        $leaveRoute = null;
        if (auth()->check()) {
            $u = Auth::user();
            // Học viên: chỉ LMS (không Dashboard / không Quản lý điểm)
            if ($u->isStudent()) {
                $lmsRoute = route('lms.learn.home');
            } else {
                if ($u->isInstructor()) {
                    // Dashboard: kê khai giờ chuẩn; lịch dạy nằm trong LMS
                    $dashboardRoute = Route::has('standard-hours.my-results.index')
                        ? route('standard-hours.my-results.index')
                        : route('dashboard');
                } else {
                    $dashboardRoute = route('dashboard');
                }
                if ($u->can('lms.index') || $u->can('lms.learn') || $u->can('lms.edit')) {
                    $lmsRoute = \Modules\Lms\Support\LmsAccess::entryUrl($u);
                }
                // Quản lý điểm: GV / Manager / Super-admin (và user có grades.*)
                // Super-admin / admin luôn thấy nút (không phụ thuộc sync permission).
                $canGrades = $u->isSuperAdmin()
                    || $u->hasRole('super-admin')
                    || $u->isManager()
                    || $u->isInstructor()
                    || $u->can('grades.index')
                    || $u->can('grades.manage');
                if (! $canGrades && method_exists($u, 'canAccessGrades')) {
                    $canGrades = $u->canAccessGrades();
                }
                if (! $canGrades && class_exists(\Modules\Grades\Services\GradeAccess::class)) {
                    try {
                        $canGrades = \Modules\Grades\Services\GradeAccess::canEnter($u);
                    } catch (\Throwable) {
                        $canGrades = false;
                    }
                }
                // User vào được admin dashboard ⇒ cho vào portal điểm (trừ student đã lọc trên)
                if (! $canGrades && $u->can('dashboards.index')) {
                    $canGrades = true;
                }
                if ($canGrades && Route::has('grades.hub')) {
                    $gradesRoute = route('grades.hub');
                }
                if (($u->isSuperAdmin() || $u->can('inventory.index')) && Route::has('inventory.portal')) {
                    $inventoryRoute = route('inventory.portal');
                }
                if (($u->isSuperAdmin() || $u->can('leave-management.index') || $u->can('leave-management.access.index')) && Route::has('leave-management.portal')) {
                    $leaveRoute = route('leave-management.portal');
                }
            }
        }
    @endphp
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /*
         * Token màu đồng bộ resources/views/partials/admin-theme.blade.php
         * (dashboard / admin panel)
         */
        :root {
            --brand: #4ea1ff;
            --brand-hover: #358fee;
            --brand-dark: #3580d6;
            --brand-sidebar: #174766;
            --brand-sidebar-deep: #123a55;
            --brand-link: #4ea1ff;
            --brand-link-hover: #2f86e8;
            --brand-ring: rgba(78, 161, 255, 0.38);
            --ivory: #faf8f4;
            --ivory-soft: #f5f3ef;
            --gray-light: #eceef2;
            --gray-muted: #e2e6ec;
            --gray-border: #d5dae3;
            --text-primary: #1f2937;
            --glass-blur: 12px;
            --glass-blur-strong: 16px;
            --glass-shadow: 0 10px 30px -14px rgba(78, 161, 255, 0.14);
            --glass-inset: inset 0 1px 0 rgba(255, 255, 255, 0.75);
            --glow-soft: 0 0 0 1px rgba(78, 161, 255, 0.22), 0 0 14px rgba(78, 161, 255, 0.2);

            /* Alias dùng trong trang home */
            --ink: var(--text-primary);
            --ink-soft: #1f2937;
            --slate: #4b5563;
            --muted: #6b7280;
            --line: var(--gray-border);
            --paper: #ffffff;
            --brand-deep: var(--brand-dark);
            --brand-glow: rgba(78, 161, 255, 0.38);
            --brand-soft: #6eb5ff;
            --brand-softest: #8ec5ff;
        }

        * { box-sizing: border-box; }

        body {
            font-family: 'Be Vietnam Pro', system-ui, sans-serif;
            color: var(--text-primary);
            background: linear-gradient(180deg, var(--ivory) 0%, #eef6ff 45%, var(--gray-light) 100%);
            -webkit-font-smoothing: antialiased;
        }

        /* —— Background mesh (cùng palette ivory / brand admin) —— */
        .home-bg {
            position: fixed;
            inset: 0;
            z-index: -1;
            background:
                radial-gradient(1200px 600px at 10% -10%, rgba(78, 161, 255, 0.2), transparent 55%),
                radial-gradient(900px 500px at 95% 5%, rgba(53, 143, 238, 0.12), transparent 50%),
                radial-gradient(800px 400px at 50% 100%, rgba(110, 181, 255, 0.1), transparent 55%),
                linear-gradient(180deg, var(--ivory) 0%, #eef6ff 45%, var(--gray-light) 100%);
        }

        .home-bg::after {
            content: '';
            position: absolute;
            inset: 0;
            opacity: 0.28;
            background-image:
                linear-gradient(rgba(23, 71, 102, 0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(23, 71, 102, 0.04) 1px, transparent 1px);
            background-size: 48px 48px;
            mask-image: radial-gradient(ellipse 80% 70% at 50% 30%, #000 20%, transparent 75%);
        }

        /* —— Nav (dày + căn giữa logo / nút) —— */
        .home-nav {
            position: sticky;
            top: 0;
            z-index: 50;
            backdrop-filter: blur(var(--glass-blur-strong)) saturate(1.2);
            -webkit-backdrop-filter: blur(var(--glass-blur-strong)) saturate(1.2);
            background: rgba(250, 248, 244, 0.86);
            border-bottom: 1px solid var(--gray-border);
            box-shadow: 0 12px 40px -24px rgba(23, 71, 102, 0.22);
        }

        .home-nav-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            height: 5.5rem;
            min-height: 5.5rem;
        }
        @media (min-width: 640px) {
            .home-nav-bar { height: 6rem; min-height: 6rem; }
        }
        @media (min-width: 1024px) {
            .home-nav-bar { height: 6.25rem; min-height: 6.25rem; }
        }

        .home-nav-brand {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            min-width: 0;
            height: 3rem;
            text-decoration: none;
            color: inherit;
        }
        @media (min-width: 640px) {
            .home-nav-brand { height: 3.25rem; gap: 1rem; }
        }

        .home-nav-logo {
            width: 3rem;
            height: 3rem;
            flex-shrink: 0;
            display: block;
            object-fit: contain;
            border-radius: 0.75rem;
            background: #fff;
            border: 1px solid rgba(78, 161, 255, 0.28);
            box-shadow: 0 4px 12px -6px var(--brand-glow);
            padding: 0.15rem;
        }
        @media (min-width: 640px) {
            .home-nav-logo { width: 3.25rem; height: 3.25rem; }
        }

        .home-nav-titles {
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0;
            line-height: 1.2;
            gap: 0.15rem;
        }
        .home-nav-titles .t1 {
            font-size: 1.05rem;
            font-weight: 700;
            color: var(--ink);
            letter-spacing: -0.02em;
            line-height: 1.15;
        }
        .home-nav-titles .t2 {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--brand-deep);
            line-height: 1.2;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        @media (min-width: 640px) {
            .home-nav-titles .t1 { font-size: 1.15rem; }
            .home-nav-titles .t2 { font-size: 0.8125rem; }
        }

        .home-nav-actions {
            display: flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.65rem;
            height: 3rem;
            flex-shrink: 0;
        }
        @media (min-width: 640px) {
            .home-nav-actions { height: 3.25rem; gap: 0.75rem; }
        }

        /* Nút / link trong navbar: cùng chiều cao logo, căn giữa dọc */
        .home-nav-actions .nav-link,
        .home-nav-actions .btn-solid,
        .home-nav-actions form,
        .home-nav-actions form .nav-link {
            height: 100%;
            margin: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            box-sizing: border-box;
            line-height: 1;
        }
        .home-nav-actions form {
            display: inline-flex;
            align-items: center;
            height: 100%;
        }

        .nav-link {
            color: var(--slate);
            font-weight: 500;
            font-size: 0.9rem;
            padding: 0 1.1rem;
            border-radius: 0.75rem;
            border: 1px solid transparent;
            background: transparent;
            cursor: pointer;
            transition: color 0.2s, background 0.2s;
            white-space: nowrap;
        }
        .nav-link:hover { color: var(--ink); background: rgba(15, 23, 42, 0.05); }

        .btn-solid {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-weight: 600;
            font-size: 0.9rem;
            color: #fff;
            background: linear-gradient(135deg, var(--brand) 0%, var(--brand-deep) 100%);
            border-radius: 0.75rem;
            padding: 0 1.35rem;
            border: none;
            box-shadow: 0 10px 22px -12px var(--brand-glow), inset 0 1px 0 rgba(255,255,255,0.25);
            transition: box-shadow 0.25s ease, filter 0.2s;
            white-space: nowrap;
            text-decoration: none;
            cursor: pointer;
            line-height: 1;
        }
        .btn-solid:hover {
            filter: brightness(1.05);
            box-shadow: 0 14px 28px -10px var(--brand-glow), inset 0 1px 0 rgba(255,255,255,0.3);
        }

        .btn-ghost {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            color: var(--ink-soft);
            background: rgba(255,255,255,0.88);
            border: 1px solid var(--line);
            border-radius: 0.85rem;
            padding: 0.85rem 1.5rem;
            box-shadow: 0 10px 24px -14px rgba(15, 23, 42, 0.22);
            transition: border-color 0.2s, box-shadow 0.25s, transform 0.25s;
            text-decoration: none;
            line-height: 1;
        }
        .btn-ghost:hover {
            border-color: rgba(78, 161, 255, 0.45);
            box-shadow: 0 14px 32px -14px var(--brand-glow);
            transform: translateY(-2px);
        }

        .btn-hero {
            padding: 1.1rem 2rem;
            font-size: 1.05rem;
            border-radius: 1rem;
            min-width: 12.5rem;
        }

        /* Nút LMS — teal đồng bộ cổng học */
        .btn-lms-home {
            background: linear-gradient(135deg, #0d9488 0%, #0f766e 100%) !important;
            box-shadow: 0 10px 22px -12px rgba(13, 148, 136, 0.5), inset 0 1px 0 rgba(255,255,255,0.25) !important;
        }
        .btn-lms-home:hover {
            filter: brightness(1.05);
            box-shadow: 0 14px 28px -10px rgba(13, 148, 136, 0.55), inset 0 1px 0 rgba(255,255,255,0.3) !important;
        }
        .btn-lms-home svg {
            width: 1.15rem;
            height: 1.15rem;
            flex-shrink: 0;
            display: block;
        }

        /* Nút Quản lý điểm — cam → teal (portal grades) */
        .btn-grades-home {
            background: linear-gradient(135deg, #ea580c 0%, #c2410c 48%, #0d9488 100%) !important;
            box-shadow: 0 10px 22px -12px rgba(234, 88, 12, 0.55), inset 0 1px 0 rgba(255,255,255,0.28) !important;
            color: #fff !important;
        }
        .btn-grades-home:hover {
            filter: brightness(1.06);
            box-shadow: 0 14px 28px -10px rgba(13, 148, 136, 0.5), inset 0 1px 0 rgba(255,255,255,0.3) !important;
            color: #fff !important;
        }
        .btn-grades-home svg {
            width: 1.15rem;
            height: 1.15rem;
            flex-shrink: 0;
            display: block;
        }
        .btn-inventory-home {
            background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%) !important;
            color: #fff !important;
        }
        .btn-leave-home {
            background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%) !important;
            color: #fff !important;
        }
        .portal-action {
            display: inline-flex !important;
            align-items: center;
            gap: .65rem;
        }
        .portal-action .portal-action-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.25rem;
            height: 2.25rem;
            flex: 0 0 2.25rem;
            border-radius: .75rem;
            background: rgba(255,255,255,.2);
            box-shadow: inset 0 1px 0 rgba(255,255,255,.3), 0 5px 12px rgba(15,23,42,.12);
        }
        .portal-action .portal-action-copy { display: flex; flex-direction: column; align-items: flex-start; line-height: 1.1; }
        .portal-action .portal-action-copy small { margin-top: .2rem; font-size: .68rem; font-weight: 500; opacity: .82; }
        .btn-hero.portal-action { justify-content: flex-start; text-align: left; min-width: 15rem; }
        .btn-hero.portal-action .portal-action-icon { width: 2.75rem; height: 2.75rem; flex-basis: 2.75rem; border-radius: .9rem; }
        .btn-hero.portal-action .portal-action-icon svg { width: 1.45rem; height: 1.45rem; }

        /* —— Hero rộng + 3D —— */
        .hero-shell {
            position: relative;
            padding: 5.5rem 0 6.5rem;
            overflow: hidden;
            perspective: 1200px;
        }
        @media (min-width: 768px) {
            .hero-shell { padding: 7rem 0 8rem; }
        }
        @media (min-width: 1024px) {
            .hero-shell { padding: 8rem 0 9rem; }
        }

        .hero-stage {
            position: relative;
            transform-style: preserve-3d;
            perspective: 1400px;
        }

        .hero-orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            pointer-events: none;
            opacity: 0.55;
        }
        .hero-orb--a {
            width: 420px; height: 420px;
            top: -100px; right: 4%;
            background: rgba(78, 161, 255, 0.38);
            animation: orbDrift 12s ease-in-out infinite;
        }
        .hero-orb--b {
            width: 360px; height: 360px;
            bottom: -60px; left: 2%;
            background: rgba(53, 143, 238, 0.2);
            animation: orbDrift 14s ease-in-out infinite reverse;
        }
        .hero-orb--c {
            width: 220px; height: 220px;
            top: 40%; left: 48%;
            background: rgba(110, 181, 255, 0.22);
            animation: orbDrift 10s ease-in-out infinite 1s;
        }

        @keyframes orbDrift {
            0%, 100% { transform: translate3d(0, 0, 0) scale(1); }
            50% { transform: translate3d(16px, -22px, 40px) scale(1.08); }
        }

        /* Vòng 3D quanh logo */
        .logo-3d-scene {
            position: relative;
            width: 11rem;
            height: 11rem;
            margin: 0 auto 1.75rem;
            perspective: 900px;
            transform-style: preserve-3d;
        }
        @media (min-width: 640px) {
            .logo-3d-scene { width: 13rem; height: 13rem; margin-bottom: 2rem; }
        }
        @media (min-width: 1024px) {
            .logo-3d-scene { width: 14.5rem; height: 14.5rem; }
        }

        .logo-3d-track {
            width: 100%;
            height: 100%;
            position: relative;
            transform-style: preserve-3d;
            transition: transform 0.15s ease-out;
            will-change: transform;
        }

        .logo-ring {
            position: absolute;
            inset: 0;
            border-radius: 28%;
            border: 1.5px solid rgba(78, 161, 255, 0.25);
            box-shadow: 0 0 0 1px rgba(255,255,255,0.4) inset;
            pointer-events: none;
            transform-style: preserve-3d;
        }
        .logo-ring--1 {
            inset: -6%;
            border-color: rgba(78, 161, 255, 0.35);
            animation: ringSpin 14s linear infinite;
            transform: rotateX(62deg) rotateZ(0deg) translateZ(-12px);
        }
        .logo-ring--2 {
            inset: -14%;
            border-color: rgba(110, 181, 255, 0.4);
            border-style: dashed;
            animation: ringSpin 20s linear infinite reverse;
            transform: rotateX(58deg) rotateZ(0deg) translateZ(-28px);
        }
        .logo-ring--3 {
            inset: 8%;
            border-radius: 24%;
            border-color: rgba(142, 197, 255, 0.28);
            animation: ringPulse 4s ease-in-out infinite;
            transform: translateZ(8px);
        }

        @keyframes ringSpin {
            to { transform: rotateX(62deg) rotateZ(360deg) translateZ(-12px); }
        }
        .logo-ring--2 {
            animation-name: ringSpin2;
        }
        @keyframes ringSpin2 {
            to { transform: rotateX(58deg) rotateZ(-360deg) translateZ(-28px); }
        }
        @keyframes ringPulse {
            0%, 100% { opacity: 0.5; transform: translateZ(8px) scale(1); }
            50% { opacity: 1; transform: translateZ(14px) scale(1.03); }
        }

        .logo-halo {
            position: absolute;
            inset: 12%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            border-radius: 1.5rem;
            background: linear-gradient(145deg, #ffffff 0%, #eef6ff 55%, #e0edff 100%);
            border: 1px solid rgba(78, 161, 255, 0.28);
            box-shadow:
                0 28px 60px -20px rgba(78, 161, 255, 0.45),
                0 12px 24px -16px rgba(23, 71, 102, 0.28),
                0 0 0 8px rgba(78, 161, 255, 0.08),
                inset 0 2px 0 rgba(255,255,255,0.95),
                inset 0 -6px 16px rgba(78, 161, 255, 0.1);
            transform: translateZ(40px);
            transform-style: preserve-3d;
            animation: logoFloat3d 5.5s ease-in-out infinite;
        }
        .logo-halo::after {
            content: '';
            position: absolute;
            inset: auto 10% -18% 10%;
            height: 28%;
            border-radius: 50%;
            background: radial-gradient(ellipse, rgba(78, 161, 255, 0.32), transparent 70%);
            filter: blur(6px);
            transform: translateZ(-30px) rotateX(80deg);
            pointer-events: none;
        }
        .logo-halo img {
            width: 100%;
            height: 100%;
            max-width: 7rem;
            max-height: 7rem;
            object-fit: contain;
            border-radius: 1.1rem;
            transform: translateZ(12px);
            filter: drop-shadow(0 8px 16px rgba(78, 161, 255, 0.25));
        }
        @media (min-width: 640px) {
            .logo-halo img { max-width: 8rem; max-height: 8rem; }
        }

        @keyframes logoFloat3d {
            0%, 100% { transform: translateZ(40px) translateY(0) rotateX(0deg); }
            50% { transform: translateZ(48px) translateY(-10px) rotateX(4deg); }
        }

        .pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.5rem 1.1rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
            letter-spacing: 0.02em;
            color: var(--brand-deep);
            background: linear-gradient(180deg, #eff6ff, #e0efff);
            border: 1px solid rgba(78, 161, 255, 0.28);
            box-shadow:
                0 8px 20px -10px var(--brand-glow),
                0 1px 0 rgba(255,255,255,0.8) inset;
            transform: translateZ(20px);
        }
        .pill-dot {
            width: 0.45rem; height: 0.45rem;
            border-radius: 50%;
            background: var(--brand);
            box-shadow: 0 0 0 3px rgba(78, 161, 255, 0.22);
        }

        .hero-title {
            font-weight: 800;
            letter-spacing: -0.035em;
            line-height: 1.05;
            color: var(--ink);
            text-shadow: 0 2px 24px rgba(23, 71, 102, 0.06);
        }

        /* Chữ brand lớn: chỉ animate một lớp gradient để tránh giật do repaint nhiều filter. */
        .hero-title-sub {
            display: inline-block;
            font-size: clamp(2.15rem, 6.2vw, 4.75rem);
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.12;
            padding: 0.12em 0.08em 0.2em;
            background: linear-gradient(
                100deg,
                var(--brand-dark) 0%,
                var(--brand) 18%,
                var(--brand-soft) 36%,
                #ffffff 50%,
                var(--brand-soft) 64%,
                var(--brand) 82%,
                var(--brand-dark) 100%
            );
            background-size: 320% 100%;
            background-position: 100% 50%;
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
            -webkit-text-fill-color: transparent;
            animation: titleShineRun 5.2s linear infinite;
            filter:
                drop-shadow(0 0 9px rgba(78, 161, 255, 0.42))
                drop-shadow(0 0 22px rgba(78, 161, 255, 0.26))
                drop-shadow(0 4px 16px rgba(53, 128, 214, 0.2));
            transform: translate3d(0, 0, 0);
            backface-visibility: hidden;
            will-change: background-position;
        }
        @keyframes titleShineRun {
            from { background-position: 100% 50%; }
            to { background-position: 0% 50%; }
        }

        .hero-lead {
            margin-top: 1.75rem;
            max-width: 40rem;
            margin-left: auto;
            margin-right: auto;
            font-size: 1.05rem;
            line-height: 1.75;
        }
        @media (min-width: 640px) {
            .hero-lead { font-size: 1.2rem; margin-top: 2rem; }
        }

        .hero-cta {
            margin-top: 2.5rem;
            gap: 0.85rem;
        }
        @media (min-width: 640px) {
            .hero-cta { margin-top: 2.75rem; }
        }

        .stat-strip {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            max-width: 42rem;
            margin: 3rem auto 0;
            perspective: 800px;
        }
        .stat-chip {
            text-align: center;
            padding: 1.15rem 0.65rem 1.2rem;
            border-radius: 1.15rem;
            background: linear-gradient(165deg, rgba(250,248,244,0.95), rgba(238,246,255,0.8));
            border: 1px solid var(--gray-border);
            backdrop-filter: blur(10px);
            box-shadow:
                0 16px 32px -22px var(--brand-glow),
                0 1px 0 rgba(255,255,255,0.85) inset;
            transform: rotateX(6deg) translateZ(0);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            transform-style: preserve-3d;
        }
        .stat-chip:hover {
            transform: rotateX(0deg) translateY(-6px) translateZ(16px);
            box-shadow: 0 22px 40px -18px var(--brand-glow);
        }
        .stat-chip strong {
            display: block;
            font-size: 1.2rem;
            font-weight: 800;
            color: var(--brand-deep);
            letter-spacing: -0.02em;
        }
        .stat-chip span {
            font-size: 0.72rem;
            font-weight: 500;
            color: var(--muted);
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        /* —— Sections —— */
        .section {
            padding: 4.5rem 0;
            position: relative;
        }
        .section-label {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--brand-deep);
            margin-bottom: 0.85rem;
        }
        .section-label::before {
            content: '';
            width: 1.25rem;
            height: 2px;
            border-radius: 2px;
            background: linear-gradient(90deg, var(--brand-soft), var(--brand), var(--brand-dark));
        }

        /* —— Feature cards —— */
        .feature-grid {
            display: grid;
            gap: 1.1rem;
            grid-template-columns: 1fr;
        }
        @media (min-width: 640px) {
            .feature-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (min-width: 1024px) {
            .feature-grid { grid-template-columns: repeat(5, 1fr); }
        }

        .feature-card {
            position: relative;
            height: 100%;
            padding: 1.4rem 1.25rem 1.35rem;
            border-radius: 1.25rem;
            background: linear-gradient(165deg, rgba(250,248,244,0.96) 0%, rgba(238,246,255,0.9) 100%);
            border: 1px solid var(--gray-border);
            box-shadow: var(--glass-shadow);
            overflow: hidden;
            transition: transform 0.28s cubic-bezier(0.22, 1, 0.36, 1),
                        box-shadow 0.28s ease,
                        border-color 0.25s ease;
        }
        .feature-card::before {
            content: '';
            position: absolute;
            inset: 0 0 auto 0;
            height: 3px;
            background: linear-gradient(90deg, var(--brand-soft), var(--brand), var(--brand-dark));
            opacity: 0;
            transition: opacity 0.25s;
        }
        .feature-card:hover {
            transform: translateY(-6px);
            border-color: rgba(78, 161, 255, 0.45);
            box-shadow: 0 24px 48px -24px var(--brand-glow);
        }
        .feature-card:hover::before { opacity: 1; }

        .feature-icon {
            width: 3rem;
            height: 3rem;
            border-radius: 0.9rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            background: linear-gradient(145deg, var(--brand-soft) 0%, var(--brand) 45%, var(--brand-dark) 100%);
            box-shadow: 0 12px 24px -12px var(--brand-glow);
            margin-bottom: 1rem;
        }
        .feature-num {
            position: absolute;
            top: 1rem;
            right: 1rem;
            font-size: 0.7rem;
            font-weight: 700;
            color: rgba(100, 116, 139, 0.45);
            letter-spacing: 0.08em;
        }

        .check-list {
            list-style: none;
            padding: 0;
            margin: 0.85rem 0 0;
            display: grid;
            gap: 0.4rem;
        }
        .check-list li {
            display: flex;
            align-items: flex-start;
            gap: 0.45rem;
            font-size: 0.78rem;
            color: var(--muted);
            line-height: 1.35;
        }
        .check-list li svg {
            flex-shrink: 0;
            margin-top: 0.1rem;
            color: var(--brand);
        }

        /* —— Values —— */
        .value-card {
            text-align: center;
            padding: 1.75rem 1.25rem;
            border-radius: 1.25rem;
            background: rgba(250, 248, 244, 0.85);
            border: 1px solid var(--gray-border);
            transition: border-color 0.25s, box-shadow 0.25s, transform 0.25s;
        }
        .value-card:hover {
            border-color: rgba(78, 161, 255, 0.4);
            box-shadow: 0 18px 36px -24px var(--brand-glow);
            transform: translateY(-4px);
        }
        .value-icon {
            width: 3.5rem;
            height: 3.5rem;
            margin: 0 auto 1rem;
            border-radius: 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(145deg, #eef6ff, #dceeff);
            color: var(--brand-dark);
            border: 1px solid rgba(78, 161, 255, 0.25);
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.8);
        }

        /* —— Footer (cùng palette sidebar admin) —— */
        .home-footer {
            position: relative;
            color: #e2e8f0;
            overflow: hidden;
            background:
                radial-gradient(800px 400px at 20% 0%, rgba(78, 161, 255, 0.28), transparent 60%),
                radial-gradient(600px 300px at 90% 100%, rgba(110, 181, 255, 0.12), transparent 55%),
                linear-gradient(180deg, var(--brand-sidebar) 0%, var(--brand-sidebar-deep) 100%);
        }
        .home-footer a {
            color: var(--brand-softest);
            transition: color 0.2s;
        }
        .home-footer a:hover { color: #fff; }

        .footer-logo {
            width: 2.75rem;
            height: 2.75rem;
            border-radius: 0.75rem;
            object-fit: contain;
            background: #fff;
            padding: 0.2rem;
            border: 1px solid rgba(255,255,255,0.25);
            box-shadow: 0 8px 20px -10px rgba(0,0,0,0.4);
        }

        .divider {
            height: 1px;
            background: linear-gradient(90deg, transparent, rgba(148,163,184,0.35), transparent);
        }

        @media (prefers-reduced-motion: reduce) {
            .logo-halo, .logo-ring, .hero-orb { animation: none !important; }
            /* Không chạy nền chữ khi người dùng yêu cầu giảm chuyển động. */
            .hero-title-sub {
                animation: none !important;
                background-position: 50% 50% !important;
                filter: drop-shadow(0 0 10px rgba(78, 161, 255, 0.4)) !important;
                will-change: auto;
            }
            .logo-3d-track { transition: none !important; }
            .feature-card:hover, .value-card:hover, .btn-solid:hover, .btn-ghost:hover, .stat-chip:hover {
                transform: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="home-bg" aria-hidden="true"></div>

    {{-- Navigation --}}
    <nav class="home-nav">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-10">
            <div class="home-nav-bar">
                <a href="{{ url('/') }}" class="home-nav-brand" aria-label="Trang chủ CDHC2">
                    <img src="{{ $siteFavicon }}" alt="" width="52" height="52"
                         class="home-nav-logo" decoding="async">
                    <span class="home-nav-titles">
                        <span class="t1">Hệ thống</span>
                        <span class="t2">Quản Lý Đào Tạo - CDHC2</span>
                    </span>
                </a>

                <div class="home-nav-actions">
                    @guest
                        <a href="{{ route('login') }}" class="nav-link hidden sm:inline-flex">Đăng nhập</a>
                        <a href="{{ route('register') }}" class="btn-solid">Đăng ký</a>
                    @else
                        @if($dashboardRoute)
                            <a href="{{ $dashboardRoute }}" class="btn-solid">
                                <svg class="h-4 w-4 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                                </svg>
                                Dashboard
                            </a>
                        @endif
                        @if($lmsRoute)
                            <a href="{{ $lmsRoute }}" class="btn-solid btn-lms-home">
                                <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/>
                                </svg>
                                LMS
                            </a>
                        @endif
                        @if($gradesRoute)
                            <a href="{{ $gradesRoute }}" class="btn-solid btn-grades-home" data-turbo="false">
                                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Quản lý điểm
                            </a>
                        @endif
                        @if($inventoryRoute)
                            <a href="{{ $inventoryRoute }}" class="btn-solid btn-inventory-home portal-action" data-turbo="false">
                                <span class="portal-action-icon" aria-hidden="true"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Zm0 0 8 4.5 8-4.5M12 12v9M8 5.25l8 4.5"/></svg></span>
                                <span class="portal-action-copy"><strong>Quản lý vật tư</strong><small>Kho · tài sản · đề xuất</small></span>
                            </a>
                        @endif
                        @if($leaveRoute)
                            <a href="{{ $leaveRoute }}" class="btn-solid btn-leave-home portal-action" data-turbo="false">
                                <span class="portal-action-icon" aria-hidden="true"><svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3h10v18H7zM9.5 7h5M9.5 11h5M9.5 15h3M9 3v-1h6v1"/></svg></span>
                                <span class="portal-action-copy"><strong>Quản lý phép</strong><small>Đề xuất · duyệt · hồ sơ</small></span>
                            </a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="nav-link">Đăng xuất</button>
                        </form>
                    @endguest
                </div>
            </div>
        </div>
    </nav>

    {{-- Hero — rộng + 3D --}}
    <header class="hero-shell">
        <div class="hero-orb hero-orb--a" aria-hidden="true"></div>
        <div class="hero-orb hero-orb--b" aria-hidden="true"></div>
        <div class="hero-orb hero-orb--c" aria-hidden="true"></div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-10">
            <div class="hero-stage text-center max-w-5xl mx-auto" id="heroStage">
                <div class="flex flex-col items-center mb-8 sm:mb-10">
                    {{-- Logo 3D: tilt theo chuột + vòng quay --}}
                    <div class="logo-3d-scene" id="logo3dScene" aria-hidden="false">
                        <div class="logo-3d-track" id="logo3dTrack">
                            <div class="logo-ring logo-ring--1" aria-hidden="true"></div>
                            <div class="logo-ring logo-ring--2" aria-hidden="true"></div>
                            <div class="logo-ring logo-ring--3" aria-hidden="true"></div>
                            <div class="logo-halo">
                                <img src="{{ $siteFavicon }}" alt="Logo CDHC2" width="128" height="128" decoding="async">
                            </div>
                        </div>
                    </div>
                    <span class="pill">
                        <span class="pill-dot" aria-hidden="true"></span>
                        Trường Cao đẳng Hậu cần 2
                    </span>
                </div>

                <h1 class="hero-title text-4xl sm:text-5xl md:text-6xl">
                    <span class="block mb-3 sm:mb-4 text-[var(--ink)]">Hệ thống</span>
                    <span class="block hero-title-sub">Quản Lý Đào Tạo - CDHC2</span>
                </h1>

                <p class="hero-lead text-[var(--slate)] font-normal">
                    Nền tảng số hóa phục vụ công tác tổ chức, quản lý và giám sát toàn bộ hoạt động đào tạo
                    tại Trường Cao đẳng Hậu cần 2. Giao diện hiện đại, thao tác dễ dàng và trực quan.
                </p>

                <div class="hero-cta flex flex-col sm:flex-row justify-center items-stretch sm:items-center">
                    @guest
                        <a href="{{ route('register') }}" class="btn-solid btn-hero">
                            <svg class="h-5 w-5 opacity-90" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Bắt đầu ngay hôm nay
                        </a>
                        <a href="{{ route('login') }}" class="btn-ghost btn-hero">
                            <svg class="h-5 w-5 text-[var(--brand-deep)]" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path fill-rule="evenodd" d="M3 3a1 1 0 000 2v8a2 2 0 002 2h2.586l-1.293 1.293a1 1 0 101.414 1.414L10 15.414l2.293 2.293a1 1 0 001.414-1.414L12.414 15H15a2 2 0 002-2V5a1 1 0 100-2H3zm11.707 4.707a1 1 0 00-1.414-1.414L10 9.586 8.707 8.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                            Đăng nhập
                        </a>
                    @else
                        @if($dashboardRoute)
                            <a href="{{ $dashboardRoute }}" class="btn-solid btn-hero">
                                <svg class="h-5 w-5 opacity-90 shrink-0" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                    <path d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zM3 10a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6zM14 9a1 1 0 00-1 1v6a1 1 0 001 1h2a1 1 0 001-1v-6a1 1 0 00-1-1h-2z"/>
                                </svg>
                                Vào Dashboard
                            </a>
                        @endif
                        @if($lmsRoute)
                            <a href="{{ $lmsRoute }}" class="btn-solid btn-hero btn-lms-home">
                                <svg class="h-5 w-5 opacity-95 shrink-0" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <path d="M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72L12 15l5-2.73v3.72z"/>
                                </svg>
                                Vào LMS
                            </a>
                        @endif
                        @if($gradesRoute)
                            <a href="{{ $gradesRoute }}" class="btn-solid btn-hero btn-grades-home" data-turbo="false">
                                <svg class="h-5 w-5 opacity-95 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Quản lý điểm
                            </a>
                        @endif
                        @if($inventoryRoute)
                            <a href="{{ $inventoryRoute }}" class="btn-solid btn-hero btn-inventory-home portal-action" data-turbo="false">
                                <span class="portal-action-icon" aria-hidden="true"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7.5 12 3l8 4.5v9L12 21l-8-4.5v-9Zm0 0 8 4.5 8-4.5M12 12v9M8 5.25l8 4.5"/></svg></span>
                                <span class="portal-action-copy"><strong>Vào Quản lý vật tư</strong><small>Kho · tài sản · điều động</small></span>
                            </a>
                        @endif
                        @if($leaveRoute)
                            <a href="{{ $leaveRoute }}" class="btn-solid btn-hero btn-leave-home portal-action" data-turbo="false">
                                <span class="portal-action-icon" aria-hidden="true"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M7 3h10v18H7zM9.5 7h5M9.5 11h5M9.5 15h3M9 3v-1h6v1"/></svg></span>
                                <span class="portal-action-copy"><strong>Vào Quản lý phép</strong><small>Đề xuất · duyệt · hồ sơ</small></span>
                            </a>
                        @endif
                    @endguest
                </div>

                <div class="stat-strip" aria-hidden="true">
                    <div class="stat-chip">
                        <strong>Lịch học</strong>
                        <span>Theo dõi realtime</span>
                    </div>
                    <div class="stat-chip">
                        <strong>Phân quyền</strong>
                        <span>An toàn dữ liệu</span>
                    </div>
                    <div class="stat-chip">
                        <strong>Báo cáo</strong>
                        <span>Số liệu trực quan</span>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- Features --}}
    <section class="section" id="chuc-nang">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-12">
                <div class="section-label justify-center">Chức năng chính</div>
                <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-[var(--ink)] mb-3">
                    Quản lý toàn diện & hiệu quả
                </h2>
                <p class="text-[var(--slate)] text-base sm:text-lg leading-relaxed">
                    Hệ thống tích hợp đầy đủ các công cụ hiện đại để tối ưu hóa
                    quy trình đào tạo và nâng cao hiệu quả quản lý.
                </p>
            </div>

            <div class="feature-grid">
                {{-- Card 1 --}}
                <article class="feature-card">
                    <span class="feature-num">01</span>
                    <div class="feature-icon" aria-hidden="true">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-[1.05rem] font-bold text-[var(--ink)] mb-2 tracking-tight">Quản lý lịch đào tạo</h3>
                    <p class="text-sm text-[var(--slate)] leading-relaxed">
                        Tạo và điều chỉnh lịch học cho từng ngành, lớp. Theo dõi tiến độ theo tuần, tháng, học kỳ.
                    </p>
                    <ul class="check-list">
                        <li>
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Sắp xếp lịch linh hoạt
                        </li>
                        <li>
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Tự động tổng hợp số liệu
                        </li>
                    </ul>
                </article>

                {{-- Card 2 --}}
                <article class="feature-card">
                    <span class="feature-num">02</span>
                    <div class="feature-icon" aria-hidden="true">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-[1.05rem] font-bold text-[var(--ink)] mb-2 tracking-tight">Theo dõi tiến độ</h3>
                    <p class="text-sm text-[var(--slate)] leading-relaxed">
                        Hỗ trợ giảng viên và cán bộ xem nhanh các lớp đang học, môn học trong ngày.
                    </p>
                    <ul class="check-list">
                        <li>
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Xem nhanh lịch học
                        </li>
                        <li>
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Đảm bảo đúng tiến độ
                        </li>
                    </ul>
                </article>

                {{-- Card 3 --}}
                <article class="feature-card">
                    <span class="feature-num">03</span>
                    <div class="feature-icon" aria-hidden="true">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-[1.05rem] font-bold text-[var(--ink)] mb-2 tracking-tight">Quản lý nhân sự</h3>
                    <p class="text-sm text-[var(--slate)] leading-relaxed">
                        Quản lý thông tin giảng viên và học viên. Phân công giảng dạy khoa học.
                    </p>
                    <ul class="check-list">
                        <li>
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Phân nhóm theo đơn vị
                        </li>
                        <li>
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Cập nhật tình trạng
                        </li>
                    </ul>
                </article>

                {{-- Card 4 --}}
                <article class="feature-card">
                    <span class="feature-num">04</span>
                    <div class="feature-icon" aria-hidden="true">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </div>
                    <h3 class="text-[1.05rem] font-bold text-[var(--ink)] mb-2 tracking-tight">Phân quyền sử dụng</h3>
                    <p class="text-sm text-[var(--slate)] leading-relaxed">
                        Cấp quyền theo vai trò: Cán bộ, giảng viên, học viên. Bảo mật đa cấp.
                    </p>
                    <ul class="check-list">
                        <li>
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Truy cập đúng chức năng
                        </li>
                        <li>
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            An toàn dữ liệu
                        </li>
                    </ul>
                </article>

                {{-- Card 5 --}}
                <article class="feature-card">
                    <span class="feature-num">05</span>
                    <div class="feature-icon" aria-hidden="true">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-[1.05rem] font-bold text-[var(--ink)] mb-2 tracking-tight">Thống kê & Báo cáo</h3>
                    <p class="text-sm text-[var(--slate)] leading-relaxed">
                        Tổng hợp dữ liệu theo nhiều tiêu chí. Xuất báo cáo nhanh chóng, chính xác.
                    </p>
                    <ul class="check-list">
                        <li>
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Báo cáo đa dạng
                        </li>
                        <li>
                            <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            Số liệu trực quan
                        </li>
                    </ul>
                </article>
            </div>
        </div>
    </section>

    <div class="max-w-7xl mx-auto px-4"><div class="divider"></div></div>

    {{-- Values --}}
    <section class="section" id="gia-tri">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center max-w-3xl mx-auto mb-12">
                <div class="section-label justify-center">Giá trị mang lại</div>
                <h2 class="text-3xl sm:text-4xl font-extrabold tracking-tight text-[var(--ink)] mb-3">
                    Giá trị mang lại của hệ thống
                </h2>
            </div>

            <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-5">
                <div class="value-card">
                    <div class="value-icon" aria-hidden="true">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-[var(--ink)] mb-1.5">Tăng tính minh bạch</h3>
                    <p class="text-sm text-[var(--muted)] leading-relaxed">Minh bạch và chủ động trong công tác đào tạo</p>
                </div>

                <div class="value-card">
                    <div class="value-icon" aria-hidden="true">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-[var(--ink)] mb-1.5">Tiết kiệm thời gian</h3>
                    <p class="text-sm text-[var(--muted)] leading-relaxed">Giảm thời gian xử lý thủ công, tối ưu quy trình</p>
                </div>

                <div class="value-card">
                    <div class="value-icon" aria-hidden="true">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-[var(--ink)] mb-1.5">Tra cứu dễ dàng</h3>
                    <p class="text-sm text-[var(--muted)] leading-relaxed">Tra cứu, theo dõi và quản lý tập trung</p>
                </div>

                <div class="value-card">
                    <div class="value-icon" aria-hidden="true">
                        <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h3 class="text-base font-bold text-[var(--ink)] mb-1.5">Quyết định chính xác</h3>
                    <p class="text-sm text-[var(--muted)] leading-relaxed">Số liệu trực quan hỗ trợ lãnh đạo</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="home-footer">
        <div class="relative max-w-7xl mx-auto py-12 sm:py-14 px-4 sm:px-6 lg:px-8">
            <div class="grid md:grid-cols-4 gap-10">
                <div class="md:col-span-2">
                    <div class="flex items-center gap-3 mb-4">
                        <img src="{{ $siteFavicon }}" alt="Logo CDHC2" width="44" height="44"
                             class="footer-logo" decoding="async">
                        <div class="leading-tight">
                            <div class="text-lg font-bold text-white tracking-tight">Hệ thống</div>
                            <div class="text-sm font-semibold" style="color: var(--brand-softest)">Quản Lý Đào Tạo - CDHC2</div>
                        </div>
                    </div>
                    <p class="text-slate-300/90 mb-1 max-w-md leading-relaxed text-sm">
                        Hệ thống Quản lý Đào tạo - Trường Cao đẳng Hậu cần 2.
                        Nền tảng số hóa toàn diện phục vụ công tác đào tạo.
                    </p>
                </div>

                <div>
                    <h4 class="font-semibold mb-4 text-white text-sm tracking-wide uppercase letter-spacing">Liên kết nhanh</h4>
                    <ul class="space-y-2.5 text-sm text-slate-300">
                        <li><a href="#">Giới thiệu</a></li>
                        <li><a href="#chuc-nang">Chức năng</a></li>
                        <li><a href="#">Hỗ trợ</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-semibold mb-4 text-white text-sm tracking-wide uppercase">Liên hệ</h4>
                    <ul class="space-y-2.5 text-sm text-slate-300">
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 shrink-0" style="color: var(--brand-soft)" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
                            </svg>
                            hc2@cdhc2.edu.vn
                        </li>
                        <li class="flex items-center gap-2">
                            <svg class="h-4 w-4 shrink-0" style="color: var(--brand-soft)" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                                <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z"/>
                            </svg>
                            028 3896 1895
                        </li>
                    </ul>
                </div>
            </div>

            <div class="mt-10 pt-6 border-t border-white/10 flex flex-col md:flex-row justify-between items-center gap-3">
                <p class="text-slate-400 text-xs sm:text-sm">
                    &copy; {{ date('Y') }} Hệ thống Quản Lý Đào Tạo - CDHC2.
                </p>
                <p class="text-slate-400 text-xs sm:text-sm text-center md:text-right">
                    Trường Cao đẳng Hậu cần 2 - Tổng Cục Hậu Cần - Kỹ Thuật
                </p>
            </div>
        </div>
    </footer>

    <script>
    (function () {
        var scene = document.getElementById('logo3dScene');
        var track = document.getElementById('logo3dTrack');
        if (!scene || !track) return;

        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (reduce) return;

        var maxTilt = 14; // degrees
        var raf = null;
        var targetX = 0, targetY = 0;
        var curX = 0, curY = 0;

        function render() {
            curX += (targetX - curX) * 0.12;
            curY += (targetY - curY) * 0.12;
            track.style.transform =
                'rotateX(' + curX.toFixed(2) + 'deg) rotateY(' + curY.toFixed(2) + 'deg)';
            raf = requestAnimationFrame(render);
        }

        function onMove(e) {
            var rect = scene.getBoundingClientRect();
            var cx = rect.left + rect.width / 2;
            var cy = rect.top + rect.height / 2;
            var clientX = e.touches ? e.touches[0].clientX : e.clientX;
            var clientY = e.touches ? e.touches[0].clientY : e.clientY;
            var nx = (clientX - cx) / (rect.width / 2);
            var ny = (clientY - cy) / (rect.height / 2);
            nx = Math.max(-1, Math.min(1, nx));
            ny = Math.max(-1, Math.min(1, ny));
            targetY = nx * maxTilt;
            targetX = -ny * maxTilt;
        }

        function onLeave() {
            targetX = 0;
            targetY = 0;
        }

        // Theo dõi chuột trên cả hero để cảm giác “sân khấu” 3D rộng hơn
        var stage = document.getElementById('heroStage') || scene;
        stage.addEventListener('mousemove', onMove, { passive: true });
        stage.addEventListener('mouseleave', onLeave, { passive: true });
        scene.addEventListener('touchmove', onMove, { passive: true });
        scene.addEventListener('touchend', onLeave, { passive: true });

        raf = requestAnimationFrame(render);
    })();
    </script>
</body>
</html>
