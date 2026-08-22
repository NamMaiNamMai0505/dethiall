@extends('layouts.lms-learner')

@section('title', 'Chi tiết hoạt động ngoài HĐCM')

@section('content')
@php
    $statusColors = [
        'draft' => 'bg-slate-100 text-slate-700',
        'submitted' => 'bg-amber-50 text-amber-700',
        'approved' => 'bg-teal-50 text-teal-700',
        'rejected' => 'bg-rose-50 text-rose-700',
    ];
@endphp

<div class="max-w-4xl mx-auto space-y-6">
    <div class="lms-card rounded-2xl overflow-hidden">
        <div class="bg-gradient-to-r from-teal-600 to-teal-500 px-6 py-5 text-white flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-lg font-bold"><i class="bi bi-activity mr-2"></i>Chi tiết hoạt động ngoài HĐCM</h1>
            <div class="flex flex-wrap gap-2">
                @if($record->canBeEditedBy(auth()->user()))
                    <a href="{{ route('lms.teach.standard-hours.external-activities.edit', $record) }}" class="px-4 py-2.5 rounded-xl bg-white text-teal-700 hover:bg-teal-50 text-sm font-medium transition-colors">
                        <i class="bi bi-pencil"></i> Chỉnh sửa
                    </a>
                @endif
                <a href="{{ route('lms.teach.standard-hours.external-activities.index') }}" class="px-4 py-2.5 rounded-xl border border-white/30 text-white hover:bg-white/10 text-sm font-medium transition-colors">
                    <i class="bi bi-arrow-left"></i> Quay lại
                </a>
            </div>
        </div>
    </div>

    <div class="lms-card rounded-2xl">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-wide text-teal-600">{{ $record->activity_type_text }}</p>
                <h2 class="mt-1 text-lg font-bold text-slate-900">{{ $record->activity_name }}</h2>
            </div>
            <span class="shrink-0 rounded-full px-3 py-1 text-xs font-semibold {{ $statusColors[$record->status] ?? '' }}">
                {{ $record->status_text }}
            </span>
        </div>

        <dl class="grid grid-cols-1 gap-x-8 gap-y-5 p-5 sm:grid-cols-2">
            <div>
                <dt class="text-sm font-medium text-slate-500">Giảng viên</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $record->instructor?->name ?? '—' }}</dd>
                <dd class="text-sm text-slate-500">{{ $record->instructor?->code }} · {{ $record->instructor?->unit?->name }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500">Thời gian</dt>
                <dd class="mt-1 text-slate-900">
                    {{ $record->from_date?->format('d/m/Y') }}
                    @if($record->to_date)
                        – {{ $record->to_date->format('d/m/Y') }}
                    @endif
                </dd>
                <dd class="text-sm text-slate-500">{{ $record->period_label }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500">Vai trò / chức trách</dt>
                <dd class="mt-1 text-slate-900">{{ $record->role_or_position ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500">Đơn vị tổ chức</dt>
                <dd class="mt-1 text-slate-900">{{ $record->organizer ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500">Địa điểm</dt>
                <dd class="mt-1 text-slate-900">{{ $record->location ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-sm font-medium text-slate-500">Minh chứng</dt>
                <dd class="mt-1">
                    @if($record->evidence_url)
                        <a href="{{ $record->evidence_url }}" target="_blank"
                           class="font-medium text-teal-700 hover:underline"><i class="bi bi-paperclip"></i> Mở minh chứng</a>
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
                        <dt class="text-sm font-medium text-slate-500">{{ $label }}</dt>
                        <dd class="mt-1 whitespace-pre-line leading-6 text-slate-800">{{ $value }}</dd>
                    </div>
                @endif
            @endforeach
            @if(filled($record->review_note))
                <div class="sm:col-span-2 rounded-lg border border-amber-200 bg-amber-50 p-4">
                    <dt class="text-sm font-semibold text-amber-900">Ý kiến người duyệt</dt>
                    <dd class="mt-1 whitespace-pre-line text-amber-800">{{ $record->review_note }}</dd>
                </div>
            @endif
        </dl>
    </div>

    <div class="lms-card rounded-2xl">
        <div class="px-5 py-4 border-b border-slate-200">
            <h3 class="font-semibold text-slate-900">Lịch sử xử lý</h3>
        </div>
        <div class="p-5 grid grid-cols-1 gap-x-6 gap-y-4 text-sm md:grid-cols-2">
            <div>
                <span class="text-slate-500">Người tạo</span>
                <div class="mt-1 font-medium text-slate-900">{{ $record->creator?->name ?? '—' }}</div>
            </div>
            <div>
                <span class="text-slate-500">Ngày tạo</span>
                <div class="mt-1 text-slate-900">{{ $record->created_at?->format('d/m/Y H:i') }}</div>
            </div>
            <div>
                <span class="text-slate-500">Cập nhật lần cuối</span>
                <div class="mt-1 text-slate-900">{{ $record->updated_at?->format('d/m/Y H:i') }}</div>
            </div>
            @if($record->approved_at)
                <div>
                    <span class="text-slate-500">Người xử lý</span>
                    <div class="mt-1 font-medium text-slate-900">{{ $record->approver?->name ?? '—' }}</div>
                </div>
                <div>
                    <span class="text-slate-500">Thời gian xử lý</span>
                    <div class="mt-1 text-slate-900">{{ $record->approved_at->format('d/m/Y H:i') }}</div>
                </div>
            @endif
        </div>
    </div>

    @if($record->isEditable())
        <div class="lms-card rounded-2xl p-5">
            <form method="POST" action="{{ route('lms.teach.standard-hours.external-activities.submit', $record) }}"
                  data-confirm="Gửi hoạt động này để duyệt?" data-turbo="false">
                @csrf @method('PATCH')
                <button type="submit" class="px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium transition-colors">
                    <i class="bi bi-send"></i> Gửi duyệt
                </button>
            </form>
        </div>
    @endif

    @if($record->isEditable())
        <div class="lms-card rounded-2xl p-5">
            <form method="POST" action="{{ route('lms.teach.standard-hours.external-activities.destroy', $record) }}"
                  data-confirm="Xóa kê khai hoạt động ngoài HĐCM này?" data-confirm-danger="1">
                @csrf @method('DELETE')
                <button type="submit" class="px-4 py-2.5 rounded-xl border border-rose-200 text-rose-600 hover:bg-rose-50 text-sm font-medium transition-colors">
                    <i class="bi bi-trash"></i> Xóa bản kê khai
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
