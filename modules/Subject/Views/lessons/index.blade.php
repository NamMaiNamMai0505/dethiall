@extends('layouts.admin')

@section('title', 'Bài học')
@section('page-title', 'Bài học / Khung chương trình môn')

@section('content')
    <x-breadcrumb :items="[
        ['title' => 'Trang chủ'],
        ['title' => 'Bài học']
    ]" />

    <x-page-header
        title="Bài học"
        :actions="array_filter([
            auth()->user()->can('subject-lessons.create') ? [
                'url' => route('subject-lessons.create', request()->only('subject_id')),
                'label' => 'Thêm bài học',
                'icon' => 'plus',
                'color' => 'green',
            ] : null,
            auth()->user()->can('subject-lessons.create') ? [
                'url' => route('subject-lessons.import'),
                'label' => 'Import bài học',
                'icon' => 'upload',
                'color' => 'blue',
            ] : null,
        ])" />

    @if(session('success'))
        <div class="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    {{-- Lọc liên động Hệ → Ngành → Môn: đổi cấp trên thì cấp dưới nạp lại ngay --}}
    <x-filter-form
        :action="route('subject-lessons.index')"
        :clear-url="route('subject-lessons.index')"
        :columns="4"
        :filters="[
            [
                'type' => 'select',
                'name' => 'training_system_id',
                'label' => 'Hệ đào tạo',
                'placeholder' => 'Tất cả hệ',
                'options' => $trainingSystems ?? [],
            ],
            [
                'type' => 'select',
                'name' => 'specialization_id',
                'label' => 'Ngành đào tạo',
                'placeholder' => 'Tất cả ngành',
                'depends_on' => 'training_system_id',
                'options' => $specializations->pluck('selection_label', 'id'),
            ],
            [
                'type' => 'select',
                'name' => 'subject_id',
                'label' => 'Môn học',
                'placeholder' => 'Tất cả môn',
                'waiting_placeholder' => 'Chọn ngành để lọc môn',
                'depends_on' => ['training_system_id', 'specialization_id'],
                'options' => $subjects->mapWithKeys(fn ($s) => [$s->id => $s->display_code . ' — ' . $s->name]),
            ],
            [
                'type' => 'search',
                'name' => 'q',
                'placeholder' => 'Tìm tên / mã bài học…',
            ],
        ]" />

    @if($selectedSubject)
        {{-- Môn đã chọn ở bộ lọc → nêu một lần ở đầu bảng, không lặp lại từng dòng --}}
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3">
            <div>
                <a href="{{ route('subject-lessons.index', request()->only(['training_system_id', 'specialization_id'])) }}"
                   class="inline-flex items-center gap-1 text-xs font-medium text-blue-700 hover:text-blue-900 hover:underline">
                    <i class="bi bi-arrow-left"></i> Quay lại danh sách môn
                </a>
                <div class="mt-2 text-xs font-semibold uppercase tracking-wide text-blue-700">
                    Môn đang chọn ·
                    {{ $selectedSubject->specialization?->trainingSystem?->name ?? 'Hệ chưa xác định' }}
                    <i class="bi bi-chevron-right mx-1 text-blue-400"></i>
                    {{ $selectedSubject->specialization?->name ?? 'Ngành chưa xác định' }}
                </div>
                <div class="mt-1 text-xl font-bold text-blue-950">{{ $selectedSubject->display_code }} — {{ $selectedSubject->name }}</div>
            </div>
            <div class="text-sm text-blue-900">
                <strong>{{ $lessons->total() }}</strong> bài học
            </div>
        </div>
    @elseif(! $showSubjectHub)
        <div class="mb-3 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            <i class="bi bi-info-circle mr-1"></i>
            Đang hiển thị kết quả tìm kiếm trên <strong>nhiều môn</strong>. Xóa từ khóa và chọn Ngành → Môn ở trên để xem đúng khung chương trình của một môn.
        </div>
    @endif

    @if($showSubjectHub)
        {{-- Hub các Môn: nhóm theo Ngành, bấm vào Môn mới hiện bài học của
             đúng Môn đó — tránh liệt kê phẳng bài học của mọi môn cùng lúc. --}}
        @forelse($subjects->groupBy('specialization_id') as $group)
            @php($firstSubject = $group->first())
            <div class="mb-5">
                <div class="mb-2 flex items-center gap-2 text-sm">
                    <span class="font-semibold text-slate-500">{{ $firstSubject->specialization?->trainingSystem?->name ?? 'Hệ chưa xác định' }}</span>
                    <i class="bi bi-chevron-right text-slate-300"></i>
                    <span class="font-bold text-slate-800">{{ $firstSubject->specialization?->name ?? 'Ngành chưa xác định' }}</span>
                </div>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach($group as $subject)
                        <a href="{{ route('subject-lessons.index', request()->only(['training_system_id', 'specialization_id']) + ['subject_id' => $subject->id]) }}"
                           class="group flex flex-col gap-2 rounded-xl border border-slate-200 bg-white p-4 shadow-sm transition hover:border-blue-300 hover:shadow-md">
                            <div class="flex items-start justify-between gap-2">
                                <span class="font-mono text-xs font-semibold text-slate-500">{{ $subject->display_code }}</span>
                                <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600 group-hover:bg-blue-100 group-hover:text-blue-700">
                                    <i class="bi bi-journal-text"></i> {{ $subject->lessons_count }}
                                </span>
                            </div>
                            <div class="font-semibold text-slate-800 group-hover:text-blue-700">{{ $subject->name }}</div>
                        </a>
                    @endforeach
                </div>
            </div>
        @empty
            <div class="rounded-xl border border-slate-200 bg-white px-4 py-10 text-center text-slate-500">
                Không có môn nào phù hợp bộ lọc hiện tại.
            </div>
        @endforelse
    @else

    @if($selectedSubject && auth()->user()->can('subject-lessons.create'))
        {{-- Form đặt ngoài <table>; các ô nhập trong hàng cuối trỏ về đây bằng
             thuộc tính form= — <form> lồng trong <tr> là HTML không hợp lệ. --}}
        <form method="POST" action="{{ route('subject-lessons.store') }}" id="quick-add-lesson" class="hidden">
            @csrf
            <input type="hidden" name="specialization_id" value="{{ $selectedSubject->specialization_id }}">
            <input type="hidden" name="subject_id" value="{{ $selectedSubject->id }}">
        </form>
    @endif

    @if(auth()->user()->can('subject-lessons.delete'))
        <div class="mb-3 flex items-center gap-3">
            <label class="inline-flex items-center gap-2 cursor-pointer select-none">
                <span class="relative inline-block h-6 w-11">
                    <input type="checkbox" id="bulk-select-toggle" class="peer sr-only">
                    <span class="absolute inset-0 rounded-full bg-gray-300 transition-colors peer-checked:bg-blue-600"></span>
                    <span class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white transition-transform peer-checked:translate-x-5"></span>
                </span>
                <span class="text-sm font-medium text-gray-700">Chọn nhiều để xóa</span>
            </label>
            <button type="button" id="bulk-delete-btn"
                    class="hidden items-center gap-2 rounded-lg bg-red-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700"
                    onclick="bulkDeleteLessons()">
                <i class="bi bi-trash"></i>
                <span>Xóa đã chọn (<span id="bulk-selected-count">0</span>)</span>
            </button>
        </div>
    @endif

    <div class="bg-white shadow rounded-lg border overflow-hidden">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-100 text-left text-xs uppercase text-slate-600">
                <tr>
                    <th class="bulk-col hidden px-3 py-2 w-8">
                        <input type="checkbox" id="bulk-select-all" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 focus:ring-2">
                    </th>
                    <th class="px-3 py-2">Mã bài</th>
                    @unless($selectedSubject)
                        <th class="px-3 py-2">Môn học</th>
                    @endunless
                    <th class="px-3 py-2">Nội dung</th>
                    <th class="px-3 py-2">Loại</th>
                    <th class="px-3 py-2">Giờ</th>
                    <th class="px-3 py-2">Bài con</th>
                    <th class="px-3 py-2">Thao tác</th>
                </tr>
            </thead>
            <tbody class="divide-y">
                @forelse($lessons as $lesson)
                    <tr class="hover:bg-slate-50">
                        <td class="bulk-col hidden px-3 py-2">
                            <input type="checkbox" class="bulk-select-item rounded border-gray-300 text-blue-600 focus:ring-blue-500 focus:ring-2" value="{{ $lesson->id }}">
                        </td>
                        <td class="px-3 py-2 font-mono text-xs">{{ $lesson->code }}</td>
                        @unless($selectedSubject)
                            <td class="px-3 py-2 text-xs text-slate-600">
                                {{ $lesson->subject?->display_code }} — {{ $lesson->subject?->name }}
                            </td>
                        @endunless
                        <td class="px-3 py-2">{{ $lesson->name }}</td>
                        <td class="px-3 py-2">
                            <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold
                                {{ $lesson->lesson_kind === 'unit' ? 'bg-indigo-100 text-indigo-800' : ($lesson->lesson_kind === 'chapter' ? 'bg-amber-100 text-amber-900' : 'bg-slate-100 text-slate-700') }}">
                                {{ $lesson->lesson_kind }}
                            </span>
                        </td>
                        <td class="px-3 py-2 text-xs text-slate-600">
                            T{{ $lesson->total_hours }} · LT{{ $lesson->theory_hours }} · TH{{ $lesson->practice_hours }} · Thi{{ $lesson->exam_hours }}
                        </td>
                        <td class="px-3 py-2 text-xs">
                            @if($lesson->children->count())
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach($lesson->children->take(8) as $child)
                                        <span class="inline-flex items-center rounded-full border border-slate-200 bg-slate-50 hover:bg-blue-50 hover:border-blue-300 transition-colors">
                                            @can('subject-lessons.edit')
                                                <a href="{{ route('subject-lessons.edit', $child) }}"
                                                   class="px-2.5 py-1 text-slate-700 hover:text-blue-700"
                                                   title="Bấm để xem/sửa bài con này">{{ $child->display_label }}</a>
                                            @else
                                                <span class="px-2.5 py-1 text-slate-700">{{ $child->display_label }}</span>
                                            @endcan
                                            @can('subject-lessons.delete')
                                                <form method="POST" action="{{ route('subject-lessons.destroy', $child) }}" class="inline" data-confirm="Bạn có chắc chắn muốn xóa bài con này?" data-confirm-danger="1" data-confirm-ok="Xóa">
                                                    @csrf @method('DELETE')
                                                    <button class="pr-2 text-red-500 hover:text-red-700" title="Xóa bài con"><i class="bi bi-x-lg"></i></button>
                                                </form>
                                            @endcan
                                        </span>
                                    @endforeach
                                    @if($lesson->children->count() > 8)
                                        <span class="px-2.5 py-1 text-slate-400">… +{{ $lesson->children->count() - 8 }}</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                            @can('subject-lessons.edit')
                                <a href="{{ route('subject-lessons.edit', $lesson) }}" class="text-blue-600 hover:text-blue-800" title="Sửa"><i class="bi bi-pencil-square"></i></a>
                            @endcan
                            @can('subject-lessons.delete')
                                <form method="POST" action="{{ route('subject-lessons.destroy', $lesson) }}" class="inline" data-confirm="Bạn có chắc chắn muốn xóa bài học này?" data-confirm-danger="1" data-confirm-ok="Xóa">
                                    @csrf @method('DELETE')
                                    <button class="ml-2 text-red-600 hover:text-red-800" title="Xóa"><i class="bi bi-trash3"></i></button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $selectedSubject ? 7 : 8 }}" class="px-4 py-10 text-center text-slate-500">
                            @if($selectedSubject)
                                Môn này chưa có bài học nào. Thêm ngay ở hàng bên dưới, hoặc
                                <a class="text-blue-600 underline" href="{{ route('subject-lessons.import') }}">import bài học</a>.
                            @else
                                Chưa có bài học. Hãy <a class="text-blue-600 underline" href="{{ route('subject-lessons.import') }}">import bài học</a>.
                            @endif
                        </td>
                    </tr>
                @endforelse

                {{-- Thêm bài trực tiếp trong bảng: sửa/xóa/bổ sung không phải rời trang --}}
                @if($selectedSubject && auth()->user()->can('subject-lessons.create'))
                    <tr class="bg-emerald-50/60">
                        <td class="bulk-col hidden px-3 py-2"></td>
                        <td class="px-3 py-2">
                        <input type="text" form="quick-add-lesson" name="code" required maxlength="100"
                        value="{{ old('code') }}"
                        class="w-24 rounded-lg border border-slate-300 px-2 py-1.5 text-xs font-mono focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30"
                        placeholder="Mã bài">
                        </td>
                        <td class="px-3 py-2">
                        <input type="text" form="quick-add-lesson" name="name" required maxlength="255"
                        value="{{ old('name') }}"
                        class="w-full rounded-lg border border-slate-300 px-2 py-1.5 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/30"
                        placeholder="Tên bài học">
                        </td>
                        <td class="px-3 py-2">
                        <select form="quick-add-lesson" name="lesson_kind" data-searchable="0"
                        class="w-28 rounded-lg border border-slate-300 px-2 py-1.5 text-xs">
                        @foreach(['lesson' => 'Bài', 'unit' => 'Unit', 'chapter' => 'Chương', 'sub' => 'Bài con', 'exam' => 'Thi'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('lesson_kind') === $value)>{{ $label }}</option>
                        @endforeach
                        </select>
                        </td>
                        <td class="px-3 py-2">
                        <div class="flex gap-1">
                        @foreach(['theory_hours' => 'LT', 'practice_hours' => 'TH', 'exam_hours' => 'Thi'] as $field => $label)
                        <input type="number" form="quick-add-lesson" name="{{ $field }}" min="0" value="{{ old($field, 0) }}"
                        title="{{ $label }}"
                        class="w-14 rounded-lg border border-slate-300 px-1.5 py-1.5 text-xs"
                        placeholder="{{ $label }}">
                        @endforeach
                        </div>
                        </td>
                        <td class="px-3 py-2">
                        <input type="number" form="quick-add-lesson" name="sort_order" min="0" value="{{ old('sort_order', 0) }}"
                        title="Thứ tự"
                        class="w-16 rounded-lg border border-slate-300 px-1.5 py-1.5 text-xs"
                        placeholder="STT">
                        </td>
                        <td class="px-3 py-2 whitespace-nowrap">
                        <button type="submit" form="quick-add-lesson"
                        class="inline-flex items-center gap-1 rounded-lg bg-emerald-600 px-3 py-1.5 text-xs font-medium text-white hover:bg-emerald-700">
                        <i class="bi bi-plus-lg"></i> <span>Thêm bài</span>
                        </button>
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
        <div class="p-3 border-t">{{ $lessons->links() }}</div>
    </div>

    @endif

@push('scripts')
<script>
(function () {
    if (window.__lessonBulkSelectBound) return;
    window.__lessonBulkSelectBound = true;

    function boot() {
        const toggle = document.getElementById('bulk-select-toggle');
        const selectAll = document.getElementById('bulk-select-all');
        const deleteBtn = document.getElementById('bulk-delete-btn');
        const countEl = document.getElementById('bulk-selected-count');
        if (!toggle) return;

        function items() {
            return document.querySelectorAll('.bulk-select-item');
        }

        function updateBar() {
            const checked = document.querySelectorAll('.bulk-select-item:checked').length;
            if (countEl) countEl.textContent = String(checked);
            deleteBtn?.classList.toggle('hidden', checked === 0);
            deleteBtn?.classList.toggle('flex', checked > 0);
        }

        toggle.addEventListener('change', function () {
            document.querySelectorAll('.bulk-col').forEach(function (el) {
                el.classList.toggle('hidden', !toggle.checked);
            });
            if (!toggle.checked) {
                items().forEach(function (cb) { cb.checked = false; });
                if (selectAll) selectAll.checked = false;
                updateBar();
            }
        });

        selectAll?.addEventListener('change', function () {
            items().forEach(function (cb) { cb.checked = selectAll.checked; });
            updateBar();
        });

        document.addEventListener('change', function (e) {
            if (e.target.classList.contains('bulk-select-item')) updateBar();
        });

        window.bulkDeleteLessons = function () {
            const ids = Array.from(document.querySelectorAll('.bulk-select-item:checked')).map(function (cb) { return cb.value; });
            if (!ids.length) return;
            if (!confirm('Xóa ' + ids.length + ' bài học đã chọn? Bài con của các bài này (nếu có) cũng sẽ bị xóa theo.')) return;

            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route('subject-lessons.bulk-delete') }}';
            form.innerHTML = '@csrf';
            ids.forEach(function (id) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ids[]';
                input.value = id;
                form.appendChild(input);
            });
            document.body.appendChild(form);
            form.submit();
        };
    }

    document.addEventListener('DOMContentLoaded', boot);
    if (document.readyState !== 'loading') boot();
    document.addEventListener('turbo:load', boot);
})();
</script>
@endpush
@endsection
