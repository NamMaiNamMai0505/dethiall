<?php

namespace Modules\User\Controllers;

use App\Http\Controllers\Controller;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Http\Request;
use Modules\User\Requests\CreatePermissionRequest;
use Modules\User\Requests\UpdatePermissionRequest;

class PermissionController extends Controller
{
    public function index()
    {
        $permissions = Permission::withCount('roles')->paginate(10);
        return view('user::permissions.index', compact('permissions'));
    }

    public function create()
    {
        $roles = Role::pluck('name', 'id');
        return view('user::permissions.create', compact('roles'));
    }

    public function store(CreatePermissionRequest $request)
    {
        $data = $request->validated();
        $permission = Permission::create([
            'name' => $data['name'],
            'guard_name' => $data['guard_name'] ?? config('auth.defaults.guard')
        ]);
        if (!empty($data['roles'])) {
            $permission->syncRoles($data['roles']);
        }
        return redirect()->route('permissions.index')
                         ->with('success', 'Quyền đã được tạo thành công!');
    }

    public function edit(Permission $permission)
    {
        $roles = Role::pluck('name', 'id');
        $assigned = $permission->roles->pluck('id')->toArray();
        return view('user::permissions.edit', compact('permission', 'roles', 'assigned'));
    }

    public function update(UpdatePermissionRequest $request, Permission $permission)
    {
        $data = $request->validated();
        $permission->update(['name' => $data['name']]);
        if (isset($data['roles'])) {
            $permission->syncRoles($data['roles']);
        }
        return redirect()->route('permissions.index')
                         ->with('success', 'Quyền đã được cập nhật thành công!');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return redirect()->route('permissions.index')
                         ->with('success', 'Quyền đã được xóa thành công!');
    }

    public function show(Permission $permission)
    {
        $permission->load('roles');
        return view('user::permissions.show', compact('permission'));
    }

    /**
     * Display permission matrix grouped by modules and actions
     */
    public function matrix()
    {
        // Define modules and actions as used in seeder
        $resources = [
            'users', 'roles', 'permissions', 'units',
            'classes', 'classrooms', 'instructors',
            'specializations', 'subjects', 'training-schedules','teaching-assignments', 'schedule-details', 'buildings',
            'instructor-schedule', 'dashboards', 'student-schedule'
        ];
        $actions = ['index', 'create', 'show', 'edit', 'delete'];
        // Load existing permission names
        $existing = Permission::pluck('name')->toArray();
        return view('user::permissions.matrix', compact('resources','actions','existing'));
    }
}
