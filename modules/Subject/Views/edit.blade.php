@extends('layouts.admin')

@section('title', 'Chỉnh sửa môn học')
@section('page-title', 'Chỉnh sửa môn học')

@section('content')
{{-- Breadcrumb --}}
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Môn học', 'url' => route('subjects.index')],
    ['title' => $subject->name, 'url' => route('subjects.show', $subject)],
    ['title' => 'Chỉnh sửa']
]" />

{{-- Page Header --}}
<x-page-header
    title="CHỈNH SỬA MÔN HỌC"
    :actions="[
        [
            'url' => route('subjects.show', $subject),
            'label' => 'Quay lại',
            'icon' => 'arrow-left',
            'color' => 'gray'
        ]
    ]" />

{{-- Form --}}
<div class="bg-white rounded-lg shadow-sm border p-6">
    <form method="POST" action="{{ route('subjects.update', $subject) }}" id="subjectForm">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            {{-- Thông tin cơ bản --}}
            <div class="lg:col-span-2">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Thông tin cơ bản</h3>
            </div>

            {{-- Tên môn học --}}
            <div>
                <x-form.input
                    name="name"
                    label="Tên môn học"
                    :value="old('name', $subject->name)"
                    required
                    placeholder="Nhập tên môn học..."
                />
            </div>

            {{-- Mã môn học --}}
            <div>
                <x-form.input
                    name="code"
                    label="Mã môn học"
                    :value="old('code', $subject->code)"
                    placeholder="Để trống để tự động tạo mã..."
                    help="Lưu full code (vd B_CDDD_M001K1); danh sách/xuất chỉ hiện {{ $subject->display_code }}. Chữ, số, gạch ngang/dưới."
                />
            </div>

            {{-- Viết tắt — xuất lịch --}}
            <div>
                <x-form.input
                    name="abbreviation"
                    label="Viết tắt"
                    :value="old('abbreviation', $subject->abbreviation)"
                    placeholder="VD: TTT"
                    help="Để trống sẽ tự lấy chữ cái đầu (Thuốc thông thường → TTT). Dùng khi xuất lịch."
                />
            </div>

            {{-- Màu nhận diện — xuất lịch huấn luyện --}}
            <div>
                @php $editColor = old('color', $subject->display_color ?? '#4EA1FF'); @endphp
                <label class="block text-sm font-medium text-gray-700 mb-1">Màu nhận diện</label>
                <div class="flex items-center gap-3">
                    <input type="color" id="color_picker" value="{{ $editColor }}"
                           class="h-10 w-14 rounded border border-gray-300 cursor-pointer"
                           oninput="document.getElementById('color_hex').value=this.value.toUpperCase()">
                    <input type="text" name="color" id="color_hex" value="{{ $editColor }}"
                           maxlength="7" placeholder="#4EA1FF"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500"
                           oninput="if(/^#[0-9A-Fa-f]{6}$/.test(this.value)) document.getElementById('color_picker').value=this.value">
                </div>
                <p class="mt-1 text-xs text-gray-500">Dùng khi xuất lịch huấn luyện (ô môn tô màu theo mẫu Excel).</p>
                @error('color')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
            </div>

            {{-- Hệ đào tạo — chỉ lọc Ngành phía dưới, không lưu vào subjects --}}
            <div>
                <label for="training_system_id" class="block text-sm font-medium text-gray-700 mb-2">
                    Hệ đào tạo
                </label>
                <div class="ui-select-field">
                    <select id="training_system_id" name="training_system_id" data-placeholder="Chọn hệ..." data-searchable="1" class="w-full">
                        <option value="">Chọn hệ đào tạo...</option>
                        @foreach($trainingSystems ?? [] as $id => $name)
                            <option value="{{ $id }}" @selected(old('training_system_id', $subject->specialization?->training_system_id) == $id)>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- Ngành --}}
            <div>
                <x-form.select
                    name="specialization_id"
                    label="Ngành đào tạo"
                    :options="$specializations"
                    :value="old('specialization_id', $subject->specialization_id)"
                    required
                    placeholder="Chọn ngành đào tạo..."
                />
            </div>

            {{-- Khoa phụ trách — phân công tường minh, không suy diễn từ mã môn --}}
            <div>
                <label for="faculty_code" class="block text-sm font-medium text-gray-700 mb-2">Khoa phụ trách</label>
                <div class="ui-select-field">
                    <select id="faculty_code" name="faculty_code" data-placeholder="Chưa phân công..." data-searchable="1" class="w-full">
                        <option value="">Chưa phân công...</option>
                        @foreach($facultyUnits ?? [] as $unit)
                            <option value="{{ $unit->faculty_code }}" @selected(old('faculty_code', $subject->faculty_code) == $unit->faculty_code)>
                                {{ $unit->faculty_code }} — {{ $unit->abbreviation ?: $unit->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <p class="mt-1 text-xs text-gray-500">Quyết định khoa nào được lọc GV/lịch cho môn này. Để trống sẽ tạm suy ra từ 2 ký tự cuối mã môn (nếu có).</p>
            </div>

            {{-- Ngưỡng vắng --}}
            <div>
                <x-form.input
                    name="absence_limit_percent"
                    label="Ngưỡng vắng (%)"
                    type="number"
                    :value="old('absence_limit_percent', $subject->absence_limit_percent)"
                    min="0"
                    max="100"
                    placeholder="Để trống dùng mức chung của hệ thống"
                    help="Vắng có phép không tính vào ngưỡng này. Vượt ngưỡng chỉ cảnh báo, không tự đánh trượt."
                />
            </div>

            {{-- Số tín chỉ --}}
            <div>
                <x-form.input
                    name="credits"
                    label="Số tín chỉ"
                    type="number"
                    :value="old('credits', $subject->credits)"
                    required
                    min="1"
                    max="20"
                />
            </div>

            {{-- Mô tả --}}
            <div class="lg:col-span-2">
                <x-form.textarea
                    name="description"
                    label="Mô tả"
                    :value="old('description', $subject->description)"
                    placeholder="Nhập mô tả môn học..."
                    rows="3"
                />
            </div>

            {{-- Thông tin học tập --}}
            <div class="lg:col-span-2">
                <h3 class="text-lg font-medium text-gray-900 mb-4 mt-6">Thông tin học tập</h3>
            </div>

            {{-- Số tiết lý thuyết --}}
            <div>
                <x-form.input
                    name="theory_hours"
                    label="Số tiết lý thuyết"
                    type="number"
                    :value="old('theory_hours', $subject->theory_hours)"
                    required
                    min="0"
                    max="500"
                />
            </div>

            {{-- Số tiết thực hành --}}
            <div>
                <x-form.input
                    name="practice_hours"
                    label="Số tiết thực hành"
                    type="number"
                    :value="old('practice_hours', $subject->practice_hours)"
                    required
                    min="0"
                    max="500"
                />
            </div>

            {{-- Số tiết tự học --}}
            <div>
                <x-form.input
                    name="self_study_hours"
                    label="Số tiết tự học"
                    type="number"
                    :value="old('self_study_hours', $subject->self_study_hours)"
                    required
                    min="0"
                    max="500"
                />
            </div>

            {{-- Số tiết thi --}}
            <div>
                <x-form.input
                    name="exam_hours"
                    label="Số tiết thi/kiểm tra"
                    type="number"
                    :value="old('exam_hours', $subject->exam_hours)"
                    required
                    min="0"
                    max="500"
                />
            </div>

            {{-- Tổng số tiết (tự động tính) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Tổng số tiết
                </label>
                <div class="px-3 py-2 border border-gray-300 rounded-md bg-gray-50 text-gray-600" id="totalHours">
                    {{ $subject->total_hours }} tiết
                </div>
            </div>

            {{-- Thông tin khác --}}
            <div class="lg:col-span-2">
                <h3 class="text-lg font-medium text-gray-900 mb-4 mt-6">Thông tin khác</h3>
            </div>

            {{-- Cấp độ --}}
            <div>
                <x-form.select
                    name="level"
                    label="Cấp độ"
                    :options="$levels"
                    :value="old('level', $subject->level)"
                    required
                />
            </div>

            {{-- Phương pháp đánh giá --}}
            <div>
                <x-form.select
                    name="assessment_method"
                    label="Phương pháp đánh giá"
                    :options="$assessmentMethods"
                    :value="old('assessment_method', $subject->assessment_method)"
                    required
                />
            </div>

            {{-- Học kỳ --}}
            <div>
                <x-form.select
                    name="semester"
                    label="Học kỳ"
                    :options="$semesters"
                    :value="old('semester', $subject->semester)"
                    placeholder="Chọn học kỳ..."
                />
            </div>

            {{-- Môn học tiên quyết --}}
            <div class="lg:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-2">
                    Môn học tiên quyết
                </label>
                <div id="prerequisitesContainer">
                    @php
                        $prerequisites = old('prerequisites', $subject->prerequisites ?: []);
                    @endphp
                    @if($prerequisites && count($prerequisites) > 0)
                        @foreach($prerequisites as $index => $prerequisite)
                            <div class="flex items-center space-x-2 mb-2">
                                <input type="text"
                                       name="prerequisites[]"
                                       value="{{ $prerequisite }}"
                                       class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                       placeholder="Nhập tên môn học tiên quyết...">
                                <button type="button"
                                        class="px-3 py-2 bg-red-500 text-white rounded-md hover:bg-red-600"
                                        onclick="removePrerequisite(this)">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        @endforeach
                    @endif
                </div>
                <button type="button"
                        class="mt-2 px-4 py-2 bg-green-500 text-white rounded-md hover:bg-green-600"
                        onclick="addPrerequisite()">
                    <i class="bi bi-plus mr-2"></i>Thêm môn học tiên quyết
                </button>
            </div>

            {{-- Checkboxes --}}
            <div class="lg:col-span-2">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-form.checkbox
                            name="is_required"
                            label="Môn học bắt buộc"
                            :checked="old('is_required', $subject->is_required)"
                        />
                    </div>
                    <div>
                        <x-form.checkbox
                            name="is_active"
                            label="Kích hoạt"
                            :checked="old('is_active', $subject->is_active)"
                        />
                    </div>
                </div>
            </div>
        </div>

        {{-- Buttons --}}
        <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
            <a href="{{ route('subjects.show', $subject) }}"
               class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">
                Hủy
            </a>
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                <i class="bi bi-check mr-2"></i>Cập nhật môn học
            </button>
        </div>
    </form>
</div>

@endsection

@push('scripts')
<script>
    // Cascade Hệ → Ngành (Tom Select nếu có) — cùng cơ chế với create.blade.php
    (function () {
        const specsBySystem = @json($specializationsBySystem ?? []);
        const systemSel = document.getElementById('training_system_id');
        const specSel = document.getElementById('specialization_id');

        function rebuildSpecOptions(systemId, keepId) {
            if (!specSel) return;
            const map = systemId && specsBySystem[systemId] ? specsBySystem[systemId] : {};
            const entries = Object.entries(map || {});
            if (specSel.tomselect) {
                const ts = specSel.tomselect;
                ts.clear(true);
                ts.clearOptions();
                ts.addOption({ value: '', text: 'Chọn ngành đào tạo...' });
                entries.forEach(([id, name]) => ts.addOption({ value: String(id), text: name }));
                ts.refreshOptions(false);
                if (keepId && map[keepId]) {
                    ts.setValue(String(keepId), true);
                }
            } else {
                specSel.innerHTML = '<option value="">Chọn ngành đào tạo...</option>';
                entries.forEach(([id, name]) => {
                    const opt = document.createElement('option');
                    opt.value = id;
                    opt.textContent = name;
                    if (keepId && String(keepId) === String(id)) opt.selected = true;
                    specSel.appendChild(opt);
                });
            }
        }

        systemSel?.addEventListener('change', function () {
            rebuildSpecOptions(this.value, null);
        });
        document.addEventListener('change', function (e) {
            if (e.target && e.target.id === 'training_system_id') {
                rebuildSpecOptions(e.target.value, null);
            }
        });
        // Không rebuild khi tải trang: $specializations của môn đang sửa đã
        // đủ toàn bộ ngành (không lọc sẵn theo Hệ) — chỉ lọc từ lần đổi Hệ
        // đầu tiên của người dùng, tránh mất option đang chọn khi vừa mở form.
    })();

    // Calculate total hours
    function calculateTotalHours() {
        const theory = parseInt(document.querySelector('input[name="theory_hours"]').value) || 0;
        const practice = parseInt(document.querySelector('input[name="practice_hours"]').value) || 0;
        const selfStudy = parseInt(document.querySelector('input[name="self_study_hours"]').value) || 0;
        const exam = parseInt(document.querySelector('input[name="exam_hours"]').value) || 0;

        const total = theory + practice + selfStudy + exam;
        document.getElementById('totalHours').textContent = total + ' tiết';
    }

    // Add event listeners
    document.querySelector('input[name="theory_hours"]').addEventListener('input', calculateTotalHours);
    document.querySelector('input[name="practice_hours"]').addEventListener('input', calculateTotalHours);
    document.querySelector('input[name="self_study_hours"]').addEventListener('input', calculateTotalHours);
    document.querySelector('input[name="exam_hours"]').addEventListener('input', calculateTotalHours);

    // Prerequisites management
    function addPrerequisite() {
        const container = document.getElementById('prerequisitesContainer');
        const div = document.createElement('div');
        div.className = 'flex items-center space-x-2 mb-2';
        div.innerHTML = `
            <input type="text"
                   name="prerequisites[]"
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                   placeholder="Nhập tên môn học tiên quyết...">
            <button type="button"
                    class="px-3 py-2 bg-red-500 text-white rounded-md hover:bg-red-600"
                    onclick="removePrerequisite(this)">
                <i class="bi bi-trash"></i>
            </button>
        `;
        container.appendChild(div);
    }

    function removePrerequisite(button) {
        button.parentElement.remove();
    }
</script>
@endpush
