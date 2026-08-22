@extends('layouts.admin')

@section('title', 'Chi tiết hoạt động ngoài HĐCM')
@section('page-title', 'Chi tiết hoạt động ngoài HĐCM')

@section('content')
@php
    $statusColors = [
        'draft' => 'bg-slate-100 text-slate-700',
        'submitted' => 'bg-amber-100 text-amber-800',
        'approved' => 'bg-emerald-100 text-emerald-800',
        'rejected' => 'bg-rose-100 text-rose-800',
    ];
    $actions = [
        ['url' => route('standard-hours.external-activities.index'), 'label' => 'Danh sách', 'icon' => 'arrow-left', 'color' => 'gray'],
    ];
    if ($record->canBeEditedBy(auth()->user())) {
        array_unshift($actions, [
            'url' => route('standard-hours.external-activities.edit', $record),
            'label' => 'Chỉnh sửa',
            'icon' => 'pencil',
            'color' => 'blue',
        ]);
    }
@endphp

<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Giờ chuẩn GV', 'url' => route('standard-hours.hub')],
    ['title' => 'Hoạt động ngoài HĐCM', 'url' => route('standard-hours.external-activities.index')],
    ['title' => $record->activity_name],
]" />

<x-page-header title="CHI TIẾT HOẠT ĐỘNG NGOÀI HĐCM" :actions="$actions" />

<div class="grid gap-6 xl:grid-cols-[minmax(0,1fr)_22rem]">
    <div class="rounded-xl border bg-white shadow-sm">
        <div class="flex items-center justify-between gap-3 border-b px-5 py-4">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">{{ $record->activity_type_text }}</p>
                <h2 class="mt-1 text-lg font-bold text-slate-900">{{ $record->activity_name }}</h2>
            </div>
            <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $statusColors[$record->status] ?? '' }}">
                {{ $record->status_text }}
            </span>
        </div>

        <dl class="grid grid-cols-1 gap-x-8 gap-y-5 p-5 sm:grid-cols-2">
            <div>
                <dt class="text-xs font-semibold uppercase text-slate-500">Giảng viên</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $record->instructor?->name ?? '—' }}</dd>
                <dd class="text-sm text-slate-500">{{ $record->instructor?->code }} · {{ $record->instructor?->unit?->name }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-slate-500">Thời gian</dt>
                <dd class="mt-1 text-slate-900">
                    {{ $record->from_date?->format('d/m/Y') }}
                    @if($record->to_date)
                        – {{ $record->to_date->format('d/m/Y') }}
                    @endif
                </dd>
                <dd class="text-sm text-slate-500">{{ $record->period_label }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-slate-500">Vai trò / chức trách</dt>
                <dd class="mt-1 text-slate-900">{{ $record->role_or_position ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-slate-500">Đơn vị tổ chức</dt>
                <dd class="mt-1 text-slate-900">{{ $record->organizer ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-slate-500">Địa điểm</dt>
                <dd class="mt-1 text-slate-900">{{ $record->location ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs font-semibold uppercase text-slate-500">Minh chứng</dt>
                <dd class="mt-1">
                    @if($record->evidence_url)
                        <a href="{{ $record->evidence_url }}" target="_blank"
                           class="font-medium text-blue-700 hover:underline"><i class="bi bi-paperclip"></i> Mở minh chứng</a>
                    @else
                        <span class="text-slate-500">Chưa có</span>
                    @endif
                </dd>
            </div>
            @foreach([
                'Chi tiết hoạt động' => $record->activity_details,
                'Kết quả / sản phẩm' => $record->result,
                'Ghi chú' => $record->notes,
            ] as $label => $value)
                @if(filled($value))
                    <div class="sm:col-span-2">
                        <dt class="text-xs font-semibold uppercase text-slate-500">{{ $label }}</dt>
                        <dd class="mt-1 whitespace-pre-line leading-6 text-slate-800">{{ $value }}</dd>
                    </div>
                @endif
            @endforeach
            @if(filled($record->review_note))
                <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 sm:col-span-2">
                    <dt class="text-xs font-semibold uppercase text-amber-700">Ý kiến người duyệt</dt>
                    <dd class="mt-1 whitespace-pre-line text-amber-900">{{ $record->review_note }}</dd>
                </div>
            @endif
        </dl>
    </div>

    <aside class="space-y-4">
        <div class="rounded-xl border bg-white p-5 shadow-sm">
            <h3 class="font-semibold text-slate-900">Lịch sử xử lý</h3>
            <div class="mt-4 space-y-3 text-sm">
                <div><span class="text-slate-500">Người tạo:</span> <strong>{{ $record->creator?->name ?? '—' }}</strong></div>
                <div><span class="text-slate-500">Ngày tạo:</span> {{ $record->created_at?->format('d/m/Y H:i') }}</div>
                <div><span class="text-slate-500">Cập nhật:</span> {{ $record->updated_at?->format('d/m/Y H:i') }}</div>
                @if($record->approved_at)
                    <div><span class="text-slate-500">Người xử lý:</span> <strong>{{ $record->approver?->name ?? '—' }}</strong></div>
                    <div><span class="text-slate-500">Thời gian:</span> {{ $record->approved_at->format('d/m/Y H:i') }}</div>
                @endif
            </div>
        </div>

        @if($record->isEditable())
            @can('standard-hours.external-activities.manage')
                <form method="POST" action="{{ route('standard-hours.external-activities.submit', $record) }}"
                      data-confirm="Gửi hoạt động này để duyệt?"
                      class="rounded-xl border bg-white p-5 shadow-sm">
                    @csrf
                    @method('PATCH')
                    <button type="submit" class="w-full justify-center {{ \Modules\StandardHours\Support\ActionButton::classes('primary') }}">
                        <i class="bi bi-send"></i> Gửi duyệt
                    </button>
                </form>
            @endcan
        @endif

        @if($record->status === \Modules\StandardHours\Models\ExternalActivityRecord::STATUS_SUBMITTED
            && \App\Support\ApprovalAgency::canReviewProfessionalActivities(auth()->user()))
            @can('standard-hours.external-activities.approve')
                <div class="rounded-xl border border-blue-200 bg-blue-50 p-5 shadow-sm">
                    <h3 class="font-semibold text-blue-950">Xử lý kê khai</h3>
                    <form method="POST" action="{{ route('standard-hours.external-activities.approve', $record) }}"
                          class="mt-4 space-y-3" data-turbo="false">
                        @csrf
                        @method('PATCH')
                        <textarea name="review_note" rows="2" class="form-textarea w-full"
                                  placeholder="Ý kiến duyệt (không bắt buộc)"></textarea>
                        <button type="submit" class="w-full justify-center {{ \Modules\StandardHours\Support\ActionButton::classes('success') }}">
                            <i class="bi bi-check-circle"></i> Duyệt
                        </button>
                    </form>
                    <form method="POST" action="{{ route('standard-hours.external-activities.reject', $record) }}"
                          class="mt-4 space-y-3 border-t border-blue-200 pt-4" data-turbo="false">
                        @csrf
                        @method('PATCH')
                        <textarea name="review_note" rows="2" required class="form-textarea w-full"
                                  placeholder="Nêu lý do từ chối"></textarea>
                        <button type="submit" class="w-full justify-center {{ \Modules\StandardHours\Support\ActionButton::classes('danger') }}">
                            <i class="bi bi-x-circle"></i> Từ chối
                        </button>
                    </form>
                </div>
            @endcan
        @endif

        @if($record->isEditable())
            @can('standard-hours.external-activities.manage')
                <form method="POST" action="{{ route('standard-hours.external-activities.destroy', $record) }}"
                      data-confirm="Xóa kê khai hoạt động ngoài HĐCM này?"
                      data-confirm-danger="1">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full justify-center {{ \Modules\StandardHours\Support\ActionButton::classes('danger') }}">
                        <i class="bi bi-trash"></i> Xóa bản kê khai
                    </button>
                </form>
            @endcan
        @endif
    </aside>
</div>
@endsection
