@extends('layouts.lms-learner')

@section('title', 'Kê khai HĐ chuyên môn')

@section('content')
@php
    $statusColors = [
        'draft' => 'bg-slate-100 text-slate-700',
        'submitted' => 'bg-amber-50 text-amber-700',
        'approved' => 'bg-teal-50 text-teal-700',
        'rejected' => 'bg-rose-50 text-rose-700',
    ];
    $currentStatus = request('status');
    $pendingCount = (int) ($assessmentCounts[\Modules\StandardHours\Models\ConversionRecord::STATUS_SUBMITTED] ?? 0);
    $assessedCount = (int) ($assessmentCounts[\Modules\StandardHours\Models\ConversionRecord::STATUS_APPROVED] ?? 0);
@endphp

<div class="max-w-6xl mx-auto space-y-6">
    <div class="lms-card rounded-2xl overflow-hidden">
        <div class="bg-gradient-to-r from-teal-600 to-teal-500 px-6 py-5 text-white flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-bold"><i class="bi bi-clipboard-check mr-2"></i>Kê khai hoạt động chuyên môn</h1>
                <p class="mt-1 text-sm text-teal-50">Kê khai và theo dõi hoạt động chuyên môn quy đổi giờ chuẩn (HĐCM).</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('lms.teach.standard-hours.hub') }}" class="px-4 py-2.5 rounded-xl border border-white/30 text-white hover:bg-white/10 text-sm font-medium transition-colors">
                    <i class="bi bi-arrow-left"></i> Về trang Giờ chuẩn
                </a>
                <a href="{{ route('lms.teach.standard-hours.conversion-records.create') }}" class="px-4 py-2.5 rounded-xl bg-white text-teal-700 hover:bg-teal-50 text-sm font-medium transition-colors">
                    <i class="bi bi-plus-circle"></i> Thêm kê khai
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-3 lg:grid-cols-3">
        <a href="{{ route('lms.teach.standard-hours.conversion-records.index') }}"
           class="rounded-2xl border px-4 py-3 flex items-center gap-3 transition {{ blank($currentStatus) ? 'border-slate-700 bg-slate-800 text-white shadow-lg' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-400' }}">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ blank($currentStatus) ? 'bg-white/20' : 'bg-slate-100' }}"><i class="bi bi-collection text-lg"></i></span>
            <span>
                <span class="block font-bold">Tất cả hồ sơ</span>
                <span class="block text-xs {{ blank($currentStatus) ? 'text-white/80' : 'text-slate-500' }}">Gồm nháp, cần bổ sung và đã gửi</span>
            </span>
        </a>
        <a href="{{ route('lms.teach.standard-hours.conversion-records.index', ['status' => 'submitted']) }}"
           class="rounded-2xl border px-4 py-3 flex items-center gap-3 transition {{ $currentStatus === 'submitted' ? 'border-amber-500 bg-amber-500 text-white shadow-lg' : 'border-amber-200 bg-amber-50 text-amber-900 hover:border-amber-400' }}">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $currentStatus === 'submitted' ? 'bg-white/20' : 'bg-white' }}"><i class="bi bi-hourglass-split text-lg"></i></span>
            <span class="flex-1">
                <span class="flex items-center justify-between gap-2">
                    <span class="font-bold">Chờ thẩm định</span>
                    <span class="inline-flex min-w-8 items-center justify-center rounded-full px-2 py-0.5 text-sm font-extrabold {{ $currentStatus === 'submitted' ? 'bg-white/20 text-white' : 'bg-white text-current' }}">{{ number_format($pendingCount) }}</span>
                </span>
                <span class="block text-xs {{ $currentStatus === 'submitted' ? 'text-white/80' : 'opacity-70' }}">Hồ sơ đã gửi, đang chờ xử lý</span>
            </span>
        </a>
        <a href="{{ route('lms.teach.standard-hours.conversion-records.index', ['status' => 'approved']) }}"
           class="rounded-2xl border px-4 py-3 flex items-center gap-3 transition {{ $currentStatus === 'approved' ? 'border-teal-600 bg-teal-600 text-white shadow-lg' : 'border-teal-200 bg-teal-50 text-teal-900 hover:border-teal-400' }}">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $currentStatus === 'approved' ? 'bg-white/20' : 'bg-white' }}"><i class="bi bi-patch-check text-lg"></i></span>
            <span class="flex-1">
                <span class="flex items-center justify-between gap-2">
                    <span class="font-bold">Đã thẩm định</span>
                    <span class="inline-flex min-w-8 items-center justify-center rounded-full px-2 py-0.5 text-sm font-extrabold {{ $currentStatus === 'approved' ? 'bg-white/20 text-white' : 'bg-white text-current' }}">{{ number_format($assessedCount) }}</span>
                </span>
                <span class="block text-xs {{ $currentStatus === 'approved' ? 'text-white/80' : 'opacity-70' }}">Hồ sơ đã xác nhận, chốt kết quả</span>
            </span>
        </a>
    </div>

    <form method="GET" action="{{ route('lms.teach.standard-hours.conversion-records.index') }}" class="lms-card rounded-2xl p-5">
        <input type="hidden" name="status" value="{{ $currentStatus }}">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-4">
            <input type="text" name="search" data-live-search="1" value="{{ request('search') }}" placeholder="Tìm theo chi tiết hoạt động..."
                   class="border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none">
            <select name="conversion_category_id" class="border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none">
                <option value="">Tất cả hoạt động chuyên môn</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}" {{ (string) request('conversion_category_id') === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                @endforeach
            </select>
            <select name="year" class="border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none">
                <option value="">Tất cả năm</option>
                @foreach($years as $value => $label)
                    <option value="{{ $value }}" {{ (string) request('year') === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <div class="flex gap-2">
                <button type="submit" class="flex-1 px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium transition-colors">Lọc</button>
                <a href="{{ route('lms.teach.standard-hours.conversion-records.index') }}" class="flex-1 text-center px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-medium transition-colors">Xóa lọc</a>
            </div>
        </div>
    </form>

    <div class="lms-card rounded-2xl overflow-hidden">
        @if($conversionRecords->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left">Chi tiết hoạt động</th>
                            <th class="px-4 py-3 text-left">Tên hoạt động chuyên môn</th>
                            <th class="px-4 py-3 text-left">Ngày</th>
                            <th class="px-4 py-3 text-left">{{ app(\Modules\StandardHours\Services\PeriodService::class)->modeLabel() }}</th>
                            <th class="px-4 py-3 text-left">SL</th>
                            <th class="px-4 py-3 text-left">Giờ QĐ</th>
                            <th class="px-4 py-3 text-left">Trạng thái</th>
                            <th class="px-4 py-3 text-left">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($conversionRecords as $record)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">{{ $record->activity_name }}</td>
                            <td class="px-4 py-3">
                                <div>{{ $record->conversionCategory?->name ?? '—' }}</div>
                                @if($record->conversionCategory)
                                    <div class="text-xs text-slate-400">
                                        HS {{ number_format((float) ($record->conversionCategory->coefficient ?? $record->conversionCategory->fixed_hours ?? 0), 2) }}
                                        / {{ $record->conversionCategory->unit }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $record->activity_date?->format('d/m/Y') ?? '—' }}</td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $record->period_label }}</td>
                            <td class="px-4 py-3">
                                {{ number_format((float) $record->quantity, 2) }}
                                <span class="text-xs text-slate-400">{{ $record->conversionCategory?->unit ?? '' }}</span>
                            </td>
                            <td class="px-4 py-3 font-medium text-teal-700">{{ number_format((float) $record->converted_hours, 2) }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $statusColors[$record->status] ?? '' }}">{{ $record->status_text }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('lms.teach.standard-hours.conversion-records.show', $record) }}" class="text-teal-600 hover:text-teal-800" title="Xem chi tiết"><i class="bi bi-eye"></i></a>
                                    @if($record->canBeEditedBy(auth()->user()))
                                        <a href="{{ route('lms.teach.standard-hours.conversion-records.edit', $record) }}" class="text-amber-600 hover:text-amber-800" title="Chỉnh sửa"><i class="bi bi-pencil"></i></a>
                                    @endif
                                    @if($record->isEditable())
                                        <form method="POST" action="{{ route('lms.teach.standard-hours.conversion-records.destroy', $record) }}" class="inline"
                                              data-confirm="Bạn có chắc chắn muốn xóa kê khai này?">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-rose-600 hover:text-rose-800" title="Xóa"><i class="bi bi-trash"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($conversionRecords->hasPages())
                <div class="px-4 py-3 border-t border-slate-100 flex justify-center">{{ $conversionRecords->appends(request()->query())->links() }}</div>
            @endif
        @else
            <div class="text-center py-12 px-4">
                <i class="bi bi-clipboard-check text-5xl text-slate-200 mb-4"></i>
                <p class="text-slate-500 mb-4">Chưa có kê khai hoạt động chuyên môn.</p>
                <a href="{{ route('lms.teach.standard-hours.conversion-records.create') }}" class="inline-flex px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium transition-colors">
                    <i class="bi bi-plus-circle"></i> Tạo kê khai
                </a>
            </div>
        @endif
    </div>
</div>
@include('partials.live-search')
@endsection
