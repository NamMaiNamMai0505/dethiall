@extends('layouts.admin')

@section('title', 'Văn bản phê duyệt đề thi')
@section('content')
<div class="space-y-5">
    @include('partials.module-menu', ['module' => 'exam'])
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Văn bản phê duyệt đề thi</h1>
            <p class="text-sm text-slate-500 mt-1">Văn bản đã được BGH ký, khóa đề, tạo QR và chuyển xuống Ban Khảo thí.</p>
        </div>
    </div>
    <form class="bg-white border rounded-xl p-4 flex flex-wrap gap-3">
        <input name="search" value="{{ request('search') }}" placeholder="Mã QĐ, tên bộ đề, lớp hoặc môn" class="flex-1 min-w-[260px] border rounded-lg px-3 py-2">
        <button class="px-4 py-2 rounded-lg bg-slate-800 text-white">Tìm kiếm</button>
    </form>
    <div class="bg-white border rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-blue-50"><tr>
                    <th class="p-3 text-left">Mã QĐ</th><th class="p-3 text-left">Tên bộ đề phê duyệt</th>
                    <th class="p-3 text-left">Lớp</th><th class="p-3 text-left">Môn</th>
                    <th class="p-3 text-left">Người phê duyệt</th><th class="p-3 text-left">Thời gian</th><th></th>
                </tr></thead>
                <tbody class="divide-y">
                @forelse($documents as $document)
                    <tr>
                        <td class="p-3 font-mono font-semibold">{{ $document->decision_code }}</td>
                        <td class="p-3">{{ $document->title }}</td>
                        <td class="p-3">{{ $document->class_name ?: '—' }}</td>
                        <td class="p-3">{{ $document->subject_name ?: '—' }}</td>
                        <td class="p-3">{{ $document->approver_name ?: '—' }}</td>
                        <td class="p-3 whitespace-nowrap">{{ $document->approved_at?->format('d/m/Y H:i') ?: '—' }}</td>
                        <td class="p-3 text-right whitespace-nowrap"><a class="text-blue-600 font-semibold" href="{{ route('essay-exams.approval-documents.show', $document) }}">Xem</a> · <a class="text-emerald-700 font-semibold" href="{{ route('essay-exams.approval-documents.download', $document) }}">Tải</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-8 text-center text-slate-500">Chưa có văn bản phê duyệt.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4">{{ $documents->links() }}</div>
    </div>
</div>
@endsection
