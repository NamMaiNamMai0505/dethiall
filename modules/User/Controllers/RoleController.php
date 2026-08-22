<?php

namespace Modules\User\Controllers;

use App\Http\Controllers\ModuleBaseController;
use App\Support\ManagementRole;
use App\Support\RoleCatalog;
use App\Support\RoleDisplay;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Modules\User\Requests\CreateRoleRequest;
use Modules\User\Requests\RepairRoleLinksRequest;
use Modules\User\Requests\UpdateRoleRequest;
use Modules\User\Services\ManagementRoleIntegrityService;
use Modules\User\Support\PermissionMatrix;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends ModuleBaseController
{
    public function index(): View
    {
        $roles = Role::query()
            ->with('permissions:id,name')
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->paginate(15);

        return view('user::roles.index', [
            'roles' => $roles,
            'subsystems' => PermissionMatrix::subsystemCoverage($roles->getCollection()),
            'catalogNames' => RoleCatalog::names(),
        ]);
    }

    public function integrity(ManagementRoleIntegrityService $service): View
    {
        $audit = $service->audit();

        return view('user::roles.integrity', [
            'summary' => $audit['summary'],
            'issues' => $audit['issues'],
        ]);
    }

    public function repairRoleLinks(
        RepairRoleLinksRequest $request,
        ManagementRoleIntegrityService $service
    ): RedirectResponse {
        $result = $service->repairRoleLinks(
            $request->validated('user_ids'),
            true,
            $request->user()
        );

        if ($result['applied'] === 0) {
            return redirect()
                ->route('roles.integrity')
                ->with('info', 'Không còn liên kết role_id nào đủ điều kiện đồng bộ an toàn.');
        }

        return redirect()
            ->route('roles.integrity')
            ->with('success', "Đã đồng bộ role_id cho {$result['applied']} tài khoản; role thực tế không bị thay đổi.");
    }

    public function create(): View
    {
        return view('user::roles.create', PermissionMatrix::build() + [
            'roleGroups' => RoleCatalog::groups(),
        ]);
    }

    public function store(CreateRoleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $role = Role::create([
            'name' => $data['name'],
            'guard_name' => 'web',
        ]);

        $this->syncRoleFromMatrix($role, $data);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('roles.edit', $role)
            ->with('success', 'Đã tạo vai trò và gán quyền thành công.');
    }

    public function show(Role $role): View
    {
        $role->load('permissions');

        return view('user::roles.show', PermissionMatrix::build($this->grantedNames($role)) + [
            'role' => $role,
        ]);
    }

    public function edit(Role $role): View
    {
        $role->load('permissions');
        $isSuperAdmin = $role->name === ManagementRole::SUPER_ADMIN;

        return view('user::roles.edit', PermissionMatrix::build($this->grantedNames($role)) + [
            'role' => $role,
            'isSystemRole' => $isSuperAdmin,
            'isSuperAdmin' => $isSuperAdmin,
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        $data = $request->validated();
        $isSuperAdmin = $role->name === ManagementRole::SUPER_ADMIN;

        // Chỉ khóa đổi tên super-admin
        if (! $isSuperAdmin) {
            $role->update(['name' => $data['name']]);
        }

        if ($isSuperAdmin) {
            // Super-admin luôn full quyền
            $role->syncPermissions(Permission::query()->where('guard_name', 'web')->get());
        } else {
            $this->syncRoleFromMatrix($role, $data);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('roles.edit', $role)
            ->with('success', 'Đã cập nhật quyền của vai trò "'.RoleDisplay::label($role->name).'".');
    }

    public function destroy(Role $role): RedirectResponse
    {
        // Super-admin xóa được mọi role trừ chính super-admin
        if ($role->name === 'super-admin') {
            return redirect()
                ->route('roles.index')
                ->with('error', 'Không thể xóa vai trò super-admin.');
        }

        // Gỡ quan hệ user-role trước khi xóa (an toàn)
        $role->users()->detach();
        $role->permissions()->detach();
        $role->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return redirect()
            ->route('roles.index')
            ->with('success', 'Đã xóa vai trò "'.$role->name.'".');
    }

    /** @return list<string> */
    private function grantedNames(Role $role): array
    {
        return $role->permissions->pluck('name')->all();
    }

    /**
     * Gán quyền từ ma trận (ứng dụng → hành động) cộng các quyền ngoài danh mục
     * được tick thủ công. Sync một lần để bỏ tick là thu hồi thật.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncRoleFromMatrix(Role $role, array $data): void
    {
        $guard = $role->guard_name ?: 'web';

        $names = PermissionMatrix::resolvePermissionNames($data['abilities'] ?? []);

        $permissions = $names === []
            ? collect()
            : Permission::query()->where('guard_name', $guard)->whereIn('name', $names)->get();

        $extraIds = collect($data['extra_permissions'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values();

        if ($extraIds->isNotEmpty()) {
            $permissions = $permissions->merge(
                Permission::query()->where('guard_name', $guard)->whereIn('id', $extraIds)->get()
            )->unique('id');
        }

        $role->syncPermissions($permissions->all());
    }
}
