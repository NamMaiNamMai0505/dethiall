@extends('layouts.admin')

@section('title', $specialization->name)
@section('page-title', 'Chi tiết Ngành đào tạo')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Ngành đào tạo', 'url' => route('specializations.index')],
    ['title' => is_array($specialization->name) ? implode(', ', $specialization->name) : $specialization->name]
]" />

{{-- Page Header --}}
<div class="flex justify-between items-center mb-6">
    <div>
        <div class="flex items-center">
            <h1 class="text-2xl font-bold text-gray-900 mr-3">{{ is_array($specialization->name) ? implode(', ', $specialization->name) : $specialization->name }}</h1>
            <x-status-badge :is-active="$specialization->is_active" size="lg" />
        </div>
        <p class="text-gray-600 mt-1">Thông tin chi tiết về ngành đào tạo</p>
    </div>
    <div class="flex space-x-2">
        <a href="{{ route('specializations.index') }}"
           class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
            <i class="bi bi-arrow-left mr-2"></i>Quay lại
        </a>
        <a href="{{ route('specializations.edit', $specialization) }}"
           class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg font-medium">
            <i class="bi bi-pencil mr-2"></i>Chỉnh sửa
        </a>
        <a href="{{ route('subjects.index', ['specialization_id' => $specialization->id]) }}"
           class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
            <i class="bi bi-book mr-2"></i>Môn học
        </a>
        <a href="{{ route('subject-lessons.index', ['specialization_id' => $specialization->id]) }}"
           class="bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 rounded-lg font-medium">
            <i class="bi bi-list-check mr-2"></i>Bài học
        </a>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Main Content --}}
    <div class="lg:col-span-2 space-y-6">

        {{-- Basic Information --}}
        <div class="bg-white rounded-lg shadow-sm border">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">
                    <i class="bi bi-info-circle text-blue-500 mr-2"></i>
                    Thông tin cơ bản
                </h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Mã ngành</label>
                        <div class="flex items-center">
                            <code class="bg-blue-100 text-blue-800 px-3 py-2 rounded-lg text-lg font-mono">{{ $specialization->major_code ?? '—' }}</code>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Hệ đào tạo</label>
                        <div class="inline-flex items-center rounded-full bg-teal-50 px-3 py-2 font-semibold text-teal-700 ring-1 ring-inset ring-teal-200">
                            <i class="bi bi-layers mr-2"></i>
                            {{ $specialization->trainingSystem?->name ?? 'Chưa xác định' }}
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Trạng thái</label>
                        <div>
                            <x-status-badge :is-active="$specialization->is_active" size="lg" />
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Trình độ đào tạo</label>
                        <div>
                            <x-level-badge :level="$specialization->level" :text="is_array($specialization->level_text) ? implode(', ', $specialization->level_text) : $specialization->level_text" size="lg" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Thời gian đào tạo</label>
                        <div class="flex items-center">
                            <span class="text-2xl font-bold text-blue-600">{{ $specialization->duration_months }}</span>
                            <span class="text-gray-500 ml-2">tháng</span>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-500 mb-1">Hình thức đào tạo</label>
                        <div class="inline-flex items-center rounded-full bg-violet-50 px-3 py-2 font-semibold text-violet-700 ring-1 ring-inset ring-violet-200">
                            <i class="bi bi-mortarboard mr-2"></i>
                            {{ $specialization->training_form_text }}
                        </div>
                    </div>
                </div>

                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-500 mb-1">Loại chứng chỉ</label>
                    <div>
                        <x-certification-badge :type="$specialization->certification_type" :text="is_array($specialization->certification_type_text) ? implode(', ', $specialization->certification_type_text) : $specialization->certification_type_text" size="lg" />
                    </div>
                </div>

                @if($specialization->description)
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-500 mb-2">Mô tả</label>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-gray-800">{{ is_array($specialization->description) ? json_encode($specialization->description) : $specialization->description }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>

        {{-- Prerequisites --}}
        @if($specialization->prerequisites && count($specialization->prerequisites) > 0)
        <div class="bg-white rounded-lg shadow-sm border">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">
                    <i class="bi bi-list-check text-orange-500 mr-2"></i>
                    Điều kiện tiên quyết
                </h3>
            </div>
            <div class="p-6">
                <div class="space-y-3">
                    @foreach($specialization->prerequisites as $prerequisite)
                    <div class="flex items-start">
                        <i class="bi bi-check-circle text-green-500 mr-3 mt-0.5"></i>
                        <span class="text-gray-800">{{ is_array($prerequisite) ? implode(', ', $prerequisite) : $prerequisite }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif

        {{-- Actions --}}
        <div class="bg-white rounded-lg shadow-sm border">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">
                    <i class="bi bi-gear text-gray-500 mr-2"></i>
                    Thao tác
                </h3>
            </div>
            <div class="p-6">
                <div class="flex flex-wrap gap-3">
                    {{-- Edit --}}
                    <a href="{{ route('specializations.edit', $specialization) }}"
                       class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg font-medium">
                        <i class="bi bi-pencil mr-2"></i>Chỉnh sửa
                    </a>

                    {{-- Toggle Status --}}
                    <form method="POST"
                          action="{{ route('specializations.toggle-status', $specialization) }}"
                          class="inline"
                          data-confirm="Bạn có chắc chắn muốn {{ $specialization->is_active ? 'tạm dừng' : 'kích hoạt' }} ngành đào tạo này?">
                        @csrf
                        @method('PATCH')
                        <button type="submit"
                                class="bg-{{ $specialization->is_active ? 'red' : 'green' }}-600 hover:bg-{{ $specialization->is_active ? 'red' : 'green' }}-700 text-white px-4 py-2 rounded-lg font-medium">
                            <i class="bi bi-{{ $specialization->is_active ? 'pause' : 'play' }} mr-2"></i>
                            {{ $specialization->is_active ? 'Tạm dừng' : 'Kích hoạt' }}
                        </button>
                    </form>

                    {{-- Delete --}}
                    <form method="POST"
                          action="{{ route('specializations.destroy', $specialization) }}"
                          class="inline"
                          data-confirm="Xóa ngành này sẽ xóa luôn TOÀN BỘ môn học và bài học thuộc ngành. Bạn có chắc chắn muốn xóa?"
                          data-confirm-danger="1"
                          data-confirm-ok="Xóa">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium">
                            <i class="bi bi-trash mr-2"></i>Xóa
                        </button>
                    </form>

                    {{-- Duplicate --}}
                    <a href="{{ route('specializations.create', ['duplicate' => $specialization->id]) }}"
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium"
                       title="Tạo bản sao">
                        <i class="bi bi-copy mr-2"></i>Sao chép
                    </a>

                    {{-- Import Subjects --}}
                    @if(auth()->check() && auth()->user()->can('subjects.edit'))
                    <button type="button"
                            onclick="openImportModal()"
                            class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium">
                        <i class="bi bi-upload mr-2"></i>Nhập môn học
                    </button>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- Sidebar --}}
    <div class="lg:col-span-1 space-y-6">

        {{-- Statistics --}}
        <div class="bg-white rounded-lg shadow-sm border">
            <div class="px-4 py-3 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">
                    <i class="bi bi-bar-chart text-blue-500 mr-2"></i>
                    Thống kê
                </h3>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-2 gap-4 text-center">
                    <div class="bg-blue-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600">{{ $specialization->subjects_count ?? 0 }}</div>
                        <div class="text-sm text-blue-600">Môn học</div>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg">
                        <div class="text-2xl font-bold text-green-600">0</div>
                        <div class="text-sm text-green-600">Học viên</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Metadata --}}
        <x-metadata-card
            title="Thông tin hệ thống"
            icon="info"
            icon-color="gray"
            :data="[
                ['label' => 'ID', 'value' => $specialization->id],
                ['label' => 'Người tạo', 'value' => is_array($specialization->creator->name ?? null) ? implode(', ', $specialization->creator->name) : ($specialization->creator->name ?? 'N/A')],
                ['label' => 'Ngày tạo', 'value' => $specialization->created_at->format('d/m/Y H:i')],
                ...($specialization->updater && $specialization->updated_at != $specialization->created_at ? [
                    ['label' => 'Cập nhật cuối', 'value' => $specialization->updated_at->format('d/m/Y H:i')],
                    ['label' => 'Người cập nhật', 'value' => is_array($specialization->updater->name ?? null) ? implode(', ', $specialization->updater->name) : ($specialization->updater->name ?? null)]
                ] : [])
            ]" />

        {{-- Related Links --}}
        <div class="bg-white rounded-lg shadow-sm border">
            <div class="px-4 py-3 border-b border-gray-200">
                <h3 class="text-lg font-medium text-gray-900">
                    <i class="bi bi-link text-blue-500 mr-2"></i>
                    Liên kết
                </h3>
            </div>
            <div class="p-4">
                <div class="space-y-2">
                    <a href="#" class="block w-full text-left px-3 py-2 text-gray-400 bg-gray-50 rounded-lg cursor-not-allowed">
                        <i class="bi bi-book mr-2"></i>Khóa học liên quan
                    </a>
                    <a href="#" class="block w-full text-left px-3 py-2 text-gray-400 bg-gray-50 rounded-lg cursor-not-allowed">
                        <i class="bi bi-people mr-2"></i>Học viên
                    </a>
                    <a href="#" class="block w-full text-left px-3 py-2 text-gray-400 bg-gray-50 rounded-lg cursor-not-allowed">
                        <i class="bi bi-award mr-2"></i>Chứng chỉ
                    </a>
                    <a href="#" class="block w-full text-left px-3 py-2 text-gray-400 bg-gray-50 rounded-lg cursor-not-allowed">
                        <i class="bi bi-graph-up mr-2"></i>Báo cáo
                    </a>
                </div>
                <div class="mt-3 text-xs text-gray-500 italic">
                    Các tính năng này sẽ được phát triển trong tương lai
                </div>
            </div>
        </div>

    </div>
</div>

{{-- Import Modal --}}
<div id="importModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
        {{-- Modal Header --}}
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
            <h3 class="text-xl font-semibold text-gray-900">
                <i class="bi bi-upload text-green-600 mr-2"></i>
                Nhập môn học từ Excel
            </h3>
            <button type="button" onclick="closeImportModal()" class="text-gray-400 hover:text-gray-600">
                <i class="bi bi-x text-2xl"></i>
            </button>
        </div>

        {{-- Modal Body --}}
        <div class="p-6">
            {{-- Download Template Section --}}
            <div class="mb-6 bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="bi bi-file-earmark-excel text-blue-600 text-2xl mr-3"></i>
                        <div>
                            <p class="font-medium text-blue-900">Tải xuống file mẫu</p>
                            <p class="text-sm text-blue-700">Sử dụng file mẫu để đảm bảo định dạng đúng</p>
                        </div>
                    </div>
                    <a href="{{ route('subjects.import.template') }}"
                       class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">
                        <i class="bi bi-download mr-2"></i>Tải mẫu
                    </a>
                </div>
            </div>

            {{-- Upload Section --}}
            <form id="importForm" enctype="multipart/form-data">
                @csrf
                <div class="mb-4">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Chọn file Excel để nhập
                    </label>

                    {{-- Drag & Drop Zone --}}
                    <div id="dropZone"
                         class="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center hover:border-green-500 transition-colors cursor-pointer">
                        <input type="file"
                               id="fileInput"
                               name="file"
                               accept=".xlsx,.xls"
                               class="hidden"
                               onchange="handleFileSelect(event)">

                        <div id="uploadPrompt">
                            <i class="bi bi-cloud-upload text-4xl text-gray-400 mb-3"></i>
                            <p class="text-gray-600 mb-2">Kéo thả file vào đây hoặc</p>
                            <button type="button"
                                    onclick="document.getElementById('fileInput').click()"
                                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg font-medium">
                                Chọn file
                            </button>
                            <p class="text-sm text-gray-500 mt-2">Hỗ trợ: .xlsx, .xls (Tối đa 5MB)</p>
                        </div>

                        <div id="fileInfo" class="hidden">
                            <i class="bi bi-file-earmark-excel text-green-600 text-4xl mb-3"></i>
                            <p class="text-gray-800 font-medium" id="fileName"></p>
                            <p class="text-sm text-gray-500" id="fileSize"></p>
                            <button type="button"
                                    onclick="clearFile()"
                                    class="mt-3 text-red-600 hover:text-red-800 text-sm">
                                <i class="bi bi-x-circle mr-1"></i>Xóa file
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Progress Bar --}}
                <div id="progressBar" class="hidden mb-4">
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div id="progressFill" class="bg-green-600 h-2.5 rounded-full transition-all" style="width: 0%"></div>
                    </div>
                    <p id="progressText" class="text-sm text-gray-600 mt-2 text-center"></p>
                </div>

                {{-- Error/Success Messages --}}
                <div id="messageBox" class="hidden mb-4 p-4 rounded-lg"></div>

                {{-- Data Preview Table --}}
                <div id="dataPreview" class="hidden mt-4">
                    <h4 class="font-semibold text-gray-900 mb-3">Xem trước dữ liệu (<span id="totalRows">0</span> môn học)</h4>
                    <div class="border border-gray-300 rounded-lg overflow-hidden max-h-96 overflow-y-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 sticky top-0">
                                <tr>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">STT</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Mã MH</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tên môn học</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tín chỉ</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Lý thuyết</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Thực hành</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Tự học</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Thi/KT</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cấp độ</th>
                                    <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase">Học kỳ</th>
                                </tr>
                            </thead>
                            <tbody id="previewTableBody" class="bg-white divide-y divide-gray-200">
                                {{-- Data will be inserted here --}}
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>
        </div>

        {{-- Modal Footer --}}
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3 rounded-b-lg">
            <button type="button"
                    onclick="closeImportModal()"
                    class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg font-medium">
                <i class="bi bi-x mr-2"></i>Đóng
            </button>
            <button type="button"
                    onclick="submitImport()"
                    id="uploadBtn"
                    disabled
                    class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium disabled:bg-gray-400 disabled:cursor-not-allowed">
                <i class="bi bi-upload mr-2"></i>Nhập dữ liệu
            </button>
        </div>
    </div>
</div>

{{-- SheetJS Library for Excel parsing --}}
<script src="https://cdn.sheetjs.com/xlsx-0.20.1/package/dist/xlsx.full.min.js"></script>

<script>
    let selectedFile = null;
    let parsedData = [];

    function openImportModal() {
        document.getElementById('importModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeImportModal() {
        document.getElementById('importModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
        clearFile();
        hideMessage();
        hideDataPreview();
    }

    // Drag and drop handlers
    const dropZone = document.getElementById('dropZone');

    dropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropZone.classList.add('border-green-500', 'bg-green-50');
    });

    dropZone.addEventListener('dragleave', () => {
        dropZone.classList.remove('border-green-500', 'bg-green-50');
    });

    dropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('border-green-500', 'bg-green-50');

        const files = e.dataTransfer.files;
        if (files.length > 0) {
            document.getElementById('fileInput').files = files;
            handleFileSelect({ target: { files: files } });
        }
    });

    function handleFileSelect(event) {
        const file = event.target.files[0];
        if (!file) return;

        // Validate file type
        const validTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'];
        if (!validTypes.includes(file.type) && !file.name.endsWith('.xlsx') && !file.name.endsWith('.xls')) {
            showMessage('Vui lòng chọn file Excel (.xlsx hoặc .xls)', 'error');
            return;
        }

        // Validate file size (5MB)
        if (file.size > 5 * 1024 * 1024) {
            showMessage('File quá lớn. Vui lòng chọn file nhỏ hơn 5MB', 'error');
            return;
        }

        selectedFile = file;

        // Update UI
        document.getElementById('uploadPrompt').classList.add('hidden');
        document.getElementById('fileInfo').classList.remove('hidden');
        document.getElementById('fileName').textContent = file.name;
        document.getElementById('fileSize').textContent = formatFileSize(file.size);
        hideMessage();

        // Parse Excel file
        parseExcelFile(file);
    }

    function parseExcelFile(file) {
        const reader = new FileReader();

        showProgressBar();
        updateProgress(10, 'Đang đọc file...');

        reader.onload = function(e) {
            try {
                updateProgress(30, 'Đang phân tích dữ liệu...');

                const data = new Uint8Array(e.target.result);
                const workbook = XLSX.read(data, { type: 'array' });

                // Find the data sheet by name, fallback to first sheet
                let dataSheetName = 'Dữ liệu môn học';
                let sheetToRead = workbook.Sheets[dataSheetName];

                // If named sheet not found, try first sheet
                if (!sheetToRead) {
                    sheetToRead = workbook.Sheets[workbook.SheetNames[0]];
                }

                // Convert to JSON
                const jsonData = XLSX.utils.sheet_to_json(sheetToRead, { header: 1 });

                // Skip header row and parse data
                parsedData = [];
                for (let i = 1; i < jsonData.length; i++) {
                    const row = jsonData[i];

                    // Skip empty rows
                    if (!row || row.length === 0 || !row[1]) continue;

                    parsedData.push({
                        code: row[0] || '',
                        name: row[1] || '',
                        description: row[2] || '',
                        credits: parseInt(row[3]) || 1,
                        theory_hours: parseInt(row[4]) || 0,
                        practice_hours: parseInt(row[5]) || 0,
                        self_study_hours: parseInt(row[6]) || 0,
                        exam_hours: parseInt(row[7]) || 0,
                        level: row[8] || 'Cơ bản',
                        semester: row[9] || 'semester_1',
                        assessment_method: row[10] || 'Thi viết'
                    });
                }

                updateProgress(60, 'Đã đọc ' + parsedData.length + ' môn học');

                if (parsedData.length === 0) {
                    showMessage('File không có dữ liệu hợp lệ', 'error');
                    hideProgressBar();
                    return;
                }

                // Display preview
                displayDataPreview();

                updateProgress(100, 'Hoàn thành!');
                setTimeout(() => {
                    hideProgressBar();
                    document.getElementById('uploadBtn').disabled = false;
                }, 500);

            } catch (error) {
                console.error('Error parsing Excel:', error);
                showMessage('Lỗi khi đọc file Excel: ' + error.message, 'error');
                hideProgressBar();
            }
        };

        reader.onerror = function() {
            showMessage('Lỗi khi đọc file', 'error');
            hideProgressBar();
        };

        reader.readAsArrayBuffer(file);
    }

    function displayDataPreview() {
        const tbody = document.getElementById('previewTableBody');
        tbody.innerHTML = '';

        parsedData.forEach((row, index) => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-gray-50';
            tr.innerHTML = `
                <td class="px-3 py-2 text-sm text-gray-900">${index + 1}</td>
                <td class="px-3 py-2 text-sm text-gray-900">${row.code || '<span class="text-gray-400 italic">Tự động</span>'}</td>
                <td class="px-3 py-2 text-sm text-gray-900">${row.name}</td>
                <td class="px-3 py-2 text-sm text-gray-900">${row.credits}</td>
                <td class="px-3 py-2 text-sm text-gray-900">${row.theory_hours}</td>
                <td class="px-3 py-2 text-sm text-gray-900">${row.practice_hours}</td>
                <td class="px-3 py-2 text-sm text-gray-900">${row.self_study_hours}</td>
                <td class="px-3 py-2 text-sm text-gray-900">${row.exam_hours}</td>
                <td class="px-3 py-2 text-sm text-gray-900">${row.level}</td>
                <td class="px-3 py-2 text-sm text-gray-900">${row.semester || '<span class="text-gray-400 italic">-</span>'}</td>
            `;
            tbody.appendChild(tr);
        });

        document.getElementById('totalRows').textContent = parsedData.length;
        document.getElementById('dataPreview').classList.remove('hidden');
    }

    function hideDataPreview() {
        document.getElementById('dataPreview').classList.add('hidden');
        document.getElementById('previewTableBody').innerHTML = '';
        parsedData = [];
    }

    function clearFile() {
        selectedFile = null;
        parsedData = [];
        document.getElementById('fileInput').value = '';
        document.getElementById('uploadPrompt').classList.remove('hidden');
        document.getElementById('fileInfo').classList.add('hidden');
        document.getElementById('uploadBtn').disabled = true;
        hideProgressBar();
        hideDataPreview();
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    function submitImport() {
        if (parsedData.length === 0) {
            showMessage('Không có dữ liệu để nhập', 'error');
            return;
        }

        // Disable upload button
        const uploadBtn = document.getElementById('uploadBtn');
        uploadBtn.disabled = true;
        uploadBtn.innerHTML = '<i class="bi bi-hourglass-split mr-2"></i>Đang xử lý...';

        // Show progress
        showProgressBar();
        updateProgress(0, 'Đang gửi dữ liệu...');

        fetch('/specializations/{{ $specialization->id }}/subjects/import', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({
                subjects: parsedData
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateProgress(100, 'Hoàn thành!');

                let message = data.message || 'Nhập dữ liệu thành công!';
                if (data.data && data.data.errors && data.data.errors.length > 0) {
                    // Has some errors
                    message += '<br><br><strong>Chi tiết lỗi:</strong><ul class="list-disc pl-5 mt-2">';
                    data.data.errors.forEach(error => {
                        message += `<li>Dòng ${error.row}: ${error.message}</li>`;
                    });
                    message += '</ul>';
                    showMessage(message, 'warning');
                } else {
                    showMessage(message, 'success');
                }

                setTimeout(() => {
                    window.location.reload();
                }, 3000);
            } else {
                updateProgress(100, 'Có lỗi xảy ra');

                let errorMessage = data.message || 'Có lỗi xảy ra khi nhập dữ liệu';
                if (data.errors && data.errors.length > 0) {
                    errorMessage += '<br><br><strong>Chi tiết lỗi:</strong><ul class="list-disc pl-5 mt-2">';
                    data.errors.forEach(error => {
                        errorMessage += `<li>Dòng ${error.row}: ${error.message}</li>`;
                    });
                    errorMessage += '</ul>';
                }

                showMessage(errorMessage, 'error');
                uploadBtn.disabled = false;
                uploadBtn.innerHTML = '<i class="bi bi-upload mr-2"></i>Nhập dữ liệu';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Có lỗi xảy ra. Vui lòng thử lại.', 'error');
            uploadBtn.disabled = false;
            uploadBtn.innerHTML = '<i class="bi bi-upload mr-2"></i>Nhập dữ liệu';
            hideProgressBar();
        });
    }

    function showProgressBar() {
        document.getElementById('progressBar').classList.remove('hidden');
    }

    function hideProgressBar() {
        document.getElementById('progressBar').classList.add('hidden');
        document.getElementById('progressFill').style.width = '0%';
    }

    function updateProgress(percent, text) {
        document.getElementById('progressFill').style.width = percent + '%';
        document.getElementById('progressText').textContent = text;
    }

    function showMessage(message, type) {
        const messageBox = document.getElementById('messageBox');
        messageBox.classList.remove('hidden');

        if (type === 'success') {
            messageBox.className = 'mb-4 p-4 rounded-lg bg-green-50 border border-green-200 text-green-800';
            messageBox.innerHTML = '<i class="bi bi-check-circle mr-2"></i>' + message;
        } else if (type === 'warning') {
            messageBox.className = 'mb-4 p-4 rounded-lg bg-yellow-50 border border-yellow-200 text-yellow-800';
            messageBox.innerHTML = '<i class="bi bi-exclamation-triangle mr-2"></i>' + message;
        } else {
            messageBox.className = 'mb-4 p-4 rounded-lg bg-red-50 border border-red-200 text-red-800';
            messageBox.innerHTML = '<i class="bi bi-exclamation-circle mr-2"></i>' + message;
        }
    }

    function hideMessage() {
        document.getElementById('messageBox').classList.add('hidden');
    }
</script>

@endsection
