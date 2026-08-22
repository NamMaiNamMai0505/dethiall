<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Modules\Lms\Models\LmsAssignmentSubmission;
use Modules\Lms\Models\LmsAssignmentSubmissionVersion;
use Modules\Lms\Models\LmsCourse;

/**
 * Sprint 9 T8 — Retention file bài nộp (dọn định kỳ).
 */
class PruneLmsSubmissions extends Command
{
    protected $signature = 'lms:prune-submissions
                            {--months=24 : Xóa file nộp của khóa kết thúc hơn N tháng}
                            {--archived : Chỉ khóa archived}
                            {--dry-run : Chỉ liệt kê, không xóa}';

    protected $description = 'Xóa file bài nộp LMS cũ theo policy retention (giữ metadata điểm)';

    public function handle(): int
    {
        if (! Schema::hasTable('lms_assignment_submissions')) {
            $this->error('Thiếu bảng submissions.');

            return self::FAILURE;
        }

        $months = max(1, (int) $this->option('months'));
        $dry = (bool) $this->option('dry-run');
        $cutoff = now()->subMonths($months);

        $courseQ = LmsCourse::query();
        if ($this->option('archived')) {
            $courseQ->where('status', 'archived');
        } else {
            $courseQ->where(function ($q) use ($cutoff) {
                $q->where(function ($q2) use ($cutoff) {
                    $q2->whereNotNull('ends_at')->where('ends_at', '<', $cutoff);
                })->orWhere(function ($q2) use ($cutoff) {
                    $q2->whereNull('ends_at')
                        ->where('status', 'archived')
                        ->where('updated_at', '<', $cutoff);
                });
            });
        }

        $courseIds = $courseQ->pluck('id');
        if ($courseIds->isEmpty()) {
            $this->info('Không có khóa nào trong phạm vi retention.');

            return self::SUCCESS;
        }

        $subs = LmsAssignmentSubmission::query()
            ->whereHas('assignment', fn ($q) => $q->whereIn('lms_course_id', $courseIds))
            ->whereNotNull('file_path')
            ->get();

        $deleted = 0;
        $bytes = 0;

        foreach ($subs as $sub) {
            $disk = $sub->disk ?: 'public';
            if ($sub->file_path && Storage::disk($disk)->exists($sub->file_path)) {
                try {
                    $bytes += (int) Storage::disk($disk)->size($sub->file_path);
                } catch (\Throwable $e) {
                }
                if (! $dry) {
                    Storage::disk($disk)->delete($sub->file_path);
                    $sub->update(['file_path' => null, 'file_name' => $sub->file_name ? '[pruned] '.$sub->file_name : null]);
                }
                $deleted++;
            }
        }

        if (Schema::hasTable('lms_assignment_submission_versions')) {
            $vers = LmsAssignmentSubmissionVersion::query()
                ->whereHas('submission.assignment', fn ($q) => $q->whereIn('lms_course_id', $courseIds))
                ->whereNotNull('file_path')
                ->get();
            foreach ($vers as $v) {
                $disk = $v->disk ?: 'public';
                if ($v->file_path && Storage::disk($disk)->exists($v->file_path)) {
                    if (! $dry) {
                        Storage::disk($disk)->delete($v->file_path);
                        $v->update(['file_path' => null]);
                    }
                    $deleted++;
                }
            }
        }

        $msg = ($dry ? '[DRY-RUN] ' : '')."lms:prune-submissions files={$deleted} ~MB=".round($bytes / 1048576, 2)." months={$months}";
        $this->info($msg);
        Log::info($msg);

        return self::SUCCESS;
    }
}
