@extends('layouts.admin')
@section('title', 'Khảo sát — '.$course->title)
@section('page-title', 'Khảo sát CLĐT')
@section('content')
<a href="{{ route('lms.courses.show', $course) }}" class="text-sm text-blue-600">← {{ $course->title }}</a>
<h1 class="text-xl font-bold mt-2 mb-4">Khảo sát chất lượng đào tạo</h1>
<div class="bg-white border rounded-xl p-4 mb-4">
<form method="POST" action="{{ route('lms.courses.surveys.store', $course) }}" class="grid sm:grid-cols-2 gap-3 text-sm">
    @csrf
    <input name="title" required placeholder="Tên khảo sát" class="border rounded-lg px-3 py-2 sm:col-span-2" value="Khảo sát chất lượng khóa học">
    <textarea name="description" rows="2" placeholder="Mô tả" class="border rounded-lg px-3 py-2 sm:col-span-2"></textarea>
    <label class="flex items-center gap-2"><input type="checkbox" name="is_published" value="1" checked> Công bố ngay</label>
    <label class="flex items-center gap-2"><input type="checkbox" name="is_anonymous" value="1"> Ẩn danh trên báo cáo</label>
    <button class="sm:col-span-2 bg-blue-600 text-white rounded-lg px-3 py-2">Tạo khảo sát (kèm câu mặc định)</button>
</form>
</div>
<ul class="bg-white border rounded-xl divide-y">
@forelse($surveys as $s)
    <li class="px-4 py-3 flex flex-wrap justify-between gap-2 text-sm">
        <div>
            <strong>{{ $s->title }}</strong>
            <span class="text-slate-500">· {{ $s->questions_count }} câu · {{ $s->responses_count }} phản hồi
                · {{ $s->is_published ? 'Đang mở' : 'Nháp' }}
            </span>
        </div>
        <div class="flex gap-2">
            <form method="POST" action="{{ route('lms.courses.surveys.publish', [$course, $s]) }}">@csrf
                <button class="text-slate-600">{{ $s->is_published ? 'Ẩn' : 'Công bố' }}</button>
            </form>
            <a href="{{ route('lms.courses.surveys.show', [$course, $s]) }}" class="text-blue-600">Chi tiết / KQ</a>
        </div>
    </li>
@empty
    <li class="px-4 py-8 text-center text-slate-500">Chưa có khảo sát.</li>
@endforelse
</ul>
@endsection
