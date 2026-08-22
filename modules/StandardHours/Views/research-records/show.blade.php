@extends('layouts.admin')

@section('title', 'Chi tiết kê khai NCKH')
@section('page-title', 'Chi tiết kê khai NCKH')

@section('content')
@php
    $statusColors = [
        'draft' => 'bg-gray-100 text-gray-800',
        'submitted' => 'bg-yellow-100 text-yellow-800',
        'approved' => 'bg-green-100 text-green-800',
        'rejected' => 'bg-red-100 text-red-800',
    ];
    $headerActions = [
        ['url' => route('standard-hours.research-records.index'), 'label' => 'Quay lại', 'icon' => 'arrow-left', 'color' => 'gray'],
    ];
    if ($researchRecord->canBeEditedBy(auth()->user())) {
        array_unshift($headerActions, [
            'url' => route('standard-hours.research-records.edit', $researchRecord),
            'label' => $researchRecord->status === \Modules\StandardHours\Models\ResearchRecord::STATUS_APPROVED ? 'Chỉnh sửa (đã thẩm định)' : 'Chỉnh sửa',
            'icon' => 'pencil',
            'color' => 'blue',
        ]);
    }
@endphp

<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Kê khai NCKH', 'url' => route('standard-hours.research-records.index')],
    ['title' => $researchRecord->product_name]
]" />

<x-page-header title="CHI TIẾT KÊ KHAI NCKH" :actions="$headerActions" />

<div class="grid gap-6">
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">Thông tin kê khai</h3>
            <span class="inline-flex px-2.5 py-1 rounded-full text-xs font-medium {{ $statusColors[$researchRecord->status] ?? '' }}">
                {{ $researchRecord->status_text }}
            </span>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-600">Giảng viên</label>
                    <div class="mt-1 font-medium">{{ $researchRecord->instructor->name }}</div>
                    <div class="text-sm text-gray-500">{{ $researchRecord->instructor->code }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Đơn vị</label>
                    <div class="mt-1">{{ $researchRecord->instructor->unit->name ?? '—' }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Tên sản phẩm</label>
                    <div class="mt-1 font-medium">{{ $researchRecord->product_name }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Danh mục NCKH</label>
                    <div class="mt-1">{{ $researchRecord->researchCategory->name }}</div>
                    <div class="text-sm text-gray-500">{{ $researchRecord->researchCategory->code }} — {{ number_format($researchRecord->researchCategory->research_hours, 0) }} giờ</div>
                </div>
                @if($researchRecord->role)
                <div>
                    <label class="text-sm font-medium text-gray-600">Vai trò</label>
                    <div class="mt-1">{{ $researchRecord->role }}</div>
                </div>
                @endif
                <div>
                    <label class="text-sm font-medium text-gray-600">Vai trò tham gia</label>
                    <div class="mt-1">{{ $researchRecord->participation_type_text }}</div>
                </div>
                @if($researchRecord->publication_date)
                <div>
                    <label class="text-sm font-medium text-gray-600">Ngày công bố</label>
                    <div class="mt-1">{{ $researchRecord->publication_date->format('d/m/Y') }}</div>
                </div>
                @endif
                <div>
                    <label class="text-sm font-medium text-gray-600">Ngày nghiệm thu</label>
                    <div class="mt-1">{{ $researchRecord->acceptance_date->format('d/m/Y') }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">{{ $researchRecord->period_mode === 'academic_year' ? 'Năm học' : 'Năm' }}</label>
                    <div class="mt-1">{{ $researchRecord->period_label }}</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Tổng số thành viên tham gia</label>
                    <div class="mt-1">{{ $researchRecord->member_count }} người</div>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-600">Số năm thực hiện</label>
                    <div class="mt-1">{{ $researchRecord->duration_years }} năm</div>
                </div>
                @if($researchRecord->publication_place)
                <div>
                    <label class="text-sm font-medium text-gray-600">Nơi xuất bản</label>
                    <div class="mt-1">{{ $researchRecord->publication_place }}</div>
                </div>
                @endif
                @if($researchRecord->contribution_percent !== null)
                <div>
                    <label class="text-sm font-medium text-gray-600">Tỷ lệ đóng góp của người kê khai</label>
                    <div class="mt-1">{{ number_format((float) $researchRecord->contribution_percent, 2) }}%</div>
                </div>
                @endif
                <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-3 rounded-xl border border-blue-100 bg-blue-50 p-4">
                    <div>
                        <label class="text-sm font-medium text-gray-600">Giờ sản phẩm mỗi năm</label>
                        <div class="mt-1 font-semibold text-gray-900">
                            {{ number_format((float) ($researchRecord->annual_product_hours ?? $researchRecord->converted_hours), 2) }} giờ
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Hệ thống tính cho cá nhân</label>
                        <div class="mt-1 font-semibold text-blue-700">
                            {{ number_format((float) ($researchRecord->calculated_hours ?? $researchRecord->converted_hours), 2) }} giờ
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-600">Số giờ kê khai chốt</label>
                        <div class="mt-1 text-lg font-bold {{ $researchRecord->has_hours_adjustment ? 'text-amber-700' : 'text-green-700' }}">
                            {{ number_format((float) $researchRecord->converted_hours, 2) }} giờ
                        </div>
                    </div>
                </div>
                @if($researchRecord->has_hours_adjustment)
                <div class="md:col-span-2 rounded-lg border border-amber-200 bg-amber-50 p-3">
                    <label class="text-sm font-semibold text-amber-900">Giải trình điều chỉnh giờ</label>
                    <div class="mt-1 whitespace-pre-line text-sm text-amber-800">{{ $researchRecord->hours_adjustment_note ?: 'Chưa nhập giải trình.' }}</div>
                </div>
                @endif
                @if($researchRecord->notes)
                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-600">Ghi chú</label>
                    <div class="mt-1 text-gray-700 whitespace-pre-line">{{ $researchRecord->notes }}</div>
                </div>
                @endif
                <div class="md:col-span-2">
                    <label class="text-sm font-medium text-gray-600">Minh chứng</label>
                    <div class="mt-1">
                        @if($researchRecord->evidence_url)
                            <a href="{{ $researchRecord->evidence_url }}" target="_blank"
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

    @if($researchRecord->members->count() > 1)
    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">Dữ liệu thành viên lịch sử ({{ $researchRecord->members->count() }})</h3>
            <p class="mt-1 text-xs text-gray-500">Bản ghi được tạo trước khi áp dụng cơ chế mỗi người tự kê khai phần giờ của mình.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-gray-700">
                    <tr>
                        <th class="px-4 py-2 text-left">Giảng viên</th>
                        <th class="px-4 py-2 text-left">Vai trò</th>
                        <th class="px-4 py-2 text-left">Loại tham gia</th>
                        <th class="px-4 py-2 text-left">Tỷ lệ (%)</th>
                        <th class="px-4 py-2 text-left">Giờ quy đổi</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @foreach($researchRecord->members as $member)
                    <tr>
                        <td class="px-4 py-2">
                            <div class="font-medium">{{ $member->instructor->name }}</div>
                            <div class="text-xs text-gray-500">{{ $member->instructor->code }}</div>
                            @if($member->is_declarant)<span class="text-xs text-blue-600">Người kê khai</span>@endif
                        </td>
                        <td class="px-4 py-2">{{ $member->role ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $member->participation_type === 'lead' ? 'Chủ nhiệm' : 'Thành viên' }}</td>
                        <td class="px-4 py-2">{{ $member->contribution_percent !== null ? number_format($member->contribution_percent, 0).'%' : '—' }}</td>
                        <td class="px-4 py-2 font-medium text-blue-700">{{ number_format($member->converted_hours, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="bg-white rounded-lg shadow-sm">
        <div class="px-4 py-3 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">Lịch sử xử lý</h3>
        </div>
        <div class="p-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div>
                    <span class="text-gray-600">Người tạo</span>
                    <div class="mt-1 font-medium">{{ $researchRecord->creator->name ?? '—' }}</div>
                </div>
                <div>
                    <span class="text-gray-600">Ngày tạo</span>
                    <div class="mt-1">{{ $researchRecord->created_at->format('d/m/Y H:i') }}</div>
                </div>
                <div>
                    <span class="text-gray-600">Cập nhật lần cuối</span>
                    <div class="mt-1">{{ $researchRecord->updater->name ?? '—' }}</div>
                </div>
                <div>
                    <span class="text-gray-600">Thời gian cập nhật</span>
                    <div class="mt-1">{{ $researchRecord->updated_at->format('d/m/Y H:i') }}</div>
                </div>
                @if($researchRecord->approved_at)
                <div>
                    <span class="text-gray-600">Người thẩm định</span>
                    <div class="mt-1 font-medium">{{ $researchRecord->approver->name ?? '—' }}</div>
                </div>
                <div>
                    <span class="text-gray-600">Thời gian thẩm định</span>
                    <div class="mt-1">{{ $researchRecord->approved_at->format('d/m/Y H:i') }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    @if($researchRecord->isEditable())
        @can('standard-hours.research-records.manage')
        <div class="bg-white rounded-lg shadow-sm p-4">
            <form action="{{ route('standard-hours.research-records.submit', $researchRecord) }}" method="POST"
                  data-confirm="Gửi kê khai này để thẩm định?">
                @csrf @method('PATCH')
                <button type="submit" class="{{ \Modules\StandardHours\Support\ActionButton::classes('primary') }}">
                    <i class="bi bi-send"></i> Gửi thẩm định
                </button>
            </form>
        </div>
        @endcan
    @endif

    @if($researchRecord->status === \Modules\StandardHours\Models\ResearchRecord::STATUS_SUBMITTED
        && \App\Support\ApprovalAgency::canReviewResearch(auth()->user()))
        @can('standard-hours.research-records.approve')
        <div class="bg-white rounded-lg shadow-sm p-4 flex flex-wrap gap-3">
            <form action="{{ route('standard-hours.research-records.approve', $researchRecord) }}" method="POST"
                  data-confirm="Xác nhận hồ sơ này đạt thẩm định?">
                @csrf @method('PATCH')
                <button type="submit" class="{{ \Modules\StandardHours\Support\ActionButton::classes('success') }}">
                    <i class="bi bi-check-circle"></i> Xác nhận đã thẩm định
                </button>
            </form>
            <form action="{{ route('standard-hours.research-records.reject', $researchRecord) }}" method="POST"
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
