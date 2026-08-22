@extends('layouts.lms-learner')

@section('title', 'Hoạt động ngoài HĐCM')

@section('content')
@php
    $statusColors = [
        'draft' => 'bg-slate-100 text-slate-700',
        'submitted' => 'bg-amber-50 text-amber-700',
        'approved' => 'bg-teal-50 text-teal-700',
        'rejected' => 'bg-rose-50 text-rose-700',
    ];
@endphp

<div class="max-w-6xl mx-auto space-y-6">
    <div class="lms-card rounded-2xl overflow-hidden">
        <div class="bg-gradient-to-r from-teal-600 to-teal-500 px-6 py-5 text-white flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="text-lg font-bold"><i class="bi bi-activity mr-2"></i>Hoạt động ngoài HĐCM</h1>
                <p class="mt-1 text-sm text-teal-50">Theo dõi các hoạt động ngoài danh mục HĐ chuyên môn và NCKH — không cộng vào giờ chuẩn.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('lms.teach.standard-hours.hub') }}" class="px-4 py-2.5 rounded-xl border border-white/30 text-white hover:bg-white/10 text-sm font-medium transition-colors">
                    <i class="bi bi-arrow-left"></i> Về trang Giờ chuẩn
                </a>
                <a href="{{ route('lms.teach.standard-hours.external-activities.create') }}" class="px-4 py-2.5 rounded-xl bg-white text-teal-700 hover:bg-teal-50 text-sm font-medium transition-colors">
                    <i class="bi bi-plus-circle"></i> Thêm kê khai
                </a>
            </div>
        </div>
    </div>

    <div class="rounded-xl border border-amber-200 bg-amber-50 text-amber-900 px-4 py-3 text-sm">
        <i class="bi bi-info-circle mr-1"></i>
        Hoạt động ngoài HĐCM được theo dõi riêng và không tự động cộng vào kết quả giờ chuẩn hằng năm.
    </div>

    <form method="GET" action="{{ route('lms.teach.standard-hours.external-activities.index') }}" class="lms-card rounded-2xl p-5">
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
            <input type="text" name="search" data-live-search="1" value="{{ request('search') }}" placeholder="Tìm hoạt động, đơn vị tổ chức..."
                   class="border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none">
            <select name="activity_type" class="border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none">
                <option value="">Tất cả nhóm hoạt động</option>
                @foreach($activityTypes as $value => $label)
                    <option value="{{ $value }}" {{ (string) request('activity_type') === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="status" class="border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none">
                <option value="">Tất cả trạng thái</option>
                @foreach($statuses as $value => $label)
                    <option value="{{ $value }}" {{ (string) request('status') === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <select name="year" class="border border-slate-200 rounded-xl text-sm px-3 py-2.5 focus:border-teal-400 focus:ring-1 focus:ring-teal-400 outline-none">
                <option value="">Tất cả năm</option>
                @foreach($years as $value => $label)
                    <option value="{{ $value }}" {{ (string) request('year') === (string) $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="mt-3 flex gap-2">
            <button type="submit" class="px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium transition-colors">Lọc</button>
            <a href="{{ route('lms.teach.standard-hours.external-activities.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-medium transition-colors">Xóa lọc</a>
        </div>
    </form>

    <div class="lms-card rounded-2xl overflow-hidden">
        @if($records->isEmpty())
            <div class="text-center py-12 px-4">
                <i class="bi bi-activity text-5xl text-slate-200 mb-4"></i>
                <p class="text-slate-500 mb-4">Chưa có hoạt động ngoài HĐCM.</p>
                <a href="{{ route('lms.teach.standard-hours.external-activities.create') }}" class="inline-flex px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium transition-colors">
                    <i class="bi bi-plus-circle"></i> Thêm kê khai đầu tiên
                </a>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-slate-50 text-slate-600 border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-3 text-left">Hoạt động</th>
                            <th class="px-4 py-3 text-left">Thời gian</th>
                            <th class="px-4 py-3 text-left">{{ $periodModeLabel }}</th>
                            <th class="px-4 py-3 text-left">Vai trò</th>
                            <th class="px-4 py-3 text-left">Trạng thái</th>
                            <th class="px-4 py-3 text-left">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($records as $record)
                        <tr class="hover:bg-slate-50">
                            <td class="px-4 py-3">
                                <div class="font-medium text-slate-900">{{ $record->activity_name }}</div>
                                <div class="mt-0.5 text-xs text-slate-500">{{ $record->activity_type_text }}</div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ $record->from_date?->format('d/m/Y') }}
                                @if($record->to_date && !$record->to_date->isSameDay($record->from_date))
                                    <span class="text-slate-400">→</span> {{ $record->to_date->format('d/m/Y') }}
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">{{ $record->period_label }}</td>
                            <td class="px-4 py-3">{{ $record->role_or_position ?: '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold {{ $statusColors[$record->status] ?? '' }}">{{ $record->status_text }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <a href="{{ route('lms.teach.standard-hours.external-activities.show', $record) }}" class="text-teal-600 hover:text-teal-800" title="Xem chi tiết"><i class="bi bi-eye"></i></a>
                                    @if($record->canBeEditedBy(auth()->user()))
                                        <a href="{{ route('lms.teach.standard-hours.external-activities.edit', $record) }}" class="text-amber-600 hover:text-amber-800" title="Chỉnh sửa"><i class="bi bi-pencil"></i></a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if($records->hasPages())
                <div class="px-4 py-3 border-t border-slate-100 flex justify-center">{{ $records->appends(request()->query())->links() }}</div>
            @endif
        @endif
    </div>
</div>
@include('partials.live-search')
@endsection
