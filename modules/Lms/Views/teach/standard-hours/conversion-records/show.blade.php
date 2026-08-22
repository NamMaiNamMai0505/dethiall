@extends('layouts.lms-learner')

@section('title', 'Chi tiết kê khai HĐ CM')

@section('content')
@php
    $statusColors = [
        'draft' => 'bg-slate-100 text-slate-700',
        'submitted' => 'bg-amber-50 text-amber-700',
        'approved' => 'bg-teal-50 text-teal-700',
        'rejected' => 'bg-rose-50 text-rose-700',
    ];
@endphp

<div class="max-w-3xl mx-auto space-y-6">
    <div class="lms-card rounded-2xl overflow-hidden">
        <div class="bg-gradient-to-r from-teal-600 to-teal-500 px-6 py-5 text-white flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-lg font-bold"><i class="bi bi-clipboard-check mr-2"></i>Chi tiết kê khai HĐ chuyên môn</h1>
            <div class="flex flex-wrap gap-2">
                @if($conversionRecord->canBeEditedBy(auth()->user()))
                    <a href="{{ route('lms.teach.standard-hours.conversion-records.edit', $conversionRecord) }}" class="px-4 py-2.5 rounded-xl bg-white text-teal-700 hover:bg-teal-50 text-sm font-medium transition-colors">
                        <i class="bi bi-pencil"></i> Chỉnh sửa
                    </a>
                @endif
                <a href="{{ route('lms.teach.standard-hours.conversion-records.index') }}" class="px-4 py-2.5 rounded-xl border border-white/30 text-white hover:bg-white/10 text-sm font-medium transition-colors">
                    <i class="bi bi-arrow-left"></i> Quay lại
                </a>
            </div>
        </div>
    </div>

    <div class="lms-card rounded-2xl">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
            <h3 class="font-semibold text-slate-900">Thông tin kê khai</h3>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $statusColors[$conversionRecord->status] ?? '' }}">
                {{ $conversionRecord->status_text }}
            </span>
        </div>
        <div class="p-5 grid grid-cols-1 gap-x-6 gap-y-4 md:grid-cols-2">
            <div>
                <label class="text-sm font-medium text-slate-500">Giảng viên</label>
                <div class="mt-1 font-medium text-slate-900">{{ $conversionRecord->instructor->name }}</div>
                <div class="text-sm text-slate-500">{{ $conversionRecord->instructor->code }}</div>
            </div>
            <div>
                <label class="text-sm font-medium text-slate-500">Đơn vị</label>
                <div class="mt-1 text-slate-900">{{ $conversionRecord->instructor->unit->name ?? '—' }}</div>
            </div>
            <div>
                <label class="text-sm font-medium text-slate-500">Chi tiết hoạt động</label>
                <div class="mt-1 font-medium text-slate-900">{{ $conversionRecord->activity_name }}</div>
            </div>
            <div>
                <label class="text-sm font-medium text-slate-500">Tên hoạt động chuyên môn</label>
                <div class="mt-1 text-slate-900">{{ $conversionRecord->conversionCategory->name }}</div>
                <div class="text-sm text-slate-500">{{ $conversionRecord->conversionCategory->code }}</div>
            </div>
            <div>
                <label class="text-sm font-medium text-slate-500">Ngày thực hiện</label>
                <div class="mt-1 text-slate-900">{{ $conversionRecord->activity_date->format('d/m/Y') }}</div>
            </div>
            <div>
                <label class="text-sm font-medium text-slate-500">{{ $conversionRecord->period_mode === 'academic_year' ? 'Năm học' : 'Năm' }}</label>
                <div class="mt-1 text-slate-900">{{ $conversionRecord->period_label }}</div>
            </div>
            <div>
                <label class="text-sm font-medium text-slate-500">Số lượng</label>
                <div class="mt-1 text-slate-900">
                    {{ number_format($conversionRecord->quantity, 2) }}
                    {{ $conversionRecord->conversionCategory->unit }}
                </div>
            </div>
            <div>
                <label class="text-sm font-medium text-slate-500">Hệ số quy đổi</label>
                <div class="mt-1 text-slate-900">
                    @if($conversionRecord->conversionCategory?->conversion_method === 'coefficient')
                        {{ number_format((float) $conversionRecord->conversionCategory->coefficient, 2) }}
                        <span class="text-sm text-slate-500">/ {{ $conversionRecord->conversionCategory->unit }}</span>
                    @else
                        {{ number_format((float) $conversionRecord->conversionCategory->fixed_hours, 2) }} giờ cố định
                    @endif
                </div>
            </div>
            <div>
                <label class="text-sm font-medium text-slate-500">Giờ chuẩn quy đổi</label>
                <div class="mt-1 text-lg font-semibold text-teal-700">
                    {{ number_format($conversionRecord->converted_hours, 2) }} giờ
                </div>
            </div>
            <div>
                <label class="text-sm font-medium text-slate-500">Điều kiện tính giờ</label>
                <div class="mt-1 flex flex-wrap gap-2">
                    @if($conversionRecord->is_external_invitation)
                        <span class="rounded-full bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700">Mời giảng ngoài — không cộng giờ chuẩn</span>
                    @elseif($conversionRecord->has_other_remuneration)
                        <span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">Có thù lao riêng — không tính vượt</span>
                    @else
                        <span class="rounded-full bg-teal-50 px-2.5 py-1 text-xs font-medium text-teal-700">Đủ điều kiện tính giờ và giờ vượt</span>
                    @endif
                </div>
            </div>
            @if($conversionRecord->notes)
            <div class="md:col-span-2">
                <label class="text-sm font-medium text-slate-500">Ghi chú</label>
                <div class="mt-1 whitespace-pre-line text-slate-700">{{ $conversionRecord->notes }}</div>
            </div>
            @endif
            <div class="md:col-span-2">
                <label class="text-sm font-medium text-slate-500">Minh chứng</label>
                <div class="mt-1">
                    @if($conversionRecord->evidence_url)
                        <a href="{{ $conversionRecord->evidence_url }}" target="_blank" class="inline-flex items-center gap-1 text-teal-600 hover:underline">
                            <i class="bi bi-file-earmark-text"></i> Xem minh chứng
                        </a>
                    @else
                        <span class="text-slate-500">Chưa có file đính kèm</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="lms-card rounded-2xl">
        <div class="px-5 py-4 border-b border-slate-200">
            <h3 class="font-semibold text-slate-900">Lịch sử xử lý</h3>
        </div>
        <div class="p-5 grid grid-cols-1 gap-x-6 gap-y-4 text-sm md:grid-cols-2">
            <div>
                <span class="text-slate-500">Người tạo</span>
                <div class="mt-1 font-medium text-slate-900">{{ $conversionRecord->creator->name ?? '—' }}</div>
            </div>
            <div>
                <span class="text-slate-500">Ngày tạo</span>
                <div class="mt-1 text-slate-900">{{ $conversionRecord->created_at->format('d/m/Y H:i') }}</div>
            </div>
            <div>
                <span class="text-slate-500">Cập nhật lần cuối</span>
                <div class="mt-1 text-slate-900">{{ $conversionRecord->updater->name ?? '—' }}</div>
            </div>
            <div>
                <span class="text-slate-500">Thời gian cập nhật</span>
                <div class="mt-1 text-slate-900">{{ $conversionRecord->updated_at->format('d/m/Y H:i') }}</div>
            </div>
            @if($conversionRecord->approved_at)
            <div>
                <span class="text-slate-500">Người thẩm định</span>
                <div class="mt-1 font-medium text-slate-900">{{ $conversionRecord->approver->name ?? '—' }}</div>
            </div>
            <div>
                <span class="text-slate-500">Thời gian thẩm định</span>
                <div class="mt-1 text-slate-900">{{ $conversionRecord->approved_at->format('d/m/Y H:i') }}</div>
            </div>
            @endif
        </div>
    </div>

    @if($conversionRecord->isEditable())
        <div class="lms-card rounded-2xl p-5">
            <form action="{{ route('lms.teach.standard-hours.conversion-records.submit', $conversionRecord) }}" method="POST"
                  data-confirm="Gửi kê khai này để thẩm định?" data-turbo="false">
                @csrf @method('PATCH')
                <button type="submit" class="px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium transition-colors">
                    <i class="bi bi-send"></i> Gửi thẩm định
                </button>
            </form>
        </div>
    @endif
</div>
@endsection
