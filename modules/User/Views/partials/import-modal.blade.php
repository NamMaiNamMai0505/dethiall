{{-- Shared import modal. $importMode: "all" | "user" | "student" --}}
@php
    $importMode = $importMode ?? 'all';
    $isStudentOnlyImport = $importMode === 'student';
    $isUserOnlyImport = $importMode === 'user';
    // ID riêng theo mode — tránh Turbo để lại modal user che form lớp học viên
    $importModalId = 'importModal_' . $importMode;
    $importFileInputId = 'importFileInput_' . $importMode;
    $importClassSetupId = 'studentClassSetup_' . $importMode;
@endphp

@push('styles')
<style>
    /* Import modal: solid panel — override admin glass (.bg-white.rounded-xl is translucent) */
    .import-modal-root {
        position: fixed !important;
        inset: 0 !important;
        z-index: 10050 !important;
        display: none;
    }
    .import-modal-root.is-open {
        display: block !important;
    }
    .import-modal-root .import-modal-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.55) !important;
        backdrop-filter: none !important;
    }
    .import-modal-root .import-modal-center {
        position: relative;
        z-index: 1;
        min-height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        box-sizing: border-box;
    }
    .import-modal-panel {
        width: 100%;
        max-width: 56rem;
        max-height: min(90vh, 900px);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        border-radius: 0.75rem;
        border: 1px solid #e5e7eb !important;
        /* solid white — NOT glass */
        background: #ffffff !important;
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35) !important;
    }
    .import-modal-panel .import-modal-header,
    .import-modal-panel .import-modal-footer {
        flex-shrink: 0;
        background: #ffffff !important;
        backdrop-filter: none !important;
    }
    .import-modal-panel .import-modal-footer {
        background: #f9fafb !important;
    }
    .import-modal-panel .import-modal-body {
        flex: 1 1 auto;
        min-height: 0;
        overflow-y: auto;
        overflow-x: hidden;
        -webkit-overflow-scrolling: touch;
        background: #ffffff !important;
    }
    .import-modal-panel .import-modal-body .bg-white,
    .import-modal-panel .import-modal-body .bg-gray-50,
    .import-modal-panel .import-modal-body .bg-blue-50,
    .import-modal-panel .import-modal-body .bg-indigo-50 {
        backdrop-filter: none !important;
        -webkit-backdrop-filter: none !important;
    }
    .import-modal-panel select,
    .import-modal-panel input:not([type="file"]):not(.date-input):not(.flatpickr-input) {
        background-color: #ffffff !important;
    }

    /* Date fields trong import: đồng bộ date-input-theme (flatpickr + icon) */
    .import-modal-root .date-input-field {
        width: 100%;
    }
    .import-modal-root .date-input-control {
        position: relative;
        width: 100%;
        min-height: 40px;
    }
    .import-modal-root .date-input-control .date-input-icon {
        position: absolute;
        right: 0.75rem;
        top: 50%;
        transform: translateY(-50%);
        color: #9ca3af;
        pointer-events: none;
        z-index: 3;
        font-size: 1rem;
    }
    .import-modal-root .date-input-control:focus-within .date-input-icon {
        color: #4ea1ff;
    }
    .import-modal-root input.date-input,
    .import-modal-root .date-input-control input.flatpickr-input,
    .import-modal-root .date-input-control .flatpickr-input.form-control {
        width: 100% !important;
        min-height: 40px !important;
        height: 40px !important;
        padding: 0 2.5rem 0 0.75rem !important;
        border-radius: 0.625rem !important;
        border: 1px solid #d5dae3 !important;
        background: rgba(250, 248, 244, 0.92) !important;
        color: #1f2937 !important;
        font-size: 0.875rem !important;
        box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.03);
        cursor: pointer;
    }
    .import-modal-root input.date-input:focus,
    .import-modal-root .date-input-control input.flatpickr-input:focus {
        outline: none !important;
        border-color: #4ea1ff !important;
        box-shadow: 0 0 0 3px rgba(78, 161, 255, 0.22), inset 0 1px 2px rgba(15, 23, 42, 0.03) !important;
    }

    /* Ẩn hoàn toàn file input native (tránh lộ "Choose File" / nút xấu trình duyệt) */
    .import-modal-root input[type="file"][data-import-file-input],
    .import-modal-root input[type="file"].import-file-input-hidden {
        position: absolute !important;
        width: 1px !important;
        height: 1px !important;
        padding: 0 !important;
        margin: -1px !important;
        overflow: hidden !important;
        clip: rect(0, 0, 0, 0) !important;
        white-space: nowrap !important;
        border: 0 !important;
        opacity: 0 !important;
        pointer-events: none !important;
        appearance: none !important;
        -webkit-appearance: none !important;
        background: transparent !important;
        font-size: 0 !important;
        color: transparent !important;
    }

    .import-modal-root [data-import-dropzone] {
        position: relative;
    }

    .import-modal-root [data-import-pick-label] {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.4rem;
        min-height: 2.5rem;
        padding: 0.5rem 1.15rem;
        border-radius: 0.625rem;
        border: 1px solid #86efac;
        background: linear-gradient(180deg, #22c55e 0%, #16a34a 100%);
        color: #ffffff !important;
        font-size: 0.875rem;
        font-weight: 600;
        line-height: 1.25;
        box-shadow: 0 1px 2px rgba(22, 163, 74, 0.25);
        cursor: pointer;
        user-select: none;
        transition: filter 0.15s ease, box-shadow 0.15s ease, transform 0.1s ease;
    }
    .import-modal-root [data-import-pick-label]:hover {
        filter: brightness(1.05);
        box-shadow: 0 4px 12px rgba(22, 163, 74, 0.28);
    }
    .import-modal-root [data-import-pick-label]:active {
        transform: translateY(1px);
        filter: brightness(0.98);
    }

    /* Tom Select trong import modal (modal portal ra body, ngoài #admin-content) */
    .import-modal-root .ui-select-field,
    .import-modal-root .instructor-select-field {
        position: relative;
        width: 100%;
    }
    .import-modal-root .ts-wrapper {
        font-family: inherit;
        width: 100%;
    }
    .import-modal-root .ts-wrapper.dropdown-active {
        z-index: 10055;
        position: relative;
    }
    .import-modal-root .ts-wrapper .ts-control {
        background: #ffffff !important;
        border: 1px solid #d5dae3 !important;
        border-radius: 0.625rem !important;
        min-height: 40px;
        padding: 0.4rem 0.75rem !important;
        box-shadow: inset 0 1px 2px rgba(15, 23, 42, 0.03);
        font-size: 0.875rem;
        color: #1f2937;
    }
    .import-modal-root .ts-wrapper.single .ts-control {
        background-image: none !important;
    }
    .import-modal-root .ts-wrapper.focus .ts-control,
    .import-modal-root .ts-wrapper.dropdown-active .ts-control {
        border-color: #4ea1ff !important;
        box-shadow: 0 0 0 3px rgba(78, 161, 255, 0.22), inset 0 1px 2px rgba(15, 23, 42, 0.03) !important;
    }
    .import-modal-root .ts-wrapper .ts-control input {
        font-size: 0.875rem !important;
        color: #1f2937 !important;
    }
    .import-modal-root .ts-wrapper .ts-control > .item {
        background: transparent !important;
        color: #1f2937 !important;
        border: none !important;
        font-weight: 500;
    }
    .import-modal-root .ts-wrapper .clear-button {
        color: #9ca3af;
    }
    .import-modal-root .ts-wrapper .clear-button:hover {
        color: #4ea1ff;
    }
    .import-modal-root .ts-wrapper.ts-no-search:not(.disabled) .ts-control {
        cursor: pointer;
    }
    .import-modal-root .ts-wrapper.ts-no-search .ts-control input {
        display: none !important;
        width: 0 !important;
        min-width: 0 !important;
        padding: 0 !important;
        margin: 0 !important;
        opacity: 0 !important;
        position: absolute !important;
        pointer-events: none !important;
    }
</style>
@endpush

{{-- Import Modal (id theo mode: importModal_user | importModal_student) --}}
<div id="{{ $importModalId }}"
     class="import-modal-root hidden"
     data-import-modal
     data-import-mode="{{ $importMode }}"
     aria-hidden="true"
     role="dialog">
    <div class="import-modal-backdrop" data-import-backdrop></div>
    <div class="import-modal-center">
        <div id="{{ $importModalId }}_panel" class="import-modal-panel" onclick="event.stopPropagation()">
            {{-- Header cố định --}}
            <div class="import-modal-header flex justify-between items-center gap-3 px-5 py-4 border-b border-gray-200">
                <h3 class="text-lg sm:text-xl font-semibold text-gray-900">
                    <i class="bi bi-upload text-green-600 mr-2"></i>
                    {{ $isStudentOnlyImport ? 'Import học viên từ Excel' : 'Import người dùng từ Excel' }}
                </h3>
                <button type="button" onclick="closeImportModal()"
                        class="flex-shrink-0 w-9 h-9 inline-flex items-center justify-center rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-800">
                    <i class="bi bi-x-lg text-lg"></i>
                </button>
            </div>

            {{-- Body cuộn độc lập --}}
            <div class="import-modal-body px-5 py-4 space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-start gap-3 min-w-0">
                        <i class="bi bi-file-earmark-excel text-blue-600 text-2xl flex-shrink-0"></i>
                        <div class="min-w-0">
                            <p class="font-medium text-blue-900">Tải file mẫu</p>
                            <p class="text-sm text-blue-700">
                                @if($isStudentOnlyImport)
                                    Sheet «Dữ liệu học viên» (hoặc sheet dữ liệu bất kỳ)
                                @elseif($isUserOnlyImport)
                                    Sheet «Dữ liệu người dùng»
                                @else
                                    Sheet «Dữ liệu người dùng» / «Dữ liệu học viên»
                                @endif
                            </p>
                        </div>
                    </div>
                    <a href="{{ route('users.import.template') }}"
                       class="inline-flex items-center justify-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium shrink-0">
                        <i class="bi bi-download"></i> Tải mẫu
                    </a>
                </div>

                @if($isStudentOnlyImport)
                    <div class="hidden" aria-hidden="true">
                        <input type="radio" name="importType" value="student" checked data-import-type="student">
                    </div>
                @elseif($isUserOnlyImport)
                    <div class="hidden" aria-hidden="true">
                        <input type="radio" name="importType" value="user" checked data-import-type="user">
                    </div>
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-3 text-sm text-gray-700">
                        <i class="bi bi-person-fill text-blue-600 mr-1"></i>
                        Import <strong>người dùng nội bộ / giảng viên</strong> (không gồm học viên).
                    </div>
                @else
                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                    <label class="block text-sm font-semibold text-gray-700 mb-3">Loại dữ liệu import</label>
                    <div class="flex flex-col sm:flex-row sm:flex-wrap gap-3 sm:gap-5">
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="importType" value="all" checked class="text-blue-600" onchange="handleImportTypeChange()">
                            <span class="text-sm text-gray-800">Tất cả</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="importType" value="user" class="text-blue-600" onchange="handleImportTypeChange()">
                            <span class="text-sm text-gray-800">Chỉ người dùng</span>
                        </label>
                        <label class="inline-flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="importType" value="student" class="text-blue-600" onchange="handleImportTypeChange()">
                            <span class="text-sm text-gray-800">Chỉ học viên</span>
                        </label>
                    </div>
                </div>
                @endif

                <div id="{{ $importClassSetupId }}"
                     data-import-class-setup
                     class="{{ $isStudentOnlyImport ? '' : 'hidden' }} border border-indigo-200 bg-indigo-50 rounded-xl p-4 space-y-3">
                    <div>
                        <h4 class="font-semibold text-indigo-900 text-sm sm:text-base">
                            <i class="bi bi-people mr-1"></i> Cấu hình lớp cho danh sách học viên
                        </h4>
                        <p class="text-xs text-indigo-800 mt-1 leading-relaxed">
                            Chọn khoa, ngành, đại đội/tiểu đoàn, giảng đường, GV phụ trách.
                            Lớp được tạo/cập nhật trước khi gán học viên. Quân số = số HV trong file (chỉnh được).
                        </p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label for="import_unit_id" class="block text-sm font-medium text-gray-700 mb-1">Khoa / đơn vị <span class="text-red-500">*</span></label>
                            <div class="ui-select-field">
                                <select id="import_unit_id"
                                        data-placeholder="Chọn khoa"
                                        data-searchable="1"
                                        class="w-full text-sm">
                                    <option value="">Chọn khoa</option>
                                    @foreach($units as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label for="import_specialization_id" class="block text-sm font-medium text-gray-700 mb-1">Ngành đào tạo <span class="text-red-500">*</span></label>
                            <div class="ui-select-field">
                                <select id="import_specialization_id"
                                        data-placeholder="Chọn ngành"
                                        data-searchable="1"
                                        class="w-full text-sm">
                                    <option value="">Chọn ngành</option>
                                    @foreach($specializations ?? [] as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Đại đội / Tiểu đoàn</label>
                            <input type="text" id="import_management_unit" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white"
                                   placeholder="VD: Đại đội 1 / Tiểu đoàn A">
                        </div>
                        <div>
                            <label for="import_classroom_id" class="block text-sm font-medium text-gray-700 mb-1">Giảng đường / phòng học</label>
                            <div class="ui-select-field">
                                <select id="import_classroom_id"
                                        data-placeholder="Chọn giảng đường"
                                        data-searchable="1"
                                        class="w-full text-sm">
                                    <option value="">Chọn giảng đường</option>
                                    @foreach($classrooms ?? [] as $id => $name)
                                        <option value="{{ $id }}">{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tên lớp <span class="text-red-500">*</span></label>
                            <input type="text" id="import_class_name" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white" placeholder="VD: Lớp CDDD K12">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Mã lớp <span class="text-red-500">*</span></label>
                            <input type="text" id="import_class_code" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-mono uppercase bg-white" placeholder="VD: CDDD-K12-A">
                        </div>
                        <div>
                            <label for="import_instructor_id" class="block text-sm font-medium text-gray-700 mb-1">GV phụ trách lớp</label>
                            <div class="instructor-select-field">
                                <select id="import_instructor_id"
                                        data-instructor-select
                                        data-placeholder="Tìm và chọn giảng viên..."
                                        data-searchable="1"
                                        class="w-full text-sm">
                                    <option value="">Tìm và chọn giảng viên...</option>
                                    @foreach($instructorsForImport ?? [] as $ins)
                                        <option value="{{ $ins->id }}"
                                                data-name="{{ $ins->name }}"
                                                data-code="{{ $ins->code }}"
                                                data-unit="{{ $ins->unit->name ?? '' }}"
                                                data-unit-id="{{ $ins->unit_id }}">
                                            {{ $ins->name }} ({{ $ins->code }}){{ $ins->unit ? ' - '.$ins->unit->name : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Quân số lớp (max)</label>
                            <input type="number" id="import_max_students" min="1" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white"
                                   placeholder="Mặc định = số HV trong file">
                        </div>
                        <div class="date-input-field">
                            <label for="import_start_date" class="block text-sm font-medium text-gray-700 mb-1">Ngày bắt đầu</label>
                            <div class="date-input-control">
                                <input type="date"
                                       id="import_start_date"
                                       class="date-input date-input--ready w-full text-sm"
                                       data-import-date>
                                <i class="bi bi-calendar3 date-input-icon" aria-hidden="true"></i>
                            </div>
                        </div>
                        <div class="date-input-field">
                            <label for="import_end_date" class="block text-sm font-medium text-gray-700 mb-1">Ngày kết thúc</label>
                            <div class="date-input-control">
                                <input type="date"
                                       id="import_end_date"
                                       class="date-input date-input--ready w-full text-sm"
                                       data-import-date>
                                <i class="bi bi-calendar3 date-input-icon" aria-hidden="true"></i>
                            </div>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-1">Thời gian đào tạo (tháng)</label>
                            <input type="number" id="import_duration_months" min="1" max="120" class="w-full sm:max-w-xs px-3 py-2 border border-gray-300 rounded-lg text-sm bg-white" placeholder="VD: 36">
                        </div>
                    </div>
                </div>

                <form id="importForm" enctype="multipart/form-data" onsubmit="return false;">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">File Excel <span class="text-red-500">*</span></label>
                        <div id="dropZone"
                             data-import-dropzone
                             class="border-2 border-dashed border-gray-300 rounded-lg p-6 sm:p-8 text-center hover:border-green-500 transition-colors cursor-pointer bg-white">
                            {{-- Input ẩn hoàn toàn; mở dialog qua label[for] --}}
                            <input type="file"
                                   id="{{ $importFileInputId }}"
                                   name="file"
                                   accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
                                   class="import-file-input-hidden"
                                   data-import-file-input
                                   tabindex="-1"
                                   aria-hidden="true">

                            <div id="uploadPrompt" data-import-upload-prompt>
                                <i class="bi bi-cloud-upload text-4xl text-gray-400 mb-3 block"></i>
                                <p class="text-gray-600 mb-2">Kéo thả file vào đây hoặc</p>
                                <label for="{{ $importFileInputId }}"
                                       id="pickFileBtn"
                                       data-import-pick-label>
                                    <i class="bi bi-folder2-open"></i>
                                    Chọn file
                                </label>
                                <p class="text-sm text-gray-500 mt-2">.xlsx / .xls · tối đa 5MB</p>
                            </div>

                            <div id="fileInfo" data-import-file-info class="hidden">
                                <i class="bi bi-file-earmark-excel text-green-600 text-4xl mb-3 block"></i>
                                <p class="text-gray-800 font-medium break-all" id="fileName" data-import-file-name></p>
                                <p class="text-sm text-gray-500" id="fileSize" data-import-file-size></p>
                                <button type="button" id="clearFileBtn" data-import-clear-btn
                                        class="mt-3 inline-flex items-center gap-1 text-red-600 hover:text-red-800 text-sm font-medium">
                                    <i class="bi bi-x-circle"></i>Xóa file
                                </button>
                            </div>
                        </div>
                    </div>

                    <div id="progressBar" class="hidden mt-4">
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div id="progressFill" class="bg-green-600 h-2.5 rounded-full transition-all" style="width: 0%"></div>
                        </div>
                        <p id="progressText" class="text-sm text-gray-600 mt-2 text-center"></p>
                    </div>

                    <div id="messageBox" class="hidden mt-4 p-4 rounded-lg text-sm"></div>

                    <div id="dataPreview" class="hidden mt-4">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-1 mb-2">
                            <h4 class="font-semibold text-gray-900">Xem trước (<span id="totalRows">0</span> bản ghi)</h4>
                            <p id="previewFormHint" class="hidden text-xs text-indigo-700">
                                <i class="bi bi-info-circle mr-0.5"></i>
                                Ô có nhãn <span class="px-1 py-0.5 rounded bg-indigo-100 font-medium">form</span>
                                = cột trống, lấy từ cấu hình lớp bên trên
                            </p>
                        </div>
                        <div class="border border-gray-300 rounded-lg overflow-auto max-h-64">
                            <table class="min-w-full divide-y divide-gray-200 text-sm">
                                <thead class="bg-slate-100 text-slate-700">
                                    <tr>
                                        <th class="px-3 py-2 text-left whitespace-nowrap">STT</th>
                                        <th class="px-3 py-2 text-left whitespace-nowrap">Loại</th>
                                        <th class="px-3 py-2 text-left whitespace-nowrap">Họ tên</th>
                                        <th class="px-3 py-2 text-left whitespace-nowrap">Mã</th>
                                        <th class="px-3 py-2 text-left whitespace-nowrap">Email</th>
                                        <th class="px-3 py-2 text-left whitespace-nowrap">Đơn vị</th>
                                        <th class="px-3 py-2 text-left whitespace-nowrap">Vai trò</th>
                                        <th class="px-3 py-2 text-left whitespace-nowrap">Mã lớp</th>
                                    </tr>
                                </thead>
                                <tbody id="previewTableBody" class="bg-white divide-y divide-gray-200"></tbody>
                            </table>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Footer cố định --}}
            <div class="import-modal-footer px-5 py-3 border-t border-gray-200 flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                <button type="button" onclick="closeImportModal()"
                        class="w-full sm:w-auto bg-gray-600 hover:bg-gray-700 text-white px-4 py-2.5 rounded-lg font-medium">
                    Đóng
                </button>
                <button type="button" id="uploadBtn" disabled
                        class="w-full sm:w-auto bg-green-600 hover:bg-green-700 text-white px-4 py-2.5 rounded-lg font-medium disabled:bg-gray-400 disabled:cursor-not-allowed">
                    <i class="bi bi-upload mr-1"></i> Nhập dữ liệu
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
    {{-- SheetJS bắt buộc để đọc Excel (trước đây bị sót khi tách partial) --}}
    <script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>
    <script>
        let selectedFile = null;
        let parsedData = [];
        let allParsedData = [];
        const IMPORT_MODE = @json($importMode);
        const IS_STUDENT_ONLY_IMPORT = IMPORT_MODE === 'student';
        const IS_USER_ONLY_IMPORT = IMPORT_MODE === 'user';
        const IMPORT_RELOAD_URL = @json($importReloadUrl ?? null);
        const IMPORT_FILE_INPUT_ID = @json($importFileInputId);
        const IMPORT_MODAL_ID = @json($importModalId);
        const IMPORT_CLASS_SETUP_ID = @json($importClassSetupId);

        function cell(row, idx) {
            const v = row && row[idx] !== undefined && row[idx] !== null ? row[idx] : '';
            return String(v).trim();
        }

        function normalizeSheetName(name) {
            return String(name || '')
                .normalize('NFC')
                .toLowerCase()
                .replace(/\s+/g, ' ')
                .trim();
        }

        /** Tìm sheet theo tên / regex, bỏ sheet hướng dẫn & danh mục */
        function findWorkbookSheet(workbook, matchers) {
            const names = workbook.SheetNames || [];
            for (let i = 0; i < names.length; i++) {
                const raw = names[i];
                const n = normalizeSheetName(raw);
                // Bỏ sheet phụ
                if (/hướng dẫn|huong dan|đơn vị|don vi|phân quyền|phan quyen|permission|lớp học|lop hoc|classes/i.test(n)) {
                    continue;
                }
                for (let m = 0; m < matchers.length; m++) {
                    const rule = matchers[m];
                    if (typeof rule === 'string' && n === normalizeSheetName(rule)) {
                        return workbook.Sheets[raw];
                    }
                    if (rule instanceof RegExp && rule.test(n)) {
                        return workbook.Sheets[raw];
                    }
                }
            }
            return null;
        }

        function isHeaderRow(row) {
            if (!row || !row.length) return false;
            const joined = row.map(function (c) { return String(c || '').toLowerCase(); }).join(' ');
            return /họ|tên|name|email|mã|ms|mật khẩu|password|đơn vị|vai trò|loại/i.test(joined);
        }

        function looksLikeDataRow(row) {
            // Có ít nhất cột tên (A)
            return !!(row && cell(row, 0));
        }

        function mapRowToUser(row, index) {
            return {
                type: 'user',
                name: cell(row, 0),
                code: cell(row, 1) || ('USER_' + index),
                email: cell(row, 2),
                password: cell(row, 3) || 'password',
                unit_name: cell(row, 4),
                role_name: cell(row, 5),
                user_type: cell(row, 6) || 'internal_user',
            };
        }

        function mapRowToStudent(row, index) {
            return {
                type: 'student',
                name: cell(row, 0),
                code: cell(row, 1) || null,
                email: cell(row, 2),
                password: cell(row, 3) || 'password',
                unit_name: cell(row, 4),
                role_name: cell(row, 5) || 'student',
                user_type: 'student',
                // Cột H mã lớp; G có thể là loại user hoặc mã lớp tùy file
                class_code: cell(row, 7) || (cell(row, 6) && !/^(student|instructor|internal_user)$/i.test(cell(row, 6)) ? cell(row, 6) : '') || '',
            };
        }

        function parseSheetToRows(sheet, asType) {
            if (!sheet) return [];
            const jsonData = XLSX.utils.sheet_to_json(sheet, { header: 1, defval: '', raw: false });
            if (!jsonData || !jsonData.length) return [];

            let start = 0;
            if (isHeaderRow(jsonData[0])) start = 1;

            const out = [];
            for (let i = start; i < jsonData.length; i++) {
                const row = jsonData[i];
                if (!looksLikeDataRow(row)) continue;
                // Bỏ dòng hoàn toàn trống email+name đã check name; bỏ dòng mẫu hướng dẫn
                const name = cell(row, 0);
                if (/^họ và tên$|^họ tên$|^name$/i.test(name)) continue;

                if (asType === 'student') {
                    out.push(mapRowToStudent(row, i));
                } else {
                    out.push(mapRowToUser(row, i));
                }
            }
            return out;
        }

        function collectImportSheets(workbook) {
            const names = workbook.SheetNames || [];
            const dataSheets = [];
            names.forEach(function (raw) {
                const n = normalizeSheetName(raw);
                if (/hướng dẫn|huong dan|đơn vị|don vi|phân quyền|phan quyen|permission|lớp học|lop hoc|classes|danh mục/i.test(n)) {
                    return;
                }
                dataSheets.push({ name: raw, sheet: workbook.Sheets[raw], norm: n });
            });
            return dataSheets;
        }

        function getImportType() {
            if (IS_STUDENT_ONLY_IMPORT) return 'student';
            if (IS_USER_ONLY_IMPORT) return 'user';
            const modal = getImportModal();
            return modal?.querySelector('input[name="importType"]:checked')?.value || 'all';
        }

        function inferImportModeFromPath() {
            const path = (window.location.pathname || '').toLowerCase();
            if (path.indexOf('/students') !== -1) return 'student';
            if (path.indexOf('/users') !== -1) return 'user';
            return null;
        }

        function resolveImportModal(modeHint) {
            const mode = modeHint || IMPORT_MODE;
            return document.getElementById('importModal_' + mode)
                || document.querySelector('[data-import-modal][data-import-mode="' + mode + '"]')
                || document.getElementById(IMPORT_MODAL_ID)
                || null;
        }

        function getImportModal() {
            // Ưu tiên modal đúng mode trang hiện tại (tránh Turbo dính script mode khác)
            return resolveImportModal(IMPORT_MODE)
                || resolveImportModal(inferImportModeFromPath())
                || document.querySelector('[data-import-modal]');
        }

        function getImportClassSetup() {
            const modal = getImportModal();
            if (!modal) return null;
            const mode = modal.getAttribute('data-import-mode') || IMPORT_MODE;
            return document.getElementById('studentClassSetup_' + mode)
                || modal.querySelector('[data-import-class-setup]')
                || document.getElementById(IMPORT_CLASS_SETUP_ID)
                || null;
        }

        function getImportFileInput() {
            const modal = getImportModal();
            if (!modal) return null;
            const mode = modal.getAttribute('data-import-mode') || IMPORT_MODE;
            return document.getElementById('importFileInput_' + mode)
                || modal.querySelector('[data-import-file-input]')
                || document.getElementById(IMPORT_FILE_INPUT_ID)
                || null;
        }

        function ensureImportModalOnBody(modeHint) {
            const preferredMode = modeHint || IMPORT_MODE || inferImportModeFromPath() || 'user';
            let keep = resolveImportModal(preferredMode);

            // Fallback: bất kỳ modal import nào còn trong DOM
            if (!keep) {
                keep = document.querySelector('[data-import-modal]');
            }

            // Legacy
            if (!keep) {
                keep = document.getElementById('importModal');
            }

            if (!keep) return null;

            // Gỡ modal mode khác dính trên body (không đụng keep)
            document.querySelectorAll('[data-import-modal]').forEach(function (m) {
                if (m === keep) return;
                m.classList.add('hidden');
                m.classList.remove('is-open');
                m.style.display = 'none';
                if (m.parentElement === document.body) {
                    m.remove();
                }
            });
            document.querySelectorAll('#importModal').forEach(function (m) {
                if (m !== keep) m.remove();
            });

            if (keep.parentElement !== document.body) {
                document.body.appendChild(keep);
            }
            return keep;
        }

        function initImportClassSelects() {
            const setup = getImportClassSetup();
            if (!setup) return;
            if (IS_STUDENT_ONLY_IMPORT || getImportType() === 'student') {
                setup.classList.remove('hidden');
            }
            if (setup.classList.contains('hidden')) return;
            requestAnimationFrame(function () {
                if (typeof window.initTomSelects === 'function') {
                    window.initTomSelects(setup);
                }
                initImportDateInputs(setup);
            });
        }

        /** Dùng chung style Flatpickr + icon lịch như form admin (date-input-theme) */
        function initImportDateInputs(root) {
            const scope = root || getImportModal() || document;
            if (typeof window.initDateInputs === 'function') {
                window.initDateInputs(scope);
                return;
            }
            scope.querySelectorAll('input[type="date"][data-import-date], input[type="date"].date-input').forEach(function (input) {
                input.classList.add('date-input', 'date-input--ready');
            });
        }

        function openImportModalSelf() {
            const modal = ensureImportModalOnBody(IMPORT_MODE);
            if (!modal) {
                window.PortalPopup.error('Không tìm thấy form import. Hãy tải lại trang.');
                return;
            }
            modal.classList.remove('hidden');
            modal.classList.add('is-open');
            modal.style.display = 'block';
            modal.setAttribute('aria-hidden', 'false');
            document.documentElement.style.overflow = 'hidden';
            document.body.style.overflow = 'hidden';

            const mode = modal.getAttribute('data-import-mode') || IMPORT_MODE;
            if (mode === 'student' || IS_STUDENT_ONLY_IMPORT) {
                const setup = getImportClassSetup();
                if (setup) setup.classList.remove('hidden');
                initImportClassSelects();
            } else {
                handleImportTypeChange();
            }

            requestAnimationFrame(function () {
                initImportDateInputs(modal);
                if (mode === 'student' || IS_STUDENT_ONLY_IMPORT) initImportClassSelects();
            });
        }

        function closeImportModal() {
            const modal = getImportModal()
                || document.querySelector('[data-import-modal].is-open')
                || document.querySelector('[data-import-modal]');
            if (!modal) return;
            if (typeof window.closeAllTomSelects === 'function') {
                window.closeAllTomSelects();
            }
            modal.classList.add('hidden');
            modal.classList.remove('is-open');
            modal.style.display = 'none';
            modal.setAttribute('aria-hidden', 'true');
            document.documentElement.style.overflow = '';
            document.body.style.overflow = '';
            clearFile();
            hideMessage();
        }

        // Mở đúng modal theo mode (tránh Turbo giữ openImportModal của trang HV khi sang ND)
        function openImportModal(modeHint) {
            const hint = (typeof modeHint === 'string' && modeHint) ? modeHint : null;
            const mode = hint
                || inferImportModeFromPath()
                || IMPORT_MODE
                || 'user';

            const reg = window.UserImportRegistry && window.UserImportRegistry[mode];
            if (reg && typeof reg.openSelf === 'function') {
                return reg.openSelf();
            }

            // Registry mode khác nếu DOM còn modal
            if (window.UserImportRegistry) {
                const keys = Object.keys(window.UserImportRegistry);
                for (let i = 0; i < keys.length; i++) {
                    const k = keys[i];
                    if (document.getElementById('importModal_' + k) && window.UserImportRegistry[k].openSelf) {
                        return window.UserImportRegistry[k].openSelf();
                    }
                }
            }

            // Cuối cùng: mở modal bất kỳ còn trong DOM
            const any = ensureImportModalOnBody(mode);
            if (!any) {
                window.PortalPopup.error('Không tìm thấy form import. Hãy tải lại trang (Ctrl+F5).');
                return;
            }
            any.classList.remove('hidden');
            any.classList.add('is-open');
            any.style.display = 'block';
            any.setAttribute('aria-hidden', 'false');
            document.documentElement.style.overflow = 'hidden';
            document.body.style.overflow = 'hidden';
        }

        function handleImportTypeChange() {
            const importType = getImportType();
            const setup = getImportClassSetup();
            if (setup) {
                setup.classList.toggle('hidden', importType !== 'student');
            }
            if (importType === 'student') {
                initImportClassSelects();
                bindImportFormPreviewRefresh();
            } else if (typeof window.closeAllTomSelects === 'function') {
                window.closeAllTomSelects();
            }
            if (allParsedData.length > 0) {
                filterDataByImportType();
                displayDataPreview();
            }
        }

        function filterDataByImportType() {
            const importType = getImportType();

            if (importType === 'all') {
                parsedData = allParsedData.slice();
            } else if (importType === 'user') {
                parsedData = allParsedData.filter(function (item) { return item.type === 'user'; });
                // File chỉ có sheet HV / dòng gắn student → vẫn cho import như user nếu user cố chọn
                if (parsedData.length === 0 && allParsedData.length > 0) {
                    parsedData = allParsedData.map(function (row) {
                        return Object.assign({}, row, {
                            type: 'user',
                            user_type: row.user_type && row.user_type !== 'student' ? row.user_type : 'internal_user',
                        });
                    });
                }
            } else {
                // Chỉ học viên
                parsedData = allParsedData.filter(function (item) { return item.type === 'student'; });
                // File ghi vào sheet "người dùng" / sheet khác → vẫn coi là học viên để import được
                if (parsedData.length === 0 && allParsedData.length > 0) {
                    parsedData = allParsedData.map(function (row) {
                        return Object.assign({}, row, {
                            type: 'student',
                            user_type: 'student',
                            role_name: row.role_name || 'student',
                            class_code: row.class_code || '',
                        });
                    });
                }
            }

            const btn = document.getElementById('uploadBtn');
            if (btn) btn.disabled = parsedData.length === 0;
        }

        /** Giá trị bù từ form cấu hình lớp (khi cột file trống) */
        function getImportPreviewDefaults() {
            const importType = getImportType();
            if (importType !== 'student') return null;

            const unitEl = document.getElementById('import_unit_id');
            let unitName = '';
            if (unitEl) {
                const unitVal = typeof window.getSelectValue === 'function'
                    ? window.getSelectValue(unitEl)
                    : unitEl.value;
                if (unitVal) {
                    const opt = unitEl.querySelector('option[value="' + CSS.escape(String(unitVal)) + '"]');
                    unitName = (opt?.textContent || '').trim();
                }
            }

            return {
                unit_name: unitName,
                class_code: (document.getElementById('import_class_code')?.value || '').trim(),
                role_name: 'student',
            };
        }

        /** Ô xem trước: ưu tiên file, trống thì form; gắn nhãn "form" */
        function previewCellHtml(fileValue, formFallback) {
            const file = String(fileValue ?? '').trim();
            if (file) {
                return escapeHtml(file);
            }
            const fb = String(formFallback ?? '').trim();
            if (fb) {
                return '<span class="text-indigo-700" title="Lấy từ form cấu hình lớp">' +
                    escapeHtml(fb) +
                    '</span> <span class="inline-block text-[10px] leading-none px-1 py-0.5 rounded bg-indigo-100 text-indigo-700 align-middle">form</span>';
            }
            return '<span class="text-gray-400">—</span>';
        }

        function bindImportFormPreviewRefresh() {
            const ids = [
                'import_unit_id',
                'import_specialization_id',
                'import_classroom_id',
                'import_instructor_id',
                'import_class_code',
                'import_class_name',
            ];
            ids.forEach(function (id) {
                const el = document.getElementById(id);
                if (!el || el.dataset.previewBound === '1') return;
                el.dataset.previewBound = '1';
                const refresh = function () {
                    if (parsedData.length > 0) displayDataPreview();
                };
                el.addEventListener('change', refresh);
                el.addEventListener('input', refresh);
            });
        }

        function handleFileSelect(event) {
            try {
                const file = event?.target?.files?.[0] || event?.dataTransfer?.files?.[0] || null;
                if (!file) return;

                const name = (file.name || '').toLowerCase();
                if (!name.endsWith('.xlsx') && !name.endsWith('.xls')) {
                    showMessage('Vui lòng chọn file Excel (.xlsx hoặc .xls)', 'error');
                    return;
                }
                if (file.size > 5 * 1024 * 1024) {
                    showMessage('File quá lớn. Tối đa 5MB.', 'error');
                    return;
                }

                selectedFile = file;
                const modal = getImportModal();
                const prompt = modal?.querySelector('[data-import-upload-prompt]') || document.getElementById('uploadPrompt');
                const info = modal?.querySelector('[data-import-file-info]') || document.getElementById('fileInfo');
                const nameEl = modal?.querySelector('[data-import-file-name]') || document.getElementById('fileName');
                const sizeEl = modal?.querySelector('[data-import-file-size]') || document.getElementById('fileSize');

                prompt?.classList.add('hidden');
                info?.classList.remove('hidden');
                if (nameEl) nameEl.textContent = file.name;
                if (sizeEl) sizeEl.textContent = formatFileSize(file.size);
                hideMessage();
                parseExcelFile(file);
            } catch (err) {
                console.error('handleFileSelect', err);
                showMessage('Không đọc được file đã chọn. Thử lại hoặc tải lại trang.', 'error');
            }
        }

        function parseExcelFile(file) {
            if (typeof XLSX === 'undefined') {
                showMessage('Chưa tải được thư viện đọc Excel. Tải lại trang.', 'error');
                return;
            }

            const reader = new FileReader();
            showProgressBar();
            updateProgress(10, 'Đang đọc file...');

            reader.onload = function (e) {
                try {
                    updateProgress(30, 'Đang phân tích dữ liệu...');
                    const data = new Uint8Array(e.target.result);
                    const workbook = XLSX.read(data, { type: 'array' });
                    allParsedData = [];

                    const importType = getImportType();

                    // 1) Sheet chuẩn template
                    const userSheet = findWorkbookSheet(workbook, [
                        'Dữ liệu người dùng',
                        /người dùng|nguoi dung|\buser\b|cán bộ|can bo/i,
                    ]);
                    const studentSheet = findWorkbookSheet(workbook, [
                        'Dữ liệu học viên',
                        /học viên|hoc vien|\bstudent\b|sinh viên|sinh vien/i,
                    ]);

                    if (userSheet) {
                        allParsedData = allParsedData.concat(parseSheetToRows(userSheet, 'user'));
                    }
                    if (studentSheet && studentSheet !== userSheet) {
                        allParsedData = allParsedData.concat(parseSheetToRows(studentSheet, 'student'));
                    }

                    // 2) Chưa có dòng → quét mọi sheet dữ liệu còn lại
                    if (allParsedData.length === 0) {
                        const dataSheets = collectImportSheets(workbook);
                        const preferStudent = importType === 'student';
                        dataSheets.forEach(function (item) {
                            const asStudent = preferStudent
                                || /học viên|hoc vien|student|sinh viên/i.test(item.norm);
                            const rows = parseSheetToRows(item.sheet, asStudent ? 'student' : 'user');
                            allParsedData = allParsedData.concat(rows);
                        });
                    }

                    // 3) Vẫn trống → sheet đầu tiên có dữ liệu (kể cả tên lạ)
                    if (allParsedData.length === 0 && workbook.SheetNames && workbook.SheetNames.length) {
                        for (let s = 0; s < workbook.SheetNames.length; s++) {
                            const sh = workbook.Sheets[workbook.SheetNames[s]];
                            const asType = importType === 'user' ? 'user' : 'student';
                            const rows = parseSheetToRows(sh, asType);
                            if (rows.length) {
                                allParsedData = rows;
                                break;
                            }
                        }
                    }

                    // 4) Loại trùng email trong cùng batch (giữ dòng sau)
                    const byEmail = {};
                    allParsedData.forEach(function (row) {
                        const key = (row.email || '').toLowerCase() || ('__name__' + (row.name || '') + '__' + (row.code || ''));
                        byEmail[key] = row;
                    });
                    allParsedData = Object.keys(byEmail).map(function (k) { return byEmail[k]; });

                    filterDataByImportType();
                    updateProgress(70, 'Đã đọc ' + allParsedData.length + ' dòng · dùng ' + parsedData.length + ' bản ghi');

                    if (allParsedData.length === 0) {
                        showMessage('File không có dữ liệu hợp lệ (cần ít nhất cột Họ tên). Kiểm tra lại sheet và dòng dữ liệu.', 'error');
                        hideProgressBar();
                        displayDataPreview();
                        const btnEmpty = document.getElementById('uploadBtn');
                        if (btnEmpty) btnEmpty.disabled = true;
                        return;
                    }

                    // Đã có dữ liệu → luôn cho xem trước + bật nút (filter đã convert type nếu cần)
                    displayDataPreview();
                    updateProgress(100, 'Sẵn sàng import (' + parsedData.length + ' bản ghi)');
                    const uploadBtn = document.getElementById('uploadBtn');
                    if (uploadBtn) uploadBtn.disabled = parsedData.length === 0;
                    setTimeout(function () { hideProgressBar(); }, 300);

                    if (parsedData.length === 0) {
                        showMessage('Không còn bản ghi sau khi lọc. Thử chọn "Nhập tất cả" hoặc kiểm tra file.', 'warning');
                    } else if (importType === 'student' && allParsedData.some(function (r) { return r.type === 'user'; })) {
                        // Đã auto-convert: nhắc nhẹ
                        const onlyUsers = allParsedData.every(function (r) { return r.type !== 'student'; });
                        if (onlyUsers) {
                            showMessage('Đã nhận ' + parsedData.length + ' dòng từ file và gán loại Học viên (form lớp sẽ bù đơn vị/mã lớp nếu trống).', 'success');
                        }
                    }
                } catch (error) {
                    console.error(error);
                    showMessage('Lỗi đọc Excel: ' + error.message, 'error');
                    hideProgressBar();
                }
            };

            reader.onerror = function () {
                showMessage('Không đọc được file.', 'error');
                hideProgressBar();
            };
            reader.readAsArrayBuffer(file);
        }

        function displayDataPreview() {
            const tbody = document.getElementById('previewTableBody');
            const preview = document.getElementById('dataPreview');
            if (!tbody || !preview) return;

            tbody.innerHTML = '';
            const defaults = getImportPreviewDefaults();
            const hint = document.getElementById('previewFormHint');
            if (hint) {
                hint.classList.toggle('hidden', !defaults);
            }

            if (!parsedData.length) {
                document.getElementById('totalRows').textContent = '0';
                preview.classList.remove('hidden');
                tbody.innerHTML = '<tr><td colspan="8" class="px-3 py-6 text-center text-gray-500 text-sm">Không có bản ghi để xem trước.</td></tr>';
                return;
            }

            parsedData.forEach((row, index) => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-gray-50';
                const isStudent = row.type === 'student';
                const typeLabel = isStudent
                    ? '<span class="px-2 py-0.5 bg-purple-100 text-purple-800 rounded text-xs">Học viên</span>'
                    : '<span class="px-2 py-0.5 bg-blue-100 text-blue-800 rounded text-xs">Người dùng</span>';

                // HV + form: cột trống hiện giá trị form (nhãn "form")
                const useForm = isStudent && defaults;
                const unitHtml = useForm
                    ? previewCellHtml(row.unit_name, defaults.unit_name)
                    : (row.unit_name ? escapeHtml(row.unit_name) : '<span class="text-gray-400">—</span>');
                const roleHtml = useForm
                    ? previewCellHtml(row.role_name, defaults.role_name)
                    : (row.role_name ? escapeHtml(row.role_name) : '<span class="text-gray-400">—</span>');
                const classHtml = useForm
                    ? previewCellHtml(row.class_code, defaults.class_code)
                    : (row.class_code ? escapeHtml(row.class_code) : '<span class="text-gray-400">—</span>');

                tr.innerHTML = `
                    <td class="px-3 py-2">${index + 1}</td>
                    <td class="px-3 py-2">${typeLabel}</td>
                    <td class="px-3 py-2">${escapeHtml(row.name) || '<span class="text-gray-400">—</span>'}</td>
                    <td class="px-3 py-2 font-mono text-xs">${row.code ? escapeHtml(row.code) : '<span class="text-gray-400">—</span>'}</td>
                    <td class="px-3 py-2">${escapeHtml(row.email) || '<span class="text-gray-400">—</span>'}</td>
                    <td class="px-3 py-2">${unitHtml}</td>
                    <td class="px-3 py-2">${roleHtml}</td>
                    <td class="px-3 py-2 font-mono text-xs">${classHtml}</td>`;
                tbody.appendChild(tr);
            });

            document.getElementById('totalRows').textContent = String(parsedData.length);
            preview.classList.remove('hidden');
            bindImportFormPreviewRefresh();
        }

        function escapeHtml(str) {
            return String(str ?? '')
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function hideDataPreview() {
            const preview = document.getElementById('dataPreview');
            if (preview) preview.classList.add('hidden');
            const tbody = document.getElementById('previewTableBody');
            if (tbody) tbody.innerHTML = '';
            document.getElementById('previewFormHint')?.classList.add('hidden');
            // Không xóa parsedData/allParsedData ở đây — clearFile lo việc đó
        }

        function clearFile() {
            selectedFile = null;
            parsedData = [];
            allParsedData = [];
            const fi = getImportFileInput();
            if (fi) fi.value = '';
            const modal = getImportModal();
            (modal?.querySelector('[data-import-upload-prompt]') || document.getElementById('uploadPrompt'))
                ?.classList.remove('hidden');
            (modal?.querySelector('[data-import-file-info]') || document.getElementById('fileInfo'))
                ?.classList.add('hidden');
            const btn = modal?.querySelector('#uploadBtn') || document.getElementById('uploadBtn');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="bi bi-upload mr-1"></i> Nhập dữ liệu';
            }
            hideProgressBar();
            hideDataPreview();
        }

        function formatFileSize(bytes) {
            if (!bytes) return '0 Bytes';
            const k = 1024;
            const sizes = ['Bytes', 'KB', 'MB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
        }

        function importSelectValue(id) {
            if (typeof window.getSelectValue === 'function') {
                return window.getSelectValue(id) || null;
            }
            return document.getElementById(id)?.value || null;
        }

        function buildClassSetup() {
            const importType = getImportType();
            if (importType !== 'student') return null;

            return {
                unit_id: importSelectValue('import_unit_id'),
                specialization_id: importSelectValue('import_specialization_id'),
                management_unit: document.getElementById('import_management_unit')?.value || null,
                classroom_id: importSelectValue('import_classroom_id'),
                class_name: document.getElementById('import_class_name')?.value || null,
                class_code: (document.getElementById('import_class_code')?.value || '').trim(),
                instructor_id: importSelectValue('import_instructor_id'),
                start_date: document.getElementById('import_start_date')?.value || null,
                end_date: document.getElementById('import_end_date')?.value || null,
                duration_months: document.getElementById('import_duration_months')?.value || null,
                max_students: document.getElementById('import_max_students')?.value || null,
            };
        }

        function validateStudentClassSetup(setup) {
            if (!setup) return 'Thiếu cấu hình lớp.';
            if (!setup.unit_id) return 'Chọn khoa/đơn vị cho lớp.';
            if (!setup.specialization_id) return 'Chọn ngành đào tạo cho lớp.';
            if (!setup.class_name) return 'Nhập tên lớp.';
            if (!setup.class_code) return 'Nhập mã lớp.';
            return null;
        }

        function submitImport() {
            if (!parsedData.length) {
                showMessage('Không có dữ liệu để nhập. Hãy chọn file Excel.', 'error');
                return;
            }

            const importType = getImportType();
            let classSetup = null;
            if (importType === 'student') {
                classSetup = buildClassSetup();
                const err = validateStudentClassSetup(classSetup);
                if (err) {
                    showMessage(err, 'error');
                    if (window.Notify) Notify.warning(err);
                    return;
                }
                // Cột trống trong file → bỏ qua, bù từ form cấu hình lớp
                const unitLabel = document.querySelector('#import_unit_id option:checked')?.textContent?.trim() || '';
                parsedData = parsedData.map(row => {
                    const unitName = (row.unit_name || '').trim();
                    const roleName = (row.role_name || '').trim();
                    const classCode = (row.class_code || '').trim();
                    const password = (row.password || '').trim();
                    return {
                        ...row,
                        type: 'student',
                        user_type: 'student',
                        // Cột trống → dùng form / default, không gửi rỗng gây lỗi
                        unit_name: unitName || unitLabel || null,
                        role_name: roleName || 'student',
                        class_code: classCode || classSetup.class_code || null,
                        password: password || 'password',
                    };
                });
            }

            const uploadBtn = document.getElementById('uploadBtn');
            uploadBtn.disabled = true;
            uploadBtn.innerHTML = '<i class="bi bi-hourglass-split mr-1"></i> Đang xử lý...';
            showProgressBar();
            updateProgress(20, 'Đang gửi dữ liệu...');

            const token = document.querySelector('#importForm input[name="_token"]')?.value
                || document.querySelector('meta[name="csrf-token"]')?.content
                || '{{ csrf_token() }}';

            const payload = { users: parsedData };
            if (classSetup) payload.class_setup = classSetup;

            fetch(@json(route('users.import')), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: JSON.stringify(payload),
            })
                .then(async (response) => {
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        throw new Error(data.message || 'Import thất bại (' + response.status + ')');
                    }
                    return data;
                })
                .then((data) => {
                    updateProgress(100, 'Hoàn thành!');
                    if (data.success) {
                        let message = data.message || 'Nhập dữ liệu thành công!';
                        if (data.data?.errors?.length) {
                            message += '<br><ul class="list-disc pl-5 mt-2">';
                            data.data.errors.slice(0, 15).forEach(e => {
                                message += `<li>Dòng ${e.row}: ${escapeHtml(e.message)}</li>`;
                            });
                            message += '</ul>';
                            showMessage(message, 'warning');
                        } else {
                            showMessage(message, 'success');
                        }
                        if (window.Notify) Notify.success(data.message || 'Import thành công');
                        setTimeout(() => {
                            if (IMPORT_RELOAD_URL) {
                                window.location.href = IMPORT_RELOAD_URL;
                            } else {
                                window.location.reload();
                            }
                        }, 1800);
                    } else {
                        showMessage(data.message || 'Có lỗi khi nhập dữ liệu', 'error');
                        uploadBtn.disabled = false;
                        uploadBtn.innerHTML = '<i class="bi bi-upload mr-1"></i> Nhập dữ liệu';
                    }
                })
                .catch((error) => {
                    console.error(error);
                    showMessage(error.message || 'Có lỗi xảy ra. Vui lòng thử lại.', 'error');
                    if (window.Notify) Notify.error(error.message || 'Import lỗi');
                    uploadBtn.disabled = false;
                    uploadBtn.innerHTML = '<i class="bi bi-upload mr-1"></i> Nhập dữ liệu';
                    hideProgressBar();
                });
        }

        function showProgressBar() {
            document.getElementById('progressBar')?.classList.remove('hidden');
        }
        function hideProgressBar() {
            document.getElementById('progressBar')?.classList.add('hidden');
            const fill = document.getElementById('progressFill');
            if (fill) fill.style.width = '0%';
        }
        function updateProgress(percent, text) {
            const fill = document.getElementById('progressFill');
            const t = document.getElementById('progressText');
            if (fill) fill.style.width = percent + '%';
            if (t) t.textContent = text || '';
        }
        function showMessage(message, type) {
            const messageBox = document.getElementById('messageBox');
            if (!messageBox) return;
            messageBox.classList.remove('hidden');
            const map = {
                success: 'bg-green-50 border border-green-200 text-green-800',
                warning: 'bg-yellow-50 border border-yellow-200 text-yellow-800',
                error: 'bg-red-50 border border-red-200 text-red-800',
            };
            messageBox.className = 'mb-4 p-4 rounded-lg text-sm ' + (map[type] || map.error);
            messageBox.innerHTML = message;
        }
        function hideMessage() {
            document.getElementById('messageBox')?.classList.add('hidden');
        }

        // Registry theo mode — mỗi trang (user/student) đăng ký handler riêng
        window.UserImportRegistry = window.UserImportRegistry || {};
        window.UserImportRegistry[IMPORT_MODE] = {
            mode: IMPORT_MODE,
            modalId: IMPORT_MODAL_ID,
            fileInputId: IMPORT_FILE_INPUT_ID,
            openSelf: openImportModalSelf,
            close: closeImportModal,
            clearFile: clearFile,
            handleFileSelect: handleFileSelect,
            submit: submitImport,
            getFileInput: getImportFileInput,
            ensureModal: ensureImportModalOnBody,
        };

        // API “active” = mode script vừa load (trang hiện tại)
        window.UserImport = window.UserImportRegistry[IMPORT_MODE];

        // Global open: luôn chọn đúng mode theo nút / URL / registry
        window.openImportModal = openImportModal;
        window.closeImportModal = closeImportModal;
        window.handleImportTypeChange = handleImportTypeChange;

        function bindImportFileHandlers() {
            ensureImportModalOnBody();
            if (IS_STUDENT_ONLY_IMPORT) {
                const setup = getImportClassSetup();
                if (setup) setup.classList.remove('hidden');
            }
            // Gắn change trực tiếp lên input hiện tại (ổn định hơn delegation thuần)
            const fi = getImportFileInput();
            if (fi && fi.dataset.importBound !== '1') {
                fi.dataset.importBound = '1';
                fi.addEventListener('change', function (e) {
                    window.UserImport?.handleFileSelect?.(e);
                });
            }
        }

        if (!window.__userImportHandlersBound) {
            window.__userImportHandlersBound = true;

            // Capture phase: không bị stopPropagation từ UI khác
            document.addEventListener('change', function (e) {
                const t = e.target;
                if (!t) return;
                if (t.matches?.('[data-import-file-input]') || (t.id && String(t.id).startsWith('importFileInput_'))) {
                    window.UserImport?.handleFileSelect?.(e);
                }
            }, true);

            document.addEventListener('click', function (e) {
                const openBtn = e.target.closest('[data-import-open]');
                if (openBtn) {
                    e.preventDefault();
                    const mode = openBtn.getAttribute('data-import-open') || null;
                    window.openImportModal?.(mode);
                    return;
                }

                const clearBtn = e.target.closest('[data-import-clear-btn], #clearFileBtn');
                if (clearBtn) {
                    e.preventDefault();
                    e.stopPropagation();
                    window.UserImport?.clearFile?.();
                    return;
                }

                const uploadBtn = e.target.closest('#uploadBtn');
                if (uploadBtn) {
                    e.preventDefault();
                    window.UserImport?.submit?.();
                    return;
                }

                // Click vùng drop (không phải label/nút) → kích hoạt input
                const dropZone = e.target.closest('[data-import-dropzone], #dropZone');
                if (dropZone && !e.target.closest('label, button, a, input, select, textarea')) {
                    const fi = window.UserImport?.getFileInput?.() || getImportFileInput();
                    if (fi) {
                        // Không preventDefault trên label path; chỉ trigger khi click nền
                        try { fi.value = ''; } catch (_) {}
                        fi.click();
                    }
                    return;
                }

                if (e.target.closest('[data-import-backdrop]')) {
                    window.UserImport?.close?.();
                }
            }, true);

            document.addEventListener('dragover', function (e) {
                const dropZone = e.target.closest('[data-import-dropzone], #dropZone');
                if (!dropZone) return;
                e.preventDefault();
                dropZone.classList.add('border-green-500', 'bg-green-50');
            });

            document.addEventListener('dragleave', function (e) {
                const dropZone = e.target.closest('[data-import-dropzone], #dropZone');
                if (!dropZone) return;
                dropZone.classList.remove('border-green-500', 'bg-green-50');
            });

            document.addEventListener('drop', function (e) {
                const dropZone = e.target.closest('[data-import-dropzone], #dropZone');
                if (!dropZone) return;
                e.preventDefault();
                dropZone.classList.remove('border-green-500', 'bg-green-50');
                const files = e.dataTransfer?.files;
                if (files?.length) {
                    const fi = window.UserImport?.getFileInput?.() || getImportFileInput();
                    if (fi) {
                        try {
                            const dt = new DataTransfer();
                            dt.items.add(files[0]);
                            fi.files = dt.files;
                        } catch (_) {}
                    }
                    window.UserImport?.handleFileSelect?.({ target: { files } });
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key !== 'Escape') return;
                const openModal = document.querySelector('[data-import-modal].is-open, [data-import-modal]:not(.hidden)');
                if (openModal && openModal.style.display !== 'none' && !openModal.classList.contains('hidden')) {
                    window.UserImport?.close?.();
                }
            });

            document.addEventListener('turbo:load', bindImportFileHandlers);
            document.addEventListener('turbo:render', bindImportFileHandlers);
            document.addEventListener('turbo:before-cache', function () {
                // Đưa modal về trạng thái đóng trước khi Turbo cache trang
                try { window.UserImport?.close?.(); } catch (_) {}
            });
        }

        // Chạy ngay (Turbo không re-fire DOMContentLoaded)
        bindImportFileHandlers();
    </script>
@endpush
