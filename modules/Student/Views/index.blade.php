@extends('layouts.admin')

@section('title', 'Quản lý Học viên')
@section('page-title', 'Quản lý Học viên')

@section('content')
    {{-- Breadcrumb --}}
    <x-breadcrumb :items="[
        ['title' => 'Trang chủ'],
        ['title' => 'Quản lý tài khoản', 'url' => route('accounts.hub')],
        ['title' => 'Học viên']
    ]"/>
    {{-- Page Header --}}
    @if(auth()->check() && auth()->user()->can('users.create'))
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900">DANH SÁCH HỌC VIÊN</h1>
            </div>
            <div class="flex gap-3">
                <button type="button"
                        data-import-open="student"
                        onclick="openImportModal('student')"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium">
                    <i class="bi bi-upload mr-2"></i>Import học viên
                </button>
                <a href="{{ route('students.create') }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                    <i class="bi bi-plus mr-2"></i>Tạo mới
                </a>
            </div>
        </div>
    @else
        <x-page-header title="DANH SÁCH HỌC VIÊN"/>
    @endif


    {{-- Filters (self-closing như danh sách người dùng nội bộ → có nút Lọc) --}}
    <x-filter-form
        :action="route('students.index')"
        :clear-url="route('students.index')"
        :filters="[
            ['type' => 'search', 'name' => 'search', 'placeholder' => 'Tìm kiếm theo tên, email hoặc mã học viên...'],
            ['type' => 'select', 'name' => 'class_id', 'placeholder' => 'Tất cả lớp học', 'options' => $classes->toArray()],
            [
                'type' => 'select',
                'name' => 'status',
                'placeholder' => 'Tất cả trạng thái',
                'options' => ['1' => 'Hoạt động', '0' => 'Tạm ngừng'],
            ],
        ]"
    />

    {{-- Results Summary and Per Page Selector --}}
    @if ($students->total() > 0)
        <div class="mb-4 flex justify-between items-center">
            <div class="text-sm text-gray-600">
                Hiển thị {{ $students->firstItem() }} - {{ $students->lastItem() }}
                trong tổng số {{ $students->total() }} kết quả
                @if(request()->filled('search'))
                    <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                        Tìm kiếm: "{{ request()->get('search') }}"
                    </span>
                @endif
                @if(request()->filled('class_id'))
                    <span class="ml-2 px-2 py-1 bg-indigo-100 text-indigo-800 rounded text-xs">
                        Lớp: {{ $classes[request('class_id')] ?? request('class_id') }}
                    </span>
                @endif
                @if(request()->has('status') && request('status') !== '')
                    <span class="ml-2 px-2 py-1 bg-emerald-100 text-emerald-800 rounded text-xs">
                        {{ (string) request('status') === '1' ? 'Hoạt động' : 'Tạm ngừng' }}
                    </span>
                @endif
            </div>

            <div class="flex items-center space-x-3">
                <div class="flex items-center space-x-2">
                    <label for="per_page" class="text-sm text-gray-600">Hiển thị:</label>
                    <select name="per_page" id="per_page"
                        class="text-sm border border-gray-300 rounded-md px-2 py-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        onchange="changePerPage(this.value)">
                        @foreach([5, 10, 15, 25, 50] as $option)
                            <option value="{{ $option }}" {{ request('per_page', 10) == $option ? 'selected' : '' }}>
                                {{ $option }}
                            </option>
                        @endforeach
                    </select>
                    <span class="text-sm text-gray-600">/ trang</span>
                </div>

            @canPermission('students.delete')
            <div class="flex items-center space-x-2">
                <button id="deleteSelectedBtn"
                        class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                        style="display: none;"
                        onclick="deleteSelected()">
                    <i class="bi bi-trash mr-2"></i>Xóa đã chọn (<span id="selectedCount">0</span>)
                </button>
            </div>
            @endcanPermission
            </div>
        </div>
    @endif

    {{-- Table --}}
    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        @if ($students->count() > 0)
            <table class="w-full">
                <thead class="bg-slate-100 text-slate-800">
                <tr>
                    <th class="px-4 py-3 text-left font-semibold w-12 text-slate-700">
                        <input type="checkbox"
                               id="selectAll"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 focus:ring-2"
                               onchange="toggleAll(this)">
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc']) }}"
                           class="inline-flex items-center gap-1 text-slate-800 hover:text-blue-700 font-semibold transition-colors">
                            Tên học viên
                            @if (request('sort_by') == 'name')
                                <i class="bi bi-arrow-{{ request('sort_order') == 'asc' ? 'up' : 'down' }} text-blue-600"></i>
                            @else
                                <i class="bi bi-arrow-down-up text-slate-400 text-xs"></i>
                            @endif
                        </a>
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Mã SV</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'email', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc']) }}"
                           class="inline-flex items-center gap-1 text-slate-800 hover:text-blue-700 font-semibold transition-colors">
                            Email
                            @if (request('sort_by') == 'email')
                                <i class="bi bi-arrow-{{ request('sort_order') == 'asc' ? 'up' : 'down' }} text-blue-600"></i>
                            @else
                                <i class="bi bi-arrow-down-up text-slate-400 text-xs"></i>
                            @endif
                        </a>
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">Lớp học</th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'status', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc']) }}"
                           class="inline-flex items-center gap-1 text-slate-800 hover:text-blue-700 font-semibold transition-colors">
                            Trạng thái
                            @if (request('sort_by') == 'status')
                                <i class="bi bi-arrow-{{ request('sort_order') == 'asc' ? 'up' : 'down' }} text-blue-600"></i>
                            @else
                                <i class="bi bi-arrow-down-up text-slate-400 text-xs"></i>
                            @endif
                        </a>
                    </th>
                    <th class="px-4 py-3 text-left font-semibold text-slate-700">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'created_at', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc']) }}"
                           class="inline-flex items-center gap-1 text-slate-800 hover:text-blue-700 font-semibold transition-colors">
                            Ngày tạo
                            @if (request('sort_by') == 'created_at' || !request('sort_by'))
                                <i class="bi bi-arrow-{{ request('sort_order') == 'asc' || !request('sort_order') ? 'down' : 'up' }} text-blue-600"></i>
                            @else
                                <i class="bi bi-arrow-down-up text-slate-400 text-xs"></i>
                            @endif
                        </a>
                    </th>
                    <th class="px-4 py-3 text-right font-semibold text-slate-700 whitespace-nowrap">Thao tác</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                @foreach ($students as $student)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <input type="checkbox"
                                   class="student-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 focus:ring-2"
                                   value="{{ $student->id }}"
                                   onchange="updateSelection()">
                        </td>
                        <td class="px-4 py-3">{{ $student->name }}</td>
                        <td class="px-4 py-3">
                            <span class="text-sm text-gray-600">{{ $student->code ?? '-' }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $student->email }}</td>
                        <td class="px-4 py-3">{{ $student->class->name ?? '-' }}</td>
                        <td class="px-4 py-3">
                            <x-status-badge :is-active="$student->status === 1"/>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-600">
                                {{ $student->created_at->format('d/m/Y H:i') }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex space-x-2 items-center justify-end action-icons">
                                @canPermission('users.show')
                                <a href="{{ route('students.show', $student) }}"
                                   class="action-icon text-blue-600"
                                   title="Xem chi tiết">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @endcanPermission

                                @canPermission('users.edit')
                                <a href="{{ route('students.edit', $student) }}"
                                   class="action-icon text-green-600"
                                   title="Chỉnh sửa">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endcanPermission

                                @canPermission('users.delete')
                                <form action="{{ route('students.destroy', $student) }}" method="POST" class="inline"
                                      data-confirm="Bạn có chắc chắn muốn xóa học viên này?"
                                      data-confirm-danger="1"
                                      data-confirm-ok="Xóa">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="action-icon text-red-600"
                                            title="Xóa">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                                @endcanPermission

                                @if (!auth()->check() || (!auth()->user()->can('users.show') && !auth()->user()->can('users.edit') && !auth()->user()->can('users.delete')))
                                    <span class="text-gray-400 text-sm">Không có quyền</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
            {{-- Pagination --}}
            @if ($students->hasPages())
                <div class="bg-gray-50 px-4 py-3 border-t border-gray-200">
                    <div class="flex justify-center">
                        {{ $students->appends(request()->query())->links() }}
                    </div>
                </div>
            @endif
        @else
            <div class="text-center py-12">
                <i class="bi bi-search text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Không tìm thấy kết quả</h3>
                <p class="text-gray-500 mb-6">
                    @if (request()->hasAny(['search', 'class_id', 'status']))
                        Không có học viên nào phù hợp với tiêu chí tìm kiếm.
                    @else
                        Chưa có học viên nào được tạo.
                    @endif
                </p>
                @if (request()->hasAny(['search', 'class_id', 'status']))
                    <a href="{{ route('students.index') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                        Xóa bộ lọc
                    </a>
                @else
                    <a href="{{ route('students.create') }}"
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                        <i class="bi bi-plus mr-2"></i>Tạo học viên đầu tiên
                    </a>
                @endif
            </div>
        @endif
    </div>

    @include('user::partials.import-modal', [
        'importMode' => 'student',
        'importReloadUrl' => route('students.index'),
        'units' => $units ?? collect(),
        'specializations' => $specializations ?? collect(),
        'classrooms' => $classrooms ?? collect(),
        'instructorsForImport' => $instructorsForImport ?? collect(),
    ])
@endsection

@push('scripts')
    <script>
        // Function to change per page and reload
        function changePerPage(perPage) {
            const url = new URL(window.location.href);
            url.searchParams.set('per_page', perPage);
            url.searchParams.delete('page'); // Reset to first page when changing per_page
            window.location.href = url.toString();
        }

        // Toggle all checkboxes
        function toggleAll(selectAllCheckbox) {
            const checkboxes = document.querySelectorAll('.student-checkbox:not(:disabled)');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateSelection();
        }

        // Update selection count and show/hide delete button
        function updateSelection() {
            const checkboxes = document.querySelectorAll('.student-checkbox:checked');
            const selectedCount = checkboxes.length;
            const deleteBtn = document.getElementById('deleteSelectedBtn');
            const countSpan = document.getElementById('selectedCount');
            const selectAllCheckbox = document.getElementById('selectAll');

            // Update count
            countSpan.textContent = selectedCount;

            // Show/hide delete button
            if (selectedCount > 0) {
                deleteBtn.style.display = 'block';
            } else {
                deleteBtn.style.display = 'none';
            }

            // Update select all checkbox state
            const allCheckboxes = document.querySelectorAll('.student-checkbox:not(:disabled)');
            const checkedCheckboxes = document.querySelectorAll('.student-checkbox:checked');

            if (checkedCheckboxes.length === 0) {
                selectAllCheckbox.indeterminate = false;
                selectAllCheckbox.checked = false;
            } else if (checkedCheckboxes.length === allCheckboxes.length) {
                selectAllCheckbox.indeterminate = false;
                selectAllCheckbox.checked = true;
            } else {
                selectAllCheckbox.indeterminate = true;
            }
        }

        // Delete selected students
        function deleteSelected() {
            const checkboxes = document.querySelectorAll('.student-checkbox:checked');
            const selectedIds = Array.from(checkboxes).map(cb => cb.value);

            if (selectedIds.length === 0) {
                Notify.warning('Vui lòng chọn ít nhất một học viên để xóa.');
                return;
            }

            const message = `Bạn có chắc chắn muốn xóa ${selectedIds.length} học viên đã chọn? Bản ghi sẽ chuyển vào thùng rác (có thể khôi phục).`;

            uiConfirm(message, { danger: true, confirmText: 'Xóa', title: 'Xóa học viên' }).then((ok) => {
                if (!ok) return;

                // Create a form to submit the bulk delete request
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("students.bulk-delete") }}';

                // Add CSRF token
                const csrfField = document.createElement('input');
                csrfField.type = 'hidden';
                csrfField.name = '_token';
                csrfField.value = '{{ csrf_token() }}';
                form.appendChild(csrfField);

                // Add method field
                const methodField = document.createElement('input');
                methodField.type = 'hidden';
                methodField.name = '_method';
                methodField.value = 'DELETE';
                form.appendChild(methodField);

                // Add selected student IDs
                selectedIds.forEach(id => {
                    const idField = document.createElement('input');
                    idField.type = 'hidden';
                    idField.name = 'student_ids[]';
                    idField.value = id;
                    form.appendChild(idField);
                });

                document.body.appendChild(form);
                form.submit();
            });
        }
    </script>
@endpush

