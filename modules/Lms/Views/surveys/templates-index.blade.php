@extends('layouts.admin')
@section('title', 'Template khảo sát LMS')
@section('page-title', 'Template khảo sát')

@section('content')
    <x-breadcrumb :items="[
        ['title' => 'LMS', 'url' => route('lms.hub')],
        ['title' => 'Template khảo sát'],
    ]" />

    <div class="flex flex-wrap justify-between gap-3 mb-5">
        <div>
            <h1 class="text-xl font-bold text-slate-900">Template khảo sát (cross-course)</h1>
            <p class="text-sm text-slate-500">Tạo một lần và áp dụng cho nhiều khóa học LMS.</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-4">
        <div class="bg-white border rounded-xl p-4">
            <h2 class="font-semibold mb-3 text-sm">Tạo template mới</h2>
            <form method="POST" action="{{ route('lms.survey-templates.store') }}" class="space-y-3">
                @csrf
                <input name="title" required maxlength="255" placeholder="Tên template *"
                       class="w-full border rounded-lg text-sm px-3 py-2">
                <textarea name="description" rows="2" placeholder="Mô tả" class="w-full border rounded-lg text-sm px-3 py-2"></textarea>
                <button class="w-full bg-blue-600 text-white rounded-lg text-sm py-2 font-semibold">Tạo</button>
            </form>
        </div>
        <div class="lg:col-span-2 bg-white border rounded-xl overflow-hidden">
            <table class="min-w-full text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase text-slate-500">
                <tr>
                    <th class="px-4 py-2">Template</th>
                    <th class="px-4 py-2">Câu hỏi</th>
                    <th class="px-4 py-2">TT</th>
                    <th class="px-4 py-2"></th>
                </tr>
                </thead>
                <tbody class="divide-y">
                @forelse($templates as $t)
                    <tr>
                        <td class="px-4 py-2 font-medium">{{ $t->title }}</td>
                        <td class="px-4 py-2">{{ $t->questions_count }}</td>
                        <td class="px-4 py-2">{{ $t->is_active ? 'Active' : 'Tắt' }}</td>
                        <td class="px-4 py-2 text-right">
                            <a href="{{ route('lms.survey-templates.show', $t) }}" class="text-blue-600 font-medium">Sửa</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-4 py-10 text-center text-slate-500">Chưa có template.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
