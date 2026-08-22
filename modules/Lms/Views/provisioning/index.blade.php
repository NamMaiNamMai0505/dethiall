@extends('layouts.admin')

@section('title', 'Đồng bộ lịch vào LMS')
@section('page-title', 'Đồng bộ lịch vào LMS')

@section('content')
    <x-breadcrumb :items="[
        ['title' => 'Trang chủ'],
        ['title' => 'LMS', 'url' => route('lms.hub')],
        ['title' => 'Đồng bộ từ lịch'],
    ]" />

    <div class="mb-6 overflow-hidden rounded-2xl border border-blue-200 bg-gradient-to-br from-blue-700 via-indigo-700 to-slate-900 p-6 text-white shadow-lg">
        <div class="flex flex-col justify-between gap-5 lg:flex-row lg:items-end">
            <div class="max-w-3xl">
                <span class="inline-flex rounded-full bg-white/15 px-3 py-1 text-xs font-semibold tracking-wide">LỊCH ĐÀO TẠO → LỚP HỌC PHẦN</span>
                <h1 class="mt-3 text-2xl font-bold">Bổ sung lịch thực tế vào các môn đã ghi danh theo ngành</h1>
                <p class="mt-2 text-sm leading-6 text-blue-100">Lớp học phần đã được tạo tự động từ Ngành × Lớp. Màn hình này chỉ đồng bộ giảng viên thực dạy, bài học và buổi điểm danh từ lịch đào tạo; không tạo lại môn.</p>
            </div>
            <a href="{{ route('lms.courses.index') }}" class="inline-flex items-center justify-center rounded-xl border border-white/30 bg-white/10 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/20">
                <i class="bi bi-collection-play mr-2"></i>Xem khóa học
            </a>
        </div>
    </div>

    <form method="GET" action="{{ route('lms.provisioning.index') }}" class="mb-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-4">
        <input name="q" data-live-search="1" value="{{ request('q') }}" placeholder="Tên lịch, mã lịch, mã lớp..." class="rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500 md:col-span-2">
        <select name="academic_year" class="rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">Tất cả năm học</option>
            @foreach($academicYears as $year)
                <option value="{{ $year }}" @selected(request('academic_year') === $year)>{{ $year }}</option>
            @endforeach
        </select>
        <div class="flex gap-2">
            <select name="status" class="min-w-0 flex-1 rounded-xl border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                <option value="active" @selected(request('status', 'active') === 'active')>Đang hoạt động</option>
                <option value="all" @selected(request('status') === 'all')>Tất cả</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Ngừng hoạt động</option>
            </select>
            <button class="rounded-xl bg-slate-900 px-4 text-white transition hover:bg-blue-700" title="Lọc"><i class="bi bi-funnel"></i></button>
        </div>
    </form>

    <form method="POST" action="{{ route('lms.provisioning.store') }}" data-turbo="false">
        @csrf
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                <input type="checkbox" name="sync_content" value="1" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                Đồng bộ luôn bài học và buổi điểm danh
            </label>
            <button type="submit" class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-bold text-white shadow-md shadow-blue-200 transition hover:-translate-y-0.5 hover:bg-blue-700 hover:shadow-lg">
                <i class="bi bi-arrow-repeat mr-2"></i>Đồng bộ lịch đã chọn
            </button>
        </div>

        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-slate-100 text-left text-xs uppercase tracking-wide text-slate-600">
                        <tr>
                            <th class="w-12 px-4 py-3"><input type="checkbox" data-check-all class="rounded border-slate-300 text-blue-600"></th>
                            <th class="px-4 py-3">Lịch đào tạo</th>
                            <th class="px-4 py-3">Lớp</th>
                            <th class="px-4 py-3">Năm / học kỳ</th>
                            <th class="px-4 py-3 text-center">Môn / tiết</th>
                            <th class="px-4 py-3">Thời gian</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($schedules as $schedule)
                            @php($subjectCount = $schedule->scheduleDetails->pluck('subject_id')->filter()->unique()->count())
                            <tr class="transition hover:bg-blue-50/50">
                                <td class="px-4 py-4"><input type="checkbox" name="schedule_ids[]" value="{{ $schedule->id }}" class="schedule-check rounded border-slate-300 text-blue-600"></td>
                                <td class="px-4 py-4">
                                    <div class="font-semibold text-slate-900">{{ $schedule->name }}</div>
                                    <code class="text-xs text-slate-500">{{ $schedule->code ?: '#'.$schedule->id }}</code>
                                </td>
                                <td class="px-4 py-4">{{ $schedule->class?->name ?: $schedule->class_code ?: '—' }}</td>
                                <td class="px-4 py-4"><div>{{ $schedule->academic_year ?: '—' }}</div><div class="text-xs text-slate-500">{{ $schedule->semester_text }}</div></td>
                                <td class="px-4 py-4 text-center"><strong class="text-blue-700">{{ $subjectCount }}</strong> / {{ $schedule->scheduleDetails->count() }}</td>
                                <td class="whitespace-nowrap px-4 py-4 text-xs text-slate-600">{{ $schedule->start_date?->format('d/m/Y') }} – {{ $schedule->end_date?->format('d/m/Y') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-12 text-center text-slate-500">Không có lịch đào tạo phù hợp trong phạm vi của bạn.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 p-4">{{ $schedules->links() }}</div>
        </div>
    </form>

    @push('scripts')
        <script>
            (() => {
                const bind = () => {
                    const all = document.querySelector('[data-check-all]');
                    if (!all || all.dataset.bound === '1') return;
                    all.dataset.bound = '1';
                    all.addEventListener('change', () => document.querySelectorAll('.schedule-check').forEach(item => item.checked = all.checked));
                };
                document.addEventListener('turbo:load', bind);
                document.addEventListener('DOMContentLoaded', bind);
                bind();
            })();
        </script>
    @endpush
@include('partials.live-search')
@endsection
