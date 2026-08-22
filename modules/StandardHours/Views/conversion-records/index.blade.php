@extends('layouts.admin')

@section('title', 'Kê khai HĐ chuyên môn')
@section('page-title', 'Kê khai HĐ chuyên môn')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Giờ chuẩn GV', 'url' => route('standard-hours.hub')],
    ['title' => 'Kê khai HĐ chuyên môn']
]" />

<x-page-header title="KÊ KHAI HOẠT ĐỘNG CHUYÊN MÔN" :actions="[
    \Modules\StandardHours\Support\HubNavigation::backAction(),
    ['url' => route('standard-hours.conversion-records.create'), 'label' => 'Thêm kê khai', 'icon' => 'plus', 'color' => 'blue'],
]" />

@include('standardhours::partials.assessment-sections', [
    'routeName' => 'standard-hours.conversion-records.index',
])

@php
    $conversionFilters = [
        ['type' => 'search', 'name' => 'search', 'placeholder' => 'Tìm theo chi tiết hoạt động, GV...'],
        ['type' => 'select', 'name' => 'conversion_category_id', 'placeholder' => 'Tất cả hoạt động chuyên môn', 'options' => $categories->pluck('name', 'id')->toArray()],
        ['type' => 'select', 'name' => 'year', 'placeholder' => 'Tất cả năm', 'options' => $years],
        ['type' => 'select', 'name' => 'status', 'placeholder' => 'Tất cả trạng thái', 'options' => $statuses],
    ];
    if (empty($isInstructorView)) {
        array_splice($conversionFilters, 1, 0, [[
            'type' => 'instructor-select',
            'name' => 'instructor_id',
            'placeholder' => 'Tất cả giảng viên',
            'options' => $instructors,
        ]]);
    }
@endphp

<x-filter-form
    :action="route('standard-hours.conversion-records.index')"
    :clear-url="route('standard-hours.conversion-records.index')"
    :filters="$conversionFilters">

@if($conversionRecords->total() > 0)
    <div class="mb-4 text-sm text-gray-600">{{ $conversionRecords->firstItem() }}-{{ $conversionRecords->lastItem() }} / {{ $conversionRecords->total() }}</div>
@endif
</x-filter-form>

<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    @if($conversionRecords->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-100 text-slate-800 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left">STT</th>
                        <th class="px-4 py-3 text-left">Giảng viên</th>
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
                <tbody class="divide-y divide-gray-200">
                    @foreach($conversionRecords as $i => $record)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">{{ $conversionRecords->firstItem() + $i }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $record->instructor?->name ?? '—' }}</div>
                            <div class="text-xs text-gray-500">{{ $record->instructor?->code ?? '' }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $record->activity_name }}</td>
                        <td class="px-4 py-3 text-sm">
                            <div>{{ $record->conversionCategory?->name ?? '—' }}</div>
                            @if($record->conversionCategory)
                                <div class="text-xs text-gray-500">
                                    HS {{ number_format((float) ($record->conversionCategory->coefficient ?? $record->conversionCategory->fixed_hours ?? 0), 2) }}
                                    / {{ $record->conversionCategory->unit }}
                                </div>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $record->activity_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-3 whitespace-nowrap">{{ $record->period_label }}</td>
                        <td class="px-4 py-3">
                            {{ number_format((float) $record->quantity, 2) }}
                            <span class="text-xs text-gray-500">{{ $record->conversionCategory?->unit ?? '' }}</span>
                        </td>
                        <td class="px-4 py-3 font-medium text-blue-700">{{ number_format((float) $record->converted_hours, 2) }}</td>
                        <td class="px-4 py-3">
                            @php
                                $colors = ['draft' => 'bg-gray-100 text-gray-800', 'submitted' => 'bg-yellow-100 text-yellow-800', 'approved' => 'bg-green-100 text-green-800', 'rejected' => 'bg-red-100 text-red-800'];
                            @endphp
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs {{ $colors[$record->status] ?? '' }}">{{ $record->status_text }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex space-x-2">
                                @canPermission('standard-hours.conversion-records.view')
                                    <a href="{{ route('standard-hours.conversion-records.show', $record) }}"
                                       class="text-blue-600 hover:text-blue-800" title="Xem chi tiết">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                @endcanPermission
                                @if($record->canBeEditedBy(auth()->user()))
                                    @canPermission('standard-hours.conversion-records.manage')
                                        <a href="{{ route('standard-hours.conversion-records.edit', $record) }}"
                                           class="text-green-600 hover:text-green-800" title="Chỉnh sửa">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endcanPermission
                                @endif
                                @if($record->isEditable())
                                    @canPermission('standard-hours.conversion-records.manage')
                                        <form method="POST" action="{{ route('standard-hours.conversion-records.destroy', $record) }}" class="inline"
                                              data-confirm="Bạn có chắc chắn muốn xóa kê khai này?">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800" title="Xóa">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endcanPermission
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($conversionRecords->hasPages())
        <div class="px-4 py-3 border-t flex justify-center">{{ $conversionRecords->appends(request()->query())->links() }}</div>
        @endif
    @else
        <div class="text-center py-12">
            <i class="bi bi-clipboard-check text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 mb-4">Chưa có kê khai hoạt động chuyên môn.</p>
            <a href="{{ route('standard-hours.conversion-records.create') }}" class="{{ \Modules\StandardHours\Support\ActionButton::classes('primary') }}">Tạo kê khai</a>
        </div>
    @endif
</div>
@endsection
