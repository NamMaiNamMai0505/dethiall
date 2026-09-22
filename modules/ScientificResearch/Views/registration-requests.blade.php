@section('title', 'Bổ sung và gia hạn đề tài NCKH')
@section('page-title', 'Bổ sung và gia hạn đề tài NCKH')
@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
<x-breadcrumb :items="[['title' => 'Trang chủ'], ['title' => 'Nghiên cứu khoa học', 'url' => route('scientific-research.index')], ['title' => 'Bổ sung & Gia hạn']]" />
<x-page-header title="BỔ SUNG HỒ SƠ & GIA HẠN ĐỀ TÀI" subtitle="Xử lý hồ sơ đã nộp: bổ sung/chỉnh sửa hồ sơ theo yêu cầu thẩm định hoặc xin gia hạn đề tài đã duyệt" />
@include('partials.module-menu', ['module' => 'scientific-research'])
@include('scientific-research::partials.ui-style')

<div class="sr-page">
@if(session('success'))
    <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 font-semibold text-emerald-700">{{ session('success') }}</div>
@endif
@if($errors->any())
    <div class="mb-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 font-semibold text-rose-700">{{ $errors->first() }}</div>
@endif

<section class="p-5">
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="font-extrabold text-slate-900">Danh sách hồ sơ cần xử lý</h2>
            <p class="text-sm text-slate-500">Bổ sung ở đây là bổ sung/chỉnh sửa hồ sơ đề tài theo yêu cầu thẩm định; không phải bổ sung thành viên.</p>
        </div>
        <form method="GET" action="{{ route('scientific-research.registrations.requests') }}" class="flex min-w-[280px] flex-wrap gap-2">
            <input name="q" value="{{ request('q') }}" placeholder="Tìm mã, tên đề tài, chủ nhiệm" class="min-w-0 flex-1 rounded-lg border px-3 py-2.5">
            <button class="sr-action bg-slate-900 px-4 py-2.5 text-white"><i class="bi bi-search"></i> Tìm</button>
        </form>
    </div>

    <div class="mb-6 rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
                <h3 class="font-extrabold text-slate-900">Bảng theo dõi xin gia hạn</h3>
                <p class="text-sm text-slate-500">Tổng hợp các đề tài đã từng xin gia hạn và số lần gia hạn của từng đề tài.</p>
            </div>
            <span class="rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700">{{ number_format($extensionOverview->total()) }} đề tài</span>
        </div>
        <div class="overflow-x-auto p-3">
            <table class="w-full min-w-[960px] text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Đề tài</th>
                        <th class="px-4 py-3">Người xin / Chủ nhiệm</th>
                        <th class="px-4 py-3 text-right">Số lần</th>
                        <th class="px-4 py-3 text-center">Chờ duyệt</th>
                        <th class="px-4 py-3 text-center">Đã duyệt</th>
                        <th class="px-4 py-3 text-center">Từ chối</th>
                        <th class="px-4 py-3">Lần gần nhất</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($extensionOverview as $item)
                        @php
                            $latestExtension = $item->extensionRequests->first();
                        @endphp
                        <tr class="align-top hover:bg-slate-50/80">
                            <td class="px-4 py-3">
                                <p class="font-bold text-slate-900">{{ $item->project_code ?: 'Chưa cấp mã' }}</p>
                                <p class="mt-1 line-clamp-2 text-slate-600">{{ $item->title }}</p>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-bold text-slate-900">{{ $latestExtension?->requester?->name ?: $item->user?->name ?: '—' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $item->user?->unit?->name ?: '—' }}</p>
                            </td>
                            <td class="px-4 py-3 text-right text-lg font-extrabold text-indigo-700">{{ number_format((int) $item->extension_requests_count) }}</td>
                            <td class="px-4 py-3 text-center font-bold text-amber-700">{{ number_format((int) $item->pending_extension_requests_count) }}</td>
                            <td class="px-4 py-3 text-center font-bold text-emerald-700">{{ number_format((int) $item->approved_extension_requests_count) }}</td>
                            <td class="px-4 py-3 text-center font-bold text-rose-700">{{ number_format((int) $item->rejected_extension_requests_count) }}</td>
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-800">{{ $latestExtension?->requested_at?->format('d/m/Y H:i') ?: '—' }}</p>
                                <p class="mt-1 text-xs text-slate-500">Xin đến: {{ $latestExtension?->requested_until?->format('d/m/Y') ?: '—' }}</p>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('scientific-research.registrations.show', $item) }}" class="sr-action border px-3 py-2 text-slate-700"><i class="bi bi-eye"></i> Xem</a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-sm text-slate-500">Chưa có đề tài nào xin gia hạn.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="border-t border-slate-100 px-5 py-3">{{ $extensionOverview->links() }}</div>
    </div>

    <div class="grid gap-4">
        @forelse($registrations as $registration)
            <article class="rounded-lg border border-slate-200 bg-white p-4">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div class="min-w-0">
                        <p class="text-xs font-bold uppercase text-slate-500">{{ $registration->project_code ?: 'Chưa cấp mã' }} · {{ $registration->researchCategory?->code }}</p>
                        <h3 class="mt-1 text-lg font-extrabold text-slate-900">{{ $registration->title }}</h3>
                        <p class="mt-1 text-sm text-slate-500">Chủ nhiệm: <strong>{{ $registration->user?->name ?: '—' }}</strong> · Thời gian: {{ $registration->start_date?->format('d/m/Y') ?: '—' }} → {{ $registration->end_date?->format('d/m/Y') ?: '—' }}</p>
                    </div>
                    <span class="sr-badge bg-blue-50 text-blue-700">{{ $statuses[$registration->status] ?? $registration->status }}</span>
                </div>

                @if($registration->unit_review_note || $registration->review_note)
                    <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-3">
                        <p class="text-sm font-bold text-amber-900">Yêu cầu / ghi chú thẩm định</p>
                        @if($registration->unit_review_note)
                            <p class="mt-1 whitespace-pre-line text-sm text-amber-800">Chỉ huy đơn vị: {{ $registration->unit_review_note }}</p>
                        @endif
                        @if($registration->review_note)
                            <p class="mt-1 whitespace-pre-line text-sm text-amber-800">Ban Khoa học Quân sự: {{ $registration->review_note }}</p>
                        @endif
                    </div>
                @endif

                <div class="mt-4 grid gap-4 lg:grid-cols-2">
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-4">
                        <p class="font-bold text-amber-900">Bổ sung hồ sơ theo yêu cầu thẩm định</p>
                        @if($registration->status === 'NEEDS_REVISION')
                            <form method="POST" action="{{ route('scientific-research.registrations.revision.submit', $registration) }}" class="mt-2">
                                @csrf
                                <p class="text-sm text-amber-800">Sau khi đã chỉnh thông tin đề tài, bổ sung thuyết minh/file đính kèm hoặc nội dung được yêu cầu, nhập phần đã bổ sung để gửi lại thẩm định.</p>
                                <textarea name="revision_response_note" rows="4" required placeholder="VD: Đã bổ sung thuyết minh, chỉnh thời gian thực hiện, cập nhật file đăng ký..." class="mt-3 w-full rounded-lg border px-3 py-2.5">{{ old('revision_response_note') }}</textarea>
                                <div class="mt-3 flex flex-wrap gap-2">
                                    <a href="{{ route('scientific-research.registrations.show', $registration) }}" class="sr-action border px-4 py-2.5 text-slate-700"><i class="bi bi-pencil-square"></i> Mở hồ sơ để chỉnh</a>
                                    <button class="sr-action bg-amber-600 px-4 py-2.5 text-white"><i class="bi bi-send-check"></i> Gửi bổ sung</button>
                                </div>
                            </form>
                        @else
                            <p class="mt-1 text-sm text-amber-800">Hiện hồ sơ chưa có yêu cầu bổ sung/chỉnh sửa từ đơn vị thẩm định.</p>
                            <a href="{{ route('scientific-research.registrations.show', $registration) }}" class="sr-action mt-3 inline-flex border px-4 py-2.5 text-slate-700"><i class="bi bi-eye"></i> Xem hồ sơ</a>
                        @endif
                    </div>

                    @if(in_array($registration->status, ['APPROVED', 'IN_PROGRESS'], true))
                        <form method="POST" action="{{ route('scientific-research.registrations.extension.request', $registration) }}" class="rounded-lg border border-indigo-200 bg-indigo-50 p-4">
                            @csrf
                            <p class="font-bold text-indigo-900">Xin gia hạn đề tài</p>
                            <p class="mt-1 text-sm text-indigo-800">Dành cho đề tài đã được duyệt hoặc đang thực hiện nhưng cần thêm thời gian.</p>
                            <label class="mt-3 block text-sm font-semibold text-indigo-900">Xin gia hạn đến
                                <input name="extension_requested_until" type="date" required value="{{ old('extension_requested_until', $registration->extended_until?->format('Y-m-d') ?: $registration->end_date?->copy()->addMonth()->format('Y-m-d')) }}" class="mt-1 w-full rounded-lg border px-3 py-2.5">
                            </label>
                            <textarea name="extension_request_note" rows="4" required placeholder="Nêu lý do xin gia hạn, khối lượng đã thực hiện, khó khăn và thời hạn đề nghị..." class="mt-3 w-full rounded-lg border px-3 py-2.5">{{ old('extension_request_note') }}</textarea>
                            <button class="sr-action mt-3 bg-indigo-600 px-4 py-2.5 text-white"><i class="bi bi-clock-history"></i> Gửi xin gia hạn</button>
                        </form>
                    @elseif($registration->status === 'EXTENSION_REQUESTED')
                        <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4">
                            <p class="font-bold text-indigo-900">Đang chờ duyệt gia hạn</p>
                            <p class="mt-1 text-sm text-indigo-800">Ngày đề nghị gia hạn: <strong>{{ $registration->extension_requested_until?->format('d/m/Y') ?: '—' }}</strong></p>
                            <p class="mt-2 whitespace-pre-line text-sm text-indigo-800">{{ $registration->extension_request_note ?: 'Chưa nhập lý do.' }}</p>
                        </div>
                    @else
                        <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4">
                            <p class="font-bold text-indigo-900">Xin gia hạn đề tài</p>
                            <p class="mt-1 text-sm text-indigo-800">Chỉ mở khi đề tài đã được duyệt hoặc đang thực hiện.</p>
                        </div>
                    @endif
                </div>

                @if($registration->extensionRequests->isNotEmpty())
                    <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4" data-extension-history>
                        <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p class="font-extrabold text-slate-900">Lịch sử xin gia hạn</p>
                                <p class="text-sm text-slate-500">Mỗi lần xin gia hạn được lưu thành một dòng riêng để tiện theo dõi.</p>
                            </div>
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-slate-600">{{ $registration->extensionRequests->count() }} lần</span>
                        </div>
                        <div class="mb-3 grid gap-2 md:grid-cols-[minmax(220px,1fr)_220px_160px]">
                            <input data-extension-filter-text placeholder="Lọc lý do, người gửi, người duyệt" class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm">
                            <select data-extension-filter-status class="rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm">
                                <option value="">Tất cả trạng thái</option>
                                <option value="PENDING">Chờ duyệt</option>
                                <option value="APPROVED">Đã duyệt</option>
                                <option value="REJECTED">Từ chối</option>
                            </select>
                            <div class="flex items-center rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-500">
                                <span data-extension-filter-count>{{ $registration->extensionRequests->count() }}</span>&nbsp;dòng hiển thị
                            </div>
                        </div>
                        <div class="overflow-x-auto rounded-lg border border-slate-200 bg-white">
                            <table class="min-w-[1280px] table-fixed text-sm">
                                <colgroup>
                                    <col class="w-[135px]">
                                    <col class="w-[150px]">
                                    <col class="w-[165px]">
                                    <col class="w-[280px]">
                                    <col class="w-[155px]">
                                    <col class="w-[250px]">
                                    <col class="w-[150px]">
                                    <col class="w-[110px]">
                                </colgroup>
                                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                                    <tr>
                                        <th class="whitespace-nowrap px-3 py-3">Ngày gửi</th>
                                        <th class="whitespace-nowrap px-3 py-3">Người gửi</th>
                                        <th class="whitespace-nowrap px-3 py-3">Gia hạn đến</th>
                                        <th class="whitespace-nowrap px-3 py-3">Lý do</th>
                                        <th class="whitespace-nowrap px-3 py-3">Trạng thái</th>
                                        <th class="whitespace-nowrap px-3 py-3">Ý kiến duyệt</th>
                                        <th class="whitespace-nowrap px-3 py-3">Người duyệt</th>
                                        <th class="whitespace-nowrap px-3 py-3 text-right">Thao tác</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($registration->extensionRequests as $extension)
                                        @php
                                            $canReviewExtension = \App\Support\PermissionCheck::can(auth()->user(), 'scientific-research.registrations.agency-approve') || \App\Support\PermissionCheck::can(auth()->user(), 'scientific-research.registrations.status');
                                            $extensionFormId = 'extension-update-'.$extension->id;
                                        @endphp
                                        <tr data-extension-row data-status="{{ $extension->status }}" data-search="{{ \Illuminate\Support\Str::lower(($extension->request_note ?: '').' '.($extension->review_note ?: '').' '.($extension->requester?->name ?: '').' '.($extension->reviewer?->name ?: '')) }}" class="align-top hover:bg-slate-50/80">
                                            <td class="whitespace-nowrap px-3 py-3 text-slate-600">{{ $extension->requested_at?->format('d/m/Y H:i') ?: '—' }}</td>
                                            <td class="break-words px-3 py-3 font-semibold text-slate-800">{{ $extension->requester?->name ?: '—' }}</td>
                                            <td class="px-3 py-3">
                                                <form id="{{ $extensionFormId }}" method="POST" action="{{ route('scientific-research.registrations.extension.update', $extension) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                </form>
                                                <input form="{{ $extensionFormId }}" name="requested_until" type="date" required value="{{ old('requested_until', $extension->requested_until?->format('Y-m-d')) }}" class="w-full min-w-[145px] rounded-lg border px-3 py-2">
                                            </td>
                                            <td class="px-3 py-3">
                                                <textarea form="{{ $extensionFormId }}" name="request_note" rows="2" required class="w-full min-w-[250px] rounded-lg border px-3 py-2">{{ old('request_note', $extension->request_note) }}</textarea>
                                            </td>
                                            <td class="px-3 py-3">
                                                @if($canReviewExtension)
                                                    <select form="{{ $extensionFormId }}" name="status" class="w-full min-w-[135px] rounded-lg border px-3 py-2">
                                                        <option value="PENDING" @selected($extension->status === 'PENDING')>Chờ duyệt</option>
                                                        <option value="APPROVED" @selected($extension->status === 'APPROVED')>Đã duyệt</option>
                                                        <option value="REJECTED" @selected($extension->status === 'REJECTED')>Từ chối</option>
                                                    </select>
                                                @else
                                                    <span class="inline-flex rounded-full bg-indigo-50 px-3 py-1 text-xs font-bold text-indigo-700">{{ $extension->status_label }}</span>
                                                @endif
                                            </td>
                                            <td class="px-3 py-3">
                                                @if($canReviewExtension)
                                                    <textarea form="{{ $extensionFormId }}" name="review_note" rows="2" class="w-full min-w-[220px] rounded-lg border px-3 py-2">{{ old('review_note', $extension->review_note) }}</textarea>
                                                @else
                                                    <p class="w-full min-w-[220px] whitespace-pre-line text-slate-600">{{ $extension->review_note ?: '—' }}</p>
                                                @endif
                                            </td>
                                            <td class="break-words px-3 py-3 text-slate-600">{{ $extension->reviewer?->name ?: '—' }}</td>
                                            <td class="px-3 py-3">
                                                <div class="flex justify-end gap-2">
                                                    <button form="{{ $extensionFormId }}" class="sr-action bg-blue-600 px-3 py-2 text-white" title="Lưu"><i class="bi bi-save"></i></button>
                                                    <form method="POST" action="{{ route('scientific-research.registrations.extension.destroy', $extension) }}" onsubmit="return confirm('Xóa dòng gia hạn này?')">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button class="sr-action border border-rose-200 px-3 py-2 text-rose-700" title="Xóa"><i class="bi bi-trash"></i></button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </article>
        @empty
            <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">Không có hồ sơ cần bổ sung hoặc xin gia hạn.</div>
        @endforelse
    </div>

    <div class="mt-4">{{ $registrations->links() }}</div>
</section>
</div>
</div>
<script>
document.querySelectorAll('[data-extension-history]').forEach((box) => {
    const textInput = box.querySelector('[data-extension-filter-text]');
    const statusInput = box.querySelector('[data-extension-filter-status]');
    const count = box.querySelector('[data-extension-filter-count]');
    const rows = Array.from(box.querySelectorAll('[data-extension-row]'));
    const apply = () => {
        const keyword = (textInput?.value || '').trim().toLowerCase();
        const status = statusInput?.value || '';
        let visible = 0;
        rows.forEach((row) => {
            const matchText = !keyword || (row.dataset.search || '').includes(keyword);
            const matchStatus = !status || row.dataset.status === status;
            const show = matchText && matchStatus;
            row.classList.toggle('hidden', !show);
            if (show) visible += 1;
        });
        if (count) count.textContent = visible;
    };
    textInput?.addEventListener('input', apply);
    statusInput?.addEventListener('change', apply);
});
</script>
@endsection
