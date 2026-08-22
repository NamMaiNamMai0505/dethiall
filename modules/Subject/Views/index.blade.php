@extends('layouts.admin')

@section('title', 'Quản lý Môn học')
@section('page-title', 'Quản lý Môn học')

@section('content')
    {{-- Breadcrumb --}}
    <x-breadcrumb :items="[
            ['title' => 'Trang chủ'],
            ['title' => 'Môn học']
        ]" />

    {{-- Page Header --}}
    <x-page-header title="DANH SÁCH MÔN HỌC" :actions="array_filter([
            auth()->user()->can('subjects.create') ? [
                'url' => route('subjects.create'),
                'label' => 'Tạo mới',
                'icon' => 'plus',
                'color' => 'blue'
            ] : null,
        ])" />

    @if(auth()->user()->can('subjects.delete'))
        <div class="mb-4 flex items-center gap-3">
            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                <span class="relative inline-block h-6 w-11">
                    <input type="checkbox" id="bulk-select-toggle" class="peer sr-only">
                    <span class="absolute inset-0 rounded-full bg-gray-300 transition-colors peer-checked:bg-blue-600"></span>
                    <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white transition-transform peer-checked:translate-x-5"></span>
                </span>
                <span class="text-sm font-medium text-gray-700">Chọn nhiều để xóa</span>
            </label>
            <button type="button" id="bulk-delete-btn"
                    class="hidden items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700"
                    onclick="bulkDeleteSubjects()">
                <i class="bi bi-trash"></i>
                <span>Xóa đã chọn (<span id="bulk-selected-count">0</span>)</span>
            </button>
        </div>
    @endif

    {{-- Filters --}}
    <x-filter-form :action="route('subjects.index')" :clear-url="route('subjects.index')" :filters="[
            [
                'type' => 'search',
                'name' => 'search',
                'placeholder' => 'Tìm kiếm tên, mã hoặc mô tả...'
            ],
            [
                'type' => 'select',
                'name' => 'training_system_id',
                'placeholder' => 'Tất cả hệ',
                'options' => $trainingSystems ?? []
            ],
            [
                'type' => 'select',
                'name' => 'specialization_id',
                'placeholder' => 'Tất cả ngành học',
                'depends_on' => 'training_system_id',
                'options' => $specializations
            ],
            [
                'type' => 'select',
                'name' => 'level',
                'placeholder' => 'Tất cả cấp độ',
                'options' => $levels
            ],
            [
                'type' => 'select',
                'name' => 'assessment_method',
                'placeholder' => 'Tất cả phương pháp đánh giá',
                'options' => $assessmentMethods
            ],
            [
                'type' => 'select',
                'name' => 'semester',
                'placeholder' => 'Tất cả học kỳ',
                'options' => $semesters
            ],
            [
                'type' => 'select',
                'name' => 'subject_type',
                'placeholder' => 'Tất cả tính chất',
                'options' => [
                    'required' => 'Bắt buộc',
                    'elective' => 'Tự chọn'
                ]
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
        @if($subjects->total() > 0)
            <div class="mb-4 flex justify-between items-center">
                <div class="text-sm text-gray-600">
                    Hiển thị {{ $subjects->firstItem() }} - {{ $subjects->lastItem() }}
                    trong tổng số {{ $subjects->total() }} kết quả
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
        @if($subjects->count() > 0)
            <table class="w-full">
                <thead class="bg-slate-100 text-slate-800 border-b border-slate-200">
                    <tr>
                        <th class="bulk-col hidden px-4 py-3 text-left font-medium w-10">
                            <input type="checkbox" id="bulk-select-all" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 focus:ring-2">
                        </th>
                        <th class="px-4 py-3 text-left font-medium">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'code', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc']) }}"
                                class="inline-flex items-center gap-1 text-slate-800 hover:text-blue-700 font-semibold transition-colors">
                                Mã
                                @if(request('sort_by') == 'code')
                                    <i class="bi bi-arrow-{{ request('sort_order') == 'asc' ? 'up' : 'down' }} ml-1"></i>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-3 text-left font-medium">
                            <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc']) }}"
                                class="inline-flex items-center gap-1 text-slate-800 hover:text-blue-700 font-semibold transition-colors">
                                Tên môn học
                                @if(request('sort_by') == 'name')
                                    <i class="bi bi-arrow-{{ request('sort_order') == 'asc' ? 'up' : 'down' }} ml-1"></i>
                                @endif
                            </a>
                        </th>
                        <th class="px-4 py-3 text-left font-medium">Ngành đào tạo</th>
                        <th class="px-4 py-3 text-left font-medium">Tín chỉ & Số Tiết học</th>
                        <th class="px-4 py-3 text-left font-medium">Cấp độ</th>
                        <th class="px-4 py-3 text-left font-medium">Phương pháp ĐG</th>
                        <th class="px-4 py-3 text-left font-medium">Tính chất</th>
                        <th class="px-4 py-3 text-left font-medium">Trạng thái</th>
                        <th class="px-4 py-3 text-left font-medium">Thao tác</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($subjects as $subject)
                        <tr class="hover:bg-gray-50">
                            <td class="bulk-col hidden px-4 py-3">
                                <input type="checkbox" class="bulk-select-item rounded border-gray-300 text-blue-600 focus:ring-blue-500 focus:ring-2" value="{{ $subject->id }}">
                            </td>
                            <td class="px-4 py-3">
                                <code
                                    class="bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm font-mono" title="Full: {{ $subject->code }}">{{ $subject->display_code }}</code>
                            </td>
                            <td class="px-4 py-3">
                                <div>
                                    <div class="font-medium text-gray-900 flex flex-wrap items-center gap-2">
                                        <span>{{ $subject->name }}</span>
                                        @if($subject->abbreviation)
                                            <span class="bg-amber-100 text-amber-900 px-2 py-0.5 rounded text-xs font-mono font-semibold"
                                                  title="Viết tắt (xuất lịch)">{{ $subject->abbreviation }}</span>
                                        @endif
                                        <span class="inline-flex items-center gap-1 text-xs text-slate-600"
                                              title="Màu nhận diện (xuất lịch huấn luyện)">
                                            <span class="inline-block w-3 h-3 rounded border border-slate-300"
                                                  style="background: {{ $subject->display_color }}"></span>
                                            <span class="font-mono">{{ $subject->display_color }}</span>
                                        </span>
                                    </div>
                                    @if($subject->description)
                                        <div class="text-sm text-gray-600">
                                            {{ Str::limit($subject->description, 50) }}
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm">
                                    <div class="font-medium text-blue-600">{{ $subject->specialization->name ?? 'N/A' }}</div>
                                    @if($subject->specialization)
                                        <div class="text-gray-500">Mã ngành: {{ $subject->specialization->major_code ?? '—' }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm">
                                    <div class="font-bold text-lg text-green-600">{{ $subject->credits }} TC</div>
                                    <div class="text-gray-600">{{ $subject->hours_display }}</div>
                                    <div class="text-xs text-gray-500">Tổng: {{ $subject->total_hours }}h</div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <x-level-badge :color="$subject->level_color" :text="$subject->level_text" />
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                    {{ $subject->assessment_method_text }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <x-custom-badge :color="$subject->subject_type_color" :text="$subject->subject_type_text" />
                            </td>
                            <td class="px-4 py-3">
                                <x-status-badge :is-active="$subject->is_active" />
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex space-x-2">
                                    @if(auth()->check() && auth()->user()->can('subjects.show'))
                                        <a href="{{ route('subjects.show', $subject) }}"
                                            class="text-blue-600 hover:text-blue-800 text-sm font-medium" title="Xem">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    @endif


                                    @if(auth()->check() && auth()->user()->can('subjects.edit'))
                                        <a href="{{ route('subjects.edit', $subject) }}"
                                            class="text-green-600 hover:text-green-800 text-sm font-medium" title="Sửa">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" action="{{ route('subjects.toggle-status', $subject) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit"
                                                class="text-{{ $subject->is_active ? 'orange' : 'green' }}-600 hover:text-{{ $subject->is_active ? 'orange' : 'green' }}-800 text-sm font-medium"
                                                title="{{ $subject->is_active ? 'Tạm dừng' : 'Kích hoạt' }}">
                                                <i class="bi bi-{{ $subject->is_active ? 'pause-circle' : 'play-circle' }}"></i>
                                            </button>
                                        </form>

                                    @endif
                                    @if(auth()->check() && auth()->user()->can('subjects.edit'))
                                        <form method="POST" action="{{ route('subjects.destroy', $subject) }}" class="inline"
                                            data-confirm='Xóa môn học này sẽ xóa luôn TOÀN BỘ bài học thuộc môn. Bạn có chắc chắn muốn xóa?'
                                            data-confirm-danger="1"
                                            data-confirm-ok="Xóa">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium"
                                                title="Xóa">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            {{-- Pagination --}}
            @if($subjects->hasPages())
                <div class="bg-gray-50 px-4 py-3 border-t border-gray-200">
                    <div class="flex justify-end">
                        {{ $subjects->appends(request()->query())->links() }}
                    </div>
                </div>
            @endif
        @else
            <div class="text-center py-12">
                <i class="bi bi-search text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Không tìm thấy kết quả</h3>
                <p class="text-gray-500 mb-6">
                    @if(request()->hasAny(['search', 'specialization_id', 'level', 'assessment_method', 'subject_type', 'status']))
                        Không có môn học nào phù hợp với tiêu chí tìm kiếm.
                    @else
                        Chưa có môn học nào được tạo.
                    @endif
                </p>
                @if(request()->hasAny(['search', 'specialization_id', 'level', 'assessment_method', 'subject_type', 'status']))
                    <a href="{{ route('subjects.index') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                        Xóa bộ lọc
                    </a>
                @else
                    <a href="{{ route('subjects.create') }}"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                        <i class="bi bi-plus mr-2"></i>Tạo môn học đầu tiên
                    </a>
                @endif
            </div>
        @endif
    </div>

@push('scripts')
<script>
(function () {
    if (window.__subjectBulkSelectBound) return;
    window.__subjectBulkSelectBound = true;

    function boot() {
        const toggle = document.getElementById('bulk-select-toggle');
        const selectAll = document.getElementById('bulk-select-all');
        const deleteBtn = document.getElementById('bulk-delete-btn');
        const countEl = document.getElementById('bulk-selected-count');
        if (!toggle) return;

        function items() {
            return document.querySelectorAll('.bulk-select-item');
        }

        function updateBar() {
            const checked = document.querySelectorAll('.bulk-select-item:checked').length;
            if (countEl) countEl.textContent = String(checked);
            deleteBtn?.classList.toggle('hidden', checked === 0);
            deleteBtn?.classList.toggle('flex', checked > 0);
        }

        toggle.addEventListener('change', function () {
            document.querySelectorAll('.bulk-col').forEach(function (el) {
                el.classList.toggle('hidden', !toggle.checked);
            });
            if (!toggle.checked) {
                items().forEach(function (cb) { cb.checked = false; });
                if (selectAll) selectAll.checked = false;
                updateBar();
            }
        });

        selectAll?.addEventListener('change', function () {
            items().forEach(function (cb) { cb.checked = selectAll.checked; });
            updateBar();
        });

        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('bulk-select-item')) updateBar();
        });

        window.bulkDeleteSubjects = function () {
            const ids = Array.from(document.querySelectorAll('.bulk-select-item:checked')).map(function (cb) { return cb.value; });
            if (!ids.length) return;
            if (!confirm('Xóa ' + ids.length + ' môn học đã chọn? Toàn bộ bài học thuộc các môn này cũng sẽ bị xóa theo. Không thể hoàn tác thao tác này trên giao diện.')) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('subjects.bulk-delete') }}';
            form.innerHTML = '@csrf';
            ids.forEach(function (id) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                form.appendChild(input);
            });
            document.body.appendChild(form);
            form.submit();
        };
    }

    document.addEventListener('DOMContentLoaded', boot);
    if (document.readyState !== 'loading') boot();
    document.addEventListener('turbo:load', boot);
})();
</script>
@endpush
@endsection
