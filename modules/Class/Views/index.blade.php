@extends('layouts.admin')

@section('title', 'Quản lý Lớp học')
@section('page-title', 'Quản lý Lớp học')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Lớp học']
]" />

{{-- Page Header --}}
<x-page-header
    title="DANH SÁCH LỚP HỌC"
    :actions="array_filter([
        auth()->user()->can('classes.create') ? [
            'url' => route('classes.create'),
            'label' => 'Tạo mới',
            'icon' => 'plus',
            'color' => 'blue'
        ] : null,
    ])" />

{{-- Filters --}}
<x-filter-form
    :action="route('classes.index')"
    :clear-url="route('classes.index')"
    :filters="[
        [
            'type' => 'search',
            'name' => 'search',
            'placeholder' => 'Tìm kiếm theo tên, mã...'
        ],
        [
            'type' => 'select',
            'name' => 'training_system_id',
            'placeholder' => 'Tất cả hệ (DS/QS)',
            'options' => $trainingSystems ?? []
        ],
        [
            'type' => 'select',
            'name' => 'specialization',
            'placeholder' => 'Tất cả ngành đào tạo',
            'depends_on' => 'training_system_id',
            'options' => $specializations->mapWithKeys(fn ($s) => [
                $s->id => $s->name.($s->trainingSystem ? ' — '.$s->trainingSystem->name : ''),
            ]),
        ],
        [
            'type' => 'select',
            'name' => 'instructor',
            'placeholder' => 'Tất cả Giảng viên phụ trách',
            'options' => $instructors->pluck('name', 'id')
        ],
        [
            'type' => 'select',
            'name' => 'classroom',
            'placeholder' => 'Tất cả giảng đường',
            'options' => $classrooms->pluck('name', 'id')
        ],
        [
            'type' => 'select',
            'name' => 'status',
            'placeholder' => 'Tất cả trạng thái',
            'options' => [
                '1' => 'Đang hoạt động',
                '0' => 'Tạm ngưng'
            ]
        ]
    ]">

{{-- Results Summary and Per Page Selector --}}
@if($classes->total() > 0)
    <div class="mb-4 flex justify-between items-center">
        <div class="text-sm text-gray-600">
            Hiển thị {{ $classes->firstItem() }} - {{ $classes->lastItem() }}
            trong tổng số {{ $classes->total() }} kết quả
        </div>

        <div class="flex items-center space-x-2">
            <label for="per_page" class="text-sm text-gray-600">Hiển thị:</label>
            <select name="per_page" id="per_page"
                class="text-sm border border-gray-300 rounded-md px-2 py-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                onchange="this.form.submit()">
                @foreach([5, 10, 15, 25, 50] as $option)
                    <option value="{{ $option }}" {{ request('per_page', 10) == $option ? 'selected' : '' }}>
                        {{ $option }}
                    </option>
                @endforeach
            </select>
            <span class="text-sm text-gray-600">/ trang</span>
        </div>
    </div>
@endif

</x-filter-form>

{{-- Table --}}
<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    @if($classes->count() > 0)
        <table class="w-full">
            <thead class="bg-slate-100 text-slate-800 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-left font-medium">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'code', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc']) }}"
                           class="inline-flex items-center gap-1 text-slate-800 hover:text-blue-700 font-semibold transition-colors">
                            Mã lớp
                            @if(request('sort_by') == 'code')
                                <i class="bi bi-arrow-{{ request('sort_order') == 'asc' ? 'up' : 'down' }} ml-1"></i>
                            @endif
                        </a>
                    </th>
                    <th class="px-4 py-3 text-left font-medium">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc']) }}"
                           class="inline-flex items-center gap-1 text-slate-800 hover:text-blue-700 font-semibold transition-colors">
                            Tên lớp
                            @if(request('sort_by') == 'name')
                                <i class="bi bi-arrow-{{ request('sort_order') == 'asc' ? 'up' : 'down' }} ml-1"></i>
                            @endif
                        </a>
                    </th>
                    <th class="px-4 py-3 text-left font-medium">Ngành đào tạo</th>
                    <th class="px-4 py-3 text-left font-medium">Giảng viên phụ trách</th>
                    <th class="px-4 py-3 text-left font-medium">Giảng đường</th>
                    <th class="px-4 py-3 text-left font-medium">Thời gian</th>
                    <th class="px-4 py-3 text-left font-medium">Số lượng</th>
                    <th class="px-4 py-3 text-left font-medium">Trạng thái</th>
                    <th class="px-4 py-3 text-left font-medium">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'created_at', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc']) }}"
                           class="inline-flex items-center gap-1 text-slate-800 hover:text-blue-700 font-semibold transition-colors">
                            Ngày tạo
                            @if(request('sort_by') == 'created_at' || !request('sort_by'))
                                <i class="bi bi-arrow-{{ (request('sort_order') == 'asc' || !request('sort_order')) ? 'down' : 'up' }} ml-1"></i>
                            @endif
                        </a>
                    </th>
                    <th class="px-4 py-3 text-left font-medium">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @foreach($classes as $class)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <code class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm font-mono">{{ $class->code }}</code>
                    </td>
                    <td class="px-4 py-3">
                        <div>
                            <div class="font-medium text-gray-900">{{ $class->name }}</div>
                            @if($class->description)
                                <div class="text-sm text-gray-600">
                                    {{ Str::limit($class->description, 60) }}
                                </div>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3">{{ $class->specialization->name  ?? 'N/A'}}</td>
                    <td class="px-4 py-3">{{ $class->instructor->name ?? 'N/A'}}</td>
                    <td class="px-4 py-3">{{ $class->classroom->name ?? 'N/A' }}</td>
                    <td class="px-4 py-3">
                        <div class="text-sm">
                            <div>{{ $class->start_date->format('d/m/Y') }} -</div>
                            <div>{{ $class->end_date->format('d/m/Y') }}</div>
                            <div class="text-gray-500">({{ $class->duration_months }} tháng)</div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm">
                            <div>Hiện tại: <span class="font-semibold">{{ $class->current_students }}</span></div>
                            <div>Tối đa: <span class="font-semibold">{{ $class->max_students }}</span></div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <x-status-badge :is-active="$class->is_active" />
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm text-gray-600">
                            <div>{{ $class->created_at->format('d/m/Y') }}</div>
                            <div class="text-xs">{{ $class->creator->name ?? 'N/A' }}</div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <x-table.action-buttons
                            :item="$class"
                            :routes="[
                                'show' => 'classes.show',
                                'edit' => 'classes.edit',
                                'destroy' => 'classes.destroy'
                            ]" />
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Pagination --}}
        @if($classes->hasPages())
        <div class="bg-gray-50 px-4 py-3 border-t border-gray-200">
            <div class="flex justify-center">
                {{ $classes->appends(request()->query())->links() }}
            </div>
        </div>
        @endif
    @else
        <div class="text-center py-12">
            <i class="bi bi-search text-6xl text-gray-300 mb-4"></i>
            <h3 class="text-lg font-medium text-gray-900 mb-2">Không tìm thấy kết quả</h3>
            <p class="text-gray-500 mb-6">
                @if(request()->hasAny(['search', 'specialization', 'instructor', 'classroom', 'status']))
                    Không có lớp học nào phù hợp với tiêu chí tìm kiếm.
                @else
                    Chưa có lớp học nào được tạo.
                @endif
            </p>
            @if(request()->hasAny(['search', 'specialization', 'instructor', 'classroom', 'status']))
                <a href="{{ route('classes.index') }}"
                   class="text-blue-600 hover:text-blue-800 font-medium">
                    Xóa bộ lọc
                </a>
            @else
                <a href="{{ route('classes.create') }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                    <i class="bi bi-plus mr-2"></i>Tạo lớp học đầu tiên
                </a>
            @endif
        </div>
    @endif
</div>

@endsection

@push('scripts')
<script>
    // Auto submit form when filters change
    document.querySelectorAll('select[name]').forEach(function(select) {
        select.addEventListener('change', function() {
            this.form.submit();
        });
    });
</script>
@endpush
