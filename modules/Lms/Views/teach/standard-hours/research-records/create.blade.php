@extends('layouts.lms-learner')

@section('title', 'Thêm kê khai NCKH')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="lms-card rounded-2xl overflow-hidden">
        <div class="bg-gradient-to-r from-teal-600 to-teal-500 px-6 py-5 text-white flex flex-wrap items-center justify-between gap-3">
            <h1 class="text-lg font-bold"><i class="bi bi-plus-circle mr-2"></i>Thêm kê khai NCKH</h1>
            <a href="{{ route('lms.teach.standard-hours.research-records.index') }}" class="px-4 py-2.5 rounded-xl border border-white/30 text-white hover:bg-white/10 text-sm font-medium transition-colors">
                <i class="bi bi-arrow-left"></i> Quay lại
            </a>
        </div>
    </div>

    <div class="lms-card rounded-2xl p-5">
        <form action="{{ route('lms.teach.standard-hours.research-records.store') }}" method="POST"
              enctype="multipart/form-data" data-turbo="false">
            @csrf
            @include('lms::teach.standard-hours.research-records._form')
            <div class="border-t border-slate-200 mt-6 pt-6 flex justify-end gap-3">
                <button type="submit" name="status" value="draft" class="px-4 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-sm font-medium transition-colors">Lưu nháp</button>
                <button type="submit" name="status" value="submitted" class="px-4 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-medium transition-colors">Lưu & Gửi duyệt</button>
            </div>
        </form>
    </div>
</div>
@endsection
