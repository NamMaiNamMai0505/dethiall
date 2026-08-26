@extends('layouts.admin')

@section('title', 'Quản lý Người dùng')
@section('page-title', 'Quản lý Người dùng')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Người dùng', 'url' => route('users.index')],
    ['title' => 'Tạo mới']
]" />

<x-page-header
    title="TẠO MỚI NGƯỜI DÙNG"
    :actions="[[
        'url' => route('users.index'),
        'label' => 'Quay lại',
        'icon' => 'arrow-left',
        'color' => 'gray',
    ]]" />

<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    <form action="{{ route('users.store') }}" method="POST">
        @csrf

        <div class="p-6 space-y-6">
            <!-- Phân quyền (đặt trước thông tin cơ bản) -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Phân quyền</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-form.select
                            label="Vai trò"
                            name="role_id"
                            id="role_id"
                            required
                            :options="$roles"
                            placeholder="Chọn vai trò"
                            :value="old('role_id')"
                        />
                    </div>

                    <div id="military_personnel_link_field" class="md:col-span-2 rounded-xl border border-blue-200 bg-blue-50/60 p-4">
                        <label for="leave_personnel_id" class="block text-sm font-semibold text-slate-700">
                            Liên kết hồ sơ quân nhân hiện có
                        </label>
                        <p class="mt-1 text-xs text-slate-600">Áp dụng cho quân nhân đề xuất phép, chỉ huy cơ quan và quân lực. Tìm theo họ tên, mã quân nhân, cấp bậc hoặc đơn vị.</p>
                        <input type="search" id="military_personnel_search" class="mt-3 w-full rounded-lg border-slate-200 px-3 py-2.5" placeholder="Gõ để tìm quân nhân..." autocomplete="off">
                        <select name="leave_personnel_id" id="leave_personnel_id" class="mt-2 w-full rounded-lg border-slate-200 px-3 py-2.5">
                            <option value="">Chọn hồ sơ quân nhân cần liên kết...</option>
                            @foreach($militaryPersonnel ?? [] as $person)
                                <option value="{{ $person->id }}"
                                        data-search="{{ strtolower(trim(($person->staff_code ?? '').' '.($person->name ?? '').' '.($person->rank ?? '').' '.($person->position ?? '').' '.($person->unit ?? '').' '.($person->email ?? '').' '.($person->gmail ?? ''))) }}"
                                        data-name="{{ $person->name }}"
                                        data-code="{{ $person->staff_code }}"
                                        data-email="{{ $person->gmail ?: $person->email }}"
                                        data-rank="{{ $person->rank }}"
                                        data-position="{{ $person->position }}"
                                        data-unit="{{ $person->unit }}"
                                        {{ old('leave_personnel_id') == $person->id ? 'selected' : '' }}>
                                    {{ $person->staff_code ?: 'Chưa có mã' }} — {{ $person->name }}{{ $person->rank ? ' — '.$person->rank : '' }}{{ $person->unit ? ' — '.$person->unit : '' }}{{ $person->user_id ? ' (đã liên kết)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        @error('leave_personnel_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>

                    <div id="unit_field_wrap">
                        <x-form.select
                            label="Khoa / đơn vị"
                            name="unit_id"
                            id="unit_id"
                            :options="$units"
                            placeholder="Chọn khoa / đơn vị"
                            :value="old('unit_id')"
                        />
                        <p id="unit_help_manager" class="hidden mt-1 text-xs text-indigo-700 bg-indigo-50 border border-indigo-100 rounded-lg p-2">
                            <i class="bi bi-info-circle mr-1"></i>
                            Vai trò lịch đào tạo <strong>bắt buộc</strong> chọn đúng Phòng Đào tạo hoặc Khoa đã được phân loại.
                        </p>
                        @error('unit_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <x-form.select
                            label="Loại người dùng"
                            name="user_type"
                            required
                            :options="['instructor' => 'Giảng viên', 'internal_user' => 'Nội bộ', 'student' => 'Sinh viên']"
                            placeholder="Chọn loại người dùng"
                            :value="old('user_type', 'internal_user')"
                            id="user_type"
                        />
                    </div>

                    <div id="instructor_field" class="hidden">
                        <label for="instructor_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Giảng viên
                        </label>
                        <select id="instructor_id"
                                name="instructor_id"
                                data-instructor-select
                                class="w-full @error('instructor_id') border-red-300 @enderror">
                            <option value="">Tìm và chọn giảng viên...</option>
                            @foreach($instructors as $instructor)
                                <option value="{{ $instructor->id }}"
                                        data-name="{{ $instructor->name }}"
                                        data-code="{{ $instructor->code }}"
                                        data-email="{{ $instructor->email }}"
                                        data-phone="{{ $instructor->phone }}"
                                        data-unit="{{ $instructor->unit->name ?? '' }}"
                                        data-unit-id="{{ $instructor->unit_id }}"
                                        {{ old('instructor_id') == $instructor->id ? 'selected' : '' }}>
                                    {{ $instructor->name }} ({{ $instructor->code }}){{ $instructor->unit ? ' - '.$instructor->unit->name : '' }}
                                </option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            Chỉ GV chưa có tài khoản. Chọn khoa để lọc danh sách. Chọn GV sẽ điền họ tên / mã / email (nếu có).
                        </p>
                        @error('instructor_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="military_rank_field" class="hidden">
                        <x-form.select
                            label="Cấp bậc"
                            name="military_rank_id"
                            id="military_rank_id"
                            :options="$militaryRanks ?? []"
                            placeholder="Chọn cấp bậc"
                            :value="old('military_rank_id')"
                            data-searchable="1"
                            help="Áp dụng cho mọi vai trò, ngoại trừ Học viên."
                        />
                    </div>

                    <div id="position_field" class="hidden space-y-4">
                        <x-form.select
                            label="Chức danh (tỉ lệ % giờ chuẩn)"
                            name="position_id"
                            id="position_id"
                            :options="$managerPositions"
                            placeholder="Chọn chức danh — VD Hiệu trưởng 10%"
                            :value="old('position_id')"
                        />
                        <x-form.select
                            label="Đối tượng (định mức GC + NCKH)"
                            name="object_type_id"
                            id="object_type_id"
                            :options="$objectTypes ?? []"
                            placeholder="Chọn đối tượng — VD 02 · GC 380"
                            :value="old('object_type_id')"
                        />
                        <p class="text-xs text-slate-500 -mt-2">
                            Giờ phải đạt = định mức GC của đối tượng × tỉ lệ chức danh
                            (VD: Hiệu trưởng 10% × Đối tượng 02 = 380 × 10% = <strong>38 giờ</strong>).
                            Gán sang hồ sơ GV khi chọn giảng viên.
                        </p>
                    </div>

                    <div id="class_field" class="hidden">
                        <x-form.select
                            label="Lớp học"
                            name="class_id"
                            :options="$classes"
                            placeholder="Chọn lớp học"
                            :value="old('class_id')"
                            id="class_id"
                            data-searchable="1"
                            data-placeholder="Tìm và chọn lớp học..."
                        />
                    </div>
                </div>
            </div>

            <!-- Thông tin cơ bản -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Thông tin cơ bản</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <x-form.input
                            label="Họ và tên"
                            name="name"
                            id="name"
                            required
                            placeholder="Nhập họ và tên"
                            :value="old('name')"
                        />
                    </div>

                    <div>
                        <x-form.input
                            label="Mã người dùng"
                            name="code"
                            id="code"
                            placeholder="Nhập mã người dùng (tùy chọn)"
                            :value="old('code')"
                        />
                    </div>

                    <div>
                        <x-form.input
                            type="email"
                            label="Email"
                            name="email"
                            required
                            placeholder="Nhập email"
                            :value="old('email')"
                        />
                    </div>

                    <div>
                        <x-form.input
                            type="tel"
                            label="Số điện thoại"
                            name="phone"
                            placeholder="Nhập số điện thoại"
                            :value="old('phone')"
                        />
                    </div>
                </div>
            </div>

            <!-- Mật khẩu -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Mật khẩu</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="relative">
                        <x-form.input
                            type="password"
                            label="Mật khẩu"
                            name="password"
                            required
                            placeholder="Nhập mật khẩu"
                            id="password"
                        />
                        <button type="button" onclick="togglePassword('password', this)" class="absolute right-3 top-9 text-gray-500 focus:outline-none">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                    <div class="relative">
                        <x-form.input
                            type="password"
                            label="Xác nhận mật khẩu"
                            name="password_confirmation"
                            required
                            placeholder="Xác nhận mật khẩu"
                            id="password_confirmation"
                        />
                        <button type="button" onclick="togglePassword('password_confirmation', this)" class="absolute right-3 top-9 text-gray-500 focus:outline-none">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Trạng thái -->
            <div>
                <h3 class="text-lg font-semibold mb-4">Trạng thái</h3>
                <div>
                    <x-form.checkbox
                        label="Hoạt động"
                        name="status"
                        :checked="old('status', true)"
                    />
                </div>
            </div>
        </div>

        <div class="bg-gray-50 px-6 py-4 border-t flex justify-end gap-3">
            <a href="{{ route('users.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition-colors bg-gray-500 hover:bg-gray-600 text-white">
                <i class="bi bi-x-lg"></i> Hủy
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium shadow-sm transition-colors bg-blue-600 hover:bg-blue-700 text-white">
                <i class="bi bi-save"></i> Lưu người dùng
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
function togglePassword(fieldId, btn) {
    const input = document.getElementById(fieldId);
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        btn.innerHTML = '<i class="bi bi-eye-slash"></i>';
    } else {
        input.type = 'password';
        btn.innerHTML = '<i class="bi bi-eye"></i>';
    }
}

@php
    $instructorsForJs = collect($instructors ?? [])->map(function ($i) {
        return [
            'value' => (string) $i->id,
            'text' => $i->name.' ('.$i->code.')'.($i->unit ? ' - '.$i->unit->name : ''),
            'name' => $i->name,
            'code' => $i->code,
            'email' => $i->email,
            'phone' => $i->phone,
            'unit' => $i->unit->name ?? '',
            'unitId' => $i->unit_id !== null ? (string) $i->unit_id : '',
        ];
    })->values();
@endphp
(function () {
    const boundRoleSelects = window.__userCreateBoundRoleSelects
        || (window.__userCreateBoundRoleSelects = new WeakSet());
    const instructorRoleId = @json($instructorRoleId);
    const managerRoleId = @json($managerRoleId);
    const unitRequiredRoleIds = @json($unitRequiredRoleIds ?? []);
    const rankEligibleRoleIds = @json($rankEligibleRoleIds ?? []);
    const managerPositionIds = @json($managerPositionIds ?? []);
    const allInstructors = @json($instructorsForJs);

    function setSelectValue(el, value, silent) {
        if (!el) return;
        const v = value == null ? '' : String(value);
        if (typeof window.setTomValues === 'function') {
            window.setTomValues(el, v, silent !== false);
            return;
        }
        if (el.tomselect) {
            el.tomselect.setValue(v, silent !== false);
            return;
        }
        el.value = v;
        if (!silent) el.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function getSelectValue(el) {
        if (!el) return '';
        if (typeof window.getSelectValue === 'function') return String(window.getSelectValue(el) || '');
        if (el.tomselect) {
            const val = el.tomselect.getValue();
            return String(Array.isArray(val) ? (val[0] || '') : (val || ''));
        }
        return String(el.value || '');
    }

    function bindSelectChange(el, handler) {
        if (!el) return;
        if (typeof window.onTomChange === 'function') {
            window.onTomChange(el, handler);
            return;
        }
        el.addEventListener('change', handler);
        if (el.tomselect) el.tomselect.on('change', handler);
    }

    function boot() {
        const roleSelect = document.getElementById('role_id');
        const userTypeSelect = document.getElementById('user_type');
        const militaryRankField = document.getElementById('military_rank_field');
        const militaryRankSelect = document.getElementById('military_rank_id');
        const positionField = document.getElementById('position_field');
        const positionSelect = document.getElementById('position_id');
        const instructorField = document.getElementById('instructor_field');
        const classField = document.getElementById('class_field');
        const instructorSelect = document.getElementById('instructor_id');
        const classSelect = document.getElementById('class_id');
        const nameInput = document.getElementById('name');
        const codeInput = document.getElementById('code');
        const emailInput = document.getElementById('email');
        const phoneInput = document.getElementById('phone');
        const unitSelect = document.getElementById('unit_id');
        const militaryLinkField = document.getElementById('military_personnel_link_field');
        const militaryPersonnelSelect = document.getElementById('leave_personnel_id');
        const militaryPersonnelSearch = document.getElementById('military_personnel_search');
        const militaryLinkRoleIds = @json($militaryLinkRoleIds ?? []);
        if (!roleSelect || !userTypeSelect || !instructorField) return;
        if (boundRoleSelects.has(roleSelect)) return;
        boundRoleSelects.add(roleSelect);

        let syncing = false;

        function isInstructorRole() {
            return instructorRoleId && getSelectValue(roleSelect) === String(instructorRoleId);
        }

        function isInstructorType() {
            return getSelectValue(userTypeSelect) === 'instructor';
        }

        function toggleMilitaryRankField() {
            const canAssignRank = getSelectValue(userTypeSelect) !== 'student'
                && rankEligibleRoleIds.map(String).includes(getSelectValue(roleSelect));
            militaryRankField?.classList.toggle('hidden', !canAssignRank);
            if (!canAssignRank && militaryRankSelect) {
                setSelectValue(militaryRankSelect, '', true);
            } else if (canAssignRank && militaryRankField && typeof window.initTomSelects === 'function') {
                requestAnimationFrame(function () { window.initTomSelects(militaryRankField); });
            }
        }

        function getSelectedInstructorOption() {
            if (!instructorSelect) return null;
            const value = getSelectValue(instructorSelect);
            if (!value) return null;
            return instructorSelect.querySelector('option[value="' + CSS.escape(String(value)) + '"]');
        }

        function syncInstructorToUserFields() {
            const option = getSelectedInstructorOption();
            if (!option) return;

            if (option.dataset.name && nameInput) nameInput.value = option.dataset.name;
            if (codeInput) codeInput.value = option.dataset.code || '';
            if (emailInput && option.dataset.email) emailInput.value = option.dataset.email;
            if (phoneInput && option.dataset.phone) phoneInput.value = option.dataset.phone;

            // Đồng bộ khoa theo GV (silent — không loop filter)
            if (option.dataset.unitId && unitSelect) {
                const currentUnit = getSelectValue(unitSelect);
                if (currentUnit !== String(option.dataset.unitId)) {
                    setSelectValue(unitSelect, option.dataset.unitId, true);
                }
            }
        }

        function selectedMilitaryPersonnelOption() {
            if (!militaryPersonnelSelect) return null;
            const value = getSelectValue(militaryPersonnelSelect);
            if (!value) return null;
            return militaryPersonnelSelect.querySelector('option[value="' + CSS.escape(String(value)) + '"]');
        }

        function setFromPersonnel(input, value) {
            if (!input) return;
            const text = String(value || '').trim();
            if (text) input.value = text;
            input.readOnly = Boolean(text);
            input.classList.toggle('bg-slate-100', Boolean(text));
        }

        function syncMilitaryPersonnelToUserFields() {
            const option = selectedMilitaryPersonnelOption();
            if (!option) return;

            setFromPersonnel(nameInput, option.dataset.name);
            setFromPersonnel(codeInput, option.dataset.code);
            setFromPersonnel(emailInput, option.dataset.email);

            // Cấp bậc là select: tự chọn được option tương ứng; nếu hồ sơ chưa có thì vẫn cho chọn.
            if (militaryRankSelect) {
                const rankText = String(option.dataset.rank || '').trim().toLowerCase();
                const rankOption = Array.from(militaryRankSelect.options).find(function (item) {
                    return rankText && item.textContent.trim().toLowerCase().includes(rankText);
                });
                if (rankOption) setSelectValue(militaryRankSelect, rankOption.value, true);
            }

            if (positionSelect && option.dataset.position) {
                const positionText = option.dataset.position.trim().toLowerCase();
                const positionOption = Array.from(positionSelect.options).find(function (item) {
                    return positionText && item.textContent.trim().toLowerCase().includes(positionText);
                });
                if (positionOption) setSelectValue(positionSelect, positionOption.value, true);
            }

            // Đơn vị trong hồ sơ quân nhân là tên; tự khớp với option đơn vị nếu tìm thấy.
            if (unitSelect && option.dataset.unit) {
                const unitText = option.dataset.unit.trim().toLowerCase();
                const unitOption = Array.from(unitSelect.options).find(function (item) {
                    return unitText && item.textContent.trim().toLowerCase().includes(unitText);
                });
                if (unitOption) setSelectValue(unitSelect, unitOption.value, true);
            }
        }

        function toggleMilitaryPersonnelLink() {
            const selectedRole = roleSelect.options[roleSelect.selectedIndex];
            const roleText = String(selectedRole?.textContent || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
            const show = true;
            militaryLinkField?.classList.toggle('hidden', !show);
            if (!show && militaryPersonnelSelect) {
                setSelectValue(militaryPersonnelSelect, '', true);
            }
        }

        function filterMilitaryPersonnelOptions() {
            if (!militaryPersonnelSelect || !militaryPersonnelSearch) return;
            const term = militaryPersonnelSearch.value.trim().toLowerCase();
            Array.from(militaryPersonnelSelect.options).forEach(function (option, index) {
                if (index === 0) return;
                option.hidden = Boolean(term) && !String(option.dataset.search || option.textContent).toLowerCase().includes(term);
            });
        }

        function filterInstructorOptions(preserveSelected) {
            if (!instructorSelect) return;
            const unitId = getSelectValue(unitSelect);
            const selected = preserveSelected ? getSelectValue(instructorSelect) : '';

            let list = allInstructors.slice();
            if (unitId) {
                list = list.filter(function (item) {
                    return String(item.unitId || '') === String(unitId);
                });
            }

            // Placeholder option
            const items = [{ value: '', text: unitId ? 'Tìm GV trong khoa đã chọn...' : 'Tìm và chọn giảng viên...' }]
                .concat(list);

            // Giữ selected nếu vẫn còn trong list
            let keep = '';
            if (selected && list.some(function (i) { return String(i.value) === String(selected); })) {
                keep = selected;
            }

            if (typeof window.setTomSelectOptions === 'function') {
                window.setTomSelectOptions(instructorSelect, items, {
                    selected: keep,
                    enabled: true,
                });
            } else {
                instructorSelect.innerHTML = '';
                items.forEach(function (item) {
                    const opt = document.createElement('option');
                    opt.value = item.value;
                    opt.textContent = item.text;
                    if (item.name) opt.dataset.name = item.name;
                    if (item.code) opt.dataset.code = item.code;
                    if (item.email) opt.dataset.email = item.email;
                    if (item.unit) opt.dataset.unit = item.unit;
                    if (item.unitId) opt.dataset.unitId = item.unitId;
                    if (keep && String(item.value) === String(keep)) opt.selected = true;
                    instructorSelect.appendChild(opt);
                });
            }

            // Re-bind change after rebuild
            bindSelectChange(instructorSelect, function () {
                if (syncing) return;
                syncInstructorToUserFields();
            });
        }

        function showInstructorBox(show) {
            if (show) {
                instructorField.classList.remove('hidden');
                if (instructorSelect) instructorSelect.setAttribute('required', 'required');
                if (typeof window.initInstructorSelects === 'function') {
                    window.initInstructorSelects(instructorField);
                }
                filterInstructorOptions(true);
            } else {
                instructorField.classList.add('hidden');
                if (instructorSelect) {
                    instructorSelect.removeAttribute('required');
                    if (instructorSelect.tomselect) {
                        try { instructorSelect.tomselect.clear(true); } catch (e) { /* ignore */ }
                    } else {
                        instructorSelect.value = '';
                    }
                }
            }
        }

        function togglePositionField() {
            // Chức danh + Đối tượng: nội bộ (quản lý) hoặc giảng viên
            const t = getSelectValue(userTypeSelect);
            const show = t === 'internal_user' || t === 'instructor';
            if (show) {
                positionField?.classList.remove('hidden');
                if (positionField && typeof window.initTomSelects === 'function') {
                    requestAnimationFrame(function () {
                        window.initTomSelects(positionField);
                    });
                }
            } else {
                positionField?.classList.add('hidden');
                if (positionSelect) setSelectValue(positionSelect, '', true);
                const ot = document.getElementById('object_type_id');
                if (ot) setSelectValue(ot, '', true);
            }
        }

        function toggleClassField() {
            if (getSelectValue(userTypeSelect) === 'student') {
                classField?.classList.remove('hidden');
                if (classSelect) {
                    classSelect.setAttribute('required', 'required');
                    classSelect.setAttribute('data-searchable', '1');
                    classSelect.setAttribute('data-placeholder', 'Tìm và chọn lớp học...');
                }
                // Field lúc đầu ẩn → Tom Select bỏ qua; hiện ra rồi mới init
                if (classField && typeof window.initTomSelects === 'function') {
                    requestAnimationFrame(function () {
                        window.initTomSelects(classField);
                    });
                }
            } else {
                classField?.classList.add('hidden');
                if (classSelect) {
                    classSelect.removeAttribute('required');
                    setSelectValue(classSelect, '', true);
                }
            }
        }

        function applyManagerRoleFromPosition() {
            if (!positionSelect || !managerRoleId) return;
            if (getSelectValue(userTypeSelect) !== 'internal_user') return;
            if (managerPositionIds.map(String).includes(getSelectValue(positionSelect))) {
                setSelectValue(roleSelect, String(managerRoleId), true);
                syncManagerUnitField();
            }
        }

        function syncManagerUnitField() {
            const isManager = unitRequiredRoleIds.map(String).includes(getSelectValue(roleSelect));
            const help = document.getElementById('unit_help_manager');
            if (help) help.classList.toggle('hidden', !isManager);
            if (unitSelect) {
                if (isManager) unitSelect.setAttribute('required', 'required');
                else unitSelect.removeAttribute('required');
            }
            if (isManager && getSelectValue(userTypeSelect) !== 'internal_user') {
                setSelectValue(userTypeSelect, 'internal_user', true);
                togglePositionField();
                toggleClassField();
                showInstructorBox(false);
            }
        }

        /**
         * Vai trò instructor → loại = Giảng viên ngay (Tom Select UI)
         * Loại khác instructor → ẩn box chọn GV
         * Box GV hiện khi loại = instructor (hoặc vai trò instructor vừa chọn)
         */
        function onRoleChange() {
            if (syncing) return;
            syncing = true;
            try {
                if (isInstructorRole()) {
                    setSelectValue(userTypeSelect, 'instructor', true);
                    togglePositionField();
                    toggleClassField();
                    showInstructorBox(true);
                } else {
                    // Đổi role khác instructor: nếu đang là loại GV thì về nội bộ
                    if (isInstructorType()) {
                        setSelectValue(userTypeSelect, 'internal_user', true);
                    }
                    togglePositionField();
                    toggleClassField();
                    showInstructorBox(isInstructorType());
                }
                syncManagerUnitField();
                toggleMilitaryRankField();
                toggleMilitaryPersonnelLink();
            } finally {
                syncing = false;
            }
        }

        function onUserTypeChange() {
            if (syncing) return;
            syncing = true;
            try {
                const type = getSelectValue(userTypeSelect);
                if (type === 'instructor') {
                    // Đồng bộ role instructor nếu có
                    if (instructorRoleId && !isInstructorRole()) {
                        setSelectValue(roleSelect, String(instructorRoleId), true);
                    }
                    showInstructorBox(true);
                } else {
                    showInstructorBox(false);
                }
                togglePositionField();
                toggleClassField();
                syncManagerUnitField();
                toggleMilitaryRankField();
                toggleMilitaryPersonnelLink();
            } finally {
                syncing = false;
            }
        }

        function onUnitChange() {
            if (syncing) return;
            // Chỉ lọc khi đang hiện box GV
            if (isInstructorType() || isInstructorRole()) {
                filterInstructorOptions(false);
            }
        }

        // Init Tom Select trước khi bind
        if (typeof window.initTomSelects === 'function') {
            window.initTomSelects(document.getElementById('admin-content') || document);
        }

        bindSelectChange(roleSelect, onRoleChange);
        bindSelectChange(userTypeSelect, onUserTypeChange);
        bindSelectChange(unitSelect, onUnitChange);
        bindSelectChange(positionSelect, applyManagerRoleFromPosition);
        bindSelectChange(instructorSelect, syncInstructorToUserFields);
        bindSelectChange(militaryPersonnelSelect, syncMilitaryPersonnelToUserFields);
        militaryPersonnelSearch?.addEventListener('input', filterMilitaryPersonnelOptions);

        // Trạng thái ban đầu (old input / default)
        syncing = true;
        try {
            if (isInstructorRole()) {
                setSelectValue(userTypeSelect, 'instructor', true);
            }
            togglePositionField();
            toggleClassField();
            showInstructorBox(isInstructorType() || isInstructorRole());
            syncManagerUnitField();
            toggleMilitaryRankField();
            toggleMilitaryPersonnelLink();
            if ((isInstructorType() || isInstructorRole()) && getSelectedInstructorOption()) {
                syncInstructorToUserFields();
            }
            if (selectedMilitaryPersonnelOption()) syncMilitaryPersonnelToUserFields();
        } finally {
            syncing = false;
        }
    }

    document.addEventListener('DOMContentLoaded', boot);
    document.addEventListener('turbo:load', boot);
    if (document.readyState !== 'loading') boot();
})();
</script>
@endpush
