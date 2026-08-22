@extends('layouts.grades')
@section('title', 'Tạo bảng điểm')
@section('content')
<div class="max-w-lg mx-auto grades-card p-6">
    <div class="mb-4">
        <a href="{{ route('grades.hub') }}" class="text-sm text-teal-700 font-semibold hover:underline">← Hub điểm</a>
        <h1 class="text-xl font-bold mt-1">Tạo bảng điểm</h1>
        <p class="text-xs text-slate-500 mt-1">
            @if(auth()->user() && \Modules\Grades\Services\GradeAccess::usesFacultyWizard(auth()->user()))
                PDOT: chọn Môn + Lớp để quản lý điểm.
            @else
                Giảng viên: chọn <strong>Môn</strong> trước, rồi <strong>Lớp</strong> bạn dạy.
            @endif
        </p>
    </div>
    <form method="POST" action="{{ route('grades.books.store') }}" class="space-y-4">
        @csrf
        <div>
            <label class="block text-sm font-medium mb-1">Tiêu đề *</label>
            <input type="text" name="title" value="{{ old('title', 'Bảng điểm '.now()->format('m/Y')) }}" required
                   class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Môn *</label>
            <select name="subject_id" id="subject_id" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                <option value="">Chọn môn</option>
                @foreach($subjects as $id => $name)
                    <option value="{{ $id }}" @selected((string)old('subject_id', $prefillSubjectId) === (string)$id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Lớp *</label>
            <select name="class_id" id="class_id" required class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm">
                <option value="">Chọn lớp</option>
                @foreach($classes as $id => $name)
                    <option value="{{ $id }}" @selected((string)old('class_id', $prefillClassId) === (string)$id)>{{ $name }}</option>
                @endforeach
            </select>
            <p class="text-xs text-slate-500 mt-1">Chỉ lớp bạn dạy môn đã chọn.</p>
        </div>
        <div>
            <label class="block text-sm font-medium mb-1">Năm học</label>
            <x-academic-year-select
                name="academic_year"
                :selected="old('academic_year', \App\Support\AcademicYearCatalog::currentCode())"
                required
                class="text-sm"
            />
        </div>
        <div class="flex gap-2">
            <button type="submit" class="grades-btn grades-btn-solid">Tạo</button>
            <a href="{{ route('grades.hub') }}" class="grades-btn grades-btn-ghost">Huỷ</a>
        </div>
    </form>
</div>
@push('scripts')
<script>
(function () {
    var map = @json($classesBySubject ?? new \stdClass());
    var subjectSelect = document.getElementById('subject_id');
    var classSelect = document.getElementById('class_id');
    var keepClass = @json(old('class_id', $prefillClassId));

    function fillClasses(subjectId, keepId) {
        if (!classSelect) return;
        var opts = map[subjectId] || map[String(subjectId)] || {};
        classSelect.innerHTML = '<option value="">Chọn lớp</option>';
        Object.keys(opts).forEach(function (id) {
            var o = document.createElement('option');
            o.value = id;
            o.textContent = opts[id];
            if (keepId && String(keepId) === String(id)) o.selected = true;
            classSelect.appendChild(o);
        });
        if (!Object.keys(opts).length) {
            classSelect.innerHTML = '<option value="">— Không có lớp cho môn này —</option>';
        }
    }

    subjectSelect?.addEventListener('change', function () {
        fillClasses(subjectSelect.value, null);
    });
    if (subjectSelect?.value) {
        fillClasses(subjectSelect.value, keepClass || classSelect.value);
    }
})();
</script>
@endpush
@endsection
