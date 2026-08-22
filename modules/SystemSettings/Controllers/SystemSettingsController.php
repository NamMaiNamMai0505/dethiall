<?php

namespace Modules\SystemSettings\Controllers;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\SystemSetting;
use App\Support\SystemSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SystemSettingsController extends Controller
{
    private const PORTALS = ['dashboard', 'lms', 'grades'];

    public function index(Request $request): View
    {
        $portal = in_array($request->route('portal'), self::PORTALS, true)
            ? $request->route('portal')
            : 'dashboard';

        $academicYears = AcademicYear::query()->orderByDesc('start_year')->get();
        $settings = SystemSetting::query()
            ->whereIn('portal', ['shared', $portal])
            ->get()
            ->keyBy(fn (SystemSetting $setting) => $setting->portal.'.'.$setting->key);

        return view('system-settings::portals.'.$portal, [
            'portal' => $portal,
            'academicYears' => $academicYears,
            'settings' => $settings,
            'canEdit' => (bool) $request->user()?->isSuperAdmin(),
        ]);
    }

    public function storeAcademicYear(Request $request): RedirectResponse
    {
        $this->ensureCanEdit($request);

        $data = $request->validate([
            'start_year' => ['required', 'integer', 'min:2000', 'max:2200', 'unique:academic_years,start_year'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_current' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $start = (int) $data['start_year'];
        $isCurrent = $request->boolean('is_current');

        DB::transaction(function () use ($data, $start, $isCurrent, $request): void {
            if ($isCurrent) {
                AcademicYear::query()->update(['is_current' => false]);
            }

            AcademicYear::query()->create([
                'code' => $start.'-'.($start + 1),
                'start_year' => $start,
                'end_year' => $start + 1,
                'name' => 'Năm học '.$start.'-'.($start + 1),
                'starts_at' => $data['starts_at'] ?? $start.'-08-01',
                'ends_at' => $data['ends_at'] ?? ($start + 1).'-07-31',
                'is_current' => $isCurrent,
                'is_active' => $request->boolean('is_active', true),
                'sort_order' => $start,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);
        });

        return back()->with('success', 'Đã thêm năm học '.$start.'-'.($start + 1).' vào danh mục dùng chung.');
    }

    public function updateAcademicYear(Request $request, AcademicYear $academicYear): RedirectResponse
    {
        $this->ensureCanEdit($request);

        $data = $request->validate([
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $academicYear->update([
            ...$data,
            'is_active' => $request->boolean('is_active'),
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Đã cập nhật '.$academicYear->code.'.');
    }

    public function makeCurrent(Request $request, AcademicYear $academicYear): RedirectResponse
    {
        $this->ensureCanEdit($request);

        DB::transaction(function () use ($academicYear, $request): void {
            AcademicYear::query()->update(['is_current' => false]);
            $academicYear->update([
                'is_current' => true,
                'is_active' => true,
                'updated_by' => $request->user()->id,
            ]);
        });

        return back()->with('success', $academicYear->code.' đã là năm học hiện hành của toàn hệ thống.');
    }

    public function updateGeneral(Request $request, string $portal): RedirectResponse
    {
        $this->ensureCanEdit($request);
        abort_unless(in_array($portal, self::PORTALS, true), 404);

        if ($portal === 'dashboard') {
            $data = $request->validate([
                'parent_organization_name' => ['required', 'string', 'max:255'],
                'organization_name' => ['required', 'string', 'max:255'],
                'national_heading' => ['required', 'string', 'max:255'],
                'national_motto' => ['required', 'string', 'max:255'],
                'document_location' => ['required', 'string', 'max:255'],
                'default_export_format' => ['required', Rule::in(['word', 'excel'])],
                'default_page_size' => ['required', Rule::in(['A4', 'A3', 'Letter'])],
                'default_orientation' => ['required', Rule::in(['portrait', 'landscape'])],
            ]);
            $this->putSettings('shared', $data);
        } elseif ($portal === 'lms') {
            $gradeWeightKeys = [
                'grade_weight_assignments',
                'grade_weight_exams',
                'grade_weight_attendance',
                'grade_weight_progress',
            ];
            $updatesGradeWeights = collect($gradeWeightKeys)
                ->contains(fn (string $key): bool => $request->exists($key));
            $gradeWeightPresenceRule = $updatesGradeWeights ? 'required' : 'sometimes';

            $data = $request->validate([
                'default_course_status' => ['required', Rule::in(['draft', 'published'])],
                'default_assignment_max_score' => ['required', 'numeric', 'min:1', 'max:1000'],
                'submission_max_file_mb' => ['required', 'integer', 'min:1', 'max:500'],
                'allow_late_by_default' => ['required', 'boolean'],
                'default_exam_duration_minutes' => ['required', 'integer', 'min:5', 'max:480'],
                'default_exam_attempts' => ['required', 'integer', 'min:1', 'max:20'],
                'default_exam_pass_score' => ['required', 'numeric', 'min:0', 'max:1000'],
                'shuffle_questions_by_default' => ['required', 'boolean'],
                'notify_assignment_graded' => ['required', 'boolean'],
                'grade_weight_assignments' => [$gradeWeightPresenceRule, 'numeric', 'min:0', 'max:100'],
                'grade_weight_exams' => [$gradeWeightPresenceRule, 'numeric', 'min:0', 'max:100'],
                'grade_weight_attendance' => [$gradeWeightPresenceRule, 'numeric', 'min:0', 'max:100'],
                'grade_weight_progress' => [$gradeWeightPresenceRule, 'numeric', 'min:0', 'max:100'],
            ]);
            if ($updatesGradeWeights) {
                $lmsWeightTotal = collect($gradeWeightKeys)
                    ->sum(fn (string $key): float => (float) $data[$key]);
                if (abs($lmsWeightTotal - 100) > 0.001) {
                    throw ValidationException::withMessages([
                        'grade_weight_progress' => 'Tổng trọng số LMS phải bằng đúng 100%. Hiện tại: '.$lmsWeightTotal.'%.',
                    ]);
                }
            }
            $this->putSettings('lms', $data, [
                'default_assignment_max_score' => 'number',
                'submission_max_file_mb' => 'number',
                'allow_late_by_default' => 'boolean',
                'default_exam_duration_minutes' => 'number',
                'default_exam_attempts' => 'number',
                'default_exam_pass_score' => 'number',
                'shuffle_questions_by_default' => 'boolean',
                'notify_assignment_graded' => 'boolean',
                'grade_weight_assignments' => 'number',
                'grade_weight_exams' => 'number',
                'grade_weight_attendance' => 'number',
                'grade_weight_progress' => 'number',
            ]);
        } else {
            $data = $request->validate([
                'max_score' => ['required', 'numeric', 'min:1', 'max:100'],
                'pass_score' => ['required', 'numeric', 'min:0', 'lte:max_score'],
                'excellent_score' => ['required', 'numeric', 'gte:pass_score', 'lte:max_score'],
                'decimal_places' => ['required', 'integer', 'min:0', 'max:2'],
                'rounding_mode' => ['required', Rule::in(['half_up', 'half_down', 'half_even'])],
                'weight_oral_15' => ['required', 'numeric', 'min:0', 'max:100'],
                'weight_period_1' => ['required', 'numeric', 'min:0', 'max:100'],
                'weight_midterm' => ['required', 'numeric', 'min:0', 'max:100'],
                'weight_final' => ['required', 'numeric', 'min:0', 'max:100'],
            ]);

            $weightTotal = collect([
                $data['weight_oral_15'],
                $data['weight_period_1'],
                $data['weight_midterm'],
                $data['weight_final'],
            ])->sum(fn ($value) => (float) $value);
            if (abs($weightTotal - 100) > 0.001) {
                throw ValidationException::withMessages([
                    'weight_final' => 'Tổng bốn trọng số phải bằng đúng 100%. Hiện tại: '.$weightTotal.'%.',
                ]);
            }

            $this->putSettings('grades', $data, [
                'max_score' => 'number',
                'pass_score' => 'number',
                'excellent_score' => 'number',
                'decimal_places' => 'number',
                'weight_oral_15' => 'number',
                'weight_period_1' => 'number',
                'weight_midterm' => 'number',
                'weight_final' => 'number',
            ]);
        }

        return back()->with('success', 'Đã lưu cài đặt '.strtoupper($portal).'.');
    }

    private function ensureCanEdit(Request $request): void
    {
        abort_unless($request->user()?->isSuperAdmin(), 403, 'Chỉ quản trị viên được thay đổi cài đặt hệ thống.');
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  array<string, string>  $types
     */
    private function putSettings(string $portal, array $values, array $types = []): void
    {
        DB::transaction(function () use ($portal, $values, $types): void {
            foreach ($values as $key => $value) {
                $type = $types[$key] ?? 'string';
                $normalized = match ($type) {
                    'boolean' => (bool) $value,
                    'number' => is_int($value) ? $value : (float) $value,
                    default => (string) $value,
                };
                SystemSettings::put($portal, $key, $normalized, $type);
            }
        });
    }
}
