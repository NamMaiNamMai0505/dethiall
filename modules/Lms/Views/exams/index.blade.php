@extends('layouts.admin')
@section('title', 'Thi online — '.$course->title)
@section('page-title', 'Thi & NHCH')
@section('content')
<a href="{{ route('lms.courses.show', $course) }}" class="text-sm text-blue-600">← {{ $course->title }}</a>
<h1 class="text-xl font-bold mt-2 mb-4">Ngân hàng đề & Bài thi</h1>

<div class="grid lg:grid-cols-2 gap-4 mb-6">
    <div class="bg-white border rounded-xl p-4 space-y-3">
        <h2 class="font-semibold">Ngân hàng đề</h2>
        <form method="POST" action="{{ route('lms.courses.exams.banks.store', $course) }}" class="flex gap-2">
            @csrf
            <input name="title" required placeholder="Tên NHCH" class="flex-1 border rounded-lg text-sm px-3 py-2">
            <button class="bg-slate-800 text-white rounded-lg text-sm px-3 py-2">Tạo</button>
        </form>
        @foreach($banks as $bank)
            <div class="border rounded-lg p-3 text-sm">
                <div class="font-medium mb-2">{{ $bank->title }} ({{ $bank->questions_count }} câu)</div>
                <form method="POST" action="{{ route('lms.courses.exams.questions.store', [$course, $bank]) }}" class="grid gap-2">
                    @csrf
                    <select name="type" class="border rounded-lg text-sm px-2 py-1.5">
                        <option value="mcq">Trắc nghiệm</option>
                        <option value="true_false">Đúng/Sai</option>
                        <option value="short">Điền ngắn</option>
                    </select>
                    <textarea name="stem" required rows="2" placeholder="Câu hỏi" class="border rounded-lg text-sm px-2 py-1.5"></textarea>
                    <textarea name="options" rows="3" placeholder="MCQ: mỗi dòng 1 đáp án" class="border rounded-lg text-sm px-2 py-1.5"></textarea>
                    <input name="correct_answer" required placeholder="Đáp án: 0 (index MCQ) / true / text" class="border rounded-lg text-sm px-2 py-1.5">
                    <input type="number" step="0.1" name="points" value="1" class="border rounded-lg text-sm px-2 py-1.5">
                    <button class="bg-blue-600 text-white rounded-lg text-sm px-3 py-1.5">Thêm câu</button>
                </form>
            </div>
        @endforeach
    </div>
    <div class="bg-white border rounded-xl p-4 space-y-3">
        <h2 class="font-semibold">Bài thi</h2>
        <form method="POST" action="{{ route('lms.courses.exams.store', $course) }}" class="grid sm:grid-cols-2 gap-2 text-sm">
            @csrf
            <input name="title" required placeholder="Tên bài thi" class="border rounded-lg px-3 py-2 sm:col-span-2">
            <input type="number" name="duration_minutes" value="{{ \Modules\Lms\Support\LmsSettings::examDurationMinutes() }}" class="border rounded-lg px-3 py-2" placeholder="Phút">
            <input type="number" name="max_attempts" value="{{ \Modules\Lms\Support\LmsSettings::examAttempts() }}" class="border rounded-lg px-3 py-2">
            <input type="number" step="0.1" name="pass_score" value="{{ \Modules\Lms\Support\LmsSettings::examPassScore() }}" class="border rounded-lg px-3 py-2" placeholder="Điểm đạt">
            <select name="bank_id" class="border rounded-lg px-3 py-2 sm:col-span-2">
                <option value="">— Lấy toàn bộ câu từ NHCH —</option>
                @foreach($banks as $bank)
                    <option value="{{ $bank->id }}">{{ $bank->title }}</option>
                @endforeach
            </select>
            <input type="hidden" name="shuffle_questions" value="0">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="shuffle_questions" value="1" @checked(\Modules\Lms\Support\LmsSettings::shuffleQuestions())>
                Xáo câu hỏi
            </label>
            <label class="flex items-center gap-2"><input type="checkbox" name="proctor_basic" value="1" checked> Proctor cơ bản</label>
            <label class="flex items-center gap-2"><input type="checkbox" name="is_published" value="1"> Công bố</label>
            <button class="sm:col-span-2 bg-blue-600 text-white rounded-lg px-3 py-2">Tạo bài thi</button>
        </form>
        <ul class="divide-y text-sm">
            @forelse($exams as $exam)
                <li class="py-2 flex justify-between">
                    <span><strong>{{ $exam->title }}</strong> · {{ $exam->questions_count }} câu · {{ $exam->duration_minutes }}'</span>
                    <a href="{{ route('lms.courses.exams.attempts', [$course, $exam]) }}" class="text-blue-600">Lượt làm</a>
                </li>
            @empty
                <li class="text-slate-500 py-4">Chưa có bài thi.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
