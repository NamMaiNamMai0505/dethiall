<?php

namespace Modules\User\Services;

use App\Models\User;
use App\Support\ManagementRole;
use App\Support\RoleAssignment;
use App\Support\TrainingDept;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Unit\Models\Unit;
use Spatie\Permission\Models\Role;

class ManagementRoleIntegrityService
{
    /**
     * @return array{
     *     summary: array{roles_checked: int, units_checked: int, users_checked: int, errors: int, warnings: int, repairable: int},
     *     issues: Collection<int, array<string, mixed>>
     * }
     */
    public function audit(): array
    {
        $issues = collect();
        $roles = $this->auditRoles($issues);
        $units = $this->auditUnits($issues);
        $usersChecked = $this->auditUsers($roles, $issues);

        return [
            'summary' => [
                'roles_checked' => count(ManagementRole::scopedRoles()),
                'units_checked' => $units->count(),
                'users_checked' => $usersChecked,
                'errors' => $issues->where('severity', 'error')->count(),
                'warnings' => $issues->where('severity', 'warning')->count(),
                'repairable' => $issues->where('repairable', true)->count(),
            ],
            'issues' => $issues->values(),
        ];
    }

    /**
     * Đồng bộ cột users.role_id khi tài khoản chỉ có đúng một role thực tế.
     * Không gán role mới và không xử lý trường hợp có nhiều role.
     *
     * @param  list<int|string>  $identifiers
     * @return array{candidates: int, applied: int, rows: Collection<int, array<string, mixed>>}
     */
    public function repairRoleLinks(
        array $identifiers = [],
        bool $apply = false,
        ?User $actor = null
    ): array {
        $query = User::query()->with(['roles', 'roleRelation'])->orderBy('id');
        $this->applyUserFilters($query, $identifiers);
        $candidates = $query->get()
            ->map(fn (User $user) => $this->repairCandidate($user))
            ->filter()
            ->values();

        if (! $apply || $candidates->isEmpty()) {
            return [
                'candidates' => $candidates->count(),
                'applied' => 0,
                'rows' => $candidates,
            ];
        }

        $appliedRows = DB::transaction(function () use ($actor, $candidates): Collection {
            $rows = collect();

            foreach ($candidates as $candidate) {
                $user = User::query()
                    ->with(['roles', 'roleRelation'])
                    ->lockForUpdate()
                    ->find($candidate['user_id']);
                if (! $user) {
                    continue;
                }

                $freshCandidate = $this->repairCandidate($user);
                if (! $freshCandidate) {
                    continue;
                }

                $user->forceFill(['role_id' => $freshCandidate['target_role_id']])->save();
                $freshCandidate['applied'] = true;
                $rows->push($freshCandidate);

                Log::notice('Primary role link synchronized.', [
                    'actor_id' => $actor?->id,
                    'user_id' => $user->id,
                    'from_role_id' => $freshCandidate['current_role_id'],
                    'to_role_id' => $freshCandidate['target_role_id'],
                ]);
            }

            return $rows;
        });

        return [
            'candidates' => $candidates->count(),
            'applied' => $appliedRows->count(),
            'rows' => $appliedRows,
        ];
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $issues
     * @return Collection<string, Role>
     */
    private function auditRoles(Collection $issues): Collection
    {
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->whereIn('name', ManagementRole::scopedRoles())
            ->with('permissions')
            ->get()
            ->keyBy('name');

        foreach (ManagementRole::scopedRoles() as $roleName) {
            $role = $roles->get($roleName);
            if (! $role) {
                $issues->push($this->issue(
                    'error',
                    'missing_role',
                    ManagementRole::label($roleName),
                    'Role chưa tồn tại. Hãy chạy migration hoặc permissions:sync.'
                ));

                continue;
            }

            $expected = $this->expectedPermissions($roleName);
            $actual = $role->permissions->pluck('name')->sort()->values();
            $missing = $expected->diff($actual)->values();

            // Chỉ báo lỗi khi vai trò THIẾU quyền nền của nó. Quyền cấp thêm là
            // hợp lệ: quản trị viên tick trực tiếp trên ma trận, và migration cố
            // ý giữ quyền gộp cũ cho giai đoạn chuyển đổi.
            if ($missing->isNotEmpty()) {
                $issues->push($this->issue(
                    'error',
                    'missing_permissions',
                    ManagementRole::label($roleName),
                    'Thiếu quyền nền: '.$missing->implode(', ')
                ));
            }
        }

        return $roles;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $issues
     * @return Collection<int, Unit>
     */
    private function auditUnits(Collection $issues): Collection
    {
        $units = Unit::query()
            ->where(function ($query): void {
                $query->whereIn('functional_type', [
                    Unit::FUNCTIONAL_TRAINING_OFFICE,
                    Unit::FUNCTIONAL_FACULTY,
                ])->orWhereRaw('UPPER(code) = ?', [TrainingDept::UNIT_CODE_TRAINING_OFFICE]);

                foreach (TrainingDept::FACULTY_CODES as $facultyCode) {
                    $query->orWhereRaw('UPPER(code) = ?', [$facultyCode]);
                }
            })
            ->orderBy('id')
            ->get();

        foreach ($units as $unit) {
            $code = strtoupper(trim((string) $unit->code));
            $facultyCode = strtoupper(trim((string) $unit->faculty_code));
            $subject = sprintf('#%d %s (%s)', $unit->id, $unit->name, $unit->code);

            if (
                $code === TrainingDept::UNIT_CODE_TRAINING_OFFICE
                && $unit->functional_type !== Unit::FUNCTIONAL_TRAINING_OFFICE
            ) {
                $issues->push($this->issue(
                    'error',
                    'training_office_not_classified',
                    $subject,
                    'Đơn vị PHONG_DT chưa được phân loại là Phòng Đào tạo.'
                ));
            }

            if (
                in_array($code, TrainingDept::FACULTY_CODES, true)
                && ($unit->functional_type !== Unit::FUNCTIONAL_FACULTY || $facultyCode !== $code)
            ) {
                $issues->push($this->issue(
                    'error',
                    'faculty_not_classified',
                    $subject,
                    "Đơn vị {$code} phải có loại Khoa và mã phạm vi {$code}."
                ));
            }

            if (
                $unit->functional_type === Unit::FUNCTIONAL_FACULTY
                && ! in_array($facultyCode, TrainingDept::FACULTY_CODES, true)
            ) {
                $issues->push($this->issue(
                    'error',
                    'invalid_faculty_code',
                    $subject,
                    'Đơn vị loại Khoa phải có mã phạm vi hợp lệ từ K1 đến K8.'
                ));
            }
        }

        $units->where('functional_type', Unit::FUNCTIONAL_FACULTY)
            ->filter(fn (Unit $unit) => trim((string) $unit->faculty_code) !== '')
            ->groupBy(fn (Unit $unit) => strtoupper(trim((string) $unit->faculty_code)))
            ->filter(fn (Collection $group) => $group->count() > 1)
            ->each(function (Collection $group, string $facultyCode) use ($issues): void {
                $issues->push($this->issue(
                    'error',
                    'duplicate_faculty_code',
                    'Mã phạm vi '.$facultyCode,
                    'Đang được gán cho nhiều đơn vị: '.$group->map(
                        fn (Unit $unit) => sprintf('#%d %s', $unit->id, $unit->name)
                    )->implode(', ')
                ));
            });

        return $units;
    }

    /**
     * @param  Collection<string, Role>  $roles
     * @param  Collection<int, array<string, mixed>>  $issues
     */
    private function auditUsers(Collection $roles, Collection $issues): int
    {
        $usersChecked = 0;

        User::query()
            ->with(['unit', 'roles', 'roleRelation'])
            ->orderBy('id')
            ->chunkById(250, function (Collection $users) use ($issues, $roles, &$usersChecked): void {
                foreach ($users as $user) {
                    $usersChecked++;
                    $assignedRoles = $user->roles->unique('id')->values();
                    $assignedNames = $assignedRoles->pluck('name');
                    $primaryRole = $user->roleRelation;

                    if ($assignedRoles->count() === 1 && (int) $user->role_id !== (int) $assignedRoles->first()->id) {
                        $targetRole = $assignedRoles->first();
                        $issues->push($this->userIssue(
                            'error',
                            'role_link_mismatch',
                            $user,
                            sprintf(
                                'role_id đang là %s nhưng role thực tế duy nhất là %s.',
                                $primaryRole?->name ?? 'trống',
                                $targetRole->name
                            ),
                            true,
                            (int) $targetRole->id
                        ));
                    } elseif ($assignedRoles->isEmpty() && $primaryRole) {
                        $issues->push($this->userIssue(
                            'error',
                            'role_assignment_missing',
                            $user,
                            "role_id đang là {$primaryRole->name} nhưng tài khoản chưa có role thực tế."
                        ));
                    } elseif (
                        $assignedRoles->count() > 1
                        && (! $primaryRole || ! $assignedRoles->contains('id', $primaryRole->id))
                    ) {
                        $issues->push($this->userIssue(
                            'error',
                            'role_link_ambiguous',
                            $user,
                            'Tài khoản có nhiều role nhưng role_id không trỏ tới role nào đang được gán.'
                        ));
                    }

                    if ($assignedNames->contains(ManagementRole::LEGACY_MANAGER)) {
                        $issues->push($this->userIssue(
                            'warning',
                            'legacy_manager',
                            $user,
                            'Tài khoản vẫn dùng role manager cũ; cần chạy lệnh transition sau khi rà soát.'
                        ));
                    }

                    $dedicatedRoles = $assignedNames->intersect(ManagementRole::scopedRoles())->values();
                    if ($dedicatedRoles->count() > 1) {
                        $issues->push($this->userIssue(
                            'error',
                            'conflicting_management_roles',
                            $user,
                            'Đang gán đồng thời nhiều role quản lý chuyên trách: '.$dedicatedRoles->implode(', ')
                        ));
                    }

                    foreach ($dedicatedRoles as $roleName) {
                        $role = $roles->get($roleName);
                        if (! $role) {
                            continue;
                        }

                        $validation = RoleAssignment::roleUnitValidationError(
                            $role->id,
                            $user->unit_id ? (int) $user->unit_id : null,
                            $user->user_type
                        );

                        if ($validation) {
                            $issues->push($this->userIssue(
                                'error',
                                'invalid_role_scope',
                                $user,
                                ManagementRole::label($roleName).': '.$validation['message']
                            ));
                        }
                    }
                }
            }, 'users.id', 'id');

        return $usersChecked;
    }

    /**
     * Ma trận chuẩn của vai trò — một nguồn duy nhất là RoleCatalog, kể cả với
     * quản lý toàn trường. Trước đây vai trò này được suy ra bằng "tất cả quyền
     * trừ roles/permissions" nên luôn lệch với ma trận hiển thị trên giao diện.
     *
     * @return Collection<int, string>
     */
    private function expectedPermissions(string $roleName): Collection
    {
        return collect(ManagementRole::permissionNames($roleName))->sort()->values();
    }

    /** @return array<string, mixed>|null */
    private function repairCandidate(User $user): ?array
    {
        $assignedRoles = $user->roles->unique('id')->values();
        if ($assignedRoles->count() !== 1) {
            return null;
        }

        $targetRole = $assignedRoles->first();
        if ((int) $user->role_id === (int) $targetRole->id) {
            return null;
        }

        return [
            'user_id' => (int) $user->id,
            'user_name' => (string) $user->name,
            'user_email' => (string) $user->email,
            'current_role_id' => $user->role_id ? (int) $user->role_id : null,
            'current_role' => (string) ($user->roleRelation?->name ?? ''),
            'target_role_id' => (int) $targetRole->id,
            'target_role' => (string) $targetRole->name,
            'applied' => false,
        ];
    }

    /** @param  list<int|string>  $identifiers */
    private function applyUserFilters($query, array $identifiers): void
    {
        $filters = collect($identifiers)
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values();

        if ($filters->isEmpty()) {
            return;
        }

        $query->where(function ($filterQuery) use ($filters): void {
            foreach ($filters as $filter) {
                $filterQuery->orWhere('id', ctype_digit($filter) ? (int) $filter : -1)
                    ->orWhere('email', $filter)
                    ->orWhere('code', $filter);
            }
        });
    }

    /** @return array<string, mixed> */
    private function issue(
        string $severity,
        string $code,
        string $subject,
        string $message,
        bool $repairable = false,
        ?int $targetRoleId = null
    ): array {
        return [
            'severity' => $severity,
            'code' => $code,
            'subject' => $subject,
            'message' => $message,
            'repairable' => $repairable,
            'target_role_id' => $targetRoleId,
        ];
    }

    /** @return array<string, mixed> */
    private function userIssue(
        string $severity,
        string $code,
        User $user,
        string $message,
        bool $repairable = false,
        ?int $targetRoleId = null
    ): array {
        return $this->issue(
            $severity,
            $code,
            sprintf('#%d %s <%s>', $user->id, $user->name, $user->email),
            $message,
            $repairable,
            $targetRoleId
        ) + ['user_id' => (int) $user->id];
    }
}
