<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Modules\User\Services\ManagementRoleIntegrityService;

class AuditManagementRoles extends Command
{
    protected $signature = 'management-roles:audit
        {--json : Xuất kết quả dạng JSON để dùng trong pipeline}
        {--strict : Trả mã lỗi nếu còn bất kỳ cảnh báo hoặc lỗi cấu hình nào}';

    protected $description = 'Kiểm tra chỉ đọc role quản lý, permission và phạm vi đơn vị trước khi triển khai';

    public function handle(ManagementRoleIntegrityService $service): int
    {
        $audit = $service->audit();
        $summary = $audit['summary'];
        $issues = $audit['issues'];

        if ($this->option('json')) {
            $this->line((string) json_encode([
                'ok' => $issues->isEmpty(),
                'summary' => $summary,
                'issues' => $issues->all(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        } else {
            $this->renderHumanOutput($summary, $issues);
        }

        return $this->option('strict') && $issues->isNotEmpty()
            ? self::FAILURE
            : self::SUCCESS;
    }

    /**
     * @param  array{roles_checked: int, units_checked: int, users_checked: int, errors: int, warnings: int, repairable: int}  $summary
     * @param  Collection<int, array<string, mixed>>  $issues
     */
    private function renderHumanOutput(array $summary, Collection $issues): void
    {
        $this->info('Audit tính toàn vẹn role và phạm vi quản lý (chỉ đọc)');
        $this->line(sprintf(
            'Role: %d | Đơn vị: %d | Tài khoản: %d | Lỗi: %d | Cảnh báo: %d | Có thể sửa an toàn: %d',
            $summary['roles_checked'],
            $summary['units_checked'],
            $summary['users_checked'],
            $summary['errors'],
            $summary['warnings'],
            $summary['repairable']
        ));

        if ($issues->isEmpty()) {
            $this->info('Cấu hình role, permission, liên kết tài khoản và đơn vị hợp lệ.');

            return;
        }

        $this->table(
            ['Mức', 'Mã', 'Đối tượng', 'Chi tiết', 'Sửa an toàn'],
            $issues->map(fn (array $issue) => [
                strtoupper((string) $issue['severity']),
                $issue['code'],
                $issue['subject'],
                $issue['message'],
                $issue['repairable'] ? 'Có' : 'Không',
            ])->all()
        );

        $this->warn('Không có dữ liệu nào bị thay đổi. Xử lý các mục trên rồi chạy lại với --strict.');
    }
}
