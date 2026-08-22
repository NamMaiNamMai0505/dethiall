@extends('layouts.admin')

@section('title', 'Chỉnh sửa Người dùng')
@section('page-title', 'Chỉnh sửa Người dùng')

@section('content')
@php
    $posVal = $selectedPositionId ?? old('position_id', $user->position_id);
    $otVal = $selectedObjectTypeId ?? old('object_type_id', $user->object_type_id);
    $showNorms = in_array(old('user_type', $user->user_type), ['instructor', 'internal_user'], true);
    $showMilitaryRank = old('user_type', $user->user_type) !== 'student'
        && in_array(
            (int) old('role_id', $user->roles->first()->id ?? $user->role_id),
            $rankEligibleRoleIds ?? [],
            true
        );
@endphp

<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Người dùng', 'url' => route('users.index')],
    ['title' => 'Chỉnh sửa']
]" />

<x-page-header
    title="CHỈNH SỬA NGƯỜI DÙNG"
    :actions="[[
        'url' => route('users.index'),
        'label' => 'Quay lại',
        'icon' => 'arrow-left',
        'color' => 'gray',
    ]]" />

<div class="bg-white rounded-lg shadow-sm border overflow-hidden">
    <form id="user-form" action="{{ route('users.update', $user) }}" method="POST">
        @csrf
        @method('PUT')

        <div class="p-6 space-y-6">
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
                            :value="old('role_id', $user->roles->first()->id ?? $user->role_id)"
                        />
                    </div>
                    <div id="unit_field_wrap">
                        <x-form.select
                            label="Khoa / đơn vị"
                            name="unit_id"
                            id="unit_id"
                            :options="$units"
                            placeholder="Chọn khoa / đơn vị"
                            :value="old('unit_id', $user->unit_id)"
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
                            :value="old('user_type', $user->user_type)"
                            id="user_type"
                        />
                    </div>

                    <div id="military_rank_field" class="{{ $showMilitaryRank ? '' : 'hidden' }}">
                        <x-form.select
                            label="Cấp bậc"
                            name="military_rank_id"
                            id="military_rank_id"
                            :options="$militaryRanks ?? []"
                            placeholder="Chọn cấp bậc"
                            :value="old('military_rank_id', $user->military_rank_id)"
                            data-searchable="1"
                            help="Áp dụng cho mọi vai trò, ngoại trừ Học viên."
                        />
                    </div>

                    <div id="position_field" class="{{ $showNorms ? '' : 'hidden' }} md:col-span-2 space-y-4 border border-slate-100 rounded-xl p-4 bg-slate-50/80">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Giờ chuẩn — chỉ gán trên tài khoản User</p>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <x-form.select
                                label="Chức danh (tỉ lệ %)"
                                name="position_id"
                                id="position_id"
                                :options="$managerPositions"
                                placeholder="Chọn chức danh"
                                :value="$posVal"
                            />
                            <x-form.select
                                label="Đối tượng (GC + NCKH)"
                                name="object_type_id"
                                id="object_type_id"
                                :options="$objectTypes ?? []"
                                placeholder="Chọn đối tượng"
                                :value="$otVal"
                            />
                        </div>
                        <p class="text-xs text-slate-500">
                            Giờ phải đạt = GC đối tượng × % chức danh (VD 380 × 10% = 38). Lưu đồng bộ sang hồ sơ GV nếu có liên kết.
                        </p>
                    </div>

                    <div id="instructor_field" class="{{ old('user_type', $user->user_type) === 'instructor' ? '' : 'hidden' }}">
                        <label class="block text-sm font-medium text-gray-700 mb-2">Giảng viên</label>
                        @if($user->instructor_id)
                            {{-- Đã liên kết hồ sơ GV: chỉ hiển thị, không cho đổi ở đây để tránh
                                 ràng buộc nhầm sang hồ sơ khác. Muốn đổi liên kết, thao tác từ
                                 module Giảng viên. --}}
                            <div class="w-full border border-gray-200 bg-gray-50 rounded-lg px-3 py-2 text-gray-700">
                                {{ $instructors[$user->instructor_id] ?? ('#'.$user->instructor_id) }}
                            </div>
                            <input type="hidden" name="instructor_id" value="{{ $user->instructor_id }}">
                        @else
                            <select id="instructor_id" name="instructor_id" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                <option value="">Chọn giảng viên...</option>
                                @foreach($instructors as $id => $label)
                                    <option value="{{ $id }}" @selected((string) old('instructor_id') === (string) $id)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('instructor_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                        @endif
                    </div>

                    <div id="class_field" class="{{ old('user_type', $user->user_type) === 'student' ? '' : 'hidden' }}">
                        <x-form.select
                            label="Lớp học"
                            name="class_id"
                            :options="$classes"
                            placeholder="Chọn lớp học"
                            :value="old('class_id', $user->class_id)"
                            id="class_id"
                        />
                    </div>
                </div>
            </div>

            <div>
                <h3 class="text-lg font-semibold mb-4">Thông tin cơ bản</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-form.input label="Họ và tên" name="name" id="name" required placeholder="Họ và tên" :value="old('name', $user->name)" />
                    <x-form.input label="Mã người dùng" name="code" id="code" placeholder="Mã (tuỳ chọn)" :value="old('code', $user->code)" />
                    <x-form.input type="email" label="Email" name="email" required placeholder="Email" :value="old('email', $user->email)" />
                    <x-form.input type="tel" label="Số điện thoại" name="phone" placeholder="Số điện thoại" :value="old('phone', $user->phone)" />
                </div>
            </div>

            <div>
                <h3 class="text-lg font-semibold mb-4">Mật khẩu</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <x-form.input type="password" label="Mật khẩu mới" name="password" placeholder="Để trống nếu không đổi" id="password" />
                    <x-form.input type="password" label="Xác nhận mật khẩu" name="password_confirmation" placeholder="Xác nhận" id="password_confirmation" />
                </div>
            </div>

            <div>
                <h3 class="text-lg font-semibold mb-4">Trạng thái</h3>
                <x-form.checkbox label="Hoạt động" name="status" :checked="old('status', (bool)$user->status)" />
            </div>
        </div>

        <div class="bg-gray-50 px-6 py-4 border-t flex justify-end gap-3">
            <a href="{{ route('users.index') }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium shadow-sm bg-gray-500 hover:bg-gray-600 text-white">
                <i class="bi bi-x-lg"></i> Hủy
            </a>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium shadow-sm bg-blue-600 hover:bg-blue-700 text-white">
                <i class="bi bi-save"></i> Lưu thay đổi
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
(function () {
    const boundForms = window.__userEditBoundForms
        || (window.__userEditBoundForms = new WeakSet());

    function boot() {
        const form = document.getElementById('user-form');
        if (!form || boundForms.has(form)) return;
        boundForms.add(form);

        const managerRoleId = @json($managerRoleId ?? null);
        const unitRequiredRoleIds = @json($unitRequiredRoleIds ?? []);
        const managerPositionIds = @json($managerPositionIds ?? []);
        const rankEligibleRoleIds = @json($rankEligibleRoleIds ?? []);
        const userTypeSelect = form.querySelector('#user_type');
        const roleSelect = form.querySelector('#role_id');
        const positionField = form.querySelector('#position_field');
        const positionSelect = form.querySelector('#position_id');
        const militaryRankField = form.querySelector('#military_rank_field');
        const militaryRankSelect = form.querySelector('#military_rank_id');
        const instructorField = form.querySelector('#instructor_field');
        const classField = form.querySelector('#class_field');
        const unitHelp = form.querySelector('#unit_help_manager');

        function val(el) {
            if (!el) return '';
            if (el.tomselect) {
                const v = el.tomselect.getValue();
                return String(Array.isArray(v) ? (v[0] || '') : (v || ''));
            }
            return String(el.value || '');
        }

        function toggle() {
            const t = val(userTypeSelect) || userTypeSelect?.value || '';
            const showNorms = t === 'internal_user' || t === 'instructor';
            if (positionField) positionField.classList.toggle('hidden', !showNorms);
            if (instructorField) instructorField.classList.toggle('hidden', t !== 'instructor');
            if (classField) classField.classList.toggle('hidden', t !== 'student');
            const requiresUnit = unitRequiredRoleIds.map(String).includes(val(roleSelect));
            if (unitHelp) unitHelp.classList.toggle('hidden', !requiresUnit);
            const unitSelect = form.querySelector('#unit_id');
            if (requiresUnit) unitSelect?.setAttribute('required', 'required');
            else unitSelect?.removeAttribute('required');
            if (requiresUnit && t !== 'internal_user') {
                if (userTypeSelect?.tomselect) userTypeSelect.tomselect.setValue('internal_user', true);
                else if (userTypeSelect) userTypeSelect.value = 'internal_user';
            }
            const canAssignRank = t !== 'student'
                && rankEligibleRoleIds.map(String).includes(val(roleSelect));
            militaryRankField?.classList.toggle('hidden', !canAssignRank);
            if (!canAssignRank && militaryRankSelect) {
                if (militaryRankSelect.tomselect) militaryRankSelect.tomselect.clear(true);
                else militaryRankSelect.value = '';
            } else if (canAssignRank && militaryRankField && typeof window.initTomSelects === 'function') {
                requestAnimationFrame(function () { window.initTomSelects(militaryRankField); });
            }
        }

        userTypeSelect?.addEventListener('change', toggle);
        roleSelect?.addEventListener('change', toggle);
        positionSelect?.addEventListener('change', function () {
            if (!managerRoleId || !positionSelect) return;
            if (val(userTypeSelect) !== 'internal_user') return;
            if (managerPositionIds.map(String).includes(String(val(positionSelect)))) {
                if (roleSelect.tomselect) roleSelect.tomselect.setValue(String(managerRoleId));
                else roleSelect.value = String(managerRoleId);
                toggle();
            }
        });
        toggle();
    }

    document.addEventListener('DOMContentLoaded', boot);
    document.addEventListener('turbo:load', boot);
    if (document.readyState !== 'loading') boot();
})();
</script>
@endpush
