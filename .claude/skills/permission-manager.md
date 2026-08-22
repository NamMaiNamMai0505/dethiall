# Permission Manager Skill

## Purpose
Manage Role-Based Access Control (RBAC) using Spatie Laravel Permission package with custom sync command.

## When to Use
- User wants to add/modify permissions
- User needs to restrict access to features
- User asks about roles or permissions
- Creating new modules that need access control
- Debugging permission-related issues

## CRITICAL: System Overview (Updated 2025-12)

### ⚠️ IMPORTANT CHANGES
The permission system was refactored in December 2025. Key changes:
- **Convention changed**: Now uses `{module}.{action}` format (e.g., `users.index`)
- **Old format deprecated**: `{action}.{module}` (e.g., `view.user`) is NO LONGER used
- **Single source of truth**: Only `SyncPermissionsAndRoles.php` command
- **PermissionService removed**: DO NOT use or recreate
- **3 roles only**: super-admin, student, instructor

### System Roles (Only 3)

1. **super-admin**
   - Full system access to ALL 70 permissions
   - Only assigned to `admin@example.com`
   - Bypasses all permission checks

2. **student**
   - Permissions: `student-schedule.index`, `student-schedule.show`, `dashboards.index`
   - Can only view their own schedule

3. **instructor**
   - Permissions: `instructor-schedule.index`, `instructor-schedule.show`, `dashboards.index`
   - Can only view their teaching schedule

**Note**: Roles `admin`, `manager`, `staff` were removed in the refactor.

### Permission Naming Convention

**Format**: `{module}.{action}`

**Standard Actions:**
- `index` - List/view all resources
- `show` - View single resource details
- `create` - Create new resources
- `edit` - Update existing resources
- `delete` - Delete resources

**Examples:**
- ✅ `users.index` - View user list
- ✅ `classes.create` - Create new classes
- ✅ `training-schedules.edit` - Edit training schedules
- ✅ `student-schedule.show` - View student schedule details
- ❌ `view.student` - OLD FORMAT, DO NOT USE
- ❌ `create.class` - OLD FORMAT, DO NOT USE

**Module Naming Rules:**
- Lowercase with hyphens for multi-word modules
- Examples: `student-schedule`, `instructor-schedule`, `teaching-assignments`
- ❌ NOT: `Student schedule` (spaces), `StudentSchedule` (CamelCase)

## Managing Permissions

### Single Source of Truth

**CRITICAL**: All permission management is in `app/Console/Commands/SyncPermissionsAndRoles.php`

**DO NOT:**
- ❌ Create `PermissionSeeder.php` or similar seeders
- ❌ Create `PermissionService.php` (removed)
- ❌ Manually modify database tables
- ❌ Use migrations to create permissions (except for production hotfixes)

### 1. Add New Module Permissions

Edit `app/Console/Commands/SyncPermissionsAndRoles.php`:

```php
private function createPermissions()
{
    $this->info('Creating permissions...');

    $modules = [
        // Core admin modules (super-admin only)
        'users' => ['index', 'show', 'create', 'edit', 'delete'],

        // Add your new module here
        'your-module' => ['index', 'show', 'create', 'edit', 'delete'],

        // Schedule viewing modules (read-only)
        'student-schedule' => ['index', 'show'],
        'instructor-schedule' => ['index', 'show'],
    ];

    foreach ($modules as $module => $actions) {
        foreach ($actions as $action) {
            // CORRECT FORMAT: module.action
            $permissionName = "{$module}.{$action}";

            $permission = Permission::firstOrCreate(
                ['name' => $permissionName],
                ['guard_name' => 'web']
            );
        }
    }
}
```

### 2. Assign Permissions to Roles

In the same file, modify `assignPermissionsToRoles()` method:

```php
private function assignPermissionsToRoles()
{
    $this->info('Assigning permissions to roles...');

    // Super Admin - Gets ALL permissions automatically
    $superAdmin = Role::findByName('super-admin');
    $allPermissions = Permission::all();
    $superAdmin->syncPermissions($allPermissions);

    // Student - Read-only student schedule
    $student = Role::findByName('student');
    $studentPermissions = Permission::whereIn('name', [
        'student-schedule.index',
        'student-schedule.show',
        'dashboards.index',
        // Add more if needed
    ])->get();
    $student->syncPermissions($studentPermissions);

    // Instructor - Read-only instructor schedule
    $instructor = Role::findByName('instructor');
    $instructorPermissions = Permission::whereIn('name', [
        'instructor-schedule.index',
        'instructor-schedule.show',
        'dashboards.index',
        // Add more if needed
    ])->get();
    $instructor->syncPermissions($instructorPermissions);
}
```

### 3. Sync Permissions

```bash
# Update permissions (add new ones, keep existing)
php artisan permissions:sync

# Reset everything and rebuild from scratch
php artisan permissions:sync --reset
npm run sync-permission  # Alias for above
```

**When to use `--reset`:**
- When restructuring permissions completely
- When fixing permission naming issues
- When removing old permissions

**When to use without `--reset`:**
- Adding new module permissions
- Normal development updates

## Using Permissions

### In Routes

**Single Permission:**
```php
Route::middleware(['auth', 'permission:users.index'])
    ->group(function () {
        Route::get('/users', [UserController::class, 'index']);
    });
```

**Multiple Permissions (any):**
```php
Route::middleware(['auth', 'permission.multiple:users.edit,users.index'])
    ->group(function () {
        // User needs either users.edit OR users.index
    });
```

**Role-Based:**
```php
Route::middleware(['auth', 'role:instructor'])
    ->group(function () {
        // Only instructor role can access
    });
```

**Module Route Protection Pattern:**
```php
Route::middleware(['auth'])->group(function () {
    Route::get('/users', [UserController::class, 'index'])
        ->middleware('permission:users.index');

    Route::get('/users/create', [UserController::class, 'create'])
        ->middleware('permission:users.create');

    Route::post('/users', [UserController::class, 'store'])
        ->middleware('permission:users.create');

    Route::get('/users/{user}/edit', [UserController::class, 'edit'])
        ->middleware('permission:users.edit');

    Route::put('/users/{user}', [UserController::class, 'update'])
        ->middleware('permission:users.edit');

    Route::delete('/users/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:users.delete');
});
```

### In Controllers

**Using ModuleBaseController (Recommended):**

Most controllers extend `ModuleBaseController` which automatically applies permission middleware based on `$moduleName` property:

```php
class UserController extends ModuleBaseController
{
    public function __construct()
    {
        parent::__construct();
        // ModuleBaseController automatically applies:
        // - permission:users.index -> only(['index'])
        // - permission:users.show -> only(['show'])
        // - permission:users.create -> only(['create', 'store'])
        // - permission:users.edit -> only(['edit', 'update'])
        // - permission:users.delete -> only(['destroy'])
    }
}
```

**Manual Permission Middleware:**
```php
public function __construct()
{
    $this->middleware('permission:users.index')->only(['index']);
    $this->middleware('permission:users.create')->only(['create', 'store']);
    $this->middleware('permission:users.edit')->only(['edit', 'update']);
    $this->middleware('permission:users.delete')->only('destroy');
}
```

**In-Method Checks:**
```php
public function edit(User $user)
{
    // Option 1: authorize() method
    $this->authorize('users.edit');

    // Option 2: can() check
    if (!auth()->user()->can('users.edit')) {
        abort(403);
    }

    return view('user::edit', compact('user'));
}
```

**Using Form Requests:**
```php
// In CreateUserRequest.php
public function authorize(): bool
{
    return $this->user()->can('users.create');
}
```

### In Blade Views

**Check Permission:**
```blade
@can('users.edit')
    <a href="{{ route('users.edit', $user) }}">Edit</a>
@endcan

@can('users.delete')
    <form method="POST" action="{{ route('users.destroy', $user) }}">
        @csrf
        @method('DELETE')
        <button type="submit">Delete</button>
    </form>
@endcan
```

**Check Role:**
```blade
@role('super-admin')
    <p>Super Admin only content</p>
@endrole

@hasrole('instructor|student')
    <p>Instructor or Student content</p>
@endhasrole
```

**Check Multiple Permissions:**
```blade
@canany(['users.edit', 'users.delete'])
    <p>Can edit or delete users</p>
@endcanany
```

### In Code (Direct Checks)

```php
// Check permission
if (auth()->user()->can('users.edit')) {
    // User has permission
}

// Check role
if (auth()->user()->hasRole('instructor')) {
    // User is instructor
}

// Check any role
if (auth()->user()->hasAnyRole(['instructor', 'student'])) {
    // User is instructor or student
}

// Super admin bypass check
if (auth()->user()->hasRole('super-admin')) {
    // Super admin has all permissions automatically
}
```

## Custom Middleware

Project includes custom middleware in `bootstrap/app.php`:

**permission** - Single permission check:
```php
Route::middleware(['auth', 'permission:users.index'])->group(...);
```

**permission.multiple** - Multiple permissions (any):
```php
Route::middleware(['auth', 'permission.multiple:users.edit,users.index'])->group(...);
```

**role** - Role-based access:
```php
Route::middleware(['auth', 'role:instructor'])->group(...);
```

## Best Practices

### 1. Consistent Naming

✅ **Good:**
```php
$modules = [
    'users' => ['index', 'show', 'create', 'edit', 'delete'],
    'student-schedule' => ['index', 'show'],
    'instructor-schedule' => ['index', 'show'],
];
```

❌ **Avoid:**
```php
$modules = [
    'user' => ['view', 'create', 'edit', 'delete'], // Wrong: 'user' should be 'users', 'view' should be 'index'
    'Student schedule' => [...], // Wrong: has spaces
    'InstructorSchedule' => [...], // Wrong: CamelCase
];
```

### 2. Single Source of Truth

- ALL permissions defined in `SyncPermissionsAndRoles.php`
- NO separate seeders
- NO manual database modifications
- Run `permissions:sync` after every change

### 3. Module Actions

For full CRUD modules:
```php
'users' => ['index', 'show', 'create', 'edit', 'delete'],
```

For read-only modules:
```php
'student-schedule' => ['index', 'show'],
```

For dashboard/single-action modules:
```php
'dashboards' => ['index'],
```

### 4. Testing Permissions

```php
// In tests
$user = User::factory()->create();
$user->assignRole('student');

$this->actingAs($user)
    ->get('/student-schedule')
    ->assertOk();

$this->actingAs($user)
    ->get('/users') // No permission
    ->assertForbidden();
```

## Troubleshooting

### Permission Denied Errors

1. Check if permission exists:
   ```bash
   php artisan tinker
   >>> \Spatie\Permission\Models\Permission::where('name', 'like', 'users%')->get()
   ```

2. Check user has permission:
   ```bash
   >>> $user = \App\Models\User::find(1);
   >>> $user->getAllPermissions()->pluck('name');
   ```

3. Clear permission cache:
   ```bash
   php artisan cache:clear
   php artisan config:clear
   ```

4. Re-sync permissions:
   ```bash
   php artisan permissions:sync --reset
   ```

### Wrong Permission Format

If you see errors like "Permission `view.user` does not exist":
- This is the OLD format
- Update code to use `users.index` instead
- Run `php artisan permissions:sync --reset`

### New Permissions Not Appearing

1. Added to `SyncPermissionsAndRoles.php`? ✓
2. Run `php artisan permissions:sync`? ✓
3. Clear config cache? `php artisan config:clear` ✓
4. Check database `permissions` table ✓

### User Has Wrong Role

To assign roles to users:
```bash
php artisan tinker
>>> $user = \App\Models\User::where('email', 'student@example.com')->first();
>>> $user->syncRoles(['student']);
```

## Database Tables

Spatie creates these tables:
- `permissions` - All available permissions
- `roles` - All available roles (only 3: super-admin, student, instructor)
- `model_has_permissions` - User-permission assignments (direct)
- `model_has_roles` - User-role assignments
- `role_has_permissions` - Role-permission assignments

## Common Patterns

### Module-Wide Protection

```php
// In module's web.php
Route::middleware(['auth', 'permission:users.index'])->prefix('users')->group(function () {
    // All routes require users.index

    Route::get('/', [UserController::class, 'index'])->name('users.index');

    Route::middleware('permission:users.create')->group(function () {
        Route::get('/create', [UserController::class, 'create'])->name('users.create');
        Route::post('/', [UserController::class, 'store'])->name('users.store');
    });

    Route::middleware('permission:users.edit')->group(function () {
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('users.update');
    });

    Route::delete('/{user}', [UserController::class, 'destroy'])
        ->middleware('permission:users.delete')
        ->name('users.destroy');
});
```

### Dynamic Permission Checks

```php
// Check if user can perform any action on module
$module = 'users';
$canView = auth()->user()->can("{$module}.index");
$canEdit = auth()->user()->can("{$module}.edit");
$canCreate = auth()->user()->can("{$module}.create");
$canDelete = auth()->user()->can("{$module}.delete");

$hasAnyAccess = auth()->user()->hasAnyPermission([
    "{$module}.index",
    "{$module}.edit",
    "{$module}.create",
    "{$module}.delete",
]);
```

## Migration from Old System

If you encounter old permission format (`action.module`):

1. **DO NOT** try to support both formats
2. Update all code to use `module.action`
3. Run `php artisan permissions:sync --reset`
4. Update any hardcoded permission checks in code
5. Clear all caches

## References

- Spatie Permission Docs: https://spatie.be/docs/laravel-permission
- Main sync command: `app/Console/Commands/SyncPermissionsAndRoles.php`
- Config: `config/permission.php`
- Middleware registration: `bootstrap/app.php`
- Module documentation: `.claude/skills/module-generator.md`
