@extends('layouts.admin')
@section('title', 'Bài tập — '.$course->title)
@section('page-title', 'Bài tập LMS')
@section('content')
<div class="mb-4 flex flex-wrap justify-between gap-2">
    <div>
        <a href="{{ route('lms.courses.show', $course) }}" class="text-sm text-blue-600">← {{ $course->title }}</a>
        <h1 class="text-xl font-bold mt-1">Bài tập</h1>
        <p class="text-sm text-slate-500">Gắn bài tập theo bài học để học viên nộp đúng phần.</p>
    </div>
</div>
<div class="bg-white border rounded-xl p-4 mb-4 shadow-sm">
    <form method="POST" action="{{ route('lms.courses.assignments.store', $course) }}" class="grid sm:grid-cols-2 gap-3">
        @csrf
        <input name="title" required placeholder="Tên bài tập" class="border rounded-lg text-sm px-3 py-2">
        <select name="lms_lesson_id" class="border rounded-lg text-sm px-3 py-2 tom-select">
            <option value="">— Không gắn bài (chung) —</option>
            @foreach($lessons ?? [] as $lesson)
                <option value="{{ $lesson->id }}">{{ $lesson->sort_order }}. {{ $lesson->title }}</option>
            @endforeach
        </select>
        <input type="number" step="0.1" name="max_score" value="{{ \Modules\Lms\Support\LmsSettings::assignmentMaxScore() }}" class="border rounded-lg text-sm px-3 py-2" placeholder="Điểm tối đa">
        <input type="datetime-local" name="due_at" class="border rounded-lg text-sm px-3 py-2">
        <input type="hidden" name="allow_late" value="0">
        <label class="text-sm flex items-center gap-2">
            <input type="checkbox" name="allow_late" value="1" @checked(\Modules\Lms\Support\LmsSettings::allowLateByDefault())>
            Cho nộp muộn
        </label>
        <textarea name="description" rows="2" placeholder="Mô tả" class="sm:col-span-2 border rounded-lg text-sm px-3 py-2"></textarea>
        <label class="text-sm flex items-center gap-2"><input type="checkbox" name="is_published" value="1" checked> Công bố</label>
        <button class="bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm px-4 py-2 font-semibold">Tạo bài tập</button>
    </form>
</div>
<div class="bg-white border rounded-xl overflow-hidden shadow-sm">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50"><tr>
            <th class="text-left px-4 py-2">Bài tập</th>
            <th class="text-left px-4 py-2">Bài học</th>
            <th class="text-left px-4 py-2">Hạn</th>
            <th class="text-left px-4 py-2">Điểm</th>
            <th class="px-4 py-2"></th>
        </tr></thead>
        <tbody class="divide-y">
        @forelse($assignments as $a)
            <tr>
                <td class="px-4 py-2 font-medium">{{ $a->title }}</td>
                <td class="px-4 py-2 text-slate-600">{{ $a->lesson->title ?? '—' }}</td>
                <td class="px-4 py-2">{{ $a->due_at?->format('d/m/Y H:i') ?? '—' }}</td>
                <td class="px-4 py-2">{{ $a->max_score }}</td>
                <td class="px-4 py-2 text-right">
                    <a class="text-teal-700 font-medium" href="{{ route('lms.courses.assignments.submissions', [$course, $a]) }}">Chấm bài</a>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="px-4 py-8 text-center text-slate-500">Chưa có bài tập.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
