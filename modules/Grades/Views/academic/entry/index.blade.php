@extends('layouts.grades')
@section('title', 'Vào điểm lớp · môn')

@section('content')
<div class="mb-6">
    <a href="{{ route('grades.academic.hub') }}" class="text-sm text-teal-700 font-semibold hover:underline">← Tổng kết · TN</a>
    <h1 class="text-2xl font-bold text-slate-900 mt-1">Chọn lớp · môn cần vào điểm</h1>
    <p class="text-sm text-slate-500 mt-1">
        @if($canEnter)
            Vào điểm trực tiếp, import Excel, ghi rèn luyện / kỷ luật.
        @else
            Chỉ xem / in theo ma trận quyền.
        @endif
    </p>
</div>

<form method="GET" class="grades-card p-5 grid sm:grid-cols-3 gap-3 mb-6">
    <div>
        <label class="text-xs font-semibold text-slate-500">Môn / học phần</label>
        <select name="subject_id" class="w-full border rounded-lg px-3 py-2 text-sm mt-1" onchange="this.form.submit()">
            <option value="">— Chọn môn —</option>
            @foreach($subjects as $s)
                <option value="{{ $s->id }}" @selected((int)$subjectId === (int)$s->id)>{{ $s->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Lớp</label>
        <select name="class_id" class="w-full border rounded-lg px-3 py-2 text-sm mt-1" onchange="this.form.submit()">
            <option value="">— Chọn lớp —</option>
            @foreach($classes as $c)
                <option value="{{ $c->id }}" @selected((int)$classId === (int)$c->id)>{{ $c->name }}</option>
            @endforeach
        </select>
    </div>
    <div>
        <label class="text-xs font-semibold text-slate-500">Năm học</label>
        <x-academic-year-select
            name="academic_year"
            :selected="$year ?: \App\Support\AcademicYearCatalog::currentCode()"
            class="mt-1 text-sm"
        />
    </div>
</form>

@if($classId && $subjectId)
    <div class="grid sm:grid-cols-2 gap-4 mb-6">
        @if($canEnter)
        <div class="grades-card p-5">
            <h2 class="font-semibold mb-3">Vào điểm trực tiếp</h2>
            <form method="POST" action="{{ route('grades.academic.entry.open') }}" data-turbo="false">
                @csrf
                <input type="hidden" name="class_id" value="{{ $classId }}">
                <input type="hidden" name="subject_id" value="{{ $subjectId }}">
                <input type="hidden" name="academic_year" value="{{ $year }}">
                <button class="grades-btn grades-btn-solid">Mở / tạo bảng điểm</button>
            </form>
        </div>
        <div class="grades-card p-5">
            <h2 class="font-semibold mb-3">Vào điểm từ file Excel</h2>
            <p class="text-xs text-slate-500 mb-2">
                Cột: <strong>Mã HV</strong> hoặc <strong>Họ tên</strong>,
                <strong>15 phút</strong>, <strong>1 tiết</strong>, <strong>Giữa kỳ</strong>, <strong>Điểm thi</strong>
            </p>
            <form method="POST" action="{{ route('grades.academic.entry.import') }}" enctype="multipart/form-data" data-turbo="false" class="space-y-2">
                @csrf
                <input type="hidden" name="class_id" value="{{ $classId }}">
                <input type="hidden" name="subject_id" value="{{ $subjectId }}">
                <input type="hidden" name="academic_year" value="{{ $year }}">
                <input type="file" name="file" accept=".xlsx,.xls,.csv" required class="text-sm w-full">
                <button class="grades-btn grades-btn-teal">Import điểm</button>
            </form>
        </div>
        <div class="grades-card p-5 sm:col-span-2">
            <h2 class="font-semibold mb-3">Rèn luyện · Kỷ luật · Tạm ngừng học</h2>
            <form method="POST" action="{{ route('grades.academic.entry.conduct') }}" class="grid sm:grid-cols-3 gap-3" data-turbo="false">
                @csrf
                <input type="hidden" name="class_id" value="{{ $classId }}">
                <input type="hidden" name="academic_year" value="{{ $year }}">
                <div class="sm:col-span-2">
                    <label class="text-xs font-semibold text-slate-500">Học viên *</label>
                    <select name="user_id" required class="w-full border rounded-lg px-3 py-2 text-sm mt-1">
                        <option value="">— Chọn học viên trong lớp —</option>
                        @foreach($students as $st)
                            <option value="{{ $st->id }}">{{ $st->name }}@if($st->code) ({{ $st->code }})@endif</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500">Xếp loại RL</label>
                    <select name="conduct_rank" class="w-full border rounded-lg px-3 py-2 text-sm mt-1">
                        <option value="">—</option>
                        @foreach(['Xuất sắc','Tốt','Khá','Trung bình','Yếu'] as $rk)
                            <option value="{{ $rk }}">{{ $rk }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="text-xs font-semibold text-slate-500">Kỷ luật</label>
                    <input name="discipline" class="w-full border rounded-lg px-3 py-2 text-sm mt-1" placeholder="VD: Khiển trách / Đình chỉ">
                </div>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="suspended" value="1"> Tạm ngừng / thôi học
                </label>
                <div>
                    <button class="grades-btn grades-btn-ghost">Ghi kết quả</button>
                </div>
            </form>

            @if($students->isNotEmpty())
            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full text-sm grades-table">
                    <thead class="bg-slate-50 border-b">
                    <tr>
                        <th class="px-2 py-2 text-left">HV</th>
                        <th class="px-2 py-2 text-left">RL</th>
                        <th class="px-2 py-2 text-left">Kỷ luật</th>
                        <th class="px-2 py-2 text-left">TN học</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    @foreach($students as $st)
                        @php $c = $conducts[$st->id] ?? null; @endphp
                        <tr>
                            <td class="px-2 py-1.5">{{ $st->name }}</td>
                            <td class="px-2 py-1.5">{{ $c->conduct_rank ?? '—' }}</td>
                            <td class="px-2 py-1.5">{{ $c->discipline ?? '—' }}</td>
                            <td class="px-2 py-1.5">{{ $c && $c->suspended ? 'Có' : '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
        @else
        <div class="grades-card p-5 sm:col-span-2 text-sm text-slate-500">
            Vai trò của bạn chỉ được <strong>xem / in</strong> — không vào điểm trực tiếp hay file.
        </div>
        @endif
    </div>

    <div class="grades-card overflow-hidden">
        <div class="px-4 py-3 border-b font-semibold">Bảng điểm đã có</div>
        <ul class="divide-y">
            @forelse($books as $b)
                <li>
                    <a href="{{ route('grades.books.show', $b) }}" class="block px-4 py-3 hover:bg-orange-50/40">
                        <div class="font-medium">{{ $b->title }}</div>
                        <div class="text-xs text-slate-500">{{ $b->statusLabel() }} · {{ $b->academic_year }}</div>
                    </a>
                </li>
            @empty
                <li class="px-4 py-8 text-center text-sm text-slate-500">Chưa có bảng điểm cho cặp lớp–môn này.</li>
            @endforelse
        </ul>
    </div>
@endif
@endsection
