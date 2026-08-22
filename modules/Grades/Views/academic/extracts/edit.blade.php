@extends('layouts.grades')
@section('title', $student->name)

@section('content')
<div class="mb-4">
    <a href="{{ route('grades.academic.extracts.index') }}" class="text-sm text-teal-700 font-semibold">← DS trích ngang</a>
    <h1 class="text-xl font-bold mt-1">{{ $student->name }}</h1>
    <p class="text-sm text-slate-500">{{ $student->code }} · {{ $student->class?->name }}</p>
</div>

<form method="POST" action="{{ route('grades.academic.extracts.update', $student) }}" class="grades-card p-5 grid sm:grid-cols-2 gap-3" data-turbo="false">
    @csrf @method('PUT')
    @foreach([
        'student_code' => 'Mã HV',
        'birth_place' => 'Nơi sinh',
        'ethnicity' => 'Dân tộc',
        'religion' => 'Tôn giáo',
        'id_number' => 'CCCD/CMND',
        'id_issued_place' => 'Nơi cấp',
        'phone' => 'Điện thoại',
        'father_name' => 'Họ tên cha',
        'mother_name' => 'Họ tên mẹ',
    ] as $field => $label)
        <div>
            <label class="text-xs font-semibold text-slate-500">{{ $label }}</label>
            <input name="{{ $field }}" value="{{ old($field, $profile->$field) }}"
                   class="w-full border rounded-lg px-3 py-2 text-sm mt-1" @readonly(!$canEdit)>
        </div>
    @endforeach
    <div>
        <label class="text-xs font-semibold text-slate-500">Ngày cấp CCCD</label>
        <input type="date" name="id_issued_at" value="{{ old('id_issued_at', optional($profile->id_issued_at)->format('Y-m-d')) }}"
               class="w-full border rounded-lg px-3 py-2 text-sm mt-1" @readonly(!$canEdit)>
    </div>
    <div class="sm:col-span-2">
        <label class="text-xs font-semibold text-slate-500">Địa chỉ thường trú</label>
        <input name="permanent_address" value="{{ old('permanent_address', $profile->permanent_address) }}"
               class="w-full border rounded-lg px-3 py-2 text-sm mt-1" @readonly(!$canEdit)>
    </div>
    <div class="sm:col-span-2">
        <label class="text-xs font-semibold text-slate-500">Ghi chú</label>
        <textarea name="note" rows="2" class="w-full border rounded-lg px-3 py-2 text-sm mt-1" @readonly(!$canEdit)>{{ old('note', $profile->note) }}</textarea>
    </div>
    @if($canEdit)
        <div class="sm:col-span-2"><button class="grades-btn grades-btn-solid">Lưu</button></div>
    @endif
</form>
@endsection
