<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roles = DB::table('roles')->whereIn('name', [
            'training-office-manager',
            'faculty-schedule-manager',
            'standard-hours-manager',
        ])->where('guard_name', 'web')->get();

        $permissionIds = DB::table('permissions')->where('guard_name', 'web')->pluck('id', 'name');

        $allowed = [
            'training-office-manager' => [
                'dashboards.index', 'units.index', 'units.show',
                'specializations.index', 'specializations.show',
                'classes.index', 'classes.show', 'subjects.index', 'subjects.show',
                'subject-lessons.index', 'subject-lessons.show',
                'instructors.index', 'instructors.show', 'classrooms.index', 'classrooms.show',
                'training-schedules.index', 'training-schedules.show', 'training-schedules.create', 'training-schedules.edit',
                'schedule-details.index', 'schedule-details.show', 'schedule-details.create', 'schedule-details.edit',
                'export-templates.index', 'export-templates.show',
            ],
            'faculty-schedule-manager' => [
                'dashboards.index', 'units.index', 'units.show', 'classes.index', 'classes.show',
                'subjects.index', 'subjects.show', 'subject-lessons.index', 'subject-lessons.show',
                'subject-lessons.create', 'subject-lessons.edit', 'instructors.index', 'instructors.show',
                'classrooms.index', 'classrooms.show', 'training-schedules.index', 'training-schedules.show',
                'schedule-details.index', 'schedule-details.show', 'schedule-details.edit',
                'teaching-assignments.index', 'teaching-assignments.show', 'teaching-assignments.create', 'teaching-assignments.edit',
                'export-templates.index', 'export-templates.show',
            ],
            'standard-hours-manager' => [
                'dashboards.index', 'units.index', 'units.show', 'instructors.index', 'instructors.show',
                'standard-hours.index', 'standard-hours.show', 'standard-hours.create', 'standard-hours.edit',
                'standard-hours.delete', 'standard-hours.approve', 'export-templates.index', 'export-templates.show',
            ],
        ];

        foreach ($roles as $role) {
            DB::table('role_has_permissions')->where('role_id', $role->id)->delete();
            foreach ($allowed[$role->name] ?? [] as $name) {
                if (isset($permissionIds[$name])) {
                    DB::table('role_has_permissions')->insertOrIgnore([
                        'permission_id' => $permissionIds[$name],
                        'role_id' => $role->id,
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Permission assignments are data managed by the role UI; no unsafe rollback.
    }
};
