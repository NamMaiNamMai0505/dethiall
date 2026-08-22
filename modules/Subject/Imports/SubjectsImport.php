<?php

namespace Modules\Subject\Imports;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Modules\Subject\Models\Subject;
use Modules\Subject\Support\SubjectCodePrefix;
use Modules\Unit\Models\Unit;

class SubjectsImport implements SkipsEmptyRows, SkipsOnFailure, ToModel, WithHeadingRow, WithValidation
{
    use SkipsFailures;

    protected int $specializationId;

    protected string $codePrefix;

    protected int $importedCount = 0;

    protected int $updatedCount = 0;

    protected int $skippedCount = 0;

    protected ?array $validFacultyCodes = null;

    public function __construct(int $specializationId, string $codePrefix)
    {
        $this->specializationId = $specializationId;
        $this->codePrefix = SubjectCodePrefix::normalizePrefix($codePrefix);
    }

    public function model(array $row): ?Subject
    {
        $name = $this->value($row, ['ten_mon_hoc', 'ten mon hoc', 'name']);
        $rawCode = $this->value($row, ['ma_mon_hoc', 'ma mon hoc', 'code']);

        if (blank($name) && blank($rawCode)) {
            $this->skippedCount++;

            return null;
        }

        if (blank($name)) {
            $this->skippedCount++;

            return null;
        }

        $localCode = filled($rawCode)
            ? trim((string) $rawCode)
            : $this->generateLocalCode((string) $name);

        $code = SubjectCodePrefix::buildSubjectCode($this->codePrefix, $localCode);

        $abbreviation = $this->nullableString($this->value($row, [
            'viet_tat', 'viet tat', 'abbreviation', 'viet_tat_mon',
        ]));
        if ($abbreviation === null || $abbreviation === '') {
            $abbreviation = Subject::generateAbbreviationFromName((string) $name);
        }
        $abbreviation = $abbreviation !== '' ? mb_strtoupper($abbreviation, 'UTF-8') : null;

        $colorRaw = $this->nullableString($this->value($row, [
            'mau', 'mau_nhan_dien', 'color', 'mau mon',
        ]));
        $color = Subject::normalizeColor($colorRaw)
            ?: Subject::defaultColorForKey(trim((string) $name));

        // Mã khoa đọc trực tiếp từ cột trong file - không còn suy ra từ hậu
        // tố mã môn. Chỉ nhận nếu khớp đúng mã Khoa chuyên môn đang hoạt
        // động; sai/để trống thì để null (accessor tự fallback hậu tố mã
        // môn cho dữ liệu cũ chưa gán).
        $facultyCode = $this->resolveFacultyCode($this->value($row, [
            'ma_khoa', 'ma khoa', 'khoa', 'faculty_code',
        ]));

        $payload = [
            'name' => trim((string) $name),
            'abbreviation' => $abbreviation,
            'color' => $color,
            'description' => $this->nullableString($this->value($row, ['mo_ta', 'mo ta', 'description'])),
            'specialization_id' => $this->specializationId,
            'credits' => (int) ($this->value($row, ['so_tin_chi', 'so tin chi', 'credits'], 1) ?: 1),
            'theory_hours' => (int) ($this->value($row, [
                'gio_ly_thuyet', 'so_tiet_ly_thuyet', 'so tiet ly thuyet', 'theory_hours',
            ], 0) ?: 0),
            'practice_hours' => (int) ($this->value($row, [
                'gio_thuc_hanh', 'so_tiet_thuc_hanh', 'so tiet thuc hanh', 'practice_hours',
            ], 0) ?: 0),
            'self_study_hours' => (int) ($this->value($row, [
                'gio_tu_hoc', 'so_tiet_tu_hoc', 'so tiet tu hoc', 'self_study_hours',
            ], 0) ?: 0),
            'exam_hours' => (int) ($this->value($row, [
                'gio_thi', 'so_tiet_thi', 'so tiet thi', 'exam_hours',
            ], 0) ?: 0),
            'level' => $this->mapLevel($this->value($row, ['cap_do', 'cap do', 'level'], 'basic')),
            'semester' => $this->mapSemester($this->value($row, ['hoc_ky', 'hoc ky', 'semester'])),
            'assessment_method' => $this->mapAssessmentMethod(
                $this->value($row, ['phuong_phap_danh_gia', 'phuong phap danh gia', 'assessment_method'], 'exam')
            ),
            'updated_by' => Auth::id(),
        ];

        // Chỉ ghi đè faculty_code khi file có gán rõ ràng - tránh xoá mất
        // gán khoa đã có (đặt tay hoặc từ lần import trước) khi cột này
        // để trống ở một lần import lại.
        if ($facultyCode !== null) {
            $payload['faculty_code'] = $facultyCode;
        }

        $existingSubject = Subject::where('code', $code)->first();

        if ($existingSubject) {
            $existingSubject->update($payload);
            $this->updatedCount++;

            return null;
        }

        $this->importedCount++;

        return new Subject(array_merge($payload, [
            'code' => $code,
            'is_required' => true,
            'is_active' => true,
            'created_by' => Auth::id(),
        ]));
    }

    public function rules(): array
    {
        return [
            '*.ten_mon_hoc' => 'nullable',
            'ten_mon_hoc' => 'nullable|string|max:255',
            'so_tin_chi' => 'nullable|numeric|min:0|max:30',
            'gio_ly_thuyet' => 'nullable|numeric|min:0',
            'so_tiet_ly_thuyet' => 'nullable|numeric|min:0',
            'gio_thuc_hanh' => 'nullable|numeric|min:0',
            'so_tiet_thuc_hanh' => 'nullable|numeric|min:0',
            'gio_tu_hoc' => 'nullable|numeric|min:0',
            'so_tiet_tu_hoc' => 'nullable|numeric|min:0',
            'gio_thi' => 'nullable|numeric|min:0',
            'so_tiet_thi' => 'nullable|numeric|min:0',
        ];
    }

    public function getImportedCount(): int
    {
        return $this->importedCount;
    }

    public function getUpdatedCount(): int
    {
        return $this->updatedCount;
    }

    public function getSkippedCount(): int
    {
        return $this->skippedCount;
    }

    public function getCodePrefix(): string
    {
        return $this->codePrefix;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $keys
     */
    protected function value(array $row, array $keys, mixed $default = null): mixed
    {
        $normalized = [];
        foreach ($row as $key => $val) {
            $normalized[$this->normalizeKey((string) $key)] = $val;
        }

        foreach ($keys as $key) {
            $nk = $this->normalizeKey($key);
            if (array_key_exists($nk, $normalized) && $normalized[$nk] !== null && $normalized[$nk] !== '') {
                return $normalized[$nk];
            }
        }

        return $default;
    }

    protected function normalizeKey(string $key): string
    {
        $key = Str::ascii(mb_strtolower(trim($key)));
        $key = str_replace(['đ'], ['d'], $key);
        $key = preg_replace('/[^a-z0-9]+/', '_', $key) ?? $key;

        return trim($key, '_');
    }

    protected function resolveFacultyCode(mixed $rawCode): ?string
    {
        $code = $this->nullableString($rawCode);
        if ($code === null) {
            return null;
        }

        $code = mb_strtoupper($code, 'UTF-8');

        if ($this->validFacultyCodes === null) {
            $this->validFacultyCodes = Unit::query()
                ->where('functional_type', Unit::FUNCTIONAL_FACULTY)
                ->pluck('faculty_code')
                ->filter()
                ->map(fn ($c) => mb_strtoupper($c, 'UTF-8'))
                ->all();
        }

        return in_array($code, $this->validFacultyCodes, true) ? $code : null;
    }

    protected function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return trim((string) $value);
    }

    protected function mapLevel(mixed $level): string
    {
        $level = mb_strtolower(trim((string) $level));

        $levelMap = [
            'cơ bản' => 'basic',
            'co ban' => 'basic',
            'basic' => 'basic',
            'trung cấp' => 'intermediate',
            'trung cap' => 'intermediate',
            'intermediate' => 'intermediate',
            'nâng cao' => 'advanced',
            'nang cao' => 'advanced',
            'advanced' => 'advanced',
        ];

        return $levelMap[$level] ?? 'basic';
    }

    protected function mapSemester(mixed $semester): ?string
    {
        if ($semester === null || $semester === '') {
            return null;
        }

        $semester = mb_strtolower(trim((string) $semester));

        $semesterMap = [
            'học kỳ 1' => 'semester_1',
            'hoc ky 1' => 'semester_1',
            'semester 1' => 'semester_1',
            'semester_1' => 'semester_1',
            '1' => 'semester_1',
            'học kỳ 2' => 'semester_2',
            'hoc ky 2' => 'semester_2',
            'semester 2' => 'semester_2',
            'semester_2' => 'semester_2',
            '2' => 'semester_2',
            'học kỳ 3' => 'semester_3',
            'hoc ky 3' => 'semester_3',
            'semester 3' => 'semester_3',
            'semester_3' => 'semester_3',
            '3' => 'semester_3',
            'học kỳ 4' => 'semester_4',
            'hoc ky 4' => 'semester_4',
            'semester 4' => 'semester_4',
            'semester_4' => 'semester_4',
            '4' => 'semester_4',
            'học kỳ 5' => 'semester_5',
            'hoc ky 5' => 'semester_5',
            'semester 5' => 'semester_5',
            'semester_5' => 'semester_5',
            '5' => 'semester_5',
            'học kỳ 6' => 'semester_6',
            'hoc ky 6' => 'semester_6',
            'semester 6' => 'semester_6',
            'semester_6' => 'semester_6',
            '6' => 'semester_6',
            'học kỳ hè' => 'summer',
            'hoc ky he' => 'summer',
            'summer' => 'summer',
            'hè' => 'summer',
            'he' => 'summer',
        ];

        return $semesterMap[$semester] ?? null;
    }

    protected function mapAssessmentMethod(mixed $method): string
    {
        $method = mb_strtolower(trim((string) $method));

        $methodMap = [
            'thi viết' => 'exam',
            'thi viet' => 'exam',
            'exam' => 'exam',
            'thi trắc nghiệm' => 'multiple_choice_questions',
            'thi trac nghiem' => 'multiple_choice_questions',
            'trắc nghiệm' => 'multiple_choice_questions',
            'trac nghiem' => 'multiple_choice_questions',
            'multiple choice' => 'multiple_choice_questions',
            'multiple_choice_questions' => 'multiple_choice_questions',
            'bài tập' => 'assignment',
            'bai tap' => 'assignment',
            'assignment' => 'assignment',
            'đồ án' => 'project',
            'do an' => 'project',
            'project' => 'project',
            'tổng hợp' => 'combined',
            'tong hop' => 'combined',
            'combined' => 'combined',
        ];

        return $methodMap[$method] ?? 'exam';
    }

    protected function generateLocalCode(string $name): string
    {
        $baseCode = Str::upper(Str::slug($name, ''));
        $baseCode = substr($baseCode !== '' ? $baseCode : 'MH', 0, 10);

        $code = $baseCode;
        $counter = 1;
        $full = SubjectCodePrefix::buildSubjectCode($this->codePrefix, $code);

        while (Subject::where('code', $full)->exists()) {
            $code = $baseCode.$counter;
            $full = SubjectCodePrefix::buildSubjectCode($this->codePrefix, $code);
            $counter++;
        }

        return $code;
    }
}
