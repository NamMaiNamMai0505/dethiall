@extends('layouts.admin')

@section('title', 'Chi tiết kê khai HĐ CM')
@section('page-title', 'Chi tiết kê khai HĐ CM')

@section('content')
@php
    $statusColors = [
        'draft' => 'bg-gray-100 text-gray-800',
        'submitted' => 'bg-yellow-100 text-yellow-800',
        'approved' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
    ];
    $headerActions = [
        ['url' => route('standard-hours.conversion-records.index'), 'label' => 'Quay lại', 'icon' => 'arrow-left', 'color' => 'gray'],
    ];
    if ($conversionRecord->canBeEditedBy(auth()->user())) {
        array_unshift($headerActions, [
            'url' => route('standard-hours.conversion-records.edit', $conversionRecord),
            'label' => $conversionRecord->status === \Modules\StandardHours\Models\ConversionRecord::STATUS_APPROVED ? 'Chỉnh sửa (đã thẩm định)' : 'Chỉnh sửa',
            'icon' => 'pencil',
            'color' => 'blue',
        ]);
    }
@endphp

<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Kê khai HĐ CM', 'url' => route('standard-hours.conversion-records.index')],
    ['title' => $conversionRecord->activity_name]
]" />

<x-page-header title="CHI TIẾT KÊ KHAI HĐ CHUYÊN MÔN" :actions="$headerActions" />

<div class="grid gap-6">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">Thông tin kê khai</h3>
            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium {{ $statusColors[$conversionRecord->status] ?? '' }}">
                {{ $conversionRecord->status_text }}
            </span>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-600">Giảng viên</label>
                    <div class="mt-1 font-medium">{{ $conversionRecord->instructor->name }}</div>
                    <div class="text-sm text-gray-500">{{ $conversionRecord->instructor->code }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Đơn vị</label>
                    <div class="mt-1">{{ $conversionRecord->instructor->unit->name ?? '—' }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Chi tiết hoạt động</label>
                    <div class="mt-1 font-medium">{{ $conversionRecord->activity_name }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Tên hoạt động chuyên môn</label>
                    <div class="mt-1">{{ $conversionRecord->conversionCategory->name }}</div>
                    <div class="text-sm text-gray-500">{{ $conversionRecord->conversionCategory->code }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Ngày thực hiện</label>
                    <div class="mt-1">{{ $conversionRecord->activity_date->format('d/m/Y') }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">{{ $conversionRecord->period_mode === 'academic_year' ? 'Năm học' : 'Năm' }}</label>
                    <div class="mt-1">{{ $conversionRecord->period_label }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Số lượng</label>
                    <div class="mt-1">
                        {{ number_format($conversionRecord->quantity, 2) }}
                        {{ $conversionRecord->conversionCategory->unit }}
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Hệ số quy đổi</label>
                    <div class="mt-1">
                        @if($conversionRecord->conversionCategory?->conversion_method === 'coefficient')
                            {{ number_format((float) $conversionRecord->conversionCategory->coefficient, 2) }}
                            <span class="text-sm text-gray-500">/ {{ $conversionRecord->conversionCategory->unit }}</span>
                        @else
                            {{ number_format((float) $conversionRecord->conversionCategory->fixed_hours, 2) }} giờ cố định
                        @endif
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Giờ chuẩn quy đổi</label>
                    <div class="mt-1 text-lg font-semibold text-blue-700">
                        {{ number_format($conversionRecord->converted_hours, 2) }} giờ
                    </div>
                    <div class="text-xs text-gray-500 mt-1">
                        @if($conversionRecord->conversionCategory?->conversion_method === 'coefficient')
                            {{ number_format($conversionRecord->quantity, 2) }}
                            × {{ number_format((float) $conversionRecord->conversionCategory->coefficient, 2) }}
                            = {{ number_format($conversionRecord->converted_hours, 2) }}
                        @else
                            {{ number_format($conversionRecord->quantity, 2) }}
                            × {{ number_format((float) $conversionRecord->conversionCategory->fixed_hours, 2) }}
                            = {{ number_format($conversionRecord->converted_hours, 2) }}
                        @endif
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Điều kiện tính giờ</label>
                    <div class="mt-1 flex flex-wrap gap-2">
                        @if($conversionRecord->is_external_invitation)
                            <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-800">Mời giảng ngoài — không cộng giờ chuẩn</span>
                        @elseif($conversionRecord->has_other_remuneration)
                            <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-medium text-amber-800">Có thù lao riêng — không tính vượt</span>
                        @else
                            <span class="rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-800">Đủ điều kiện tính giờ và giờ vượt</span>
                        @endif
                    </div>
                </div>
                @if($conversionRecord->notes)
                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-600">Ghi chú</label>
                    <div class="mt-1 text-gray-700 whitespace-pre-line">{{ $conversionRecord->notes }}</div>
                </div>
                @endif
                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-600">Minh chứng</label>
                    <div class="mt-1">
                        @if($conversionRecord->evidence_url)
                            <a href="{{ $conversionRecord->evidence_url }}" target="_blank"
                               class="text-blue-600 hover:underline inline-flex items-center gap-1">
                                <i class="bi bi-file-earmark-text"></i> Xem minh chứng
                            </a>
                        @else
                            <span class="text-gray-500">Chưa có file đính kèm</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">Lịch sử xử lý</h3>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div>
                    <span class="text-gray-600">Người tạo</span>
                    <div class="mt-1 font-medium">{{ $conversionRecord->creator->name ?? '—' }}</div>
                </div>
                <div>
                    <span class="text-gray-600">Ngày tạo</span>
                    <div class="mt-1">{{ $conversionRecord->created_at->format('d/m/Y H:i') }}</div>
                </div>
                <div>
                    <span class="text-gray-600">Cập nhật lần cuối</span>
                    <div class="mt-1">{{ $conversionRecord->updater->name ?? '—' }}</div>
                </div>
                <div>
                    <span class="text-gray-600">Thời gian cập nhật</span>
                    <div class="mt-1">{{ $conversionRecord->updated_at->format('d/m/Y H:i') }}</div>
                </div>
                @if($conversionRecord->approved_at)
                <div>
                    <span class="text-gray-600">Người thẩm định</span>
                    <div class="mt-1 font-medium">{{ $conversionRecord->approver->name ?? '—' }}</div>
                </div>
                <div>
                    <span class="text-gray-600">Thời gian thẩm định</span>
                    <div class="mt-1">{{ $conversionRecord->approved_at->format('d/m/Y H:i') }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    @if($conversionRecord->isEditable())
        @can('standard-hours.conversion-records.manage')
        <div class="bg-white rounded-lg shadow-sm p-4">
            <form action="{{ route('standard-hours.conversion-records.submit', $conversionRecord) }}" method="POST"
                  data-confirm="Gửi kê khai này để thẩm định?">
                @csrf @method('PATCH')
                <button type="submit" class="{{ \Modules\StandardHours\Support\ActionButton::classes('primary') }}">
                    <i class="bi bi-send"></i> Gửi thẩm định
                </button>
            </form>
        </div>
        @endcan
    @endif

    @if($conversionRecord->status === \Modules\StandardHours\Models\ConversionRecord::STATUS_SUBMITTED
        && \App\Support\ApprovalAgency::canReviewProfessionalActivities(auth()->user()))
        @can('standard-hours.conversion-records.approve')
        <div class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap gap-3">
            <form action="{{ route('standard-hours.conversion-records.approve', $conversionRecord) }}" method="POST"
                  data-confirm="Xác nhận hồ sơ này đạt thẩm định?">
                @csrf @method('PATCH')
                <button type="submit" class="{{ \Modules\StandardHours\Support\ActionButton::classes('success') }}">
                    <i class="bi bi-check-circle"></i> Xác nhận đã thẩm định
                </button>
            </form>
            <form action="{{ route('standard-hours.conversion-records.reject', $conversionRecord) }}" method="POST"
                  data-confirm="Chuyển hồ sơ về để người kê khai bổ sung?">
                @csrf @method('PATCH')
                <button type="submit" class="{{ \Modules\StandardHours\Support\ActionButton::classes('danger') }}">
                    <i class="bi bi-arrow-counterclockwise"></i> Yêu cầu bổ sung
                </button>
            </form>
        </div>
        @endcan
    @endif
</div>
@endsection
