<?php

namespace App\Http\Controllers\Traits;

use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

trait HasPermissionChecks
{
    /**
     * Check if user has permission
     */
    protected function checkPermission(string $permission): void
    {
        if (!Auth::check()) {
            abort(401, 'Chưa đăng nhập');
        }

        if (!Auth::user()->can($permission)) {
            abort(403, 'Không có quyền truy cập');
        }
    }

    /**
     * Check if user has role
     */
    protected function checkRole(string $role): void
    {
        if (!Auth::check()) {
            abort(401, 'Chưa đăng nhập');
        }

        if (!Auth::user()->hasRole($role)) {
            abort(403, 'Không có quyền truy cập');
        }
    }

    /**
     * Check if user has any of the permissions
     */
    protected function checkAnyPermission(array $permissions): void
    {
        if (!Auth::check()) {
            abort(401, 'Chưa đăng nhập');
        }

        if (!Auth::user()->hasAnyPermission($permissions)) {
            abort(403, 'Không có quyền truy cập');
        }
    }

    /**
     * Check if user has all permissions
     */
    protected function checkAllPermissions(array $permissions): void
    {
        if (!Auth::check()) {
            abort(401, 'Chưa đăng nhập');
        }

        if (!Auth::user()->hasAllPermissions($permissions)) {
            abort(403, 'Không có quyền truy cập');
        }
    }

    /**
     * Get permissions for current module based on controller name
     */
    protected function getModulePermissions(): array
    {
        $controllerName = class_basename(static::class);
        $moduleName = $this->getModuleName($controllerName);

        return [
            'view' => "{$moduleName}.index",
            'show' => "{$moduleName}.show", 
            'create' => "{$moduleName}.create",
            'edit' => "{$moduleName}.edit",
            'delete' => "{$moduleName}.delete",
        ];
    }

    /**
     * Get correct module name for permission checking
     */
    protected function getModuleName(string $controllerName): string
    {
        $moduleName = strtolower(str_replace('Controller', '', $controllerName));
        
        // Handle special cases where controller name doesn't match permission prefix
        $moduleMapping = [
            'trainingschedule' => 'training-schedules',
            'user' => 'users',
            'class' => 'classes',
            'instructor' => 'instructors',
            'teachingassignment'=> 'teaching-assignments',
            'scheduledetail'=> 'schdule-details',
            'subject' => 'subjects',
            'unit' => 'units',
            'classroom' => 'classrooms',
            'building' => 'buildings',
            'specialization' => 'specializations',
            'role' => 'roles',
            'permission' => 'permissions',
            'home' => 'home',
            'dashboard' => 'dashboard'
        ];
        
        return $moduleMapping[$moduleName] ?? $moduleName;
    }

    /**
     * Check permission for specific action
     */
    protected function checkActionPermission(string $action): void
    {
        $permissions = $this->getModulePermissions();
        
        if (isset($permissions[$action])) {
            $this->checkPermission($permissions[$action]);
        }
    }
}
