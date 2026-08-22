@extends('layouts.admin')
@section('title', $survey->title)
@section('page-title', 'Kết quả khảo sát')
@section('content')
<a href="{{ route('lms.courses.surveys.index', $course) }}" class="text-sm text-blue-600">← Khảo sát</a>
<h1 class="text-xl font-bold mt-2 mb-1">{{ $survey->title }}</h1>
<p class="text-sm text-slate-500 mb-4">{{ $responses->count() }} phản hồi</p>

<div class="grid lg:grid-cols-2 gap-4 mb-6">
@foreach($survey->questions as $q)
    <div class="bg-white border rounded-xl p-4 text-sm">
        <div class="font-medium mb-2">{{ $q->stem }}</div>
        @if($q->type === 'rating_1_5')
            <div class="text-2xl font-bold text-teal-700">{{ $stats[$q->id]['avg'] ?? '—' }} <span class="text-sm font-normal text-slate-400">/ 5</span></div>
            <div class="text-xs text-slate-500">{{ $stats[$q->id]['count'] ?? 0 }} đánh giá</div>
        @else
            <div class="text-xs text-slate-500">{{ $stats[$q->id]['count'] ?? 0 }} câu trả lời (xem danh sách bên dưới)</div>
        @endif
    </div>
@endforeach
</div>

<div class="bg-white border rounded-xl p-4 mb-4">
    <h2 class="font-semibold text-sm mb-2">Thêm câu hỏi</h2>
    <form method="POST" action="{{ route('lms.courses.surveys.questions.store', [$course, $survey]) }}" class="grid sm:grid-cols-2 gap-2 text-sm">
        @csrf
        <select name="type" class="border rounded-lg px-2 py-1.5">
            <option value="rating_1_5">Thang 1–5</option>
            <option value="mcq">Trắc nghiệm</option>
            <option value="text">Tự luận</option>
        </select>
        <input name="stem" required placeholder="Nội dung câu hỏi" class="border rounded-lg px-2 py-1.5">
        <textarea name="options" rows="2" placeholder="MCQ: mỗi dòng 1 lựa chọn" class="sm:col-span-2 border rounded-lg px-2 py-1.5"></textarea>
        <button class="sm:col-span-2 bg-slate-800 text-white rounded-lg px-3 py-2">Thêm</button>
    </form>
</div>

<div class="bg-white border rounded-xl overflow-hidden">
<table class="min-w-full text-xs">
<thead class="bg-slate-50"><tr>
<th class="text-left px-3 py-2">HV</th><th class="text-left px-3 py-2">Lúc</th><th class="text-left px-3 py-2">Tóm tắt</th>
</tr></thead>
<tbody class="divide-y">
@foreach($responses as $r)
<tr>
    <td class="px-3 py-2">{{ $survey->is_anonymous ? 'Ẩn danh' : ($r->user?->name ?? '—') }}</td>
    <td class="px-3 py-2">{{ $r->submitted_at?->format('d/m H:i') }}</td>
    <td class="px-3 py-2 max-w-md truncate">{{ is_array($r->answers) ? collect($r->answers)->take(3)->map(fn($v,$k)=>"$k:$v")->implode(' · ') : '' }}</td>
</tr>
@endforeach
</tbody>
</table>
</div>
@endsection
