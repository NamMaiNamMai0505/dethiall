@extends('layouts.admin')

@section('title', 'Import bài học')
@section('page-title', 'Import bài học')

@section('content')
    <x-breadcrumb :items="[
        ['title' => 'Trang chủ'],
        ['title' => 'Bài học', 'url' => route('subject-lessons.index')],
        ['title' => 'Import bài học'],
    ]" />

    <div class="max-w-3xl mx-auto">
        <div class="bg-white shadow rounded-xl border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b bg-gradient-to-r from-teal-50 to-emerald-50 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h2 class="text-lg font-bold text-slate-800">Import bài học</h2>
                    <p class="text-sm text-slate-600 mt-1">
                        Chọn <strong>Ngành → Môn</strong> rồi tải file lên — toàn bộ bài trong file sẽ ghi vào
                        đúng môn đó. Mã bài đã tồn tại (cùng môn) sẽ được <strong>cập nhật</strong>, không tạo trùng.
                    </p>
                </div>
                <a href="{{ route('subject-lessons.import.template') }}"
                   class="inline-flex shrink-0 items-center gap-2 rounded-lg bg-teal-600 hover:bg-teal-700 text-white px-4 py-2 text-sm font-semibold">
                    <i class="bi bi-download"></i> Tải file mẫu
                </a>
            </div>

            <form method="POST" action="{{ route('subject-lessons.import.store') }}" enctype="multipart/form-data"
                  data-lesson-import-form class="p-6 space-y-5">
                @csrf

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Hệ đào tạo</label>
                        <select id="training-system-id" class="w-full rounded-lg border-slate-300" data-searchable="1">
                            <option value="">— Chọn hệ —</option>
                            @foreach($specializations->pluck('trainingSystem')->filter()->unique('id') as $system)
                                <option value="{{ $system->id }}" @selected(old('training_system_id') == $system->id)>{{ $system->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Ngành đào tạo <span class="text-red-500">*</span></label>
                        <select name="specialization_id" id="specialization-id" required class="w-full rounded-lg border-slate-300" data-searchable="1">
                            <option value="">— Chọn hệ trước, hoặc chọn ngành —</option>
                        </select>
                        @error('specialization_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold text-slate-700">Môn học <span class="text-red-500">*</span></label>
                        <select name="subject_id" id="subject-id" required class="w-full rounded-lg border-slate-300" data-searchable="1">
                            <option value="">— Chọn ngành trước —</option>
                        </select>
                        @error('subject_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">File Excel bài học <span class="text-red-500">*</span></label>
                    <x-admin.file-drop name="file" accept=".xlsx,.xls" required
                        help="Chỉ nhận file Excel (.xlsx, .xls), tối đa 20MB." />
                </div>

                <div class="rounded-lg bg-teal-50 border border-teal-100 p-3 text-xs text-teal-900 space-y-1">
                    <p><strong>Cột dữ liệu:</strong> Mã môn học (tuỳ chọn — chỉ để đối chiếu với môn đã chọn ở
                        trên), Mã bài học, Mã bài cha (tuỳ chọn — để phân cấp Unit/Chương → Bài con), Tên bài
                        học, Loại bài, Thứ tự, Giờ lý thuyết, Giờ thực hành, Giờ thi, Học kỳ, Mô tả.</p>
                    <p class="mt-2 text-teal-800/90">
                        Toàn bộ bài trong file sẽ được ghi vào đúng <strong>Môn đã chọn</strong> ở trên. Nếu cột
                        "Mã môn học" trong file khác với môn đã chọn, dòng đó sẽ báo lỗi thay vì bị ghi nhầm.
                        Tải file mẫu ở trên để xem đầy đủ hướng dẫn và ví dụ minh hoạ phân cấp Unit → Bài con.
                    </p>
                    <p class="mt-2 text-teal-800/90">
                        <strong>Bỏ trống "Mã bài cha"</strong> thì hệ thống tự nối theo <strong>thứ tự dòng</strong>
                        trong file: Unit chứa Chương phía sau nó; Chương (hoặc Unit nếu chưa có Chương) chứa các
                        dòng Bài/Bài con/Thi phía sau. File phải xếp Unit/Chương <strong>trước</strong> các bài
                        con của nó thì mới nối đúng — điền "Mã bài cha" nếu muốn chỉ định chính xác.
                    </p>
                </div>

                @include('subject::partials.lesson-import-feedback')

                <div class="flex justify-end gap-3 pt-2">
                    <a href="{{ route('subject-lessons.index') }}" class="px-4 py-2 border rounded-lg text-sm">Huỷ</a>
                    <button type="submit" data-import-submit data-idle-label="Import"
                            class="inline-flex items-center gap-2 px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-semibold disabled:pointer-events-none transition">
                        <i data-import-submit-icon class="bi bi-upload"></i>
                        <span data-import-submit-label>Import</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    @php
        $allSpecializations = $specializations->map(fn ($s) => [
            'value' => $s->id,
            'text' => $s->selection_label,
            'training_system_id' => $s->training_system_id,
        ])->values();
        $allSubjects = $subjects->map(fn ($s) => [
            'value' => $s->id,
            'text' => $s->display_code.' — '.$s->name,
            'specialization_id' => $s->specialization_id,
        ])->values();
    @endphp
    const allSpecializations = @json($allSpecializations);
    const allSubjects = @json($allSubjects);
    const oldSystemId = @json(old('training_system_id') ? (string) old('training_system_id') : '');
    const oldSpecializationId = @json(old('specialization_id') ? (string) old('specialization_id') : '');
    const oldSubjectId = @json(old('subject_id') ? (string) old('subject_id') : '');

    function boot() {
        const system = document.getElementById('training-system-id');
        const specialization = document.getElementById('specialization-id');
        const subject = document.getElementById('subject-id');
        if (!system || !specialization || !subject) return;
        if (typeof window.setTomSelectOptions !== 'function') {
            setTimeout(boot, 50);
            return;
        }
        // Trang này qua lại nhiều lần bằng Turbo (không reload toàn trang) —
        // tránh gắn listener trùng lặp trên cùng 1 lượt render.
        if (system.dataset.lessonImportBound === '1') return;
        system.dataset.lessonImportBound = '1';

        function fillSpecialization(systemId, selected) {
            const items = systemId
                ? allSpecializations.filter(s => String(s.training_system_id) === String(systemId))
                : allSpecializations;
            window.setTomSelectOptions(specialization, items, { selected: selected || '', enabled: true });
        }

        function fillSubject(specializationId, selected) {
            if (!specializationId) {
                window.setTomSelectOptions(subject, [], { selected: '', enabled: true });
                return;
            }
            const items = allSubjects.filter(s => String(s.specialization_id) === String(specializationId));
            window.setTomSelectOptions(subject, items, { selected: selected || '', enabled: true });
        }

        // addEventListener trực tiếp trên <select> gốc — KHÔNG dùng onTomChange:
        // setTomSelectOptions destroy + tạo lại instance Tom Select mới mỗi lần
        // đổi Hệ/Ngành, nên handler gắn qua el.tomselect.on(...) sẽ bị rớt theo
        // instance cũ. Listener native gắn trên chính node <select> thì không
        // bị ảnh hưởng bởi việc huỷ/tạo lại Tom Select instance.
        system.addEventListener('change', function () {
            const systemId = window.getSelectValue(system);
            fillSpecialization(systemId, '');
            fillSubject('', '');
        });
        specialization.addEventListener('change', function () {
            fillSubject(window.getSelectValue(specialization), '');
        });

        // Khởi tạo lần đầu: giữ lại giá trị cũ (validation lỗi quay lại form)
        fillSpecialization(oldSystemId, oldSpecializationId);
        fillSubject(oldSpecializationId, oldSubjectId);
    }

    // Điều hướng nội bộ qua Turbo không reload toàn trang -> không tự chạy lại
    // <script> nằm sẵn trong HTML, phải tự chạy boot() ở cả 2 mốc.
    document.addEventListener('DOMContentLoaded', boot);
    document.addEventListener('turbo:load', boot);
    if (document.readyState !== 'loading') boot();
})();
</script>
@endpush

@include('subject::partials.lesson-import-ajax-script')
