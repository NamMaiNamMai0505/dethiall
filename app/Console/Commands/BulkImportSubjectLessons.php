<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Modules\Specialization\Models\Specialization;
use Modules\Subject\Models\Subject;
use Modules\Subject\Models\SubjectLesson;

/**
 * Import hàng loạt bài học (Unit/Chương/Bài) cho các môn ĐÃ CÓ SẴN trên hệ
 * thống, dựng từ file "Khung CT-27-03.11.2025-in.xlsx". Không tạo Ngành/Môn
 * mới - chỉ khớp Ngành (theo Mã số) + Tên môn học với Subject đã tồn tại rồi
 * ghi Bài học vào đúng môn đó.
 *
 * Dữ liệu nguồn: database/seeders/data/subject_lessons_import.json
 */
class BulkImportSubjectLessons extends Command
{
    protected $signature = 'subject-lessons:bulk-import
        {--apply : Ghi dữ liệu (mặc định chỉ xem trước, không thay đổi)}';

    protected $description = 'Import hàng loạt Bài học cho các Môn đã có sẵn, khớp theo Ngành (Mã số) + Tên môn học';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $path = database_path('seeders/data/subject_lessons_import.json');
        if (! file_exists($path)) {
            $this->error("Không tìm thấy file dữ liệu: {$path}");

            return self::FAILURE;
        }

        $entries = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        $matched = 0;
        $unmatchedSpecialization = [];
        $unmatchedSubject = [];
        $importedLessons = 0;
        $updatedLessons = 0;

        foreach ($entries as $entry) {
            $specCode = $entry['specialization_code'];
            $subjectName = trim($entry['subject_name']);

            $specialization = Specialization::query()->where('code', $specCode)->first();
            if (! $specialization) {
                $unmatchedSpecialization[$specCode] = ($unmatchedSpecialization[$specCode] ?? 0) + 1;

                continue;
            }

            $subject = Subject::query()
                ->where('specialization_id', $specialization->id)
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($subjectName)])
                ->first();

            if (! $subject) {
                $unmatchedSubject[] = "{$specCode} — {$subjectName}";

                continue;
            }

            $matched++;

            if (! $apply) {
                continue;
            }

            DB::transaction(function () use ($entry, $subject, &$importedLessons, &$updatedLessons) {
                $codeToId = [];

                // Pass 1: tạo/cập nhật từng bài (chưa gán parent_id).
                foreach ($entry['lessons'] as $row) {
                    $lesson = SubjectLesson::withTrashed()
                        ->where('subject_id', $subject->id)
                        ->where('code', $row['code'])
                        ->first();

                    $payload = [
                        'subject_id' => $subject->id,
                        'code' => $row['code'],
                        'name' => $row['name'],
                        'lesson_kind' => $this->mapKind($row['kind']),
                        'sort_order' => $row['order'] ?? 0,
                        'theory_hours' => $row['theory_hours'] ?? 0,
                        'practice_hours' => $row['practice_hours'] ?? 0,
                        'exam_hours' => $row['exam_hours'] ?? 0,
                        'total_hours' => ($row['theory_hours'] ?? 0) + ($row['practice_hours'] ?? 0) + ($row['exam_hours'] ?? 0),
                        'description' => $row['description'] ?: null,
                    ];

                    if ($lesson) {
                        $lesson->restore();
                        $lesson->update($payload);
                        $updatedLessons++;
                    } else {
                        $lesson = SubjectLesson::create($payload);
                        $importedLessons++;
                    }

                    $codeToId[$row['code']] = $lesson->id;
                }

                // Pass 2: nối parent_id theo "parent_code" đã có sẵn từ trích xuất.
                foreach ($entry['lessons'] as $row) {
                    if (empty($row['parent_code']) || ! isset($codeToId[$row['parent_code']])) {
                        continue;
                    }

                    SubjectLesson::query()
                        ->whereKey($codeToId[$row['code']])
                        ->update(['parent_id' => $codeToId[$row['parent_code']]]);
                }
            });
        }

        $this->info("Khớp được Môn: {$matched} / ".count($entries));

        if ($unmatchedSpecialization !== []) {
            $this->warn('Ngành không tìm thấy (theo Mã số):');
            $this->table(
                ['Mã số Ngành', 'Số môn bị bỏ qua'],
                collect($unmatchedSpecialization)->map(fn ($count, $code) => [$code, $count])->values()
            );
        }

        if ($unmatchedSubject !== []) {
            $this->warn('Môn không khớp được tên trong đúng Ngành ('.count($unmatchedSubject).' môn) — cần đối chiếu lại tên môn trên hệ thống:');
            foreach ($unmatchedSubject as $row) {
                $this->line("  - {$row}");
            }
        }

        if ($apply) {
            $this->info("Đã ghi: {$importedLessons} bài mới, {$updatedLessons} bài cập nhật.");
        } else {
            $this->warn('DRY-RUN: chưa thay đổi dữ liệu. Dùng --apply sau khi kiểm tra danh sách khớp/không khớp ở trên.');
        }

        return self::SUCCESS;
    }

    private function mapKind(string $kind): string
    {
        return match (mb_strtolower(trim($kind))) {
            'unit' => SubjectLesson::KIND_UNIT,
            'chương', 'chuong' => SubjectLesson::KIND_CHAPTER,
            'bài con', 'bai con' => SubjectLesson::KIND_SUB,
            'thi' => SubjectLesson::KIND_EXAM,
            default => SubjectLesson::KIND_LESSON,
        };
    }
}
