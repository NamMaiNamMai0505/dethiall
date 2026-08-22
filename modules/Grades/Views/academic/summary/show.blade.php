@extends('layouts.grades')
@section('title', $session->title)

@section('content')
<div class="flex flex-wrap items-end justify-between gap-3 mb-4">
    <div>
        <a href="{{ route('grades.academic.summary.index') }}" class="text-sm text-teal-700 font-semibold hover:underline">← Đợt xét</a>
        <h1 class="text-xl font-bold text-slate-900 mt-1">{{ $session->title }}</h1>
        <p class="text-sm text-slate-500">Năm {{ $session->academic_year }} · <span class="grades-chip grades-chip-wait">{{ $session->statusLabel() }}</span></p>
    </div>
    <div class="flex flex-wrap gap-2">
        @if($canEdit)
            <form method="POST"
                  action="{{ route('grades.academic.summary.calculate', $session) }}"
                  data-confirm="Tính / cập nhật kết quả từ điểm TB?"
                  data-confirm-title="Tính kết quả"
                  data-confirm-ok="Tính kết quả"
                  data-turbo="false">
                @csrf
                <button class="grades-btn grades-btn-teal">Tính kết quả</button>
            </form>
            @if($session->status !== 'pending_approve' && $session->status !== 'approved')
            <form method="POST" action="{{ route('grades.academic.summary.submit', $session) }}" data-turbo="false">
                @csrf
                <button class="grades-btn grades-btn-ghost">Gửi phê duyệt</button>
            </form>
            @endif
        @endif
        @if($canApprove && $session->status === 'pending_approve')
            <form method="POST" action="{{ route('grades.academic.summary.approve', $session) }}" data-turbo="false">
                @csrf
                <button class="grades-btn grades-btn-solid">BGH phê duyệt</button>
            </form>
        @endif
        <a href="{{ route('grades.academic.summary.print', $session) }}" class="grades-btn grades-btn-ghost" target="_blank" data-turbo="false">
            <i class="bi bi-printer"></i> In sổ điểm
        </a>
    </div>
</div>

<form method="GET" class="mb-4 flex flex-wrap gap-2 items-end">
    <div>
        <label class="text-xs font-semibold text-slate-500">Lọc danh sách</label>
        <select name="status" class="border border-slate-200 rounded-lg px-3 py-2 text-sm block mt-1" onchange="this.form.submit()">
            <option value="">— Tất cả —</option>
            @foreach($statuses as $k => $lab)
                <option value="{{ $k }}" @selected($filter === $k)>{{ $lab }}</option>
            @endforeach
        </select>
    </div>
</form>

<div class="grades-card overflow-x-auto">
    <table class="min-w-full text-sm grades-table">
        <thead class="bg-slate-50 border-b">
        <tr>
            <th class="px-2 py-2 text-left">#</th>
            <th class="px-2 py-2 text-left">Họ tên</th>
            <th class="px-2 py-2 text-left">Lớp</th>
            <th class="px-2 py-2 text-left">GPA</th>
            <th class="px-2 py-2 text-left">Điểm TN</th>
            <th class="px-2 py-2 text-left">Kết quả</th>
            <th class="px-2 py-2 text-left">Thi lại</th>
            @if($canEdit)<th class="px-2 py-2"></th>@endif
        </tr>
        </thead>
        <tbody class="divide-y">
        @forelse($results as $r)
            <tr class="hover:bg-orange-50/30">
                <td class="px-2 py-2">{{ $r->rank_order ?? '—' }}</td>
                <td class="px-2 py-2 font-medium">{{ $r->student?->name }}</td>
                <td class="px-2 py-2 text-slate-600">{{ $r->classModel?->name }}</td>
                <td class="px-2 py-2">{{ \Modules\Grades\Support\GradeSettings::format($r->gpa) }}</td>
                <td class="px-2 py-2">{{ \Modules\Grades\Support\GradeSettings::format($r->exam_score) }}</td>
                <td class="px-2 py-2"><span class="grades-chip grades-chip-open">{{ $r->statusLabel() }}</span></td>
                <td class="px-2 py-2">{{ $r->retake_registered ? 'Có' : '—' }}</td>
                @if($canEdit)
                <td class="px-2 py-2 text-right">
                    <details class="inline-block text-left">
                        <summary class="cursor-pointer text-teal-700 font-semibold text-xs">Sửa</summary>
                        <form method="POST" action="{{ route('grades.academic.results.update', $r) }}" class="mt-2 p-3 grades-card space-y-2 w-64" data-turbo="false">
                            @csrf @method('PATCH')
                            <select name="result_status" class="w-full border rounded-lg px-2 py-1 text-xs">
                                @foreach($statuses as $k => $lab)
                                    <option value="{{ $k }}" @selected($r->result_status === $k)>{{ $lab }}</option>
                                @endforeach
                            </select>
                            <input type="number" step="0.01" name="gpa" value="{{ $r->gpa }}" placeholder="GPA" class="w-full border rounded-lg px-2 py-1 text-xs">
                            <input type="number" step="0.01" name="exam_score" value="{{ $r->exam_score }}" placeholder="Điểm TN" class="w-full border rounded-lg px-2 py-1 text-xs">
                            <input type="number" name="rank_order" value="{{ $r->rank_order }}" placeholder="Thứ tự" class="w-full border rounded-lg px-2 py-1 text-xs">
                            <label class="flex items-center gap-1 text-xs"><input type="checkbox" name="retake_registered" value="1" @checked($r->retake_registered)> Đăng ký thi lại TN</label>
                            <button class="grades-btn grades-btn-solid text-xs w-full">Lưu</button>
                        </form>
                    </details>
                </td>
                @endif
            </tr>
        @empty
            <tr><td colspan="8" class="px-4 py-10 text-center text-slate-500">Chưa có danh sách. Bấm «Tính kết quả».</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="p-3">{{ $results->links() }}</div>
</div>
@endsection
