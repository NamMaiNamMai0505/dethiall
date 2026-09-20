@section('title', 'Chi tiết đăng ký NCKH')
@section('page-title', 'Chi tiết đăng ký NCKH')
@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
<x-breadcrumb :items="[['title' => 'Trang chủ'], ['title' => 'Nghiên cứu khoa học', 'url' => route('scientific-research.index')], ['title' => 'Chi tiết đăng ký']]" />
<x-page-header title="{{ $registration->title }}" subtitle="{{ $registration->researchCategory?->code }} — {{ $registration->researchCategory?->name }}" />
@include('partials.module-menu', ['module' => 'scientific-research'])
@include('scientific-research::partials.ui-style')

<div class="sr-page">
@if(session('success'))
    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 font-semibold text-emerald-700">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 font-semibold text-rose-700">{{ $errors->first() }}</div>
@endif
@php
    $isReviewMode = $isReviewMode ?? request('mode') === 'review';
    $canChangeStatus = \App\Support\PermissionCheck::userCanAny([
        'scientific-research.registrations.status',
        'scientific-research.registrations.unit-approve',
        'scientific-research.registrations.agency-approve',
        'scientific-research.registrations.return',
    ]);
    $canSyncStandardHours = \App\Support\PermissionCheck::userCan('scientific-research.registrations.sync-standard-hours');
    $isRegistrationOwner = (int) $registration->user_id === (int) auth()->id();
    $canEditRegistration = $isRegistrationOwner || \App\Support\PermissionCheck::userCan('scientific-research.registrations.edit');
    $canDeleteRegistration = \App\Support\PermissionCheck::userCan('scientific-research.registrations.delete');
    $canSubmitRegistrationRequest = ! $isReviewMode && ($isRegistrationOwner || $canEditRegistration);
    $canReviewExtension = \App\Support\PermissionCheck::userCanAny([
        'scientific-research.registrations.status',
        'scientific-research.registrations.agency-approve',
    ]);
    $canSubmitResult = ! $isReviewMode && \App\Support\PermissionCheck::userCanAny(['scientific-research.results.create', 'scientific-research.results.submit']);
    $canUpdateResult = \App\Support\PermissionCheck::userCanAny(['scientific-research.results.edit', 'scientific-research.results.approve']);
    $canDeleteResult = \App\Support\PermissionCheck::userCan('scientific-research.results.delete');
    $effectiveParticipantCount = max((int) $registration->participant_count, $registration->members->count() + 1);
    $initialScientificResearchMembers = collect(old('member_user_ids', $registration->members->pluck('user_id')->all()))
        ->values()
        ->map(function ($userId, $index) use ($registration) {
            $member = $registration->members->values()->get($index);
            return [
                'user_id' => $userId,
                'role' => old("member_roles.$index", $member?->role ?: 'Thành viên'),
                'percent' => old("member_contribution_percents.$index", $member?->contribution_percent ?: 0),
            ];
        })
        ->filter(fn ($row) => filled($row['user_id']) || filled($row['role']) || (float) $row['percent'] > 0)
        ->values();
@endphp

<div class="grid min-w-0 gap-5 xl:grid-cols-3">
    <section class="p-5 xl:col-span-2">
        <div class="grid min-w-0 gap-4 md:grid-cols-2">
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 md:col-span-2">
                <p class="text-sm font-semibold text-blue-900">Nơi tiếp nhận</p>
                <p class="mt-1 text-sm text-blue-800">Hồ sơ đi qua Chỉ huy đơn vị quản lý trước; sau khi được duyệt bước 1 sẽ chuyển đến Ban Khoa học Quân sự tiếp nhận và thẩm định.</p>
            </div>
            <div><p class="text-sm text-slate-500">Chủ nhiệm đề tài</p><p class="font-bold">{{ $registration->user?->name }}</p></div>
            <div><p class="text-sm text-slate-500">Trạng thái</p><p class="font-bold">{{ $statuses[$registration->status] ?? $registration->status }}</p></div>
            <div><p class="text-sm text-slate-500">Thời gian thực hiện</p><p class="font-bold">{{ $registration->start_date?->format('d/m/Y') ?: '—' }} → {{ $registration->end_date?->format('d/m/Y') ?: '—' }}</p></div>
            <div><p class="text-sm text-slate-500">Kinh phí</p><p class="font-bold">{{ number_format((float) $registration->budget, 0, ',', '.') }} đ</p></div>
            <div><p class="text-sm text-slate-500">Tổng số người tham gia</p><p class="font-bold">{{ $effectiveParticipantCount }} người <span class="text-xs font-normal text-slate-500">(bao gồm chủ nhiệm)</span></p></div>
            <div><p class="text-sm text-slate-500">Năm học</p><p class="font-bold">{{ $registration->academic_year ?: '—' }}</p></div>
            <div><p class="text-sm text-slate-500">Năm thực hiện</p><p class="font-bold">{{ $registration->implementation_year ?: '—' }}</p></div>
            <div><p class="text-sm text-slate-500">Số năm thực hiện</p><p class="font-bold">{{ $registration->duration_years ?: 1 }}</p></div>
            <div><p class="text-sm text-slate-500">Số lượng sản phẩm</p><p class="font-bold">{{ $registration->product_quantity ?: 1 }}</p></div>
            <div><p class="text-sm text-slate-500">Tỷ lệ chủ nhiệm</p><p class="font-bold">{{ number_format((float) ($registration->lead_contribution_percent ?? 100), 2, ',', '.') }}%</p></div>
            <div><p class="text-sm text-slate-500">Đợt thông báo NCKH</p><p class="font-bold">{{ $registration->announcement?->title ?: 'Không gắn với đợt thông báo' }}</p></div>
            <div class="md:col-span-2"><p class="text-sm text-slate-500">Chủ đề/lĩnh vực đề tài</p><p class="font-bold">{{ $registration->topic ?: '—' }}</p></div>
            <div><p class="text-sm text-slate-500">Tiến độ</p><p class="font-bold">{{ (int) $registration->progress_percent }}%</p></div>
            <div><p class="text-sm text-slate-500">Gia hạn đến</p><p class="font-bold">{{ $registration->extended_until?->format('d/m/Y') ?: '—' }}</p></div>
            <div><p class="text-sm text-slate-500">Giờ chuẩn GV</p><p class="font-bold">{{ $registration->standard_hours_research_record_id ? 'Đã đồng bộ #'.$registration->standard_hours_research_record_id : 'Chưa đồng bộ' }}</p></div>
            @if($registration->unit_review_note || $registration->review_note)
                <div class="md:col-span-2 rounded-lg border border-amber-200 bg-amber-50 p-3">
                    <p class="text-sm font-semibold text-amber-900">Ghi chú thẩm định / yêu cầu bổ sung</p>
                    @if($registration->unit_review_note)
                        <p class="mt-1 whitespace-pre-line text-sm text-amber-800">Chỉ huy đơn vị: {{ $registration->unit_review_note }}</p>
                    @endif
                    @if($registration->review_note)
                        <p class="mt-1 whitespace-pre-line text-sm text-amber-800">Ban Khoa học Quân sự: {{ $registration->review_note }}</p>
                    @endif
                </div>
            @endif
            @if($registration->revision_response_note)
                <div class="md:col-span-2 rounded-lg border border-blue-200 bg-blue-50 p-3">
                    <p class="text-sm font-semibold text-blue-900">Nội dung đã gửi bổ sung</p>
                    <p class="mt-1 whitespace-pre-line text-sm text-blue-800">{{ $registration->revision_response_note }}</p>
                    <p class="mt-1 text-xs text-blue-700">Gửi lúc: {{ $registration->revision_submitted_at?->format('d/m/Y H:i') ?: '—' }}</p>
                </div>
            @endif
            @if($registration->extension_request_note || $registration->extension_review_note)
                <div class="md:col-span-2 rounded-lg border border-indigo-200 bg-indigo-50 p-3">
                    <p class="text-sm font-semibold text-indigo-900">Yêu cầu gia hạn</p>
                    @if($registration->extension_requested_until)
                        <p class="mt-1 text-sm text-indigo-800">Ngày xin gia hạn đến: <strong>{{ $registration->extension_requested_until->format('d/m/Y') }}</strong></p>
                    @endif
                    @if($registration->extension_request_note)
                        <p class="mt-1 whitespace-pre-line text-sm text-indigo-800">Lý do: {{ $registration->extension_request_note }}</p>
                    @endif
                    @if($registration->extension_review_note)
                        <p class="mt-1 whitespace-pre-line text-sm text-indigo-800">Ý kiến duyệt: {{ $registration->extension_review_note }}</p>
                    @endif
                </div>
            @endif
            <div class="md:col-span-2"><p class="text-sm text-slate-500">Nội dung</p><p class="whitespace-pre-line">{{ $registration->content ?: '—' }}</p></div>
        </div>
        <div class="mt-5">
            <h3 class="font-bold text-slate-900">Giảng viên đồng tác giả</h3>
            <div class="mt-2 overflow-x-auto rounded-lg border border-slate-200">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-50 text-left text-slate-600">
                        <tr>
                            <th class="px-3 py-2">Họ tên</th>
                            <th class="px-3 py-2">Đơn vị</th>
                            <th class="px-3 py-2">Vai trò</th>
                            <th class="px-3 py-2">Tỷ lệ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-t">
                            <td class="px-3 py-2 font-semibold">{{ $registration->user?->name }}</td>
                            <td class="px-3 py-2">{{ $registration->user?->unit?->name ?: '—' }}</td>
                            <td class="px-3 py-2">Chủ nhiệm</td>
                            <td class="px-3 py-2">{{ number_format((float) ($registration->lead_contribution_percent ?? 100), 2, ',', '.') }}%</td>
                        </tr>
                        @forelse($registration->members as $member)
                            <tr class="border-t">
                                <td class="px-3 py-2 font-semibold">{{ $member->full_name }}</td>
                                <td class="px-3 py-2">{{ $member->unit_name ?: '—' }}</td>
                                <td class="px-3 py-2">{{ $member->role }}</td>
                                <td class="px-3 py-2">{{ number_format((float) $member->contribution_percent, 2, ',', '.') }}%</td>
                            </tr>
                        @empty
                            <tr class="border-t"><td colspan="4" class="px-3 py-3 text-slate-500">Không có đồng tác giả.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-5">
            <h3 class="font-bold text-slate-900">File đăng ký</h3>
            <div class="mt-2 flex flex-wrap gap-2">
                @forelse($registration->files as $file)
                    <a href="{{ route('scientific-research.files.download', $file) }}" class="rounded-lg border px-3 py-2 text-sm font-bold text-blue-700"><i class="bi bi-download"></i> {{ $file->file_name }}</a>
                @empty
                    <span class="text-sm text-slate-500">Chưa có file.</span>
                @endforelse
            </div>
        </div>
    </section>

    <section class="p-5">
        <h2 class="font-extrabold text-slate-900">Thẩm định, phê duyệt đề tài</h2>
        @if($canChangeStatus)
            <div class="mt-3 rounded-lg border border-blue-200 bg-blue-50 p-3 text-sm text-blue-800">
                Xử lý theo quy trình quản lý đề tài NCKH: phê duyệt, yêu cầu bổ sung hoặc không phê duyệt hồ sơ.
            </div>
            <form method="POST" action="{{ route('scientific-research.registrations.status', $registration) }}" class="mt-4 grid gap-3">
                @csrf @method('PATCH')
                <input type="hidden" name="progress_percent" value="{{ (int) $registration->progress_percent }}">
                <input type="hidden" name="extended_until" value="{{ $registration->extended_until?->format('Y-m-d') }}">
                <textarea name="review_note" rows="4" placeholder="Ý kiến thẩm định / nội dung yêu cầu bổ sung / lý do từ chối" class="rounded-lg border px-3 py-2.5">{{ old('review_note', $registration->review_note) }}</textarea>
                <div class="grid gap-2">
                    @if(in_array($registration->status, ['SUBMITTED', 'PROPOSED'], true))
                        <button name="status" value="UNIT_APPROVED" class="sr-action bg-emerald-600 px-4 py-2.5 text-white"><i class="bi bi-check2-circle"></i> Phê duyệt cấp đơn vị</button>
                        <button name="status" value="NEEDS_REVISION" class="sr-action border border-amber-200 px-4 py-2.5 text-amber-700"><i class="bi bi-pencil-square"></i> Yêu cầu bổ sung</button>
                        <button name="status" value="REJECTED" class="sr-action border border-rose-200 px-4 py-2.5 text-rose-700"><i class="bi bi-x-circle"></i> Không phê duyệt</button>
                    @elseif($registration->status === 'UNIT_APPROVED')
                        <button name="status" value="UNDER_REVIEW" class="sr-action bg-blue-600 px-4 py-2.5 text-white"><i class="bi bi-search"></i> Tiếp nhận thẩm định</button>
                        <button name="status" value="NEEDS_REVISION" class="sr-action border border-amber-200 px-4 py-2.5 text-amber-700"><i class="bi bi-pencil-square"></i> Yêu cầu bổ sung</button>
                        <button name="status" value="REJECTED" class="sr-action border border-rose-200 px-4 py-2.5 text-rose-700"><i class="bi bi-x-circle"></i> Không phê duyệt</button>
                    @elseif($registration->status === 'UNDER_REVIEW')
                        <button name="status" value="APPROVED" class="sr-action bg-emerald-600 px-4 py-2.5 text-white"><i class="bi bi-check2-circle"></i> Phê duyệt đề tài</button>
                        <button name="status" value="NEEDS_REVISION" class="sr-action border border-amber-200 px-4 py-2.5 text-amber-700"><i class="bi bi-pencil-square"></i> Yêu cầu bổ sung</button>
                        <button name="status" value="REJECTED" class="sr-action border border-rose-200 px-4 py-2.5 text-rose-700"><i class="bi bi-x-circle"></i> Không phê duyệt</button>
                    @elseif($registration->status === 'NEEDS_REVISION')
                        <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800">Đang chờ chủ nhiệm gửi hồ sơ bổ sung ở chức năng “Bổ sung & Gia hạn”.</div>
                    @elseif($registration->status === 'EXTENSION_REQUESTED')
                        <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-3 text-sm text-indigo-800">Yêu cầu gia hạn xử lý ở khối “Duyệt yêu cầu gia hạn” phía dưới.</div>
                    @else
                        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 text-sm text-slate-600">Trạng thái hiện tại không còn quyết định thẩm định trực tiếp.</div>
                    @endif
                </div>
            </form>
        @else
            <p class="mt-3 text-sm text-slate-500">Tài khoản hiện tại chỉ được xem trạng thái hồ sơ.</p>
        @endif
        @if($canSyncStandardHours && $registration->status === 'COMPLETED' && ! $registration->standard_hours_research_record_id)
            <form method="POST" action="{{ route('scientific-research.registrations.sync-standard-hours', $registration) }}" class="mt-3" onsubmit="return confirm('Đồng bộ đề tài này sang Giờ chuẩn GV?')">
                @csrf
                <button class="sr-action w-full bg-emerald-600 px-4 py-2.5 text-white"><i class="bi bi-arrow-left-right"></i> Đẩy sang Giờ chuẩn GV</button>
            </form>
        @endif
    </section>
</div>

@if($isReviewMode && $registration->status === 'EXTENSION_REQUESTED')
<section class="mt-5 p-5">
    <h2 class="font-extrabold text-slate-900">Duyệt yêu cầu gia hạn</h2>
    <div class="mt-4 rounded-lg border border-indigo-200 bg-indigo-50 p-4">
        <p class="font-bold text-indigo-900">Yêu cầu gia hạn đang chờ duyệt</p>
        <p class="mt-1 text-sm text-indigo-800">Ngày đề nghị: <strong>{{ $registration->extension_requested_until?->format('d/m/Y') ?: '—' }}</strong></p>
        <p class="mt-2 whitespace-pre-line text-sm text-indigo-800">{{ $registration->extension_request_note ?: 'Chưa nhập lý do.' }}</p>
        @if($canReviewExtension)
            <form method="POST" action="{{ route('scientific-research.registrations.extension.review', $registration) }}" class="mt-3 grid gap-3">
                @csrf @method('PATCH')
                <textarea name="extension_review_note" rows="3" placeholder="Ý kiến duyệt/từ chối gia hạn" class="w-full rounded-lg border px-3 py-2.5">{{ old('extension_review_note') }}</textarea>
                <div class="flex flex-wrap gap-2">
                    <button name="decision" value="approve" class="sr-action bg-emerald-600 px-4 py-2.5 text-white"><i class="bi bi-check2-circle"></i> Duyệt gia hạn</button>
                    <button name="decision" value="reject" class="sr-action border border-rose-200 px-4 py-2.5 text-rose-700"><i class="bi bi-x-circle"></i> Từ chối gia hạn</button>
                </div>
            </form>
        @endif
    </div>
</section>
@endif

@if(! $isReviewMode)
<section class="mt-5 p-5">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <h2 class="font-extrabold text-slate-900">Cập nhật thông tin đề tài</h2>
        @if($canDeleteRegistration)
        <form method="POST" action="{{ route('scientific-research.registrations.destroy', $registration) }}" onsubmit="return confirm('Xóa đề tài NCKH này?')">
            @csrf @method('DELETE')
            <button class="sr-action border border-rose-200 px-3 py-2 text-sm text-rose-700"><i class="bi bi-trash"></i> Xóa đề tài</button>
        </form>
        @endif
    </div>
    @if($canEditRegistration)
    <form method="POST" action="{{ route('scientific-research.registrations.update', $registration) }}" class="grid min-w-0 gap-4 md:grid-cols-2">
        @csrf @method('PATCH')
        <label class="text-sm font-semibold text-slate-700 md:col-span-2">Đợt thông báo NCKH
            <select name="announcement_id" class="mt-1 w-full rounded-lg border px-3 py-2.5">
                <option value="">Không gắn với đợt thông báo</option>
                @foreach($openAnnouncements as $announcement)
                    <option value="{{ $announcement->id }}" @selected($registration->announcement_id === $announcement->id)>{{ $announcement->title }}</option>
                @endforeach
            </select>
            <span class="mt-1 block text-xs text-slate-500">Chỉ dùng để liên kết đề tài với thông báo/đợt mở đăng ký NCKH.</span>
        </label>
        <label class="text-sm font-semibold text-slate-700 md:col-span-2">Danh mục NCKH từ Giờ chuẩn GV
            <select name="research_category_id" required class="mt-1 w-full rounded-lg border px-3 py-2.5">
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected($registration->research_category_id === $category->id)>{{ $category->code }} — {{ $category->name }}</option>
                @endforeach
            </select>
            <span class="mt-1 block text-xs text-slate-500">Chỉ chọn danh mục có sẵn. Thêm/sửa danh mục thực hiện bên Giờ chuẩn GV.</span>
        </label>
        <div class="rounded-lg border border-slate-200 bg-slate-50 p-3 md:col-span-2">
            <p class="text-sm font-semibold text-slate-700">Chủ nhiệm đề tài</p>
            <p class="mt-1 font-bold text-slate-900">{{ $registration->user?->name ?: '—' }}</p>
            <p class="mt-1 text-xs text-slate-500">Chủ nhiệm đề tài được xác định theo tài khoản đã tạo đăng ký.</p>
        </div>
        <label class="text-sm font-semibold text-slate-700 md:col-span-2">Tên đề tài / tên sản phẩm<input name="title" value="{{ old('title', $registration->title) }}" required class="mt-1 w-full rounded-lg border px-3 py-2.5"></label>
        <label class="text-sm font-semibold text-slate-700 md:col-span-2">Chủ đề/lĩnh vực đề tài<input name="topic" value="{{ old('topic', $registration->topic) }}" placeholder="VD: Chuyển đổi số trong đào tạo, điều dưỡng, y học cơ sở..." class="mt-1 w-full rounded-lg border px-3 py-2.5"></label>
        <label class="text-sm font-semibold text-slate-700 md:col-span-2">Nội dung<textarea name="content" rows="3" class="mt-1 w-full rounded-lg border px-3 py-2.5">{{ old('content', $registration->content) }}</textarea></label>
        <label class="text-sm font-semibold text-slate-700">Năm học<input name="academic_year" value="{{ old('academic_year', $registration->academic_year) }}" placeholder="VD: 2026-2027" class="mt-1 w-full rounded-lg border px-3 py-2.5"></label>
        <label class="text-sm font-semibold text-slate-700">Năm thực hiện<input name="implementation_year" type="number" min="2000" max="2200" value="{{ old('implementation_year', $registration->implementation_year ?: now()->year) }}" class="mt-1 w-full rounded-lg border px-3 py-2.5"></label>
        <label class="text-sm font-semibold text-slate-700">Kinh phí<input name="budget" type="number" min="0" step="1000" value="{{ old('budget', (float) $registration->budget) }}" class="mt-1 w-full rounded-lg border px-3 py-2.5"></label>
        <label class="text-sm font-semibold text-slate-700">Từ ngày<input name="start_date" type="date" value="{{ old('start_date', $registration->start_date?->format('Y-m-d')) }}" class="mt-1 w-full rounded-lg border px-3 py-2.5"></label>
        <label class="text-sm font-semibold text-slate-700">Đến ngày<input name="end_date" type="date" value="{{ old('end_date', $registration->end_date?->format('Y-m-d')) }}" class="mt-1 w-full rounded-lg border px-3 py-2.5"></label>
        <label class="text-sm font-semibold text-slate-700">Số năm thực hiện<input name="duration_years" type="number" min="0.25" max="20" step="0.25" value="{{ old('duration_years', $registration->duration_years ?: 1) }}" class="mt-1 w-full rounded-lg border px-3 py-2.5"></label>
        <label class="text-sm font-semibold text-slate-700">Số lượng sản phẩm<input name="product_quantity" type="number" min="1" max="999" value="{{ old('product_quantity', $registration->product_quantity ?: 1) }}" class="mt-1 w-full rounded-lg border px-3 py-2.5"></label>
        <label class="text-sm font-semibold text-slate-700">Tổng số người tham gia <span class="text-xs font-normal text-slate-500">(bao gồm chủ nhiệm)</span><input name="participant_count" type="number" min="1" max="999" value="{{ old('participant_count', $effectiveParticipantCount) }}" required class="mt-1 w-full rounded-lg border px-3 py-2.5" data-sr-participant-count></label>
        <label class="text-sm font-semibold text-slate-700">Tỷ lệ chủ nhiệm (%)<input name="lead_contribution_percent" type="number" min="0" max="100" step="0.01" value="{{ old('lead_contribution_percent', $registration->lead_contribution_percent ?? 100) }}" class="mt-1 w-full rounded-lg border px-3 py-2.5"></label>
        <div class="md:col-span-2 rounded-lg border border-slate-200 bg-white p-4" data-sr-members-block data-sr-initial-members="{{ $initialScientificResearchMembers->toJson() }}">
            <div class="mb-3 flex items-center justify-between gap-3">
                <div>
                    <p class="font-bold text-slate-900">Thành viên tham gia đề tài</p>
                    <p class="text-xs text-slate-500">Chủ nhiệm cập nhật danh sách thành viên của đề tài tại đây. Thông tin năng lực/chuyên môn từng cán bộ cập nhật ở mục Cán bộ NCKH.</p>
                </div>
                <span class="sr-badge bg-slate-100 text-slate-700" data-sr-member-count-label>0 thành viên</span>
            </div>
            <div class="grid gap-3" data-sr-members-list></div>
            <div class="hidden rounded-lg border border-dashed border-slate-300 bg-slate-50 p-4 text-sm text-slate-500" data-sr-members-empty>
                Chỉ có chủ nhiệm đề tài, không cần nhập đồng tác giả.
            </div>
        </div>
        <button class="sr-action bg-blue-600 px-5 py-2.5 text-white md:col-span-2"><i class="bi bi-save"></i> Lưu thông tin đề tài</button>
    </form>
    @else
        <p class="text-sm text-slate-500">Tài khoản hiện tại chưa có quyền sửa thông tin đề tài.</p>
    @endif
</section>
@endif

@if($registration->results->isNotEmpty() || $canUpdateResult)
<section class="mt-5 p-5">
    <h2 class="font-extrabold text-slate-900">Kết quả đã nộp</h2>
    <div class="mt-4 space-y-4">
        @forelse($registration->results as $result)
            <article class="rounded-xl border border-slate-200 p-4">
                @if($canUpdateResult)
                <form method="POST" action="{{ route('scientific-research.results.update', $result) }}" class="grid gap-3 md:grid-cols-4">
                    @csrf @method('PATCH')
                    <label class="text-sm font-semibold text-slate-700">Ngày hoàn thành<input name="completed_on" type="date" value="{{ $result->completed_on?->format('Y-m-d') }}" class="mt-1 w-full rounded-lg border px-3 py-2.5"></label>
                    <label class="text-sm font-semibold text-slate-700">Năm<input name="implementation_year" type="number" min="2000" max="2200" value="{{ $result->implementation_year }}" required class="mt-1 w-full rounded-lg border px-3 py-2.5"></label>
                    <label class="text-sm font-semibold text-slate-700">Trạng thái<select name="status" class="mt-1 w-full rounded-lg border px-3 py-2.5"><option value="SUBMITTED" @selected($result->status === 'SUBMITTED')>Đã nộp</option><option value="ACCEPTED" @selected($result->status === 'ACCEPTED')>Đã nghiệm thu</option><option value="NEEDS_REVISION" @selected($result->status === 'NEEDS_REVISION')>Cần bổ sung</option><option value="REJECTED" @selected($result->status === 'REJECTED')>Từ chối</option></select></label>
                    <label class="text-sm font-semibold text-slate-700">Ghi chú duyệt<input name="review_note" value="{{ $result->review_note }}" class="mt-1 w-full rounded-lg border px-3 py-2.5"></label>
                    <label class="text-sm font-semibold text-slate-700 md:col-span-4">Tóm tắt<textarea name="summary" rows="2" class="mt-1 w-full rounded-lg border px-3 py-2.5">{{ $result->summary }}</textarea></label>
                    <button class="rounded-lg bg-slate-900 px-4 py-2.5 font-bold text-white md:col-span-2">Cập nhật kết quả</button>
                </form>
                @else
                    <div class="grid gap-2 text-sm md:grid-cols-3">
                        <div><span class="text-slate-500">Ngày hoàn thành:</span> <strong>{{ $result->completed_on?->format('d/m/Y') ?: '—' }}</strong></div>
                        <div><span class="text-slate-500">Năm:</span> <strong>{{ $result->implementation_year }}</strong></div>
                        <div><span class="text-slate-500">Trạng thái:</span> <strong>{{ $result->status }}</strong></div>
                        <div class="md:col-span-3 whitespace-pre-line">{{ $result->summary ?: '—' }}</div>
                    </div>
                @endif
                <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                    <div class="flex flex-wrap gap-2">
                        @forelse($result->files as $file)
                            <a href="{{ route('scientific-research.files.download', $file) }}" class="rounded-lg border px-3 py-2 text-sm font-bold text-blue-700"><i class="bi bi-download"></i> {{ $file->file_name }}</a>
                        @empty
                            <span class="text-sm text-slate-500">Chưa có file kết quả.</span>
                        @endforelse
                    </div>
                    @if($canDeleteResult)
                    <form method="POST" action="{{ route('scientific-research.results.destroy', $result) }}" onsubmit="return confirm('Xóa kết quả này?')">
                        @csrf @method('DELETE')
                        <button class="rounded-lg border border-rose-200 px-3 py-2 text-sm font-bold text-rose-700">Xóa kết quả</button>
                    </form>
                    @endif
                </div>
            </article>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">Chưa có kết quả đã nộp.</div>
@endforelse
    </div>
</section>
@endif
</div>

<template id="sr-member-row-template">
    <div class="grid gap-3 rounded-lg border border-slate-100 bg-slate-50 p-3 md:grid-cols-[48px_minmax(0,2fr)_minmax(0,1fr)_160px]">
        <div class="flex h-10 items-center justify-center self-end rounded-lg bg-white text-sm font-extrabold text-slate-600" data-sr-member-index></div>
        <label class="text-xs font-bold text-slate-600">Giảng viên đồng tác giả
            <select name="member_user_ids[]" class="mt-1 w-full rounded-lg border px-3 py-2.5 text-sm" data-sr-member-user>
                <option value="">Chọn thành viên</option>
                @foreach($internalUsers as $memberUser)
                    <option value="{{ $memberUser->id }}">
                        {{ $memberUser->name }}{{ $memberUser->code ? ' - '.$memberUser->code : '' }}{{ $memberUser->unit?->name ? ' - '.$memberUser->unit->name : '' }}
                    </option>
                @endforeach
            </select>
        </label>
        <label class="text-xs font-bold text-slate-600">Vai trò trong đề tài
            <input name="member_roles[]" placeholder="VD: Thành viên" class="mt-1 w-full rounded-lg border px-3 py-2.5 text-sm" data-sr-member-role>
        </label>
        <label class="text-xs font-bold text-slate-600">Tỷ lệ đóng góp (%)
            <input name="member_contribution_percents[]" type="number" min="0" max="100" step="0.01" class="mt-1 w-full rounded-lg border px-3 py-2.5 text-sm" placeholder="VD: 20" data-sr-member-percent>
        </label>
    </div>
</template>

</div>
@endsection
