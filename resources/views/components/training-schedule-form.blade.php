@props([
    'action' => '',
    'method' => 'POST',
    'schedule' => null,
    'specializations' => [],
    'instructors' => []
])

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if(strtoupper($method) !== 'POST')
        @method($method)
    @endif

    {{-- Basic Information --}}
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <h3 class="text-lg font-medium text-gray-900 mb-4">Thông tin cơ bản</h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Name --}}
            <x-form.input
                name="name"
                label="Tên lịch đào tạo"
                :value="old('name', $schedule->name ?? '')"
                required />

            {{-- Code --}}
            <x-form.input
                name="code"
                label="Mã lịch"
                :value="old('code', $schedule->code ?? '')"
                placeholder="Để trống để tự động tạo" />

            {{-- Specialization --}}
            <x-form.select
                name="specialization_id"
                label="Ngành đào tạo"
                :options="$specializations->pluck('name', 'id')"
                :value="old('specialization_id', $schedule->specialization_id ?? '')"
                placeholder="Chọn ngành đào tạo"
                required />

            {{-- Class ID --}}
            <x-form.input
                name="class_id"
                label="Lớp học"
                :value="old('class_id', $schedule->class_id ?? '')"
                placeholder="VD: CNTT01, QTKD02" />

            {{-- Academic Year --}}
            <x-academic-year-select
                name="academic_year"
                label="Năm học"
                :value="old('academic_year', $schedule->academic_year ?? '')"
                required />

            {{-- Semester --}}
            <x-form.select
                name="semester"
                label="Học kỳ"
                :options="[
                    'semester_1' => 'Học kỳ 1',
                    'semester_2' => 'Học kỳ 2',
                    'summer' => 'Học kỳ hè'
                ]"
                :value="old('semester', $schedule->semester ?? '')"
                placeholder="Chọn học kỳ"
                required />

            {{-- Start Date --}}
            <x-form.input
                name="start_date"
                type="date"
                label="Ngày bắt đầu"
                :value="old('start_date', $schedule->start_date?->format('Y-m-d') ?? '')"
                required />

            {{-- End Date --}}
            <x-form.input
                name="end_date"
                type="date"
                label="Ngày kết thúc"
                :value="old('end_date', $schedule->end_date?->format('Y-m-d') ?? '')"
                required />
        </div>

        {{-- Active Status --}}
        <div class="mt-6">
            <x-form.checkbox
                name="is_active"
                label="Kích hoạt lịch đào tạo"
                :checked="old('is_active', $schedule->is_active ?? true)"
                value="1" />
        </div>
    </div>

    {{-- Weekly Schedule Section --}}
    <div class="bg-white rounded-lg shadow-sm border p-6">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-medium text-gray-900">Lịch theo tuần</h3>
            <button type="button" onclick="addWeek()"
                    class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-sm">
                <i class="bi bi-plus"></i> Thêm tuần
            </button>
        </div>

        <div id="weeks-container">
            {{-- Weeks will be added dynamically --}}
        </div>
    </div>

    {{-- Submit Buttons --}}
    <div class="flex justify-end space-x-3">
        <a href="{{ route('training-schedules.index') }}"
           class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">
            Hủy
        </a>
        <button type="submit"
                class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg">
            {{ $schedule ? 'Cập nhật' : 'Tạo mới' }}
        </button>
    </div>
</form>

@push('scripts')
<script>
let weekCount = 0;

function addWeek() {
    weekCount++;
    const container = document.getElementById('weeks-container');
    const weekHtml = `
        <div class="border rounded-lg p-4 mb-4 week-item" data-week="${weekCount}">
            <div class="flex justify-between items-center mb-3">
                <h4 class="font-medium">Tuần ${weekCount}</h4>
                <button type="button" onclick="removeWeek(this)"
                        class="text-red-500 hover:text-red-700">
                    <i class="bi bi-trash"></i>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <input type="date" name="weeks[${weekCount}][start_date]"
                       placeholder="Ngày bắt đầu"
                       class="border border-gray-300 rounded px-3 py-2">
                <input type="date" name="weeks[${weekCount}][end_date]"
                       placeholder="Ngày kết thúc"
                       class="border border-gray-300 rounded px-3 py-2">
                <input type="text" name="weeks[${weekCount}][content]"
                       placeholder="Nội dung tuần"
                       class="border border-gray-300 rounded px-3 py-2">
            </div>
        </div>
    `;
    container.insertAdjacentHTML('beforeend', weekHtml);
}

function removeWeek(button) {
    button.closest('.week-item').remove();
}

// Load existing weeks for edit form
document.addEventListener('DOMContentLoaded', function() {
    @if($schedule && $schedule->weekly_schedule)
        const existingWeeks = @json($schedule->weekly_schedule ?? []);
        Object.entries(existingWeeks).forEach(([weekKey, weekData]) => {
            addWeek();
            const weekItem = document.querySelector(`[data-week="${weekCount}"]`);
            if (weekItem) {
                weekItem.querySelector('input[name$="[start_date]"]').value = weekData.start_date || '';
                weekItem.querySelector('input[name$="[end_date]"]').value = weekData.end_date || '';
                weekItem.querySelector('input[name$="[content]"]').value = weekData.content || '';
            }
        });
    @endif
});
</script>
@endpush
