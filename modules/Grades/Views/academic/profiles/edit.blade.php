@extends('layouts.grades')
@section('title', 'Hồ sơ TN')

@section('content')
<div class="mb-4">
    <a href="{{ route('grades.academic.profiles.index') }}" class="text-sm text-teal-700 font-semibold hover:underline">← Hồ sơ</a>
    <h1 class="text-xl font-bold mt-1">Hồ sơ tốt nghiệp</h1>
    <p class="text-sm text-slate-500"><span class="grades-chip grades-chip-wait">{{ $profile->statusLabel() }}</span></p>
</div>

<form method="POST" action="{{ route('grades.academic.profiles.update', $profile) }}" class="grades-card p-5 space-y-4" data-turbo="false">
    @csrf @method('PUT')
    <div class="grid sm:grid-cols-2 gap-3">
        <div>
            <label class="text-xs font-semibold text-slate-500">Đợt xét</label>
            <select name="session_id" class="w-full border rounded-lg px-3 py-2 text-sm mt-1" @disabled(!$canEdit)>
                <option value="">—</option>
                @foreach($sessions as $s)
                    <option value="{{ $s->id }}" @selected($profile->session_id == $s->id)>{{ $s->academic_year }} · {{ $s->title }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Lớp</label>
            <select name="class_id" class="w-full border rounded-lg px-3 py-2 text-sm mt-1" @disabled(!$canEdit)>
                <option value="">—</option>
                @foreach($classes as $id => $name)
                    <option value="{{ $id }}" @selected($profile->class_id == $id)>{{ $name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Ngành</label>
            <input name="major_name" value="{{ old('major_name', $profile->major_name) }}" class="w-full border rounded-lg px-3 py-2 text-sm mt-1" @readonly(!$canEdit)>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Khóa</label>
            <input name="cohort" value="{{ old('cohort', $profile->cohort) }}" class="w-full border rounded-lg px-3 py-2 text-sm mt-1" @readonly(!$canEdit)>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Hệ</label>
            <input name="system_type" value="{{ old('system_type', $profile->system_type) }}" class="w-full border rounded-lg px-3 py-2 text-sm mt-1" @readonly(!$canEdit)>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Thời gian</label>
            <input name="duration_text" value="{{ old('duration_text', $profile->duration_text) }}" class="w-full border rounded-lg px-3 py-2 text-sm mt-1" @readonly(!$canEdit)>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Số quyết định</label>
            <input name="decision_number" value="{{ old('decision_number', $profile->decision_number) }}" class="w-full border rounded-lg px-3 py-2 text-sm mt-1" @readonly(!$canEdit)>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Ngày ký QĐ</label>
            <input type="date" name="decision_date" value="{{ old('decision_date', optional($profile->decision_date)->format('Y-m-d')) }}" class="w-full border rounded-lg px-3 py-2 text-sm mt-1" @readonly(!$canEdit)>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Ngày ký văn bằng</label>
            <input type="date" name="diploma_sign_date" value="{{ old('diploma_sign_date', optional($profile->diploma_sign_date)->format('Y-m-d')) }}" class="w-full border rounded-lg px-3 py-2 text-sm mt-1" @readonly(!$canEdit)>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Số vào sổ</label>
            <input name="registry_number" value="{{ old('registry_number', $profile->registry_number) }}" class="w-full border rounded-lg px-3 py-2 text-sm mt-1" @readonly(!$canEdit)>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Series văn bằng</label>
            <input name="series_number" value="{{ old('series_number', $profile->series_number) }}" class="w-full border rounded-lg px-3 py-2 text-sm mt-1" @readonly(!$canEdit)>
        </div>
        <div>
            <label class="text-xs font-semibold text-slate-500">Nhóm series</label>
            <input name="series_group" value="{{ old('series_group', $profile->series_group) }}" class="w-full border rounded-lg px-3 py-2 text-sm mt-1" @readonly(!$canEdit)>
        </div>
        <div class="sm:col-span-2">
            <label class="text-xs font-semibold text-slate-500">Ghi chú</label>
            <textarea name="note" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm mt-1" @readonly(!$canEdit)>{{ old('note', $profile->note) }}</textarea>
        </div>
    </div>
    <div class="flex flex-wrap gap-2">
        @if($canEdit)
            <button class="grades-btn grades-btn-solid">Ghi dữ liệu</button>
            @if($profile->status !== 'pending_approve' && $profile->status !== 'approved')
            <button formaction="{{ route('grades.academic.profiles.submit', $profile) }}" formmethod="POST" class="grades-btn grades-btn-ghost"
                    onclick="this.form.querySelector('[name=_method]')?.remove(); event.preventDefault(); fetch(this.formAction,{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'text/html'},body:new URLSearchParams({_token:'{{ csrf_token() }}'})}).then(()=>location.reload())">
                Gửi BGH duyệt
            </button>
            @endif
        @endif
        @if($canApprove && $profile->status === 'pending_approve')
            <button formaction="{{ route('grades.academic.profiles.approve', $profile) }}" class="grades-btn grades-btn-teal"
                    formmethod="POST" onclick="event.preventDefault(); fetch('{{ route('grades.academic.profiles.approve', $profile) }}',{method:'POST',headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}'},body:new URLSearchParams({_token:'{{ csrf_token() }}'})}).then(()=>location.reload())">
                BGH phê duyệt
            </button>
        @endif
        <a href="{{ route('grades.academic.profiles.print-score', $profile) }}" class="grades-btn grades-btn-ghost" target="_blank" data-turbo="false">In phiếu điểm</a>
        @if(\Modules\Grades\Services\GradeActor::canPrintDiploma(auth()->user()))
            <a href="{{ route('grades.academic.profiles.print-diploma', $profile) }}" class="grades-btn grades-btn-ghost" target="_blank" data-turbo="false">In văn bằng</a>
        @endif
    </div>
</form>

@if($canEdit && $profile->status !== 'approved')
<form method="POST" action="{{ route('grades.academic.profiles.submit', $profile) }}" class="mt-3" data-turbo="false">
    @csrf
    <button class="grades-btn grades-btn-ghost text-xs">Gửi phê duyệt hồ sơ</button>
</form>
@endif
@if($canApprove && $profile->status === 'pending_approve')
<form method="POST" action="{{ route('grades.academic.profiles.approve', $profile) }}" class="mt-2" data-turbo="false">
    @csrf
    <button class="grades-btn grades-btn-solid text-xs">Xác nhận BGH phê duyệt</button>
</form>
@endif
@endsection
