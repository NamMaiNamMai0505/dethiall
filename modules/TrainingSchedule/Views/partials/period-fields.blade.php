{{--
  Phân quyền theo khung tiết:
  - PDOT: chỉnh Môn / Loại tiết / Địa điểm — xem (khoá) Bài học + GV
  - Khoa: chỉnh Bài học + GV — xem (khoá) Môn / Loại / Địa điểm (khung PDOT)
  - Super-admin: full
  - Khoa + môn khoa khác: khoá cả hàng
--}}
@php
    $selectedSubject = old("details.$period.subject_id", $detail->subject_id ?? '');
    $selectedLesson = old("details.$period.subject_lesson_id", $detail->subject_lesson_id ?? '');
    $selectedInstructor = old("details.$period.instructor_id", $detail->instructor_id ?? '');
    $selectedLessonType = old("details.$period.lesson_type", $detail->lesson_type ?? '');
    $selectedClassroom = old("details.$period.classroom_id", $detail->classroom_id ?? '');
    $selectedUnitLesson = old("details.$period.unit_lesson_id", $detail?->subjectLesson?->parent_id ?? '');
    $selectedStandardHoursCategory = old(
        "details.$period.standard_hours_conversion_category_id",
        $detail->standard_hours_conversion_category_id
            ?? collect($standardHoursScheduleCategories ?? [])->first()?->id
            ?? ''
    );

    $isSuper = ! empty($isFullScheduleAccess);
    $roleFaculty = ! empty($isFacultyManager) && ! $isSuper;
    $rolePdot = ! empty($canManageSkeleton) && empty($isFacultyManager) && ! $isSuper;
    // Super-admin / full: không khoá theo vai

    $facultySubjectIds = collect($facultySubjectIds ?? []);
    $subjectOwnedByFaculty = true;
    if ($roleFaculty && $facultySubjectIds->isNotEmpty() && $selectedSubject !== '' && $selectedSubject !== null) {
        $subjectOwnedByFaculty = $facultySubjectIds->contains((int) $selectedSubject);
    }

    // Tiết môn khoa khác: khoá hết
    $lockEntireRow = $roleFaculty && ! $subjectOwnedByFaculty && $selectedSubject !== '' && $selectedSubject !== null;

    // Khoa: luôn khoá khung skeleton (môn / loại / địa điểm) — kể cả khi trống (chờ PDOT)
    $lockSkeleton = $roleFaculty || $lockEntireRow;
    // PDOT: khoá phần phân công (bài / GV)
    $lockAssignment = $rolePdot || $lockEntireRow;

    $roClass = 'bg-slate-50 text-slate-600 cursor-not-allowed opacity-90';
    $okClass = 'bg-white focus:ring-blue-500 focus:border-blue-500';
@endphp
{{-- gap-y rộng hơn gap-x: mỗi ô có dòng chú thích bên dưới, hàng dưới phải đủ
     khoảng trống nếu không chú thích sẽ dính vào ô kế tiếp. --}}
<div class="flex-1 grid grid-cols-1 gap-x-3 gap-y-5 sm:grid-cols-2 lg:grid-cols-3"
     data-period-role="{{ $roleFaculty ? 'faculty' : ($rolePdot ? 'pdot' : 'full') }}"
     data-period="{{ $period }}">
    {{-- 1. Môn học (PDOT) --}}
    <div>
        <label class="block text-[11px] font-medium mb-0.5 {{ $lockSkeleton ? 'text-slate-400' : 'text-gray-500' }}">
            Môn học
            @if($rolePdot)<span class="text-blue-600 font-normal">(PDOT)</span>@endif
            @if($roleFaculty)<span class="text-slate-400 font-normal">(chỉ xem)</span>@endif
        </label>
        <select name="details[{{ $period }}][subject_id]"
                class="h-9 w-full min-w-0 text-sm border border-gray-300 rounded-md subject-select {{ $lockSkeleton ? $roClass : $okClass }}"
                data-period="{{ $period }}"
                @if($lockSkeleton) data-locked="1" disabled @endif>
            <option value="">-- Chọn môn --</option>
            @foreach($subjects as $sub)
                @php
                    $isRo = ! empty($sub->faculty_read_only);
                    $showOpt = ! $isRo || (string) $selectedSubject === (string) $sub->id;
                @endphp
                @if($showOpt)
                    <option value="{{ $sub->id }}" name="{{ $sub->name }}"
                        data-fully-scheduled="{{ ($sub->is_fully_scheduled ?? false) ? 'true' : 'false' }}"
                        data-faculty-owned="{{ ! empty($sub->faculty_owned) || ! $roleFaculty ? '1' : '0' }}"
                        {{ (string) $selectedSubject === (string) $sub->id ? 'selected' : '' }}>
                        {{ $sub->display_code ?? $sub->code }} — {{ $sub->name }}{{ ($sub->is_fully_scheduled ?? false) ? ' (đã xếp hết)' : '' }}{{ $isRo ? ' (khoa khác)' : '' }}
                    </option>
                @endif
            @endforeach
        </select>
        @if($lockSkeleton)
            <input type="hidden" name="details[{{ $period }}][subject_id]" value="{{ $selectedSubject }}">
        @endif
        @error("details.$period.subject_id")
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- 2. Loại tiết (PDOT) --}}
    <div>
        <label class="block text-[11px] font-medium mb-0.5 {{ $lockSkeleton ? 'text-slate-400' : 'text-gray-500' }}">
            Loại tiết
            @if($rolePdot)<span class="text-blue-600 font-normal">(PDOT)</span>@endif
            @if($roleFaculty)<span class="text-slate-400 font-normal">(chỉ xem)</span>@endif
        </label>
        <select name="details[{{ $period }}][lesson_type]"
                class="h-9 w-full min-w-0 text-sm border border-gray-300 rounded-md lesson-type-select {{ $lockSkeleton ? $roClass : $okClass }}"
                data-period="{{ $period }}"
                @if($lockSkeleton) data-locked="1" disabled @endif>
            <option value="">-- Loại buổi --</option>
            @foreach(['theory' => 'Lý thuyết', 'practice' => 'Thực hành', 'self_study' => 'Tự học', 'final_exam' => 'Thi/kiểm tra (GV khảo thí)'] as $key => $label)
                <option value="{{ $key }}" {{ (string) $selectedLessonType === (string) $key ? 'selected' : '' }}>
                    {{ $label }}
                </option>
            @endforeach
        </select>
        @if($lockSkeleton)
            <input type="hidden" name="details[{{ $period }}][lesson_type]" value="{{ $selectedLessonType }}">
        @endif
        @error("details.$period.lesson_type")
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- 3. Bài / Unit (Khoa) — hai select xếp chồng, cần tách rõ để không dính nhau --}}
    <div class="space-y-2.5">
        <label class="block text-[11px] font-medium mb-0.5 {{ $lockAssignment ? 'text-slate-400' : 'text-gray-500' }}">
            Bài học
            @if($roleFaculty && ! $lockEntireRow)<span class="text-teal-700 font-normal">(Khoa)</span>@endif
            @if($rolePdot)<span class="text-slate-400 font-normal">(chỉ xem — Khoa phân)</span>@endif
            @if($lockEntireRow)<span class="text-slate-400 font-normal">(khoa khác)</span>@endif
        </label>
        <select name="details[{{ $period }}][unit_lesson_id]"
                class="h-9 w-full min-w-0 text-sm border border-gray-300 rounded-md unit-lesson-select {{ $lockAssignment ? $roClass : $okClass }}"
                data-period="{{ $period }}"
                data-selected="{{ $selectedUnitLesson }}"
                @if($lockAssignment) disabled @endif>
            <option value="">-- Unit / Chương (nếu có) --</option>
        </select>
        <select name="details[{{ $period }}][subject_lesson_id]"
                class="h-9 w-full min-w-0 text-sm border border-gray-300 rounded-md subject-lesson-select {{ $lockAssignment ? $roClass : $okClass }}"
                data-period="{{ $period }}"
                data-selected="{{ $selectedLesson }}"
                @if($lockAssignment) disabled @endif>
            <option value="">-- Chọn bài --</option>
            @if($selectedLesson && $detail?->subjectLesson)
                <option value="{{ $selectedLesson }}" selected>
                    {{ $detail->subjectLesson->display_label }}
                </option>
            @elseif($selectedLesson)
                <option value="{{ $selectedLesson }}" selected data-pending="1">Đang tải...</option>
            @endif
        </select>
        @if($lockAssignment)
            <input type="hidden" name="details[{{ $period }}][subject_lesson_id]" value="{{ $selectedLesson }}">
        @endif
        @error("details.$period.subject_lesson_id")
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- 4. Hệ số giờ chuẩn (PDOT) --}}
    <div>
        <label class="block text-[11px] font-medium mb-0.5 {{ $lockSkeleton ? 'text-slate-400' : 'text-gray-500' }}">
            Quy đổi giờ chuẩn
            @if($rolePdot)<span class="text-blue-600 font-normal">(PDOT)</span>@endif
            @if($roleFaculty)<span class="text-slate-400 font-normal">(chỉ xem)</span>@endif
        </label>
        <select name="details[{{ $period }}][standard_hours_conversion_category_id]"
                class="h-9 w-full min-w-0 text-sm border border-gray-300 rounded-md {{ $lockSkeleton ? $roClass : $okClass }}"
                @if($lockSkeleton) data-locked="1" disabled @endif>
            <option value="">Mặc định 1,00 giờ</option>
            @foreach(collect($standardHoursScheduleCategories ?? []) as $category)
                <option value="{{ $category->id }}" {{ (string) $selectedStandardHoursCategory === (string) $category->id ? 'selected' : '' }}>
                    {{ $category->code }} — {{ $category->name }}
                    ({{ number_format((float) ($category->coefficient ?? $category->fixed_hours ?? 1), 2) }})
                </option>
            @endforeach
        </select>
        @if($lockSkeleton)
            <input type="hidden" name="details[{{ $period }}][standard_hours_conversion_category_id]" value="{{ $selectedStandardHoursCategory }}">
        @endif
        <p class="mt-2 text-[11px] leading-4 text-slate-500">Mỗi tiết được nhân hệ số đã chọn khi truy xuất.</p>
    </div>

    {{-- 5. Giảng viên (Khoa) --}}
    <div>
        <label class="block text-[11px] font-medium mb-0.5 {{ $lockAssignment ? 'text-slate-400' : 'text-gray-500' }}">
            Giảng viên
            @if($roleFaculty && ! $lockEntireRow)<span class="text-teal-700 font-normal">(Khoa)</span>@endif
            @if($rolePdot)<span class="text-slate-400 font-normal">(chỉ xem — Khoa phân)</span>@endif
            @if($lockEntireRow)<span class="text-slate-400 font-normal">(khoa khác)</span>@endif
        </label>
        <select name="details[{{ $period }}][instructor_id]"
                class="h-9 w-full min-w-0 text-sm border border-gray-300 rounded-md instructor-select {{ $lockAssignment ? $roClass : $okClass }}"
                data-period="{{ $period }}"
                @if($lockAssignment) disabled @endif>
            <option value="">-- Chọn GV --</option>
            @if($selectedInstructor)
                <option value="{{ $selectedInstructor }}" selected data-pending="1">Đang tải...</option>
            @endif
        </select>
        @if($lockAssignment)
            <input type="hidden" name="details[{{ $period }}][instructor_id]" value="{{ $selectedInstructor }}">
        @endif
        <p class="instructor-hint text-xs mt-2 leading-4 text-gray-500" data-period="{{ $period }}" style="display:none;"></p>
        @error("details.$period.instructor_id")
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>

    {{-- 6. Địa điểm (PDOT) --}}
    <div>
        <label class="block text-[11px] font-medium mb-0.5 {{ $lockSkeleton ? 'text-slate-400' : 'text-gray-500' }}">
            Địa điểm
            @if($rolePdot)<span class="text-blue-600 font-normal">(PDOT)</span>@endif
            @if($roleFaculty)<span class="text-slate-400 font-normal">(chỉ xem)</span>@endif
        </label>
        <select name="details[{{ $period }}][classroom_id]"
                class="h-9 w-full min-w-0 text-sm border border-gray-300 rounded-md classroom-select {{ $lockSkeleton ? $roClass : $okClass }}"
                data-period="{{ $period }}"
                @if($lockSkeleton) data-locked="1" disabled @endif>
            <option value="">-- Chọn phòng --</option>
            @foreach(\Modules\Classroom\Models\Classroom::where('status', 1)->get() as $room)
                <option value="{{ $room->id }}"
                    {{ (string) $selectedClassroom === (string) $room->id ? 'selected' : '' }}>
                    {{ $room->name }}
                </option>
            @endforeach
        </select>
        @if($lockSkeleton)
            <input type="hidden" name="details[{{ $period }}][classroom_id]" value="{{ $selectedClassroom }}">
        @endif
        @error("details.$period.classroom_id")
            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
        @enderror
    </div>
</div>
@if($roleFaculty && ($selectedSubject === '' || $selectedSubject === null))
    <p class="mt-1 text-[11px] text-slate-500">Chưa có khung môn từ Phòng Đào tạo — chờ PDOT xếp môn / loại / phòng trước.</p>
@endif
