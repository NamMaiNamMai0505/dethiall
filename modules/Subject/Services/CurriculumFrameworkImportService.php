<?php

namespace Modules\Subject\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Specialization\Models\Specialization;
use Modules\Subject\Models\Subject;
use Modules\Subject\Models\SubjectLesson;
use Modules\Subject\Support\SubjectCodePrefix;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/**
 * Import khung CTĐT dạng DSCTDDS.xlsx:
 * 1) Danh sách môn (mã, tên, TC, LT, TH, thi, ôn)
 * 2) Phân học kỳ
 * 3) Khung chương trình môn (bài/chương/Unit + bài con)
 *
 * Skip môn đã có (so khớp local code MxxxKy trong specialization).
 */
class CurriculumFrameworkImportService
{
    private const INSERT_CHUNK_SIZE = 500;

    public function import(
        string $filePath,
        int $specializationId,
        ?string $codePrefix = null,
        ?string $requiredFacultyCode = null
    ): array {
        $specialization = Specialization::query()->findOrFail($specializationId);
        $prefix = SubjectCodePrefix::normalizePrefix($codePrefix, $specialization);

        $this->ensureImportSchemaIsReady();
        $spreadsheet = $this->loadDataSpreadsheet($filePath, 'H');
        $sheet = $spreadsheet->getActiveSheet();
        $highest = (int) $sheet->getHighestRow();

        $title = trim((string) $sheet->getCell('A1')->getFormattedValue());

        // --- Parse sections ---
        $subjectsMaster = $this->parseMasterSubjects($sheet, $highest);
        $semesterMap = $this->parseSemesters($sheet, $highest); // localCode => semester_1..
        $lessonBlocks = $this->parseLessonBlocks($sheet, $highest); // localCode => [lessons]

        if ($subjectsMaster === []) {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            throw new \RuntimeException(
                'Không tìm thấy bảng môn học trong file. Hãy dùng đúng mẫu DSCTDDS.xlsx và giữ nguyên dòng tiêu đề "Mã MH".'
            );
        }

        if ($requiredFacultyCode !== null) {
            $requiredFacultyCode = strtoupper(trim($requiredFacultyCode));
            $outsideScope = collect(array_keys($subjectsMaster))
                ->filter(fn (string $localCode) => SubjectCodePrefix::facultyCodeFromSubjectCode($localCode) !== $requiredFacultyCode)
                ->take(5)
                ->values();

            if ($outsideScope->isNotEmpty()) {
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);

                throw new \RuntimeException(sprintf(
                    'File có môn ngoài phạm vi khoa %s (%s). Mọi mã môn phải kết thúc bằng %s.',
                    $requiredFacultyCode,
                    $outsideScope->implode(', '),
                    $requiredFacultyCode
                ));
            }
        }

        $stats = [
            'title' => $title,
            'prefix' => $prefix,
            'subjects_in_file' => count($subjectsMaster),
            'subjects_created' => 0,
            'subjects_skipped' => 0,
            'lessons_created' => 0,
            'lessons_skipped' => 0,
            'semesters_updated' => 0,
            'errors' => [],
        ];

        try {
            DB::transaction(function () use (
                $subjectsMaster,
                $semesterMap,
                $lessonBlocks,
                $specialization,
                $prefix,
                &$stats
            ) {
                $userId = Auth::id();

                foreach ($subjectsMaster as $localCode => $row) {
                    $fullCode = SubjectCodePrefix::buildSubjectCode($prefix, $localCode);

                    $existing = Subject::withTrashed()
                        ->where('specialization_id', $specialization->id)
                        ->where(function ($q) use ($localCode, $fullCode) {
                            $q->where('code', $fullCode)
                                ->orWhere('code', $localCode)
                                ->orWhere('code', 'like', '%_'.$localCode);
                        })
                        ->first();

                    if ($existing) {
                        if ($existing->trashed()) {
                            $existing->restore();
                        }
                        $stats['subjects_skipped']++;
                        $subject = $existing;
                        // vẫn cập nhật học kỳ / bài nếu thiếu
                    } else {
                        $conflictingSubject = Subject::withTrashed()
                            ->where('code', $fullCode)
                            ->first();
                        if ($conflictingSubject) {
                            $conflictingSubject->loadMissing('specialization:id,name,code');

                            throw new \RuntimeException(sprintf(
                                'Mã %s đã thuộc ngành %s. Hãy nhập prefix khác cho ngành đang import.',
                                $fullCode,
                                $conflictingSubject->specialization?->name ?? 'khác'
                            ));
                        }

                        $semester = $semesterMap[$localCode] ?? null;
                        $subject = Subject::create([
                            'name' => $row['name'],
                            'code' => $fullCode,
                            'abbreviation' => Subject::generateAbbreviationFromName($row['name']),
                            'color' => Subject::defaultColorForKey($localCode),
                            'specialization_id' => $specialization->id,
                            'credits' => $row['credits'],
                            'theory_hours' => $row['theory'],
                            'practice_hours' => $row['practice'],
                            'exam_hours' => $row['exam'],
                            'review_hours' => $row['review'],
                            'self_study_hours' => 0,
                            'semester' => $semester,
                            'level' => 'basic',
                            'assessment_method' => 'exam',
                            'is_required' => true,
                            'is_active' => true,
                            'created_by' => $userId,
                            'updated_by' => $userId,
                        ]);
                        $stats['subjects_created']++;
                    }

                    // Update semester if provided
                    if (! empty($semesterMap[$localCode]) && empty($subject->semester)) {
                        $subject->semester = $semesterMap[$localCode];
                        $subject->save();
                        $stats['semesters_updated']++;
                    } elseif (! empty($semesterMap[$localCode]) && $subject->semester !== $semesterMap[$localCode]) {
                        $subject->semester = $semesterMap[$localCode];
                        $subject->save();
                        $stats['semesters_updated']++;
                    }

                    // Lessons
                    $lessons = $lessonBlocks[$localCode] ?? [];
                    if ($lessons === []) {
                        continue;
                    }

                    $hasAny = SubjectLesson::query()->where('subject_id', $subject->id)->exists();
                    if ($hasAny) {
                        // skip re-import lessons if already has data
                        $stats['lessons_skipped'] += count($lessons);

                        continue;
                    }

                    $stats['lessons_created'] += $this->insertLessonsInBatches($subject, $lessons);
                }
            }, 3);
        } finally {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }

        return $stats;
    }

    /**
     * Import danh sách bài học cho một môn từ mẫu 3 cột:
     * Mã bài học, Tên bài học, Thứ tự.
     *
     * @return array{imported:int,updated:int,skipped:int}
     */
    public function importSubjectLessons(string $filePath, Subject $subject): array
    {
        $this->ensureImportSchemaIsReady();
        $spreadsheet = $this->loadDataSpreadsheet($filePath, 'C');

        try {
            $sheet = $spreadsheet->getActiveSheet();
            $highestRow = (int) $sheet->getHighestRow();
            $rows = $sheet->rangeToArray(
                'A1:C'.max(1, $highestRow),
                null,
                true,
                true,
                false
            );

            $preparedByCode = [];
            $skipped = 0;
            $fallbackOrder = 0;
            $now = now();

            foreach ($rows as $index => $row) {
                $code = trim((string) ($row[0] ?? ''));
                if (
                    $code === ''
                    || preg_match('/^mã\s*bài/iu', $code)
                ) {
                    if ($code === '' && $index !== 0) {
                        $skipped++;
                    }

                    continue;
                }

                if (mb_strlen($code) > 100) {
                    $skipped++;

                    continue;
                }

                $normalizedCode = mb_strtolower($code);
                if (isset($preparedByCode[$normalizedCode])) {
                    $skipped++;
                }

                $fallbackOrder++;
                $preparedByCode[$normalizedCode] = [
                    'subject_id' => $subject->id,
                    'parent_id' => null,
                    'code' => $code,
                    'name' => trim((string) ($row[1] ?? '')) ?: null,
                    'lesson_kind' => SubjectLesson::KIND_LESSON,
                    'sort_order' => is_numeric($row[2] ?? null)
                        ? max(0, (int) $row[2])
                        : $fallbackOrder,
                    'theory_hours' => 0,
                    'practice_hours' => 0,
                    'exam_hours' => 0,
                    'total_hours' => 0,
                    'semester' => $subject->semester,
                    'description' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                    'deleted_at' => null,
                ];
            }

            if ($preparedByCode === []) {
                return ['imported' => 0, 'updated' => 0, 'skipped' => $skipped];
            }

            $codes = array_column(array_values($preparedByCode), 'code');
            $existingCodes = SubjectLesson::withTrashed()
                ->where('subject_id', $subject->id)
                ->whereIn('code', $codes)
                ->pluck('code')
                ->mapWithKeys(fn ($code) => [mb_strtolower((string) $code) => true])
                ->all();

            $updated = count(array_intersect_key($preparedByCode, $existingCodes));
            $imported = count($preparedByCode) - $updated;

            foreach (array_chunk(array_values($preparedByCode), self::INSERT_CHUNK_SIZE) as $chunk) {
                SubjectLesson::query()->upsert(
                    $chunk,
                    ['subject_id', 'code'],
                    ['name', 'sort_order', 'updated_at', 'deleted_at']
                );
            }

            return compact('imported', 'updated', 'skipped');
        } finally {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }
    }

    /**
     * Ghi bài học theo lô để file khung lớn không tạo hàng nghìn query tuần tự.
     *
     * Root được insert trước, sau đó ánh xạ ID Unit để insert các bài con.
     * Mã đã xoá mềm vẫn được giữ trong tập kiểm tra để không vi phạm unique key.
     *
     * @param  list<array{code:string,name:string,kind:string,theory:int,practice:int,exam:int,total:int}>  $lessons
     */
    private function insertLessonsInBatches(Subject $subject, array $lessons): int
    {
        $usedCodes = SubjectLesson::withTrashed()
            ->where('subject_id', $subject->id)
            ->pluck('code')
            ->mapWithKeys(fn ($code) => [mb_strtolower((string) $code) => true])
            ->all();

        $rootRows = [];
        $childRows = [];
        $unitParentCode = null;
        $sort = 0;
        $now = now();

        foreach ($lessons as $lesson) {
            $sort++;
            $code = $this->uniqueLessonCode(
                (string) ($lesson['code'] ?: 'L'.$sort),
                $usedCodes
            );

            $row = [
                'subject_id' => $subject->id,
                'parent_id' => null,
                'code' => $code,
                'name' => $lesson['name'],
                'lesson_kind' => $lesson['kind'],
                'sort_order' => $sort,
                'theory_hours' => (int) $lesson['theory'],
                'practice_hours' => (int) $lesson['practice'],
                'exam_hours' => (int) $lesson['exam'],
                'total_hours' => (int) $lesson['total'],
                'semester' => $subject->semester,
                'description' => null,
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => null,
            ];

            if ($lesson['kind'] === SubjectLesson::KIND_SUB && $unitParentCode !== null) {
                $row['_parent_code'] = $unitParentCode;
                $childRows[] = $row;
            } else {
                $rootRows[] = $row;
            }

            if ($lesson['kind'] === SubjectLesson::KIND_UNIT) {
                $unitParentCode = $code;
            } elseif ($lesson['kind'] !== SubjectLesson::KIND_SUB) {
                $unitParentCode = null;
            }
        }

        foreach (array_chunk($rootRows, self::INSERT_CHUNK_SIZE) as $chunk) {
            SubjectLesson::query()->insert($chunk);
        }

        if ($childRows !== []) {
            $parentCodes = array_values(array_unique(array_column($childRows, '_parent_code')));
            $parentIds = SubjectLesson::query()
                ->where('subject_id', $subject->id)
                ->whereIn('code', $parentCodes)
                ->pluck('id', 'code');

            foreach ($childRows as &$row) {
                $parentCode = $row['_parent_code'];
                unset($row['_parent_code']);
                $row['parent_id'] = $parentIds->get($parentCode);
            }
            unset($row);

            foreach (array_chunk($childRows, self::INSERT_CHUNK_SIZE) as $chunk) {
                SubjectLesson::query()->insert($chunk);
            }
        }

        return count($rootRows) + count($childRows);
    }

    /**
     * @param  array<string, bool>  $usedCodes
     */
    private function uniqueLessonCode(string $requestedCode, array &$usedCodes): string
    {
        $base = trim($requestedCode) !== '' ? trim($requestedCode) : 'L';
        $code = $base;
        $suffix = 1;

        while (isset($usedCodes[mb_strtolower($code)])) {
            $code = $base.'-'.$suffix;
            $suffix++;
        }

        $usedCodes[mb_strtolower($code)] = true;

        return $code;
    }

    private function loadDataSpreadsheet(string $filePath, string $lastColumn)
    {
        try {
            $reader = IOFactory::createReaderForFile($filePath);
            $worksheetInfo = $reader->listWorksheetInfo($filePath);
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'File không phải workbook Excel hợp lệ hoặc đã bị hỏng.',
                previous: $e
            );
        }

        if ($worksheetInfo === []) {
            throw new \RuntimeException('File Excel không có sheet dữ liệu.');
        }

        $lastColumnIndex = Coordinate::columnIndexFromString($lastColumn);
        $reader->setReadDataOnly(true)
            ->setReadEmptyCells(false)
            ->setIncludeCharts(false)
            ->setLoadSheetsOnly([$worksheetInfo[0]['worksheetName']])
            ->setReadFilter(new class($lastColumnIndex) implements IReadFilter
            {
                public function __construct(private readonly int $lastColumnIndex) {}

                public function readCell($columnAddress, $row, $worksheetName = ''): bool
                {
                    return Coordinate::columnIndexFromString($columnAddress) <= $this->lastColumnIndex;
                }
            });

        try {
            return $reader->load($filePath);
        } catch (\Throwable $e) {
            throw new \RuntimeException(
                'Không đọc được dữ liệu Excel. Hãy tải lại file mẫu và không đổi cấu trúc sheet.',
                previous: $e
            );
        }
    }

    private function ensureImportSchemaIsReady(): void
    {
        $requiredColumns = [
            'subject_id',
            'parent_id',
            'code',
            'name',
            'lesson_kind',
            'sort_order',
            'theory_hours',
            'practice_hours',
            'exam_hours',
            'total_hours',
            'semester',
            'deleted_at',
        ];

        if (! Schema::hasTable('subject_lessons')) {
            throw new \RuntimeException(
                'CSDL chưa có bảng subject_lessons. Vui lòng chạy migration trước khi import.'
            );
        }

        $existingColumns = Schema::getColumnListing('subject_lessons');
        $missingColumns = array_values(array_diff($requiredColumns, $existingColumns));

        if ($missingColumns !== []) {
            throw new \RuntimeException(
                'CSDL bài học chưa được cập nhật. Thiếu cột: '.implode(', ', $missingColumns).'.'
            );
        }
    }

    protected function parseMasterSubjects($sheet, int $highest): array
    {
        $out = [];
        $inTable = false;

        for ($r = 1; $r <= $highest; $r++) {
            $a = $this->cell($sheet, 'A', $r);
            $b = $this->cell($sheet, 'B', $r);

            if (preg_match('/^HỌC\s*KỲ$/iu', $a) || preg_match('/^HỌC\s*KỲ$/iu', $b)) {
                break;
            }

            if (preg_match('/^Mã\s*MH$/iu', $a) || preg_match('/mã\s*mh/iu', $a)) {
                $inTable = true;

                continue;
            }

            if (! $inTable) {
                continue;
            }

            if (! preg_match('/^M\d+[A-Z]?\d*$/iu', $a) && ! preg_match('/^M\d+K\d+$/iu', $a)) {
                continue;
            }

            $local = strtoupper($a);
            $out[$local] = [
                'name' => $b !== '' ? $b : $local,
                'credits' => (int) $this->num($sheet, 'C', $r),
                'total' => (int) $this->num($sheet, 'D', $r),
                'theory' => (int) $this->num($sheet, 'E', $r),
                'practice' => (int) $this->num($sheet, 'F', $r),
                'exam' => (int) $this->num($sheet, 'G', $r),
                'review' => (int) $this->num($sheet, 'H', $r),
            ];
        }

        return $out;
    }

    protected function parseSemesters($sheet, int $highest): array
    {
        $map = [];
        $current = null;
        $inSemesterSection = false;

        for ($r = 1; $r <= $highest; $r++) {
            $a = $this->cell($sheet, 'A', $r);
            $b = $this->cell($sheet, 'B', $r);

            if (preg_match('/^HỌC\s*KỲ$/iu', $a) || preg_match('/^HỌC\s*KỲ$/iu', $b)) {
                $inSemesterSection = true;

                continue;
            }

            if (preg_match('/KHUNG\s*CHƯƠNG\s*TRÌNH\s*MÔN/iu', $a.$b)) {
                break;
            }

            if (! $inSemesterSection) {
                continue;
            }

            if (preg_match('/học\s*kỳ\s*(\d+)/iu', $a.' '.$b, $m)) {
                $n = (int) $m[1];
                $current = 'semester_'.$n;

                continue;
            }

            if ($current && preg_match('/^M\d+K\d+$/iu', $a)) {
                $map[strtoupper($a)] = $current;
            }
        }

        return $map;
    }

    /**
     * @return array<string, list<array{code:string,name:string,kind:string,theory:int,practice:int,exam:int,total:int}>>
     */
    protected function parseLessonBlocks($sheet, int $highest): array
    {
        $blocks = [];
        $currentCode = null;
        $inCurriculum = false;
        $inLessonTable = false;

        for ($r = 1; $r <= $highest; $r++) {
            $a = $this->cell($sheet, 'A', $r);
            $b = $this->cell($sheet, 'B', $r);

            if (preg_match('/KHUNG\s*CHƯƠNG\s*TRÌNH\s*MÔN/iu', $a.$b)) {
                $inCurriculum = true;

                continue;
            }

            if (! $inCurriculum) {
                continue;
            }

            // Subject header: M019K2 | Tên môn
            if (preg_match('/^M\d+K\d+$/iu', $a) && $b !== '') {
                $currentCode = strtoupper($a);
                $blocks[$currentCode] = $blocks[$currentCode] ?? [];
                $inLessonTable = false;

                continue;
            }

            if ($currentCode && preg_match('/Số\s*TT/iu', $a) && preg_match('/Nội\s*dung/iu', $b)) {
                $inLessonTable = true;

                continue;
            }

            if (! $currentCode || ! $inLessonTable) {
                continue;
            }

            // skip totals / notes
            if ($b === '' && $a === '') {
                continue;
            }
            if (preg_match('/^(tổng|cộng|kiểm tra:|thi kết)/iu', $b)) {
                if (preg_match('/^(tổng\s*cộng|cộng)$/iu', $b)) {
                    $inLessonTable = false;
                }

                continue;
            }
            // next subject header handled above

            // Chapter headers like I | Chương ...
            $name = $b !== '' ? $b : $a;
            if ($name === '') {
                continue;
            }

            // Sub-content under Unit: no STT in A, or B like 1. Grammar / 1.1. xxx
            $isSub = false;
            if ($a === '' && preg_match('/^\d+(\.\d+)*\.?\s+/u', $b)) {
                $isSub = true;
            } elseif ($a === '' && $b !== '' && ! preg_match('/^(Unit|Bài|Bài|Chương|CHƯƠNG|Thi|Kiểm)/iu', $b)) {
                // nested outline without number
                if (preg_match('/^(Grammar|Vocabulary|Skills|Reading|Listening|Speaking|Writing)/iu', $b)
                    || preg_match('/^\d+\./u', $b)) {
                    $isSub = true;
                }
            }

            if ($isSub) {
                $blocks[$currentCode][] = [
                    'code' => $this->makeSubCode($name, count($blocks[$currentCode]) + 1),
                    'name' => $name,
                    'kind' => SubjectLesson::KIND_SUB,
                    'theory' => (int) $this->num($sheet, 'D', $r),
                    'practice' => (int) $this->num($sheet, 'E', $r),
                    'exam' => (int) $this->num($sheet, 'F', $r),
                    'total' => (int) $this->num($sheet, 'C', $r),
                ];

                continue;
            }

            // Root lesson/unit/chapter rows usually have STT in A
            if ($a === '' && ! preg_match('/^(Unit|Bài|Bài|Chương)/iu', $b)) {
                // kiểm tra / thi lines without STT
                if (preg_match('/kiểm\s*tra|thi\s*kết/iu', $b)) {
                    $blocks[$currentCode][] = [
                        'code' => 'EX'.(count($blocks[$currentCode]) + 1),
                        'name' => $b,
                        'kind' => SubjectLesson::KIND_EXAM,
                        'theory' => 0,
                        'practice' => 0,
                        'exam' => (int) ($this->num($sheet, 'F', $r) ?: $this->num($sheet, 'C', $r)),
                        'total' => (int) $this->num($sheet, 'C', $r),
                    ];
                }

                continue;
            }

            $kind = $this->detectKind($name, $a);
            $code = $this->makeLessonCode($name, $a, $kind, count($blocks[$currentCode]) + 1);

            $blocks[$currentCode][] = [
                'code' => $code,
                'name' => $name,
                'kind' => $kind,
                'theory' => (int) $this->num($sheet, 'D', $r),
                'practice' => (int) $this->num($sheet, 'E', $r),
                'exam' => (int) $this->num($sheet, 'F', $r),
                'total' => (int) $this->num($sheet, 'C', $r),
            ];
        }

        return $blocks;
    }

    protected function detectKind(string $name, string $stt): string
    {
        if (preg_match('/^unit\s*\d+/iu', $name)) {
            return SubjectLesson::KIND_UNIT;
        }
        if (preg_match('/^chương|^chuong/iu', $name) || preg_match('/^[IVXLC]+$/u', $stt)) {
            return SubjectLesson::KIND_CHAPTER;
        }
        if (preg_match('/thi\s|kiểm\s*tra/iu', $name)) {
            return SubjectLesson::KIND_EXAM;
        }

        // Bài and Chương treated as same family for allocation — keep kind lesson/chapter for display
        if (preg_match('/^bài|^bài/iu', $name)) {
            return SubjectLesson::KIND_LESSON;
        }

        return SubjectLesson::KIND_LESSON;
    }

    protected function makeLessonCode(string $name, string $stt, string $kind, int $fallback): string
    {
        if (preg_match('/unit\s*(\d+)/iu', $name, $m)) {
            return 'U'.$m[1];
        }
        if (preg_match('/(?:bài|bài|chương|chuong)\s*(\d+)/iu', $name, $m)) {
            $prefix = $kind === SubjectLesson::KIND_CHAPTER ? 'C' : 'B';

            return $prefix.$m[1];
        }
        if (preg_match('/^\d+$/', $stt)) {
            return 'L'.$stt;
        }
        if (preg_match('/^[IVXLC]+$/u', $stt)) {
            return 'CH'.$stt;
        }

        return 'L'.$fallback;
    }

    protected function makeSubCode(string $name, int $fallback): string
    {
        if (preg_match('/^(\d+(?:\.\d+)*)/u', $name, $m)) {
            return 'S'.str_replace('.', '_', $m[1]);
        }

        return 'S'.$fallback;
    }

    protected function cell($sheet, string $col, int $row): string
    {
        $v = (string) $sheet->getCell($col.$row)->getFormattedValue();
        $v = preg_replace('/\s+/u', ' ', $v) ?? $v;

        return trim($v);
    }

    protected function num($sheet, string $col, int $row): float
    {
        $v = $this->cell($sheet, $col, $row);
        $v = str_replace([',', ' '], ['', ''], $v);
        if ($v === '' || ! is_numeric($v)) {
            return 0;
        }

        return (float) $v;
    }
}
