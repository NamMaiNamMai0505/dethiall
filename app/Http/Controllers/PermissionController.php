<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use App\Models\User;

class PermissionController extends ModuleBaseController
{
    public function __construct()
    {
        parent::__construct();

        // Override module name for this controller
        $this->moduleName = 'permission';
    }

    /**
     * Display permissions overview
     */
    public function index()
    {
        $this->checkViewPermission();
        
        $roles = Role::with('permissions')->get();
        $permissions = Permission::all()->groupBy(function ($permission) {
            $parts = explode('.', $permission->name);
            return count($parts) > 1 ? $parts[1] : 'other';
        });
        
        $users = User::with('roles')->paginate(10);
        
        return view('user::permissions.index', compact('roles', 'permissions', 'users'));
    }

    /**
     * Show role management
     */
    public function roles()
    {
        $this->checkViewPermission();
        
        $roles = Role::with('permissions', 'users')->get();
        $permissions = Permission::all();
        
        return view('permissions.roles', compact('roles', 'permissions'));
    }

    /**
     * Store a new role
     */
    public function storeRole(Request $request)
    {
        $this->checkCreatePermission();
        
        $request->validate([
            'name' => 'required|string|unique:roles,name',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $role = Role::create(['name' => $request->name]);
        
        if ($request->permissions) {
            $role->syncPermissions($request->permissions);
        }

        return redirect()->route('permissions.roles')->with('success', 'Vai trò đã được tạo thành công!');
    }

    /**
     * Update role permissions
     */
    public function updateRole(Request $request, Role $role)
    {
        $this->checkEditPermission();
        
        $request->validate([
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        $role->syncPermissions($request->permissions ?? []);

        return redirect()->route('permissions.roles')->with('success', 'Quyền của vai trò đã được cập nhật!');
    }

    /**
     * Delete role
     */
    public function destroyRole(Role $role)
    {
        $this->checkDeletePermission();
        
        if ($role->users()->count() > 0) {
            return redirect()->route('permissions.roles')->with('error', 'Không thể xóa vai trò đang được sử dụng!');
        }

        $role->delete();

        return redirect()->route('permissions.roles')->with('success', 'Vai trò đã được xóa!');
    }

    /**
     * Show user permissions
     */
    public function users()
    {
        $this->checkViewPermission();
        
        $users = User::with('roles', 'permissions')->paginate(15);
        $roles = Role::all();
        
        return view('permissions.users', compact('users', 'roles'));
    }

    /**
     * Update user roles
     */
    public function updateUser(Request $request, User $user)
    {
        $this->checkEditPermission();
        
        $request->validate([
            'roles' => 'array',
            'roles.*' => 'exists:roles,id'
        ]);

        $roleNames = Role::whereIn('id', $request->roles ?? [])->pluck('name')->toArray();
        $user->syncRoles($roleNames);

        return redirect()->route('permissions.users')->with('success', 'Vai trò của người dùng đã được cập nhật!');
    }

    /**
     * Initialize permissions for all modules
     * NOTE: Use 'php artisan permissions:sync' command instead
     * This method is deprecated and kept for backward compatibility
     */
    public function initializePermissions(Request $request)
    {
        $this->checkCreatePermission();

        // Redirect to use the command instead
        return redirect()->route('permissions.index')->with('info', 'Please use "php artisan permissions:sync" command to initialize permissions.');
    }

    /**
     * Show module permissions overview
     */
    public function modules()
    {
        $this->checkViewPermission();

        // Get all modules from filesystem
        $modulesPath = base_path('modules');
        $modules = [];
        if (is_dir($modulesPath)) {
            $directories = scandir($modulesPath);
            foreach ($directories as $directory) {
                if ($directory !== '.' && $directory !== '..' && is_dir($modulesPath.'/'.$directory)) {
                    $modules[] = strtolower($directory);
                }
            }
        }

        // Get permissions grouped by module
        $permissionsByModule = [];
        if (auth()->check()) {
            $user = auth()->user();
            $permissions = $user->getAllPermissions();
            foreach ($permissions as $permission) {
                $parts = explode('.', $permission->name);
                if (count($parts) >= 2) {
                    $module = $parts[0];
                    $action = $parts[1];
                    if (!isset($permissionsByModule[$module])) {
                        $permissionsByModule[$module] = [];
                    }
                    $permissionsByModule[$module][] = $action;
                }
            }
        }

        return view('permissions.modules', compact('modules', 'permissionsByModule'));
    }

    /**
     * Get user permissions data for AJAX
     */
    public function getUserPermissions(User $user)
    {
        $this->checkViewPermission();
        
        return response()->json([
            'roles' => $user->roles->pluck('name'),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'direct_permissions' => $user->permissions->pluck('name')
        ]);
    }

    /**
     * Check if user can access specific action
     */
    public function checkAccess(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'permission' => 'required|string'
        ]);

        $user = User::find($request->user_id);
        $hasPermission = $user->can($request->permission);

        return response()->json([
            'has_permission' => $hasPermission,
            'user_name' => $user->name,
            'permission' => $request->permission
        ]);
    }
}
