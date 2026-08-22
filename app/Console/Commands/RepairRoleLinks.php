<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Modules\User\Services\ManagementRoleIntegrityService;

class RepairRoleLinks extends Command
{
    protected $signature = 'roles:repair-links
        {--apply : Ghi users.role_id theo role thực tế duy nhất}
        {--json : Xuất kết quả dạng JSON}
        {--user=* : Chỉ xử lý user theo ID, email hoặc mã tài khoản}';

    protected $description = 'Rà soát/sửa liên kết role_id khi tài khoản chỉ có đúng một role thực tế';

    public function handle(ManagementRoleIntegrityService $service): int
    {
        $result = $service->repairRoleLinks(
            (array) $this->option('user'),
            (bool) $this->option('apply')
        );

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'dry_run' => ! $this->option('apply'),
                'candidates' => $result['candidates'],
                'applied' => $result['applied'],
                'rows' => $result['rows']->all(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        if ($result['rows']->isEmpty()) {
            $this->info('Không có liên kết role_id nào đủ điều kiện sửa an toàn.');

            return self::SUCCESS;
        }

        $this->table(
            ['ID', 'Tài khoản', 'Role hiện tại', 'Role thực tế', 'Kết quả'],
            $result['rows']->map(fn (array $row) => [
                $row['user_id'],
                $row['user_name'].' <'.$row['user_email'].'>',
                $row['current_role'] ?: 'Trống',
                $row['target_role'],
                $row['applied'] ? 'Đã đồng bộ' : 'Sẽ đồng bộ',
            ])->all()
        );

        if ($this->option('apply')) {
            $this->info("Đã đồng bộ {$result['applied']} tài khoản.");
        } else {
            $this->warn('DRY-RUN: chưa thay đổi dữ liệu. Dùng --apply sau khi kiểm tra danh sách.');
        }

        return self::SUCCESS;
    }
}
