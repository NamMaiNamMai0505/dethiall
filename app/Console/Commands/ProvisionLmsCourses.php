<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Modules\Class\Models\ClassModel;
use Modules\Lms\Services\LmsCourseProvisioningService;
use Modules\TrainingSchedule\Models\TrainingSchedule;

class ProvisionLmsCourses extends Command
{
    protected $signature = 'lms:provision-courses
                            {--schedule= : Chỉ xử lý một lịch đào tạo}
                            {--class= : Chỉ xử lý một lớp hành chính}
                            {--all : Bao gồm lịch đã ngừng hoạt động}
                            {--dry-run : Chỉ thống kê, không ghi dữ liệu}
                            {--no-content : Không đồng bộ khung bài học}';

    protected $description = 'Ghi danh môn theo ngành vào lớp LMS và đồng bộ dữ liệu từ lịch đào tạo';

    public function handle(LmsCourseProvisioningService $service): int
    {
        if (! Schema::hasTable('lms_courses') || ! Schema::hasColumn('lms_courses', 'provision_key')) {
            $this->error('Chưa chạy migration tích hợp LMS với lịch đào tạo.');

            return self::FAILURE;
        }

        $classQuery = ClassModel::query()->whereNotNull('specialization_id')->orderBy('id');
        if ($classId = $this->option('class')) {
            $classQuery->whereKey((int) $classId);
        } elseif (! $this->option('all')) {
            $classQuery->where('is_active', true);
        }
        if ($this->option('schedule')) {
            $classQuery->whereRaw('1 = 0');
        }

        $classes = $classQuery->get();
        if (! $this->option('dry-run')) {
            foreach ($classes as $class) {
                $result = $service->provisionFromClassCurriculum($class);
                $this->line("Lớp #{$class->id} {$class->name}: +{$result['created']} / ↻{$result['updated']} / lưu trữ {$result['archived']}");
            }
        }

        $query = TrainingSchedule::query()->hasDetails()->orderBy('id');
        if ($scheduleId = $this->option('schedule')) {
            $query->whereKey((int) $scheduleId);
        } elseif ($this->option('class')) {
            $query->whereRaw('1 = 0');
        } elseif (! $this->option('all')) {
            $query->where('is_active', true);
        }

        $schedules = $query->get();
        if ($this->option('dry-run')) {
            $this->info('Lớp theo ngành sẽ đồng bộ: '.$classes->count());
            $rows = $schedules->map(fn (TrainingSchedule $schedule) => [
                $schedule->id,
                $schedule->code ?: '—',
                $schedule->class_code ?: $schedule->class_id ?: '—',
                $schedule->scheduleDetails()->whereNotNull('subject_id')->distinct()->count('subject_id'),
            ]);
            $this->table(['ID', 'Mã lịch', 'Lớp', 'Số khóa dự kiến'], $rows);

            return self::SUCCESS;
        }

        $summary = ['created' => 0, 'updated' => 0, 'failed' => 0];
        foreach ($schedules as $schedule) {
            try {
                $result = $service->provisionFromTrainingSchedule($schedule, null, ! $this->option('no-content'));
                $summary['created'] += $result['created'];
                $summary['updated'] += $result['updated'];
                $this->line("#{$schedule->id} {$schedule->name}: +{$result['created']} / ↻{$result['updated']}");
            } catch (\Throwable $exception) {
                $summary['failed']++;
                $this->error("#{$schedule->id}: {$exception->getMessage()}");
                Log::error('lms:provision-courses failed', [
                    'training_schedule_id' => $schedule->id,
                    'exception' => $exception,
                ]);
            }
        }

        $this->info("Hoàn tất: mới={$summary['created']}, cập nhật={$summary['updated']}, lỗi={$summary['failed']}.");

        return $summary['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
