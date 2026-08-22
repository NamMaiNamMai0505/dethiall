@extends('layouts.admin')

@section('title', $mode === 'edit' ? 'Sửa bài học' : 'Thêm bài học')
@section('page-title', $mode === 'edit' ? 'Sửa bài học' : 'Thêm bài học')

@section('content')
<x-breadcrumb :items="[
    ['title' => 'Trang chủ'],
    ['title' => 'Bài học', 'url' => route('subject-lessons.index')],
    ['title' => $mode === 'edit' ? 'Sửa bài học' : 'Thêm bài học']
]" />

<x-page-header :title="$mode === 'edit' ? 'SỬA BÀI HỌC' : 'THÊM BÀI HỌC'" :actions="[[
    'url' => route('subject-lessons.index'), 'label' => 'Quay lại', 'icon' => 'arrow-left', 'color' => 'gray'
]]" />

<form method="POST" action="{{ $mode === 'edit' ? route('subject-lessons.update', $lesson) : route('subject-lessons.store') }}" class="space-y-5">
    @csrf
    @if($mode === 'edit') @method('PUT') @endif
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Hệ đào tạo</label>
                <select id="training-system-id" class="w-full rounded-lg border-slate-300" data-searchable="1">
                    <option value="">— Chọn hệ —</option>
                    @foreach($specializations->pluck('trainingSystem')->filter()->unique('id') as $system)
                        <option value="{{ $system->id }}" @selected(old('training_system_id', $lesson->subject?->specialization?->training_system_id) == $system->id)>{{ $system->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Ngành đào tạo</label>
                <select name="specialization_id" id="specialization-id" required class="w-full rounded-lg border-slate-300" data-searchable="1">
                    <option value="">— Chọn ngành —</option>
                    @foreach($specializations as $specialization)
                        <option value="{{ $specialization->id }}" data-system-id="{{ $specialization->training_system_id }}" @selected(old('specialization_id', $lesson->subject?->specialization_id) == $specialization->id)>{{ $specialization->selection_label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Môn học</label>
                <select name="subject_id" id="subject-id" required class="w-full rounded-lg border-slate-300" data-searchable="1">
                    <option value="">— Chọn môn —</option>
                    @foreach($subjects as $subject)
                        <option value="{{ $subject->id }}" data-specialization-id="{{ $subject->specialization_id }}" @selected(old('subject_id', $lesson->subject_id) == $subject->id)>{{ $subject->display_code }} — {{ $subject->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-4">
            <div><label class="mb-1 block text-sm font-semibold text-slate-700">Mã bài</label><input name="code" required value="{{ old('code', $lesson->code) }}" class="w-full rounded-lg border-slate-300"></div>
            <div><label class="mb-1 block text-sm font-semibold text-slate-700">Tên bài</label><input name="name" required value="{{ old('name', $lesson->name) }}" class="w-full rounded-lg border-slate-300"></div>
            <div><label class="mb-1 block text-sm font-semibold text-slate-700">Loại</label><select name="lesson_kind" class="w-full rounded-lg border-slate-300">@foreach(['unit'=>'Unit','chapter'=>'Chương','lesson'=>'Bài','sub'=>'Bài con','exam'=>'Thi'] as $key => $label)<option value="{{ $key }}" @selected(old('lesson_kind', $lesson->lesson_kind ?: 'lesson') === $key)>{{ $label }}</option>@endforeach</select></div>
            <div>
                <label class="mb-1 block text-sm font-semibold text-slate-700">Bài cha</label>
                <select name="parent_id" id="parent-id" class="w-full rounded-lg border-slate-300" data-searchable="1" data-selected="{{ old('parent_id', $lesson->parent_id) }}">
                    <option value="">— Không có (bài gốc) —</option>
                </select>
                <p class="mt-1 text-xs text-slate-400">Chọn bài đã có của cùng môn để bài này thành bài con.</p>
            </div>
        </div>
        <div class="mt-4 grid grid-cols-2 gap-4 md:grid-cols-5">
            @foreach(['sort_order'=>'Thứ tự','theory_hours'=>'Giờ LT','practice_hours'=>'Giờ TH','exam_hours'=>'Giờ thi','total_hours'=>'Tổng giờ'] as $field => $label)
                <div><label class="mb-1 block text-sm font-semibold text-slate-700">{{ $label }}</label><input type="number" min="0" name="{{ $field }}" value="{{ old($field, $lesson->{$field} ?? 0) }}" class="w-full rounded-lg border-slate-300"></div>
            @endforeach
        </div>
        <div class="mt-4"><label class="mb-1 block text-sm font-semibold text-slate-700">Mô tả</label><textarea name="description" rows="3" class="w-full rounded-lg border-slate-300">{{ old('description', $lesson->description) }}</textarea></div>
    </div>
    <div class="flex justify-end gap-2"><a href="{{ route('subject-lessons.index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700">Hủy</a><button class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">{{ $mode === 'edit' ? 'Cập nhật' : 'Lưu bài học' }}</button></div>
</form>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const system = document.getElementById('training-system-id');
    const specialization = document.getElementById('specialization-id');
    const subject = document.getElementById('subject-id');
    const parentSelect = document.getElementById('parent-id');
    const currentLessonId = {{ $lesson->id ?? 'null' }};

    const sync = () => {
        const systemId = system.value;
        Array.from(specialization.options).forEach(o => { if (o.value) o.hidden = Boolean(systemId && o.dataset.systemId !== systemId); });
        if (specialization.selectedOptions[0]?.hidden) specialization.value = '';
        const specializationId = specialization.value;
        Array.from(subject.options).forEach(o => { if (o.value) o.hidden = Boolean(specializationId && o.dataset.specializationId !== specializationId); });
        if (subject.selectedOptions[0]?.hidden) subject.value = '';
    };
    system.addEventListener('change', sync); specialization.addEventListener('change', sync); sync();

    // Nạp danh sách "Bài cha" theo môn đã chọn — loại trừ chính bài đang
    // sửa và các bài con trực tiếp của nó để tránh tạo vòng lặp cha-con.
    const loadParentOptions = () => {
        const subjectId = subject.value;
        const selectedParentId = parentSelect.dataset.selected || '';
        parentSelect.innerHTML = '<option value="">— Không có (bài gốc) —</option>';
        if (!subjectId) {
            return;
        }
        fetch(`{{ route('subject-lessons.api.by-subject') }}?subject_id=${subjectId}`, {
            headers: {'X-Requested-With': 'XMLHttpRequest'},
        })
            .then(res => res.json())
            .then(data => {
                const roots = data.lessons || [];
                roots.forEach(root => {
                    if (root.id === currentLessonId) {
                        return;
                    }
                    const opt = document.createElement('option');
                    opt.value = root.id;
                    opt.textContent = root.label;
                    if (String(root.id) === String(selectedParentId)) opt.selected = true;
                    parentSelect.appendChild(opt);

                    (root.children || []).forEach(child => {
                        if (child.id === currentLessonId) {
                            return;
                        }
                        const childOpt = document.createElement('option');
                        childOpt.value = child.id;
                        childOpt.textContent = '— ' + child.label;
                        if (String(child.id) === String(selectedParentId)) childOpt.selected = true;
                        parentSelect.appendChild(childOpt);
                    });
                });
            });
    };
    subject.addEventListener('change', loadParentOptions);
    if (subject.value) {
        loadParentOptions();
    }
});
</script>
@endsection
