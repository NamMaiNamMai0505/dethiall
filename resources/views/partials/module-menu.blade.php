@php
    $module = $module ?? 'inventory';
    $menus = [
        'inventory' => [
            'title' => 'Quản lý vật tư',
            'subtitle' => 'Danh mục, kho và toàn bộ quy trình vật tư',
            'icon' => 'bi-box-seam',
            'groups' => [
                ['label' => 'Tổng quan', 'items' => [
                    ['label' => 'Tổng quan', 'route' => 'inventory.index', 'icon' => 'bi-grid-1x2'],
                    ['label' => 'Tìm kiếm', 'route' => 'inventory.search', 'icon' => 'bi-search'],
                    ['label' => 'Vật tư của tôi', 'route' => 'inventory.my-catalog', 'icon' => 'bi-person-badge'],
                ]],
                ['label' => 'Danh mục & kho', 'items' => [
                    ['label' => 'Ngành vật tư', 'route' => 'inventory.category', 'icon' => 'bi-tags'],
                    ['label' => 'Tòa nhà', 'route' => 'buildings.index', 'icon' => 'bi-building'],
                    ['label' => 'Phòng', 'route' => 'classrooms.index', 'icon' => 'bi-door-open'],
                    ['label' => 'Kho vật tư', 'route' => 'inventory.warehouse', 'icon' => 'bi-boxes'],
                    ['label' => 'Cập nhật vật tư', 'route' => 'inventory.assets', 'icon' => 'bi-pencil-square'],
                ]],
                ['label' => 'Nghiệp vụ', 'items' => [
                    ['label' => 'Đề xuất', 'route' => 'inventory.proposals', 'icon' => 'bi-send'],
                    ['label' => 'Thanh lý', 'route' => 'inventory.liquidation', 'icon' => 'bi-recycle'],
                    ['label' => 'Phân công sửa chữa', 'route' => 'inventory.repairs', 'icon' => 'bi-tools'],
                    ['label' => 'Điều động & thu hồi', 'route' => 'inventory.transfers', 'icon' => 'bi-arrow-left-right'],
                ]],
                ['label' => 'Nhật ký & báo cáo', 'items' => [
                    ['label' => 'Nhật ký vật tư', 'route' => 'inventory.logs', 'icon' => 'bi-clock-history'],
                    ['label' => 'Báo cáo vật tư', 'route' => 'inventory.reports', 'icon' => 'bi-bar-chart'],
                    ['label' => 'Báo cáo biến động', 'route' => 'inventory.movement-report', 'icon' => 'bi-graph-up-arrow'],
                    ['label' => 'Mẫu báo cáo Word', 'route' => 'inventory.templates', 'icon' => 'bi-file-earmark-word'],
                ]],
            ],
        ],
        'leave' => [
            'title' => 'Quản lý phép',
            'subtitle' => 'Nhân sự, đơn phép, duyệt và báo cáo nghỉ phép',
            'icon' => 'bi-calendar2-check',
            'groups' => [
                ['label' => 'Tổng quan', 'items' => [
                    ['label' => 'Tổng quan', 'route' => 'leave-management.index', 'icon' => 'bi-grid-1x2'],
                    ['label' => 'Danh sách nhân sự', 'route' => 'leave-management.personnel', 'icon' => 'bi-people'],
                    ['label' => 'Danh bạ phép', 'route' => 'leave-management.directory', 'icon' => 'bi-journal-text'],
                    ['label' => 'Đề xuất nghỉ phép', 'route' => 'leave-management.requests', 'icon' => 'bi-file-earmark-plus'],
                    ['label' => 'Duyệt phép', 'route' => 'leave-management.approvals', 'icon' => 'bi-check2-circle'],
                ]],
                ['label' => 'Danh mục', 'items' => [
                    ['label' => 'Đơn vị', 'route' => 'leave-management.units', 'icon' => 'bi-diagram-3'],
                    ['label' => 'Lớp', 'route' => 'leave-management.classes', 'icon' => 'bi-people-fill'],
                    ['label' => 'Quy định phép', 'route' => 'leave-management.regulations.dashboard', 'icon' => 'bi-sliders'],
                    ['label' => 'Địa phương', 'route' => 'leave-management.localities', 'icon' => 'bi-geo-alt'],
                    ['label' => 'Chức vụ', 'route' => 'leave-management.positions', 'icon' => 'bi-person-vcard'],
                ]],
                ['label' => 'Lưu trữ & quản trị', 'items' => [
                    ['label' => 'Đợt nghỉ', 'route' => 'leave-management.batches', 'icon' => 'bi-calendar3'],
                    ['label' => 'Hồ sơ phép', 'route' => 'leave-management.records', 'icon' => 'bi-archive'],
                    ['label' => 'Cảnh báo', 'route' => 'leave-management.alerts', 'icon' => 'bi-bell'],
                    ['label' => 'Nhật ký xử lý', 'route' => 'leave-management.audit', 'icon' => 'bi-clock-history'],
                    ['label' => 'Cấu hình email', 'route' => 'leave-management.mail', 'icon' => 'bi-envelope'],
                ]],
                ['label' => 'Báo cáo', 'items' => [
                    ['label' => 'Báo cáo phép', 'route' => 'leave-management.reports', 'icon' => 'bi-bar-chart-line'],
                ]],
            ],
        ],
        'exam' => [
            'title' => 'Đề thi',
            'subtitle' => 'Soạn đề, duyệt, ngân hàng đề và rút đề',
            'icon' => 'bi-journal-richtext',
            'groups' => [
                ['label' => 'Soạn & quản lý', 'items' => [
                    ['label' => 'Danh sách đề', 'route' => 'essay-exams.index', 'icon' => 'bi-list-ul'],
                    ['label' => 'Đề của tôi', 'route' => 'essay-exams.mine', 'icon' => 'bi-person-lines-fill'],
                    ['label' => 'Soạn / Import đề', 'route' => 'essay-exams.create', 'icon' => 'bi-file-earmark-plus'],
                ]],
                ['label' => 'Kiểm duyệt & ngân hàng', 'items' => [
                    ['label' => 'Duyệt đề', 'route' => 'essay-exams.approval', 'icon' => 'bi-check2-square'],
                    ['label' => 'Ngân hàng đề tự luận', 'route' => 'essay-exams.bank', 'icon' => 'bi-archive'],
                    ['label' => 'Đáp án đề tích hợp', 'route' => 'essay-exams.integrated-answers.index', 'icon' => 'bi-card-checklist'],
                    ['label' => 'Đề đã sử dụng', 'route' => 'essay-exams.used', 'icon' => 'bi-eye'],
                ]],
                ['label' => 'Tổ chức rút đề', 'items' => [
                    ['label' => 'Rút đề', 'route' => 'essay-exams.draw', 'icon' => 'bi-shuffle'],
                    ['label' => 'Biên bản rút đề', 'route' => 'essay-exams.draw.minutes', 'icon' => 'bi-file-text'],
                    ['label' => 'Tổ chức thi', 'route' => 'exam-organization.index', 'icon' => 'bi-calendar2-check'],
                ]],
            ],
        ],
    ];
    $config = $menus[$module] ?? $menus['inventory'];
    if ($module === 'exam') {
        $hiddenExamRoutes = ['essay-exams.draw.minutes', 'exam-organization.index'];
        $config['groups'] = array_map(function (array $group) use ($hiddenExamRoutes): array {
            $group['items'] = array_values(array_filter($group['items'], fn (array $item): bool => !in_array($item['route'], $hiddenExamRoutes, true)));
            return $group;
        }, $config['groups']);
    }
    $routePermissions = [
        'inventory.' => 'inventory.index',
        'leave-management.index' => 'leave-management.index',
        'leave-management.personnel' => 'leave-management.personnel.index',
        'leave-management.directory' => 'leave-management.personnel.show',
        'leave-management.requests' => 'leave-management.requests.index',
        'leave-management.approvals' => 'leave-management.approvals.index',
        'leave-management.units' => 'leave-management.catalogs.index',
        'leave-management.classes' => 'leave-management.catalogs.index',
        'leave-management.regulations' => 'leave-management.regulations.index',
        'leave-management.regulations.dashboard' => 'leave-management.regulations.index',
        'leave-management.localities' => 'leave-management.catalogs.index',
        'leave-management.positions' => 'leave-management.catalogs.index',
        'leave-management.batches' => 'leave-management.batches.index',
        'leave-management.records' => 'leave-management.records.index',
        'leave-management.alerts' => 'leave-management.alerts.index',
        'leave-management.audit' => 'leave-management.audit.index',
        'leave-management.mail' => 'leave-management.mail.index',
        'leave-management.reports' => 'leave-management.reports.index',
        'essay-exams.index' => 'essay-exams.index',
        'essay-exams.mine' => 'essay-exams.index',
        'essay-exams.create' => 'essay-exams.authoring.index',
        'essay-exams.approval' => 'essay-exams.approval.index',
        'essay-exams.bank' => 'essay-exams.bank.index',
        'essay-exams.used' => 'essay-exams.bank.index',
        'essay-exams.draw' => 'essay-exams.draw.index',
        'exam-organization.index' => 'exam-organization.index',
    ];
    $config['groups'] = array_values(array_filter(array_map(function (array $group) use ($routePermissions): array {
        $group['items'] = array_values(array_filter($group['items'], function (array $item) use ($routePermissions): bool {
            $permission = null;
            foreach ($routePermissions as $route => $required) {
                if ($route === $item['route'] || (str_ends_with($route, '.') && str_starts_with($item['route'], $route))) { $permission = $required; break; }
            }
            return $permission === null || auth()->user()?->can($permission);
        }));
        return $group;
    }, $config['groups']), fn (array $group): bool => count($group['items']) > 0));
@endphp

<style>
    :root { --web-navy-900: #13234f; --web-navy-800: #1b326d; --web-blue-700: #29478f; --web-blue-600: #2563eb; }
    .module-menu-shell, .module-menu-shell + .space-y-5 { font-family: Inter, "Segoe UI", Arial, sans-serif; -webkit-font-smoothing: antialiased; }
    .module-menu-shell + .space-y-5 { max-width: 1440px; margin: 0 auto; }
    .module-menu-shell + .space-y-5 { color: #0f172a; }
    .module-menu-shell + .space-y-5 h1,
    .module-menu-shell + .space-y-5 h2,
    .module-menu-shell + .space-y-5 h3 { color: #0b1736; font-weight: 800; letter-spacing: -.015em; }
    .module-menu-shell + .space-y-5 label { color: #1e293b; font-weight: 700; }
    .module-menu-shell + .space-y-5 > form,
    .module-menu-shell + .space-y-5 > div > form,
    .module-menu-shell + .space-y-5 .rounded.border.bg-white { border-color: #e2e8f0; border-radius: 1rem; box-shadow: 0 10px 30px rgba(15, 23, 42, .06); }
    .module-menu-shell + .space-y-5 input:not([type=file]),
    .module-menu-shell + .space-y-5 select,
    .module-menu-shell + .space-y-5 textarea { border-color: #dbe3ef; border-radius: .7rem; transition: border-color .2s, box-shadow .2s; }
    .module-menu-shell + .space-y-5 input:focus,
    .module-menu-shell + .space-y-5 select:focus,
    .module-menu-shell + .space-y-5 textarea:focus { border-color: #3b82f6; box-shadow: 0 0 0 4px rgba(99, 102, 241, .12); outline: 0; }
    .module-menu-shell + .space-y-5 input:not([type=file]),
    .module-menu-shell + .space-y-5 select,
    .module-menu-shell + .space-y-5 textarea { color: #172554; font-weight: 600; }
    .module-menu-shell + .space-y-5 input::placeholder,
    .module-menu-shell + .space-y-5 textarea::placeholder { color: #64748b; opacity: 1; font-weight: 500; }
    .module-menu-shell + .space-y-5 table thead { color: #172554; font-weight: 800; }
    .module-menu-shell + .space-y-5 table tbody { color: #1e293b; }
    .module-menu-shell + .space-y-5 table td { font-weight: 550; }
    .module-menu-shell + .space-y-5 p.text-slate-500,
    .module-menu-shell + .space-y-5 p.text-slate-400 { color: #475569; }
    .module-menu-shell + .space-y-5 button.bg-blue-600,
    .module-menu-shell + .space-y-5 button.bg-blue-600 { background: linear-gradient(135deg, var(--web-navy-800), var(--web-blue-600)); box-shadow: 0 6px 14px rgba(19, 35, 79, .22); }
    .module-menu-shell + .space-y-5 button.bg-blue-600:hover,
    .module-menu-shell + .space-y-5 button.bg-blue-600:hover { filter: brightness(1.06); transform: translateY(-1px); }
    .module-menu-shell { border-color: #dbeafe; background: linear-gradient(135deg, #ffffff 0%, #f8fbff 100%); box-shadow: 0 12px 32px rgba(19, 35, 79, .10); }
    .module-menu-shell a:not([aria-current="page"]) { color: #1e3a5f !important; font-weight: 700; }
    .module-menu-shell a:not([aria-current="page"]):hover { color: #123d88 !important; }
    .module-menu-shell .text-slate-500 { color: #334155 !important; font-weight: 700; }
    .module-menu-shell .text-slate-400 { color: #475569 !important; font-weight: 700; }
    .module-menu-shell a[aria-current="page"] { color: #ffffff !important; font-weight: 800; }
</style>
<section class="module-menu-shell mb-7 overflow-hidden rounded-3xl border bg-white">
    <div class="relative overflow-hidden px-6 py-6 text-white sm:px-8" style="background: linear-gradient(115deg, #13234f 0%, #1b326d 56%, #29478f 100%);">
        <div class="absolute -right-16 -top-20 h-64 w-64 rounded-full bg-blue-400/20 blur-3xl"></div><div class="absolute -bottom-24 left-1/3 h-48 w-48 rounded-full bg-blue-400/20 blur-3xl"></div>
        <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div class="flex items-start gap-4"><span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl border border-white/20 bg-white/10 text-2xl shadow-xl backdrop-blur"><i class="bi {{ $config['icon'] }}"></i></span><div><div class="mb-2 flex flex-wrap items-center gap-2"><span class="rounded-full border border-blue-300/30 bg-blue-400/15 px-2.5 py-1 text-[10px] font-extrabold uppercase tracking-[.18em] text-blue-50">Workspace</span><span class="text-xs font-semibold text-blue-100">{{ count($config['groups']) }} nhóm chức năng</span></div><h2 class="text-2xl font-extrabold tracking-tight sm:text-3xl">{{ $config['title'] }}</h2><p class="mt-1 max-w-2xl text-sm font-medium text-blue-50">{{ $config['subtitle'] }}</p></div></div>
            <div class="grid grid-cols-2 gap-2 sm:flex"><div class="rounded-xl border border-white/15 bg-white/10 px-4 py-2 backdrop-blur"><p class="text-[10px] uppercase tracking-wider text-blue-200">Trạng thái</p><p class="mt-1 flex items-center gap-1.5 text-sm font-bold"><span class="h-2 w-2 rounded-full bg-emerald-400"></span> Đang hoạt động</p></div><div class="rounded-xl border border-white/15 bg-white/10 px-4 py-2 backdrop-blur"><p class="text-[10px] uppercase tracking-wider text-blue-200">Truy cập</p><p class="mt-1 text-sm font-bold">Toàn hệ thống</p></div></div>
        </div>
    </div>
    <div class="border-b border-slate-100 bg-slate-50/80 px-4 py-3 sm:px-6"><div class="flex flex-wrap items-center gap-2"><span class="mr-1 text-xs font-bold uppercase tracking-wider text-slate-400">Đi tới</span>@foreach($config['groups'] as $group)<span class="hidden h-4 w-px bg-slate-200 sm:block"></span><span class="mr-1 text-xs font-semibold text-slate-500">{{ $group['label'] }}</span>@foreach($group['items'] as $item) @if(Route::has($item['route'])) @php($active = request()->routeIs($item['route']) || ($item['route'] === 'essay-exams.index' && request()->routeIs('essay-exams.show'))) <a href="{{ route($item['route']) }}" aria-current="{{ $active ? 'page' : 'false' }}" class="inline-flex items-center gap-1.5 rounded-lg border px-2.5 py-1.5 text-xs font-semibold transition {{ $active ? 'border-blue-600 bg-blue-600 text-white shadow-md shadow-blue-200' : 'border-slate-200 bg-white text-slate-600 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700' }}"><i class="bi {{ $item['icon'] }}"></i>{{ $item['label'] }}</a> @endif @endforeach @endforeach</div></div>
    <div class="grid gap-5 p-5 md:grid-cols-2 xl:grid-cols-4">
        @foreach($config['groups'] as $group)
            <div class="rounded-2xl border border-slate-100 bg-white p-3 shadow-sm"><p class="mb-2 px-2 text-[10px] font-extrabold uppercase tracking-[.16em] text-slate-400">{{ $group['label'] }}</p><div class="space-y-1">@foreach($group['items'] as $item) @if(Route::has($item['route'])) @php($active = request()->routeIs($item['route']) || ($item['route'] === 'essay-exams.index' && request()->routeIs('essay-exams.show'))) <a href="{{ route($item['route']) }}" aria-current="{{ $active ? 'page' : 'false' }}" class="group flex items-center justify-between gap-2 rounded-xl px-3 py-2.5 text-sm transition {{ $active ? 'bg-blue-600 font-bold text-white shadow-md shadow-blue-100' : 'text-slate-600 hover:bg-blue-50 hover:text-blue-700' }}"><span class="flex items-center gap-2.5"><i class="bi {{ $item['icon'] }} w-4"></i><span>{{ $item['label'] }}</span></span><i class="bi bi-arrow-up-right text-xs opacity-0 transition group-hover:opacity-100 {{ $active ? 'opacity-100' : '' }}"></i></a> @endif @endforeach</div></div>
        @endforeach
    </div>
</section>
