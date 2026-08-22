@extends('layouts.admin')

@section('title', 'Sửa khóa học LMS')
@section('page-title', 'Sửa khóa học LMS')

@section('content')
    <x-breadcrumb :items="[
        ['title' => 'Trang chủ'],
        ['title' => 'LMS', 'url' => route('lms.hub')],
        ['title' => $course->title, 'url' => route('lms.courses.show', $course)],
        ['title' => 'Sửa'],
    ]" />

    <div class="max-w-2xl mx-auto bg-white rounded-xl border shadow-sm p-6">
        <h1 class="text-xl font-bold mb-4">Sửa: {{ $course->title }}</h1>
        <p class="text-xs text-slate-500 mb-4">Môn: <strong>{{ $course->subject->name ?? '—' }}</strong> · Lớp: <strong>{{ $course->classModel->name ?? '—' }}</strong> (không đổi sau khi tạo)</p>

        <form method="POST" action="{{ route('lms.courses.update', $course) }}" class="space-y-4">
            @csrf
            @method('PUT')
            <div>
                <label class="block text-sm font-semibold mb-1">Tiêu đề *</label>
                <input type="text" name="title" value="{{ old('title', $course->title) }}" required class="w-full border rounded-lg text-sm px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Mã</label>
                <input type="text" name="code" value="{{ old('code', $course->code) }}" class="w-full border rounded-lg text-sm px-3 py-2 font-mono">
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Giảng viên phụ trách</label>
                <select name="instructor_id" class="w-full border rounded-lg text-sm px-3 py-2">
                    <option value="">— Không gán —</option>
                    @foreach($instructors as $ins)
                        <option value="{{ $ins->id }}" @selected(old('instructor_id', $course->instructor_id) == $ins->id)>
                            {{ $ins->name }}@if($ins->code) ({{ $ins->code }})@endif
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-slate-500 mt-1">Danh sách gợi ý từ Teaching Assignment của môn.</p>
            </div>
            <div class="grid sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-sm font-semibold mb-1">Năm học</label>
                    <select name="academic_year_id" class="w-full border rounded-lg text-sm px-3 py-2" data-searchable="1">
                        <option value="">— Chưa xác định —</option>
                        @foreach($academicYears as $year)
                            <option value="{{ $year->id }}" @selected(old('academic_year_id', $course->academic_year_id) == $year->id)>{{ $year->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Học kỳ</label>
                    <select name="term" class="w-full border rounded-lg text-sm px-3 py-2">
                        <option value="">— Chưa xác định —</option>
                        @foreach(['semester_1' => 'Học kỳ 1', 'semester_2' => 'Học kỳ 2', 'semester_3' => 'Học kỳ 3', 'semester_4' => 'Học kỳ 4', 'semester_5' => 'Học kỳ 5', 'semester_6' => 'Học kỳ 6', 'summer' => 'Học kỳ hè'] as $termValue => $termLabel)
                            <option value="{{ $termValue }}" @selected(old('term', $course->term) === $termValue)>{{ $termLabel }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Trạng thái</label>
                <select name="status" class="w-full border rounded-lg text-sm px-3 py-2">
                    @foreach(['draft' => 'Nháp', 'published' => 'Đang mở', 'archived' => 'Lưu trữ'] as $k => $lab)
                        <option value="{{ $k }}" @selected(old('status', $course->status) === $k)>{{ $lab }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold mb-1">Mô tả</label>
                <textarea name="description" rows="3" class="w-full border rounded-lg text-sm px-3 py-2">{{ old('description', $course->description) }}</textarea>
            </div>
            <label class="inline-flex items-center gap-2 text-sm">
                <input type="checkbox" name="resync_members" value="1" class="rounded border-slate-300">
                Đồng bộ lại thành viên sau khi lưu
            </label>
            <div class="flex justify-between pt-2">
                <div>
                    @can('lms.delete')
                        <button type="submit" form="del-course" class="text-red-600 text-sm hover:underline">Xoá</button>
                    @endcan
                </div>
                <div class="flex gap-2">
                    <a href="{{ route('lms.courses.show', $course) }}" class="px-4 py-2 border rounded-lg text-sm">Huỷ</a>
                    <button type="submit" class="px-5 py-2 bg-blue-600 text-white rounded-lg text-sm font-semibold">Lưu</button>
                </div>
            </div>
        </form>
        @can('lms.delete')
            <form id="del-course" method="POST" action="{{ route('lms.courses.destroy', $course) }}" class="hidden"
                  data-confirm="Xoá khóa học này? Hành động có thể không hoàn tác."
                  data-confirm-danger="1"
                  data-confirm-title="Xoá khóa học"
                  data-confirm-ok="Xoá khóa">
                @csrf
                @method('DELETE')
            </form>
        @endcan
    </div>
@endsection
