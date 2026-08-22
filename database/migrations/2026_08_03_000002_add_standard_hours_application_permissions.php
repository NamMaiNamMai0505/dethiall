<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $definitions = [
            'standard-hours.view',
            'standard-hours.object-types.view', 'standard-hours.object-types.manage',
            'standard-hours.positions.view', 'standard-hours.positions.manage',
            'standard-hours.departments.view', 'standard-hours.departments.manage',
            'standard-hours.department-overtime.view', 'standard-hours.department-overtime.manage',
            'standard-hours.norm-reductions.view', 'standard-hours.norm-reductions.manage',
            'standard-hours.conversion-categories.view', 'standard-hours.conversion-categories.manage',
            'standard-hours.research-categories.view', 'standard-hours.research-categories.manage',
            'standard-hours.conversion-records.view', 'standard-hours.conversion-records.manage', 'standard-hours.conversion-records.approve',
            'standard-hours.research-records.view', 'standard-hours.research-records.manage', 'standard-hours.research-records.approve',
            'standard-hours.external-activities.view', 'standard-hours.external-activities.manage', 'standard-hours.external-activities.approve',
            'standard-hours.calculations.view', 'standard-hours.calculations.run', 'standard-hours.calculations.approve',
            'standard-hours.reports.view', 'standard-hours.reports.export',
            'standard-hours.hour-exchanges.view', 'standard-hours.hour-exchanges.manage',
            'standard-hours.settings.view', 'standard-hours.settings.manage',
        ];

        foreach ($definitions as $name) {
            DB::table('permissions')->insertOrIgnore([
                'name' => $name,
                'guard_name' => 'web',
            ]);
        }

        $permissionIds = DB::table('permissions')
            ->whereIn('name', $definitions)
            ->where('guard_name', 'web')
            ->pluck('id', 'name');

        $roles = [
            'standard-hours-manager' => $definitions,
            'exam-manager' => array_values(array_filter($definitions, fn (string $name) =>
                in_array($name, ['standard-hours.view', 'standard-hours.conversion-records.view', 'standard-hours.conversion-records.approve', 'standard-hours.research-records.view', 'standard-hours.research-records.approve', 'standard-hours.external-activities.view', 'standard-hours.external-activities.approve', 'standard-hours.calculations.view', 'standard-hours.calculations.approve', 'standard-hours.reports.view', 'standard-hours.reports.export'], true)
            )),
            'approval-agency' => array_values(array_filter($definitions, fn (string $name) =>
                str_ends_with($name, '.view') || str_ends_with($name, '.approve')
            )),
            'instructor' => array_values(array_filter($definitions, fn (string $name) =>
                in_array($name, ['standard-hours.view', 'standard-hours.conversion-records.view', 'standard-hours.conversion-records.manage', 'standard-hours.research-records.view', 'standard-hours.research-records.manage', 'standard-hours.external-activities.view', 'standard-hours.external-activities.manage'], true)
            )),
        ];

        foreach ($roles as $roleName => $names) {
            $roleId = DB::table('roles')->where('name', $roleName)->where('guard_name', 'web')->value('id');
            if (! $roleId) {
                continue;
            }

            foreach ($names as $name) {
                if (isset($permissionIds[$name])) {
                    DB::table('role_has_permissions')->insertOrIgnore([
                        'permission_id' => $permissionIds[$name],
                        'role_id' => $roleId,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Permissions are retained for compatibility with role assignments.
    }
};
