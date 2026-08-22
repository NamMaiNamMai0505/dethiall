@extends('layouts.admin')

@section('title', 'Export điểm nhiều khóa')
@section('page-title', 'Export điểm nhiều khóa LMS')

@section('content')
    <x-breadcrumb :items="[
        ['title' => 'Trang chủ'],
        ['title' => 'LMS', 'url' => route('lms.hub')],
        ['title' => 'Export điểm'],
    ]" />

    <div class="max-w-3xl mx-auto bg-white rounded-xl border shadow-sm p-6">
        <h1 class="text-xl font-bold text-slate-900 mb-1">Báo cáo điểm nhiều khóa</h1>
        <p class="text-sm text-slate-500 mb-5">
            Chọn một hoặc nhiều khóa → tải 1 file CSV (assignment/exam/chuyên cần/tiến độ/final).
        </p>

        <form method="POST" action="{{ route('lms.gradebook.export-multi.download') }}" data-turbo="false">
            @csrf
            <div class="mb-3 flex flex-wrap gap-2">
                <button type="button" id="sel-all" class="text-xs px-2 py-1 rounded border">Chọn tất cả</button>
                <button type="button" id="sel-none" class="text-xs px-2 py-1 rounded border">Bỏ chọn</button>
            </div>
            <div class="max-h-96 overflow-y-auto border rounded-lg divide-y">
                @forelse($courses as $c)
                    <label class="flex items-start gap-3 px-3 py-2.5 hover:bg-slate-50 cursor-pointer text-sm">
                        <input type="checkbox" name="course_ids[]" value="{{ $c->id }}" class="mt-1 course-cb"
                               @checked(collect(old('course_ids', []))->contains($c->id))>
                        <span>
                            <span class="font-semibold text-slate-900">{{ $c->title }}</span>
                            <span class="block text-xs text-slate-400">
                                #{{ $c->id }}
                                @if($c->code) · {{ $c->code }} @endif
                                · {{ $c->status }}
                            </span>
                        </span>
                    </label>
                @empty
                    <p class="p-6 text-center text-slate-500 text-sm">Chưa có khóa LMS trong phạm vi của bạn.</p>
                @endforelse
            </div>
            @error('course_ids')<p class="text-red-500 text-xs mt-2">{{ $message }}</p>@enderror

            <div class="flex justify-end gap-2 mt-5">
                <a href="{{ route('lms.courses.index') }}" class="px-4 py-2 border rounded-lg text-sm">Huỷ</a>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-semibold">
                    Tải CSV
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
document.getElementById('sel-all')?.addEventListener('click', function () {
    document.querySelectorAll('.course-cb').forEach(function (c) { c.checked = true; });
});
document.getElementById('sel-none')?.addEventListener('click', function () {
    document.querySelectorAll('.course-cb').forEach(function (c) { c.checked = false; });
});
</script>
@endpush
