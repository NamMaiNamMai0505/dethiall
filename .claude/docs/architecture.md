# Architecture Documentation

## System Overview

The Training Management System (Hệ thống Quản lý Đào Tạo) is built on Laravel 12 using a **custom modular architecture** where each business domain is encapsulated in its own module.

**Key Characteristics:**
- **Modular monolith** - Not microservices, not standard Laravel structure
- **Custom module system** - Not nanobox/laravel-modules package
- **Role-based access control** - Using Spatie Laravel Permission
- **Convention over configuration** - Strong coding standards

## Architecture Diagram

```
Training Management System
│
├── Application Layer (Laravel 12)
│   ├── bootstrap/
│   │   ├── app.php          # App configuration, middleware, schedules
│   │   └── providers.php    # Service provider registration
│   │
│   ├── modules/             # Business logic modules (custom)
│   │   ├── ModuleServiceProvider.php  # Base class
│   │   ├── Student/
│   │   ├── Class/
│   │   ├── Instructor/
│   │   └── [16 total modules]
│   │
│   ├── app/
│   │   ├── Models/          # Shared models (User, etc.)
│   │   ├── Http/
│   │   │   └── Middleware/  # Custom middleware
│   │   └── Console/
│   │       └── Commands/    # Artisan commands
│   │
│   └── routes/              # Global routes
│       ├── web.php
│       └── api.php
│
├── Data Layer
│   ├── MySQL Database       # Primary data store
│   ├── Eloquent ORM         # Data access layer
│   └── Migrations           # Schema version control
│
├── Presentation Layer
│   ├── Blade Templates      # Server-side rendering
│   ├── Tailwind CSS v4      # Styling
│   ├── Alpine.js            # Client-side interactivity
│   └── Vite                 # Asset bundling
│
└── Infrastructure
    ├── Viettel Cloud S3     # File storage & backups
    └── Spatie Backup        # Automated backups
```

## Module Architecture

### Design Principles

1. **Domain-Driven Design (DDD)**
   - Each module represents a business domain
   - Self-contained with own controllers, models, views, routes
   - Clear boundaries between modules

2. **Separation of Concerns**
   - Controllers: Handle HTTP requests/responses
   - Models: Business logic and data access
   - Requests: Validation rules
   - Views: Presentation logic
   - Routes: URL mapping

3. **Convention Over Configuration**
   - Standardized module structure
   - Predictable naming patterns
   - Consistent CRUD operations

### Module Structure (Standard Template)

```
modules/{ModuleName}/
│
├── Controllers/
│   └── {ModuleName}Controller.php
│       - index()    # List resources
│       - create()   # Show create form
│       - store()    # Save new resource
│       - show()     # Display single resource
│       - edit()     # Show edit form
│       - update()   # Update resource
│       - destroy()  # Delete resource
│
├── Models/
│   └── {ModelName}.php
│       - Relationships (belongsTo, hasMany, etc.)
│       - Scopes (query builders)
│       - Accessors/Mutators
│       - Business logic methods
│
├── Requests/
│   ├── Create{ModuleName}Request.php
│   │   - rules()         # Validation rules for creation
│   │   - authorize()     # Permission check
│   │
│   └── Update{ModuleName}Request.php
│       - rules()         # Validation rules for updates
│       - authorize()     # Permission check
│
├── Providers/
│   └── {ModuleName}ServiceProvider.php
│       - boot()          # Register routes, views, migrations
│       - register()      # Bind services to container
│
├── Routes/
│   ├── web.php          # Web routes (with CSRF protection)
│   └── api.php          # API routes (stateless)
│
└── Views/
    ├── index.blade.php   # List view
    ├── create.blade.php  # Create form
    ├── edit.blade.php    # Edit form
    └── show.blade.php    # Detail view
```

### Module Communication

**Direct Model Relationships (Preferred):**
```php
// Student module can directly access Class model
$student->class;  // BelongsTo relationship
$class->students; // HasMany relationship
```

**Shared Models:**
- Core models in `app/Models/` (e.g., User)
- Domain models in `modules/{Module}/Models/`
- All namespaced under `Modules\` or `App\Models\`

**No Event Bus or Message Queue Between Modules:**
- Modules communicate via direct method calls
- Relationships via Eloquent
- Keep it simple for monolithic architecture

## Available Modules

| Module              | Purpose                           | Key Models          |
|---------------------|-----------------------------------|---------------------|
| Authentication      | Login, logout, password reset     | User                |
| Building            | Building/facility management      | Building            |
| Class               | Class management                  | ClassModel          |
| Classroom           | Physical classroom resources      | Classroom           |
| Dashboard           | Admin dashboard                   | -                   |
| Home                | Public homepage                   | -                   |
| Instructor          | Instructor profiles               | Instructor          |
| InstructorSchedule  | Instructor schedule viewing       | -                   |
| ScheduleDetail      | Schedule detail management        | ScheduleDetail      |
| Specialization      | Academic specializations          | Specialization      |
| Student             | Student profiles                  | Student             |
| StudentSchedule     | Student schedule viewing          | -                   |
| Subject             | Subject/course catalog            | Subject             |
| TeachingAssignment  | Instructor-class assignments      | TeachingAssignment  |
| TrainingSchedule    | Training program schedules        | TrainingSchedule    |
| Unit                | Organizational units/departments  | Unit                |
| User                | User account management           | User                |

## Data Flow

### Standard CRUD Flow

```
User Request
    ↓
Route (with middleware: auth, permission)
    ↓
Controller Method
    ↓
Form Request Validation (authorize + rules)
    ↓
Model Operation (via Eloquent)
    ↓
Database Transaction
    ↓
Response (redirect or view)
    ↓
View Rendering (Blade)
    ↓
User Response
```

### Example: Creating a Student

```
1. GET /students/create
   → StudentController@create
   → permission:create.student middleware
   → Returns create view

2. POST /students
   → StudentController@store
   → CreateStudentRequest validates data
   → CreateStudentRequest checks permission
   → Student::create($validated)
   → DB transaction
   → Redirect to students.index with success message

3. GET /students
   → StudentController@index
   → permission:view.student middleware
   → Student::with('class')->paginate(15)
   → Returns index view with students
```

## Permission System Architecture

### Layer Structure

```
User
  ↓ has
Role (super-admin, admin, manager, instructor, staff)
  ↓ has
Permission (view.student, create.class, etc.)
  ↓ guards
Resource (routes, controllers, views)
```

### Permission Flow

```
Request
    ↓
Authentication (auth middleware)
    ↓
Permission Check (permission middleware)
    ↓
Check Cache (Spatie Permission)
    ↓
┌─ Super Admin? → Bypass all checks → Grant Access
│
└─ Has Permission?
    ├─ Yes → Grant Access
    └─ No  → 403 Forbidden
```

### Permission Sync Process

```
php artisan permissions:sync
    ↓
Read SyncPermissionsAndRoles.php
    ↓
For each module:
    ├─ Create/update permissions in DB
    └─ Assign to roles
    ↓
Clear permission cache
    ↓
Done
```

## Database Architecture

### Schema Organization

**Core Tables:**
- `users` - User accounts
- `roles` - RBAC roles
- `permissions` - RBAC permissions
- `model_has_roles` - User-role pivot
- `model_has_permissions` - User-permission pivot
- `role_has_permissions` - Role-permission pivot

**Domain Tables:**
- `students` - Student records
- `classes` - Class information
- `instructors` - Instructor profiles
- `subjects` - Subjects/courses
- `specializations` - Academic programs
- `training_schedules` - Training program schedules
- `schedule_details` - Individual schedule entries
- `teaching_assignments` - Instructor-class assignments
- `buildings` - Building/facility info
- `classrooms` - Physical classrooms
- `units` - Organizational units

### Relationship Patterns

**Common Patterns:**

```php
// BelongsTo (many-to-one)
class Student extends Model
{
    public function class(): BelongsTo
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }
}

// HasMany (one-to-many)
class ClassModel extends Model
{
    public function students(): HasMany
    {
        return $this->hasMany(Student::class, 'class_id');
    }
}

// BelongsToMany (many-to-many with pivot)
class Student extends Model
{
    public function subjects(): BelongsToMany
    {
        return $this->belongsToMany(Subject::class, 'student_subject')
            ->withPivot('grade', 'semester')
            ->withTimestamps();
    }
}
```

**Soft Deletes:**
```php
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    use SoftDeletes;

    // Creates 'deleted_at' column
    // Model::withTrashed() to include soft deleted
    // Model::onlyTrashed() to get only soft deleted
}
```

## Service Provider Architecture

### Provider Hierarchy

```
ServiceProvider (Laravel Base)
    ↓ extends
ModuleServiceProvider (Abstract Base)
    ↓ extends
Concrete Module Providers (Student, Class, etc.)
```

### ModuleServiceProvider (Base Class)

```php
abstract class ModuleServiceProvider extends ServiceProvider
{
    protected string $moduleName;      // e.g., "Student"
    protected string $moduleNameLower; // e.g., "student"

    public function boot(): void
    {
        $this->registerConfig();    // Load config files
        $this->registerViews();     // Register view namespace
        $this->loadMigrationsFrom(module_path($this->moduleName, 'Database/Migrations'));
    }

    protected function registerViews(): void
    {
        // Registers views as: {modulename}::viewname
        $viewPath = module_path($this->moduleName, 'Views');
        $this->loadViewsFrom($viewPath, $this->moduleNameLower);
    }
}
```

### Module Provider Lifecycle

```
1. Application Boot
   ↓
2. Load bootstrap/providers.php
   ↓
3. For each provider:
   ├─ Call register() method
   │  └─ Bind services to container
   ↓
4. After all registered:
   ├─ Call boot() method
   │  ├─ Register routes
   │  ├─ Register views
   │  ├─ Load migrations
   │  └─ Publish assets
```

## Frontend Architecture

### Tech Stack

- **Blade** - Server-side templating
- **Tailwind CSS v4** - Utility-first CSS
- **Alpine.js** - Lightweight JavaScript framework
- **Vite** - Fast asset bundling with HMR

### Layout Structure

```
resources/views/layouts/
│
├── admin.blade.php      # Admin panel layout
│   ├─ Navigation
│   ├─ Sidebar
│   ├─ Main content area
│   └─ Footer
│
└── app.blade.php        # Public site layout
    ├─ Header
    ├─ Main content area
    └─ Footer
```

### Component Organization

**Blade Components:**
```
resources/views/components/
├── button.blade.php
├── input.blade.php
├── modal.blade.php
└── alert.blade.php

Usage:
<x-button type="primary">Save</x-button>
<x-input name="email" label="Email" />
```

**Tailwind v4 Specifics:**
```css
/* Use @import instead of @tailwind directives */
@import "tailwindcss";

/* Custom utilities */
@layer utilities {
  .custom-class {
    /* ... */
  }
}
```

## Security Architecture

### Authentication Flow

```
User Login
    ↓
Validate Credentials
    ↓
Create Session
    ↓
Set auth()->user()
    ↓
Redirect to intended page
```

### Authorization Layers

**Layer 1: Route Middleware**
```php
Route::middleware(['auth', 'permission:view.student'])
```

**Layer 2: Controller Authorization**
```php
$this->authorize('edit.student');
```

**Layer 3: Form Request Authorization**
```php
public function authorize(): bool
{
    return $this->user()->can('create.student');
}
```

**Layer 4: View-Level Checks**
```blade
@can('delete.student')
    <button>Delete</button>
@endcan
```

### CSRF Protection

- All POST/PUT/PATCH/DELETE forms require `@csrf` token
- API routes typically exempt (stateless)
- Validation happens in middleware

## Backup Architecture

### Backup Strategy

**Daily Backups:**
- Database only: 2:00 AM
- Cleanup old backups: 4:00 AM
- Health monitoring: 5:00 AM

**Weekly Backups:**
- Full (database + files): Sunday 3:00 AM

**Storage:**
- Local: `storage/app/backups/`
- Remote: Viettel Cloud S3

**Retention:**
- Daily: Keep 7 days
- Weekly: Keep 4 weeks
- Monthly: Keep 6 months

### Backup Configuration

```php
// config/backup.php
'backup' => [
    'source' => [
        'files' => [
            'include' => [base_path()],
            'exclude' => [
                base_path('vendor'),
                base_path('node_modules'),
                storage_path('framework'),
            ],
        ],
        'databases' => ['mysql'],
    ],
    'destination' => [
        'disks' => ['local', 's3'],
    ],
],
```

## Performance Considerations

### Eager Loading (N+1 Prevention)

**Bad:**
```php
$students = Student::all();
foreach ($students as $student) {
    echo $student->class->name; // N+1 queries!
}
```

**Good:**
```php
$students = Student::with('class')->get();
foreach ($students as $student) {
    echo $student->class->name; // Single query
}
```

### Pagination

Always paginate large datasets:
```php
$students = Student::with('class')->paginate(15);
```

### Query Optimization

```php
// Select only needed columns
Student::select('id', 'name', 'email')->get();

// Use chunking for large datasets
Student::chunk(100, function ($students) {
    // Process batch
});
```

### Caching Strategy

```php
// Cache configuration (production)
php artisan config:cache
php artisan route:cache
php artisan view:cache

// Cache permissions (Spatie)
// Auto-cached for 24 hours

// Application cache (Redis recommended)
Cache::remember('students.active', 3600, function () {
    return Student::where('status', 'active')->get();
});
```

## Deployment Architecture

### Deployment Checklist

```bash
# 1. Pull latest code
git pull origin main

# 2. Install dependencies
composer install --optimize-autoloader --no-dev
npm install && npm run build

# 3. Run migrations
php artisan migrate --force

# 4. Cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 5. Sync permissions
php artisan permissions:sync

# 6. Restart services
sudo systemctl restart php8.2-fpm
sudo systemctl restart nginx
```

### Environment-Specific Configs

**Development:**
- `APP_DEBUG=true`
- Detailed error messages
- Query logging enabled

**Production:**
- `APP_DEBUG=false`
- Error logging to files/Sentry
- All caches enabled
- OPcache enabled

## Monitoring & Logging

### Logging

```php
// Application logs
storage/logs/laravel.log

// View logs in terminal
php artisan pail

// Log levels
Log::emergency($message);
Log::alert($message);
Log::critical($message);
Log::error($message);
Log::warning($message);
Log::notice($message);
Log::info($message);
Log::debug($message);
```

### Monitoring Points

- Backup success/failure
- Permission sync operations
- Database query performance
- Application errors
- User authentication events

## Scalability Considerations

### Current Architecture (Monolith)

**Strengths:**
- Simple deployment
- Easy to develop and test
- Direct database access
- No network latency between modules

**When to Scale:**
- Single server can't handle load
- Database becomes bottleneck
- Need independent module deployment

### Future Options

**Horizontal Scaling:**
- Load balancer + multiple app servers
- Shared database + Redis cache
- S3 for shared file storage

**Vertical Scaling:**
- Bigger server (easier for monolith)
- More CPU/RAM
- SSD storage

**Database Optimization:**
- Read replicas
- Query optimization
- Indexing strategy

## References

- Main CLAUDE.md: `/CLAUDE.md`
- Module base class: `modules/ModuleServiceProvider.php`
- Bootstrap config: `bootstrap/app.php`
- Provider registration: `bootstrap/providers.php`
- Permission sync: `app/Console/Commands/SyncPermissionsAndRoles.php`
