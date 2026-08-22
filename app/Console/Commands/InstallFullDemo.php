<?php

namespace App\Console\Commands;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\LmsDemoSeeder;
use Database\Seeders\ScheduleDemoSeeder;
use Database\Seeders\TrainingScheduleExportDemoSeeder;
use Illuminate\Console\Command;
use Modules\StandardHours\Database\Seeders\StandardHoursDemoSeeder;

class InstallFullDemo extends Command
{
    protected $signature = 'demo:install {--fresh : Xóa database rồi migrate lại trước khi seed}';

    protected $description = 'Cài bộ dữ liệu demo đầy đủ cho Dashboard, LMS, Grades, Lịch huấn luyện và Giờ chuẩn';

    public function handle(): int
    {
        if ($this->option('fresh')) {
            if (! $this->confirm('Lệnh --fresh sẽ xóa toàn bộ database hiện tại. Tiếp tục?', false)) {
                $this->warn('Đã hủy.');

                return self::SUCCESS;
            }
            $this->call('migrate:fresh', ['--force' => true]);
        }

        if (! User::query()->exists()) {
            $this->call('db:seed', ['--class' => DatabaseSeeder::class]);
        } else {
            $this->comment('Core data đã tồn tại — bỏ qua UserSeeder/core để tránh tạo trùng.');
        }
        $this->call('db:seed', ['--class' => ScheduleDemoSeeder::class]);
        $this->call('lms:provision-courses', ['--all' => true]);
        $this->call('db:seed', ['--class' => TrainingScheduleExportDemoSeeder::class]);
        $this->call('db:seed', ['--class' => LmsDemoSeeder::class]);
        $this->call('db:seed', ['--class' => StandardHoursDemoSeeder::class]);

        $this->newLine();
        $this->info('Bộ demo đã sẵn sàng. Mật khẩu các tài khoản demo: password');
        $this->line('Dashboard/Admin: admin@example.com');
        $this->line('LMS Giảng viên: giangvien@example.com');
        $this->line('LMS Giảng viên 2: gv2@example.com');
        $this->line('LMS Học viên: hocvien@example.com');
        $this->line('LMS Học viên 2: hv2@example.com');
        $this->line('Giờ chuẩn — Giảng viên: giangvien@example.com');
        $this->line('Giờ chuẩn — Quản lý duyệt: quanlykhoa@example.com');
        $this->line('Routes: /dashboard · /lms · /lms/gv · /lms/hoc · /grades · /standard-hours');
        $this->line('HDSD Giờ chuẩn: /standard-hours/guide');
        $this->line('Template Engine: /export-templates/lms · /export-templates/grades');
        $this->line('Demo LHL chia nhóm tiết: mã DEMO-LHL-EXPORT-RANGES');
        $this->line('HDSD LMS: docs/HDSD_LMS.md');

        return self::SUCCESS;
    }
}
