@extends('layouts.admin')

@section('title', 'Quản lý Người dùng')
@section('page-title', 'Quản lý Người dùng')

@section('content')
    {{-- Breadcrumb --}}
    <x-breadcrumb :items="[
        ['title' => 'Trang chủ'],
        ['title' => 'Quản lý tài khoản', 'url' => route('accounts.hub')],
        ['title' => 'Người dùng']
    ]"/>
    {{-- Page Header --}}
    @if(auth()->check() && auth()->user()->can('users.create'))
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">DANH SÁCH NGƯỜI DÙNG</h1>
            </div>
            <div class="flex gap-3">
                <button type="button"
                        data-import-open="user"
                        onclick="openImportModal('user')"
                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium">
                    <i class="bi bi-upload mr-2"></i>Import người dùng
                </button>
                <a href="{{ route('users.create') }}"
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                    <i class="bi bi-plus mr-2"></i>Tạo mới
                </a>
            </div>
        </div>
    @else
        <x-page-header title="DANH SÁCH NGƯỜI DÙNG"/>
    @endif

    {{-- Filters --}}
    <x-filter-form :action="route('users.index')" :clear-url="route('users.index')" :filters="[
        ['type' => 'search', 'name' => 'search', 'placeholder' => 'Tìm kiếm theo tên, email hoặc mã người dùng...'],
        ['type' => 'select', 'name' => 'unit_id', 'placeholder' => 'Tất cả đơn vị', 'options' => $units->toArray()],
        [
            'type' => 'select',
            'name' => 'status',
            'placeholder' => 'Tất cả trạng thái',
            'options' => ['1' => 'Hoạt động', '0' => 'Tạm ngừng'],
        ],
        [
            'type' => 'select',
            'name' => 'user_type',
            'placeholder' => 'Tất cả loại người dùng',
            'options' => ['instructor' => 'Giảng viên', 'internal_user' => 'Nội bộ'],
        ],
        [
            'type' => 'select',
            'name' => 'sync_status',
            'placeholder' => 'Tình trạng đồng bộ',
            'options' => ['unsynced' => 'Chưa đồng bộ', 'synced' => 'Đã đồng bộ'],
        ],
    ]"/>

    {{-- Results Summary --}}
    @if ($users->total() > 0)
        <div class="mb-4 flex justify-between items-center">
            <div class="text-sm text-gray-600">
                Hiển thị {{ $users->firstItem() }} - {{ $users->lastItem() }}
                trong tổng số {{ $users->total() }} kết quả
                @if(request()->has('search') && !empty(trim(request()->get('search'))))
                    <span class="ml-2 px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">
                        Tìm kiếm: "{{ request()->get('search') }}"
                    </span>
                @endif
            </div>
            {{-- Bulk Actions --}}
            <div class="flex items-center space-x-2">
                @canPermission('users.create')
                <button id="syncInstructorBtn"
                        class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg font-medium disabled:opacity-50 disabled:cursor-not-allowed transition-all hover:shadow-md"
                        style="display: none;"
                        onclick="syncInstructorSelected()">
                    <i class="bi bi-arrow-repeat"></i>
                    <span>Đồng bộ giảng viên (<span id="syncInstructorCount">0</span>)</span>
                </button>
                @endcanPermission

                @canPermission('users.delete')
                <button id="deleteSelectedBtn"
                        class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium disabled:opacity-50 disabled:cursor-not-allowed transition-all hover:shadow-md"
                        style="display: none;"
                        onclick="deleteSelected()">
                    <i class="bi bi-trash"></i>
                    <span>Xóa đã chọn (<span id="selectedCount">0</span>)</span>
                </button>
                @endcanPermission
            </div>
        </div>
    @endif

    {{-- Table --}}
    <div class="bg-white rounded-lg shadow-sm border overflow-hidden">
        @if ($users->count() > 0)
            <table class="w-full">
                <thead class="bg-slate-100 text-slate-800 border-b border-slate-200">
                <tr>
                    <th class="px-4 py-3 text-left font-medium w-12">
                        <input type="checkbox"
                               id="selectAll"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 focus:ring-2"
                               onchange="toggleAll(this)">
                    </th>
                    <th class="px-4 py-3 text-left font-medium">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'name', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc']) }}"
                           class="inline-flex items-center gap-1 text-slate-800 hover:text-blue-700 font-semibold transition-colors">
                            Tên người dùng
                            @if (request('sort_by') == 'name')
                                <i class="bi bi-arrow-{{ request('sort_order') == 'asc' ? 'up' : 'down' }} ml-1"></i>
                            @endif
                        </a>
                    </th>
                    <th class="px-4 py-3 text-left font-medium">Mã</th>
                    <th class="px-4 py-3 text-left font-medium">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'email', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc']) }}"
                           class="inline-flex items-center gap-1 text-slate-800 hover:text-blue-700 font-semibold transition-colors">
                            Email
                            @if (request('sort_by') == 'email')
                                <i class="bi bi-arrow-{{ request('sort_order') == 'asc' ? 'up' : 'down' }} ml-1"></i>
                            @endif
                        </a>
                    </th>
                    <th class="px-4 py-3 text-left font-medium">Đơn vị</th>
                    <th class="px-4 py-3 text-left font-medium">Vai trò</th>
                    <th class="px-4 py-3 text-left font-medium">Loại</th>
                    <th class="px-4 py-3 text-left font-medium">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'status', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc']) }}"
                           class="inline-flex items-center gap-1 text-slate-800 hover:text-blue-700 font-semibold transition-colors">
                            Trạng thái
                            @if (request('sort_by') == 'status')
                                <i class="bi bi-arrow-{{ request('sort_order') == 'asc' ? 'up' : 'down' }} ml-1"></i>
                            @endif
                        </a>
                    </th>
                    <th class="px-4 py-3 text-left font-medium">
                        <a href="{{ request()->fullUrlWithQuery(['sort_by' => 'created_at', 'sort_order' => request('sort_order') == 'asc' ? 'desc' : 'asc']) }}"
                           class="inline-flex items-center gap-1 text-slate-800 hover:text-blue-700 font-semibold transition-colors">
                            Ngày tạo
                            @if (request('sort_by') == 'created_at' || !request('sort_by'))
                                <i
                                    class="bi bi-arrow-{{ request('sort_order') == 'asc' || !request('sort_order') ? 'down' : 'up' }} ml-1"></i>
                            @endif
                        </a>
                    </th>
                    <th class="px-4 py-3 text-right font-medium whitespace-nowrap">Thao tác</th>
                </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                @foreach ($users as $user)
                    @php
                        $isCurrentUser = $user->id === auth()->id();
                        $isSuperAdmin = $user->hasRole('super-admin');
                        $canDelete = !$isCurrentUser && !$isSuperAdmin;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3">
                            <input type="checkbox"
                                   class="user-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 focus:ring-2"
                                   value="{{ $user->id }}"
                                   data-user-type="{{ $user->user_type }}"
                                   data-instructor-id="{{ $user->instructor_id ?? '' }}"
                                   onchange="updateSelection()"
                                {{ !$canDelete ? 'disabled' : '' }}
                                {{ !$canDelete ? 'title=Không thể xóa người dùng này' : '' }}>
                        </td>
                        <td class="px-4 py-3">
                            {{ $user->name }}
                            @if($isCurrentUser)
                                <span class="ml-2 px-2 py-1 bg-green-100 text-green-800 rounded text-xs">Bạn</span>
                            @endif
                            @if($isSuperAdmin)
                                <span
                                    class="ml-2 px-2 py-1 bg-red-100 text-red-800 rounded text-xs">Super Admin</span>
                            @endif
                            @if($user->militaryRank)
                                <div class="mt-1"><x-military-rank-badge :rank="$user->militaryRank" compact /></div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="text-sm text-gray-600">{{ $user->code ?? '-' }}</span>
                        </td>
                        <td class="px-4 py-3">{{ $user->email }}</td>
                        <td class="px-4 py-3">{{ $user->unit->name ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $user->roleRelation->name ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if($user->user_type === 'instructor')
                                <span class="px-2 py-1 bg-purple-100 text-purple-800 rounded text-xs">Giảng viên</span>
                            @elseif($user->user_type === 'internal_user')
                                <span class="px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs">Nội bộ</span>
                            @else
                                <span class="px-2 py-1 bg-gray-100 text-gray-800 rounded text-xs">-</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <x-status-badge :is-active="$user->status === 1"/>
                        </td>
                        <td class="px-4 py-3">
                            <div class="text-sm text-gray-600">
                                {{ $user->created_at->format('d/m/Y H:i') }}
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex space-x-2 items-center justify-end action-icons">
                                @canPermission('users.show')
                                <a href="{{ route('users.show', $user) }}"
                                   class="action-icon text-blue-600"
                                   title="Xem chi tiết">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @endcanPermission

                                @canPermission('users.edit')
                                <a href="{{ route('users.edit', $user) }}"
                                   class="action-icon text-green-600"
                                   title="Chỉnh sửa">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @endcanPermission

                                @canPermission('users.delete')
                                @if($canDelete)
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" class="inline"
                                          data-confirm="Bạn có chắc chắn muốn xóa người dùng này?"
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
                                @else
                                    <span class="action-icon text-gray-300 cursor-not-allowed opacity-60" title="Không thể xóa">
                                        <i class="bi bi-trash"></i>
                                    </span>
                                @endif
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
            @if ($users->hasPages())
                <div class="bg-gray-50 px-4 py-3 border-t border-gray-200">
                    <div class="flex justify-center">
                        {{ $users->appends(request()->query())->links() }}
                    </div>
                </div>
            @endif
        @else
            <div class="text-center py-12">
                <i class="bi bi-search text-6xl text-gray-300 mb-4"></i>
                <h3 class="text-lg font-medium text-gray-900 mb-2">Không tìm thấy kết quả</h3>
                <p class="text-gray-500 mb-6">
                    @if (request()->hasAny(['search', 'unit_id', 'status']))
                        Không có người dùng nào phù hợp với tiêu chí tìm kiếm.
                    @else
                        Chưa có người dùng nào được tạo.
                    @endif
                </p>
                @if (request()->hasAny(['search', 'unit_id', 'status']))
                    <a href="{{ route('users.index') }}" class="text-blue-600 hover:text-blue-800 font-medium">
                        Xóa bộ lọc
                    </a>
                @else
                    <a href="{{ route('users.create') }}"
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                        <i class="bi bi-plus mr-2"></i>Tạo người dùng đầu tiên
                    </a>
                @endif
            </div>
        @endif
    </div>


{{-- Import người dùng nội bộ (không gồm học viên) --}}
@include('user::partials.import-modal', [
    'importMode' => 'user',
    'importReloadUrl' => route('users.index'),
    'units' => $units ?? collect(),
    'specializations' => collect(),
    'classrooms' => collect(),
    'instructorsForImport' => collect(),
])

{{-- Sync Result Modal --}}
<div id="syncResultModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
        <!-- Modal Header -->
        <div class="flex items-center justify-between pb-3 border-b">
            <h3 class="text-xl font-semibold text-gray-900">Kết quả đồng bộ giảng viên</h3>
            <button onclick="closeSyncResultModal()" class="text-gray-400 hover:text-gray-600">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <!-- Modal Body -->
        <div class="py-4 space-y-4">
            <!-- Success Section - Synced -->
            <div id="successSection" class="p-4 bg-green-50 border border-green-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-green-800 font-medium">
                        Đã đồng bộ thành công <span id="syncedCount" class="font-bold">0</span> giảng viên
                    </span>
                </div>
            </div>

            <!-- Success Section - Created -->
            <div id="createdSection" class="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-blue-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v2H7a1 1 0 100 2h2v2a1 1 0 102 0v-2h2a1 1 0 100-2h-2V7z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-blue-800 font-medium">
                        Đã tạo mới <span id="createdCount" class="font-bold">0</span> giảng viên
                    </span>
                </div>
            </div>

            <!-- Skipped Section -->
            <div id="skippedSection" class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-yellow-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-yellow-800 font-medium">
                        Đã bỏ qua <span id="skippedCount" class="font-bold">0</span> giảng viên (đã đồng bộ trước đó)
                    </span>
                </div>
            </div>

            <!-- Error Section -->
            <div id="errorSection" class="p-4 bg-red-50 border border-red-200 rounded-lg">
                <div class="flex items-start mb-3">
                    <svg class="w-5 h-5 text-red-600 mr-2 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                    </svg>
                    <span class="text-red-800 font-medium">
                        Có <span id="errorCount" class="font-bold">0</span> lỗi xảy ra
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white border rounded">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Tên người dùng</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Email</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-700 uppercase">Lỗi</th>
                            </tr>
                        </thead>
                        <tbody id="errorTableBody" class="divide-y divide-gray-200">
                            <!-- Error rows will be inserted here by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal Footer -->
        <div class="flex justify-end pt-3 border-t">
            <button onclick="closeSyncResultModal()"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg font-medium transition-colors">
                Đóng
            </button>
        </div>
    </div>
</div>

@endsection

@push('scripts')
    <script>
        // Auto submit form when select filters change
        document.addEventListener('DOMContentLoaded', function () {
            // Get all select elements within the filter form
            const filterForm = document.querySelector('form[action*="users"]');
            if (filterForm) {
                const selectElements = filterForm.querySelectorAll('select[name]');
                selectElements.forEach(select => {
                    select.addEventListener('change', function () {
                        // Only auto-submit for select elements, not for search input
                        if (this.tagName === 'SELECT') {
                            this.form.submit();
                        }
                    });
                });
            }
        });

        // Toggle all checkboxes
        function toggleAll(selectAllCheckbox) {
            const checkboxes = document.querySelectorAll('.user-checkbox:not(:disabled)');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateSelection();
        }

        // Update selection count and show/hide delete button
        function updateSelection() {
            const checkboxes = document.querySelectorAll('.user-checkbox:checked');
            const selectedCount = checkboxes.length;
            const deleteBtn = document.getElementById('deleteSelectedBtn');
            const countSpan = document.getElementById('selectedCount');
            const selectAllCheckbox = document.getElementById('selectAll');

            // Update delete count
            countSpan.textContent = selectedCount;

            // Show/hide delete button
            if (selectedCount > 0) {
                deleteBtn.style.display = 'block';
            } else {
                deleteBtn.style.display = 'none';
            }

            // Sync instructor button logic
            const syncInstructorBtn = document.getElementById('syncInstructorBtn');
            const syncInstructorCountSpan = document.getElementById('syncInstructorCount');

            if (syncInstructorBtn && syncInstructorCountSpan) {
                // Count only instructor users
                let instructorCount = 0;
                checkboxes.forEach(cb => {
                    if (cb.dataset.userType === 'instructor') {
                        instructorCount++;
                    }
                });

                syncInstructorCountSpan.textContent = instructorCount;

                // Show/hide sync instructor button
                if (instructorCount > 0) {
                    syncInstructorBtn.style.display = 'block';
                } else {
                    syncInstructorBtn.style.display = 'none';
                }
            }

            // Update select all checkbox state
            const allCheckboxes = document.querySelectorAll('.user-checkbox:not(:disabled)');
            const checkedCheckboxes = document.querySelectorAll('.user-checkbox:checked');

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

        // Delete selected users
        function deleteSelected() {
            const checkboxes = document.querySelectorAll('.user-checkbox:checked');
            const selectedIds = Array.from(checkboxes).map(cb => cb.value);

            if (selectedIds.length === 0) {
                Notify.warning('Vui lòng chọn ít nhất một người dùng để xóa.');
                return;
            }

            const message = `Bạn có chắc chắn muốn xóa ${selectedIds.length} người dùng đã chọn? Hành động này không thể hoàn tác.`;

            uiConfirm(message, { danger: true, confirmText: 'Xóa', title: 'Xóa người dùng' }).then((ok) => {
                if (!ok) return;

                // Create a form to submit the bulk delete request
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '{{ route("users.bulk-delete") }}';

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

                // Add selected user IDs
                selectedIds.forEach(id => {
                    const idField = document.createElement('input');
                    idField.type = 'hidden';
                    idField.name = 'user_ids[]';
                    idField.value = id;
                    form.appendChild(idField);
                });

                document.body.appendChild(form);
                form.submit();
            });
        }

        // Sync selected instructor users
        function syncInstructorSelected() {
            const checkboxes = document.querySelectorAll('.user-checkbox:checked');
            const instructorCheckboxes = Array.from(checkboxes).filter(cb => cb.dataset.userType === 'instructor');
            const selectedIds = instructorCheckboxes.map(cb => cb.value);

            if (selectedIds.length === 0) {
                Notify.warning('Vui lòng chọn ít nhất một giảng viên để đồng bộ.');
                return;
            }

            const message = `Bạn có chắc chắn muốn đồng bộ ${selectedIds.length} giảng viên với hệ thống?`;

            uiConfirm(message, { title: 'Đồng bộ giảng viên', confirmText: 'Đồng bộ' }).then((ok) => {
                if (!ok) return;

                // Show loading state
                const syncBtn = document.getElementById('syncInstructorBtn');
                const originalText = syncBtn.innerHTML;
                syncBtn.innerHTML = '<i class="bi bi-arrow-repeat animate-spin"></i><span>Đang xử lý...</span>';
                syncBtn.disabled = true;

                // Send AJAX request
                fetch('/users-bulk-sync-instructor', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        user_ids: selectedIds
                    })
                })
                .then(response => response.json())
                .then(data => {
                    // Reset button
                    syncBtn.innerHTML = originalText;
                    syncBtn.disabled = false;

                    if (data.success) {
                        // Show result modal
                        showSyncResultModal(data);
                    } else {
                        Notify.error('Có lỗi xảy ra: ' + (data.message || 'Vui lòng thử lại'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    syncBtn.innerHTML = originalText;
                    syncBtn.disabled = false;
                    Notify.error('Có lỗi xảy ra khi đồng bộ. Vui lòng thử lại.');
                });
            });
        }

        // Show sync result modal
        function showSyncResultModal(data) {
            const modal = document.getElementById('syncResultModal');
            const syncedCount = document.getElementById('syncedCount');
            const createdCount = document.getElementById('createdCount');
            const skippedCount = document.getElementById('skippedCount');
            const errorCount = document.getElementById('errorCount');
            const errorTableBody = document.getElementById('errorTableBody');
            const successSection = document.getElementById('successSection');
            const createdSection = document.getElementById('createdSection');
            const skippedSection = document.getElementById('skippedSection');
            const errorSection = document.getElementById('errorSection');

            // Update counts
            syncedCount.textContent = data.synced_count || 0;
            createdCount.textContent = data.created_count || 0;
            skippedCount.textContent = data.skipped_count || 0;
            errorCount.textContent = data.errors ? data.errors.length : 0;

            // Show/hide sections
            successSection.style.display = (data.synced_count > 0) ? 'block' : 'none';
            createdSection.style.display = (data.created_count > 0) ? 'block' : 'none';
            skippedSection.style.display = (data.skipped_count > 0) ? 'block' : 'none';
            errorSection.style.display = (data.errors && data.errors.length > 0) ? 'block' : 'none';

            // Populate error table
            errorTableBody.innerHTML = '';
            if (data.errors && data.errors.length > 0) {
                data.errors.forEach(error => {
                    const row = document.createElement('tr');
                    row.className = 'hover:bg-gray-50';
                    row.innerHTML = `
                        <td class="px-4 py-3 border-b">${error.user_name}</td>
                        <td class="px-4 py-3 border-b">${error.user_email}</td>
                        <td class="px-4 py-3 border-b text-red-600">${error.error}</td>
                    `;
                    errorTableBody.appendChild(row);
                });
            }

            // Show modal
            modal.classList.remove('hidden');
        }

        // Close sync result modal
        function closeSyncResultModal() {
            const modal = document.getElementById('syncResultModal');
            modal.classList.add('hidden');
            // Reload page to refresh data
            window.location.reload();
        }
    </script>
@endpush
