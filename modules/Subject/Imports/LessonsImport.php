<?php

namespace Modules\Subject\Imports;

use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Modules\Subject\Models\Subject;
use Modules\Subject\Models\SubjectLesson;

/**
 * Import bài học (đơn giản, không phụ thuộc khung CTĐT nguyên khối). Phải
 * chọn sẵn Ngành → Môn trên form trước khi import ($forcedSubject) - toàn
 * bộ file được ghi vào đúng môn đó. Cột "Mã môn học" trong file (nếu có)
 * chỉ dùng để ĐỐI CHIẾU: dòng nào ghi mã khác môn đã chọn sẽ báo lỗi thay
 * vì âm thầm ghi nhầm sang môn khác - để trống cột này cũng được.
 *
 * Phân cấp cha-con:
 * - Điền cột "Mã bài cha" (khớp "Mã bài học" của một dòng khác CÙNG môn
 *   trong cùng file) để chỉ định tường minh - luôn được ưu tiên.
 * - Bỏ trống thì hệ thống TỰ suy luận theo thứ tự dòng trong file + cột
 *   "Loại bài": Unit chứa Chương; Chương (hoặc Unit nếu dòng đó chưa có
 *   Chương nào) chứa Bài/Bài con/Thi. File phải xếp đúng thứ tự phân cấp
 *   (Unit/Chương đứng trước các bài con của nó) để suy luận đúng.
 */
class LessonsImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    protected int $importedCount = 0;

    protected int $updatedCount = 0;

    protected int $skippedCount = 0;

    /** @var list<string> */
    protected array $errors = [];

    public function __construct(protected Subject $forcedSubject) {}

    public function collection(BaseCollection $rows): void
    {
        // Pass 1: tạo/cập nhật từng bài (chưa gán parent_id) - cần ID trước
        // khi có thể phân giải "Mã bài cha" ở pass 2.
        // Tự nhận diện cha-con theo THỨ TỰ DÒNG + Loại bài khi "Mã bài cha"
        // bỏ trống: Unit chứa Chương; Chương (hoặc Unit nếu dòng đó chưa có
        // Chương nào) chứa Bài/Bài con/Thi. "Mã bài cha" điền tay luôn được
        // ưu tiên hơn suy luận này.
        $currentUnit = null;
        $currentChapter = null;
        $created = [];
        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;
            $subjectCode = $this->nullableString($this->value($row, ['ma_mon_hoc', 'ma mon hoc']));
            $lessonCode = $this->nullableString($this->value($row, ['ma_bai_hoc', 'ma bai hoc', 'ma_bai', 'code']));
            $name = $this->nullableString($this->value($row, ['ten_bai_hoc', 'ten bai hoc', 'name']));

            if (blank($subjectCode) && blank($lessonCode) && blank($name)) {
                $this->skippedCount++;

                continue;
            }

            if (blank($lessonCode) || blank($name)) {
                $this->errors[] = "Dòng {$rowNumber}: thiếu Mã bài học hoặc Tên bài học.";
                $this->skippedCount++;

                continue;
            }

            // Đã chọn sẵn Môn trên form - cột "Mã môn học" (nếu điền) chỉ để
            // đối chiếu, không dùng để tra cứu môn khác.
            if (! blank($subjectCode) && ! $this->matchesForcedSubject($subjectCode)) {
                $this->errors[] = "Dòng {$rowNumber}: Mã môn học \"{$subjectCode}\" không khớp với môn \"{$this->forcedSubject->display_code}\" đã chọn.";
                $this->skippedCount++;

                continue;
            }
            $subject = $this->forcedSubject;

            $lessonKind = $this->mapKind($this->value($row, ['loai_bai', 'loai bai', 'loai'], 'Bài'));
            $sortOrder = (int) ($this->value($row, ['thu_tu', 'thu tu', 'sort_order'], $index + 1) ?: $index + 1);
            $theoryHours = (int) ($this->value($row, ['gio_ly_thuyet', 'gio ly thuyet'], 0) ?: 0);
            $practiceHours = (int) ($this->value($row, ['gio_thuc_hanh', 'gio thuc hanh'], 0) ?: 0);
            $examHours = (int) ($this->value($row, ['gio_thi'], 0) ?: 0);
            $semester = $this->mapSemester($this->value($row, ['hoc_ky', 'hoc ky'])) ?? $subject->semester;
            $description = $this->nullableString($this->value($row, ['mo_ta', 'mo ta', 'description']));

            $lesson = SubjectLesson::withTrashed()
                ->where('subject_id', $subject->id)
                ->where('code', $lessonCode)
                ->first();

            $payload = [
                'subject_id' => $subject->id,
                'code' => $lessonCode,
                'name' => $name,
                'lesson_kind' => $lessonKind,
                'sort_order' => $sortOrder,
                'theory_hours' => $theoryHours,
                'practice_hours' => $practiceHours,
                'exam_hours' => $examHours,
                'total_hours' => $theoryHours + $practiceHours + $examHours,
                'semester' => $semester,
                'description' => $description,
            ];

            if ($lesson) {
                $lesson->restore();
                $lesson->update($payload);
                $this->updatedCount++;
            } else {
                $lesson = SubjectLesson::create($payload);
                $this->importedCount++;
            }

            $parentCode = $this->nullableString($this->value($row, ['ma_bai_cha', 'ma bai cha']));

            $autoParentId = null;
            if (blank($parentCode)) {
                $autoParentId = match ($lessonKind) {
                    SubjectLesson::KIND_UNIT => null,
                    SubjectLesson::KIND_CHAPTER => $currentUnit?->id,
                    default => $currentChapter?->id ?? $currentUnit?->id,
                };
            }

            if ($lessonKind === SubjectLesson::KIND_UNIT) {
                $currentUnit = $lesson;
                $currentChapter = null;
            } elseif ($lessonKind === SubjectLesson::KIND_CHAPTER) {
                $currentChapter = $lesson;
            }

            $created[] = [
                'lesson' => $lesson,
                'subject_id' => $subject->id,
                'parent_code' => $parentCode,
                'auto_parent_id' => $autoParentId,
            ];
        }

        // Pass 2: phân giải "Mã bài cha" (điền tay) trong cùng môn, sau khi
        // mọi bài của lượt import này đã có ID. Dòng nào không điền cột này
        // thì dùng parent suy luận theo thứ tự dòng (auto_parent_id) đã
        // tính sẵn ở Pass 1.
        foreach ($created as $entry) {
            if (blank($entry['parent_code'])) {
                if ($entry['auto_parent_id'] && $entry['auto_parent_id'] !== $entry['lesson']->id) {
                    $entry['lesson']->update(['parent_id' => $entry['auto_parent_id']]);
                }

                continue;
            }

            $parent = SubjectLesson::where('subject_id', $entry['subject_id'])
                ->where('code', $entry['parent_code'])
                ->first();

            if (! $parent) {
                $this->errors[] = "Bài \"{$entry['lesson']->code}\": không tìm thấy Mã bài cha \"{$entry['parent_code']}\" trong cùng môn.";

                continue;
            }

            if ($parent->id === $entry['lesson']->id) {
                $this->errors[] = "Bài \"{$entry['lesson']->code}\": Mã bài cha không thể là chính nó.";

                continue;
            }

            $entry['lesson']->update(['parent_id' => $parent->id]);
        }
    }

    protected function matchesForcedSubject(string $code): bool
    {
        $code = trim($code);
        $actual = trim((string) $this->forcedSubject->code);

        return $code === $actual || Str::endsWith($actual, '_'.$code) || Str::endsWith($actual, $code);
    }

    protected function mapKind(mixed $value): string
    {
        $value = mb_strtolower(trim((string) $value));

        return match (true) {
            str_contains($value, 'unit') => SubjectLesson::KIND_UNIT,
            str_contains($value, 'chương'), str_contains($value, 'chuong') => SubjectLesson::KIND_CHAPTER,
            str_contains($value, 'con') => SubjectLesson::KIND_SUB,
            str_contains($value, 'thi') => SubjectLesson::KIND_EXAM,
            default => SubjectLesson::KIND_LESSON,
        };
    }

    protected function mapSemester(mixed $semester): ?string
    {
        if ($semester === null || $semester === '') {
            return null;
        }

        $semester = mb_strtolower(trim((string) $semester));
        if (preg_match('/(\d)/', $semester, $m)) {
            $n = (int) $m[1];
            if ($n >= 1 && $n <= 6) {
                return 'semester_'.$n;
            }
        }
        if (str_contains($semester, 'hè') || str_contains($semester, 'he')) {
            return 'summer';
        }

        return null;
    }

    /**
     * @param  \Illuminate\Support\Collection<string, mixed>  $row
     * @param  list<string>  $keys
     */
    protected function value(BaseCollection $row, array $keys, mixed $default = null): mixed
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

    protected function nullableString(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return trim((string) $value);
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

    /** @return list<string> */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
