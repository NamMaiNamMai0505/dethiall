@extends('layouts.admin')
@section('title', $template->title)
@section('page-title', 'Template khảo sát')

@section('content')
    <a href="{{ route('lms.survey-templates.index') }}" class="text-sm text-blue-600">← Template</a>
    <h1 class="text-xl font-bold mt-2 mb-1">{{ $template->title }}</h1>
    <p class="text-sm text-slate-500 mb-4">{{ $template->description }}</p>

    <div class="grid lg:grid-cols-2 gap-4">
        <div class="bg-white border rounded-xl p-4">
            <h2 class="font-semibold text-sm mb-3">Thêm câu hỏi</h2>
            <form method="POST" action="{{ route('lms.survey-templates.questions.store', $template) }}" class="space-y-2">
                @csrf
                <select name="type" class="w-full border rounded-lg text-sm px-3 py-2">
                    <option value="rating_1_5">Rating 1–5</option>
                    <option value="mcq">Trắc nghiệm</option>
                    <option value="text">Tự luận</option>
                </select>
                <textarea name="stem" required rows="2" class="w-full border rounded-lg text-sm px-3 py-2" placeholder="Nội dung câu *"></textarea>
                <textarea name="options" rows="2" class="w-full border rounded-lg text-sm px-3 py-2" placeholder="Options MCQ (mỗi dòng 1)"></textarea>
                <label class="text-sm flex gap-2 items-center"><input type="checkbox" name="is_required" value="1" checked> Bắt buộc</label>
                <button class="bg-blue-600 text-white rounded-lg text-sm px-4 py-2">Thêm</button>
            </form>
        </div>
        <div class="bg-white border rounded-xl overflow-hidden">
            <div class="px-4 py-2 border-b font-semibold text-sm">Câu hỏi ({{ $template->questions->count() }})</div>
            <ul class="divide-y text-sm">
                @forelse($template->questions as $q)
                    <li class="px-4 py-2">
                        <span class="text-xs text-slate-400">{{ $q->type }}</span>
                        <div class="font-medium">{{ $q->stem }}</div>
                    </li>
                @empty
                    <li class="px-4 py-8 text-center text-slate-500">Chưa có câu.</li>
                @endforelse
            </ul>
        </div>
    </div>
@endsection
