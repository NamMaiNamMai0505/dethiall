@extends('layouts.admin')

@section('title', 'Hoạt động ngoài HĐCM')
@section('page-title', 'Hoạt động ngoài HĐCM')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Giờ chuẩn GV', 'url' => route('standard-hours.hub')],
    ['title' => 'Hoạt động ngoài HĐCM'],
]" />

<x-page-header
    title="KÊ KHAI HOẠT ĐỘNG NGOÀI HĐCM"
    subtitle="Theo dõi các hoạt động ngoài danh mục HĐ chuyên môn và NCKH"
    :actions="[
        \Modules\StandardHours\Support\HubNavigation::backAction(),
        ['url' => route('standard-hours.external-activities.create'), 'label' => 'Thêm kê khai', 'icon' => 'plus', 'color' => 'blue'],
    ]" />

@php
    $filters = [
        ['type' => 'search', 'name' => 'search', 'placeholder' => 'Tìm hoạt động, giảng viên, đơn vị tổ chức...'],
        ['type' => 'select', 'name' => 'activity_type', 'placeholder' => 'Tất cả nhóm hoạt động', 'options' => $activityTypes],
        ['type' => 'select', 'name' => 'year', 'placeholder' => 'Tất cả kỳ', 'options' => $years],
        ['type' => 'select', 'name' => 'status', 'placeholder' => 'Tất cả trạng thái', 'options' => $statuses],
        ['type' => 'date', 'name' => 'from_date', 'label' => 'Từ ngày'],
        ['type' => 'date', 'name' => 'to_date', 'label' => 'Đến ngày'],
    ];
    if (empty($isInstructorView)) {
        array_splice($filters, 1, 0, [[
            'type' => 'instructor-select',
            'name' => 'instructor_id',
            'placeholder' => 'Tất cả giảng viên',
            'options' => $instructors,
        ]]);
    }
    $statusColors = [
        'draft' => 'bg-slate-100 text-slate-700',
        'submitted' => 'bg-amber-100 text-amber-800',
        'approved' => 'bg-emerald-100 text-emerald-800',
        'rejected' => 'bg-rose-100 text-rose-800',
    ];
@endphp

<x-filter-form
    :action="route('standard-hours.external-activities.index')"
    :clear-url="route('standard-hours.external-activities.index')"
    :filters="$filters"
    :columns="4">
    @if($records->total() > 0)
        <p class="text-sm text-slate-600">
            Đang hiển thị {{ $records->firstItem() }}–{{ $records->lastItem() }} trên {{ $records->total() }} kê khai.
        </p>
    @endif
</x-filter-form>

<div class="overflow-hidden rounded-xl border bg-white shadow-sm">
    @if($records->isEmpty())
        <div class="px-6 py-14 text-center">
            <i class="bi bi-activity text-5xl text-slate-300"></i>
            <p class="mt-3 text-slate-500">Chưa có hoạt động ngoài HĐCM.</p>
            <a href="{{ route('standard-hours.external-activities.create') }}"
               class="mt-4 {{ \Modules\StandardHours\Support\ActionButton::classes('primary') }}">
                Thêm kê khai đầu tiên
            </a>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-sm">
                <thead class="bg-slate-100 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr>
                        <th class="px-4 py-3">Giảng viên</th>
                        <th class="px-4 py-3">Hoạt động</th>
                        <th class="px-4 py-3">Thời gian</th>
                        <th class="px-4 py-3">{{ $periodModeLabel }}</th>
                        <th class="px-4 py-3">Vai trò</th>
                        <th class="px-4 py-3">Trạng thái</th>
                        <th class="px-4 py-3 text-right">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($records as $record)
                        <tr class="hover:bg-blue-50/40">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900">{{ $record->instructor?->name ?? '—' }}</div>
                                <div class="text-xs text-slate-500">{{ $record->instructor?->code }}</div>
                            </td>
                            <td class="max-w-sm px-4 py-3">
                                <div class="font-medium text-slate-900">{{ $record->activity_name }}</div>
                                <div class="mt-0.5 text-xs text-slate-500">{{ $record->activity_type_text }}</div>
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                {{ $record->from_date?->format('d/m/Y') }}
                                @if($record->to_date && !$record->to_date->isSameDay($record->from_date))
                                    <span class="text-slate-400">→</span> {{ $record->to_date->format('d/m/Y') }}
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">{{ $record->period_label }}</td>
                            <td class="px-4 py-3">{{ $record->role_or_position ?: '—' }}</td>
                            <td class="px-4 py-3">
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusColors[$record->status] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ $record->status_text }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('standard-hours.external-activities.show', $record) }}"
                                       class="text-blue-700 hover:text-blue-900" title="Xem chi tiết"><i class="bi bi-eye"></i></a>
                                    @if($record->canBeEditedBy(auth()->user()))
                                        <a href="{{ route('standard-hours.external-activities.edit', $record) }}"
                                           class="text-emerald-700 hover:text-emerald-900" title="Chỉnh sửa"><i class="bi bi-pencil"></i></a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($records->hasPages())
            <div class="border-t bg-slate-50 px-4 py-3">{{ $records->links() }}</div>
        @endif
    @endif
</div>
@endsection
