@section('title', 'Quản lý nghiên cứu khoa học')
@section('page-title', 'Quản lý nghiên cứu khoa học')
@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
<x-breadcrumb :items="[['title' => 'Trang chủ'], ['title' => 'Nghiên cứu khoa học']]" />
<x-page-header title="QUẢN LÝ NGHIÊN CỨU KHOA HỌC" subtitle="Thông báo · đăng ký · nộp kết quả · dùng danh mục NCKH từ Giờ chuẩn GV" />
@include('partials.module-menu', ['module' => 'scientific-research'])
@include('scientific-research::partials.ui-style')

<div class="sr-page">
@if(session('success'))
    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 font-semibold text-emerald-700">{{ session('success') }}</div>
@endif
@if(session('error'))
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 font-semibold text-rose-700">{{ session('error') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 font-semibold text-rose-700">{{ $errors->first() }}</div>
@endif

<div class="mb-5 grid min-w-0 gap-4 md:grid-cols-3 xl:grid-cols-6">
    <div class="sr-stat p-4" style="--accent:#2563eb"><div class="flex items-center justify-between"><p class="text-sm font-semibold text-slate-500">Thông báo</p><i class="bi bi-megaphone text-blue-600"></i></div><p class="mt-2 text-3xl font-extrabold text-slate-900">{{ number_format($stats['announcements']) }}</p></div>
    <div class="sr-stat p-4" style="--accent:#059669"><div class="flex items-center justify-between"><p class="text-sm font-semibold text-slate-500">Đang mở</p><i class="bi bi-unlock text-emerald-600"></i></div><p class="mt-2 text-3xl font-extrabold text-emerald-700">{{ number_format($stats['open_announcements']) }}</p></div>
    <div class="sr-stat p-4" style="--accent:#4f46e5"><div class="flex items-center justify-between"><p class="text-sm font-semibold text-slate-500">Đăng ký</p><i class="bi bi-file-earmark-text text-indigo-600"></i></div><p class="mt-2 text-3xl font-extrabold text-indigo-700">{{ number_format($stats['registrations']) }}</p></div>
    <div class="sr-stat p-4" style="--accent:#0891b2"><div class="flex items-center justify-between"><p class="text-sm font-semibold text-slate-500">Đang thực hiện</p><i class="bi bi-hourglass-split text-cyan-600"></i></div><p class="mt-2 text-3xl font-extrabold text-cyan-700">{{ number_format($stats['in_progress']) }}</p></div>
    <div class="sr-stat p-4" style="--accent:#d97706"><div class="flex items-center justify-between"><p class="text-sm font-semibold text-slate-500">Kết quả đã nộp</p><i class="bi bi-upload text-amber-600"></i></div><p class="mt-2 text-3xl font-extrabold text-amber-700">{{ number_format($stats['submitted_results']) }}</p></div>
    <div class="sr-stat p-4" style="--accent:#7c3aed"><div class="flex items-center justify-between"><p class="text-sm font-semibold text-slate-500">Hoàn thành</p><i class="bi bi-check2-circle text-violet-600"></i></div><p class="mt-2 text-3xl font-extrabold text-violet-700">{{ number_format($stats['completed']) }}</p></div>
</div>

@if(in_array($section, ['dashboard', 'topics'], true))
<div class="grid min-w-0 gap-5 xl:grid-cols-3">
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm {{ $section === 'topics' ? 'xl:col-span-3' : 'xl:col-span-2' }}">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <div>
                <h2 class="text-lg font-extrabold text-slate-900">{{ request('review_queue') === '1' ? 'Thẩm định đề tài nghiên cứu khoa học' : 'Đăng ký nghiên cứu khoa học' }}</h2>
                <p class="text-sm text-slate-500">
                    {{ request('review_queue') === '1'
                        ? 'Hàng đợi hồ sơ chờ chỉ huy đơn vị/Ban Khoa học Quân sự xử lý.'
                        : 'Hồ sơ đi qua chỉ huy đơn vị, sau đó chuyển Ban Khoa học Quân sự tiếp nhận và thẩm định.' }}
                </p>
            </div>
            @if(request('review_queue') !== '1')
                <a href="{{ route('scientific-research.registrations.create') }}" class="sr-action bg-blue-600 px-4 py-2 text-white"><i class="bi bi-plus-lg"></i> Đăng ký</a>
            @endif
        </div>
        <form method="GET" action="{{ request()->routeIs('scientific-research.registrations.review') ? route('scientific-research.registrations.review') : route('scientific-research.registrations.index') }}" class="sr-toolbar mb-4 grid gap-3 p-3 md:grid-cols-4">
            @if(request('review_queue') === '1')
                <input type="hidden" name="review_queue" value="1">
            @endif
            <input name="q" value="{{ request('q') }}" placeholder="Tìm mã, tên đề tài, chủ nhiệm" class="rounded-lg border px-3 py-2.5 md:col-span-2">
            <select name="status" class="rounded-lg border px-3 py-2.5">
                <option value="">Tất cả trạng thái</option>
                @foreach($statuses as $status => $label)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ $label }}</option>
                @endforeach
            </select>
            <select name="category_id" class="rounded-lg border px-3 py-2.5">
                <option value="">Tất cả danh mục</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>{{ $category->code }} — {{ $category->name }}</option>
                @endforeach
            </select>
            <button class="sr-action bg-slate-900 px-4 py-2.5 text-white md:col-span-4"><i class="bi bi-funnel"></i> Lọc danh sách</button>
        </form>
        @if(request('review_queue') === '1')
            <div class="grid gap-3">
                @forelse($registrations as $registration)
                    <article class="rounded-lg border border-slate-200 bg-white p-4">
                        <div class="grid min-w-0 gap-4 lg:grid-cols-[minmax(0,1fr)_170px_150px] lg:items-center">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2 text-xs font-bold uppercase text-slate-500">
                                    <span>{{ $registration->project_code ?: 'Chưa cấp mã' }}</span>
                                    <span>·</span>
                                    <span>{{ $registration->researchCategory?->code }} — {{ $registration->researchCategory?->name }}</span>
                                </div>
                                <h3 class="mt-1 text-base font-extrabold text-slate-900">{{ $registration->title }}</h3>
                                <p class="mt-1 text-sm text-slate-600">Chủ nhiệm: <strong>{{ $registration->user?->name ?: '—' }}</strong></p>
                            </div>
                            <div class="text-sm text-slate-600">
                                <p class="font-bold text-slate-900">Thời gian</p>
                                <p>{{ $registration->start_date?->format('d/m/Y') ?: '—' }} → {{ $registration->end_date?->format('d/m/Y') ?: '—' }}</p>
                            </div>
                            <div class="flex flex-wrap items-center justify-start gap-2 lg:justify-end">
                                <span class="sr-badge bg-blue-50 text-blue-700">{{ $statuses[$registration->status] ?? $registration->status }}</span>
                                <a href="{{ route('scientific-research.registrations.review.show', $registration) }}" class="sr-action bg-blue-600 px-4 py-2 text-white"><i class="bi bi-clipboard-check"></i> Thẩm định</a>
                            </div>
                        </div>
                    </article>
                @empty
                    <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">Chưa có hồ sơ chờ thẩm định.</div>
                @endforelse
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1100px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr><th class="px-4 py-3">Chủ nhiệm đề tài</th><th class="px-4 py-3">Danh mục</th><th class="px-4 py-3">Tên đề tài / sản phẩm</th><th class="px-4 py-3">Thời gian</th><th class="px-4 py-3">Trạng thái</th><th class="sr-actions-cell px-4 py-3">Thao tác</th></tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($registrations as $registration)
                            <tr>
                                <td class="px-4 py-3 font-semibold text-slate-800">{{ $registration->user?->name }}</td>
                                <td class="px-4 py-3">{{ $registration->researchCategory?->code }} — {{ $registration->researchCategory?->name }}</td>
                                <td class="px-4 py-3">{{ $registration->title }}</td>
                                <td class="px-4 py-3">{{ $registration->start_date?->format('d/m/Y') ?: '—' }} → {{ $registration->end_date?->format('d/m/Y') ?: '—' }}</td>
                                <td class="px-4 py-3"><span class="sr-badge bg-blue-50 text-blue-700">{{ $statuses[$registration->status] ?? $registration->status }}</span></td>
                                <td class="sr-actions-cell px-4 py-3">
                                    <div class="sr-table-actions">
                                        <a href="{{ route('scientific-research.registrations.show', $registration) }}" class="sr-action sr-action-sm border border-blue-200 bg-blue-50 text-blue-700"><i class="bi bi-eye"></i> Chi tiết</a>
                                        <form method="POST" action="{{ route('scientific-research.registrations.destroy', $registration) }}" onsubmit="return confirm('Xóa đề tài NCKH này?')">
                                            @csrf @method('DELETE')
                                            <button class="sr-action sr-action-sm border border-rose-200 bg-rose-50 text-rose-700"><i class="bi bi-trash"></i> Xóa</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-10 text-center text-slate-500">Chưa có đăng ký NCKH.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
        <div class="mt-3">{{ $registrations->links() }}</div>
    </section>

    @if($section === 'dashboard')
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="text-lg font-extrabold text-slate-900">Đợt đăng ký NCKH</h2>
        <p class="mt-2 text-sm text-slate-600">Quản lý các đợt mở đăng ký đề tài, thời hạn nhận hồ sơ, biểu mẫu và hướng dẫn nộp hồ sơ.</p>
        <div class="mt-4 grid gap-2">
            <a href="{{ route('scientific-research.announcements.index') }}" class="sr-action bg-blue-600 px-4 py-2.5 text-white"><i class="bi bi-megaphone"></i> Quản lý đợt đăng ký</a>
            <a href="{{ route('scientific-research.portal') }}" class="sr-action border border-slate-200 px-4 py-2.5 text-slate-700"><i class="bi bi-globe2"></i> Cổng thông tin</a>
        </div>
    </section>
    @endif
</div>
@endif

@if(in_array($section, ['dashboard', 'announcements'], true))
<div class="mt-5 grid min-w-0 gap-5 xl:grid-cols-3">
    @if($section === 'announcements')
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <h2 class="text-lg font-extrabold text-slate-900">Tạo đợt đăng ký NCKH</h2>
            <form method="POST" action="{{ route('scientific-research.announcements.store') }}" enctype="multipart/form-data" class="mt-4 grid gap-3">
                @csrf
                <label class="text-sm font-semibold text-slate-700">Tên đợt đăng ký <span class="text-rose-500">*</span><input name="title" required placeholder="VD: Đăng ký đề tài NCKH năm 2026" class="mt-1 w-full rounded-lg border px-3 py-2"></label>
                <label class="text-sm font-semibold text-slate-700">Nội dung / hướng dẫn<textarea name="content" rows="4" placeholder="Nhập phạm vi đăng ký, yêu cầu hồ sơ, đơn vị tiếp nhận..." class="mt-1 w-full rounded-lg border px-3 py-2"></textarea></label>
                <label class="text-sm font-semibold text-slate-700">Hạn nhận hồ sơ<input name="closes_at" type="datetime-local" class="mt-1 w-full rounded-lg border px-3 py-2"></label>
                <label class="text-sm font-semibold text-slate-700">Tình trạng nhận hồ sơ<select name="status" class="mt-1 w-full rounded-lg border px-3 py-2"><option value="OPEN">Đang nhận hồ sơ</option><option value="CLOSED">Dừng nhận hồ sơ</option></select></label>
                <label class="text-sm font-semibold text-slate-700">Biểu mẫu hồ sơ<input name="template" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.txt" class="mt-1 w-full rounded-lg border px-3 py-2"></label>
                <button class="rounded-lg bg-slate-900 px-4 py-2.5 font-bold text-white">Lưu đợt đăng ký</button>
            </form>
        </section>
    @endif
<section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm {{ $section === 'announcements' ? 'xl:col-span-2' : 'xl:col-span-3' }}">
    <h2 class="mb-4 text-lg font-extrabold text-slate-900">Các đợt đăng ký NCKH</h2>
    <div class="grid gap-3 lg:grid-cols-2">
        @forelse($announcements as $announcement)
            <article class="rounded-xl border border-slate-200 p-4">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-slate-900">{{ $announcement->title }}</h3>
                        <p class="mt-1 text-sm text-slate-500">Hạn nhận hồ sơ: {{ $announcement->closes_at?->format('d/m/Y H:i') ?: 'Không giới hạn' }} · {{ $announcement->registrations_count }} đăng ký</p>
                    </div>
                    <span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $announcement->isOpen() ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $announcement->isOpen() ? 'Đang nhận hồ sơ' : 'Dừng nhận hồ sơ' }}</span>
                </div>
                @if($announcement->content)<p class="mt-3 text-sm text-slate-600">{{ $announcement->content }}</p>@endif
                @if($announcement->template_path)<a href="{{ asset('storage/'.$announcement->template_path) }}" class="mt-3 inline-block text-sm font-bold text-blue-700">Tải biểu mẫu: {{ $announcement->template_name }}</a>@endif
                <details class="mt-3 rounded-lg bg-slate-50 p-3">
                    <summary class="cursor-pointer text-sm font-bold text-slate-700">Cập nhật đợt đăng ký</summary>
                    <form method="POST" action="{{ route('scientific-research.announcements.update', $announcement) }}" enctype="multipart/form-data" class="mt-3 grid gap-2">
                        @csrf @method('PATCH')
                        <input name="title" value="{{ $announcement->title }}" required class="rounded border px-3 py-2 text-sm">
                        <textarea name="content" rows="2" class="rounded border px-3 py-2 text-sm">{{ $announcement->content }}</textarea>
                        <input name="closes_at" type="datetime-local" value="{{ $announcement->closes_at?->format('Y-m-d\TH:i') }}" class="rounded border px-3 py-2 text-sm">
                        <select name="status" class="rounded border px-3 py-2 text-sm"><option value="OPEN" @selected($announcement->status === 'OPEN')>Đang nhận hồ sơ</option><option value="CLOSED" @selected($announcement->status === 'CLOSED')>Dừng nhận hồ sơ</option></select>
                        <input name="template" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.txt" class="rounded border px-3 py-2 text-sm">
                        <button class="rounded bg-slate-900 px-3 py-2 text-sm font-bold text-white">Lưu cập nhật</button>
                    </form>
                    <form method="POST" action="{{ route('scientific-research.announcements.destroy', $announcement) }}" onsubmit="return confirm('Xóa đợt đăng ký này?')" class="mt-2">
                        @csrf @method('DELETE')
                        <button class="text-sm font-bold text-rose-700">Xóa đợt đăng ký</button>
                    </form>
                </details>
            </article>
        @empty
            <p class="text-sm text-slate-500">Chưa có đợt đăng ký NCKH.</p>
        @endforelse
    </div>
    <div class="mt-3">{{ $announcements->links() }}</div>
</section>
    @if($section === 'announcements')
        <div></div>
    @endif
</div>
@endif

@if(in_array($section, ['dashboard', 'results'], true))
<section class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="mb-4 text-lg font-extrabold text-slate-900">Kết quả nghiên cứu khoa học</h2>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-4 py-3">Đề tài</th><th class="px-4 py-3">Người thực hiện</th><th class="px-4 py-3">Danh mục</th><th class="px-4 py-3">Năm</th><th class="px-4 py-3">Ngày hoàn thành</th><th class="px-4 py-3">Trạng thái</th><th class="px-4 py-3"></th></tr></thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($results as $result)
                    <tr>
                        <td class="px-4 py-3 font-semibold">{{ $result->registration?->title }}</td>
                        <td class="px-4 py-3">{{ $result->registration?->user?->name }}</td>
                        <td class="px-4 py-3">{{ $result->registration?->researchCategory?->code }} — {{ $result->registration?->researchCategory?->name }}</td>
                        <td class="px-4 py-3">{{ $result->implementation_year }}</td>
                        <td class="px-4 py-3">{{ $result->completed_on?->format('d/m/Y') ?: '—' }}</td>
                        <td class="px-4 py-3"><span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">{{ $result->status }}</span></td>
                        <td class="px-4 py-3 text-right">
                            <details class="inline-block text-left">
                                <summary class="cursor-pointer font-bold text-blue-700">Xử lý</summary>
                                <form method="POST" action="{{ route('scientific-research.results.update', $result) }}" class="mt-2 grid w-72 gap-2 rounded-lg border bg-white p-3 shadow">
                                    @csrf @method('PATCH')
                                    <input name="completed_on" type="date" value="{{ $result->completed_on?->format('Y-m-d') }}" class="rounded border px-2 py-2 text-sm">
                                    <input name="implementation_year" type="number" min="2000" max="2200" value="{{ $result->implementation_year }}" required class="rounded border px-2 py-2 text-sm">
                                    <select name="status" class="rounded border px-2 py-2 text-sm"><option value="SUBMITTED" @selected($result->status === 'SUBMITTED')>Đã nộp</option><option value="ACCEPTED" @selected($result->status === 'ACCEPTED')>Đã nghiệm thu</option><option value="NEEDS_REVISION" @selected($result->status === 'NEEDS_REVISION')>Cần bổ sung</option><option value="REJECTED" @selected($result->status === 'REJECTED')>Từ chối</option></select>
                                    <textarea name="summary" rows="2" class="rounded border px-2 py-2 text-sm">{{ $result->summary }}</textarea>
                                    <input name="review_note" value="{{ $result->review_note }}" placeholder="Ghi chú" class="rounded border px-2 py-2 text-sm">
                                    <button class="rounded bg-slate-900 px-3 py-2 text-sm font-bold text-white">Lưu</button>
                                </form>
                                <form method="POST" action="{{ route('scientific-research.results.destroy', $result) }}" onsubmit="return confirm('Xóa kết quả này?')" class="mt-1">
                                    @csrf @method('DELETE')
                                    <button class="text-sm font-bold text-rose-700">Xóa</button>
                                </form>
                            </details>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">Chưa có kết quả NCKH.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $results->links() }}</div>
</section>
@endif

@if(in_array($section, ['staff','plans','councils','funding','products','repository'], true))
<section class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    @if($section === 'staff')
        <div class="sr-section-head">
            <div>
                <h2>Cán bộ NCKH</h2>
                <p>Chọn đề tài đã đăng ký để cập nhật thông tin năng lực, chuyên môn và thành tích của các cán bộ/thành viên trong đề tài đó.</p>
            </div>
        </div>
        @php
            $selectedStaffRegistration = $allRegistrations->firstWhere('id', (int) request('registration_id'));
            $staffTeam = collect();
            if ($selectedStaffRegistration) {
                $staffTeam = collect([
                    [
                        'user' => $selectedStaffRegistration->user,
                        'role' => 'Chủ nhiệm đề tài',
                    ],
                ])->merge($selectedStaffRegistration->members->map(fn ($member) => [
                    'user' => $member->user,
                    'role' => $member->role ?: 'Thành viên',
                ]))->filter(fn ($row) => $row['user']);
            }
        @endphp
        <form method="GET" action="{{ route('scientific-research.staff.index') }}" class="mb-5 rounded-xl border bg-slate-50 p-4">
            <label class="text-sm font-semibold text-slate-700">Đề tài đã đăng ký
                <select name="registration_id" class="mt-1 w-full rounded-lg border px-3 py-2.5" onchange="this.form.submit()">
                    <option value="">Chọn đề tài để cập nhật cán bộ/thành viên</option>
                    @foreach($allRegistrations as $registration)
                        <option value="{{ $registration->id }}" @selected((int) request('registration_id') === (int) $registration->id)>{{ $registration->project_code ?: 'Chưa cấp mã' }} — {{ $registration->title }}</option>
                    @endforeach
                </select>
            </label>
        </form>
        @if($selectedStaffRegistration)
            <div class="mb-5 rounded-xl border border-blue-100 bg-blue-50/40 p-4">
                <p class="font-extrabold text-slate-900">{{ $selectedStaffRegistration->title }}</p>
                <p class="mt-1 text-sm text-slate-600">{{ $selectedStaffRegistration->project_code ?: 'Chưa cấp mã' }} · {{ $selectedStaffRegistration->researchCategory?->code }} — {{ $selectedStaffRegistration->researchCategory?->name }}</p>
            </div>
            <div class="mb-5 grid gap-3 md:grid-cols-2">
                @foreach($staffTeam as $row)
                    @php
                        $staffUser = $row['user'];
                        $profile = $staffProfilesByUser->get($staffUser->id);
                    @endphp
                    <form method="POST" action="{{ $profile ? route('scientific-research.staff.update', $profile) : route('scientific-research.staff.store') }}" class="rounded-xl border bg-white p-4 shadow-sm">
                        @csrf
                        @if($profile) @method('PATCH') @endif
                        <input type="hidden" name="user_id" value="{{ $staffUser->id }}">
                        <div class="mb-3">
                            <p class="font-extrabold text-slate-900">{{ $staffUser->name }}</p>
                            <p class="text-sm text-slate-500">{{ $row['role'] }} · {{ $staffUser->code ?: 'Chưa có mã' }} · {{ $staffUser->unit?->name ?: 'Chưa có đơn vị' }}</p>
                        </div>
                        <div class="grid gap-3 md:grid-cols-2">
                            <label class="text-sm font-semibold text-slate-700">Trình độ<input name="degree" value="{{ $profile?->degree }}" placeholder="VD: Thạc sĩ, Tiến sĩ..." class="mt-1 w-full rounded-lg border px-3 py-2.5"></label>
                            <label class="text-sm font-semibold text-slate-700">Chuyên môn<input name="specialization" value="{{ $profile?->specialization }}" placeholder="Chuyên môn chính" class="mt-1 w-full rounded-lg border px-3 py-2.5"></label>
                            <label class="text-sm font-semibold text-slate-700 md:col-span-2">Lĩnh vực nghiên cứu<input name="research_fields" value="{{ $profile?->research_fields }}" placeholder="VD: Điều dưỡng, y học cơ sở, công nghệ thông tin..." class="mt-1 w-full rounded-lg border px-3 py-2.5"></label>
                            <label class="text-sm font-semibold text-slate-700 md:col-span-2">Thành tích khoa học<textarea name="scientific_achievements" rows="2" placeholder="Bài báo, đề tài đã tham gia, giải thưởng, kinh nghiệm nghiên cứu..." class="mt-1 w-full rounded-lg border px-3 py-2.5">{{ $profile?->scientific_achievements }}</textarea></label>
                        </div>
                        <button class="mt-3 rounded-lg bg-blue-600 px-4 py-2.5 font-bold text-white">Lưu thông tin cán bộ</button>
                    </form>
                @endforeach
            </div>
        @else
            <div class="mb-5 rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">Chọn một đề tài đã đăng ký để cập nhật thông tin các cán bộ/thành viên.</div>
        @endif
        <div class="grid gap-3 md:grid-cols-2">
            @forelse($staffProfiles as $item)
                <article class="rounded-xl border bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-extrabold text-slate-900">{{ $item->full_name }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $item->unit_name ?: 'Chưa có đơn vị' }} · {{ $item->degree ?: 'Chưa có trình độ' }}</p>
                        </div>
                        <button type="button" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700" onclick="document.getElementById('sr-staff-setup-{{ $item->id }}').classList.toggle('hidden')">Thiết lập</button>
                    </div>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                            <p class="text-[11px] font-bold uppercase text-slate-500">Chuyên môn</p>
                            <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $item->specialization ?: 'Chưa có chuyên môn' }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                            <p class="text-[11px] font-bold uppercase text-slate-500">Lĩnh vực nghiên cứu</p>
                            <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $item->research_fields ?: 'Chưa có lĩnh vực nghiên cứu' }}</p>
                        </div>
                    </div>
                    @if($item->scientific_achievements)
                        <div class="mt-3 rounded-lg border border-slate-100 bg-white p-3 text-sm text-slate-700">
                            <span class="font-semibold">Thành tích khoa học:</span> {{ $item->scientific_achievements }}
                        </div>
                    @endif
                    <div id="sr-staff-setup-{{ $item->id }}" class="mt-3 hidden rounded-lg border border-amber-200 bg-amber-50/40 p-3">
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <p class="text-sm font-extrabold text-slate-900">Cập nhật thông tin cán bộ NCKH</p>
                            <button type="button" class="rounded-lg border bg-white px-3 py-1.5 text-xs font-bold text-slate-600" onclick="this.closest('[id^=sr-staff-setup-]').classList.add('hidden')">Thu gọn</button>
                        </div>
                        <form method="POST" action="{{ route('scientific-research.staff.update', $item) }}" class="grid gap-2 md:grid-cols-2">
                            @csrf @method('PATCH')
                            <label class="text-xs font-semibold text-slate-600 md:col-span-2">Cán bộ/thành viên trong hệ thống
                                <select name="user_id" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
                                    <option value="">Không gắn tài khoản</option>
                                    @foreach($internalUsers as $staffUser)
                                        <option value="{{ $staffUser->id }}" @selected((int) $item->user_id === (int) $staffUser->id)>{{ $staffUser->name }}{{ $staffUser->code ? ' - '.$staffUser->code : '' }}{{ $staffUser->unit?->name ? ' - '.$staffUser->unit->name : '' }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="text-xs font-semibold text-slate-600">Họ tên<input name="full_name" value="{{ $item->full_name }}" required class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                            <label class="text-xs font-semibold text-slate-600">Đơn vị<input name="unit_name" value="{{ $item->unit_name }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                            <label class="text-xs font-semibold text-slate-600">Chức danh<input name="academic_title" value="{{ $item->academic_title }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                            <label class="text-xs font-semibold text-slate-600">Trình độ<input name="degree" value="{{ $item->degree }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                            <label class="text-xs font-semibold text-slate-600">Chuyên môn<input name="specialization" value="{{ $item->specialization }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                            <label class="text-xs font-semibold text-slate-600">Lĩnh vực nghiên cứu<input name="research_fields" value="{{ $item->research_fields }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                            <label class="text-xs font-semibold text-slate-600 md:col-span-2">Thành tích khoa học<textarea name="scientific_achievements" rows="2" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">{{ $item->scientific_achievements }}</textarea></label>
                            <button class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-bold text-white md:col-span-2">Lưu hồ sơ</button>
                        </form>
                        <form method="POST" action="{{ route('scientific-research.staff.destroy', $item) }}" onsubmit="return confirm('Xóa hồ sơ cán bộ này?')" class="mt-2">@csrf @method('DELETE')<button class="text-sm font-bold text-rose-700">Xóa hồ sơ</button></form>
                    </div>
                </article>
            @empty
                <div class="rounded-xl border bg-white px-4 py-8 text-center text-slate-500">Chưa có thông tin cán bộ NCKH.</div>
            @endforelse
        </div>
        <div class="mt-3">{{ $staffProfiles->links() }}</div>
    @elseif($section === 'plans')
        <div class="sr-section-head">
            <div>
                <h2>Quản lý kế hoạch NCKH</h2>
                <p>Lập kế hoạch năm học, giao nhiệm vụ nghiên cứu, theo dõi tiến độ và đánh giá kết quả thực hiện.</p>
            </div>
            <div class="sr-section-kpis">
                <span><b>{{ number_format($stats['plans']) }}</b> kế hoạch</span>
                <span><b>{{ number_format($stats['registrations']) }}</b> đề tài</span>
            </div>
        </div>
        <div class="sr-workflow mb-5">
            <span>1. Chọn đề tài đã đăng ký</span>
            <span>2. Lập kế hoạch</span>
            <span>3. Giao nhiệm vụ</span>
            <span>4. Theo dõi thực hiện</span>
            <span>5. Đánh giá kết quả</span>
        </div>
        @php
            $planAssigneesByRegistration = $allRegistrations->mapWithKeys(function ($registration) {
                $assignees = [];
                if ($registration->user) {
                    $assignees[] = [
                        'key' => 'lead-'.$registration->user->id,
                        'name' => $registration->user->name,
                        'role' => 'Chủ nhiệm đề tài',
                        'unit' => $registration->user->unit?->name,
                    ];
                }
                foreach ($registration->members as $member) {
                    $assignees[] = [
                        'key' => 'member-'.$member->id,
                        'name' => $member->full_name ?: $member->user?->name,
                        'role' => $member->role ?: 'Thành viên',
                        'unit' => $member->unit_name ?: $member->user?->unit?->name,
                    ];
                }

                return [(string) $registration->id => $assignees];
            });
            $planTaskRows = function ($value) {
                $decoded = json_decode((string) $value, true);

                return collect(($decoded['type'] ?? null) === 'by_assignee' ? ($decoded['rows'] ?? []) : [])->keyBy('key');
            };
            $planTaskStatusLabels = [
                'ASSIGNED' => 'Đã giao',
                'IN_PROGRESS' => 'Đang thực hiện',
                'COMPLETED' => 'Hoàn thành',
                'BLOCKED' => 'Đang vướng',
            ];
            $planStatusLabels = [
                'DRAFT' => 'Dự thảo',
                'APPROVED' => 'Đã duyệt',
                'IN_PROGRESS' => 'Đang thực hiện',
                'EVALUATED' => 'Đã đánh giá',
                'CLOSED' => 'Đóng',
            ];
        @endphp
        @if(\App\Support\PermissionCheck::can(auth()->user(), 'scientific-research.plans.create'))
        <form method="POST" action="{{ route('scientific-research.plans.store') }}" class="mb-5 grid gap-3 md:grid-cols-4">@csrf
            <label class="text-sm font-semibold text-slate-700 md:col-span-2">Đề tài đã đăng ký <span class="text-rose-500">*</span>
                <select name="registration_id" required id="sr-plan-registration" class="mt-1 w-full rounded-lg border px-3 py-2.5">
                    <option value="">Chọn đề tài đã đăng ký</option>
                    @foreach($allRegistrations as $registration)
                        <option value="{{ $registration->id }}">{{ $registration->project_code ?: 'Chưa cấp mã' }} — {{ $registration->title }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-slate-700">Năm học
                <input name="school_year" placeholder="VD: 2026-2027" class="mt-1 w-full rounded-lg border px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold text-slate-700">Năm kế hoạch
                <input name="plan_year" type="number" min="2000" max="2200" value="{{ now()->year }}" placeholder="VD: {{ now()->year }}" class="mt-1 w-full rounded-lg border px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold text-slate-700">Trạng thái
                <select name="status" class="mt-1 w-full rounded-lg border px-3 py-2.5"><option value="DRAFT">Dự thảo</option><option value="APPROVED">Đã duyệt</option><option value="IN_PROGRESS">Đang thực hiện</option><option value="EVALUATED">Đã đánh giá</option><option value="CLOSED">Đóng</option></select>
            </label>
            <label class="text-sm font-semibold text-slate-700">Ngày bắt đầu
                <input name="starts_on" type="date" class="mt-1 w-full rounded-lg border px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold text-slate-700">Ngày kết thúc
                <input name="ends_on" type="date" class="mt-1 w-full rounded-lg border px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold text-slate-700 md:col-span-2">Mục tiêu
                <textarea name="objectives" rows="2" placeholder="Nhập mục tiêu kế hoạch" class="mt-1 w-full rounded-lg border px-3 py-2.5"></textarea>
            </label>
            <div class="rounded-lg border bg-slate-50 p-3 md:col-span-4">
                <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                    <p class="text-sm font-semibold text-slate-700">Giao nhiệm vụ theo từng người trong tổ đề tài</p>
                    <span class="text-xs font-semibold text-slate-500">Chọn đề tài để hiện danh sách</span>
                </div>
                <div id="sr-plan-assignee-tasks" class="grid gap-3 md:grid-cols-2">
                    <p class="text-sm text-slate-500 md:col-span-2">Chưa chọn đề tài.</p>
                </div>
                <textarea name="assigned_tasks" rows="2" placeholder="Ghi chú nhiệm vụ chung nếu cần" class="mt-3 w-full rounded-lg border px-3 py-2.5"></textarea>
            </div>
            <button class="rounded-lg bg-blue-600 px-4 py-2.5 font-bold text-white md:col-span-4"><i class="bi bi-calendar2-check"></i> Tạo kế hoạch theo dõi</button>
        </form>
        @endif
        <div class="mb-3 grid gap-3 rounded-xl border bg-white p-3 md:grid-cols-4">
            <label class="text-xs font-bold uppercase text-slate-500">Tìm kế hoạch / đề tài
                <input id="sr-plan-filter-search" placeholder="Nhập tên kế hoạch hoặc đề tài" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm normal-case">
            </label>
            <label class="text-xs font-bold uppercase text-slate-500">Trạng thái
                <select id="sr-plan-filter-status" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm normal-case">
                    <option value="">Tất cả trạng thái</option>
                    <option value="DRAFT">Dự thảo</option>
                    <option value="APPROVED">Đã duyệt</option>
                    <option value="IN_PROGRESS">Đang thực hiện</option>
                    <option value="EVALUATED">Đã đánh giá</option>
                    <option value="CLOSED">Đóng</option>
                </select>
            </label>
            <label class="text-xs font-bold uppercase text-slate-500">Tiến độ
                <select id="sr-plan-filter-progress" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm normal-case">
                    <option value="">Tất cả tiến độ</option>
                    <option value="not-started">Chưa bắt đầu</option>
                    <option value="doing">Đang làm</option>
                    <option value="done">Hoàn thành</option>
                </select>
            </label>
            <div class="flex items-end">
                <button type="button" id="sr-plan-filter-reset" class="w-full rounded-lg border px-3 py-2 text-sm font-bold text-slate-700">Xóa lọc</button>
            </div>
        </div>
        <div class="grid gap-3">
            @forelse($plans as $item)
                @php
                    $savedPlanTasks = $planTaskRows($item->assigned_tasks);
                    $canManagePlan = \App\Support\PermissionCheck::can(auth()->user(), 'scientific-research.plans.edit');
                    $myPlanTasks = $savedPlanTasks->filter(function ($task) use ($item) {
                        $taskKey = (string) ($task['key'] ?? '');
                        if (str_starts_with($taskKey, 'lead-')) {
                            return (int) str_replace('lead-', '', $taskKey) === (int) auth()->id()
                                && (int) $item->registration?->user_id === (int) auth()->id();
                        }
                        if (str_starts_with($taskKey, 'member-')) {
                            return $item->registration?->members->contains(fn ($member) => (int) $member->id === (int) str_replace('member-', '', $taskKey) && (int) $member->user_id === (int) auth()->id());
                        }
                        return false;
                    });
                    $visiblePlanTasks = $canManagePlan ? $savedPlanTasks : $myPlanTasks;
                    $teamProgress = $visiblePlanTasks->isNotEmpty()
                        ? (int) round($visiblePlanTasks->avg(fn ($task) => (int) ($task['progress_percent'] ?? 0)))
                        : null;
                @endphp
                <article class="rounded-xl border bg-white p-4 shadow-sm" data-plan-row data-search="{{ \Illuminate\Support\Str::lower($item->name.' '.$item->registration?->title.' '.$item->unit_name) }}" data-status="{{ $item->status }}" data-progress="{{ $teamProgress ?? 0 }}">
                    <div class="flex flex-col gap-3 {{ $canManagePlan ? 'lg:flex-row lg:items-start lg:justify-between' : '' }}">
                        <div class="min-w-0 flex-1">
                            <p class="text-base font-extrabold text-slate-900">{{ $item->name }}</p>
                            <div class="mt-2 rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                                <p class="text-[11px] font-bold uppercase text-slate-500">Đề tài</p>
                                <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $item->registration?->title ?: 'Chưa gắn đề tài' }}</p>
                            </div>
                            <div class="mt-3 grid gap-2 sm:grid-cols-2 xl:grid-cols-4">
                                <div class="rounded-lg border border-slate-100 bg-white px-3 py-2">
                                    <p class="text-[11px] font-bold uppercase text-slate-500">Đơn vị</p>
                                    <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $item->unit_name ?: $item->registration?->user?->unit?->name ?: 'Chưa có đơn vị' }}</p>
                                </div>
                                <div class="rounded-lg border border-slate-100 bg-white px-3 py-2">
                                    <p class="text-[11px] font-bold uppercase text-slate-500">Năm học/KH</p>
                                    <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $item->school_year ?: $item->plan_year ?: 'Chưa có năm kế hoạch' }}</p>
                                </div>
                                <div class="rounded-lg border border-slate-100 bg-white px-3 py-2">
                                    <p class="text-[11px] font-bold uppercase text-slate-500">Thời gian</p>
                                    <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $item->starts_on?->format('d/m/Y') ?: '—' }} - {{ $item->ends_on?->format('d/m/Y') ?: '—' }}</p>
                                </div>
                                <div class="rounded-lg border border-slate-100 bg-white px-3 py-2">
                                    <p class="text-[11px] font-bold uppercase text-slate-500">Trạng thái</p>
                                    <p class="mt-0.5 text-sm font-semibold text-blue-700">{{ $planStatusLabels[$item->status] ?? $item->status }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="w-full shrink-0 rounded-lg border border-emerald-100 bg-emerald-50/50 p-3 {{ $canManagePlan ? 'lg:w-64' : '' }}">
                            @if(($teamProgress ?? null) !== null)
                                <div class="mb-1 flex justify-between text-xs font-semibold text-slate-600"><span>{{ $canManagePlan ? 'Tiến độ đề tài' : 'Tiến độ của tôi' }}</span><span>{{ $teamProgress }}%</span></div>
                                <div class="h-2 overflow-hidden rounded-full bg-white"><div class="h-full rounded-full bg-emerald-500" style="width: {{ $teamProgress }}%"></div></div>
                            @else
                                <p class="text-xs font-semibold text-slate-400">{{ $canManagePlan ? 'Tiến độ đề tài' : 'Tiến độ của tôi' }}: chưa có dữ liệu</p>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 grid gap-3 {{ $canManagePlan ? 'lg:grid-cols-[1fr_auto]' : '' }}">
                        <div class="rounded-lg border border-blue-100 bg-blue-50/40 p-3">
                            <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-extrabold text-slate-900">Việc của tôi</p>
                                <span class="text-xs font-semibold text-slate-500">{{ $myPlanTasks->count() }} nhiệm vụ</span>
                            </div>
                            @if($myPlanTasks->isNotEmpty())
                                <div class="grid gap-2">
                                    @foreach($myPlanTasks as $task)
                                        @php
                                            $taskKey = (string) ($task['key'] ?? '');
                                            $taskProgress = (int) ($task['progress_percent'] ?? 0);
                                        @endphp
                                        <form method="POST" action="{{ route('scientific-research.plans.tasks.update', [$item, $taskKey]) }}" class="grid gap-2 rounded-lg border border-blue-100 bg-white p-3 sm:grid-cols-6">
                                            @csrf @method('PATCH')
                                            <div class="sm:col-span-6">
                                                <p class="font-semibold text-slate-800">{{ $task['task'] ?? 'Chưa nhập nội dung nhiệm vụ' }}</p>
                                                <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                                    <div class="h-full rounded-full bg-blue-500" style="width: {{ $taskProgress }}%"></div>
                                                </div>
                                            </div>
                                            <label class="text-xs font-semibold text-slate-600 sm:col-span-2">Trạng thái
                                                <select name="status" class="mt-1 w-full rounded border px-2 py-1.5 text-sm">
                                                    @foreach($planTaskStatusLabels as $value => $label)
                                                        <option value="{{ $value }}" @selected(($task['status'] ?? 'ASSIGNED') === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            <label class="text-xs font-semibold text-slate-600 sm:col-span-1">Tiến độ
                                                <input name="progress_percent" type="number" min="0" max="100" value="{{ $taskProgress }}" class="mt-1 w-full rounded border px-2 py-1.5 text-sm">
                                            </label>
                                            <label class="text-xs font-semibold text-slate-600 sm:col-span-2">Ngày hoàn thành
                                                <input name="completed_on" type="date" value="{{ $task['completed_on'] ?? '' }}" class="mt-1 w-full rounded border px-2 py-1.5 text-sm">
                                            </label>
                                            <div class="flex items-end sm:col-span-1">
                                                <button class="w-full rounded bg-blue-600 px-3 py-2 text-xs font-bold text-white">Gửi báo cáo</button>
                                            </div>
                                            <label class="text-xs font-semibold text-slate-600 sm:col-span-6">Ghi chú/kết quả
                                                <input name="result_note" value="{{ $task['result_note'] ?? '' }}" placeholder="Nội dung báo cáo gửi chủ nhiệm..." class="mt-1 w-full rounded border px-2 py-1.5 text-sm">
                                            </label>
                                        </form>
                                    @endforeach
                                </div>
                            @else
                                <p class="rounded-lg border border-dashed border-blue-200 bg-white px-3 py-4 text-sm text-slate-500">Bạn chưa có nhiệm vụ riêng trong kế hoạch này.</p>
                            @endif
                        </div>
                        <div class="flex flex-wrap items-start gap-2 {{ $canManagePlan ? 'lg:w-40 lg:flex-col' : '' }}">
                            <button type="button" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700" onclick="document.getElementById('sr-plan-detail-{{ $item->id }}').classList.toggle('hidden')">Chi tiết</button>
                            @if(\App\Support\PermissionCheck::can(auth()->user(), 'scientific-research.plans.edit'))
                                <button type="button" class="inline-flex items-center rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700" onclick="document.getElementById('sr-plan-setup-{{ $item->id }}').classList.toggle('hidden')">Thiết lập</button>
                            @endif
                        </div>
                    </div>

                            <div id="sr-plan-detail-{{ $item->id }}" class="mt-4 hidden rounded-xl border border-slate-200 bg-slate-50 p-4 text-left">
                                <div>
                                    <div class="mb-4 flex items-start justify-between gap-3 border-b pb-3">
                                        <div>
                                            <p class="text-lg font-extrabold text-slate-900">{{ $item->name }}</p>
                                            <p class="mt-1 text-sm font-semibold text-slate-700">{{ $item->registration?->title ?: 'Chưa gắn đề tài' }}</p>
                                        </div>
                                        <button type="button" class="rounded-lg border bg-white px-3 py-1.5 text-xs font-bold text-slate-600" onclick="this.closest('[id^=sr-plan-detail-]').classList.add('hidden')">Thu gọn</button>
                                    </div>
                                    <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                                            <p class="text-[11px] font-bold uppercase text-slate-500">Đơn vị</p>
                                            <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $item->unit_name ?: $item->registration?->user?->unit?->name ?: 'Chưa có đơn vị' }}</p>
                                        </div>
                                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                                            <p class="text-[11px] font-bold uppercase text-slate-500">Năm học/KH</p>
                                            <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $item->school_year ?: $item->plan_year ?: 'Chưa có năm kế hoạch' }}</p>
                                        </div>
                                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                                            <p class="text-[11px] font-bold uppercase text-slate-500">Thời gian</p>
                                            <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $item->starts_on?->format('d/m/Y') ?: '—' }} - {{ $item->ends_on?->format('d/m/Y') ?: '—' }}</p>
                                        </div>
                                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                                            <p class="text-[11px] font-bold uppercase text-slate-500">Trạng thái</p>
                                            <p class="mt-0.5 text-sm font-semibold text-blue-700">{{ $planStatusLabels[$item->status] ?? $item->status }}</p>
                                        </div>
                                    </div>
                                    <div class="mt-3 rounded-lg border border-slate-100 bg-white p-3">
                                        <p class="text-[11px] font-bold uppercase text-slate-500">Mục tiêu</p>
                                        <p class="mt-1 text-sm text-slate-700">{{ $item->objectives ?: 'Chưa có mục tiêu' }}</p>
                                    </div>
                                    @if(($teamProgress ?? null) !== null)
                                        <div class="mt-3 rounded-lg border border-emerald-100 bg-emerald-50/50 p-3">
                                            <div class="mb-1 flex items-center justify-between text-xs font-semibold text-slate-600">
                                                <span>{{ $canManagePlan ? 'Tiến độ công việc của đề tài' : 'Tiến độ công việc của tôi' }}</span>
                                                <span>{{ $teamProgress }}%</span>
                                            </div>
                                            <div class="h-2 overflow-hidden rounded-full bg-white">
                                                <div class="h-full rounded-full bg-emerald-500" style="width: {{ $teamProgress }}%"></div>
                                            </div>
                                        </div>
                                    @endif
                                    @if($visiblePlanTasks->isNotEmpty())
                                        <div class="mt-3">
                                            <p class="mb-2 text-sm font-extrabold text-slate-900">{{ $canManagePlan ? 'Nhiệm vụ theo từng người' : 'Nhiệm vụ của tôi' }}</p>
                                            <div class="grid gap-3 md:grid-cols-2">
                                                @foreach($visiblePlanTasks as $task)
                                                    @php
                                                        $taskKey = (string) ($task['key'] ?? '');
                                                        $taskProgress = (int) ($task['progress_percent'] ?? 0);
                                                    @endphp
                                                    @php
                                                        $canUpdateTask = \App\Support\PermissionCheck::can(auth()->user(), 'scientific-research.plans.edit')
                                                            || (str_starts_with($taskKey, 'lead-') && (int) str_replace('lead-', '', $taskKey) === (int) auth()->id() && (int) $item->registration?->user_id === (int) auth()->id())
                                                            || (str_starts_with($taskKey, 'member-') && $item->registration?->members->contains(fn ($member) => (int) $member->id === (int) str_replace('member-', '', $taskKey) && (int) $member->user_id === (int) auth()->id()));
                                                    @endphp
                                                    <div class="rounded-lg border border-slate-200 bg-white p-3 text-sm">
                                                        <div class="flex flex-wrap items-start justify-between gap-2">
                                                            <div>
                                                                <p class="font-semibold text-slate-800">{{ $task['name'] ?? 'Chưa rõ người phụ trách' }}</p>
                                                                <p class="text-xs text-slate-500">{{ $task['role'] ?? 'Thành viên' }}</p>
                                                            </div>
                                                            <span class="rounded-full bg-blue-50 px-2 py-1 font-bold text-blue-700">{{ $planTaskStatusLabels[$task['status'] ?? 'ASSIGNED'] ?? 'Đã giao' }}</span>
                                                        </div>
                                                        <p class="mt-2 text-slate-700">{{ $task['task'] ?? 'Chưa nhập nội dung nhiệm vụ' }}</p>
                                                        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs">
                                                            <span class="text-slate-500">Tiến độ: {{ $taskProgress }}%</span>
                                                            @if(!empty($task['completed_on']))
                                                                <span class="text-slate-500">Hoàn thành: {{ \Illuminate\Support\Carbon::parse($task['completed_on'])->format('d/m/Y') }}</span>
                                                            @endif
                                                        </div>
                                                        <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-slate-100">
                                                            <div class="h-full rounded-full bg-blue-500" style="width: {{ $taskProgress }}%"></div>
                                                        </div>
                                                        @if(!empty($task['result_note']))
                                                            <div class="mt-2 rounded-lg bg-slate-50 px-3 py-2">
                                                                <p class="text-[11px] font-bold uppercase text-slate-500">Ghi chú/kết quả</p>
                                                                <p class="mt-0.5 text-xs text-slate-600">{{ $task['result_note'] }}</p>
                                                            </div>
                                                        @endif
                                                        @if($canUpdateTask)
                                                            <form method="POST" action="{{ route('scientific-research.plans.tasks.update', [$item, $taskKey]) }}" class="mt-2 rounded-lg border border-blue-100 bg-blue-50/50 p-3">
                                                                @csrf @method('PATCH')
                                                                <p class="mb-2 text-xs font-extrabold text-blue-700">Báo cáo tiến độ cho chủ nhiệm</p>
                                                                <div class="grid gap-2 sm:grid-cols-2">
                                                                    <label class="text-xs font-semibold text-slate-600">Trạng thái
                                                                        <select name="status" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
                                                                            @foreach($planTaskStatusLabels as $value => $label)
                                                                                <option value="{{ $value }}" @selected(($task['status'] ?? 'ASSIGNED') === $value)>{{ $label }}</option>
                                                                            @endforeach
                                                                        </select>
                                                                    </label>
                                                                    <label class="text-xs font-semibold text-slate-600">Tiến độ (%)
                                                                        <input name="progress_percent" type="number" min="0" max="100" value="{{ $taskProgress }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
                                                                    </label>
                                                                    <label class="text-xs font-semibold text-slate-600">Ngày hoàn thành
                                                                        <input name="completed_on" type="date" value="{{ $task['completed_on'] ?? '' }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
                                                                    </label>
                                                                    <label class="text-xs font-semibold text-slate-600">Ghi chú/kết quả
                                                                        <input name="result_note" value="{{ $task['result_note'] ?? '' }}" placeholder="Nội dung báo cáo gửi chủ nhiệm..." class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
                                                                    </label>
                                                                </div>
                                                                <button class="mt-2 rounded-lg bg-blue-600 px-3 py-2 text-xs font-bold text-white">Gửi báo cáo tiến độ</button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @elseif($canManagePlan && $item->assigned_tasks)
                                        <p class="mt-2 text-sm text-slate-600">{{ $item->assigned_tasks }}</p>
                                    @endif
                                </div>
                            </div>
                            @if(\App\Support\PermissionCheck::can(auth()->user(), 'scientific-research.plans.edit'))
                            <div id="sr-plan-setup-{{ $item->id }}" class="mt-4 hidden rounded-xl border border-amber-200 bg-amber-50/40 p-4 text-left">
                                <div>
                                    <div class="mb-4 flex items-start justify-between gap-3 border-b pb-3">
                                        <div>
                                            <p class="text-lg font-extrabold text-slate-900">Thiết lập kế hoạch</p>
                                            <p class="mt-1 text-sm text-slate-600">{{ $item->name }}</p>
                                        </div>
                                        <button type="button" class="rounded-lg border bg-white px-3 py-1.5 text-xs font-bold text-slate-600" onclick="this.closest('[id^=sr-plan-setup-]').classList.add('hidden')">Thu gọn</button>
                                    </div>
                        <form method="POST" action="{{ route('scientific-research.plans.update', $item) }}" class="grid gap-4">
                            @csrf @method('PATCH')
                            <div class="rounded-xl border bg-slate-50 p-3">
                                <p class="mb-3 text-sm font-extrabold text-slate-900">Thông tin kế hoạch</p>
                                <div class="grid gap-3 md:grid-cols-2">
                                    <label class="text-xs font-semibold text-slate-600 md:col-span-2">Đề tài đã đăng ký<select name="registration_id" required class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"><option value="">Chọn đề tài đã đăng ký</option>@foreach($allRegistrations as $registration)<option value="{{ $registration->id }}" @selected($item->registration_id === $registration->id)>{{ $registration->project_code ?: 'Chưa cấp mã' }} — {{ $registration->title }}</option>@endforeach</select></label>
                                    <label class="text-xs font-semibold text-slate-600">Năm học<input name="school_year" value="{{ $item->school_year }}" placeholder="VD: 2026-2027" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                                    <label class="text-xs font-semibold text-slate-600">Năm kế hoạch<input name="plan_year" type="number" min="2000" max="2200" value="{{ $item->plan_year }}" placeholder="VD: {{ now()->year }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                                    <label class="text-xs font-semibold text-slate-600">Trạng thái<select name="status" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"><option value="DRAFT" @selected($item->status === 'DRAFT')>Dự thảo</option><option value="APPROVED" @selected($item->status === 'APPROVED')>Đã duyệt</option><option value="IN_PROGRESS" @selected($item->status === 'IN_PROGRESS')>Đang thực hiện</option><option value="EVALUATED" @selected($item->status === 'EVALUATED')>Đã đánh giá</option><option value="CLOSED" @selected($item->status === 'CLOSED')>Đóng</option></select></label>
                                    <label class="text-xs font-semibold text-slate-600">Ngày bắt đầu<input name="starts_on" type="date" value="{{ $item->starts_on?->format('Y-m-d') }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                                    <label class="text-xs font-semibold text-slate-600">Ngày kết thúc<input name="ends_on" type="date" value="{{ $item->ends_on?->format('Y-m-d') }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                                    <label class="text-xs font-semibold text-slate-600 md:col-span-2">Mục tiêu<textarea name="objectives" rows="2" placeholder="Nhập mục tiêu kế hoạch" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">{{ $item->objectives }}</textarea></label>
                                </div>
                            </div>
                            <div class="rounded-xl border bg-white p-3">
                                <p class="mb-3 text-sm font-extrabold text-slate-900">Phân công nhiệm vụ</p>
                                <div class="grid gap-3 md:grid-cols-2">
                                @foreach($planAssigneesByRegistration[(string) $item->registration_id] ?? [] as $assignee)
                                    @php
                                        $task = $savedPlanTasks->get($assignee['key']);
                                    @endphp
                                    <div class="rounded-lg border bg-slate-50 p-3">
                                        <p class="text-sm font-semibold text-slate-800">{{ $assignee['name'] }}</p>
                                        <p class="text-xs text-slate-500">{{ $assignee['role'] }}{{ !empty($assignee['unit']) ? ' · '.$assignee['unit'] : '' }}</p>
                                        <input type="hidden" name="assignee_tasks[{{ $assignee['key'] }}][name]" value="{{ $assignee['name'] }}">
                                        <input type="hidden" name="assignee_tasks[{{ $assignee['key'] }}][role]" value="{{ $assignee['role'] }}">
                                        <input type="hidden" name="assignee_tasks[{{ $assignee['key'] }}][unit]" value="{{ $assignee['unit'] }}">
                                        <textarea name="assignee_tasks[{{ $assignee['key'] }}][task]" rows="2" placeholder="Nhiệm vụ giao cho {{ $assignee['name'] }}" class="mt-2 w-full rounded-lg border px-3 py-2 text-sm">{{ $task['task'] ?? '' }}</textarea>
                                        <div class="mt-2 grid gap-2 sm:grid-cols-2">
                                            <label class="text-xs font-semibold text-slate-600">Trạng thái
                                                <select name="assignee_tasks[{{ $assignee['key'] }}][status]" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
                                                    @foreach($planTaskStatusLabels as $value => $label)
                                                        <option value="{{ $value }}" @selected(($task['status'] ?? 'ASSIGNED') === $value)>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                            </label>
                                            <label class="text-xs font-semibold text-slate-600">Tiến độ (%)
                                                <input name="assignee_tasks[{{ $assignee['key'] }}][progress_percent]" type="number" min="0" max="100" value="{{ (int) ($task['progress_percent'] ?? 0) }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
                                            </label>
                                            <label class="text-xs font-semibold text-slate-600 sm:col-span-2">Ngày hoàn thành
                                                <input name="assignee_tasks[{{ $assignee['key'] }}][completed_on]" type="date" value="{{ $task['completed_on'] ?? '' }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
                                            </label>
                                        </div>
                                        <input name="assignee_tasks[{{ $assignee['key'] }}][result_note]" value="{{ $task['result_note'] ?? '' }}" placeholder="Ghi chú/kết quả thực hiện" class="mt-2 w-full rounded-lg border px-3 py-2 text-sm">
                                    </div>
                                @endforeach
                                </div>
                                <textarea name="assigned_tasks" rows="2" placeholder="Ghi chú nhiệm vụ chung nếu cần" class="mt-3 w-full rounded-lg border px-3 py-2 text-sm">{{ $savedPlanTasks->isEmpty() ? $item->assigned_tasks : '' }}</textarea>
                            </div>
                            <button class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-bold text-white">Lưu kế hoạch</button>
                        </form>
                        <form method="POST" action="{{ route('scientific-research.plans.destroy', $item) }}" onsubmit="return confirm('Xóa kế hoạch này?')" class="mt-2">@csrf @method('DELETE')<button class="text-sm font-bold text-rose-700">Xóa kế hoạch</button></form>
                                </div>
                            </div>
                            @endif
                </article>
            @empty
                <div class="rounded-xl border bg-white px-4 py-8 text-center text-slate-500">Chưa có kế hoạch.</div>
            @endforelse
        </div>
        <div class="mt-3">{{ $plans->links() }}</div>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const select = document.getElementById('sr-plan-registration');
                const box = document.getElementById('sr-plan-assignee-tasks');
                if (!select || !box) return;
                const assignees = @json($planAssigneesByRegistration);
                const escapeHtml = value => String(value || '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
                const render = () => {
                    const rows = assignees[String(select.value)] || [];
                    if (!rows.length) {
                        box.innerHTML = '<p class="text-sm text-slate-500 md:col-span-2">Chọn đề tài để hiện chủ nhiệm và thành viên thực hiện.</p>';
                        return;
                    }
                    box.innerHTML = rows.map(item => {
                        const key = escapeHtml(item.key);
                        const name = escapeHtml(item.name);
                        const role = escapeHtml(item.role || 'Thành viên');
                        const unit = escapeHtml(item.unit || '');
                        return `<div class="rounded-lg border bg-white p-3 text-sm font-semibold text-slate-700"><p>${name}<span class="ml-1 font-normal text-slate-500">· ${role}${unit ? ' · '+unit : ''}</span></p><input type="hidden" name="assignee_tasks[${key}][name]" value="${name}"><input type="hidden" name="assignee_tasks[${key}][role]" value="${role}"><input type="hidden" name="assignee_tasks[${key}][unit]" value="${unit}"><textarea name="assignee_tasks[${key}][task]" rows="2" placeholder="Nhiệm vụ giao cho ${name}" class="mt-2 w-full rounded-lg border px-3 py-2.5"></textarea><div class="mt-2 grid gap-2 sm:grid-cols-3"><label class="text-xs font-semibold text-slate-600">Trạng thái<select name="assignee_tasks[${key}][status]" class="mt-1 w-full rounded-lg border px-3 py-2"><option value="ASSIGNED">Đã giao</option><option value="IN_PROGRESS">Đang thực hiện</option><option value="COMPLETED">Hoàn thành</option><option value="BLOCKED">Đang vướng</option></select></label><label class="text-xs font-semibold text-slate-600">Tiến độ (%)<input name="assignee_tasks[${key}][progress_percent]" type="number" min="0" max="100" value="0" class="mt-1 w-full rounded-lg border px-3 py-2"></label><label class="text-xs font-semibold text-slate-600">Ngày hoàn thành<input name="assignee_tasks[${key}][completed_on]" type="date" class="mt-1 w-full rounded-lg border px-3 py-2"></label></div><input name="assignee_tasks[${key}][result_note]" placeholder="Ghi chú/kết quả thực hiện" class="mt-2 w-full rounded-lg border px-3 py-2"></div>`;
                    }).join('');
                };
                select.addEventListener('change', render);
                render();
            });
            document.addEventListener('DOMContentLoaded', () => {
                const search = document.getElementById('sr-plan-filter-search');
                const status = document.getElementById('sr-plan-filter-status');
                const progress = document.getElementById('sr-plan-filter-progress');
                const reset = document.getElementById('sr-plan-filter-reset');
                const rows = [...document.querySelectorAll('[data-plan-row]')];
                const matchProgress = (value, mode) => {
                    const number = Number(value || 0);
                    if (!mode) return true;
                    if (mode === 'not-started') return number <= 0;
                    if (mode === 'doing') return number > 0 && number < 100;
                    if (mode === 'done') return number >= 100;
                    return true;
                };
                const apply = () => {
                    const query = (search?.value || '').toLowerCase().trim();
                    const selectedStatus = status?.value || '';
                    const selectedProgress = progress?.value || '';
                    rows.forEach(row => {
                        const visible = (!query || row.dataset.search.includes(query))
                            && (!selectedStatus || row.dataset.status === selectedStatus)
                            && matchProgress(row.dataset.progress, selectedProgress);
                        row.classList.toggle('hidden', !visible);
                    });
                };
                [search, status, progress].forEach(field => field?.addEventListener('input', apply));
                [status, progress].forEach(field => field?.addEventListener('change', apply));
                reset?.addEventListener('click', () => {
                    if (search) search.value = '';
                    if (status) status.value = '';
                    if (progress) progress.value = '';
                    apply();
                });
                apply();
            });
        </script>
    @elseif($section === 'councils')
        <h2 class="mb-4 text-lg font-extrabold text-slate-900">Quản lý hội đồng khoa học</h2>
        <form method="POST" action="{{ route('scientific-research.councils.store') }}" class="mb-5 grid gap-3 md:grid-cols-4">@csrf
            <input name="name" required placeholder="Tên hội đồng" class="rounded-lg border px-3 py-2.5 md:col-span-2">
            <select name="registration_id" class="rounded-lg border px-3 py-2.5"><option value="">Chọn đề tài</option>@foreach($allRegistrations as $registration)<option value="{{ $registration->id }}">{{ $registration->project_code }} — {{ $registration->title }}</option>@endforeach</select>
            <select name="type" class="rounded-lg border px-3 py-2.5"><option value="APPRAISAL">Thẩm định</option><option value="ACCEPTANCE">Nghiệm thu</option><option value="ADVISORY">Tư vấn</option></select>
            <input name="meeting_at" type="datetime-local" class="rounded-lg border px-3 py-2.5"><input name="location" placeholder="Địa điểm" class="rounded-lg border px-3 py-2.5">
            <select name="status" class="rounded-lg border px-3 py-2.5"><option value="PLANNED">Dự kiến</option><option value="COMPLETED">Hoàn thành</option><option value="CANCELLED">Hủy</option></select>
            <input name="decision_number" placeholder="Số quyết định" class="rounded-lg border px-3 py-2.5">
            <textarea name="conclusion" rows="2" placeholder="Kết luận" class="rounded-lg border px-3 py-2.5 md:col-span-4"></textarea>
            <button class="rounded-lg bg-blue-600 px-4 py-2.5 font-bold text-white md:col-span-4">Lưu hội đồng</button>
        </form>
        @php
            $councilTypeLabels = [
                'APPRAISAL' => 'Thẩm định',
                'ACCEPTANCE' => 'Nghiệm thu',
                'ADVISORY' => 'Tư vấn',
            ];
            $councilStatusLabels = [
                'PLANNED' => 'Dự kiến',
                'COMPLETED' => 'Hoàn thành',
                'CANCELLED' => 'Hủy',
            ];
        @endphp
        <div class="grid gap-3 md:grid-cols-2">
            @forelse($councils as $item)
                <div class="rounded-xl border p-4">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-bold text-slate-900">{{ $item->name }}</p>
                            <p class="mt-1 text-sm text-slate-700">{{ $item->registration?->title ?: 'Chưa gắn đề tài' }}</p>
                        </div>
                        <button type="button" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700" onclick="document.getElementById('sr-council-setup-{{ $item->id }}').classList.toggle('hidden')">Thiết lập</button>
                    </div>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2">
                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                            <p class="text-[11px] font-bold uppercase text-slate-500">Loại hội đồng</p>
                            <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $councilTypeLabels[$item->type] ?? $item->type }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                            <p class="text-[11px] font-bold uppercase text-slate-500">Thời gian họp</p>
                            <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $item->meeting_at?->format('d/m/Y H:i') ?: 'Chưa có lịch' }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                            <p class="text-[11px] font-bold uppercase text-slate-500">Địa điểm</p>
                            <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $item->location ?: 'Chưa có địa điểm' }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                            <p class="text-[11px] font-bold uppercase text-slate-500">Trạng thái</p>
                            <p class="mt-0.5 text-sm font-semibold text-blue-700">{{ $councilStatusLabels[$item->status] ?? $item->status }}</p>
                        </div>
                    </div>
                    @if($item->decision_number || $item->conclusion)
                        <div class="mt-3 rounded-lg border border-slate-100 bg-white p-3 text-sm">
                            @if($item->decision_number)
                                <p><span class="font-semibold text-slate-700">Số quyết định:</span> {{ $item->decision_number }}</p>
                            @endif
                            @if($item->conclusion)
                                <p class="mt-1"><span class="font-semibold text-slate-700">Kết luận:</span> {{ $item->conclusion }}</p>
                            @endif
                        </div>
                    @endif
                    <div class="mt-3 rounded-lg bg-slate-50 p-3">
                        <p class="text-xs font-bold uppercase text-slate-500">Thành viên hội đồng</p>
                        <div class="mt-2 space-y-1">
                            @forelse($item->members as $member)
                                <div class="flex items-center justify-between gap-2 text-sm"><p><b>{{ $member->full_name }}</b> · {{ $member->role ?: 'Thành viên' }} @if($member->score !== null) · {{ $member->score }} điểm @endif</p><form method="POST" action="{{ route('scientific-research.councils.members.destroy', $member) }}" onsubmit="return confirm('Xóa thành viên hội đồng này?')">@csrf @method('DELETE')<button class="font-bold text-rose-700">Xóa</button></form></div>
                            @empty
                                <p class="text-sm text-slate-500">Chưa có thành viên.</p>
                            @endforelse
                        </div>
                        <form method="POST" action="{{ route('scientific-research.councils.members.store', $item) }}" class="mt-3 grid gap-2 md:grid-cols-2">
                            @csrf
                            <input name="full_name" required placeholder="Họ tên" class="rounded border px-2 py-2 text-sm">
                            <input name="role" placeholder="Vai trò" class="rounded border px-2 py-2 text-sm">
                            <input name="score" type="number" min="0" max="100" step="0.1" placeholder="Điểm" class="rounded border px-2 py-2 text-sm">
                            <input name="comment" placeholder="Nhận xét" class="rounded border px-2 py-2 text-sm">
                            <button class="rounded bg-slate-900 px-3 py-2 text-sm font-bold text-white md:col-span-2">Thêm thành viên</button>
                        </form>
                    </div>
                    <div id="sr-council-setup-{{ $item->id }}" class="mt-3 hidden rounded-lg border border-amber-200 bg-amber-50/40 p-3">
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <p class="text-sm font-extrabold text-slate-900">Thiết lập hội đồng</p>
                            <button type="button" class="rounded-lg border bg-white px-3 py-1.5 text-xs font-bold text-slate-600" onclick="this.closest('[id^=sr-council-setup-]').classList.add('hidden')">Thu gọn</button>
                        </div>
                        <form method="POST" action="{{ route('scientific-research.councils.update', $item) }}" class="grid gap-2 md:grid-cols-2">
                            @csrf @method('PATCH')
                            <label class="text-xs font-semibold text-slate-600 md:col-span-2">Tên hội đồng<input name="name" value="{{ $item->name }}" required class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                            <label class="text-xs font-semibold text-slate-600 md:col-span-2">Đề tài<select name="registration_id" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"><option value="">Chọn đề tài</option>@foreach($allRegistrations as $registration)<option value="{{ $registration->id }}" @selected($item->registration_id === $registration->id)>{{ $registration->project_code }} — {{ $registration->title }}</option>@endforeach</select></label>
                            <label class="text-xs font-semibold text-slate-600">Loại hội đồng<select name="type" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"><option value="APPRAISAL" @selected($item->type === 'APPRAISAL')>Thẩm định</option><option value="ACCEPTANCE" @selected($item->type === 'ACCEPTANCE')>Nghiệm thu</option><option value="ADVISORY" @selected($item->type === 'ADVISORY')>Tư vấn</option></select></label>
                            <label class="text-xs font-semibold text-slate-600">Thời gian họp<input name="meeting_at" type="datetime-local" value="{{ $item->meeting_at?->format('Y-m-d\TH:i') }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                            <label class="text-xs font-semibold text-slate-600">Địa điểm<input name="location" value="{{ $item->location }}" placeholder="Địa điểm" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                            <label class="text-xs font-semibold text-slate-600">Số quyết định<input name="decision_number" value="{{ $item->decision_number }}" placeholder="Số quyết định" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                            <label class="text-xs font-semibold text-slate-600">Trạng thái<select name="status" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"><option value="PLANNED" @selected($item->status === 'PLANNED')>Dự kiến</option><option value="COMPLETED" @selected($item->status === 'COMPLETED')>Hoàn thành</option><option value="CANCELLED" @selected($item->status === 'CANCELLED')>Hủy</option></select></label>
                            <label class="text-xs font-semibold text-slate-600 md:col-span-2">Kết luận<textarea name="conclusion" rows="2" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">{{ $item->conclusion }}</textarea></label>
                            <button class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-bold text-white md:col-span-2">Lưu hội đồng</button>
                        </form>
                        <form method="POST" action="{{ route('scientific-research.councils.destroy', $item) }}" onsubmit="return confirm('Xóa hội đồng này?')" class="mt-2">
                            @csrf @method('DELETE')
                            <button class="text-sm font-bold text-rose-700">Xóa hội đồng</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">Chưa có hội đồng.</p>
            @endforelse
        </div><div class="mt-3">{{ $councils->links() }}</div>
    @elseif($section === 'funding')
        @php
            $fundingTypeLabels = [
                'ESTIMATE' => 'Dự kiến',
                'ALLOCATED' => 'Được cấp',
                'SPENT' => 'Đã chi/phát sinh',
                'PAYMENT' => 'Đã thanh toán',
                'SETTLEMENT' => 'Chốt kinh phí',
            ];
            $fundingStatusLabels = [
                'PENDING' => 'Chờ xử lý',
                'APPROVED' => 'Đã duyệt',
                'PAID' => 'Đã thanh toán',
                'SETTLED' => 'Đã chốt kinh phí',
            ];
            $fundingRegistrationInfo = $allRegistrations->mapWithKeys(fn ($registration) => [
                (string) $registration->id => [
                    'code' => $registration->project_code ?: 'Chưa cấp mã',
                    'title' => $registration->title,
                    'lead' => $registration->user?->name ?: 'Chưa rõ chủ nhiệm',
                    'unit' => $registration->user?->unit?->name ?: 'Chưa có đơn vị',
                    'category' => $registration->researchCategory?->name ?: 'Chưa có lĩnh vực',
                    'academic_year' => $registration->academic_year ?: 'Chưa có năm học',
                    'time' => ($registration->start_date?->format('d/m/Y') ?: '—').' - '.($registration->end_date?->format('d/m/Y') ?: '—'),
                    'budget' => number_format((float) $registration->budget, 0, ',', '.'),
                    'members' => $registration->members->count() + 1,
                ],
            ]);
            $fundingTypeDescriptions = [
                'ESTIMATE' => 'Dự kiến số tiền cần dùng trước khi thực hiện đề tài.',
                'ALLOCATED' => 'Số tiền được cấp hoặc được giao cho đề tài.',
                'SPENT' => 'Khoản chi phí phát sinh thực tế trong quá trình thực hiện.',
                'PAYMENT' => 'Số tiền đã làm thủ tục thanh toán hoặc đã chi trả.',
                'SETTLEMENT' => 'Số tiền chốt cuối cùng khi nghiệm thu/quyết toán đề tài.',
            ];
            $fundingWorkflow = [
                'ESTIMATE' => [
                    'step' => '1',
                    'title' => 'Dự kiến',
                    'hint' => 'Lập nhu cầu kinh phí ban đầu theo thuyết minh đề tài.',
                    'items' => ['Nhập số tiền dự kiến hoặc từng hạng mục dự kiến', 'Ghi rõ căn cứ: vật tư, hội thảo, khảo sát, nghiệm thu', 'Đối chiếu với kinh phí đăng ký của đề tài'],
                    'output' => 'Bảng kinh phí dự kiến',
                    'status' => 'PENDING',
                    'name' => 'Kinh phí dự kiến của đề tài',
                    'note' => 'Căn cứ thuyết minh, dự kiến hạng mục chi...',
                ],
                'ALLOCATED' => [
                    'step' => '2',
                    'title' => 'Được cấp',
                    'hint' => 'Ghi nhận phần kinh phí được cấp cho đề tài.',
                    'items' => ['Nhập số tiền được cấp thực tế', 'Ghi quyết định/thông báo cấp kinh phí nếu có', 'So sánh với số tiền dự kiến đã lập'],
                    'output' => 'Khoản kinh phí được cấp cho đề tài',
                    'status' => 'APPROVED',
                    'name' => 'Kinh phí được cấp',
                    'note' => 'Theo quyết định/thông báo cấp kinh phí số...',
                ],
                'SPENT' => [
                    'step' => '3',
                    'title' => 'Theo dõi chi',
                    'hint' => 'Theo dõi từng khoản chi phát sinh trong quá trình thực hiện.',
                    'items' => ['Nhập từng khoản chi thực tế', 'Gắn ngày ghi nhận và nội dung chứng từ', 'Theo dõi còn lại so với kinh phí được cấp'],
                    'output' => 'Danh sách chi phí phát sinh',
                    'status' => 'PENDING',
                    'name' => 'Chi phí thực hiện đề tài',
                    'note' => 'Nội dung chi, số chứng từ, người nhận...',
                ],
                'PAYMENT' => [
                    'step' => '4',
                    'title' => 'Thanh toán',
                    'hint' => 'Ghi nhận khoản đã thanh toán hoặc đề nghị thanh toán.',
                    'items' => ['Nhập số tiền thanh toán', 'Ghi lần thanh toán, chứng từ, ngày thanh toán', 'Đối chiếu với khoản chi đã phát sinh'],
                    'output' => 'Khoản đã thanh toán',
                    'status' => 'PAID',
                    'name' => 'Thanh toán kinh phí đề tài',
                    'note' => 'Thanh toán lần..., chứng từ số...',
                ],
                'SETTLEMENT' => [
                    'step' => '5',
                    'title' => 'Chốt kinh phí',
                    'hint' => 'Chốt số liệu cuối cùng sau khi đề tài nghiệm thu/hoàn thành.',
                    'items' => ['Nhập số tiền chốt cuối cùng được chấp nhận', 'Ghi phần còn dư/thiếu nếu có', 'Xác nhận trạng thái đã chốt kinh phí'],
                    'output' => 'Biên bản/bảng chốt kinh phí đề tài',
                    'status' => 'SETTLED',
                    'name' => 'Chốt kinh phí đề tài',
                    'note' => 'Số chốt kinh phí, phần còn lại, kết luận...',
                ],
            ];
        @endphp
        <div class="sr-section-head">
            <div>
                <h2>Theo dõi kinh phí theo đề tài</h2>
                <p>Theo dõi kinh phí theo từng đề tài: dự kiến, được cấp, đã chi/phát sinh, đã thanh toán và chốt kinh phí.</p>
            </div>
            <form method="GET" action="{{ route('scientific-research.funding.index') }}" class="flex flex-wrap items-end gap-2">
                <label class="text-xs font-bold uppercase text-slate-500">Năm kinh phí
                    <input name="funding_year" type="number" min="2000" max="2100" step="1" list="sr-funding-year-options" value="{{ $selectedFundingYear }}" placeholder="Tất cả năm" class="mt-1 w-36 rounded-lg border px-3 py-2 text-sm font-semibold text-slate-700" oninput="clearTimeout(this._filterTimer); this._filterTimer = setTimeout(() => { if (this.value === '' || /^\d{4}$/.test(this.value)) this.form.submit(); }, 600)">
                    <datalist id="sr-funding-year-options">
                        @foreach($fundingYearOptions as $year)
                            <option value="{{ $year }}">Năm {{ $year }}</option>
                        @endforeach
                    </datalist>
                </label>
                @if($selectedFundingYear !== '')
                    <a href="{{ route('scientific-research.funding.index') }}" class="rounded-lg border bg-white px-3 py-2 text-sm font-bold text-slate-600">Bỏ lọc</a>
                @endif
            </form>
        </div>
        <div class="mb-5 rounded-xl border border-blue-100 bg-blue-50 p-4">
            <p class="text-xs font-bold uppercase text-blue-700">{{ $selectedFundingYear !== '' ? 'Tổng chi phí năm '.$selectedFundingYear : 'Tổng chi phí' }}</p>
            <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ number_format((float) $fundingSettlementTotal, 0, ',', '.') }}</p>
        </div>
        <div class="mb-5 rounded-xl border bg-white p-4">
            <button type="button" class="rounded-lg bg-blue-600 px-4 py-2.5 font-bold text-white" onclick="document.getElementById('sr-funding-global-create').classList.toggle('hidden')"><i class="bi bi-plus-lg"></i> Thêm khoản cho đề tài khác</button>
            <div id="sr-funding-global-create" class="mt-4 hidden">
        <form method="POST" action="{{ route('scientific-research.funding.store') }}" class="grid gap-3 md:grid-cols-4">@csrf
            <label class="text-sm font-semibold text-slate-700 md:col-span-2">Đề tài <span class="text-rose-500">*</span>
                <select name="registration_id" required id="sr-funding-registration" class="mt-1 w-full rounded-lg border px-3 py-2.5">
                    <option value="">Chọn đề tài cần theo dõi kinh phí</option>
                    @foreach($allRegistrations as $registration)
                        <option value="{{ $registration->id }}">{{ $registration->project_code ?: 'Chưa cấp mã' }} — {{ $registration->title }}</option>
                    @endforeach
                </select>
            </label>
            <div id="sr-funding-registration-info" class="hidden rounded-lg border border-blue-100 bg-blue-50/50 p-3 text-sm md:col-span-4"></div>
            <label class="text-sm font-semibold text-slate-700">Loại khoản
                <select name="type" id="sr-funding-type" class="mt-1 w-full rounded-lg border px-3 py-2.5"><option value="ESTIMATE">Dự kiến</option><option value="ALLOCATED">Được cấp</option><option value="SPENT">Đã chi/phát sinh</option><option value="PAYMENT">Đã thanh toán</option><option value="SETTLEMENT">Chốt kinh phí</option></select>
            </label>
            <label class="text-sm font-semibold text-slate-700">Số tiền
                <input name="amount" type="number" min="0" step="1000" required placeholder="Nhập số tiền" class="mt-1 w-full rounded-lg border px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold text-slate-700">Tên khoản
                <input name="item_name" id="sr-funding-item-name" required placeholder="VD: Kinh phí mua vật tư, hội thảo..." class="mt-1 w-full rounded-lg border px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold text-slate-700">Ngày ghi nhận
                <input name="spent_on" type="date" class="mt-1 w-full rounded-lg border px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold text-slate-700">Trạng thái
                <select name="status" id="sr-funding-status" class="mt-1 w-full rounded-lg border px-3 py-2.5"><option value="PENDING">Chờ xử lý</option><option value="APPROVED">Đã duyệt</option><option value="PAID">Đã thanh toán</option><option value="SETTLED">Đã chốt kinh phí</option></select>
            </label>
            <label class="text-sm font-semibold text-slate-700 md:col-span-2">Ghi chú
                <input name="note" id="sr-funding-note" placeholder="Nội dung chi, chứng từ, lần thanh toán..." class="mt-1 w-full rounded-lg border px-3 py-2.5">
            </label>
            <button class="rounded-lg bg-blue-600 px-4 py-2.5 font-bold text-white md:col-span-4"><i class="bi bi-cash-coin"></i> Ghi nhận khoản theo đề tài</button>
        </form>
            </div>
        </div>
        <div class="mb-5 rounded-xl border bg-white p-4">
            <h3 class="font-extrabold text-slate-900">Bảng kinh phí theo đề tài</h3>
            <p class="mt-1 text-sm text-slate-500">Mỗi đề tài hiển thị một dòng; mở chi tiết để xem, thêm, sửa hoặc xóa các khoản chi phát sinh của đề tài đó.</p>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full min-w-[860px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                        <tr><th class="px-3 py-2">Ngày gần nhất</th><th class="px-3 py-2">Đề tài</th><th class="px-3 py-2">Chủ nhiệm</th><th class="px-3 py-2">Kinh phí thực tế / được cấp</th><th class="px-3 py-2">Thao tác</th></tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($fundingProjects as $project)
                            @php
                                $registration = $project->registration;
                                 $projectFundings = $fundingDetailsByRegistration->get($project->registration_id, collect())->filter(fn ($row) => in_array($row->type, ['SPENT', 'PAYMENT'], true))->values();
                                $projectTypeTotals = $project->type_totals;
                                $detailId = 'sr-funding-project-'.$project->registration_id;
                            @endphp
                            <tr>
                                <td class="px-3 py-2 whitespace-nowrap">{{ $project->actual_on ? \Illuminate\Support\Carbon::parse($project->actual_on)->format('d/m/Y') : '—' }}</td>
                                <td class="px-3 py-2"><p class="font-bold text-slate-900">{{ $registration?->project_code ?: 'Chưa cấp mã' }}</p><p class="text-xs text-slate-500">{{ $registration?->title ?: 'Chưa gắn đề tài' }}</p></td>
                                <td class="px-3 py-2"><p class="font-semibold text-slate-800">{{ $registration?->user?->name ?: '—' }}</p><p class="text-xs text-slate-500">{{ $registration?->user?->unit?->name ?: 'Chưa có đơn vị' }}</p></td>
                                                                <td class="px-3 py-2">
                                    <div class="min-w-48">
                                        <div class="flex items-center justify-between gap-3 text-xs font-bold text-slate-600"><span>{{ $project->actual_source }}</span><span>{{ $project->usage_percent === null ? 'Chưa cấp' : $project->usage_percent.'%' }}</span></div>
                                        <div class="mt-1 h-2 overflow-hidden rounded-full bg-slate-100">
                                            <div class="h-full {{ $project->is_over_budget ? 'bg-rose-500' : 'bg-emerald-500' }}" style="width: {{ min(100, (float) ($project->usage_percent ?? 0)) }}%"></div>
                                        </div>
                                        <p class="mt-1 text-xs text-slate-600">{{ number_format((float) $project->actual_amount, 0, ',', '.') }} / {{ number_format((float) $project->allocated_amount, 0, ',', '.') }}</p>
                                        @if($project->is_over_budget)
                                            <p class="mt-1 text-xs font-bold text-rose-700">Kinh phí thực tế vượt số được cấp</p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-3 py-2">
                                    <div class="flex flex-wrap gap-2">
                                        <button type="button" class="rounded-lg border border-blue-200 bg-blue-50 px-3 py-1.5 text-xs font-bold text-blue-700" onclick="document.getElementById('{{ $detailId }}').classList.toggle('hidden')">Xem chi tiết</button>

                                    </div>
                                </td>
                            </tr>
                            <tr id="{{ $detailId }}" class="hidden bg-blue-50/40">
                                <td colspan="5" class="px-3 py-3">
                                    <div class="grid gap-3">
                                        @if($project->is_over_budget)
                                            <div class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm font-bold text-rose-700">Cảnh báo: kinh phí thực tế vượt số tiền được cấp {{ number_format((float) ($project->actual_amount - $project->allocated_amount), 0, ',', '.') }}.</div>
                                        @elseif($project->allocated_amount > 0)
                                            <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700">Kinh phí thực tế bằng {{ $project->usage_percent }}% kinh phí được cấp.</div>
                                        @endif
                                        <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-5">
                                            @foreach($fundingTypeLabels as $type => $label)
                                                <div class="rounded-lg bg-white px-3 py-2">
                                                    <p class="text-[11px] font-bold uppercase text-slate-500">{{ $label }}</p>
                                                    <p class="mt-1 font-extrabold text-slate-900">{{ number_format((float) ($projectTypeTotals[$type] ?? 0), 0, ',', '.') }}</p>
                                                </div>
                                            @endforeach
                                        </div>
                                        @if($registration)
                                            <div class="rounded-lg border border-emerald-200 bg-emerald-50/60 p-3">
                                                <p class="text-sm font-extrabold text-slate-900">Cập nhật kinh phí của đề tài</p>
                                                <div class="mt-2 grid gap-2 md:grid-cols-5">
                                                    @foreach($fundingTypeLabels as $type => $label)
                                                        <div class="rounded-lg bg-white px-3 py-2">
                                                            <p class="text-xs font-extrabold text-slate-900">{{ $label }}</p>
                                                            <p class="mt-1 text-[11px] leading-4 text-slate-500">{{ $fundingTypeDescriptions[$type] ?? '' }}</p>
                                                        </div>
                                                    @endforeach
                                                </div>
                                                <form method="POST" action="{{ route('scientific-research.funding.store') }}" class="mt-3 grid gap-2 md:grid-cols-5">
                                                    @csrf
                                                    <input type="hidden" name="registration_id" value="{{ $project->registration_id }}">
                                                    <label class="text-xs font-semibold text-slate-600">Loại cập nhật
                                                        <select name="type" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">
                                                            @foreach($fundingTypeLabels as $type => $label)
                                                                <option value="{{ $type }}" @selected($type === 'SPENT')>{{ $label }}</option>
                                                            @endforeach
                                                        </select>
                                                    </label>
                                                    <label class="text-xs font-semibold text-slate-600 md:col-span-2">Tên khoản/nội dung<input name="item_name" required placeholder="VD: Mua vật tư, kinh phí được cấp, quyết toán..." class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                                                    <label class="text-xs font-semibold text-slate-600">Số tiền<input name="amount" type="number" min="0" step="1000" required class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                                                    <label class="text-xs font-semibold text-slate-600">Ngày ghi nhận<input name="spent_on" type="date" value="{{ now()->format('Y-m-d') }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                                                    <label class="text-xs font-semibold text-slate-600">Trạng thái
                                                        <select name="status" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"><option value="PENDING">Chờ xử lý</option><option value="APPROVED">Đã duyệt</option><option value="PAID">Đã thanh toán</option><option value="SETTLED">Đã chốt kinh phí</option></select>
                                                    </label>
                                                    <label class="text-xs font-semibold text-slate-600 md:col-span-4">Ghi chú<input name="note" placeholder="Chứng từ, quyết định phân bổ, nội dung chi..." class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                                                    <button class="rounded-lg bg-emerald-600 px-3 py-2 text-sm font-bold text-white md:col-span-5">Lưu cập nhật kinh phí</button>
                                                </form>
                                            </div>
                                        @endif                                        <div class="overflow-x-auto rounded-lg border border-blue-100 bg-white">
                                            <table class="w-full min-w-[900px] text-sm">
                                                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                                    <tr><th class="px-3 py-2">Ngày</th><th class="px-3 py-2">Khoản</th><th class="px-3 py-2">Loại</th><th class="px-3 py-2 text-right">Số tiền</th><th class="px-3 py-2">Ghi chú</th><th class="px-3 py-2">Thao tác</th></tr>
                                                </thead>
                                                <tbody class="divide-y divide-slate-100">
                                                    @forelse($projectFundings as $projectFunding)
                                                        <tr>
                                                            <td class="whitespace-nowrap px-3 py-2">{{ $projectFunding->spent_on?->format('d/m/Y') ?: $projectFunding->created_at?->format('d/m/Y') }}</td>
                                                            <td class="px-3 py-2 font-semibold text-slate-900">{{ $projectFunding->item_name }}</td>
                                                            <td class="px-3 py-2">{{ $fundingTypeLabels[$projectFunding->type] ?? $projectFunding->type }}</td>
                                                            <td class="px-3 py-2 text-right font-extrabold text-slate-900">{{ number_format((float) $projectFunding->amount, 0, ',', '.') }}</td>
                                                            <td class="px-3 py-2 text-slate-600">{{ $projectFunding->note ?: '—' }}</td>
                                                            <td class="px-3 py-2">
                                                                <div class="flex flex-wrap gap-2">
                                                                    <button type="button" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700" onclick="document.getElementById('sr-funding-edit-{{ $projectFunding->id }}').classList.toggle('hidden')">Sửa</button>
                                                                    <form method="POST" action="{{ route('scientific-research.funding.destroy', $projectFunding) }}" onsubmit="return confirm('Xóa khoản kinh phí này?')">@csrf @method('DELETE')<button class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700">Xóa</button></form>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                        <tr id="sr-funding-edit-{{ $projectFunding->id }}" class="hidden bg-amber-50/40">
                                                            <td colspan="6" class="px-3 py-3">
                                                                <form method="POST" action="{{ route('scientific-research.funding.update', $projectFunding) }}" class="grid gap-2 md:grid-cols-4">
                                                                    @csrf @method('PATCH')
                                                                    <input type="hidden" name="registration_id" value="{{ $projectFunding->registration_id }}">
                                                                    <label class="text-xs font-semibold text-slate-600">Loại khoản<select name="type" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"><option value="ESTIMATE" @selected($projectFunding->type === 'ESTIMATE')>Dự kiến</option><option value="ALLOCATED" @selected($projectFunding->type === 'ALLOCATED')>Được cấp</option><option value="SPENT" @selected($projectFunding->type === 'SPENT')>Đã chi/phát sinh</option><option value="PAYMENT" @selected($projectFunding->type === 'PAYMENT')>Đã thanh toán</option><option value="SETTLEMENT" @selected($projectFunding->type === 'SETTLEMENT')>Chốt kinh phí</option></select></label>
                                                                    <label class="text-xs font-semibold text-slate-600">Trạng thái<select name="status" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"><option value="PENDING" @selected($projectFunding->status === 'PENDING')>Chờ xử lý</option><option value="APPROVED" @selected($projectFunding->status === 'APPROVED')>Đã duyệt</option><option value="PAID" @selected($projectFunding->status === 'PAID')>Đã thanh toán</option><option value="SETTLED" @selected($projectFunding->status === 'SETTLED')>Đã chốt kinh phí</option></select></label>
                                                                    <label class="text-xs font-semibold text-slate-600 md:col-span-2">Tên khoản<input name="item_name" value="{{ $projectFunding->item_name }}" required class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                                                                    <label class="text-xs font-semibold text-slate-600">Số tiền<input name="amount" type="number" min="0" step="1000" value="{{ (float) $projectFunding->amount }}" required class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                                                                    <label class="text-xs font-semibold text-slate-600">Ngày ghi nhận<input name="spent_on" type="date" value="{{ $projectFunding->spent_on?->format('Y-m-d') }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                                                                    <label class="text-xs font-semibold text-slate-600 md:col-span-2">Ghi chú<input name="note" value="{{ $projectFunding->note }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                                                                    <button class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-bold text-white md:col-span-4">Lưu thay đổi</button>
                                                                </form>
                                                            </td>
                                                        </tr>
                                                    @empty
                                                        <tr><td colspan="6" class="px-3 py-6 text-center text-slate-500">Chưa có khoản đã chi/phát sinh hoặc đã thanh toán của đề tài.</td></tr>
                                                    @endforelse
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-3 py-8 text-center text-slate-500">Chưa có đề tài nào có kinh phí trong phạm vi đang xem.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const select = document.getElementById('sr-funding-registration');
                const box = document.getElementById('sr-funding-registration-info');
                const typeSelect = document.getElementById('sr-funding-type');
                const statusSelect = document.getElementById('sr-funding-status');
                const itemName = document.getElementById('sr-funding-item-name');
                const noteInput = document.getElementById('sr-funding-note');
                if (!select || !box) return;
                const registrations = @json($fundingRegistrationInfo);
                const typeHints = @json(collect($fundingWorkflow)->map(fn ($step) => ['status' => $step['status'], 'name' => $step['name'], 'note' => $step['note']]));
                const escapeHtml = value => String(value || '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
                const applyTypeHint = () => {
                    const step = typeHints[typeSelect?.value] || typeHints.ESTIMATE;
                    if (!step) return;
                    if (statusSelect && step.status) statusSelect.value = step.status;
                    if (itemName && !itemName.value) itemName.placeholder = step.name || '';
                    if (noteInput) noteInput.placeholder = step.note || '';
                };
                const render = () => {
                    const item = registrations[String(select.value)];
                    if (!item) {
                        box.classList.add('hidden');
                        box.innerHTML = '';
                        return;
                    }
                    box.classList.remove('hidden');
                    box.innerHTML = `
                        <p class="text-xs font-bold uppercase text-blue-700">Thông tin lấy từ đăng ký đề tài</p>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                            <p><span class="font-semibold text-slate-700">Mã đề tài:</span> ${escapeHtml(item.code)}</p>
                            <p><span class="font-semibold text-slate-700">Chủ nhiệm:</span> ${escapeHtml(item.lead)}</p>
                            <p><span class="font-semibold text-slate-700">Đơn vị:</span> ${escapeHtml(item.unit)}</p>
                            <p><span class="font-semibold text-slate-700">Năm học:</span> ${escapeHtml(item.academic_year)}</p>
                            <p><span class="font-semibold text-slate-700">Lĩnh vực:</span> ${escapeHtml(item.category)}</p>
                            <p><span class="font-semibold text-slate-700">Thời gian:</span> ${escapeHtml(item.time)}</p>
                            <p><span class="font-semibold text-slate-700">Kinh phí đăng ký:</span> ${escapeHtml(item.budget)}</p>
                            <p><span class="font-semibold text-slate-700">Số người tham gia:</span> ${escapeHtml(item.members)}</p>
                        </div>`;
                };
                select.addEventListener('change', render);
                typeSelect?.addEventListener('change', applyTypeHint);
                render();
                applyTypeHint();
            });
        </script>
    @elseif($section === 'products')
        @php
            $productTypeLabels = [
                'ARTICLE' => 'Bài báo',
                'TEXTBOOK' => 'Giáo trình/tài liệu',
                'INITIATIVE' => 'Sáng kiến',
                'MODEL' => 'Mô hình/sản phẩm',
                'WORK' => 'Công trình',
                'OTHER' => 'Khác',
            ];
            $productStatusLabels = [
                'RECORDED' => 'Ghi nhận',
                'PUBLISHED' => 'Đã công bố',
                'ACCEPTED' => 'Đã nghiệm thu',
            ];
            $productRegistrationInfo = $allRegistrations->mapWithKeys(fn ($registration) => [
                (string) $registration->id => [
                    'code' => $registration->project_code ?: 'Chưa cấp mã',
                    'title' => $registration->title,
                    'lead' => $registration->user?->name ?: 'Chưa rõ chủ nhiệm',
                    'unit' => $registration->user?->unit?->name ?: 'Chưa có đơn vị',
                    'category' => $registration->researchCategory?->name ?: 'Chưa có lĩnh vực',
                    'academic_year' => $registration->academic_year ?: 'Chưa có năm học',
                    'time' => ($registration->start_date?->format('d/m/Y') ?: '—').' - '.($registration->end_date?->format('d/m/Y') ?: '—'),
                    'members' => $registration->members->count() + 1,
                ],
            ]);
        @endphp
        <div class="sr-section-head">
            <div>
                <h2>Quản lý sản phẩm khoa học</h2>
                <p>Ghi nhận đầu ra theo từng đề tài đã đăng ký: bài báo, giáo trình/tài liệu, sáng kiến, mô hình, công trình và minh chứng nghiệm thu.</p>
            </div>
            <div class="sr-section-kpis">
                <span><b>{{ number_format($stats['products']) }}</b> sản phẩm</span>
                <span><b>{{ $productTypeSummary->sum() }}</b> đã phân loại</span>
            </div>
        </div>
        <div class="sr-workflow mb-5">
            <span>1. Gắn đề tài</span>
            <span>2. Phân loại sản phẩm</span>
            <span>3. Ghi tác giả/công bố</span>
            <span>4. Phục vụ báo cáo</span>
        </div>
        <form method="POST" action="{{ route('scientific-research.products.store') }}" class="mb-5 grid gap-3 md:grid-cols-4">@csrf
            <label class="text-sm font-semibold text-slate-700 md:col-span-2">Đề tài <span class="text-rose-500">*</span>
                <select name="registration_id" required id="sr-product-registration" class="mt-1 w-full rounded-lg border px-3 py-2.5">
                    <option value="">Chọn đề tài có sản phẩm đầu ra</option>
                    @foreach($allRegistrations as $registration)
                        <option value="{{ $registration->id }}">{{ $registration->project_code ?: 'Chưa cấp mã' }} — {{ $registration->title }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-slate-700">Loại sản phẩm
                <select name="type" class="mt-1 w-full rounded-lg border px-3 py-2.5"><option value="ARTICLE">Bài báo</option><option value="TEXTBOOK">Giáo trình/tài liệu</option><option value="INITIATIVE">Sáng kiến</option><option value="MODEL">Mô hình/sản phẩm</option><option value="WORK">Công trình</option><option value="OTHER">Khác</option></select>
            </label>
            <label class="text-sm font-semibold text-slate-700">Trạng thái
                <select name="status" class="mt-1 w-full rounded-lg border px-3 py-2.5"><option value="RECORDED">Ghi nhận</option><option value="PUBLISHED">Đã công bố</option><option value="ACCEPTED">Đã nghiệm thu</option></select>
            </label>
            <div id="sr-product-registration-info" class="hidden rounded-lg border border-blue-100 bg-blue-50/50 p-3 text-sm md:col-span-4"></div>
            <label class="text-sm font-semibold text-slate-700 md:col-span-2">Tên sản phẩm đầu ra <span class="text-rose-500">*</span>
                <input name="title" required placeholder="VD: Tên bài báo, tên báo cáo nghiệm thu, tên mô hình, tên sáng kiến..." class="mt-1 w-full rounded-lg border px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold text-slate-700">Tác giả/nhóm tác giả
                <input name="authors" placeholder="VD: Nguyễn Văn A, Trần Thị B" class="mt-1 w-full rounded-lg border px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold text-slate-700">Nơi công bố/nghiệm thu
                <input name="publisher" placeholder="Tên tạp chí, hội thảo, đơn vị nghiệm thu..." class="mt-1 w-full rounded-lg border px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold text-slate-700">Năm công bố/nghiệm thu
                <input name="published_year" type="number" min="1900" max="2200" placeholder="VD: {{ now()->year }}" class="mt-1 w-full rounded-lg border px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold text-slate-700 md:col-span-3">Mô tả/minh chứng
                <textarea name="description" rows="2" placeholder="Ghi mã DOI, số quyết định nghiệm thu, đường dẫn minh chứng hoặc mô tả ngắn..." class="mt-1 w-full rounded-lg border px-3 py-2.5"></textarea>
            </label>
            <button class="rounded-lg bg-blue-600 px-4 py-2.5 font-bold text-white md:col-span-4"><i class="bi bi-journal-check"></i> Ghi nhận sản phẩm đầu ra</button>
        </form>
        <div class="grid gap-3">
            @forelse($products as $item)
                <article class="rounded-xl border bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-extrabold text-slate-900">{{ $item->title }}</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700">{{ $item->registration?->title ?: 'Chưa gắn đề tài' }}</p>
                        </div>
                        <button type="button" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700" onclick="document.getElementById('sr-product-setup-{{ $item->id }}').classList.toggle('hidden')">Thiết lập</button>
                    </div>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                            <p class="text-[11px] font-bold uppercase text-slate-500">Mã đề tài</p>
                            <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $item->registration?->project_code ?: 'Chưa cấp mã' }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                            <p class="text-[11px] font-bold uppercase text-slate-500">Loại sản phẩm</p>
                            <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $productTypeLabels[$item->type] ?? $item->type }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                            <p class="text-[11px] font-bold uppercase text-slate-500">Năm</p>
                            <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $item->published_year ?: 'Chưa có năm' }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                            <p class="text-[11px] font-bold uppercase text-slate-500">Trạng thái</p>
                            <p class="mt-0.5 text-sm font-semibold text-blue-700">{{ $productStatusLabels[$item->status] ?? $item->status }}</p>
                        </div>
                    </div>
                    @if($item->registration)
                        <div class="mt-3 rounded-lg border border-blue-100 bg-blue-50/50 p-3">
                            <p class="text-xs font-bold uppercase text-blue-700">Thông tin lấy từ đăng ký đề tài</p>
                            <div class="mt-2 grid gap-2 text-sm sm:grid-cols-2 lg:grid-cols-4">
                                <p><span class="font-semibold text-slate-700">Chủ nhiệm:</span> {{ $item->registration->user?->name ?: 'Chưa rõ' }}</p>
                                <p><span class="font-semibold text-slate-700">Đơn vị:</span> {{ $item->registration->user?->unit?->name ?: 'Chưa có đơn vị' }}</p>
                                <p><span class="font-semibold text-slate-700">Năm học:</span> {{ $item->registration->academic_year ?: 'Chưa có năm học' }}</p>
                                <p><span class="font-semibold text-slate-700">Lĩnh vực:</span> {{ $item->registration->researchCategory?->name ?: 'Chưa có lĩnh vực' }}</p>
                            </div>
                        </div>
                    @endif
                    <div class="mt-3 rounded-lg border border-slate-100 bg-white p-3 text-sm text-slate-700">
                        <p><span class="font-semibold">Tác giả/nhóm tác giả:</span> {{ $item->authors ?: 'Chưa có tác giả' }}</p>
                        @if($item->publisher)
                            <p class="mt-1"><span class="font-semibold">Nơi công bố/nghiệm thu:</span> {{ $item->publisher }}</p>
                        @endif
                        @if($item->description)
                            <p class="mt-1"><span class="font-semibold">Mô tả/minh chứng:</span> {{ $item->description }}</p>
                        @endif
                    </div>
                    <div id="sr-product-setup-{{ $item->id }}" class="mt-3 hidden rounded-lg border border-amber-200 bg-amber-50/40 p-3">
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <p class="text-sm font-extrabold text-slate-900">Thiết lập sản phẩm khoa học</p>
                            <button type="button" class="rounded-lg border bg-white px-3 py-1.5 text-xs font-bold text-slate-600" onclick="this.closest('[id^=sr-product-setup-]').classList.add('hidden')">Thu gọn</button>
                        </div>
                        <form method="POST" action="{{ route('scientific-research.products.update', $item) }}" class="grid gap-2 md:grid-cols-2">
                            @csrf @method('PATCH')
                            <label class="text-xs font-semibold text-slate-600 md:col-span-2">Đề tài<select name="registration_id" required class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"><option value="">Chọn đề tài</option>@foreach($allRegistrations as $registration)<option value="{{ $registration->id }}" @selected($item->registration_id === $registration->id)>{{ $registration->project_code ?: 'Chưa cấp mã' }} — {{ $registration->title }}</option>@endforeach</select></label>
                            <label class="text-xs font-semibold text-slate-600 md:col-span-2">Tên sản phẩm đầu ra<input name="title" value="{{ $item->title }}" required class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                            <label class="text-xs font-semibold text-slate-600">Loại sản phẩm<select name="type" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"><option value="ARTICLE" @selected($item->type === 'ARTICLE')>Bài báo</option><option value="TEXTBOOK" @selected($item->type === 'TEXTBOOK')>Giáo trình/tài liệu</option><option value="INITIATIVE" @selected($item->type === 'INITIATIVE')>Sáng kiến</option><option value="MODEL" @selected($item->type === 'MODEL')>Mô hình/sản phẩm</option><option value="WORK" @selected($item->type === 'WORK')>Công trình</option><option value="OTHER" @selected($item->type === 'OTHER')>Khác</option></select></label>
                            <label class="text-xs font-semibold text-slate-600">Trạng thái<select name="status" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"><option value="RECORDED" @selected($item->status === 'RECORDED')>Ghi nhận</option><option value="PUBLISHED" @selected($item->status === 'PUBLISHED')>Đã công bố</option><option value="ACCEPTED" @selected($item->status === 'ACCEPTED')>Đã nghiệm thu</option></select></label>
                            <label class="text-xs font-semibold text-slate-600">Tác giả/nhóm tác giả<input name="authors" value="{{ $item->authors }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                            <label class="text-xs font-semibold text-slate-600">Nơi công bố/nghiệm thu<input name="publisher" value="{{ $item->publisher }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                            <label class="text-xs font-semibold text-slate-600">Năm công bố/nghiệm thu<input name="published_year" type="number" min="1900" max="2200" value="{{ $item->published_year }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                            <label class="text-xs font-semibold text-slate-600 md:col-span-2">Mô tả/minh chứng<textarea name="description" rows="2" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">{{ $item->description }}</textarea></label>
                            <button class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-bold text-white md:col-span-2">Lưu sản phẩm</button>
                        </form>
                        <form method="POST" action="{{ route('scientific-research.products.destroy', $item) }}" onsubmit="return confirm('Xóa sản phẩm này?')" class="mt-2">@csrf @method('DELETE')<button class="text-sm font-bold text-rose-700">Xóa sản phẩm</button></form>
                    </div>
                </article>
            @empty
                <div class="rounded-xl border bg-white px-4 py-8 text-center text-slate-500">Chưa có sản phẩm khoa học theo đề tài.</div>
            @endforelse
        </div>
        <div class="mt-3">{{ $products->links() }}</div>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const select = document.getElementById('sr-product-registration');
                const box = document.getElementById('sr-product-registration-info');
                if (!select || !box) return;
                const registrations = @json($productRegistrationInfo);
                const escapeHtml = value => String(value || '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
                const render = () => {
                    const item = registrations[String(select.value)];
                    if (!item) {
                        box.classList.add('hidden');
                        box.innerHTML = '';
                        return;
                    }
                    box.classList.remove('hidden');
                    box.innerHTML = `
                        <p class="text-xs font-bold uppercase text-blue-700">Thông tin lấy từ đăng ký đề tài</p>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                            <p><span class="font-semibold text-slate-700">Mã đề tài:</span> ${escapeHtml(item.code)}</p>
                            <p><span class="font-semibold text-slate-700">Chủ nhiệm:</span> ${escapeHtml(item.lead)}</p>
                            <p><span class="font-semibold text-slate-700">Đơn vị:</span> ${escapeHtml(item.unit)}</p>
                            <p><span class="font-semibold text-slate-700">Năm học:</span> ${escapeHtml(item.academic_year)}</p>
                            <p><span class="font-semibold text-slate-700">Lĩnh vực:</span> ${escapeHtml(item.category)}</p>
                            <p><span class="font-semibold text-slate-700">Thời gian:</span> ${escapeHtml(item.time)}</p>
                            <p><span class="font-semibold text-slate-700">Số người tham gia:</span> ${escapeHtml(item.members)}</p>
                        </div>`;
                };
                select.addEventListener('change', render);
                render();
            });
        </script>
    @elseif($section === 'repository')
        @php
            $repositoryTypeLabels = [
                'TOPIC_FILE' => 'Hồ sơ đề tài',
                'REPORT' => 'Báo cáo',
                'SCIENTIFIC_TEXT' => 'Văn bản khoa học',
                'RESULT' => 'Kết quả',
                'OTHER' => 'Khác',
            ];
            $repositoryRegistrationInfo = $allRegistrations->mapWithKeys(fn ($registration) => [
                (string) $registration->id => [
                    'code' => $registration->project_code ?: 'Chưa cấp mã',
                    'title' => $registration->title,
                    'lead' => $registration->user?->name ?: 'Chưa rõ chủ nhiệm',
                    'unit' => $registration->user?->unit?->name ?: 'Chưa có đơn vị',
                    'category' => $registration->researchCategory?->name ?: 'Chưa có lĩnh vực',
                    'academic_year' => $registration->academic_year ?: 'Chưa có năm học',
                    'time' => ($registration->start_date?->format('d/m/Y') ?: '—').' - '.($registration->end_date?->format('d/m/Y') ?: '—'),
                    'members' => $registration->members->count() + 1,
                ],
            ]);
        @endphp
        <div class="sr-section-head">
            <div>
                <h2>Kho dữ liệu nghiên cứu khoa học</h2>
                <p>Lưu trữ hồ sơ, báo cáo, kết quả và minh chứng theo từng đề tài đã đăng ký.</p>
            </div>
            <div class="sr-section-kpis">
                <span><b>{{ number_format($stats['documents']) }}</b> tài liệu</span>
                <span><b>{{ number_format($stats['registrations']) }}</b> đề tài liên quan</span>
            </div>
        </div>
        <div class="sr-workflow mb-5">
            <span>1. Gắn đề tài</span>
            <span>2. Phân loại hồ sơ</span>
            <span>3. Lưu minh chứng</span>
            <span>4. Tra cứu/tải về</span>
        </div>
        <form method="POST" action="{{ route('scientific-research.repository.store') }}" enctype="multipart/form-data" class="mb-5 grid gap-3 md:grid-cols-4">@csrf
            <label class="text-sm font-semibold text-slate-700 md:col-span-2">Đề tài <span class="text-rose-500">*</span>
                <select name="registration_id" required id="sr-repository-registration" class="mt-1 w-full rounded-lg border px-3 py-2.5">
                    <option value="">Chọn đề tài chứa tài liệu/minh chứng</option>
                    @foreach($allRegistrations as $registration)
                        <option value="{{ $registration->id }}">{{ $registration->project_code ?: 'Chưa cấp mã' }} — {{ $registration->title }}</option>
                    @endforeach
                </select>
            </label>
            <label class="text-sm font-semibold text-slate-700">Loại tài liệu
                <select name="document_type" class="mt-1 w-full rounded-lg border px-3 py-2.5"><option value="TOPIC_FILE">Hồ sơ đề tài</option><option value="REPORT">Báo cáo</option><option value="SCIENTIFIC_TEXT">Văn bản khoa học</option><option value="RESULT">Kết quả</option><option value="OTHER">Khác</option></select>
            </label>
            <label class="text-sm font-semibold text-slate-700">Tệp tài liệu
                <input name="file" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip,.rar,.txt" class="mt-1 w-full rounded-lg border px-3 py-2.5">
            </label>
            <div id="sr-repository-registration-info" class="hidden rounded-lg border border-blue-100 bg-blue-50/50 p-3 text-sm md:col-span-4"></div>
            <label class="text-sm font-semibold text-slate-700 md:col-span-2">Tên tài liệu/minh chứng <span class="text-rose-500">*</span>
                <input name="title" required placeholder="VD: Thuyết minh đề tài, báo cáo tiến độ, biên bản nghiệm thu, minh chứng sản phẩm..." class="mt-1 w-full rounded-lg border px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold text-slate-700 md:col-span-2">Từ khóa
                <input name="keywords" placeholder="VD: báo cáo, nghiệm thu, bài báo, minh chứng..." class="mt-1 w-full rounded-lg border px-3 py-2.5">
            </label>
            <label class="text-sm font-semibold text-slate-700 md:col-span-4">Tóm tắt
                <textarea name="summary" rows="2" placeholder="Mô tả ngắn tài liệu này dùng cho nội dung gì của đề tài..." class="mt-1 w-full rounded-lg border px-3 py-2.5"></textarea>
            </label>
            <button class="rounded-lg bg-blue-600 px-4 py-2.5 font-bold text-white md:col-span-4"><i class="bi bi-database-add"></i> Đưa tài liệu vào kho dữ liệu</button>
        </form>
        <div class="grid gap-3">
            @forelse($repositoryDocuments as $item)
                <article class="rounded-xl border bg-white p-4 shadow-sm">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <p class="font-extrabold text-slate-900">{{ $item->title }}</p>
                            <p class="mt-1 text-sm font-semibold text-slate-700">{{ $item->registration?->title ?: 'Chưa gắn đề tài' }}</p>
                        </div>
                        <button type="button" class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-bold text-amber-700" onclick="document.getElementById('sr-repository-setup-{{ $item->id }}').classList.toggle('hidden')">Thiết lập</button>
                    </div>
                    <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                            <p class="text-[11px] font-bold uppercase text-slate-500">Mã đề tài</p>
                            <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $item->registration?->project_code ?: 'Chưa cấp mã' }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                            <p class="text-[11px] font-bold uppercase text-slate-500">Loại tài liệu</p>
                            <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $repositoryTypeLabels[$item->document_type] ?? $item->document_type }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                            <p class="text-[11px] font-bold uppercase text-slate-500">Từ khóa</p>
                            <p class="mt-0.5 text-sm font-semibold text-slate-800">{{ $item->keywords ?: 'Chưa có từ khóa' }}</p>
                        </div>
                        <div class="rounded-lg border border-slate-100 bg-slate-50 px-3 py-2">
                            <p class="text-[11px] font-bold uppercase text-slate-500">Tệp</p>
                            @if($item->file_path)
                                <a href="{{ asset('storage/'.$item->file_path) }}" class="mt-0.5 inline-block text-sm font-bold text-blue-700">{{ $item->file_name ?: 'Tải tài liệu' }}</a>
                            @else
                                <p class="mt-0.5 text-sm font-semibold text-slate-500">Chưa có tệp</p>
                            @endif
                        </div>
                    </div>
                    @if($item->registration)
                        <div class="mt-3 rounded-lg border border-blue-100 bg-blue-50/50 p-3">
                            <p class="text-xs font-bold uppercase text-blue-700">Thông tin lấy từ đăng ký đề tài</p>
                            <div class="mt-2 grid gap-2 text-sm sm:grid-cols-2 lg:grid-cols-4">
                                <p><span class="font-semibold text-slate-700">Chủ nhiệm:</span> {{ $item->registration->user?->name ?: 'Chưa rõ' }}</p>
                                <p><span class="font-semibold text-slate-700">Đơn vị:</span> {{ $item->registration->user?->unit?->name ?: 'Chưa có đơn vị' }}</p>
                                <p><span class="font-semibold text-slate-700">Năm học:</span> {{ $item->registration->academic_year ?: 'Chưa có năm học' }}</p>
                                <p><span class="font-semibold text-slate-700">Lĩnh vực:</span> {{ $item->registration->researchCategory?->name ?: 'Chưa có lĩnh vực' }}</p>
                            </div>
                        </div>
                    @endif
                    @if($item->summary)
                        <div class="mt-3 rounded-lg border border-slate-100 bg-white p-3 text-sm text-slate-700">
                            <span class="font-semibold">Tóm tắt:</span> {{ $item->summary }}
                        </div>
                    @endif
                    <div id="sr-repository-setup-{{ $item->id }}" class="mt-3 hidden rounded-lg border border-amber-200 bg-amber-50/40 p-3">
                        <div class="mb-3 flex items-center justify-between gap-2">
                            <p class="text-sm font-extrabold text-slate-900">Thiết lập tài liệu</p>
                            <button type="button" class="rounded-lg border bg-white px-3 py-1.5 text-xs font-bold text-slate-600" onclick="this.closest('[id^=sr-repository-setup-]').classList.add('hidden')">Thu gọn</button>
                        </div>
                        <form method="POST" action="{{ route('scientific-research.repository.update', $item) }}" enctype="multipart/form-data" class="grid gap-2 md:grid-cols-2">
                            @csrf @method('PATCH')
                            <label class="text-xs font-semibold text-slate-600 md:col-span-2">Đề tài<select name="registration_id" required class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"><option value="">Chọn đề tài</option>@foreach($allRegistrations as $registration)<option value="{{ $registration->id }}" @selected($item->registration_id === $registration->id)>{{ $registration->project_code ?: 'Chưa cấp mã' }} — {{ $registration->title }}</option>@endforeach</select></label>
                            <label class="text-xs font-semibold text-slate-600 md:col-span-2">Tên tài liệu/minh chứng<input name="title" value="{{ $item->title }}" required class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                            <label class="text-xs font-semibold text-slate-600">Loại tài liệu<select name="document_type" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"><option value="TOPIC_FILE" @selected($item->document_type === 'TOPIC_FILE')>Hồ sơ đề tài</option><option value="REPORT" @selected($item->document_type === 'REPORT')>Báo cáo</option><option value="SCIENTIFIC_TEXT" @selected($item->document_type === 'SCIENTIFIC_TEXT')>Văn bản khoa học</option><option value="RESULT" @selected($item->document_type === 'RESULT')>Kết quả</option><option value="OTHER" @selected($item->document_type === 'OTHER')>Khác</option></select></label>
                            <label class="text-xs font-semibold text-slate-600">Từ khóa<input name="keywords" value="{{ $item->keywords }}" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                            <label class="text-xs font-semibold text-slate-600 md:col-span-2">Thay tệp nếu cần<input name="file" type="file" accept=".pdf,.doc,.docx,.xls,.xlsx,.zip,.rar,.txt" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm"></label>
                            <label class="text-xs font-semibold text-slate-600 md:col-span-2">Tóm tắt<textarea name="summary" rows="2" class="mt-1 w-full rounded-lg border px-3 py-2 text-sm">{{ $item->summary }}</textarea></label>
                            <button class="rounded-lg bg-slate-900 px-3 py-2 text-sm font-bold text-white md:col-span-2">Lưu tài liệu</button>
                        </form>
                        <form method="POST" action="{{ route('scientific-research.repository.destroy', $item) }}" onsubmit="return confirm('Xóa tài liệu này?')" class="mt-2">@csrf @method('DELETE')<button class="text-sm font-bold text-rose-700">Xóa tài liệu</button></form>
                    </div>
                </article>
            @empty
                <div class="rounded-xl border bg-white px-4 py-8 text-center text-slate-500">Chưa có tài liệu theo đề tài.</div>
            @endforelse
        </div>
        <div class="mt-3">{{ $repositoryDocuments->links() }}</div>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const select = document.getElementById('sr-repository-registration');
                const box = document.getElementById('sr-repository-registration-info');
                if (!select || !box) return;
                const registrations = @json($repositoryRegistrationInfo);
                const escapeHtml = value => String(value || '').replace(/[&<>"']/g, char => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[char]));
                const render = () => {
                    const item = registrations[String(select.value)];
                    if (!item) {
                        box.classList.add('hidden');
                        box.innerHTML = '';
                        return;
                    }
                    box.classList.remove('hidden');
                    box.innerHTML = `
                        <p class="text-xs font-bold uppercase text-blue-700">Thông tin lấy từ đăng ký đề tài</p>
                        <div class="mt-2 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                            <p><span class="font-semibold text-slate-700">Mã đề tài:</span> ${escapeHtml(item.code)}</p>
                            <p><span class="font-semibold text-slate-700">Chủ nhiệm:</span> ${escapeHtml(item.lead)}</p>
                            <p><span class="font-semibold text-slate-700">Đơn vị:</span> ${escapeHtml(item.unit)}</p>
                            <p><span class="font-semibold text-slate-700">Năm học:</span> ${escapeHtml(item.academic_year)}</p>
                            <p><span class="font-semibold text-slate-700">Lĩnh vực:</span> ${escapeHtml(item.category)}</p>
                            <p><span class="font-semibold text-slate-700">Thời gian:</span> ${escapeHtml(item.time)}</p>
                            <p><span class="font-semibold text-slate-700">Số người tham gia:</span> ${escapeHtml(item.members)}</p>
                        </div>`;
                };
                select.addEventListener('change', render);
                render();
            });
        </script>
    @endif
</section>
@endif

@if($section === 'reports')
<section class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-extrabold text-slate-900">Báo cáo - thống kê NCKH</h2>
            <p class="mt-1 text-sm text-slate-500">Tổng hợp nhanh theo trạng thái, danh mục, kinh phí và sản phẩm. Các bảng bên dưới có thể cuộn khi dữ liệu nhiều.</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('scientific-research.reports.csv') }}" class="sr-action bg-emerald-600 px-4 py-2 text-sm text-white"><i class="bi bi-filetype-csv"></i> Tải dữ liệu dạng bảng</a>
            <a href="{{ route('scientific-research.reports.excel') }}" class="sr-action bg-blue-600 px-4 py-2 text-sm text-white"><i class="bi bi-file-earmark-excel"></i> Tải bảng tính</a>
            <a href="{{ route('scientific-research.reports.word') }}" class="sr-action bg-slate-900 px-4 py-2 text-sm text-white"><i class="bi bi-file-earmark-word"></i> Tải văn bản</a>
        </div>
    </div>
    <div class="mb-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4">
            <p class="text-xs font-bold uppercase text-indigo-500">Lượt xin gia hạn</p>
            <p class="mt-2 text-2xl font-extrabold text-indigo-900">{{ number_format((int) ($extensionSummary['total'] ?? 0)) }}</p>
        </div>
        <div class="rounded-xl border border-amber-100 bg-amber-50 p-4">
            <p class="text-xs font-bold uppercase text-amber-600">Đang chờ duyệt</p>
            <p class="mt-2 text-2xl font-extrabold text-amber-900">{{ number_format((int) ($extensionSummary['pending'] ?? 0)) }}</p>
        </div>
        <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-4">
            <p class="text-xs font-bold uppercase text-emerald-600">Đã duyệt gia hạn</p>
            <p class="mt-2 text-2xl font-extrabold text-emerald-900">{{ number_format((int) ($extensionSummary['approved'] ?? 0)) }}</p>
        </div>
        <div class="rounded-xl border border-rose-100 bg-rose-50 p-4">
            <p class="text-xs font-bold uppercase text-rose-600">Từ chối gia hạn</p>
            <p class="mt-2 text-2xl font-extrabold text-rose-900">{{ number_format((int) ($extensionSummary['rejected'] ?? 0)) }}</p>
        </div>
    </div>
    @php
        $registrationTotal = (int) ($stats['registrations'] ?? 0);
        $registrationPercentTotal = max(1, $registrationTotal);
        $extensionDisplayTotal = (int) ($extensionSummary['total'] ?? 0);
        $extensionPercentTotal = max(1, $extensionDisplayTotal);
        $statusRows = collect($statuses ?? [])->map(function ($label, $status) use ($statusSummary, $registrationTotal, $registrationPercentTotal) {
            $count = (int) ($statusSummary[$status] ?? 0);
            $isExtensionRequested = $status === \Modules\ScientificResearch\Models\ScientificResearchRegistration::STATUS_EXTENSION_REQUESTED;

            return [
                'label' => $isExtensionRequested ? 'Đang chờ duyệt gia hạn' : $label,
                'type' => 'Trạng thái hiện tại',
                'note' => $isExtensionRequested ? 'Không tính các đề tài đã duyệt gia hạn' : null,
                'count' => $count,
                'total' => $registrationTotal,
                'unit' => 'đề tài',
                'color' => 'bg-blue-500',
                'percent' => $registrationTotal > 0 ? round($count * 100 / $registrationPercentTotal) : 0,
            ];
        });
        $extensionRows = collect([
            ['label' => 'Lượt đang chờ duyệt gia hạn', 'count' => (int) ($extensionSummary['pending'] ?? 0), 'color' => 'bg-amber-500'],
            ['label' => 'Lượt đã duyệt gia hạn', 'count' => (int) ($extensionSummary['approved'] ?? 0), 'color' => 'bg-emerald-500'],
            ['label' => 'Lượt bị từ chối gia hạn', 'count' => (int) ($extensionSummary['rejected'] ?? 0), 'color' => 'bg-rose-500'],
        ])->map(function ($row) use ($extensionDisplayTotal, $extensionPercentTotal) {
            return $row + [
                'type' => 'Lịch sử gia hạn',
                'note' => 'Tính theo từng lượt gửi yêu cầu, không phải số đề tài hiện tại',
                'total' => $extensionDisplayTotal,
                'unit' => 'lượt',
                'percent' => $extensionDisplayTotal > 0 ? round($row['count'] * 100 / $extensionPercentTotal) : 0,
            ];
        });
        $reportFundingTypeLabels = [
            'ESTIMATE' => 'Dự kiến',
            'ALLOCATED' => 'Được cấp',
            'SPENT' => 'Chi phí phát sinh',
            'PAYMENT' => 'Đã thanh toán',
            'SETTLEMENT' => 'Chốt kinh phí',
        ];
        $reportProductTypeLabels = [
            'ARTICLE' => 'Bài báo',
            'TEXTBOOK' => 'Giáo trình/tài liệu',
            'INITIATIVE' => 'Sáng kiến',
            'MODEL' => 'Mô hình/sản phẩm',
            'WORK' => 'Công trình',
            'OTHER' => 'Khác',
        ];
    @endphp
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="font-bold text-slate-900">Thống kê trạng thái đề tài và gia hạn</h3>
            <p class="mt-1 text-sm text-slate-500">Các dòng trạng thái đếm theo đề tài hiện tại; các dòng gia hạn đếm theo lượt yêu cầu đã phát sinh.</p>
        </div>
        <div class="overflow-x-auto p-3">
            <table class="w-full min-w-[900px] text-sm">
                <thead class="text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Chỉ tiêu</th>
                        <th class="px-4 py-3">Loại số liệu</th>
                        <th class="px-4 py-3 text-right">Số lượng</th>
                        <th class="px-4 py-3">Tỷ trọng</th>
                    </tr>
                </thead>
                <tbody class="[&_tr+tr_td]:border-t [&_tr+tr_td]:border-slate-100">
                    <tr class="bg-slate-50/80">
                        <td colspan="4" class="px-4 py-2 text-xs font-extrabold uppercase text-slate-500">Trạng thái hiện tại của đề tài</td>
                    </tr>
                    @foreach($statusRows as $row)
                        <tr class="hover:bg-slate-50/80">
                            <td class="rounded-l-lg px-4 py-3.5">
                                <p class="font-semibold text-slate-800">{{ $row['label'] }}</p>
                                @if($row['note'])
                                    <p class="mt-0.5 text-xs font-medium text-slate-500">{{ $row['note'] }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-xs font-bold uppercase text-blue-700">{{ $row['type'] }}</td>
                            <td class="px-4 py-3.5 text-right font-extrabold text-slate-900">{{ number_format($row['count']) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-4">
                                    <div class="h-2.5 w-full max-w-sm overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full {{ $row['color'] }}" style="width: {{ $row['percent'] }}%"></div>
                                    </div>
                                    <span class="w-24 text-right text-xs font-bold text-slate-500">
                                        {{ $row['percent'] }}%
                                        <span class="block font-semibold text-slate-400">{{ number_format($row['count']) }}/{{ number_format($row['total']) }} {{ $row['unit'] }}</span>
                                    </span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    <tr class="bg-slate-50/80">
                        <td colspan="4" class="px-4 py-2 text-xs font-extrabold uppercase text-slate-500">Lịch sử lượt xin gia hạn</td>
                    </tr>
                    @foreach($extensionRows as $row)
                        <tr class="hover:bg-slate-50/80">
                            <td class="rounded-l-lg px-4 py-3.5">
                                <p class="font-semibold text-slate-800">{{ $row['label'] }}</p>
                                <p class="mt-0.5 text-xs font-medium text-slate-500">{{ $row['note'] }}</p>
                            </td>
                            <td class="px-4 py-3.5 text-xs font-bold uppercase text-emerald-700">{{ $row['type'] }}</td>
                            <td class="px-4 py-3.5 text-right font-extrabold text-slate-900">{{ number_format($row['count']) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-4">
                                    <div class="h-2.5 w-full max-w-sm overflow-hidden rounded-full bg-slate-100">
                                        <div class="h-full rounded-full {{ $row['color'] }}" style="width: {{ $row['percent'] }}%"></div>
                                    </div>
                                    <span class="w-24 text-right text-xs font-bold text-slate-500">
                                        {{ $row['percent'] }}%
                                        <span class="block font-semibold text-slate-400">{{ number_format($row['count']) }}/{{ number_format($row['total']) }} {{ $row['unit'] }}</span>
                                    </span>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-6 grid gap-5 xl:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="font-bold text-slate-900">Theo danh mục NCKH</h3>
            </div>
            <div class="max-h-80 overflow-y-auto">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-slate-100">
                        @forelse($categorySummary as $row)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="font-bold text-slate-900">{{ $row->code }}</p>
                                    <p class="mt-0.5 line-clamp-2 text-xs text-slate-500">{{ $row->name }}</p>
                                </td>
                                <td class="px-5 py-4 text-right font-extrabold text-blue-700">{{ number_format((int) $row->total) }}</td>
                            </tr>
                        @empty
                            <tr><td class="px-4 py-8 text-center text-sm text-slate-500">Chưa có dữ liệu.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="font-bold text-slate-900">Theo kinh phí</h3>
            </div>
            <div class="max-h-80 overflow-y-auto">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-slate-100">
                        @forelse($fundingTypeSummary as $row)
                            <tr>
                                <td class="px-5 py-4">
                                    <p class="font-bold text-slate-900">{{ $reportFundingTypeLabels[$row->type] ?? 'Loại kinh phí khác' }}</p>
                                    <p class="mt-0.5 text-xs text-slate-500">{{ number_format((int) $row->total) }} khoản</p>
                                </td>
                                <td class="px-5 py-4 text-right font-extrabold text-emerald-700">{{ number_format((float) $row->total_amount, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td class="px-4 py-8 text-center text-sm text-slate-500">Chưa có dữ liệu.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="font-bold text-slate-900">Theo sản phẩm</h3>
            </div>
            <div class="max-h-80 overflow-y-auto">
                <table class="w-full text-sm">
                    <tbody class="divide-y divide-slate-100">
                        @forelse($productTypeSummary as $type => $total)
                            <tr>
                                <td class="px-5 py-4 font-bold text-slate-900">{{ $reportProductTypeLabels[$type] ?? 'Loại sản phẩm khác' }}</td>
                                <td class="px-5 py-4 text-right font-extrabold text-violet-700">{{ number_format((int) $total) }}</td>
                            </tr>
                        @empty
                            <tr><td class="px-4 py-8 text-center text-sm text-slate-500">Chưa có dữ liệu.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endif

@if($section === 'audit')
<section class="mt-5 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
    <h2 class="mb-4 text-lg font-extrabold text-slate-900">Nhật ký thao tác NCKH</h2>
    <div class="overflow-x-auto">
        <table class="w-full min-w-[900px] text-left text-sm">
            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                <tr><th class="px-4 py-3">Thời gian</th><th class="px-4 py-3">Người thao tác</th><th class="px-4 py-3">Hành động</th><th class="px-4 py-3">Đối tượng</th><th class="px-4 py-3">Chi tiết</th></tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse($auditLogs as $log)
                    <tr class="align-top">
                        <td class="whitespace-nowrap px-4 py-3">{{ $log->created_at?->format('d/m/Y H:i:s') }}</td>
                        <td class="px-4 py-3 font-semibold">{{ $log->user?->name ?: 'Hệ thống' }}</td>
                        <td class="px-4 py-3"><span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-700">{{ $log->action_label }}</span></td>
                        <td class="px-4 py-3">{{ $log->title ?: class_basename($log->target_type ?: '') }}</td>
                        <td class="px-4 py-3">
                            @forelse($log->detail_lines as $line)
                                <div class="max-w-xl text-sm text-slate-700">{{ $line }}</div>
                            @empty
                                <span class="text-slate-400">Không có thông tin bổ sung.</span>
                            @endforelse
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="px-4 py-10 text-center text-slate-500">Chưa có nhật ký thao tác.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-3">{{ $auditLogs->links() }}</div>
</section>
@endif
</div>
</div>
@endsection
