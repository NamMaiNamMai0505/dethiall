@extends('layouts.lms-learner')

@section('title', 'Chỉnh sửa hoạt động ngoài HĐCM')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="lms-card rounded-2xl overflow-hidden">
        <div class="bg-gradient-to-r from-teal-600 to-teal-500 px-6 py-5 text-white flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-lg font-bold"><i class="bi bi-pencil-square mr-2"></i>Chỉnh sửa hoạt động ngoài HĐCM</h1>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('lms.teach.standard-hours.external-activities.show', $record) }}" class="px-4 py-2.5 rounded-xl border border-white/30 text-white hover:bg-white/10 text-sm font-medium transition-colors">
                    <i class="bi bi-eye"></i> Chi tiết
                </a>
                <a href="{{ route('lms.teach.standard-hours.external-activities.index') }}" class="px-4 py-2.5 rounded-xl border border-white/30 text-white hover:bg-white/10 text-sm font-medium transition-colors">
                    <i class="bi bi-arrow-left"></i> Quay lại
                </a>
            </div>
        </div>
    </div>

    <div class="lms-card rounded-2xl p-5">
        <form action="{{ route('lms.teach.standard-hours.external-activities.update', $record) }}" method="POST"
              enctype="multipart/form-data" data-turbo="false">
            @csrf @method('PUT')
            @include('lms::teach.standard-hours.external-activities._form')
            <div class="border-t border-slate-200 mt-6 pt-6 flex justify-end gap-3">
                <a href="{{ route('lms.teach.standard-hours.external-activities.show', $record) }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-medium transition-colors">Hủy</a>
                <button type="submit" class="px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium transition-colors">
                    <i class="bi bi-save"></i> Lưu thay đổi
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
