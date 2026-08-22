<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Traits\HasPermissionChecks;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

class ModuleBaseController extends BaseController
{
    use AuthorizesRequests, HasPermissionChecks, ValidatesRequests;

    protected string $moduleName;
    protected bool $useGenericModulePermissions = true;

    public function __construct()
    {
        // Get module name from controller class
        $controllerName = class_basename(static::class);
        $this->moduleName = $this->getModuleName($controllerName);

        // Apply auth middleware
        $this->middleware('auth');

        // Leave Management declares its own granular route permissions. The
        // controller-name fallback would generate invalid names such as
        // `leavemanagement.index` and cause false 403 responses.
        $isLeaveManagement = str_starts_with(static::class, 'Modules\\LeaveManagement\\');

        if ($this->useGenericModulePermissions && ! $isLeaveManagement) {
            $this->middleware("permission:{$this->moduleName}.index")->only(['index']);
            $this->middleware("permission:{$this->moduleName}.show")->only(['show']);
            $this->middleware("permission:{$this->moduleName}.create")->only(['create', 'store']);
            $this->middleware("permission:{$this->moduleName}.edit")->only(['edit', 'update']);
            $this->middleware("permission:{$this->moduleName}.delete")->only(['destroy', 'restore']);
        }
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
            'student' => 'users',
            'class' => 'classes',
            'instructor' => 'instructors',
            'teachingassignment' => 'teaching-assignments',
            'scheduledetail' => 'schedule-details',
            'subject' => 'subjects',
            'unit' => 'units',
            'classroom' => 'classrooms',
            'specialization' => 'specializations',
            'role' => 'roles',
            'permission' => 'permissions',
            'home' => 'home',
            'dashboard' => 'dashboard',
            'building' => 'buildings',
            'campusnetwork' => 'campus-network',
        ];

        return $moduleMapping[$moduleName] ?? $moduleName;
    }

    /**
     * Check view permission before showing index
     */
    protected function checkViewPermission(): void
    {
        $this->checkActionPermission('view');
    }

    /**
     * Check create permission before showing create form or storing
     */
    protected function checkCreatePermission(): void
    {
        $this->checkActionPermission('create');
    }

    /**
     * Check edit permission before showing edit form or updating
     */
    protected function checkEditPermission(): void
    {
        $this->checkActionPermission('edit');
    }

    /**
     * Check delete permission before deleting
     */
    protected function checkDeletePermission(): void
    {
        $this->checkActionPermission('delete');
    }
}
