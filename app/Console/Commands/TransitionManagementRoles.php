<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Support\ManagementRole;
use App\Support\TrainingScheduleAccess;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class TransitionManagementRoles extends Command
{
    protected $signature = 'management-roles:transition
        {--apply : Ghi thay đổi role vào cơ sở dữ liệu}
        {--include-role-id-only : Bao gồm tài khoản nội bộ chỉ có role_id manager nhưng chưa gán role trong bảng phân quyền}
        {--user=* : Chỉ xử lý user theo ID, email hoặc mã tài khoản}';

    protected $description = 'Rà soát/chuyển manager cũ sang role quản lý lịch đào tạo chuyên trách';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $includeRoleIdOnly = (bool) $this->option('include-role-id-only');
        $legacyRole = Role::query()
            ->where('name', ManagementRole::LEGACY_MANAGER)
            ->where('guard_name', 'web')
            ->first();

        if (! $legacyRole) {
            $this->info('Không có role manager cũ để chuyển đổi.');

            return self::SUCCESS;
        }

        if ($apply) {
            $missing = collect([
                ManagementRole::TRAINING_OFFICE_MANAGER,
                ManagementRole::FACULTY_SCHEDULE_MANAGER,
            ])->reject(fn (string $name) => Role::query()
                ->where('name', $name)
                ->where('guard_name', 'web')
                ->exists());

            if ($missing->isNotEmpty()) {
                $this->error('Thiếu role: '.$missing->implode(', ').'. Hãy chạy migration hoặc permissions:sync trước.');

                return self::FAILURE;
            }
        }

        $query = User::query()
            ->with(['unit', 'roles'])
            ->where(function ($query) use ($includeRoleIdOnly, $legacyRole): void {
                $query->whereHas(
                    'roles',
                    fn ($roleQuery) => $roleQuery->where('name', ManagementRole::LEGACY_MANAGER)
                );

                if ($includeRoleIdOnly) {
                    $query->orWhere(function ($roleIdQuery) use ($legacyRole): void {
                        $roleIdQuery->where('role_id', $legacyRole->id)
                            ->where('user_type', 'internal_user');
                    });
                }
            })
            ->orderBy('id');

        $filters = collect((array) $this->option('user'))
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->values();

        if ($filters->isNotEmpty()) {
            $query->where(function ($query) use ($filters): void {
                foreach ($filters as $filter) {
                    $query->orWhere('id', ctype_digit($filter) ? (int) $filter : -1)
                        ->orWhere('email', $filter)
                        ->orWhere('code', $filter);
                }
            });
        }

        $users = $query->get();
        if ($users->isEmpty()) {
            $this->info('Không tìm thấy tài khoản manager cũ phù hợp.');

            return self::SUCCESS;
        }

        $rows = [];
        $changed = 0;
        $unresolved = 0;

        foreach ($users as $user) {
            $targetRole = $this->targetRole($user);
            $status = $targetRole ? ($apply ? 'Đã chuyển' : 'Sẽ chuyển') : 'Giữ nguyên — cần phân loại';

            if ($targetRole && $apply) {
                $roleNames = $user->roles
                    ->pluck('name')
                    ->reject(fn (string $name) => $name === ManagementRole::LEGACY_MANAGER)
                    ->push($targetRole)
                    ->unique()
                    ->values()
                    ->all();

                $user->syncRoles($roleNames);

                if (! $user->role_id || (int) $user->role_id === (int) $legacyRole->id) {
                    $user->forceFill([
                        'role_id' => Role::findByName($targetRole, 'web')->id,
                    ])->save();
                }

                $changed++;
            } elseif (! $targetRole) {
                $unresolved++;
            }

            $rows[] = [
                $user->id,
                $user->name,
                $user->email,
                $user->unit?->code ?: '—',
                $targetRole ? ManagementRole::label($targetRole) : '—',
                $status,
            ];
        }

        $this->table(
            ['ID', 'Họ tên', 'Email', 'Đơn vị', 'Role đề xuất', 'Kết quả'],
            $rows
        );

        if ($apply) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $this->info("Đã chuyển {$changed} tài khoản; {$unresolved} tài khoản được giữ nguyên để phân loại thủ công.");
        } else {
            $this->warn('DRY-RUN: chưa có dữ liệu nào bị thay đổi. Dùng --apply sau khi đã kiểm tra bảng trên.');
        }

        return self::SUCCESS;
    }

    private function targetRole(User $user): ?string
    {
        if ($user->user_type !== 'internal_user') {
            return null;
        }

        if (TrainingScheduleAccess::unitIsTrainingOffice($user)) {
            return ManagementRole::TRAINING_OFFICE_MANAGER;
        }

        if (TrainingScheduleAccess::unitIsFaculty($user)) {
            return ManagementRole::FACULTY_SCHEDULE_MANAGER;
        }

        return null;
    }
}
