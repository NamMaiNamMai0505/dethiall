@extends('layouts.admin')

@section('title', 'Phân công giảng viên')
@section('page-title', 'Phân công giảng viên')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Môn học', 'url' => route('subjects.index')],
    ['title' => $subject->name, 'url' => route('subjects.show', $subject)],
    ['title' => 'Phân công giảng viên']
]" />

{{-- Page Header --}}
<x-page-header
    title="PHÂN CÔNG GIẢNG VIÊN"
    :actions="[
        [
            'url' => route('subjects.show', $subject),
            'label' => 'Quay lại',
            'icon' => 'arrow-left',
            'color' => 'gray'
        ]
    ]" />

{{-- Content --}}
<div class="bg-white rounded-lg shadow-sm border p-6">
    {{-- Subject Info Header --}}
    <div class="mb-6 pb-6 border-b">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">{{ $subject->name }}</h2>
                <div class="mt-2 flex items-center space-x-4">
                    <code class="bg-blue-100 text-blue-800 px-3 py-1 rounded text-sm font-mono">{{ $subject->code }}</code>
                    <span class="text-sm text-gray-600">{{ $subject->credits }} tín chỉ</span>
                    @if($subject->specialization)
                        <span class="text-sm text-gray-600">{{ $subject->specialization->name }}</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('subjects.store-instructor-assignments', $subject) }}" id="assignForm">
        @csrf

        {{-- Currently Assigned Instructors --}}
        @if($subject->instructors->count() > 0)
            <div class="mb-6 p-4 bg-blue-50 rounded-lg">
                <h3 class="text-lg font-medium text-gray-900 mb-3">
                    <i class="bi bi-info-circle text-blue-600"></i>
                    Giảng viên hiện tại ({{ $subject->instructors->count() }})
                </h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($subject->instructors as $instructor)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800">
                            {{ $instructor->name }}
                            @if($instructor->code)
                                <code class="ml-1 text-xs">({{ $instructor->code }})</code>
                            @endif
                        </span>
                    @endforeach
                </div>
                <p class="text-sm text-gray-600 mt-2">
                    <i class="bi bi-lightbulb"></i>
                    Chọn lại giảng viên bên dưới để cập nhật phân công
                </p>
            </div>
        @endif

        {{-- Filters Section --}}
        <div class="mb-6">
            <h3 class="text-lg font-medium text-gray-900 mb-3">Bộ lọc</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                {{-- Unit filter --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Đơn vị</label>
                    <select id="filterUnit" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Tất cả đơn vị</option>
                        @foreach($units as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Specialization filter --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Ngành đào tạo</label>
                    <select id="filterSpecialization" class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                        <option value="">Tất cả ngành</option>
                        @foreach($specializations as $id => $name)
                            <option value="{{ $id }}">{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Search box --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tìm kiếm</label>
                    <input type="text" id="filterSearch"
                           placeholder="Tên hoặc mã giảng viên..."
                           class="w-full rounded-lg border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                </div>
            </div>
        </div>

        {{-- Instructor Selection Grid --}}
        <div class="mb-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-gray-900">
                    Chọn giảng viên
                    <span class="text-sm font-normal text-gray-500">({{ $instructors->count() }} giảng viên)</span>
                </h3>
                <div class="flex space-x-2">
                    <button type="button" onclick="selectAllVisible()"
                            class="px-3 py-1.5 text-sm bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors">
                        <i class="bi bi-check-square"></i>
                        Chọn tất cả
                    </button>
                    <button type="button" onclick="deselectAll()"
                            class="px-3 py-1.5 text-sm bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                        <i class="bi bi-x-square"></i>
                        Bỏ chọn tất cả
                    </button>
                </div>
            </div>

            @if($instructors->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 max-h-96 overflow-y-auto border rounded-lg p-4 bg-gray-50" id="instructorGrid">
                    @foreach($instructors as $instructor)
                        <label class="instructor-card flex items-start p-4 bg-white border border-gray-200 rounded-lg cursor-pointer hover:border-blue-400 hover:shadow-md transition-all duration-200"
                               data-unit-id="{{ $instructor->unit_id ?? '' }}"
                               data-spec-id="{{ $instructor->specialization_id ?? '' }}"
                               data-name="{{ strtolower($instructor->name) }}"
                               data-code="{{ strtolower($instructor->code ?? '') }}">

                            <input type="checkbox"
                                   name="instructor_ids[]"
                                   value="{{ $instructor->id }}"
                                   {{ $subject->instructors->contains($instructor->id) ? 'checked' : '' }}
                                   class="mt-1 mr-3 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded instructor-checkbox">

                            <div class="flex-1 min-w-0">
                                <div class="flex items-start space-x-3">
                                    {{-- Avatar --}}
                                    <div class="flex-shrink-0">
                                        <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 to-indigo-600 flex items-center justify-center text-white font-bold text-sm">
                                            {{ strtoupper(mb_substr($instructor->name, 0, 1)) }}
                                        </div>
                                    </div>

                                    {{-- Info --}}
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-semibold text-gray-900 truncate">{{ $instructor->name }}</h4>

                                        @if($instructor->code)
                                            <div class="text-xs text-gray-500 mt-1">
                                                <code class="bg-gray-100 px-1.5 py-0.5 rounded">{{ $instructor->code }}</code>
                                            </div>
                                        @endif

                                        @if($instructor->email)
                                            <div class="text-xs text-gray-600 mt-1 truncate">
                                                <i class="bi bi-envelope"></i>
                                                {{ $instructor->email }}
                                            </div>
                                        @endif

                                        @if($instructor->unit)
                                            <div class="text-xs text-gray-500 mt-1 truncate">
                                                <i class="bi bi-building"></i>
                                                {{ $instructor->unit->name }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </label>
                    @endforeach
                </div>

                {{-- No results message (hidden by default) --}}
                <div id="noResults" class="hidden text-center py-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                        <i class="bi bi-search text-gray-400 text-2xl"></i>
                    </div>
                    <p class="text-gray-500 text-sm">Không tìm thấy giảng viên phù hợp với bộ lọc</p>
                </div>
            @else
                <div class="text-center py-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                        <i class="bi bi-person-x text-gray-400 text-2xl"></i>
                    </div>
                    <p class="text-gray-500 text-sm">Không có giảng viên nào trong hệ thống</p>
                </div>
            @endif
        </div>

        {{-- Selected Count Display --}}
        <div class="mb-6 p-4 bg-green-50 rounded-lg">
            <p class="text-gray-700">
                <i class="bi bi-check-circle text-green-600"></i>
                Đã chọn: <span id="selectedCount" class="font-bold text-green-700">0</span> giảng viên
            </p>
        </div>

        {{-- Validation Errors --}}
        @if($errors->any())
            <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg">
                <div class="flex items-start">
                    <i class="bi bi-exclamation-triangle text-red-600 text-xl mr-3 mt-0.5"></i>
                    <div>
                        <h4 class="text-red-800 font-medium mb-1">Có lỗi xảy ra:</h4>
                        <ul class="list-disc list-inside text-sm text-red-700">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Form Actions --}}
        <div class="flex justify-end space-x-3 pt-6 border-t">
            <a href="{{ route('subjects.show', $subject) }}"
               class="px-6 py-2.5 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors">
                <i class="bi bi-x-circle mr-1"></i>
                Hủy
            </a>
            <button type="submit"
                    class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i class="bi bi-check-circle mr-1"></i>
                Lưu phân công
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
    // Filter instructors based on unit, specialization, and search term
    function filterInstructors() {
        const unitFilter = document.getElementById('filterUnit').value;
        const specFilter = document.getElementById('filterSpecialization').value;
        const searchTerm = document.getElementById('filterSearch').value.toLowerCase();

        const cards = document.querySelectorAll('.instructor-card');
        let visibleCount = 0;

        cards.forEach(card => {
            const unitId = card.dataset.unitId;
            const specId = card.dataset.specId;
            const name = card.dataset.name;
            const code = card.dataset.code;

            let show = true;

            // Filter by unit
            if (unitFilter && unitId !== unitFilter) {
                show = false;
            }

            // Filter by specialization
            if (specFilter && specId !== specFilter) {
                show = false;
            }

            // Filter by search term (name or code)
            if (searchTerm && !name.includes(searchTerm) && !code.includes(searchTerm)) {
                show = false;
            }

            card.style.display = show ? 'flex' : 'none';
            if (show) visibleCount++;
        });

        // Show/hide no results message
        const noResults = document.getElementById('noResults');
        const instructorGrid = document.getElementById('instructorGrid');

        if (visibleCount === 0) {
            instructorGrid.style.display = 'none';
            noResults.classList.remove('hidden');
        } else {
            instructorGrid.style.display = 'grid';
            noResults.classList.add('hidden');
        }
    }

    // Update selected count
    function updateSelectedCount() {
        const checkboxes = document.querySelectorAll('.instructor-checkbox:checked');
        document.getElementById('selectedCount').textContent = checkboxes.length;
    }

    // Select all visible instructors
    function selectAllVisible() {
        const cards = document.querySelectorAll('.instructor-card');
        cards.forEach(card => {
            if (card.style.display !== 'none') {
                const checkbox = card.querySelector('.instructor-checkbox');
                checkbox.checked = true;
            }
        });
        updateSelectedCount();
    }

    // Deselect all instructors
    function deselectAll() {
        const checkboxes = document.querySelectorAll('.instructor-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        updateSelectedCount();
    }

    // Initialize
    document.addEventListener('DOMContentLoaded', function() {
        // Attach filter listeners
        document.getElementById('filterUnit').addEventListener('change', filterInstructors);
        document.getElementById('filterSpecialization').addEventListener('change', filterInstructors);
        document.getElementById('filterSearch').addEventListener('input', filterInstructors);

        // Attach checkbox change listeners
        document.querySelectorAll('.instructor-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', updateSelectedCount);
        });

        // Initialize count
        updateSelectedCount();
    });
</script>
@endpush

@endsection
