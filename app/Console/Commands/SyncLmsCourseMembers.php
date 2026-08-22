<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Lms\Models\LmsCourse;
use Modules\Lms\Services\LmsCourseService;

/**
 * Sprint 8 M4 — Job đồng bộ members định kỳ (bọc LmsCourseService::syncMembersFromCore).
 */
class SyncLmsCourseMembers extends Command
{
    protected $signature = 'lms:sync-members
                            {--course= : Chỉ đồng bộ 1 khóa (id)}
                            {--published : Chỉ khóa published}
                            {--dry-run : Không ghi DB, chỉ liệt kê}';

    protected $description = 'Đồng bộ SV/GV LMS từ lớp hành chính + phân công giảng dạy';

    public function handle(LmsCourseService $service): int
    {
        if (! Schema::hasTable('lms_courses')) {
            $this->error('Bảng lms_courses chưa tồn tại.');

            return self::FAILURE;
        }

        $query = LmsCourse::query()->orderBy('id');

        if ($id = $this->option('course')) {
            $query->where('id', (int) $id);
        }
        if ($this->option('published')) {
            $query->where('status', LmsCourse::STATUS_PUBLISHED);
        }

        $courses = $query->get();
        if ($courses->isEmpty()) {
            $this->warn('Không có khóa LMS nào để đồng bộ.');
            Log::info('lms:sync-members — empty set');

            return self::SUCCESS;
        }

        $dry = (bool) $this->option('dry-run');
        $ok = 0;
        $fail = 0;

        $this->info(($dry ? '[DRY-RUN] ' : '').'Đồng bộ '.$courses->count().' khóa…');

        foreach ($courses as $course) {
            try {
                if (! $dry) {
                    $service->syncMembersFromCore($course);
                }
                $members = $course->members()->count();
                $this->line("  #{$course->id} {$course->title} → members={$members}");
                $ok++;
            } catch (\Throwable $e) {
                $fail++;
                $this->error("  #{$course->id} FAIL: ".$e->getMessage());
                Log::error('lms:sync-members failed', [
                    'course_id' => $course->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $summary = "lms:sync-members done ok={$ok} fail={$fail} dry=".($dry ? '1' : '0');
        $this->info($summary);
        Log::info($summary, ['ok' => $ok, 'fail' => $fail, 'dry_run' => $dry]);

        return $fail > 0 ? self::FAILURE : self::SUCCESS;
    }
}
