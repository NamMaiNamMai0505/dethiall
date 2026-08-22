@extends('layouts.admin')

@section('title', 'Kê khai NCKH')
@section('page-title', 'Kê khai NCKH')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Giờ chuẩn GV', 'url' => route('standard-hours.hub')],
    ['title' => 'Kê khai NCKH']
]" />

@php
    $pageActions = [\Modules\StandardHours\Support\HubNavigation::backAction()];
    if (\App\Support\PermissionCheck::userCan('standard-hours.research-records.manage')) {
        $pageActions[] = [
            'url' => route('standard-hours.research-records.create'),
            'label' => 'Thêm kê khai',
            'icon' => 'plus',
            'color' => 'blue',
        ];
    }
@endphp

<x-page-header title="KÊ KHAI NGHIÊN CỨU KHOA HỌC" :actions="$pageActions" />

@if(!empty($isInstructorView))
    @include('standardhours::research-records._norm-card', [
        'norm' => $currentResearchNorm,
        'interactive' => false,
    ])
@endif

@include('standardhours::partials.assessment-sections', [
    'routeName' => 'standard-hours.research-records.index',
])

@php
    $researchFilters = [
        ['type' => 'search', 'name' => 'search', 'placeholder' => 'Tìm theo tên sản phẩm, GV...'],
        ['type' => 'select', 'name' => 'research_category_id', 'placeholder' => 'Tất cả danh mục', 'options' => $categories->pluck('name', 'id')->toArray()],
        ['type' => 'select', 'name' => 'year', 'placeholder' => 'Tất cả năm', 'options' => $years],
        ['type' => 'select', 'name' => 'status', 'placeholder' => 'Tất cả trạng thái', 'options' => $statuses],
    ];
    if (empty($isInstructorView)) {
        array_splice($researchFilters, 1, 0, [[
            'type' => 'instructor-select',
            'name' => 'instructor_id',
            'placeholder' => 'Tất cả giảng viên',
            'options' => $instructors,
        ]]);
    }
@endphp

<x-filter-form
    :action="route('standard-hours.research-records.index')"
    :clear-url="route('standard-hours.research-records.index')"
    :filters="$researchFilters">

@if($researchRecords->total() > 0)
    <div class="mb-4 text-sm text-gray-600">{{ $researchRecords->firstItem() }}-{{ $researchRecords->lastItem() }} / {{ $researchRecords->total() }}</div>
@endif
</x-filter-form>

<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    @if($researchRecords->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-100 text-slate-800 border-b border-slate-200">
                    <tr>
                        <th class="px-4 py-3 text-left">STT</th>
                        <th class="px-4 py-3 text-left">Giảng viên</th>
                        <th class="px-4 py-3 text-left">Sản phẩm</th>
                        <th class="px-4 py-3 text-left">Danh mục</th>
                        <th class="px-4 py-3 text-left">Nghiệm thu</th>
                        <th class="px-4 py-3 text-left">Vai trò / TV</th>
                        <th class="px-4 py-3 text-left">Giờ cá nhân</th>
                        <th class="px-4 py-3 text-left">Trạng thái</th>
                        <th class="px-4 py-3 text-left">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($researchRecords as $i => $record)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">{{ $researchRecords->firstItem() + $i }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $record->instructor?->name ?? '—' }}</div>
                            <div class="text-xs text-gray-500">{{ $record->instructor?->code ?? '' }}</div>
                        </td>
                        <td class="px-4 py-3">{{ $record->product_name }}</td>
                        <td class="px-4 py-3 text-sm">{{ $record->researchCategory?->name ?? '—' }}</td>
                        <td class="px-4 py-3">{{ $record->acceptance_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <div class="font-medium">{{ $record->participation_type_text }}</div>
                            <div class="text-xs text-gray-500">{{ $record->member_count }} người tham gia</div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="font-semibold {{ $record->has_hours_adjustment ? 'text-amber-700' : 'text-blue-700' }}">
                                {{ number_format((float) $record->converted_hours, 2) }}
                            </div>
                            @if($record->has_hours_adjustment)
                                <span class="text-xs text-amber-600" title="Khác kết quả hệ thống tính">Đã điều chỉnh</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            @php
                                $colors = ['draft' => 'bg-gray-100 text-gray-800', 'submitted' => 'bg-yellow-100 text-yellow-800', 'approved' => 'bg-green-100 text-green-800', 'rejected' => 'bg-red-100 text-red-800'];
                            @endphp
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs {{ $colors[$record->status] ?? '' }}">{{ $record->status_text }}</span>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex space-x-2">
                                @canPermission('standard-hours.research-records.view')
                                    <a href="{{ route('standard-hours.research-records.show', $record) }}"
                                       class="text-blue-600 hover:text-blue-800" title="Xem chi tiết">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                @endcanPermission
                                @if($record->canBeEditedBy(auth()->user()))
                                    @canPermission('standard-hours.research-records.manage')
                                        <a href="{{ route('standard-hours.research-records.edit', $record) }}"
                                           class="text-green-600 hover:text-green-800" title="Chỉnh sửa">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                    @endcanPermission
                                @endif
                                @if($record->isEditable())
                                    @canPermission('standard-hours.research-records.manage')
                                        <form method="POST" action="{{ route('standard-hours.research-records.destroy', $record) }}" class="inline"
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
        @if($researchRecords->hasPages())
        <div class="px-4 py-3 border-t flex justify-center">{{ $researchRecords->appends(request()->query())->links() }}</div>
        @endif
    @else
        <div class="text-center py-12">
            <i class="bi bi-journal-text text-6xl text-gray-300 mb-4"></i>
            <p class="text-gray-500 mb-4">Chưa có kê khai NCKH.</p>
            @can('standard-hours.research-records.manage')
                <a href="{{ route('standard-hours.research-records.create') }}" class="{{ \Modules\StandardHours\Support\ActionButton::classes('primary') }}">Tạo kê khai</a>
            @endcan
        </div>
    @endif
</div>
@endsection
