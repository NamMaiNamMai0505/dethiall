@extends('layouts.admin')

@section('title', 'Danh sách ngành đào tạo')
@section('page-title', 'Danh sách ngành đào tạo')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Ngành đào tạo']
]" />

{{-- Page Header --}}
<x-page-header
    title="DANH SÁCH NGÀNH ĐÀO TẠO"
    :actions="array_filter([
        [
            'url' => route('training-systems.index'),
            'label' => 'Hệ đào tạo',
            'icon' => 'layers',
            'color' => 'teal'
        ],
        auth()->user()->can('specializations.create') ? [
            'url' => route('specializations.create'),
            'label' => 'Tạo mới',
            'icon' => 'plus',
            'color' => 'blue'
        ] : null,
    ])" />

{{-- Filters --}}
<x-filter-form
    :action="route('specializations.index')"
    :clear-url="route('specializations.index')"
    :filters="[
        [
            'type' => 'search',
            'name' => 'search',
            'placeholder' => 'Tìm kiếm tên hoặc mã ngành...'
        ],
        [
            'type' => 'select',
            'name' => 'training_system_id',
            'placeholder' => 'Tất cả hệ',
            'options' => $trainingSystems ?? []
        ],
        [
            'type' => 'select',
            'name' => 'training_form',
            'placeholder' => 'Tất cả hình thức',
            'options' => $trainingForms ?? []
        ],
        [
            'type' => 'select',
            'name' => 'level',
            'placeholder' => 'Tất cả trình độ',
            'options' => $levels
        ],
        [
            'type' => 'select',
            'name' => 'status',
            'placeholder' => 'Tất cả trạng thái',
            'options' => [
                'active' => 'Đang hoạt động',
                'inactive' => 'Tạm dừng'
            ]
        ]
    ]">

{{-- Results Summary and Per Page Selector --}}
@if($specializations->total() > 0)
    <div class="mb-4 flex justify-between items-center">
        <div class="text-sm text-gray-600">
            Hiển thị {{ $specializations->firstItem() }} - {{ $specializations->lastItem() }}
            trong tổng số {{ $specializations->total() }} kết quả
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
<div class="bg-white rounded-lg shadow-sm border overflow-x-auto">
    @if($specializations->count() > 0)
        <table class="w-full min-w-[1450px]">
            <thead class="bg-slate-100 text-slate-800 border-b border-slate-200">
                <tr>
                    {{-- Mã số là khóa chính của danh mục ngành → cột ngoài cùng bên trái --}}
                    <th class="px-4 py-3 text-left font-medium">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'code', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc']) }}"
                           class="inline-flex items-center gap-1 text-slate-800 hover:text-blue-700 font-semibold transition-colors">
                            Mã số
                            @if(request('sort_by') == 'code')
                                <i class="bi bi-arrow-{{ request('sort_order') == 'asc' ? 'up' : 'down' }} ml-1"></i>
                            @endif
                        </a>
                    </th>
                    <th class="px-4 py-3 text-left font-medium">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'major_code', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc']) }}"
                           class="inline-flex items-center gap-1 text-slate-800 hover:text-blue-700 font-semibold transition-colors">
                            Mã ngành
                            @if(request('sort_by') == 'major_code')
                                <i class="bi bi-arrow-{{ request('sort_order') == 'asc' ? 'up' : 'down' }} ml-1"></i>
                            @endif
                        </a>
                    </th>
                        <th class="px-4 py-3 text-left font-medium">
                            
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc']) }}"
                           class="inline-flex items-center gap-1 text-slate-800 hover:text-blue-700 font-semibold transition-colors">
                            Ngành đào tạo
                            @if(request('sort_by') == 'name')
                                <i class="bi bi-arrow-{{ request('sort_order') == 'asc' ? 'up' : 'down' }} ml-1"></i>
                            @endif
                        </a>
                        </th>
                    <th class="px-4 py-3 text-left font-medium">Hệ đào tạo</th>
                    <th class="px-4 py-3 text-left font-medium">Hình thức</th>
                    <th class="px-4 py-3 text-left font-medium">Trình độ</th>
                    <th class="px-4 py-3 text-left font-medium">Thời gian</th>
                    <th class="px-4 py-3 text-left font-medium">Loại chứng chỉ</th>
                    <th class="px-4 py-3 text-center font-medium">Tổng số môn học</th>
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
                @foreach($specializations as $specialization)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <code class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm font-mono font-semibold">{{ $specialization->code }}</code>
                    </td>
                    <td class="px-4 py-3">
                        <code class="bg-slate-100 text-slate-700 px-2 py-1 rounded text-sm font-mono">{{ $specialization->major_code ?? '—' }}</code>
                    </td>
                    <td class="px-4 py-3">
                        <div>
                            <div class="font-medium text-gray-900">{{ $specialization->name }}</div>
                            @if($specialization->description)
                                <div class="text-sm text-gray-600">
                                    {{ Str::limit($specialization->description, 60) }}
                                </div>
                            @endif
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-full bg-teal-50 px-2.5 py-1 text-sm font-semibold text-teal-700 ring-1 ring-inset ring-teal-200">
                            {{ $specialization->trainingSystem?->name ?? 'Chưa xác định' }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center rounded-full bg-violet-50 px-2.5 py-1 text-sm font-semibold text-violet-700 ring-1 ring-inset ring-violet-200">
                            {{ $specialization->training_form_text }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <x-level-badge :level="$specialization->level" :text="$specialization->level_text" />
                    </td>
                    <td class="px-4 py-3">
                        <span class="font-semibold text-blue-600">{{ $specialization->duration_months }}</span>
                        <span class="text-gray-500 text-sm">tháng</span>
                    </td>
                    <td class="px-4 py-3">
                        <x-certification-badge :type="$specialization->certification_type" :text="$specialization->certification_type_text" />
                    </td>
                    <td class="px-4 py-3 text-center">
                        <a href="{{ route('subjects.index', ['specialization_id' => $specialization->id]) }}"
                           target="_blank"
                           class="inline-flex items-center gap-1 px-3 py-1 bg-indigo-100 text-indigo-800 rounded-full hover:bg-indigo-200 transition-colors">
                            <span class="font-semibold">{{ $specialization->subjects_count }}</span>
                            <i class="bi bi-box-arrow-up-right text-xs"></i>
                        </a>
                    </td>
                    <td class="px-4 py-3">
                        <x-status-badge :is-active="$specialization->is_active" />
                    </td>
                    <td class="px-4 py-3">
                        <div class="text-sm text-gray-600">
                            <div>{{ $specialization->created_at->format('d/m/Y') }}</div>
                            <div class="text-xs">{{ $specialization->creator->name ?? 'N/A' }}</div>
                        </div>
                    </td>
                    <td class="px-4 py-3">
                        <x-table.action-buttons
                            :item="$specialization"
                            :routes="[
                                'show' => 'specializations.show',
                                'edit' => 'specializations.edit',
                                'toggle' => 'specializations.toggle-status',
                                'destroy' => 'specializations.destroy'
                            ]"
                            delete-confirm-message="Xóa ngành này sẽ xóa luôn TOÀN BỘ môn học và bài học thuộc ngành. Bạn có chắc chắn muốn xóa?" />
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        {{-- Pagination --}}
        @if($specializations->hasPages())
        <div class="bg-gray-50 px-4 py-3 border-t border-gray-200">
            <div class="flex justify-center">
                {{ $specializations->appends(request()->query())->links() }}
            </div>
        </div>
        @endif
    @else
                        <div class="text-center py-12">
                    @if(request('search'))
                    <div class="text-gray-500 mb-4">
                        <i class="bi bi-search text-3xl mb-2"></i>
                        <p>Không có ngành đào tạo nào phù hợp với tiêu chí tìm kiếm.</p>
                    </div>
                    @else
                    <div class="text-gray-500 mb-4">
                        <i class="bi bi-inbox text-3xl mb-2"></i>
                        <p>Chưa có ngành đào tạo nào được tạo.</p>
                    </div>
                    @endif
    @endif
</div>

@endsection

{{-- Lọc liên động + tự nạp lại do component x-filter-form đảm nhiệm. --}}
