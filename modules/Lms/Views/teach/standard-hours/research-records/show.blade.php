@extends('layouts.lms-learner')

@section('title', 'Chi tiết kê khai NCKH')

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
            <h1 class="text-lg font-bold"><i class="bi bi-mortarboard mr-2"></i>Chi tiết kê khai NCKH</h1>
            <div class="flex flex-wrap gap-2">
                @if($researchRecord->canBeEditedBy(auth()->user()))
                    <a href="{{ route('lms.teach.standard-hours.research-records.edit', $researchRecord) }}" class="px-4 py-2.5 rounded-xl bg-white text-teal-700 hover:bg-teal-50 text-sm font-medium transition-colors">
                        <i class="bi bi-pencil"></i> {{ $researchRecord->status === \Modules\StandardHours\Models\ResearchRecord::STATUS_APPROVED ? 'Chỉnh sửa (đã thẩm định)' : 'Chỉnh sửa' }}
                    </a>
                @endif
                <a href="{{ route('lms.teach.standard-hours.research-records.index') }}" class="px-4 py-2.5 rounded-xl border border-white/30 text-white hover:bg-white/10 text-sm font-medium transition-colors">
                    <i class="bi bi-arrow-left"></i> Quay lại
                </a>
            </div>
        </div>
    </div>

    <div class="lms-card rounded-2xl">
        <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
            <h3 class="font-semibold text-slate-900">Thông tin kê khai</h3>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $statusColors[$researchRecord->status] ?? '' }}">
                {{ $researchRecord->status_text }}
            </span>
        </div>
        <div class="p-5 grid grid-cols-1 gap-x-6 gap-y-4 md:grid-cols-2">
            <div>
                <label class="text-sm font-medium text-slate-500">Giảng viên</label>
                <div class="mt-1 font-medium text-slate-900">{{ $researchRecord->instructor->name }}</div>
                <div class="text-sm text-slate-500">{{ $researchRecord->instructor->code }}</div>
            </div>
            <div>
                <label class="text-sm font-medium text-slate-500">Đơn vị</label>
                <div class="mt-1 text-slate-900">{{ $researchRecord->instructor->unit->name ?? '—' }}</div>
            </div>
            <div>
                <label class="text-sm font-medium text-slate-500">Tên sản phẩm</label>
                <div class="mt-1 font-medium text-slate-900">{{ $researchRecord->product_name }}</div>
            </div>
            <div>
                <label class="text-sm font-medium text-slate-500">Danh mục NCKH</label>
                <div class="mt-1 text-slate-900">{{ $researchRecord->researchCategory->name }}</div>
                <div class="text-sm text-slate-500">{{ $researchRecord->researchCategory->code }} — {{ number_format($researchRecord->researchCategory->research_hours, 0) }} giờ</div>
            </div>
            @if($researchRecord->role)
            <div>
                <label class="text-sm font-medium text-slate-500">Vai trò</label>
                <div class="mt-1 text-slate-900">{{ $researchRecord->role }}</div>
            </div>
            @endif
            <div>
                <label class="text-sm font-medium text-slate-500">Vai trò tham gia</label>
                <div class="mt-1 text-slate-900">{{ $researchRecord->participation_type_text }}</div>
            </div>
            @if($researchRecord->publication_date)
            <div>
                <label class="text-sm font-medium text-slate-500">Ngày công bố</label>
                <div class="mt-1 text-slate-900">{{ $researchRecord->publication_date->format('d/m/Y') }}</div>
            </div>
            @endif
            <div>
                <label class="text-sm font-medium text-slate-500">Ngày nghiệm thu</label>
                <div class="mt-1 text-slate-900">{{ $researchRecord->acceptance_date->format('d/m/Y') }}</div>
            </div>
            <div>
                <label class="text-sm font-medium text-slate-500">{{ $researchRecord->period_mode === 'academic_year' ? 'Năm học' : 'Năm' }}</label>
                <div class="mt-1 text-slate-900">{{ $researchRecord->period_label }}</div>
            </div>
            <div>
                <label class="text-sm font-medium text-slate-500">Tổng số thành viên tham gia</label>
                <div class="mt-1 text-slate-900">{{ $researchRecord->member_count }} người</div>
            </div>
            <div>
                <label class="text-sm font-medium text-slate-500">Số năm thực hiện</label>
                <div class="mt-1 text-slate-900">{{ $researchRecord->duration_years }} năm</div>
            </div>
            @if($researchRecord->publication_place)
            <div>
                <label class="text-sm font-medium text-slate-500">Nơi xuất bản</label>
                <div class="mt-1 text-slate-900">{{ $researchRecord->publication_place }}</div>
            </div>
            @endif
            @if($researchRecord->contribution_percent !== null)
            <div>
                <label class="text-sm font-medium text-slate-500">Tỷ lệ đóng góp của người kê khai</label>
                <div class="mt-1 text-slate-900">{{ number_format((float) $researchRecord->contribution_percent, 2) }}%</div>
            </div>
            @endif
            <div class="md:col-span-2 grid grid-cols-1 sm:grid-cols-3 gap-3 rounded-xl border border-teal-100 bg-teal-50 p-4">
                <div>
                    <label class="text-sm font-medium text-slate-500">Giờ sản phẩm mỗi năm</label>
                    <div class="mt-1 font-semibold text-slate-900">
                        {{ number_format((float) ($researchRecord->annual_product_hours ?? $researchRecord->converted_hours), 2) }} giờ
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-500">Hệ thống tính cho cá nhân</label>
                    <div class="mt-1 font-semibold text-teal-700">
                        {{ number_format((float) ($researchRecord->calculated_hours ?? $researchRecord->converted_hours), 2) }} giờ
                    </div>
                </div>
                <div>
                    <label class="text-sm font-medium text-slate-500">Số giờ kê khai chốt</label>
                    <div class="mt-1 text-lg font-bold {{ $researchRecord->has_hours_adjustment ? 'text-amber-700' : 'text-teal-700' }}">
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
                <label class="text-sm font-medium text-slate-500">Ghi chú</label>
                <div class="mt-1 whitespace-pre-line text-slate-700">{{ $researchRecord->notes }}</div>
            </div>
            @endif
            <div class="md:col-span-2">
                <label class="text-sm font-medium text-slate-500">Minh chứng</label>
                <div class="mt-1">
                    @if($researchRecord->evidence_url)
                        <a href="{{ $researchRecord->evidence_url }}" target="_blank"
                           class="inline-flex items-center gap-1 text-teal-600 hover:underline">
                            <i class="bi bi-file-earmark-text"></i> Xem minh chứng
                        </a>
                    @else
                        <span class="text-slate-500">Chưa có file đính kèm</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if($researchRecord->members->count() > 1)
    <div class="lms-card rounded-2xl">
        <div class="px-5 py-4 border-b border-slate-200">
            <h3 class="font-semibold text-slate-900">Dữ liệu thành viên lịch sử ({{ $researchRecord->members->count() }})</h3>
            <p class="mt-1 text-xs text-slate-500">Bản ghi được tạo trước khi áp dụng cơ chế mỗi người tự kê khai phần giờ của mình.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-slate-50 text-slate-600">
                    <tr>
                        <th class="px-4 py-2 text-left">Giảng viên</th>
                        <th class="px-4 py-2 text-left">Vai trò</th>
                        <th class="px-4 py-2 text-left">Loại tham gia</th>
                        <th class="px-4 py-2 text-left">Tỷ lệ (%)</th>
                        <th class="px-4 py-2 text-left">Giờ quy đổi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($researchRecord->members as $member)
                    <tr>
                        <td class="px-4 py-2">
                            <div class="font-medium text-slate-900">{{ $member->instructor->name }}</div>
                            <div class="text-xs text-slate-500">{{ $member->instructor->code }}</div>
                            @if($member->is_declarant)<span class="text-xs text-teal-600">Người kê khai</span>@endif
                        </td>
                        <td class="px-4 py-2">{{ $member->role ?? '—' }}</td>
                        <td class="px-4 py-2">{{ $member->participation_type === 'lead' ? 'Chủ nhiệm' : 'Thành viên' }}</td>
                        <td class="px-4 py-2">{{ $member->contribution_percent !== null ? number_format($member->contribution_percent, 0).'%' : '—' }}</td>
                        <td class="px-4 py-2 font-medium text-teal-700">{{ number_format($member->converted_hours, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <div class="lms-card rounded-2xl">
        <div class="px-5 py-4 border-b border-slate-200">
            <h3 class="font-semibold text-slate-900">Lịch sử xử lý</h3>
        </div>
        <div class="p-5 grid grid-cols-1 gap-x-6 gap-y-4 text-sm md:grid-cols-2">
            <div>
                <span class="text-slate-500">Người tạo</span>
                <div class="mt-1 font-medium text-slate-900">{{ $researchRecord->creator->name ?? '—' }}</div>
            </div>
            <div>
                <span class="text-slate-500">Ngày tạo</span>
                <div class="mt-1 text-slate-900">{{ $researchRecord->created_at->format('d/m/Y H:i') }}</div>
            </div>
            <div>
                <span class="text-slate-500">Cập nhật lần cuối</span>
                <div class="mt-1 text-slate-900">{{ $researchRecord->updater->name ?? '—' }}</div>
            </div>
            <div>
                <span class="text-slate-500">Thời gian cập nhật</span>
                <div class="mt-1 text-slate-900">{{ $researchRecord->updated_at->format('d/m/Y H:i') }}</div>
            </div>
            @if($researchRecord->approved_at)
            <div>
                <span class="text-slate-500">Người thẩm định</span>
                <div class="mt-1 font-medium text-slate-900">{{ $researchRecord->approver->name ?? '—' }}</div>
            </div>
            <div>
                <span class="text-slate-500">Thời gian thẩm định</span>
                <div class="mt-1 text-slate-900">{{ $researchRecord->approved_at->format('d/m/Y H:i') }}</div>
            </div>
            @endif
        </div>
    </div>

    @if($researchRecord->isEditable())
        <div class="lms-card rounded-2xl p-5">
            <form action="{{ route('lms.teach.standard-hours.research-records.submit', $researchRecord) }}" method="POST"
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
