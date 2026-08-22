<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\Specialization\Models\Specialization;
use Modules\Subject\Models\Subject;
use Modules\Subject\Models\SubjectLesson;

/**
 * Dọn rác bài học còn sót lại từ lượt import CŨ (trước khi
 * subject-lessons:bulk-import sửa đúng phân cấp Unit/Chương), bị trộn lẫn
 * với dữ liệu MỚI vì mã bài không trùng nhau nên upsert-theo-mã không ghi
 * đè được. Chỉ xoá bài có mã KHÔNG nằm trong danh sách mã mà file dữ liệu
 * hiện tại (subject_lessons_import.json) sinh ra cho đúng môn đó - không
 * đụng tới môn ngoài phạm vi 271 môn của đợt import này.
 */
class CleanupDuplicateSubjectLessons extends Command
{
    protected $signature = 'subject-lessons:cleanup-duplicates
        {--apply : Xoá dữ liệu thật (mặc định chỉ xem trước, không thay đổi)}';

    protected $description = 'Dọn bài học rác còn sót từ lượt import cũ, trộn lẫn với dữ liệu bulk-import mới';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');

        $path = database_path('seeders/data/subject_lessons_import.json');
        if (! file_exists($path)) {
            $this->error("Không tìm thấy file dữ liệu: {$path}");

            return self::FAILURE;
        }

        $entries = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        $totalDeleted = 0;
        $affectedSubjects = 0;
        $rows = [];

        foreach ($entries as $entry) {
            $specialization = Specialization::query()->where('code', $entry['specialization_code'])->first();
            if (! $specialization) {
                continue;
            }

            $subject = Subject::query()
                ->where('specialization_id', $specialization->id)
                ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower(trim($entry['subject_name']))])
                ->first();
            if (! $subject) {
                continue;
            }

            $expectedCodes = collect($entry['lessons'])->pluck('code')->all();

            $orphans = SubjectLesson::query()
                ->where('subject_id', $subject->id)
                ->whereNotIn('code', $expectedCodes)
                ->get(['id', 'code', 'name', 'lesson_kind']);

            if ($orphans->isEmpty()) {
                continue;
            }

            $affectedSubjects++;
            $totalDeleted += $orphans->count();

            $rows[] = [
                'subject' => "{$entry['specialization_code']} — {$entry['subject_name']}",
                'count' => $orphans->count(),
                'sample' => $orphans->take(3)->map(fn ($l) => "{$l->code}:{$l->name}")->implode(', '),
            ];

            if ($apply) {
                SubjectLesson::query()->whereIn('id', $orphans->pluck('id'))->delete();
            }
        }

        if ($rows === []) {
            $this->info('Không có bài rác nào cần dọn.');

            return self::SUCCESS;
        }

        $this->table(
            ['Môn', 'Số bài rác', 'Ví dụ'],
            array_map(fn (array $r) => [$r['subject'], $r['count'], $r['sample']], $rows)
        );

        $this->info("Tổng: {$affectedSubjects} môn bị ảnh hưởng, {$totalDeleted} bài rác.");

        if ($apply) {
            $this->info('Đã xoá xong.');
        } else {
            $this->warn('DRY-RUN: chưa xoá gì. Dùng --apply sau khi kiểm tra danh sách ở trên.');
        }

        return self::SUCCESS;
    }
}
