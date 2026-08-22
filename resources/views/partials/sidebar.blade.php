<style>
    /* Tooltip styles for collapsed sidebar */
    .sidebar-tooltip {
        position: relative;
    }

    .sidebar-tooltip::after {
        content: attr(data-tooltip);
        position: absolute;
        left: 100%;
        top: 50%;
        transform: translateY(-50%);
        margin-left: 0.5rem;
        padding: 0.5rem 0.75rem;
        background-color: #1f2937;
        color: white;
        border-radius: 0.375rem;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease-in-out;
        z-index: 50;
        font-size: 0.875rem;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .sidebar-tooltip:hover::after {
        opacity: 1;
    }

    /* Only show tooltips when sidebar is collapsed */
    #sidebar.w-64 .sidebar-tooltip::after {
        display: none;
    }

    /* Hide submenu items when sidebar is collapsed */
    #sidebar.w-16 .sidebar-submenu {
        display: none !important;
    }

    /* Scroll sidebar without visible scrollbar */
    #sidebar .sidebar-nav {
        scrollbar-width: none;
        -ms-overflow-style: none;
    }

    #sidebar .sidebar-nav::-webkit-scrollbar {
        display: none;
        width: 0;
        height: 0;
    }
</style>

<div class="bg-gray-800 text-white w-64 flex-shrink-0 transition-all duration-300 h-full flex flex-col min-h-0" id="sidebar" data-turbo-permanent>
    {{-- Brand: logo + tiêu đề — style đồng bộ navbar trang chủ /# --}}
    @php
        $sidebarBrandLogo = config(
            'app.favicon_url',
            'https://files-cdn.chatway.app/p0spV8BWsf2rRNReVDbnLUSpBQSg5yLEzotD4ZYm5fQPvUxe_88x88.png'
        );
    @endphp
    <div class="p-3 border-b border-gray-700 flex items-center justify-between gap-2">
        <a href="{{ url('/') }}" class="flex items-center gap-3.5 min-w-0 no-underline text-white hover:opacity-95" id="sidebar-brand">
            <img src="{{ $sidebarBrandLogo }}"
                 alt="CDHC2"
                 width="52" height="52"
                 class="sidebar-brand-logo flex-shrink-0"
                 decoding="async"
                 onerror="this.onerror=null;this.src='{{ asset('images/brand-logo.png') }}';">
            <span class="sidebar-text min-w-0 leading-tight" id="sidebar-title">
                <span class="sidebar-brand-t1">Hệ thống</span>
                <span class="sidebar-brand-t2">Quản Lý Đào Tạo - CDHC2</span>
            </span>
        </a>
        <button onclick="toggleSidebar()" class="text-gray-400 hover:text-white focus:outline-none flex-shrink-0 transition-colors duration-200"
            id="sidebar-toggle" type="button" title="Thu gọn menu">
            <i class="bi bi-chevron-left text-xl transition-transform duration-300"></i>
        </button>
    </div>
    <style>
        #sidebar #sidebar-brand {
            gap: 0.85rem; /* tách logo ↔ chữ */
        }
        /* Logo sidebar = home-nav-logo (/#) */
        #sidebar .sidebar-brand-logo {
            width: 2.5rem;
            height: 2.5rem;
            display: block;
            object-fit: contain;
            border-radius: 0.7rem;
            background: #fff;
            border: 1px solid rgba(78, 161, 255, 0.35);
            box-shadow: 0 4px 12px -6px rgba(78, 161, 255, 0.55), 0 0 0 1px rgba(255,255,255,0.08);
            padding: 0.12rem;
        }
        #sidebar.w-16 .sidebar-brand-logo {
            width: 2.15rem;
            height: 2.15rem;
            border-radius: 0.6rem;
        }
        /* Chữ brand — nhỏ hơn, dòng 2 xanh brand như trang /# */
        #sidebar #sidebar-title {
            display: flex;
            flex-direction: column;
            gap: 0.1rem;
            line-height: 1.15;
        }
        #sidebar .sidebar-brand-t1 {
            display: block;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: -0.01em;
            color: #ffffff;
        }
        #sidebar .sidebar-brand-t2 {
            display: block;
            font-size: 0.65rem;
            font-weight: 600;
            color: #6eb5ff; /* brand-soft / xanh như home .t2 */
            line-height: 1.2;
        }
    </style>

    {{-- Navigation Menu --}}
    <nav class="sidebar-nav mt-4 flex-1 overflow-y-auto min-h-0">
        <ul class="space-y-1">
            @if(auth()->check() && auth()->user()->can('dashboards.index'))
                <li>
                    <a href="{{ route('dashboard') }}" data-turbo-prefetch="hover"
                        class="sidebar-tooltip flex items-center px-4 py-2 text-sm hover:bg-gray-700 {{ request()->routeIs('dashboard') ? 'bg-blue-600' : '' }}"
                        data-tooltip="Bảng điều khiển">
                        <i class="bi bi-speedometer2 mr-3 flex-shrink-0"></i>
                        <span class="sidebar-text overflow-hidden text-ellipsis whitespace-nowrap">Bảng điều khiển</span>
                    </a>
                </li>
            @endIf

            {{-- Chỉ theo quyền. Trước đây OR thêm isSuperAdmin()/isManager() nên bỏ
                 tick trong ma trận vẫn thấy mục này. --}}
            @if(auth()->check() && Route::has('export-templates.portal.index') && auth()->user()->can('export-templates.index'))
                <li>
                    <a href="{{ route('export-templates.portal.index', ['portal' => 'dashboard']) }}"
                        class="sidebar-tooltip flex items-center px-4 py-2 text-sm hover:bg-gray-700 {{ request()->routeIs('export-templates.portal.*') && request()->route('portal') === 'dashboard' ? 'bg-blue-600' : '' }}"
                        data-tooltip="Mẫu xuất Dashboard">
                        <i class="bi bi-file-earmark-ruled mr-3 flex-shrink-0"></i>
                        <span class="sidebar-text overflow-hidden text-ellipsis whitespace-nowrap">Mẫu xuất Dashboard</span>
                    </a>
                </li>
            @endif

            @if(auth()->check() && auth()->user()->can('lms.index') && \Modules\Lms\Support\LmsAccess::usesAdminShell())
                <li>
                    <a href="{{ route('lms.hub') }}" data-turbo-prefetch="hover"
                        class="sidebar-tooltip flex items-center px-4 py-2 text-sm hover:bg-gray-700 {{ request()->routeIs('lms.*') && !request()->routeIs('lms.learn.*') && !request()->routeIs('lms.teach.*') ? 'bg-blue-600' : '' }}"
                        data-tooltip="LMS (quản trị)">
                        <i class="bi bi-mortarboard mr-3 flex-shrink-0"></i>
                        <span class="sidebar-text overflow-hidden text-ellipsis whitespace-nowrap">LMS</span>
                    </a>
                </li>
            @endif

            {{-- Chỉ theo quyền: chuỗi OR theo vai trò cũ khiến mọi manager/giảng viên
                 đều thấy Quản lý điểm dù ma trận đã bỏ tick. --}}
            @if(auth()->check() && Route::has('grades.hub') && auth()->user()->can('grades.index'))
                <li>
                    <a href="{{ route('grades.hub') }}" data-turbo="false"
                        class="sidebar-tooltip flex items-center px-4 py-2 text-sm hover:bg-gray-700 {{ request()->routeIs('grades.*') ? 'bg-blue-600' : '' }}"
                        data-tooltip="Quản lý điểm">
                        <i class="bi bi-clipboard-data mr-3 flex-shrink-0"></i>
                        <span class="sidebar-text overflow-hidden text-ellipsis whitespace-nowrap">Quản lý điểm</span>
                    </a>
                </li>
            @endif

            {{-- Wi‑Fi trường: chỉ admin (điểm danh QR LMS) --}}
            @if(auth()->check() && auth()->user()->can('campus-network.index'))
                <li>
                    <a href="{{ route('campus-network.index') }}" data-turbo-prefetch="hover"
                        class="sidebar-tooltip flex items-center px-4 py-2 text-sm hover:bg-gray-700 {{ request()->routeIs('campus-network.*') ? 'bg-blue-600' : '' }}"
                        data-tooltip="Wi-Fi trường (điểm danh)">
                        <i class="bi bi-wifi mr-3 flex-shrink-0"></i>
                        <span class="sidebar-text overflow-hidden text-ellipsis whitespace-nowrap">Wi‑Fi trường (điểm danh)</span>
                    </a>
                </li>
            @endif

            @if(auth()->check() && auth()->user()->can('training-schedules.index'))
                <li>
                    {{-- Chỉ 1 link → hub (điều hướng chi tiết trong hub, không submenu sidebar) --}}
                    <a href="{{ route('training-schedules.hub') }}"
                        class="sidebar-tooltip flex items-center px-4 py-2 text-sm hover:bg-gray-700 {{ request()->routeIs('training-schedules.*') ? 'bg-blue-600' : '' }}"
                        data-tooltip="Lịch đào tạo">
                        <i class="bi bi-calendar-event mr-3 flex-shrink-0"></i>
                        <span class="sidebar-text overflow-hidden text-ellipsis whitespace-nowrap">Lịch đào tạo</span>
                    </a>
                </li>
            @endif

            @if(auth()->check() && auth()->user()->can('instructors.index'))
                <li>
                    <a href="{{ route('instructors.index') }}"
                        class="sidebar-tooltip flex items-center px-4 py-2 text-sm hover:bg-gray-700 {{ request()->routeIs('instructors.*') ? 'bg-blue-600' : '' }}"
                        data-tooltip="Giảng viên">
                        <i class="bi bi-person-badge mr-3 flex-shrink-0"></i>
                        <span class="sidebar-text overflow-hidden text-ellipsis whitespace-nowrap">Giảng viên</span>
                    </a>
                </li>
            @endif

            @if(auth()->check() && auth()->user()->can('teaching-assignments.index'))
                <li>
                    <a href="{{ route('teaching-assignments.index') }}"
                        class="sidebar-tooltip flex items-center px-4 py-2 text-sm hover:bg-gray-700 {{ request()->routeIs('teaching-assignments.*') ? 'bg-blue-600' : '' }}"
                        data-tooltip="Phân công giảng dạy">
                        <i class="bi bi-easel2 mr-3 flex-shrink-0"></i>
                        <span class="sidebar-text overflow-hidden text-ellipsis whitespace-nowrap">Phân công giảng dạy</span>
                    </a>
                </li>
            @endif

            @if(auth()->check() && auth()->user()->can('essay-exams.index'))
                <li><a href="{{ route('essay-exams.index') }}" class="sidebar-tooltip flex items-center px-4 py-2 text-sm hover:bg-gray-700 {{ request()->routeIs('essay-exams.*') ? 'bg-blue-600' : '' }}" data-tooltip="Đề thi"><i class="bi bi-file-earmark-text mr-3 flex-shrink-0"></i><span class="sidebar-text">Đề thi</span></a></li>
            @endif
            @if(auth()->check() && auth()->user()->can('exam-organization.view'))
                <li><a href="{{ route('exam-organization.index') }}" class="sidebar-tooltip flex items-center px-4 py-2 text-sm hover:bg-gray-700 {{ request()->routeIs('exam-organization.*') ? 'bg-blue-600' : '' }}" data-tooltip="Tổ chức thi"><i class="bi bi-calendar2-check mr-3 flex-shrink-0"></i><span class="sidebar-text">Tổ chức thi</span></a></li>
            @endif
            @if(auth()->check() && auth()->user()->can('inventory.index'))
                <li><a href="{{ route('inventory.index') }}" class="sidebar-tooltip flex items-center px-4 py-2 text-sm hover:bg-gray-700 {{ request()->routeIs('inventory.*') ? 'bg-blue-600' : '' }}" data-tooltip="Quản lý vật tư"><i class="bi bi-box-seam mr-3 flex-shrink-0"></i><span class="sidebar-text">Quản lý vật tư</span></a></li>
            @endif
            @if(auth()->check() && auth()->user()->can('leave-management.index'))
                <li><a href="{{ route('leave-management.index') }}" class="sidebar-tooltip flex items-center px-4 py-2 text-sm hover:bg-gray-700 {{ request()->routeIs('leave-management.*') ? 'bg-blue-600' : '' }}" data-tooltip="Quản lý phép"><i class="bi bi-calendar2-week mr-3 flex-shrink-0"></i><span class="sidebar-text">Quản lý phép</span></a></li>
            @endif

            @if(auth()->check() && auth()->user()->can('instructor-schedule.index'))
                <li>
                    <a href="{{ route('instructor-schedule.index') }}"
                        class="sidebar-tooltip flex items-center px-4 py-2 text-sm hover:bg-gray-700 {{ request()->routeIs('instructor-schedule.*') ? 'bg-blue-600' : '' }}"
                        data-tooltip="Lịch giảng dạy của tôi">
                        <i class="bi bi-calendar-check mr-3 flex-shrink-0"></i>
                        <span class="sidebar-text overflow-hidden text-ellipsis whitespace-nowrap">Lịch giảng dạy của tôi</span>
                    </a>
                </li>
            @endif

            @if(auth()->check() && auth()->user()->isStudent() && auth()->user()->can('student-schedule.index'))
                <li>
                    <a href="{{ route('student-schedule.index') }}"
                        class="sidebar-tooltip flex items-center px-4 py-2 text-sm hover:bg-gray-700 {{ request()->routeIs('student-schedule.*') ? 'bg-blue-600' : '' }}"
                        data-tooltip="Lịch học của tôi">
                        <i class="bi bi-calendar2-week mr-3 flex-shrink-0"></i>
                        <span class="sidebar-text overflow-hidden text-ellipsis whitespace-nowrap">Lịch học của tôi</span>
                    </a>
                </li>
            @endif

            @if(auth()->check() && auth()->user()->can('specializations.index'))
                <li>
                    @php
                        $curriculumActive = request()->routeIs('specializations.*')
                            || request()->routeIs('training-systems.*')
                            || request()->routeIs('subjects.*')
                            || request()->routeIs('subject-lessons.*');
                    @endphp
                    <a href="{{ route('specializations.hub') }}"
                        class="sidebar-tooltip flex items-center px-4 py-2 text-sm hover:bg-gray-700 {{ $curriculumActive ? 'bg-blue-600' : '' }}"
                        data-tooltip="Hệ · Ngành · Môn học · Bài học">
                        <i class="bi bi-book mr-3 flex-shrink-0"></i>
                        <span class="sidebar-text overflow-hidden text-ellipsis whitespace-nowrap">Ngành đào tạo</span>
                    </a>
                    <ul class="sidebar-submenu ml-8 space-y-1 {{ $curriculumActive ? 'is-open' : 'hidden' }}">
                        <li>
                            <a href="{{ route('training-systems.index') }}"
                                class="block px-4 py-1 text-sm {{ request()->routeIs('training-systems.*') ? 'text-white' : 'text-gray-400 hover:text-white' }}">
                                Hệ đào tạo
                            </a>
                        </li>
                        <li>
                            <a href="{{ route('specializations.index') }}"
                                class="block px-4 py-1 text-sm {{ request()->routeIs('specializations.index') ? 'text-white' : 'text-gray-400 hover:text-white' }}">
                                Danh sách ngành đào tạo
                            </a>
                        </li>
                        {{-- Môn học / Bài học truy cập qua hub Ngành đào tạo, không nhân đôi ở sidebar --}}
                        @if(auth()->user()->can('specializations.create'))
                            <li>
                                <a href="{{ route('specializations.create') }}"
                                    class="block px-4 py-1 text-sm {{ request()->routeIs('specializations.create') ? 'text-white' : 'text-gray-400 hover:text-white' }}">
                                    Tạo ngành đào tạo mới
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif

            @if(auth()->check() && auth()->user()->can('classes.index'))
                <li>
                    <a href="{{ route('classes.index') }}"
                        class="sidebar-tooltip flex items-center px-4 py-2 text-sm hover:bg-gray-700 {{ request()->routeIs('classes.*') ? 'bg-blue-600' : '' }}"
                        data-tooltip="Lớp học">
                        <i class="bi bi-collection mr-3 flex-shrink-0"></i>
                        <span class="sidebar-text overflow-hidden text-ellipsis whitespace-nowrap">Lớp học</span>
                    </a>
                    <ul class="sidebar-submenu ml-8 space-y-1 {{ request()->routeIs('classes.*') ? 'is-open' : 'hidden' }}">
                        <li>
                            <a href="{{ route('classes.index') }}"
                                class="block px-4 py-1 text-sm {{ request()->routeIs('classes.index') ? 'text-white' : 'text-gray-400 hover:text-white' }}">
                                Danh sách lớp học
                            </a>
                        </li>
                        @if(auth()->user()->can('classes.create'))
                            <li>
                                <a href="{{ route('classes.create') }}"
                                    class="block px-4 py-1 text-sm {{ request()->routeIs('classes.create') ? 'text-white' : 'text-gray-400 hover:text-white' }}">
                                    Tạo lớp học mới
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif

            {{-- Môn học và Bài học nằm trong hub Ngành đào tạo, không tạo tab riêng --}}

            @if(auth()->check() && auth()->user()->can('units.index'))
                <li>
                    <a href="{{ route('units.index') }}"
                        class="sidebar-tooltip flex items-center px-4 py-2 text-sm hover:bg-gray-700 {{ request()->routeIs('units.*') ? 'bg-blue-600' : '' }}"
                        data-tooltip="Đơn vị">
                        <i class="bi bi-building mr-3 flex-shrink-0"></i>
                        <span class="sidebar-text overflow-hidden text-ellipsis whitespace-nowrap">Đơn vị</span>
                    </a>
                </li>
            @endif

            @if(auth()->check() && \App\Support\PermissionCheck::userCan('standard-hours.index'))
                <li>
                    <a href="{{ route('standard-hours.hub') }}"
                        class="sidebar-tooltip flex items-center px-4 py-2 text-sm hover:bg-gray-700 {{ request()->routeIs('standard-hours.*') ? 'bg-blue-600' : '' }}"
                        data-tooltip="Giờ chuẩn giảng viên">
                        <i class="bi bi-clock-history mr-3 flex-shrink-0"></i>
                        <span class="sidebar-text overflow-hidden text-ellipsis whitespace-nowrap">Giờ chuẩn GV</span>
                    </a>
                </li>
            @endif

            @if(auth()->check() && auth()->user()->can('trash.index'))
                <li>
                    <a href="{{ route('trash.index') }}"
                        class="sidebar-tooltip flex items-center px-4 py-2 text-sm hover:bg-gray-700 {{ request()->routeIs('trash.*') ? 'bg-blue-600' : '' }}"
                        data-tooltip="Thùng rác">
                        <i class="bi bi-trash3 mr-3 flex-shrink-0"></i>
                        <span class="sidebar-text overflow-hidden text-ellipsis whitespace-nowrap">Thùng rác</span>
                    </a>
                </li>
            @endif

            @if(auth()->check() && auth()->user()->can('buildings.index'))
                <li>
                    <a href="{{ route('buildings.index') }}"
                        class="sidebar-tooltip flex items-center px-4 py-2 text-sm hover:bg-gray-700 {{ request()->routeIs('buildings.*') ? 'bg-blue-600' : '' }}"
                        data-tooltip="Giảng đường">
                        <i class="bi bi-hospital mr-3 flex-shrink-0"></i>
                        <span class="sidebar-text overflow-hidden text-ellipsis whitespace-nowrap">Giảng đường</span>
                    </a>
                    <ul class="sidebar-submenu ml-8 space-y-1 {{ request()->routeIs('buildings.*') ? 'is-open' : 'hidden' }}">
                        <li>
                            <a href="{{ route('buildings.index') }}"
                                class="block px-4 py-1 text-sm {{ request()->routeIs('buildings.index') ? 'text-white' : 'text-gray-400 hover:text-white' }}">
                                Danh sách giảng đường
                            </a>
                        </li>
                        @if(auth()->user()->can('buildings.create'))
                            <li>
                                <a href="{{ route('buildings.create') }}"
                                    class="block px-4 py-1 text-sm {{ request()->routeIs('buildings.create') ? 'text-white' : 'text-gray-400 hover:text-white' }}">
                                    Tạo giảng đường mới
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif

            @if(auth()->check() && auth()->user()->can('classrooms.index'))
                <li>
                    <a href="{{ route('classrooms.index') }}"
                        class="sidebar-tooltip flex items-center px-4 py-2 text-sm hover:bg-gray-700 {{ request()->routeIs('classrooms.*') ? 'bg-blue-600' : '' }}"
                        data-tooltip="Phòng học">
                        <i class="bi bi-door-open mr-3 flex-shrink-0"></i>
                        <span class="sidebar-text overflow-hidden text-ellipsis whitespace-nowrap">Phòng học</span>
                    </a>
                    <ul class="sidebar-submenu ml-8 space-y-1 {{ request()->routeIs('classrooms.*') ? 'is-open' : 'hidden' }}">
                        <li>
                            <a href="{{ route('classrooms.index') }}"
                                class="block px-4 py-1 text-sm {{ request()->routeIs('classrooms.index') ? 'text-white' : 'text-gray-400 hover:text-white' }}">
                                Danh sách phòng học
                            </a>
                        </li>
                        @if(auth()->user()->can('classrooms.create'))
                            <li>
                                <a href="{{ route('classrooms.create') }}"
                                    class="block px-4 py-1 text-sm {{ request()->routeIs('classrooms.create') ? 'text-white' : 'text-gray-400 hover:text-white' }}">
                                    Tạo phòng học mới
                                </a>
                            </li>
                        @endif
                    </ul>
                </li>
            @endif
            @if(auth()->check())
                <li>
                    <a href="{{ route('signatures.index') }}"
                        class="sidebar-tooltip flex items-center px-4 py-2 text-sm hover:bg-gray-700 {{ request()->routeIs('signatures.*') ? 'bg-blue-600' : '' }}"
                        data-tooltip="Chữ ký số">
                        <i class="bi bi-pen mr-3 flex-shrink-0"></i>
                        <span class="sidebar-text overflow-hidden text-ellipsis whitespace-nowrap">Chữ ký số</span>
                    </a>
                </li>
            @endif
            @if(auth()->check() && auth()->user()->can('users.index'))
                <li>
                    <a href="{{ route('accounts.hub') }}"
                        class="sidebar-tooltip flex items-center px-4 py-2 text-sm hover:bg-gray-700 {{ request()->routeIs('accounts.*') || request()->routeIs('users.*') || request()->routeIs('students.*') || request()->routeIs('roles.*') || request()->routeIs('permissions.*') ? 'bg-blue-600' : '' }}"
                        data-tooltip="Quản lý tài khoản">
                        <i class="bi bi-people mr-3 flex-shrink-0"></i>
                        <span class="sidebar-text overflow-hidden text-ellipsis whitespace-nowrap">Quản lý tài khoản</span>
                    </a>
                </li>
            @endif
            @if(auth()->check() && auth()->user()->isSuperAdmin() && Route::has('database-management.index'))
                <li>
                    <a href="{{ route('database-management.index') }}"
                        class="sidebar-tooltip flex items-center px-4 py-2 text-sm hover:bg-gray-700 {{ request()->routeIs('database-management.*') ? 'bg-blue-600' : '' }}"
                        data-tooltip="Database Management Hub">
                        <i class="bi bi-diagram-3 mr-3 flex-shrink-0"></i>
                        <span class="sidebar-text overflow-hidden text-ellipsis whitespace-nowrap">Database Management</span>
                    </a>
                </li>
            @endif
            {{-- <li>
                <a href="#" class="flex items-center px-4 py-2 text-sm hover:bg-gray-700">
                    <i class="bi bi-bar-chart mr-3"></i>
                    Thống kê, báo cáo
                </a>
            </li> --}}
        </ul>
    </nav>
</div>

<script>
    function setSidebarTextVisible(visible) {
        const sidebarTexts = document.querySelectorAll('.sidebar-text');
        const sidebarTitle = document.getElementById('sidebar-title');

        sidebarTexts.forEach((text) => {
            text.classList.toggle('opacity-0', !visible);
            text.classList.toggle('w-0', !visible);
            text.classList.toggle('overflow-hidden', !visible);
            text.classList.toggle('pointer-events-none', !visible);
        });

        if (sidebarTitle) {
            sidebarTitle.classList.toggle('opacity-0', !visible);
            sidebarTitle.classList.toggle('w-0', !visible);
            sidebarTitle.classList.toggle('overflow-hidden', !visible);
        }
    }

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const toggleBtn = document.getElementById('sidebar-toggle');
        const icon = toggleBtn?.querySelector('i');

        if (sidebar.classList.contains('w-64')) {
            sidebar.classList.remove('w-64');
            sidebar.classList.add('w-16');
            setSidebarTextVisible(false);

            icon?.classList.remove('bi-chevron-left');
            icon?.classList.add('bi-chevron-right');

            localStorage.setItem('sidebarCollapsed', 'true');
        } else {
            sidebar.classList.remove('w-16');
            sidebar.classList.add('w-64');
            setSidebarTextVisible(true);

            icon?.classList.remove('bi-chevron-right');
            icon?.classList.add('bi-chevron-left');

            localStorage.setItem('sidebarCollapsed', 'false');
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('sidebar-toggle');
            const icon = toggleBtn?.querySelector('i');

            sidebar?.classList.remove('w-64');
            sidebar?.classList.add('w-16');
            setSidebarTextVisible(false);

            icon?.classList.remove('bi-chevron-left');
            icon?.classList.add('bi-chevron-right');
        }
    });
</script>
