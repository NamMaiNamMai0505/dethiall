<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Lms\Models\LmsAssignmentSubmission;
use Modules\Lms\Models\LmsAssignmentSubmissionVersion;

class MigrateLmsSubmissionFiles extends Command
{
    protected $signature = 'lms:migrate-submissions-private
                            {--dry-run : Chỉ thống kê, không sao chép hoặc cập nhật dữ liệu}
                            {--keep-public : Giữ lại file public sau khi chuyển thành công}';

    protected $description = 'Chuyển file bài nộp LMS từ public disk sang private local disk';

    public function handle(): int
    {
        $query = LmsAssignmentSubmission::query()
            ->whereNotNull('file_path')
            ->where(function ($builder) {
                $builder->where('disk', 'public')->orWhereNull('disk');
            })
            ->orderBy('id');

        $total = (clone $query)->count();
        $dryRun = (bool) $this->option('dry-run');
        $moved = 0;
        $missing = 0;
        $failed = 0;

        $this->info(($dryRun ? '[DRY-RUN] ' : '')."Tìm thấy {$total} file bài nộp public.");

        $query->chunkById(100, function ($submissions) use ($dryRun, &$moved, &$missing, &$failed): void {
            foreach ($submissions as $submission) {
                $sourcePath = (string) $submission->file_path;
                if (! Storage::disk('public')->exists($sourcePath)) {
                    $missing++;
                    $this->warn("#{$submission->id}: không tìm thấy {$sourcePath}");

                    continue;
                }

                if ($dryRun) {
                    $moved++;

                    continue;
                }

                $stream = Storage::disk('public')->readStream($sourcePath);
                if (! is_resource($stream)) {
                    $failed++;
                    $this->error("#{$submission->id}: không đọc được file nguồn");

                    continue;
                }

                try {
                    $written = Storage::disk('local')->writeStream($sourcePath, $stream);
                } finally {
                    fclose($stream);
                }

                if (! $written || ! Storage::disk('local')->exists($sourcePath)) {
                    $failed++;
                    $this->error("#{$submission->id}: không ghi được file private");

                    continue;
                }

                DB::transaction(function () use ($submission, $sourcePath): void {
                    $submission->forceFill(['disk' => 'local'])->save();

                    LmsAssignmentSubmissionVersion::query()
                        ->where('lms_assignment_submission_id', $submission->id)
                        ->where('file_path', $sourcePath)
                        ->where(function ($builder) {
                            $builder->where('disk', 'public')->orWhereNull('disk');
                        })
                        ->update(['disk' => 'local']);
                });

                if (! $this->option('keep-public')) {
                    Storage::disk('public')->delete($sourcePath);
                }
                $moved++;
            }
        });

        $this->info("Hoàn tất: chuyển={$moved}, thiếu={$missing}, lỗi={$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
